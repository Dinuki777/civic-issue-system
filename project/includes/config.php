<?php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'civic_reporting');

// Create MySQLi Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set Character Set
$conn->set_charset("utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/civic-issue-system/project/');

?>