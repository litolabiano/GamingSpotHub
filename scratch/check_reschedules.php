<?php
require_once __DIR__ . '/../includes/db_functions.php';
$res = $conn->query("SELECT * FROM reservation_reschedules WHERE status='pending' ORDER BY created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "\nReschedule ID: {$row['reschedule_id']}\n";
    echo "Res Date: '{$row['new_date']}'\n";
    echo "Res Time: '{$row['new_time']}'\n";
    echo "Status:   {$row['status']}\n";
    echo "Initiated: {$row['initiated_by']}\n";
}
