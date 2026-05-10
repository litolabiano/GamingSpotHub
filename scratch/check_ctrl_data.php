<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("SELECT * FROM controllers LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
