<?php
/**
 * Plugin Name: Waitlist System
 * Text Domain: waitlist
 */

if (!defined('ABSPATH')) exit;

define('WL_PATH', plugin_dir_path(__FILE__));
define('WL_URL', plugin_dir_url(__FILE__));

foreach ([
    'db',
    'admin',
    'rest',
    'ajax',
    'cron',
    'cli',
    'woocommerce'
] as $file) {
    if( file_exists(WL_PATH . "includes/{$file}.php") ){
        require_once WL_PATH . "includes/{$file}.php";
    }
}

add_action('plugins_loaded', function(){
    WL_DB::init();
    WL_Admin::init();
    WL_REST::init();
    WL_AJAX::init();
    WL_Cron::init();
    WL_WC::init();
    if (defined('WP_CLI') && WP_CLI) {
        WL_CLI::init();
    }
});

register_activation_hook(__FILE__, function(){
    WL_DB::install();
    WL_Cron::schedule();
});

register_deactivation_hook(__FILE__, function(){
    WL_Cron::unschedule();
});