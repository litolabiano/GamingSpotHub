<?php
require_once __DIR__ . '/includes/db_config.php';

echo "Adding seen_by_user column to tournament_participants table...\n";

$query = "ALTER TABLE tournament_participants ADD COLUMN seen_by_user TINYINT(1) DEFAULT 0 AFTER removed_at";

if ($conn->query($query)) {
    echo "Successfully executed: $query\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "Migration complete.\n";
