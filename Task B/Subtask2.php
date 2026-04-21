<?php

add_action('woocommerce_checkout_process', function() {
    if ( empty($_POST['billing_phone']) ) {
        wc_add_notice('Phone is required!', 'error');
    }
});

add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (!empty($_POST['custom_field'])) {
        update_order_meta($order_id, '_custom_field', sanitize_text_field($_POST['custom_field']));
    }
});

add_filter('woocommerce_checkout_fields', function($fields) {
    $fields['billing']['billing_first_name']['required'] = false;
    $fields['billing']['custom_field'] = [
        'type' => 'text',
        'label' => 'Custom Field',
        'required' => true,
    ];
    return $fields;
});

add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    if( $new_status === 'completed' ) {
        $order = wc_get_order($order_id);
        $customer_email = $order->get_billing_email();
        wp_mail($customer_email, 'Your order is completed', 'Thank you for your purchase!');
    }
}, 10, 3);

add_action('woocommerce_payment_complete', function($order_id) {
    $order = wc_get_order($order_id);
    $customer_email = $order->get_billing_email();
    wp_mail($customer_email, 'Your payment is completed', 'Thank you for your purchase!');
});

add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id) {
    if ($product_id == 123) {
        wc_add_notice('This product is restricted', 'error');
        return false;
    }
    return $passed;
}, 10, 2);


add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {
        $cart_item['data']->set_price(50); // override price
    }
});

//only for display purposes, not for actual price calculation
//for price update we can use woocommerce_before_calculate_totals hook
add_filter('woocommerce_get_price_html', function($price, $product) {
    return $price . ' (Special Offer)';
}, 10, 2);

add_action('woocommerce_before_add_to_cart_button', function() {
    echo '<p>Custom message before button</p>';
});

add_action('woocommerce_product_query', function($q) {
    $q->set('posts_per_page', 20);
});

add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    if (!empty($values['custom_data'])) {
        $item->add_meta_data('Custom Data', $values['custom_data']);
    }
}, 10, 4);