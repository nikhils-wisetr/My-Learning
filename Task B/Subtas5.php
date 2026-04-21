<?php
//Always use logger for debugging and troubleshooting, instead of using error_log or var_dump, which can be unreliable and difficult to manage in a production environment
$logger = wc_get_logger();

$logger->info('Webhook received', [
    'source' => 'my-plugin',
    'order_id' => $order_id,
]);

//wc_get_logger is a built-in function in WooCommerce that returns an instance of the WC_Logger class, which provides a simple and consistent way to log messages in WooCommerce. You can use the logger to log messages at different levels (e.g. info, warning, error) and to include additional context information (e.g. order ID, customer ID) to help with debugging and troubleshooting. The logs can be viewed in the WooCommerce logs section of the WordPress admin dashboard, or they can be sent to an external logging service for further analysis.

//Add order notes to provide additional information about the order, such as the status of the webhook processing or any errors that occurred. Order notes can be viewed in the order details page in the WordPress admin dashboard, and they can also be included in email notifications to customers or administrators.
$order->add_order_note(
    'Webhook received: ' . $payload_id
);

//logger when order is created, to verify that the order is being created correctly and to check the order details such as the total amount, the items in the order, and any custom meta data that you have added for the gift note
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    wc_get_logger()->info('Order created', [
        'source' => 'checkout-debug',
        'order_id' => $order_id,
        'total' => $order->get_total(),
    ]);
}, 10, 3);


