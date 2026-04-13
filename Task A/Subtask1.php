<?php

$name = "Nikhil";     // string
$age = 25;           // integer
$price = 99.99;      // float
$isActive = true;    // boolean
$items = ["apple", "banana"]; // array

echo $name . "\n";
echo $age . "\n";
echo $price . "\n";
echo $isActive . "\n";
print_r($items);

/*
Output:
Nikhil
25
99.99
1
Array
(
    [0] => apple
    [1] => banana
)
*/

// PHP auto-converts string to number

$a = "10";
$b = 5;

echo $a + $b; 

/*
Output:
15
*/


$user = "0"; 

if ($user) {
    echo "Valid user";
} else {
    echo "Invalid user";
}

/*
Output:
Invalid user
*/


for ($i = 1; $i <= 3; $i++) {
    echo $i . "\n";
}

/*
Output:
1
2
3
*/

$role = "admin";

switch ($role) {
    case "admin":
        echo "Full access";
        break;
    case "user":
        echo "Limited access";
        break;
    default:
        echo "No access";
}

/*
Output:
Full access
*/



$x = 10; //global scope variable 

function test() {
    echo $x; //search for local scope variable
}

test();

/*
Output:
Undefined variable $x
*/



function multiply($a, $b) {
    return $a * $b;
}
// before passing to dunction we need to check the empty or null 
echo multiply(null, 5);

/*
Output:
0
*/


function divide($a, $b) {
    if ($b == 0) {
        throw new Exception("Division by zero");
    }
    return $a / $b;
}

try {
    echo divide(10, 0);
} catch (Exception $e) {
    error_log( $e->getMessage() );
    throw $e; // rethrow if not handled
}

// Catch always show error for better practice of error handling
try {
    divide(10, 0);
} catch (Exception $e) {
    // nothing
}
?>