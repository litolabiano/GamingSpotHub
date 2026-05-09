<?php
require_once __DIR__ . '/includes/db_config.php';

echo "Adding status and removed_at columns to tournament_participants table...\n";

$queries = [
    "ALTER TABLE tournament_participants ADD COLUMN status ENUM('active', 'removed') NOT NULL DEFAULT 'active' AFTER payment_status",
    "ALTER TABLE tournament_participants ADD COLUMN removed_at DATETIME DEFAULT NULL AFTER status"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Successfully executed: $q\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

echo "Migration complete.\n";
