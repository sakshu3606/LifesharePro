<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');   // ⚠️ MUST match setup_database.php
define('DB_NAME', 'lifeshare_db');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die(json_encode([
            "success" => false,
            "message" => "Database connection failed: " . $conn->connect_error
        ]));
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
