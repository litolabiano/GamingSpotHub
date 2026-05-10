<?php
$file = 'includes/db_functions.php';
$content = file_get_contents($file);

// Replace for console_types JOINs
$content = str_replace('ON c.console_type_id = ct.type_id', 'ON c.console_type_id = ct.console_type_id', $content);
$content = str_replace('ON r.console_type_id = ct.type_id', 'ON r.console_type_id = ct.console_type_id', $content);
$content = str_replace('SELECT type_id FROM console_types', 'SELECT console_type_id FROM console_types', $content);
$content = str_replace('$ctRow[\'type_id\']', '$ctRow[\'console_type_id\']', $content);
$content = str_replace('WHERE type_id = ?', 'WHERE console_type_id = ?', $content); // might affect both, let's be careful.

// Let's use preg_replace for exact matches
$replacements = [
    '/console_types ct ON c\.console_type_id = ct\.type_id/' => 'console_types ct ON c.console_type_id = ct.console_type_id',
    '/console_types ct ON r\.console_type_id = ct\.type_id/' => 'console_types ct ON r.console_type_id = ct.console_type_id',
    '/SELECT type_id FROM console_types/' => 'SELECT console_type_id FROM console_types',
    '/\$ctRow\[\'type_id\'\]/' => '$ctRow[\'console_type_id\']',
    '/console_types WHERE type_id = \?/' => 'console_types WHERE console_type_id = ?',
    '/UPDATE console_types SET is_archived = 1 WHERE type_id = \?/' => 'UPDATE console_types SET is_archived = 1 WHERE console_type_id = ?',
    '/UPDATE console_types SET is_archived = 0 WHERE type_id = \?/' => 'UPDATE console_types SET is_archived = 0 WHERE console_type_id = ?',
    '/DELETE FROM console_types WHERE type_id = \?/' => 'DELETE FROM console_types WHERE console_type_id = ?',
    '/LEFT JOIN console_types c ON c\.type_id = ct\.console_type_id/' => 'LEFT JOIN console_types c ON c.console_type_id = ct.console_type_id',

    // controller_types
    '/controller_types ctrl_t ON ctrl\.controller_type_id = ctrl_t\.type_id/' => 'controller_types ctrl_t ON ctrl.controller_type_id = ctrl_t.Controller_type_id',
    '/controller_types ctrl2_t ON ctrl2\.controller_type_id = ctrl2_t\.type_id/' => 'controller_types ctrl2_t ON ctrl2.controller_type_id = ctrl2_t.Controller_type_id',
    '/controller_types ct ON ct\.type_id = c\.controller_type_id/' => 'controller_types ct ON ct.Controller_type_id = c.controller_type_id',
    '/UPDATE controller_types SET is_archived = 1 WHERE type_id = \?/' => 'UPDATE controller_types SET is_archived = 1 WHERE Controller_type_id = ?',
    '/UPDATE controller_types SET is_archived = 0 WHERE type_id = \?/' => 'UPDATE controller_types SET is_archived = 0 WHERE Controller_type_id = ?',
    '/DELETE FROM controller_types WHERE type_id = \?/' => 'DELETE FROM controller_types WHERE Controller_type_id = ?',
];

foreach ($replacements as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

file_put_contents($file, $content);
echo "Done replacing in db_functions.php!\n";

$fileAdmin = 'admin_sections/consoles.php';
if (file_exists($fileAdmin)) {
    $contentAdmin = file_get_contents($fileAdmin);
    $contentAdmin = str_replace("['type_id']", "['console_type_id']", $contentAdmin);
    file_put_contents($fileAdmin, $contentAdmin);
    echo "Done replacing in admin_sections/consoles.php!\n";
}

$fileReserve = 'reserve.php';
if (file_exists($fileReserve)) {
    $contentRes = file_get_contents($fileReserve);
    $contentRes = str_replace('cs.type_id', 'cs.console_type_id', $contentRes);
    $contentRes = str_replace('ct.type_id', 'ct.Controller_type_id', $contentRes);
    $contentRes = str_replace('ORDER BY type_id', 'ORDER BY console_type_id', $contentRes);
    file_put_contents($fileReserve, $contentRes);
    echo "Done replacing in reserve.php!\n";
}

$fileCheck = 'ajax/check_unit_availability.php';
if (file_exists($fileCheck)) {
    $contentCheck = file_get_contents($fileCheck);
    $contentCheck = str_replace('ct.type_id AS console_type_id', 'ct.console_type_id AS console_type_id', $contentCheck);
    $contentCheck = str_replace('ON ct.type_id = c.console_type_id', 'ON ct.console_type_id = c.console_type_id', $contentCheck);
    $contentCheck = str_replace('ON ct.type_id = c.controller_type_id', 'ON ct.Controller_type_id = c.controller_type_id', $contentCheck);
    $contentCheck = str_replace('cs.type_id = ct.console_type_id', 'cs.console_type_id = ct.console_type_id', $contentCheck);
    file_put_contents($fileCheck, $contentCheck);
    echo "Done replacing in ajax/check_unit_availability.php!\n";
}

$fileIndex = 'index.php';
if (file_exists($fileIndex)) {
    $contentIndex = file_get_contents($fileIndex);
    $contentIndex = str_replace('ct.type_id', 'ct.console_type_id', $contentIndex);
    file_put_contents($fileIndex, $contentIndex);
    echo "Done replacing in index.php!\n";
}
?>
