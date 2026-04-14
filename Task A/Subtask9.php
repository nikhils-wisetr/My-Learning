How WP-Cron Actually Works

    WordPress has NO real background scheduler
    WP-Cron is a simulation using page requests so it waits for a user to visit the site, then uses that request to trigger cron

Execution Flow
    Cron jobs stored in:wp_options → cron option (serialized array)
    On every page load:WordPress checks → any job due?
    If YES:
        Sends non-blocking request to wp-cron.php
        Then wp-cron.php executes scheduled hooks
        Jobs accumulate → run all at once later
Key Problems
    No visitors = no cron
    Tasks delayed
    Multiple missed jobs run together
    Can overload server
    Double execution
    Multiple requests trigger same cron
    Same job runs twice

Registering a Cron Job
    if (!wp_next_scheduled('myplugin_sync_orders')) {
        wp_schedule_event(time(), 'hourly', 'myplugin_sync_orders');
    }
Clear on Deactivation
        wp_clear_scheduled_hook('myplugin_sync_orders');
Define Callback
        add_action('myplugin_sync_orders', function() {
            // your logic
        });


Built-in Schedules-> hourly,twicedaily,daily,weekly
Custom Schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['every_5_min'] = [
        'interval' => 300,
        'display'  => 'Every 5 minutes',
    ];
    return $schedules;
});


Checking Cron Jobs
    Methods - Query Monitor → Scheduled Events
    WP-CLI:wp cron event list
    Disable WP-Cron (Production Best Practice)

For disable
    define('DISABLE_WP_CRON', true);

Then use real cron:
* * * * * wp cron event run --due-now


Use Action Scheduler Instead
    (Used in WooCommerce)
    Uses custom tables
    Persistent queue
    Retry on failure
    Handles long jobs
    Has admin UI

