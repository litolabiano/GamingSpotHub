<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
echo "RESERVATIONS:\n";
$res = $conn->query('DESCRIBE reservations');
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";

echo "\nGAMING_SESSIONS:\n";
$res = $conn->query('DESCRIBE gaming_sessions');
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
