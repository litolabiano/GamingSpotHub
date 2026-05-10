<?php
require 'includes/db_config.php';
$r = $conn->query('SHOW COLUMNS FROM reservation_reschedules');
while($row = $r->fetch_assoc()) echo $row['Field'] . "\n";
