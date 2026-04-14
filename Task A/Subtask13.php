<?php


// Hook into init (CRITICAL)
add_action('init', 'myplugin_register_cpt_and_taxonomy');

function myplugin_register_cpt_and_taxonomy() {

    register_post_type('product_review', [
        // Labels (UI text)
        'labels' => [
            'name' => 'Reviews',
            'singular_name' => 'Review',
        ],
        'public' => true,
        // true  → visible in frontend + admin
        // false → hidden from frontend, only internal use
        'publicly_queryable' => true,
        // true  → accessible via URL (?post_type=...)
        // false → cannot be queried on frontend
        'show_ui' => true,
        // true  → visible in admin dashboard
        // false → no admin menu
        'show_in_menu' => true,
        // true  → appears in admin sidebar
        // false → hidden (but can still exist)
        'show_in_rest' => true,
        // true  → Gutenberg editor + REST API enabled
        // false → classic editor only, no /wp-json access

        'has_archive' => true,
        // true  → enables /reviews/ archive page
        // false → no archive page

        'rewrite' => [
            'slug' => 'reviews',
        ],
        // slug = URL structure
        // changing later → requires flush_rewrite_rules()
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'author',
            'custom-fields',
        ],
        // controls fields in editor screen
        'capability_type' => 'post',
        // 'post' → uses default permissions
        // 'product_review' → creates custom caps:
        // edit_product_reviews, delete_product_reviews

        'map_meta_cap' => true,
        // true  → enables meta capability mapping (recommended)
        // false → uses primitive caps directly (less flexible)

        'menu_position' => 5,
        // position in admin sidebar

        'menu_icon' => 'dashicons-star-filled',
        'exclude_from_search' => false,
        // true  → hidden from search results
        // false → included in search

        'hierarchical' => false,
        // true  → behaves like pages (parent/child)
        // false → behaves like posts

    ]);

    register_taxonomy('review_category', 'product_review', [

        'labels' => [
            'name' => 'Review Categories',
        ],

        'public' => true,
        // true  → visible in frontend
        // false → internal use only
        'hierarchical' => true,
        // true  → category-like (parent/child)
        // false → tag-like (flat)

        'show_ui' => true,
        // true  → visible in admin
        // false → hidden
        'show_in_rest' => true,
        // true  → Gutenberg + REST API
        // false → not available in block editor
        'rewrite' => [
            'slug' => 'review-category',
        ],
        'show_admin_column' => true,
        // true  → shows taxonomy column in post list
        // false → hidden
        'query_var' => true,
        // true  → enables query (?review_category=xyz)
        // false → disables query var

    ]);
}

register_activation_hook(__FILE__, function() {
    myplugin_register_cpt_and_taxonomy();
    flush_rewrite_rules();
});