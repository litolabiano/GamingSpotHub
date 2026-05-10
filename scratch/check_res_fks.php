<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $conn->query("
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        CONSTRAINT_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE 
        REFERENCED_TABLE_SCHEMA = 'gamingspothub' 
        AND TABLE_NAME = 'reservations'
");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
