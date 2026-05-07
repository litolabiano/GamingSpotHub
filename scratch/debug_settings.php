<?php
require 'includes/db_config.php';
$res = $conn->query("SELECT * FROM system_settings WHERE setting_key = 'brevo_api_key'");
if($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "Key found: " . substr($row['setting_value'], 0, 10) . "...";
} else {
    echo "Key NOT found in system_settings.";
}
$res2 = $conn->query("SELECT * FROM system_settings WHERE setting_key = 'sender_email'");
if($res2->num_rows > 0) {
    $row2 = $res2->fetch_assoc();
    echo "\nSender: " . $row2['setting_value'];
} else {
    echo "\nSender NOT found.";
}
