<?php
require 'includes/db_config.php';
$conn->query("UPDATE reservations SET status = 'reserved' WHERE status = 'pending' AND reservation_id NOT IN (SELECT reservation_id FROM reservation_reschedules WHERE status = 'pending')");
echo 'Updated: ' . $conn->affected_rows;
