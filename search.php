<?php
$lines = file('dashboard.php');
foreach($lines as $i => $l) {
    if(stripos($l, 'reschedule') !== false) {
        echo ($i+1) . ': ' . trim($l) . "\n";
    }
}
