<?php
require_once 'includes/db_config.php';

echo "Adding controller_id_2 column to reservations table...\n";
$sql = "ALTER TABLE reservations ADD COLUMN controller_id_2 INT(11) NULL DEFAULT NULL AFTER controller_id, ADD INDEX (controller_id_2)";
if ($conn->query($sql)) {
    echo "Column added successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
