<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'restaurant_user');
define('DB_PASS', 'secure_password');
define('DB_NAME', 'restaurant_management');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>