<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');

// 1. Drop the incorrect FK
$conn->query("ALTER TABLE controllers DROP FOREIGN KEY fk_controllers_ctrlt");

// 2. Fix the data in console_type_id column (set it to the actual console type ID)
$conn->query("
    UPDATE controllers c 
    JOIN controller_types ct ON c.controller_type_id = ct.controller_type_id 
    SET c.console_type_id = ct.console_type_id
");

// 3. Add the CORRECT FK to console_types table
$conn->query("
    ALTER TABLE controllers 
    ADD CONSTRAINT fk_controllers_console 
    FOREIGN KEY (console_type_id) REFERENCES console_types(console_type_id) 
    ON DELETE SET NULL ON UPDATE CASCADE
");

if ($conn->error) {
    echo "SQL Error: " . $conn->error . "\n";
} else {
    echo "Database schema and data fixed successfully.\n";
}
