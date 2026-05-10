<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("SHOW COLUMNS FROM controllers LIKE 'console_type_id'");
print_r($res->fetch_assoc());
