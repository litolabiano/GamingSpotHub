<?php
$fileAdmin = 'admin.php';
if (file_exists($fileAdmin)) {
    $c = file_get_contents($fileAdmin);
    $c = str_replace('ON c.controller_type_id = ct.type_id', 'ON c.controller_type_id = ct.Controller_type_id', $c);
    $c = str_replace('ON ct.console_type_id = cs.type_id', 'ON ct.console_type_id = cs.console_type_id', $c);
    $c = str_replace('ON c.console_type_id = ct.type_id', 'ON c.console_type_id = ct.console_type_id', $c);
    file_put_contents($fileAdmin, $c);
    echo "Fixed admin.php\n";
}
?>
