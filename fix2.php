<?php
$file = 'dashboard.php';
$content = file_get_contents($file);
$content = str_replace('â ³', '', $content);
file_put_contents($file, $content);
echo "Fixed.\n";
