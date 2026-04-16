<!-- WordPress → Action Router → PHP Callback → JSON Response -->
<?php
add_action('wp_ajax_myplugin_save', 'myplugin_ajax_save');
add_action('wp_ajax_nopriv_myplugin_save', 'myplugin_ajax_save');

//Always trigger the same call back 
//No priv for guest users


// in JS 
// jQuery.post(myplugin_vars.ajaxurl, {
//     action: 'myplugin_save',
//     nonce: myplugin_vars.nonce,
//     ....
// }, function(response) {
//     console.log(response);
// });


// What happend now 
// 1. Request hits admin-ajax.php
// 2. WordPress loads
// 3. Reads $_POST['action']
// 4. Finds matching hook:
//    wp_ajax_myplugin_save
// 5. Executes callback
// 6. Sends response


// check_ajax_referer(...) for chcking the nonce
//  if (!current_user_can('edit_shop_orders')) {
//         wp_send_json_error(['message' => 'Not allowed'], 403); it will send error
// }

// wp_send_json_success. send the success code 

wp_localize_script('myplugin-js', 'myplugin_vars', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('myplugin_save_action'),
]); 

// first parameter (id ) will be same as menton in enqueue script