<?php
require_once __DIR__ . '/includes/db_config.php';

$sql = "ALTER TABLE consoles MODIFY status ENUM('available', 'in_use', 'maintenance', 'archived') NOT NULL DEFAULT 'available'";

if ($conn->query($sql)) {
    echo "Successfully updated consoles table schema.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
