const fs = require('fs');
const file = 'c:/xampp/htdocs/GamingSpotHub/admin.php';
let content = fs.readFileSync(file, 'utf8');

// Replace the prorate-based timeCost calculation with bracket-based billing.
// Bracket size is derived from PRICING settings (not hardcoded),
// so changing session_min_charge or hourly_rate in admin settings auto-adjusts it.
const needle = `            // Prorate cost for time actually used.\n` +
    `            // upfrontPaid covers plannedMinutes; reservationDownpayment is non-refundable.\n` +
    `            const ratePerMin   = plannedMinutes > 0\n` +
    `                                 ? (upfrontPaid - reservationDownpayment) / plannedMinutes\n` +
    `                                 : 0;\n` +
    `            const timeCost     = Math.max(0, Math.round(ratePerMin * elapsedMin * 100) / 100);\n` +
    `            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)\n` +
    `            const nonRefundBase = reservationDownpayment;  // non-refundable portion\n` +
    `            const rawRefund    = upfrontPaid - consumedCost;\n` +
    `            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);\n` +
    `            const hasRefund    = refundAmt > 0;`;

const replacement =
    `            // ── Bracket-based billing ────────────────────────────────────────\n` +
    `            // Bracket size is derived from settings: session_min_charge ÷ hourly_rate × 60\n` +
    `            // e.g. ₱20 min charge ÷ (₱80/hr) × 60 = 15 min per bracket.\n` +
    `            // Changing either rate in Settings auto-adjusts the bracket.\n` +
    `            const bracketMin   = (PRICING.hourly_rate > 0 && PRICING.session_min_charge > 0)\n` +
    `                                 ? Math.round(PRICING.session_min_charge / PRICING.hourly_rate * 60)\n` +
    `                                 : 15;  // fallback 15 min\n` +
    `            // Round elapsed time UP to the next bracket boundary\n` +
    `            // (1–bracketMin min used → billed for bracketMin, etc.)\n` +
    `            const billedMin    = elapsedMin > 0 ? Math.ceil(elapsedMin / bracketMin) * bracketMin : 0;\n` +
    `            // Cost = billed brackets × hourly rate; capped at what was actually paid\n` +
    `            const maxBillable  = Math.max(0, upfrontPaid - reservationDownpayment);\n` +
    `            const timeCost     = Math.min(maxBillable, Math.round(billedMin / 60 * PRICING.hourly_rate * 100) / 100);\n` +
    `            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)\n` +
    `            const nonRefundBase = reservationDownpayment;  // non-refundable portion\n` +
    `            const rawRefund    = upfrontPaid - consumedCost;\n` +
    `            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);\n` +
    `            const hasRefund    = refundAmt > 0;`;

const count = content.split(needle).length - 1;
console.log('Needle occurrences:', count);

if (count === 1) {
    content = content.replace(needle, replacement);
    fs.writeFileSync(file, content, 'utf8');
    console.log('Fix applied successfully!');
} else {
    console.error('ERROR: Needle not found or ambiguous. Count:', count);
}
