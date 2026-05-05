<?php
require 'includes/db_config.php';
require 'includes/db_functions.php';
$res = addConsole('Test', 'PS5', 'TEST-01', 100);
var_dump($res);
if (!$res) {
    echo "ERROR: " . $conn->error;
}
