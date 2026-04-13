<?php
global $wpdb;

// Table name
$table = $wpdb->prefix . 'users';


// 1. SELECT with prepare()

$user_id = 1;

$user = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE ID = %d",
        $user_id
    )
);

echo $user->user_login . "\n";

/*
Output (example):
admin
*/


// 2. INSERT

$data = [
    'user_login' => 'test_user',
    'user_pass'  => '123456',
];

$format = [
    '%s',
    '%s'
];

// %d → integer
// %s → string
// %f → float

$wpdb->insert($table, $data, $format);

echo "User Inserted\n";

/*
Output:
User Inserted
*/


// 3. UPDATE

$update_data = [
    'user_login' => 'updated_user'
];

$where = [
    'ID' => 1
];

$data_format = ['%s'];
$where_format = ['%d'];

$wpdb->update($table, $update_data, $where, $data_format, $where_format);

echo "User Updated\n";

/*
Output:
User Updated
*/