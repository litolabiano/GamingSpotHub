<?php
require_once 'includes/db_config.php';

echo "Checking reservations table:\n";
$res = $conn->query("DESCRIBE reservations");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error describing reservations: " . $conn->error . "\n";
}
