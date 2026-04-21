<?php
class WL_WC {
    public static function init(){
        add_action('woocommerce_single_product_summary',[__CLASS__,'add_btn_on_single_page']);
        add_action('woocommerce_product_set_stock_status',[__CLASS__,'set_stock_status'], 10, 2);
        add_action('waitlist_process_notifications',[__CLASS__,'process_notifications'],10,1);
        add_action('woocommerce_store_api_checkout_order_processed', [__CLASS__,'wl_remove_waitlist_on_purchase'], 10, 1);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__,'wl_show_waitlist_in_admin']);
    }

    public static function add_btn_on_single_page(){
        global $product, $wpdb;
        if ( ! $product || $product->is_in_stock() ) {
            return;
        }
        $table = $wpdb->prefix . 'waitlist';
        $product_id = $product->get_id();
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND product_id = %d AND status = %s",
                $user_id,
                $product_id,
                'active'
            ));
            if ( $exists ) {
                echo '<button id="wl-leave" data-product="'.esc_attr__($product_id , 'waitlist').'">'
                    . esc_html__('Leave Waitlist','waitlist') .
                '</button>';
            } else {
                echo '<button id="wl-join" data-product="'.esc_attr__($product_id,'waitlist').'">'
                    . esc_html__('Join Waitlist','waitlist') .
                '</button>';
            }
        } else {

            echo '<button id="wl-join" data-product="'.esc_attr__($product_id,'waitlist').'">'
                . esc_html__('Join Waitlist','waitlist') .
            '</button>';
        }

    }
    public static function set_stock_status($product_id, $status){
        if ( $status === 'instock' ) {
            if ( ! wp_next_scheduled('waitlist_process_notifications', [$product_id]) ) {
                wp_schedule_single_event(time(), 'waitlist_process_notifications', [$product_id]);
            }
        }
    }
    public static function process_notifications($product_id){
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';
        $entries = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE product_id = %d
            AND status = 'active'
            AND notified_at IS NULL
            LIMIT 100",
            $product_id
        ));
        if ( empty($entries) ) {
            return;
        }
        foreach ( $entries as $entry ) {
            $updated = $wpdb->update(
                $table,
                ['notified_at' => current_time('mysql')],
                [
                    'id' => $entry->id,
                    'notified_at' => null
                ]
            );
            if ( $updated ) {
                wp_mail(
                    $entry->email,
                    __('Product Back In Stock', 'waitlist'),
                    sprintf(
                        __('Product ID %d is now available.', 'waitlist'),
                        $entry->product_id
                    )
                );
            }
        }
        wp_schedule_single_event(time() + 10, 'waitlist_process_notifications', [$product_id]);
    }
    public static function wl_remove_waitlist_on_purchase($order) {
        global $wpdb;
        if ($order->get_status() === 'draft') {
            return;
        }
        $table = $wpdb->prefix . 'waitlist';
        $user_id       = $order->get_user_id();
        $billing_email = $order->get_billing_email();
        $waitlist_products = [];
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ( $billing_email && $product_id ) {
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$table} 
                        WHERE email = %s AND product_id = %d LIMIT 1",
                        $billing_email,
                        $product_id
                    )
                );
                if( $exists ) {
                    $waitlist_products[] = $product_id;
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM {$table} 
                            WHERE email = %s AND product_id = %d",
                            $billing_email,
                            $product_id
                        )
                    );
                    $order->add_order_note(
                    sprintf(
                        __('Customer purchased a product they were waitlisted for: %s', 'waitlist'),
                        $product_id
                    ),
                    false
                    );
                }
            }
        }
        if (!empty($waitlist_products)) {
            $order->update_meta_data('_waitlist_products', $waitlist_products);
            $order->save();
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