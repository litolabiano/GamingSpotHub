<?php
$file = 'c:/xampp/htdocs/GamingSpotHub/dashboard.php';
$content = file_get_contents($file);

// Fix trophy emoji (ðŸ † -> 🏆 -> <i class="fas fa-trophy"></i>)
$content = str_replace("\xF0\x9F\x8F\x86", '<i class="fas fa-trophy"></i>', $content);
// Also match the specific misinterpreted string if it's double-encoded
$content = str_replace('ðŸ †', '<i class="fas fa-trophy"></i>', $content);

// Fix hourglass emoji (â ³ -> ⏳ -> <i class="fas fa-hourglass-half"></i>)
$content = str_replace("\xE2\x8C\x9B", '<i class="fas fa-hourglass-half" style="margin-right:5px;"></i>', $content);
$content = str_replace('â ³', '<i class="fas fa-hourglass-half" style="margin-right:5px;"></i>', $content);

file_put_contents($file, $content);
echo "Fixed misinterpreted emojis in $file\n";
?>
