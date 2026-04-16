<?php
class WL_DB {

    public static function init(){
        add_action('plugins_loaded',[__CLASS__,'migrate']);
    }

    public static function install(){
        global $wpdb;

        $table = $wpdb->prefix.'waitlist';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(255),
            added_at DATETIME NOT NULL,
            notified_at DATETIME NULL,
            status VARCHAR(20) NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY product_id (product_id)
        ) {$charset};";

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('waitlist_schema_version','1.0');
    }

    public static function migrate(){
        if (get_option('waitlist_schema_version') !== '1.0'){
            self::install();
        }
    }
}