<?php
// Update DB credentials as needed
define('DB_HOST','127.0.0.1:3325');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','leave_system');

function db_connect(){
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('DB Connect Error: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
