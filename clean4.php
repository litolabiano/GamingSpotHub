<?php
$files = ['admin.php'];
foreach($files as $file) {
    if(!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = preg_replace('/[^\w\s\x{00}-\x{7F}]/u', '', $content);
    file_put_contents($file, $content);
    echo "Cleaned $file\n";
}
