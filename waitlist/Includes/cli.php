<?php
class WL_CLI {

    public static function init(){

        WP_CLI::add_command( 'waitlist export', function ( $args, $assoc ) {

            global $wpdb;
            $table = $wpdb->prefix . 'waitlist';
            $rows  = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );

            if ( empty( $rows ) ) {
                WP_CLI::warning( 'No waitlist entries found.' );
                return;
            }

            $format  = $assoc['format'] ?? 'table';
            $columns = array_keys( $rows[0] );

            if ( $format === 'json' ) {
                WP_CLI::print_value( $rows, [ 'format' => 'json' ] );
            } elseif ( $format === 'csv' ) {
                \WP_CLI\Utils\format_items( 'csv', $rows, $columns );
            } else {
                \WP_CLI\Utils\format_items( 'table', $rows, $columns );
            }
        } );

        WP_CLI::add_command( 'waitlist prune', function ( $args, $assoc ) {

            global $wpdb;
            $days  = isset( $assoc['days'] ) ? absint( $assoc['days'] ) : 90;
            $dry   = isset( $assoc['dry-run'] );
            $table = $wpdb->prefix . 'waitlist';

            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE added_at < NOW() - INTERVAL %d DAY",
                    $days
                )
            );

            if ( $dry ) {
                WP_CLI::success( "Dry run: {$count} rows would be deleted." );
                return;
            }

            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table} WHERE added_at < NOW() - INTERVAL %d DAY",
                    $days
                )
            );

            WP_CLI::success( sprintf( 'Pruned %d rows.', (int) $deleted ) );
        } );
    }
}