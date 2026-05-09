<?php
$file = 'admin.php';
if(file_exists($file)) {
    $content = file_get_contents($file);
    $content = preg_replace('/\/\/ [^\w\s]* Live Session Timers [^\w\s]*/u', '// Live Session Timers', $content);
    $content = preg_replace('/\/\/ [^\w\s]* Charts [^\w\s]*/u', '// Charts', $content);
    
    // Catch-all for lines starting with // A...
    $lines = explode("\n", $content);
    foreach($lines as &$line) {
        if(strpos($line, '// A') === 0 && strpos($line, 'Live Session') === false && strpos($line, 'Charts') === false) {
            $line = preg_replace('/A[^\w\s]+/u', '', $line);
        }
    }
    
    file_put_contents($file, implode("\n", $lines));
    echo "Cleaned admin.php safely\n";
}
