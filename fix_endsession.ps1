$file = 'c:\xampp\htdocs\GamingSpotHub\admin.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# The simple needle: the two lines just before timeCost is first used, that we know exist
$needle = "            // Time Used row: show time-only cost (not extras)`r`n            document.getElementById('endEarlyConsumedCost').textContent = '`u{20b1}' + timeCost.toFixed(2);"

$replacement = "            // Elapsed label for the `"Time Used (Xh YYm)`" row in the modal`r`n" +
    "            const elapsedMin   = Math.floor(elapsed / 60);`r`n" +
    "            const elapsedHrs   = Math.floor(elapsedMin / 60);`r`n" +
    "            const elapsedMRem  = elapsedMin % 60;`r`n" +
    "            const elapsedLabel = (elapsedHrs ? elapsedHrs + 'h ' : '') +`r`n" +
    "                                 String(elapsedMRem).padStart(2, '0') + 'm';`r`n" +
    "            const elapsedEl = document.getElementById('endEarlyElapsedStr');`r`n" +
    "            if (elapsedEl) elapsedEl.textContent = '(' + elapsedLabel + ')';`r`n" +
    "`r`n" +
    "            // Prorate cost for time actually used.`r`n" +
    "            // upfrontPaid covers plannedMinutes; reservationDownpayment is non-refundable.`r`n" +
    "            const ratePerMin   = plannedMinutes > 0`r`n" +
    "                                 ? (upfrontPaid - reservationDownpayment) / plannedMinutes`r`n" +
    "                                 : 0;`r`n" +
    "            const timeCost     = Math.max(0, Math.round(ratePerMin * elapsedMin * 100) / 100);`r`n" +
    "            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)`r`n" +
    "            const nonRefundBase = reservationDownpayment;  // non-refundable portion`r`n" +
    "            const rawRefund    = upfrontPaid - consumedCost;`r`n" +
    "            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);`r`n" +
    "            const hasRefund    = refundAmt > 0;`r`n" +
    "`r`n" +
    "            // Time Used row: show time-only cost (not extras)`r`n" +
    "            document.getElementById('endEarlyConsumedCost').textContent = '`u{20b1}' + timeCost.toFixed(2);"

$count = ($content | Select-String -Pattern ([regex]::Escape($needle)) -AllMatches).Matches.Count
Write-Host "Needle occurrences: $count"

if ($count -eq 1) {
    $newContent = $content.Replace($needle, $replacement)
    [System.IO.File]::WriteAllText($file, $newContent, [System.Text.Encoding]::UTF8)
    Write-Host "Fix applied successfully!"
} elseif ($count -eq 0) {
    Write-Host "ERROR: Needle not found in file."
    # Print lines around 1966 to debug
    $lines = $content -split "`n"
    Write-Host "Lines 1965-1970:"
    for ($i = 1964; $i -le 1970; $i++) {
        Write-Host "$($i+1): $($lines[$i])"
    }
} else {
    Write-Host "ERROR: Needle found $count times (ambiguous). No changes made."
}
