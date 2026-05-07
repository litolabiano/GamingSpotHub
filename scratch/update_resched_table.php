<?php
require_once __DIR__ . '/../includes/db_config.php';

$res = $conn->query("DESCRIBE reservation_reschedules");
$columns = [];
while($row = $res->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (!in_array('console_type', $columns)) {
    echo "Adding console_type to reservation_reschedules...\n";
    $conn->query("ALTER TABLE reservation_reschedules ADD COLUMN console_type VARCHAR(50) NULL AFTER console_id");
} else {
    echo "console_type already exists.\n";
}

$res2 = $conn->query("DESCRIBE reservation_reschedules");
while($row = $res2->fetch_assoc()) {
    print_r($row);
}
