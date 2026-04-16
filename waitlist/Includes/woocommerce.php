<?php
class WL_WC {
    public static function init(){
        add_action('woocommerce_single_product_summary',[__CLASS__,'add_btn_on_single_page']);
        add_action('woocommerce_product_set_stock_status',[__CLASS__,'set_stock_status'], 10, 2);
        add_action('waitlist_process_notifications',[__CLASS__,'process_notifications'],10,1);
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
                echo '<button id="wl-leave" data-product="'.esc_attr__($product_id).'">'
                    . esc_html__('Leave Waitlist','waitlist') .
                '</button>';
            } else {
                echo '<button id="wl-join" data-product="'.esc_attr__($product_id).'">'
                    . esc_html__('Join Waitlist','waitlist') .
                '</button>';
            }
        } else {

            echo '<button id="wl-join" data-product="'.esc_attr__($product_id).'">'
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
}