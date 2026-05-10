<?php
$fileConsoles = 'admin_sections/consoles.php';
if (file_exists($fileConsoles)) {
    $c = file_get_contents($fileConsoles);
    $c = str_replace('ctt.type_id', 'ctt.Controller_type_id', $c);
    $c = str_replace('ct.type_id', 'ct.Controller_type_id', $c);
    $c = preg_replace('/console_types\s+cs\s+ON\s+ct\.console_type_id\s*=\s*cs\.type_id/', 'console_types cs ON ct.console_type_id = cs.console_type_id', $c);
    file_put_contents($fileConsoles, $c);
    echo "Fixed consoles.php\n";
}

$fileModals = 'admin_sections/modals.php';
if (file_exists($fileModals)) {
    $c = file_get_contents($fileModals);
    $c = str_replace('ct.type_id = c.controller_type_id', 'ct.Controller_type_id = c.controller_type_id', $c);
    $c = str_replace('cs.type_id = ct.console_type_id', 'cs.console_type_id = ct.console_type_id', $c);
    $c = str_replace('<?= $ct[\'type_id\'] ?>', '<?= $ct[\'Controller_type_id\'] ?>', $c);
    file_put_contents($fileModals, $c);
    echo "Fixed modals.php\n";
}

$fileDbFunctions = 'includes/db_functions.php';
if (file_exists($fileDbFunctions)) {
    $c = file_get_contents($fileDbFunctions);
    $c = str_replace('ct.type_id AS parent_console_name', 'ct.console_type_id AS parent_console_name', $c);
    // Let's just double check if any type_id is left
    file_put_contents($fileDbFunctions, $c);
}

$fileAdmin = 'admin.php';
if (file_exists($fileAdmin)) {
    $c = file_get_contents($fileAdmin);
    $c = str_replace('[\'type_id\']', '[\'Controller_type_id\']', $c);
    file_put_contents($fileAdmin, $c);
    echo "Fixed admin.php\n";
}
?>
