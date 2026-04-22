<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$table = $wpdb->prefix . 'waitlist';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

delete_option( 'waitlist_schema_version' );

wp_clear_scheduled_hook( 'wl_prune' );
wp_clear_scheduled_hook( 'waitlist_process_notifications' );
