<?php
$q = new WP_Query([
    'post_type' => 'product_review',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    's' => 'test',
    'no_found_rows' => true,
    // true  → skips COUNT query → faster
    // false → needed for pagination
    'fields' => 'ids',
    // returns only IDs → less memory
    'update_post_meta_cache' => false,
    // skips meta loading
    'update_post_term_cache' => false,
    // skips taxonomy loading
    'meta_query' => [
    [
        'key' => 'rating',
        'value' => 4,
        'compare' => '>=',
        'type' => 'NUMERIC'
    ]
]
]);
while ($q->have_posts()) {
    $q->the_post();
    echo the_title(), the_content();
}
wp_reset_postdata(); 
// To Restores global $post
//Prevents breaking other queries

add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Only target CPT archive
    if (!$query->is_post_type_archive('product_review')) {
        return;
    }

    $query->set('posts_per_page', 20);
    $query->set('meta_key', 'rating');
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'DESC');

});