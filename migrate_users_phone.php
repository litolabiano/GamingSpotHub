<?php
require_once __DIR__ . '/includes/db_config.php';

// First, check if there are any users with NULL phone numbers and set them to an empty string or a placeholder if necessary
// However, the task implies new registrations should be required. 
// For existing data, we might need a placeholder or just ensure they have something if we want to set NOT NULL.
// Let's check for NULLs first.

$res = $conn->query("SELECT COUNT(*) as count FROM users WHERE phone IS NULL");
$row = $res->fetch_assoc();
if ($row['count'] > 0) {
    echo "Found " . $row['count'] . " users with NULL phone numbers. Updating to empty string before setting NOT NULL...\n";
    $conn->query("UPDATE users SET phone = '' WHERE phone IS NULL");
}

$sql = "ALTER TABLE users MODIFY phone VARCHAR(20) NOT NULL";

if ($conn->query($sql)) {
    echo "Successfully updated 'users' table: 'phone' column is now NOT NULL.\n";
} else {
    echo "Error updating table: " . $conn->error . "\n";
}
