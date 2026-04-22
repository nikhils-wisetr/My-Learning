<?php
class WL_DB {

    const SCHEMA_VERSION = '1.1';
    const OPTION_KEY     = 'waitlist_schema_version';

    public static function init() {
        self::migrate();
    }

    public static function install() {
        global $wpdb;

        $table   = $wpdb->prefix . 'waitlist';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            added_at DATETIME NOT NULL,
            notified_at DATETIME NULL,
            status VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_product (email, product_id),
            KEY user_id (user_id),
            KEY product_id (product_id),
            KEY email (email),
            KEY status (status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( self::OPTION_KEY, self::SCHEMA_VERSION );
    }

    public static function migrate() {
        $current = get_option( self::OPTION_KEY );

        if ( $current === self::SCHEMA_VERSION ) {
            return;
        }

        if ( version_compare( (string) $current, '1.1', '<' ) ) {
            self::dedupe_before_unique();
        }

        self::install();
    }

    /**
     * Remove duplicate (email, product_id) rows so the new UNIQUE KEY can be added.
     * Keeps the oldest row per pair.
     */
    private static function dedupe_before_unique() {
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return;
        }

        $wpdb->query(
            "DELETE t1 FROM {$table} t1
             INNER JOIN {$table} t2
             WHERE t1.email = t2.email
               AND t1.product_id = t2.product_id
               AND t1.id > t2.id"
        );
    }
}
