<?php
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
        wp_enqueue_script('wl-js', WL_URL.'assets/js/waitlist.js',['jquery'],null,true);
        wp_localize_script('wl-js','wlData',[
            'ajaxurl'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('waitlist_nonce'),
            'logged_in'=>is_user_logged_in()
        ]);
    }

    public static function render(){
        echo '<div class="wrap"><h1>' . esc_html__( 'Waitlist', 'waitlist' ) . '</h1>';
            $instance = new self(); 
            $instance->prepare_items();
            $instance->views();
            $instance->display();
        echo '</div>';
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
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $query = "SELECT * FROM {$table}";
        $count_query = "SELECT COUNT(*) FROM {$table}";
        if (!empty($status)) {
            $query       .= $wpdb->prepare(" WHERE status = %s", $status);
            $count_query .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
        $items = $wpdb->get_results($query, ARRAY_A);
        $total_items = (int) $wpdb->get_var($count_query);
        $this->items = $items;
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);
        $this->_column_headers = [$this->get_columns(), [], []];
    }
    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '';
    }
    public function get_views() {
        $current = $_GET['status'] ?? '';
        $base_url = admin_url('admin.php?page=waitlist');
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

        $views['removed'] = sprintf(
            '<a href="%s"%s>Removed</a>',
            add_query_arg('status', 'removed', $base_url),
            $current === 'removed' ? ' class="current"' : ''
        );
        return $views;
    }
}