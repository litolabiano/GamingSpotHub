<?php
$file = __DIR__ . '/admin.php';
$content = file_get_contents($file);

// Remove the inline style that forces display:flex;flex-direction:column on the sidebar
// The CSS already handles this
$old = '<div class="sidebar" id="sidebar" style="display:flex;flex-direction:column;">';
$new = '<div class="sidebar" id="sidebar">';

if (strpos($content, $old) === false) {
    die("NOT FOUND\n");
}
file_put_contents($file, str_replace($old, $new, $content));
echo "OK - inline style removed from sidebar div\n";
