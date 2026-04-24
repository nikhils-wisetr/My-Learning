<?php
class WL_AJAX {

    const RATE_LIMIT_MAX     = 5;
    const RATE_LIMIT_WINDOW  = 600; // seconds (10 minutes)

    public static function init() {
        add_action( 'wp_ajax_waitlist_join',        [ __CLASS__, 'join' ] );
        add_action( 'wp_ajax_nopriv_waitlist_join', [ __CLASS__, 'join' ] );

        add_action( 'wp_ajax_waitlist_leave',        [ __CLASS__, 'leave' ] );
        add_action( 'wp_ajax_nopriv_waitlist_leave', [ __CLASS__, 'leave' ] );
    }

    public static function join() {
        check_ajax_referer( 'waitlist_nonce', 'nonce' );

        if ( ! is_user_logged_in() && ! self::check_rate_limit() ) {
            wp_send_json_error( [ 'msg' => __( 'Too many requests. Please try again later.', 'waitlist' ) ], 429 );
        }

        global $wpdb;
        $table      = $wpdb->prefix . 'waitlist';
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        if ( ! $product_id || ! wc_get_product( $product_id ) ) {
            wp_send_json_error( [ 'msg' => __( 'Invalid product', 'waitlist' ) ] );
        }

        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $email   = wp_get_current_user()->user_email;
        } else {
            $user_id = 0;
            $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
            if ( empty( $email ) || ! is_email( $email ) ) {
                wp_send_json_error( [ 'msg' => __( 'A valid email is required', 'waitlist' ) ] );
            }
        }

        $already_active = (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE email = %s AND product_id = %d AND status = %s",
            $email, $product_id, 'active'
        ) );

        if ( $already_active ) {
            wp_send_json_error( [ 'msg' => __( 'Already in waitlist', 'waitlist' ) ] );
        }

        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (user_id, product_id, email, added_at, notified_at, status)
             VALUES (%d, %d, %s, %s, NULL, %s)
             ON DUPLICATE KEY UPDATE
                user_id     = VALUES(user_id),
                added_at    = VALUES(added_at),
                notified_at = NULL,
                status      = VALUES(status)",
            $user_id, $product_id, $email, current_time( 'mysql' ), 'active'
        );

        $result = $wpdb->query( $sql );

        if ( $result === false ) {
            self::log_error( 'join:insert_failed', [ 'email' => $email, 'product_id' => $product_id, 'error' => $wpdb->last_error ] );
            wp_send_json_error( [ 'msg' => __( 'Could not join waitlist. Please try again.', 'waitlist' ) ] );
        }

        wp_send_json_success( [ 'msg' => __( 'Joined', 'waitlist' ) ] );
    }

    public static function leave() {
        check_ajax_referer( 'waitlist_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'msg' => __( 'Login required', 'waitlist' ) ] );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( ! $product_id ) {
            wp_send_json_error( [ 'msg' => __( 'Invalid product', 'waitlist' ) ] );
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'waitlist';
        $user_id = get_current_user_id();

        $result = $wpdb->update(
            $table,
            [ 'status' => 'removed' ],
            [ 'user_id' => $user_id, 'product_id' => $product_id, 'status' => 'active' ],
            [ '%s' ],
            [ '%d', '%d', '%s' ]
        );

        if ( $result === false ) {
            self::log_error( 'leave:update_failed', [ 'user_id' => $user_id, 'product_id' => $product_id, 'error' => $wpdb->last_error ] );
            wp_send_json_error( [ 'msg' => __( 'Could not update waitlist. Please try again.', 'waitlist' ) ] );
        }

        wp_send_json_success( [ 'msg' => __( 'Removed', 'waitlist' ) ] );
    }

    /**
     * Transient-backed IP rate limiter. Returns false when the caller is over the limit.
     */
    private static function check_rate_limit() {
        $ip = self::get_client_ip();
        if ( empty( $ip ) ) {
            return true;
        }

        $key   = 'wl_rl_' . md5( $ip );
        $count = (int) get_transient( $key );

        if ( $count >= self::RATE_LIMIT_MAX ) {
            return false;
        }

        set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
        return true;
    }

    private static function get_client_ip() {
        $raw = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
        $ip  = filter_var( $raw, FILTER_VALIDATE_IP );
        return $ip ?: '';
    }

    private static function log_error( $event, array $context = [] ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( $event . ' ' . wp_json_encode( $context ), [ 'source' => 'waitlist' ] );
        }
    }
}
