<?php
require_once __DIR__ . '/../includes/db_functions.php';
$tables = ['reservations', 'reservation_reschedules', 'console_types'];
foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}
