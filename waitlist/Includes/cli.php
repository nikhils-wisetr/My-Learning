<?php
class WL_CLI {

    public static function init(){

        WP_CLI::add_command('waitlist export',function($args,$assoc){

            global $wpdb;
            $table = $wpdb->prefix.'waitlist';

            $rows = $wpdb->get_results("SELECT * FROM {$table}",ARRAY_A);

            $format = $assoc['format'] ?? 'table';
            if($format==='json'){
                WP_CLI::print_value($rows);
            } elseif($format==='csv'){
                foreach($rows as $r){
                    WP_CLI::line(implode(',',$r));
                }
            } else {
                \WP_CLI\Utils\format_items('table',$rows,array_keys($rows[0]));
            }

        });

        WP_CLI::add_command('waitlist prune',function($args,$assoc){

            global $wpdb;
            $days = $assoc['days'] ?? 90;
            $dry  = isset($assoc['dry-run']);

            $query = "DELETE FROM {$wpdb->prefix}waitlist 
                      WHERE added_at < NOW() - INTERVAL %d DAY";

            if($dry){
                WP_CLI::success("Dry run complete");
                return;
            }

            $wpdb->query($wpdb->prepare($query,$days));

            WP_CLI::success("Pruned");
        });
    }
}