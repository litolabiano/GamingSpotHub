<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("
    SELECT c.controller_id, c.console_type_id, c.controller_type_id 
    FROM controllers c 
    LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id 
    WHERE ct.console_type_id IS NULL AND c.console_type_id IS NOT NULL
");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
