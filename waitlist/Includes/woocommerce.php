<?php
class WL_WC {
    public static function init(){
        add_action('woocommerce_single_product_summary',[__CLASS__,'add_btn_on_single_page']);
        add_action('woocommerce_product_set_stock_status',[__CLASS__,'set_stock_status'], 10, 2);
        add_action('waitlist_process_notifications',[__CLASS__,'process_notifications'],10,1);
        add_action('woocommerce_store_api_checkout_order_processed', [__CLASS__,'wl_remove_waitlist_on_purchase'], 10, 1);
        add_action('woocommerce_checkout_order_processed', [__CLASS__,'wl_remove_waitlist_on_purchase_classic'], 10, 3);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__,'wl_show_waitlist_in_admin']);
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
                "SELECT id FROM {$table} WHERE user_id = %d AND product_id = %d AND status = %s",
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
    public static function set_stock_status($product_id, $status){
        if ( $status === 'instock' ) {
            if ( ! wp_next_scheduled('waitlist_process_notifications', [$product_id]) ) {
                wp_schedule_single_event(time(), 'waitlist_process_notifications', [$product_id]);
            }
        }
    }
    public static function process_notifications( $product_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'waitlist';
        $entries = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, email, product_id FROM {$table}
             WHERE product_id = %d AND status = %s AND notified_at IS NULL
             LIMIT 100",
            $product_id, 'active'
        ) );

        if ( empty( $entries ) ) {
            return;
        }

        $product      = wc_get_product( $product_id );
        $product_name = $product ? $product->get_name() : sprintf( __( 'Product #%d', 'waitlist' ), $product_id );
        $product_url  = $product ? get_permalink( $product->get_id() ) : '';

        foreach ( $entries as $entry ) {
            $claimed = $wpdb->query( $wpdb->prepare(
                "UPDATE {$table} SET notified_at = %s
                 WHERE id = %d AND notified_at IS NULL",
                current_time( 'mysql' ), $entry->id
            ) );

            if ( $claimed === false ) {
                self::log( 'error', 'process_notifications:update_failed', [
                    'id'    => $entry->id,
                    'error' => $wpdb->last_error,
                ] );
                continue;
            }

            if ( $claimed && is_email( $entry->email ) ) {
                $subject = __( 'Product Back In Stock', 'waitlist' );
                $body    = sprintf(
                    /* translators: 1: product name, 2: product URL */
                    __( "%1\$s is now available.\n\n%2\$s", 'waitlist' ),
                    $product_name,
                    $product_url
                );
                $sent = wp_mail( $entry->email, $subject, $body );

                if ( ! $sent ) {
                    self::log( 'warning', 'process_notifications:mail_failed', [
                        'id'         => $entry->id,
                        'product_id' => $entry->product_id,
                    ] );
                }
            }
        }

        wp_schedule_single_event( time() + 10, 'waitlist_process_notifications', [ $product_id ] );
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
                "SELECT id FROM {$table}
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
                    esc_html__('This customer was on the waitlist for product: %s', 'waitlist'),
                    esc_html($product->get_name())
                ) . '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }
}