<?php
if (!defined('ABSPATH')) exit;
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
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            added_at DATETIME NOT NULL,
            claimed_at DATETIME NULL,
            notified_at DATETIME NULL,
            retry_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT NULL,
            PRIMARY KEY (id),
            KEY email_product_status (email, product_id, status),
            KEY idx_worker_lookup (status, notified_at, claimed_at),
            KEY idx_claimed_at (claimed_at),
            KEY idx_product (product_id),
            KEY idx_email (email)
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

    private static function dedupe_before_unique() {
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return;
        }

        $wpdb->query(
            "DELETE t1 FROM {$wpdb->prefix}waitlist t1
             INNER JOIN {$wpdb->prefix}waitlist t2
             WHERE t1.email = t2.email
               AND t1.product_id = t2.product_id
               AND t1.id > t2.id"
        );
    }

    public static function uninstall() {
        delete_option( 'waitlist_schema_version' );
        wp_clear_scheduled_hook( 'wl_prune' );
        wp_clear_scheduled_hook( 'waitlist_cron_worker' );
        global $wpdb;
        $table = $wpdb->prefix . 'waitlist';
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}waitlist" );
    }
}