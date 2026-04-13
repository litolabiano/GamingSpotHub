<?php
/**
 * Good Spot Gaming Hub - Database Configuration
 * 
 * Database connection settings for the management system.
 * Uses MySQLi for secure database operations.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gamingspothub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set character encoding to UTF-8
$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+08:00'");
?>
