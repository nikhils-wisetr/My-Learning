<?php
class WL_REST {

    public static function init(){
        add_action('rest_api_init',[__CLASS__,'routes']);
    }

    public static function routes(){
        register_rest_route('waitlist/v1','/entries/',[
            'methods'=>'GET',
            'callback'=>[__CLASS__,'get'],
            'permission_callback'=> '__return_true',
        ]);
    }

    // public static function permission($req){
    //     return current_user_can('manage_options') || is_user_logged_in();
    // }

    public static function get($req){
        global $wpdb;
        $table       = $wpdb->prefix . 'waitlist';
        $status      = isset($req['status']) ? sanitize_text_field($req['status']) : '';
        $per_page    = isset($req['per_page']) ? intval($req['per_page']) : 10;
        $page        = isset($req['page']) ? intval($req['page']) : 1;
        $offset      = ($page - 1) * $per_page;
        $query       = "SELECT * FROM {$table}";
        $count_query = "SELECT COUNT(*) FROM {$table}";
        if (!empty($status)) {
            $query       .= $wpdb->prepare(" WHERE status = %s", $status);
            $count_query .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
        $rows   = $wpdb->get_results($query, ARRAY_A);
        $total  = $wpdb->get_var($count_query);
        return new WP_REST_Response([
            'data'  => $rows,
            'total' => (int) $total,
        ], 200);
    }
}