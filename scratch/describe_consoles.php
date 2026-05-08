<?php
require 'includes/db_config.php';
$res = $conn->query("DESCRIBE consoles");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
