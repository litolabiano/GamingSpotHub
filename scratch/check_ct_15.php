<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("SELECT * FROM controller_types WHERE controller_type_id = 15");
print_r($res->fetch_assoc());
