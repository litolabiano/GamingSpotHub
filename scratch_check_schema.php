<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$r1 = $conn->query("DESCRIBE reservations");
echo "RESERVATIONS:\n";
while($row = $r1->fetch_assoc()) echo $row['Field'] . "\n";

$r2 = $conn->query("DESCRIBE reservation_reschedules");
echo "\nRESERVATION_RESCHEDULES:\n";
while($row = $r2->fetch_assoc()) echo $row['Field'] . "\n";
