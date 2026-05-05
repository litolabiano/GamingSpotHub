<?php
$content = file_get_contents('admin_sections/modals.php');
$lines = explode("\n", $content);
// We want to replace lines 975 to 1000 inclusive. 
// Array is 0-indexed, so lines 975-1000 corresponds to indices 974-999 (26 lines).
array_splice($lines, 974, 26, [
    '            <!-- Extension cost information -->',
    '            <div style="background:rgba(95,133,218,.07);border:1px solid rgba(95,133,218,.2);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#8aa4e8;">',
    '                <i class="fas fa-info-circle"></i> Extension costs for hourly sessions are automatically added to the customer\'s Outstanding Balance and collected at the end of the session.',
    '            </div>'
]);
file_put_contents('admin_sections/modals.php', implode("\n", $lines));
echo "Fixed!\n";
