<?php
/**
 * resolve_all_conflicts.php
 * Scans all PHP files in the project and resolves git conflict markers (keeps HEAD).
 */
function resolveConflicts(string $file): array {
    $raw = file_get_contents($file);
    $content = str_replace("\r\n", "\n", $raw);

    preg_match_all('/^<<<<<<</m', $content, $m);
    $count = count($m[0]);
    if ($count === 0) return ['conflicts' => 0, 'status' => 'clean'];

    $pattern = '/^<<<<<<< [^\n]+\n(.*?)^=======\n.*?^>>>>>>> [^\n]+\n/ms';
    $resolved = preg_replace($pattern, '$1', $content);
    if ($resolved === null) return ['conflicts' => $count, 'status' => 'REGEX_FAIL'];

    // Restore original line endings
    $useCrlf = (strpos($raw, "\r\n") !== false);
    if ($useCrlf) $resolved = str_replace("\n", "\r\n", $resolved);

    file_put_contents($file, $resolved);

    preg_match_all('/^<<<<<<</m', str_replace("\r\n","\n",file_get_contents($file)), $m2);
    return ['conflicts' => $count, 'status' => count($m2[0]) === 0 ? 'fixed' : 'partial'];
}

$dir = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$fixed = $errors = $clean = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    // Skip vendor/libs
    if (strpos($path, 'assets') !== false) continue;
    if (strpos($path, 'fix_walkin') !== false) continue;

    $r = resolveConflicts($path);
    if ($r['conflicts'] > 0) {
        $rel = str_replace($dir . DIRECTORY_SEPARATOR, '', $path);
        echo sprintf("%-50s %d conflict(s) → %s\n", $rel, $r['conflicts'], $r['status']);
        if ($r['status'] === 'fixed') $fixed++;
        else $errors++;
    } else {
        $clean++;
    }
}

echo "\nDone. Fixed: $fixed  Errors: $errors  Clean: $clean\n";
