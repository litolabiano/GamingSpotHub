<?php
$files = ['dashboard.php', 'admin.php', 'reserve.php', 'admin_sections/reports.php', 'admin_sections/transactions.php', 'report_activity_logs.php'];
$replacements = [
    'â€“' => '-',
    'â€”' => '-',
    'â€¦' => '...',
    'ðŸŒ€' => '',
    'âš¡' => '',
    'ðŸš¨' => '',
    'ðŸ”§' => '',
    'ðŸ“‹' => '',
    'ðŸ“…' => '',
    'â ³' => '',
    'ðŸ †' => '',
    'â€œ' => '"',
    'â€ ' => '"',
    'â• ' => '=',
    'â• ' => '=',
    'â•' => '=',
    'Ã—' => 'x',
    'â†’' => '->'
];
foreach($files as $f) {
    if(!file_exists(__DIR__ . '/' . $f)) continue;
    $content = file_get_contents(__DIR__ . '/' . $f);
    $new = str_replace(array_keys($replacements), array_values($replacements), $content);
    if($content !== $new) {
        file_put_contents(__DIR__ . '/' . $f, $new);
        echo "Fixed $f\n";
    }
}
echo "Done.\n";
