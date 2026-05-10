<?php
$conn = new mysqli('localhost', 'root', '', 'gamingspothub');

// 1. Drop the incorrect FK if it exists
$checkFk = $conn->query("
    SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE CONSTRAINT_NAME = 'fk_controllers_ctrlt' AND TABLE_NAME = 'controllers'
");
if ($checkFk && $checkFk->num_rows > 0) {
    $conn->query("ALTER TABLE controllers DROP FOREIGN KEY fk_controllers_ctrlt");
}

// 2. Clean up orphan IDs in controller_types (set to NULL if parent console is gone)
$conn->query("
    UPDATE controller_types ct 
    LEFT JOIN console_types c ON ct.console_type_id = c.console_type_id 
    SET ct.console_type_id = NULL 
    WHERE c.console_type_id IS NULL
");

// 3. Clean up orphan IDs in controllers
$conn->query("
    UPDATE controllers c 
    LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id 
    SET c.console_type_id = NULL 
    WHERE ct.console_type_id IS NULL
");

// 4. Set console_type_id correctly for all controllers based on their type
$conn->query("
    UPDATE controllers c 
    JOIN controller_types ct ON c.controller_type_id = ct.controller_type_id 
    SET c.console_type_id = ct.console_type_id
");

// 5. Add the CORRECT FK to console_types table
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
