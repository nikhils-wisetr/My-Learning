<!-- The 3 Golden Rules
Sanitize on input
Escape on output
Authorize every action -->


<?php
// sanitize_text_field 
// Removes HTML tags
// Removes <script> or harmful code
// Strips extra whitespace
// Removes invalid UTF-8
// Converts line breaks/tabs safely
$input = "  <h1>Hello</h1> <script>alert('hack')</script>  ";
$clean = sanitize_text_field($input);
echo $clean;
/*
Output:
Hello alert('hack')
*/


$input = "Hello\n<script>alert('x')</script>";

echo sanitize_textarea_field($input);

/*
Output:
Hello
alert('x')
*/

echo sanitize_email(" test@@gmail..com ");

/*
Output:
test@gmail.com
*/


echo sanitize_title("Hello World! PHP");

/*
Output:
hello-world-php
*/

echo sanitize_user("Nikhil@123");

/*
Output:
nikhil123
*/

echo absint(-10);

/*
Output:
10
*/

echo (int)"10abc";

/*
Output:
10
*/

echo floatval("10.5abc");

/*
Output:
10.5
*/

echo sanitize_file_name("my file@#.jpg");

/*
Output:
my-file.jpg
*/

$html = "<b>Bold</b> <script>alert(1)</script>";

echo wp_kses($html, ['b' => []]);

/*
Output:
<b>Bold</b> alert(1)
*/


$html = "<p>Hello</p><script>alert(1)</script>";

echo wp_kses_post($html);

/*
Output:
<p>Hello</p>alert(1)
*/

echo esc_url_raw("javascript:alert(1)");

/*
Output:
(empty)
*/



echo esc_html("<script>alert('x')</script>");

/*
Output:
&lt;script&gt;alert('x')&lt;/script&gt;
*/

echo '<input value="' . esc_attr('" onfocus="alert(1)') . '">';

/*
Output:
<input value="&quot; onfocus=&quot;alert(1)">
*/


echo '<input value="' . esc_attr('" onfocus="alert(1)') . '">';

/*
Output:
<input value="&quot; onfocus=&quot;alert(1)">
*/


echo esc_url("javascript:alert(1)");

/*
Output:
(empty)
*/

echo "<script>var name = '" . esc_js("John's") . "';</script>";

/*
Output:
<script>var name = 'John\'s';</script>
*/


echo '<textarea>' . esc_textarea("<script>") . '</textarea>';

/*
Output:
<textarea>&lt;script&gt;</textarea>
*/


echo wp_kses_post("<p>Hello</p><script>alert(1)</script>");

/*
Output:
<p>Hello</p>alert(1)
*/


echo number_format_i18n(1000000);

/*
Output (example):
1,000,000
*/


echo esc_html__("Hello <b>World</b>", "td");

/*
Output:
Hello &lt;b&gt;World&lt;/b&gt;
*/


echo '<input value="' . esc_attr__("Hello \"World\"", "td") . '">';

/*
Output:
<input value="Hello &quot;World&quot;">
*/

wp_nonce_field('delete_order_' . $order_id, '_wpnonce');
$url = wp_nonce_url(
    admin_url('admin-post.php?action=delete&id=' . $id),
    'delete_order_' . $id
);
$nonce = wp_create_nonce('myplugin_api');
if (!current_user_can('delete_shop_order', $order_id)) {
    wp_die('Unauthorized', 403);
}

// correct way to upload file 
require_once ABSPATH . 'wp-admin/includes/file.php';

$upload = wp_handle_upload($_FILES['my_file'], ['test_form' => false]);

if (isset($upload['error'])) {
    wp_die(esc_html($upload['error']));
}

// Remote file 
$tmp = download_url($remote_url);

$file = [
    'name' => basename($remote_url),
    'tmp_name' => $tmp
];

wp_handle_sideload($file, ['test_form' => false]);

global $wp_filesystem;

require_once ABSPATH . 'wp-admin/includes/file.php';
WP_Filesystem();

$wp_filesystem->put_contents($path, $content, FS_CHMOD_FILE);