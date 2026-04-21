<?php
// Post type: product_review
// Slug: great-find

// WP checks in this exact order:

// single-product_review-great-find.php   ← most specific
// single-product_review.php
// single.php
// singular.php
// index.php                              ← final fallback

//If you change in single.php nothing happen because wordpress read the single-product_review-great-find.php file first, if it exist, then single-product_review.php, if it exist, then single.php, if it exist, then singular.php, if it exist, then index.php. So if you want to change the template for the great-find product review, you need to change the single-product_review-great-find.php file, if you want to change the template for all product reviews, you need to change the single-product_review.php file, if you want to change the template for all single posts, you need to change the single.php file, if you want to change the template for all singular posts, you need to change the singular.php file, if you want to change the template for all posts, you need to change the index.php file.


//This code snippet is an example of how to use the 'template_include' filter to load a custom template for a specific post type and user role. In this case, we are checking if the current post is a singular 'product_review' and if the current user has the capability to edit posts. If both conditions are true, we are loading a custom template called 'review-editor.php' from the plugin's templates directory. If the custom template does not exist, we are falling back to the default template.
add_filter('template_include', function ($template) {

    if (is_singular('product_review') && current_user_can('edit_posts')) {
        $custom = plugin_dir_path(__FILE__) . 'templates/review-editor.php';

        if (file_exists($custom)) {
            return $custom;
        }
    }

    return $template;
});

