<?php
/**
 * Quick patch: add games to showPage titles map in admin.php
 */
$file = __DIR__ . '/admin.php';
$content = file_get_contents($file);

$old = "        settings: 'Settings', tournaments: 'Tournaments'";
$new = "        settings: 'Settings', tournaments: 'Tournaments', games: 'Games Library'";

if (strpos($content, $new) !== false) {
    echo "Already patched.\n";
} elseif (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "Patched showPage titles map.\n";
} else {
    echo "Target string not found — no change made.\n";
}

$out = shell_exec('c:\\xampp\\php\\php.exe -l "' . $file . '" 2>&1');
echo "Syntax: $out";
