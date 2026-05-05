<?php
require 'includes/db_config.php';
require 'includes/db_functions.php';
$res = $conn->query("SELECT * FROM transactions WHERE session_id = 13");
print_r($res->fetch_all(MYSQLI_ASSOC));
$res2 = $conn->query("SELECT * FROM gaming_sessions WHERE session_id = 13");
print_r($res2->fetch_assoc());
