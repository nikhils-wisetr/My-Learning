<?php
class WL_Cron {

    public static function init(){
        add_action('wl_prune',[__CLASS__,'run']);
    }

    public static function schedule(){
        if(!wp_next_scheduled('wl_prune')){
            wp_schedule_event(time(),'daily','wl_prune');
        }
    }

    public static function unschedule(){
        wp_clear_scheduled_hook('wl_prune');
    }

    public static function run(){
        global $wpdb;
        $table = $wpdb->prefix.'waitlist';
        $wpdb->query(
            "DELETE FROM {$table} 
            WHERE added_at < NOW() - INTERVAL 90 DAY"
        );
    }
}