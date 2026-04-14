<?php

$rating = get_post_meta($id, 'rating', true);
// Output: 5
$rating = get_post_meta($id, 'rating', false);
// Output: [5, 4, 3]
$rating = get_post_meta($id, '', true);
// All meta as array

update_post_meta($id, 'rating', 5);
// No row exists	Adds new
// One row exists	Updates
// Multiple rows	Updates all

update_post_meta($id, 'rating', 5, 4);
// Updates ONLY where value = 4


add_post_meta($id, 'rating', 5);
// Always adds new row

add_post_meta($id, 'rating', 5, true);
// Adds only if key doesn’t exist if unique id true

delete_post_meta($id, 'rating');
// No value	Deletes ALL rows
// With value	Deletes matching rows

delete_post_meta($id, 'rating', 5);
// Delete key with match value 

// Heavy filtering → use custom table because meta query take time to load 

register_post_meta('product_review', 'rating', [
    'type' => 'integer',
    // ensures correct data type
    'description' => 'Rating 1-5',
    'single' => true,
    // true → single value
    // false → multiple values allowed
    'show_in_rest' => true,
    // true → visible in REST API
    // false → hidden

    'sanitize_callback' => 'absint',
    // cleans input

    'auth_callback' => function() {
        return current_user_can('edit_posts');
    },
    // controls access

]);