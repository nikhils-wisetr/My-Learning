→ muplugins_loaded  
→ plugins_loaded  
→ setup_theme  
→ after_setup_theme  
→ init  
→ wp_loaded  
→ wp  
→ template_redirect  
→ wp_head  
→ the_content  
→ wp_footer  
→ shutdown

<?php

if (is_admin()) {
    // allow delete ❌
}
// Correct way 
if (current_user_can('manage_options')) {
    echo "Admin user";
}
// In WordPress, is_admin() returns true during AJAX requests because all AJAX calls are routed through /wp-admin/admin-ajax.php.
// This function checks if the request URI belongs to the admin area, not if the user is an administrator.
// Thus, both frontend and backend AJAX calls trigger this condition, often confusing developers.


// correct way 
if (wp_doing_ajax()) {
    echo "AJAX request";
}

if (wp_doing_cron()) {
    echo "CRON running";
}

if (defined('REST_REQUEST') && REST_REQUEST) {
    echo "REST API request";
}

if (wp_is_json_request()) {
    echo "JSON expected";
}

if (wp_doing_ajax()) {
    echo "AJAX\n";
} elseif (defined('REST_REQUEST') && REST_REQUEST) {
    echo "REST\n";
} elseif (wp_doing_cron()) {
    echo "CRON\n";
} elseif (is_admin()) {
    echo "ADMIN PAGE\n";
} else {
    echo "FRONTEND\n";
}

/*
Outputs:
AJAX
REST
CRON
ADMIN PAGE
FRONTEND
*/