<?php
require 'includes/db_config.php';
$conn->query("ALTER TABLE reservation_reschedules 
ADD COLUMN old_controller_id INT NULL AFTER old_console_type_id, 
ADD COLUMN old_controller_id_2 INT NULL AFTER old_controller_id, 
ADD COLUMN new_controller_id INT NULL AFTER new_console_type_id, 
ADD COLUMN new_controller_id_2 INT NULL AFTER new_controller_id");
echo $conn->error;
