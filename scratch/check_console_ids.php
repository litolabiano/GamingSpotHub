<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("SELECT console_type_id FROM console_types");
while($row = $res->fetch_assoc()) {
    echo $row['console_type_id'] . " ";
}
