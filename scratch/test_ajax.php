<?php
$_POST['controller_type_id'] = 1; // Assuming 1 exists
$_POST['ctrl_unit_number'] = 'TEST-AJAX';
$_POST['hourly_rate'] = 25.00;
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

ob_start();
include 'c:/xampp/htdocs/GamingSpotHub/ajax/add_controller_unit.php';
$output = ob_get_clean();

echo "OUTPUT:\n$output\n";
$json = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON ERROR: " . json_last_error_msg() . "\n";
} else {
    echo "JSON SUCCESS\n";
}
