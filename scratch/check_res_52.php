<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("SELECT * FROM reservations WHERE reservation_id = 52");
echo "RESERVATION #52:\n";
print_r($res->fetch_assoc());

$res2 = $conn->query("SELECT * FROM gaming_sessions WHERE source_reservation_id = 52");
echo "\nSESSION FROM RES #52:\n";
print_r($res2->fetch_assoc());
