<?php
require_once __DIR__ . '/../includes/db_config.php';
$r = $conn->query('SELECT * FROM reservation_reschedules WHERE reservation_id = 28 AND status = "pending"');
echo "<h3>Reschedule Requests for Reservation #28</h3>";
while($data = $r->fetch_assoc()) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

$r2 = $conn->query("SELECT * FROM reservations WHERE reservation_id = 28");
echo "<h3>Reservation Data #28</h3><pre>";
print_r($r2->fetch_assoc());
echo "</pre>";
