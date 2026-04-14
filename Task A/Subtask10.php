Primitive Capabilities
    Direct capabilities assigned to roles -> edit_posts,manage_options,delete_posts
    These are basic permissions stored in DB

Meta Capabilities
Dynamic capabilities (context-based)-> edit_post,delete_post,edit_user

current_user_can('edit_post', $post_id);
edit_post → edit_posts OR edit_others_posts
map_meta_cap filter is behind that

current_user_can('edit_post'); this is not correct
current_user_can('edit_post', $post_id);

Permissions are object-specific
Without ID → wrong access control

Sometime by using this line of code
current_user_can('manage_options') check the admin level permission but sometime admin have permission to change/edit post but not all admin access

current_user_can('edit_posts');
current_user_can('manage_orders');

current_user_can_for_blog($blog_id, 'edit_posts');
Super Admin-Full control across all sites
Site Admin-Controls only one site

is_super_admin()
Only true for network-level admin