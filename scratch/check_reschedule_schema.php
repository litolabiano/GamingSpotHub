<?php
require_once 'includes/db_config.php';
$res = $conn->query("DESCRIBE reservation_reschedules");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
