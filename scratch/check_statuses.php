<?php
require 'includes/db_config.php';
$res = $conn->query("SELECT DISTINCT status FROM consoles");
while($row = $res->fetch_row()) echo $row[0] . "\n";
echo "--- Members ---\n";
$res = $conn->query("SELECT DISTINCT status, role FROM users");
while($row = $res->fetch_row()) echo $row[0] . " | " . $row[1] . "\n";
