<?php
require_once __DIR__ . '/includes/db_config.php';
$cols = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cancelled_by'")->num_rows;
if ($cols === 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN cancelled_by ENUM('user','admin') NULL DEFAULT NULL, ADD COLUMN refund_issued TINYINT(1) NOT NULL DEFAULT 0");
    echo "Columns added: cancelled_by, refund_issued\n";
} else {
    echo "Columns already exist — skipping.\n";
}
