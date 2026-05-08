<?php
require_once __DIR__ . '/../includes/db_functions.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS console_types (
        type_id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "INSERT IGNORE INTO console_types (type_name) VALUES ('PS5'), ('PS4'), ('Xbox Series X'), ('Xbox Controller');",

    "ALTER TABLE consoles MODIFY COLUMN console_type VARCHAR(50) NOT NULL;",
    "ALTER TABLE reservations MODIFY COLUMN console_type VARCHAR(50) NOT NULL;",
    "ALTER TABLE tournaments MODIFY COLUMN console_type VARCHAR(50) NOT NULL;",
    "ALTER TABLE reservation_cancellations MODIFY COLUMN console_type VARCHAR(50) NOT NULL;"
];

foreach ($queries as $q) {
    try {
        if ($conn->query($q)) {
            echo "Success: " . substr($q, 0, 50) . "...\n";
        } else {
            echo "Error: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}
