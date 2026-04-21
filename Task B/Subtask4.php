<?php
if ( WC()->session ) {
    WC()->session->set('gift_note', 'Happy Birthday');
}
add_action('woocommerce_checkout_create_order', function($order){
    if ( WC()->session ) {
        //instead of saving in session, you can also save in cookie or order meta data, or even in a custom table
        $order->update_meta_data('gift_note', WC()->session->get('gift_note'));
    }
});

//to prevent the gift note from being discounted, we need to make sure that the discounts are calculated sequentially, so that the gift note is added to the order after the discounts are applied
add_filter('woocommerce_calc_discounts_sequentially', '__return_true');

//Always use a unique key for each cart item to prevent merging of items with the same product ID, which can cause issues with the gift note being applied to the wrong item or being discounted
add_filter('woocommerce_add_cart_item_data', function($data){
    $data['unique_key'] = md5(microtime());
    return $data;
});

//For cart totals, we can use the 'woocommerce_cart_calculate_fees' hook to add the gift note as a fee, so that it is included in the total calculation
WC()->cart->get_total('edit'); // reliable total

//prevent the stock from being reduced twice, for example when the order is refunded, we can use a custom meta field to track whether the stock has already been reduced for that order
if ($order->get_meta('_processed')) return;

$order->update_meta_data('_processed', 1);
$order->save();

//For partially refunded orders, we can also check the refunded items and only restock those items that were refunded
add_action('woocommerce_order_refunded', function($order_id) {
    // custom stock logic
});

//cod is only available for customers in India, so we can use the 'woocommerce_available_payment_gateways' filter to conditionally remove the cod gateway for customers outside of India
add_filter('woocommerce_available_payment_gateways', function($gateways) {
    if (WC()->customer->get_country() !== 'IN') {
        unset($gateways['cod']);
    }
    return $gateways;
});

//HPOS is a new order storage system that is designed to improve the performance and scalability of WooCommerce. It is currently in development and is expected to be released in the near future. Once HPOS is released, it will be important to test the gift note functionality with HPOS to ensure that it works correctly and does not cause any issues with the order processing or stock management.
$order->get_id();

wc_get_orders([
    'limit' => 10
]);