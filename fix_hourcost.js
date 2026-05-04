const fs = require('fs');
const file = 'c:/xampp/htdocs/GamingSpotHub/admin.php';
let content = fs.readFileSync(file, 'utf8');

// ── Fix 1: The HOURLY block in _renderEndSessionModal ──────────────────────
// Line 2241: base was computed as (plannedMinutes / 60 * rate) which includes
// bonus free minutes. The correct base is upfrontPaid (what was actually charged).
// We also need overtime to be computed relative to the actual paid minutes, not
// plannedMinutes (which includes free bonus minutes).
//
// Strategy: replace the entire hourly block's cost calculation lines.

const needle1 = `    } else if (mode === 'hourly' && plannedMinutes) {\r
        const base    = plannedMinutes <= 30 ? PRICING.session_min_charge : (plannedMinutes / 60 * PRICING.hourly_rate);\r
        const elapsed = Math.floor((Date.now() / 1000) - startTs);\r
        const minutes = Math.floor(elapsed / 60);\r
        const overtime = minutes - plannedMinutes;\r
        const cost    = _hourlyCost(minutes, plannedMinutes) + extras;`;

const replacement1 = `    } else if (mode === 'hourly' && plannedMinutes) {\r
        // Use upfrontPaid as the true base cost (already paid by customer at start).\r
        // This correctly handles bonus-minute promotions (e.g. 4 hrs + 1 free hr)\r
        // where plannedMinutes includes free time that was NOT charged.\r
        const base    = upfrontPaid > 0 ? upfrontPaid : (plannedMinutes <= 30 ? PRICING.session_min_charge : (plannedMinutes / 60 * PRICING.hourly_rate));\r
        const elapsed = Math.floor((Date.now() / 1000) - startTs);\r
        const minutes = Math.floor(elapsed / 60);\r
        const overtime = minutes - plannedMinutes;  // overtime is after ALL booked time (incl. free bonus)\r
        // Cost = base already paid + any overtime charges\r
        const cost    = overtime > 0 ? base + _timedCost(overtime) + extras : base + extras;`;

const count1 = content.split(needle1).length - 1;
console.log('Fix 1 needle occurrences:', count1);
if (count1 === 1) {
    content = content.replace(needle1, replacement1);
    console.log('Fix 1 applied!');
} else {
    console.error('Fix 1 needle not found or ambiguous. Trying CRLF-normalized search...');
    // Try with just \n
    const needle1b = needle1.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    const count1b = content.split(needle1b).length - 1;
    console.log('Fix 1b needle occurrences:', count1b);
    if (count1b === 1) {
        content = content.replace(needle1b, replacement1.replace(/\r\n/g, '\n').replace(/\r/g, '\n'));
        console.log('Fix 1b applied!');
    } else {
        console.error('Fix 1 could not be applied.');
    }
}

fs.writeFileSync(file, content, 'utf8');
console.log('Done.');
