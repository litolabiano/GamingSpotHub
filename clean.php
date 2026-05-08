<?php
$file = 'dashboard.php';
$content = file_get_contents($file);

// Replace the UTF-8 box drawing character ─ (U+2500)
$content = str_replace("\xE2\x94\x80", "", $content);

// In case the file literally contains "â”€" (which is the utf8 of the windows-1252 characters)
$content = str_replace("â”€", "", $content);

// Also the user had "Ã¢”â" in the prompt
$content = str_replace("Ã¢”â", "", $content);

file_put_contents($file, $content);
echo "Cleaned!";
