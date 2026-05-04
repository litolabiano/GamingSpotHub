const fs = require('fs');
const file = 'c:/xampp/htdocs/GamingSpotHub/admin.php';
let content = fs.readFileSync(file, 'utf8');

// Simple ASCII-safe needle — unique in the file
const needle = "// Time Used row: show time-only cost (not extras)\r\n            document.getElementById('endEarlyConsumedCost')";

const count = (content.split(needle).length - 1);
console.log('Needle occurrences:', count);

if (count !== 1) {
    console.error('ERROR: needle not found or ambiguous.');
    process.exit(1);
}

const insertion = `// Elapsed label for the "Time Used (Xh YYm)" row in the modal
            const elapsedMin   = Math.floor(elapsed / 60);
            const elapsedHrs   = Math.floor(elapsedMin / 60);
            const elapsedMRem  = elapsedMin % 60;
            const elapsedLabel = (elapsedHrs ? elapsedHrs + 'h ' : '') +
                                 String(elapsedMRem).padStart(2, '0') + 'm';
            const elapsedEl = document.getElementById('endEarlyElapsedStr');
            if (elapsedEl) elapsedEl.textContent = '(' + elapsedLabel + ')';

            // Prorate cost for time actually used.
            // upfrontPaid covers plannedMinutes; reservationDownpayment is non-refundable.
            const ratePerMin   = plannedMinutes > 0
                                 ? (upfrontPaid - reservationDownpayment) / plannedMinutes
                                 : 0;
            const timeCost     = Math.max(0, Math.round(ratePerMin * elapsedMin * 100) / 100);
            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)
            const nonRefundBase = reservationDownpayment;  // non-refundable portion
            const rawRefund    = upfrontPaid - consumedCost;
            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);
            const hasRefund    = refundAmt > 0;

            // Time Used row: show time-only cost (not extras)
            document.getElementById('endEarlyConsumedCost')`;

content = content.replace(needle, insertion);
fs.writeFileSync(file, content, 'utf8');
console.log('Fix applied successfully!');
