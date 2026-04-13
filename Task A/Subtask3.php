<!-- Has a header comment and folder with same name -->
<?php
/*
Plugin Name: My Custom Plugin
Description: Demo plugin
Version: 1.0
*/



// hooks in plain php
$hooks = [
    'actions' => [],
    'filters' => []
];
function add_action($hook_name, $callback, $priority = 10) {
    global $hooks;

    $hooks['actions'][$hook_name][$priority][] = $callback;
}
function do_action($hook_name, ...$args) {
    global $hooks;

    if (!isset($hooks['actions'][$hook_name])) {
        return;
    }

    ksort($hooks['actions'][$hook_name]);

    foreach ($hooks['actions'][$hook_name] as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}
function add_filter($hook_name, $callback, $priority = 10) {
    global $hooks;

    $hooks['filters'][$hook_name][$priority][] = $callback;
}
function apply_filters($hook_name, $value, ...$args) {
    global $hooks;

    if (!isset($hooks['filters'][$hook_name])) {
        return $value;
    }

    ksort($hooks['filters'][$hook_name]);

    foreach ($hooks['filters'][$hook_name] as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $value = call_user_func_array($callback, array_merge([$value], $args));
        }
    }

    return $value;
}


// Action hook
add_action('init', function() {
    echo "Init action 1";
}, 20);

add_action('init', function() {
    echo "Init action 2";
}, 10);

do_action('init');

// Filter hook
add_filter('title', function($title) {
    return $title . " World";
});

add_filter('title', function($title) {
    return strtoupper($title);
}, 20);

$title = apply_filters('title', 'Hello');

echo $title;


// $wpdb is WordPress’s database abstraction layer÷

$results = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts}");

//$result is a object of data

// prepare use for prevent sql injection
$wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID = %d", $user_id);

