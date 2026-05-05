<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
foreach ($files as $file) {
    if ($file->getExtension() === 'php' && $file->getFilename() !== 'replace_status.php') {
        $content = file_get_contents($file->getPathname());
        $changed = false;
        if (strpos($content, "'confirmed'") !== false) {
            $content = str_replace("'confirmed'", "'reserved'", $content);
            $changed = true;
        }
        if (strpos($content, '"confirmed"') !== false) {
            $content = str_replace('"confirmed"', '"reserved"', $content);
            $changed = true;
        }
        // Also look for reservation_confirmed for email/notif types
        if (strpos($content, "'reservation_confirmed'") !== false) {
            $content = str_replace("'reservation_confirmed'", "'reservation_reserved'", $content);
            $changed = true;
        }
        if ($changed) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
