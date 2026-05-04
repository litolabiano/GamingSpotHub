/**
 * Global fix: everywhere the codebase multiplies (planned_minutes / 60 * rate)
 * to compute a session's base cost, it incorrectly includes bonus (free) minutes
 * in the billable total.
 *
 * Correct approach:
 *   PHP  → computeTimedCost((int)$planned_minutes)
 *   JS   → _timedCost(plannedMinutes)
 *
 * Both functions walk the bonus cycle (every bp paid min → bf free min) and
 * compute only what the customer actually owes.
 */

const fs = require('fs');

function apply(filePath, fixes) {
    let content = fs.readFileSync(filePath, 'utf8');
    let changed = 0;
    for (const { needle, replacement, label } of fixes) {
        const count = content.split(needle).length - 1;
        if (count === 1) {
            content = content.replace(needle, replacement);
            console.log(`  ✓ ${label}`);
            changed++;
        } else if (count === 0) {
            console.warn(`  ✗ NOT FOUND: ${label}`);
        } else {
            console.warn(`  ✗ AMBIGUOUS (${count}x): ${label}`);
        }
    }
    if (changed > 0) fs.writeFileSync(filePath, content, 'utf8');
    return changed;
}

// ── 1. transactions.php — outstanding balance for active hourly sessions ──────
apply('c:/xampp/htdocs/GamingSpotHub/admin_sections/transactions.php', [{
    label: 'transactions.php: psExpected uses computeTimedCost',
    needle:
        `                    $psExpected = $ps['planned_minutes'] <= 30\n` +
        `                            ? $pr['session_min_charge']\n` +
        `                            : (float)($ps['planned_minutes'] / 60 * $pr['hourly_rate']);`,
    replacement:
        `                    // Use computeTimedCost so bonus-free minutes are not billed.\n` +
        `                    // e.g. planned_minutes=300 (4hr paid + 1hr free) → ₱320, not ₱400.\n` +
        `                    $psExpected = $ps['planned_minutes'] <= 30\n` +
        `                            ? $pr['session_min_charge']\n` +
        `                            : (float)computeTimedCost((int)$ps['planned_minutes']);`,
}]);

// ── 2. db_functions.php — computeRentalFee() overtime base cost ───────────────
apply('c:/xampp/htdocs/GamingSpotHub/includes/db_functions.php', [{
    label: 'db_functions.php: computeRentalFee overtime base uses computeTimedCost',
    needle:
        `                $base_cost = ($planned_minutes <= 30)\n` +
        `                    ? $rules['session_min_charge']\n` +
        `                    : (float) ($planned_minutes / 60 * $rules['hourly_rate']);\n` +
        `                return $base_cost + computeTimedCost($overtime);`,
    replacement:
        `                // computeTimedCost handles the bonus cycle correctly;\n` +
        `                // plain planned/60*rate double-charges bonus-free minutes.\n` +
        `                $base_cost = ($planned_minutes <= 30)\n` +
        `                    ? $rules['session_min_charge']\n` +
        `                    : (float) computeTimedCost((int)$planned_minutes);\n` +
        `                return $base_cost + computeTimedCost($overtime);`,
}]);

// ── 3+4. admin.php — two JS bugs ─────────────────────────────────────────────
apply('c:/xampp/htdocs/GamingSpotHub/admin.php', [
    {
        // _hourlyCost() base calculation
        label: 'admin.php: _hourlyCost base uses _timedCost(planned)',
        needle: `    const base     = planned <= 30 ? minChg : (planned / 60 * rate);`,
        replacement: `    // _timedCost correctly accounts for the bonus-free cycle.\n    const base     = planned <= 30 ? minChg : _timedCost(planned);`,
    },
    {
        // Pay Modal tick — hardcoded 50/80 and plannedMinutes/60*80
        label: 'admin.php: Pay Modal baseCost uses _timedCost + PRICING',
        needle: `        const baseCost  = plannedMinutes <= 30 ? 50 : (plannedMinutes / 60 * 80);`,
        replacement: `        const baseCost  = plannedMinutes <= 30 ? PRICING.session_min_charge : _timedCost(plannedMinutes);`,
    },
]);

console.log('\nAll fixes applied.');
