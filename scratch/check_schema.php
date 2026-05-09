<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query('DESCRIBE reservation_reschedules');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
