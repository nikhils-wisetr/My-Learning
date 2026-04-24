<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class WL_Admin extends WP_List_Table {

    public static function init(){
        add_action('admin_menu',[__CLASS__,'menu']);
        add_action('wp_enqueue_scripts',[__CLASS__,'enqueue']);
    }

    public static function menu(){
        add_menu_page(
            __('Waitlist','waitlist'),
            __('Waitlist','waitlist'),
            'manage_options',
            'waitlist',
            [__CLASS__,'render']
        );
    }

    public static function enqueue($hook){
        wp_enqueue_script('wl-js', WL_URL.'assets/js/waitlist.js',['jquery'],'1.0.0',true);
        wp_localize_script('wl-js','wlData',[
            'ajaxurl'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('waitlist_nonce'),
            'logged_in'=>is_user_logged_in()
        ]);
    }

    public static function render(){
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'waitlist' ) );
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'Waitlist', 'waitlist' ) . '</h1>';
            $instance = new self();
            $instance->prepare_items();
            $instance->views();
            $instance->display();
        echo '</div>';
    }

    private static function get_status_filter() {
        $allowed = [ 'active', 'removed' , 'purchased' ];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
        return in_array( $status, $allowed, true ) ? $status : '';
    }
    public function get_columns() {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'email' => 'Email',
            'product_id' => 'Product',
            'status' => 'Status',
            'added_at' => 'Date',
            'notified_at' => 'Notified At'
        ];
    }
    public function prepare_items() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        global $wpdb;
        $table        = $wpdb->prefix . 'waitlist';
        $per_page     = 10;
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;
        $status       = self::get_status_filter();

        if ( $status !== '' ) {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}waitlist WHERE status = %s LIMIT %d OFFSET %d",
                    $status, $per_page, $offset
                ),
                ARRAY_A
            );
            $total_items = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}waitlist WHERE status = %s", $status )
            );
        } else {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}waitlist LIMIT %d OFFSET %d",
                    $per_page, $offset
                ),
                ARRAY_A
            );
            $total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}waitlist" );
        }

        $this->items = $items;
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);
        $this->_column_headers = [ $this->get_columns(), [], [] ];
    }
    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '';
    }
    public function get_views() {
        $current  = self::get_status_filter();
        $base_url = admin_url( 'admin.php?page=waitlist' );
        $views = [];
        $views['all'] = sprintf(
            '<a href="%s"%s>All</a>',
            $base_url,
            $current === '' ? ' class="current"' : ''
        );

        $views['active'] = sprintf(
            '<a href="%s"%s>Active</a>',
            add_query_arg('status', 'active', $base_url),
            $current === 'active' ? ' class="current"' : ''
        );

        $views['purchased'] = sprintf(
            '<a href="%s"%s>Purchased</a>',
            add_query_arg('status', 'purchased', $base_url),
            $current === 'purchased' ? ' class="current"' : ''
        );

        $views['removed'] = sprintf(
            '<a href="%s"%s>Removed</a>',
            add_query_arg('status', 'removed', $base_url),
            $current === 'removed' ? ' class="current"' : ''
        );
        return $views;
    }
}