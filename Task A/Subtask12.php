
<?php
// get_post( $id ) → Fetch ONE specific post
// WP_Post
$post = get_post(10);
echo $post->post_title;
echo $post->post_content;

$args = [
    'post_type' => 'post',
    'posts_per_page' => 5
];

$posts = get_posts($args);

foreach ($posts as $post) {
    echo $post->post_title;
}

$args = [
    'post_type' => 'post',
    'posts_per_page' => 5
];

$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();

        echo get_the_title();
    }
    wp_reset_postdata();
}

// WP_Query is slow due to 
// Pagination (extra SQL: FOUND_ROWS)
// Filters/hooks
// Complex queries (meta, taxonomy)

// WP_User

// wp_get_current_user()
$current_user = wp_get_current_user(); //Returns the currently logged-in user from session
echo $current_user->user_email;


$user = new WP_User(10);
echo $user->user_login;

$user = get_user_by('email', 'test@example.com');
// Fetches user by:'id' 'email' 'login' 'slug'
// Needs to search by field and loop all the data so it is too slow 


// Difference between WP_User & get_user_by
$user = new WP_User($id); //It hit query on databse without take any step
$user = get_user_by('id', $id); 
// get_user_by('id', $id) -> new WP_User($id) then it will take 2 steps for that so wp_user is faster than get_user_by


// WP_Term
$term = get_term( 5, 'category' );
echo $term->name;
echo $term->slug;

// Fetches one term (category, tag, or custom taxonomy)

$terms = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => false,
    'include' => [1,2,3],
    'orderby' => 'name',
    'order'   => 'ASC',
    'hierarchical' => true
]);

foreach ($terms as $term) {
    echo $term->name;
}

// WP_Comment
$comment = get_comment($comment_id);
echo $comment->comment_author;
echo $comment->comment_content;

$comment = get_comment([ $post_id ]);
get_comment( $id, ARRAY_A ); // associative array
get_comment( $id, ARRAY_N ); // numeric array

// Also we can skip the 1 step 
$query = new WP_Comment_Query([
    'post_id' => 10
]);

new WP_Error( $code, $message, $data );
$error = new WP_Error(
    'user_not_found',
    'User does not exist',
    ['user_id' => 99]
);

$error = new WP_Error();
$error->add('empty_field', 'Field is required');
$error->add('invalid_email', 'Invalid email format');


function some_function(){
    $user = get_user_by('email', 'wrong@email.com');
    if (!$user) {
        return new WP_Error('no_user', 'User not found' , [ 'id' => 1 ]);
    }
}
$result = some_function();
if (is_wp_error($result)) {
    echo $error->get_error_data();
    echo $error->get_error_code();
    echo $error->get_error_message();
} else {
    echo "Success";
}