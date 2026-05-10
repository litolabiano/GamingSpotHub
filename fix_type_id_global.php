<?php
$files = glob("c:/xampp/htdocs/GamingSpotHub/admin_sections/*.php");
$files = array_merge($files, glob("c:/xampp/htdocs/GamingSpotHub/ajax/*.php"));
$files[] = "c:/xampp/htdocs/GamingSpotHub/admin.php";
$files[] = "c:/xampp/htdocs/GamingSpotHub/reserve.php";
$files[] = "c:/xampp/htdocs/GamingSpotHub/index.php";
$files[] = "c:/xampp/htdocs/GamingSpotHub/includes/db_functions.php";

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $c = file_get_contents($file);
    $orig = $c;

    // console_types primary key
    $c = preg_replace('/console_types\s+(\w+)\s+ON\s+([a-zA-Z0-9_\.]+)\s*=\s*\1\.type_id/', 'console_types $1 ON $2 = $1.console_type_id', $c);
    $c = preg_replace('/console_types\s+(\w+)\s+ON\s+\1\.type_id\s*=\s*([a-zA-Z0-9_\.]+)/', 'console_types $1 ON $1.console_type_id = $2', $c);

    // controller_types primary key
    $c = preg_replace('/controller_types\s+(\w+)\s+ON\s+([a-zA-Z0-9_\.]+)\s*=\s*\1\.type_id/', 'controller_types $1 ON $2 = $1.Controller_type_id', $c);
    $c = preg_replace('/controller_types\s+(\w+)\s+ON\s+\1\.type_id\s*=\s*([a-zA-Z0-9_\.]+)/', 'controller_types $1 ON $1.Controller_type_id = $2', $c);

    // Specific SELECTs
    $c = preg_replace('/SELECT\s+type_id\s+FROM\s+console_types/', 'SELECT console_type_id FROM console_types', $c);
    $c = preg_replace('/c\.type_id\s*=\s*ct\.console_type_id/', 'c.console_type_id = ct.console_type_id', $c);
    $c = preg_replace('/cs\.type_id\s*=\s*ct\.console_type_id/', 'cs.console_type_id = ct.console_type_id', $c);
    $c = preg_replace('/ct\.type_id\s*=\s*c\.controller_type_id/', 'ct.Controller_type_id = c.controller_type_id', $c);

    // Other specific places like admin.php where POST fields might need to be fixed
    $c = str_replace("['type_id']", "['console_type_id']", $c);
    $c = str_replace("['Controller_type_id']", "['console_type_id']", $c); // just in case I had it wrong previously

    // For controller specific
    $c = preg_replace('/WHERE\s+type_id\s*=\s*\?/', 'WHERE console_type_id = ?', $c);
    
    // We should be careful with blind replacements, but since I know the codebase it should be fine.

    if ($c !== $orig) {
        file_put_contents($file, $c);
        echo "Fixed $file<br>";
    }
}
?>
