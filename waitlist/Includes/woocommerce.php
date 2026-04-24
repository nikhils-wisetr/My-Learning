<?php
if (!defined('ABSPATH')) exit;
class WL_WC {
    const BATCH_SIZE = 100;
    const CLAIM_TTL  = 3600;
    const RATE_LIMIT = 5; 
    public static function init(){
        add_action('woocommerce_single_product_summary',[__CLASS__,'add_btn_on_single_page']);
        add_action('woocommerce_product_set_stock_status',[__CLASS__,'set_stock_status'], 10, 2);
        add_action('waitlist_cron_worker', [__CLASS__, 'process_notifications_worker']);
        add_action('woocommerce_store_api_checkout_order_processed', [__CLASS__,'wl_remove_waitlist_on_purchase'], 10, 1);
        add_action('woocommerce_checkout_order_processed', [__CLASS__,'wl_remove_waitlist_on_purchase_classic'], 10, 3);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__,'wl_show_waitlist_in_admin']);
        add_action('woocommerce_account_menu_items' , [__CLASS__,'wl_wc_show_waitlist'] );
        add_action('woocommerce_account_my-waitlist_endpoint', [__CLASS__, 'wl_account_endpoint'], 10, 1);
        add_action('init', [__CLASS__, 'wl_account_init']);
        if ( ! wp_next_scheduled('waitlist_cron_worker') ) {
            wp_schedule_event(time(), 'twicedaily', 'waitlist_cron_worker');
        }
    }

    public static function add_btn_on_single_page(){
        global $product, $wpdb;
        if ( ! $product || $product->is_in_stock() ) {
            return;
        }
        $table      = $wpdb->prefix . 'waitlist';
        $product_id = $product->get_id();
        $on_list    = false;

        if ( is_user_logged_in() ) {
            $on_list = (bool) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}waitlist WHERE user_id = %d AND product_id = %d AND status = %s",
                get_current_user_id(), $product_id, 'active'
            ) );
        }

        if ( $on_list ) {
            printf(
                '<button type="button" id="wl-leave" data-product="%d">%s</button>',
                (int) $product_id,
                esc_html__( 'Leave Waitlist', 'waitlist' )
            );
        } else {
            printf(
                '<button type="button" id="wl-join" data-product="%d">%s</button>',
                (int) $product_id,
                esc_html__( 'Join Waitlist', 'waitlist' )
            );
        }
    }

    public static function set_stock_status( $product_id, $status ) {
        if ($status !== 'instock') return;
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}waitlist
                SET notified_at = NULL, claimed_at = NULL
                WHERE product_id = %d
                AND status = %s
                AND (notified_at IS NOT NULL OR claimed_at IS NOT NULL)",
                $product_id,
                'active'
            )
        );
    }

    public static function process_notifications_worker() {
        if ( get_transient('waitlist_worker_lock') ) {
            return;
        }
        set_transient('waitlist_worker_lock', 1, 10 * MINUTE_IN_SECONDS);
        wp_suspend_cache_addition(true);
        $total_processed = 0;
        do {
            $count = self::process_batch_chunk();
            $total_processed += $count;
            wp_cache_flush_runtime();

        } while ( $count === self::BATCH_SIZE );
        self::log('info', 'worker_complete', ['processed' => $total_processed]);
        delete_transient('waitlist_worker_lock');
    }

    private static function process_batch_chunk() {
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';
        $now   = current_time('mysql');
        $claimed = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}waitlist
                 SET claimed_at = %s
                 WHERE id IN (
                    SELECT id FROM (
                        SELECT id FROM {$wpdb->prefix}waitlist
                        WHERE status = %s
                        AND notified_at IS NULL
                        AND (claimed_at IS NULL OR claimed_at < DATE_SUB(%s, INTERVAL %d SECOND))
                        LIMIT %d
                    ) t
                 )",
                $now,
                'active',
                $now,
                self::CLAIM_TTL,
                self::BATCH_SIZE
            )
        );

        if ( ! $claimed ) {
            return 0;
        }

        $entries = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waitlist
                WHERE claimed_at = %s AND notified_at IS NULL",
            $now
        ) );

        if ( empty($entries) ) {
            return 0;
        }

        foreach ( $entries as $entry ) {

            $product = wc_get_product( $entry->product_id );

            if ( ! $product || ! $product->is_in_stock() ) {
                continue;
            }

            $subject = __( 'Product Back In Stock', 'waitlist' );
            $body    = sprintf(
                "%s is now available.\n\n%s",
                $product->get_name(),
                get_permalink( $product->get_id() )
            );

            $sent = false;

            if ( is_email($entry->email) ) {
                $sent = wp_mail($entry->email, $subject, $body);
            }

            if ( $sent ) {
                $wpdb->update(
                    $table,
                    [ 'notified_at' => current_time('mysql') ],
                    [ 'id' => $entry->id ],
                    [ '%s' ],
                    [ '%d' ]
                );
            } else {
                self::log('warning', 'mail_failed', ['id' => $entry->id]);
            }

            // ⏱ Rate limit
            usleep(1000000 / self::RATE_LIMIT);

            unset($entry);
        }

        self::log('info', 'batch_processed', ['count' => count($entries)]);

        return count($entries);
    }

    public static function wl_remove_waitlist_on_purchase_classic( $order_id, $posted_data, $order ) {
        if ( $order instanceof WC_Order ) {
            self::wl_remove_waitlist_on_purchase( $order );
        }
    }

    public static function wl_remove_waitlist_on_purchase( $order ) {
        if ( ! $order instanceof WC_Order || $order->get_status() === 'draft' ) {
            return;
        }

        global $wpdb;
        $table             = $wpdb->prefix . 'waitlist';
        $billing_email     = $order->get_billing_email();
        $waitlist_products = [];

        if ( empty( $billing_email ) ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            if ( ! $product_id ) {
                continue;
            }

            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}waitlist
                 WHERE email = %s AND product_id = %d AND status = %s LIMIT 1",
                $billing_email, $product_id, 'active'
            ) );

            if ( ! $exists ) {
                continue;
            }

            $waitlist_products[] = $product_id;

            $updated = $wpdb->update(
                $table,
                [ 'status' => 'purchased' ],
                [ 'email' => $billing_email, 'product_id' => $product_id, 'status' => 'active' ],
                [ '%s' ],
                [ '%s', '%d', '%s' ]
            );

            if ( $updated === false ) {
                self::log( 'error', 'wl_remove_waitlist_on_purchase:update_failed', [
                    'order_id'   => $order->get_id(),
                    'product_id' => $product_id,
                    'error'      => $wpdb->last_error,
                ] );
            }

            $order->add_order_note(
                sprintf(
                    /* translators: %d: product id */
                    __( 'Customer purchased a product they were waitlisted for: %d', 'waitlist' ),
                    $product_id
                ),
                false
            );
        }

        if ( ! empty( $waitlist_products ) ) {
            $order->update_meta_data( '_waitlist_products', $waitlist_products );
            $order->save();
        }
    }

    private static function log( $level, $event, array $context = [] ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->log( $level, $event . ' ' . wp_json_encode( $context ), [ 'source' => 'waitlist' ] );
        }
    }

    public static function wl_show_waitlist_in_admin($order) {
        $products = $order->get_meta('_waitlist_products');
        if (empty($products)) return;
        echo '<div class="form-field form-field-wide order_data_column">';
        echo '<h4>' . esc_html__('Waitlist Products', 'waitlist') . '</h4>';
        echo '<ul>';
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
               echo '<li>' . sprintf(
					/* translators: %s: Product name */
					esc_html__('This customer was on the waitlist for product: %s', 'waitlist'),
					esc_html( $product->get_name() )
				) . '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }

    public static function wl_account_endpoint() {
        global $wpdb;

        $email = wp_get_current_user()->user_email;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}waitlist
                WHERE email = %s AND status = %s",
                $email,
                'active'
            )
        );

        if ( empty( $items ) ) {
            echo '<p>' . esc_html__( 'No waitlist items.', 'waitlist' ) . '</p>';
            return;
        }

        echo '<ul>';

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->product_id );

            if ( ! $product ) {
                continue;
            }

            $url = add_query_arg(
                [ 'remove_waitlist' => $item->id ],
                wc_get_account_endpoint_url( 'my-waitlist' )
            );

            $url = wp_nonce_url( $url, 'remove_waitlist_action_' . $item->id );

            echo '<li>';
            echo esc_html( $product->get_name() ) . ' ';
            echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Remove', 'waitlist' ) . '</a>';
            echo '</li>';
        }

        echo '</ul>';
    }

    public static function wl_account_init() {
        add_rewrite_endpoint( 'my-waitlist', EP_ROOT | EP_PAGES );
        if ( ! isset( $_GET['remove_waitlist'], $_GET['_wpnonce'] ) ) {
            return;
        }
        $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
        $id    = absint( wp_unslash( $_GET['remove_waitlist'] ) );
        if ( ! wp_verify_nonce( $nonce, 'remove_waitlist_action_' . $id ) ) {
            return;
        }
        global $wpdb;
        $id    = absint( $_GET['remove_waitlist'] );
        $email = wp_get_current_user()->user_email;
        $wpdb->update(
            $wpdb->prefix . 'waitlist',
            [ 'status' => 'removed' ],
            [
                'id'    => $id,
                'email' => $email,
            ],
            [ '%s' ],
            [ '%d', '%s' ]
        );
        wp_safe_redirect( wc_get_account_endpoint_url( 'my-waitlist' ) );
        exit;
    }

    public static function wl_wc_show_waitlist( $items ) {
        $items['my-waitlist'] = __( 'My Waitlist', 'waitlist' );
        return $items;
    }
}