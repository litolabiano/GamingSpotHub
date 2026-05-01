# Standardize all peso sign representations to the direct ₱ UTF-8 character
# across all PHP files in GamingSpotHub

$dir   = 'c:\xampp\htdocs\GamingSpotHub'
$files = Get-ChildItem $dir -Recurse -Include '*.php','*.js' |
         Where-Object { $_.FullName -notmatch '\\vendor\\|\\PHPMailer\\' }

# ₱ in UTF-8 bytes
$peso  = [System.Text.Encoding]::UTF8.GetString([byte[]](0xE2, 0x82, 0xB1))

# HTML entity forms to replace (in HTML output context)
$entities = @('&#8369;', '&amp;#8369;', '&#x20B1;', '&#X20B1;')

$totalFiles = 0
$totalCount = 0
foreach ($file in $files) {
    $bytes   = [System.IO.File]::ReadAllBytes($file.FullName)
    $text    = [System.Text.Encoding]::UTF8.GetString($bytes)
    $changed = $false
    foreach ($ent in $entities) {
        $count = ([regex]::Matches($text, [regex]::Escape($ent))).Count
        if ($count -gt 0) {
            $text    = $text.Replace($ent, $peso)
            $totalCount += $count
            $changed = $true
        }
    }
    if ($changed) {
        [System.IO.File]::WriteAllText($file.FullName, $text, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed $($file.Name)"
        $totalFiles++
    }
}
Write-Host "`nDone. $totalCount replacements across $totalFiles files."
