$file = 'c:\xampp\htdocs\GamingSpotHub\admin_sections\tournaments.php'
$enc  = [System.Text.Encoding]::UTF8
$lines = [System.IO.File]::ReadAllLines($file, $enc)
$keep  = $lines[0..260]
[System.IO.File]::WriteAllLines($file, $keep, $enc)
Write-Host "Trimmed to $($keep.Count) lines"
