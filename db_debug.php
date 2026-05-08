<?php
$mysqli = new mysqli('localhost', 'root', '', 'gamingspothub');
$res = $mysqli->query('DESCRIBE controllers');
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= print_r($row, true);
}
file_put_contents('db_debug.txt', $out);
