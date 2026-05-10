<?php
$file = 'c:/xampp/htdocs/GamingSpotHub/admin_sections/modals.php';
$lines = file($file);
$newLines = [];

$markerPos = -1;
foreach ($lines as $i => $line) {
    if (trim($line) === '=======') {
        $markerPos = $i;
        break;
    }
}

if ($markerPos !== -1) {
    // Keep from line 1 to $markerPos - 1
    // Wait, line 0 is `<<<<<<< HEAD`. So we start from 1.
    for ($i = 1; $i < $markerPos; $i++) {
        $newLines[] = $lines[$i];
    }
    file_put_contents($file, implode("", $newLines));
    echo "Resolved modals.php by accepting HEAD";
} else {
    echo "No marker found. Let's show first 10 lines: <br>";
    for($i=0; $i<min(10, count($lines)); $i++) echo htmlspecialchars($lines[$i]) . "<br>";
}
?>
