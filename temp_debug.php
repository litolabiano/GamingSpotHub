<?php
require 'includes/db_connect.php';
$res = $conn->query("SELECT reservation_id, with_controller, controller_id, controller_fee FROM reservations ORDER BY reservation_id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
