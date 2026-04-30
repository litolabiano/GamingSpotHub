$file = 'c:\xampp\htdocs\GamingSpotHub\admin.php'
$lines = Get-Content $file
$result = @()
$skip = $false
for ($i = 0; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]
    if ($line -match 'Standalone IIFE guard closure') {
        $skip = $true
    }
    if ($skip -and $line -match "document\.addEventListener\('DOMContentLoaded'") {
        $skip = $false
        continue  # skip this line too
    }
    if (!$skip) {
        $result += $line
    }
}
Set-Content $file $result -Encoding UTF8
Write-Host "Done. Lines: $($result.Count)"
