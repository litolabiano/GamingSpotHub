<?php
require_once 'includes/db_functions.php';
global $conn;
$res = $conn->query("SHOW COLUMNS FROM console_types");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "console_types: " . $row['Field'] . "<br>\n";
    }
}
$res2 = $conn->query("SHOW COLUMNS FROM controller_types");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        echo "controller_types: " . $row['Field'] . "<br>\n";
    }
}
?>
