<?php
class WL_AJAX {

    public static function init(){
        add_action('wp_ajax_waitlist_join',[__CLASS__,'join']);
        add_action('wp_ajax_nopriv_waitlist_join',[__CLASS__,'join']);

        add_action('wp_ajax_waitlist_leave',[__CLASS__,'leave']);
        add_action('wp_ajax_nopriv_waitlist_leave',[__CLASS__,'leave']);
    }

    public static function join(){
        check_ajax_referer('waitlist_nonce','nonce');
        global $wpdb;
        $table = $wpdb->prefix.'waitlist';
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        if ( ! $product_id ) {
            wp_send_json_error(['msg'=>__('Invalid product','waitlist')]);
        }
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $email   = wp_get_current_user()->user_email;
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND product_id = %d AND status = %s",
                $user_id,
                $product_id,
                'active'
            ));
        } else {
            $user_id = 0;
            $email   = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            if ( empty($email) ) {
                wp_send_json_error(['msg'=>__('Email required','waitlist')]);
            }
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE email = %s AND product_id = %d AND status = %s",
                $email,
                $product_id,
                'active'
            ));
        }
        if ( $exists ) {
            wp_send_json_error(['msg'=>__('Already in waitlist','waitlist')]);
        }
        $wpdb->insert(
            $table,
            [
            'user_id'    => $user_id,
            'product_id' => $product_id,
            'email'      => $email,
            'added_at'   => current_time('mysql'),
            'status'     => 'active'
            ],
            [ '%d', '%d', '%s', '%s', '%s' ]
        );
        wp_send_json_success(['msg'=>__('Joined','waitlist')]);
    }
    public static function leave(){

        check_ajax_referer('waitlist_nonce','nonce');

        if ( ! is_user_logged_in() ) {
            wp_send_json_error(['msg'=>__('Login required','waitlist')]);
        }

        global $wpdb;
        $table = $wpdb->prefix.'waitlist';

        $user_id = get_current_user_id();
        $product_id = absint($_POST['product_id']);

        $wpdb->update(
            $table,
            ['status'=>'removed'],
            [
                'user_id'=>$user_id,
                'product_id'=>$product_id
            ],
            [ '%s'],
            [ '%d' , '%d' ]
        );

        wp_send_json_success(['msg'=>__('Removed','waitlist')]);
    }
}