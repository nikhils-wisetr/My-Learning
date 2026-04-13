<?php
/*
Plugin Name: Subtask 4 Demo
*/

// ✅ ACTION: Runs early when WordPress initializes
add_action('init', 'my_function');

function my_function() {
    echo "INIT HOOK FIRED\n";
}


// ✅ ACTION: Load CSS/JS on frontend
add_action('wp_enqueue_scripts', 'load_assets');

function load_assets() {
    wp_enqueue_script('id' , 'path' , ['depanedncy'] , version , source( true / false ));
}


// ✅ ACTION: WooCommerce checkout validation
add_action('woocommerce_checkout_process', 'validate_checkout');

function validate_checkout() {
    echo "CHECKOUT VALIDATION RUNNING\n";
}


// ✅ FILTER: Modify post content
add_filter('the_content', 'modify_content');

function modify_content($content) {
    return $content . "\n[Content Modified]";
}


// ❗ NOTE: woocommerce_cart_totals is NOT a filter (it's an action in WooCommerce UI)
// Correct example using a real filter:
add_filter('woocommerce_cart_total', 'modify_totals');

function modify_totals($total) {
    return $total . " (Updated)";
}


/*
Expected Flow (Simplified Output Order):

INIT HOOK FIRED
ASSETS LOADED

(When viewing a post)
Original Content
[Content Modified]

(When viewing cart)
$100 → $100 (Updated)

(At checkout submit)
CHECKOUT VALIDATION RUNNING
*/