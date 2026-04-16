<!-- 1. Request hits /wp-json/
2. WordPress loads
3. init runs
4. rest_api_init runs  ← IMPORTANT
5. Routes registered
6. Router matches URL
7. Args validated
8. permission_callback runs
9. callback runs
10. Response returned -->

<?php
add_action('rest_api_init', function() {

    register_rest_route('nikhil/v1', '/orders/(?P<id>\d+)', [

        [
            'methods'  => 'GET',
            'callback' => 'nikhil_get_order',

            'permission_callback' => 'myplugin_can_read_order',

            'args' => [
                'id' => [
                    'required' => true,

                    'validate_callback' => function($value) {
                        return is_numeric($value) && $value > 0;
                    },

                    'sanitize_callback' => 'absint',
                ],
            ],
        ],

    ]);
});

function myplugin_can_read_order( $request ){
    $id = (int) $request['id'];
    return current_user_can('read_shop_order', $id);
}
function myplugin_get_order($request) {

    $id = (int) $request['id'];

    $order = wc_get_order($id);

    if (!$order) {
        return new WP_Error(
            'order_not_found',
            'Order not found',
            ['status' => 404]
        );
    }

    return new WP_REST_Response([
        'id' => $order->get_id(),
        'total' => $order->get_total(),
    ], 200);
}

add_filter('rest_prepare_post', function($response, $post) {

    $data = $response->get_data();

    $data['custom'] = 'value';

    $response->set_data($data);

    return $response;

}, 10, 2);

add_filter('rest_prepare_post', 'modify_post_response', 10, 3);
// 👉 This filter runs when WordPress prepares the response object for a post.

// rest_pre_dispatch is also there
// Modify post response
// ✔ Add/remove fields

// rest_post_dispatch
// Just before output