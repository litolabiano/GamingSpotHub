<?php
require_once __DIR__ . '/../includes/db_functions.php';
$res = $conn->query("SHOW TABLES LIKE 'console_types'");
if ($res->num_rows > 0) {
    echo "Table console_types exists.\n";
    $types = getConsoleTypes();
    print_r($types);
} else {
    echo "Table console_types does NOT exist.\n";
}
