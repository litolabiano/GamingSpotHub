<?php
$file = 'dashboard.php';
$content = file_get_contents($file);

// Clean up weird characters before "Action Required:"
$content = preg_replace('/[^\w\s\x{00}-\x{7F}]+\s*Action Required:/u', 'Action Required:', $content);
$content = preg_replace('/[^\w\s\x{00}-\x{7F}]+\s*Your Reservation Has Been/u', 'Your Reservation Has Been', $content);

file_put_contents($file, $content);
echo "Cleaned dashboard.php\n";
