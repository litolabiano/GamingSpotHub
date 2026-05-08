<?php
require_once __DIR__ . '/../includes/db_config.php';
$r = $conn->query('DESCRIBE reservations');
echo "<table border=1>";
while($f=$r->fetch_assoc()) {
    echo "<tr><td>" . implode("</td><td>", $f) . "</td></tr>";
}
echo "</table>";

$r = $conn->query('DESCRIBE reservation_reschedules');
echo "<h3>Reschedule Table</h3><table border=1>";
while($f=$r->fetch_assoc()) {
    echo "<tr><td>" . implode("</td><td>", $f) . "</td></tr>";
}
echo "</table>";
