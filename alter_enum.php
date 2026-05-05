<?php
require 'includes/db_config.php';
$sql = "ALTER TABLE consoles MODIFY console_type ENUM('PS5', 'PS4', 'Xbox Series X', 'Xbox Controller') NOT NULL;";
if ($conn->query($sql)) {
    echo "Success";
} else {
    echo "Error: " . $conn->error;
}
