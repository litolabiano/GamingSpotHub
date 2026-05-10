<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("SELECT * FROM controller_types WHERE controller_type_id IN (101,102,103)");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
