Question	If answer is…
How many rows?	Large → custom table
How often written?	Frequent → custom table
How often queried?	Frequent → indexed table
Need filtering/search?	Yes → custom table



For options table

    Key-value store (site-wide config)

    Good for:
    update_option('site_color', 'blue');
    Bad for:
    Per-user data
    Logs
    Orders
    Large arrays

    Critical Concept: Autoload
    Some options load on every request
    add_option('my_data', $big_array, '', 'yes'); // ❌ autoload
    Result:Memory bloat , Slow site

    So Rule - Options = small, global config only

post_meta
    Key-value attached to posts

    Good:
    update_post_meta($post_id, 'price', 100);
    Bad:
    Thousands of meta rows per post
    Complex filtering & slow meta_query

    Not indexed properly → full table scan

user_meta
    Same as post_meta but for users

    Same problems:
    Not scalable & Slow queries

Transients
    Temporary cached data
    set_transient('api_data', $data, 3600);


With Redis/Memcached:
    Stored in memory
    Fast
    Auto-expire

    Transients = cache only, not storage

Object Cache
    wp_cache_set('key', $data);
    wp_cache_get('key');

Custom Tables (IMPORTANT)

👉 Use when:

    Large data
    Frequent queries
    Need indexes
    Orders
    Logs
    Events
    Analytics

    function myplugin_install_table() {
    global $wpdb;

    $table = $wpdb->prefix . 'myplugin_events';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT UNSIGNED NOT NULL,
        event_type VARCHAR(64) NOT NULL,
        payload LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY event_type (event_type)
    ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            update_option('myplugin_schema_version', '1.2');
        } else {
            error_log('Table creation failed');
        }
    }

    $wpdb->get_charset_collate() use for same character set and collation as the WordPress database
    dbDelta($sql) - create OR update database tables

