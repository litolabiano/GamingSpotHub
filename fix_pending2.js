const fs = require('fs');

// ── Fix admin.php ──────────────────────────────────────────────────────────
const adminFile = 'c:/xampp/htdocs/GamingSpotHub/admin.php';
let admin = fs.readFileSync(adminFile, 'utf8');

const adminNeedle =
    `    if ($sess['status'] === 'active' && $paidSoFar > 0) {\r\n` +
    `        // For hourly sessions: only flag as pending if there is a REAL shortfall.\r\n` +
    `        // planned_minutes includes free bonus time — use computeHourlySessionBaseCost()\r\n` +
    `        // to get the true base cost (avoids false ₱80 owed on 4hr+1hr-free sessions).\r\n` +
    `        if ($sess['rental_mode'] === 'hourly' && !empty($sess['planned_minutes'])) {\r\n` +
    `            $baseCost = computeHourlySessionBaseCost((int)$sess['planned_minutes']);\r\n` +
    `            $extras   = (float)($sess['approved_extras'] ?? 0);\r\n` +
    `            if ($paidSoFar >= $baseCost + $extras - 0.01) {\r\n` +
    `                continue; // Fully paid — not a pending balance\r\n` +
    `            }\r\n` +
    `        }\r\n` +
    `        // open_time always needs end-of-session payment; unlimited shouldn't reach here\r\n` +
    `        $sess['paid_so_far'] = $paidSoFar;\r\n` +
    `        $pendingSessions[] = $sess;`;

const adminReplacement =
    `    if ($sess['status'] === 'active') {\r\n` +
    `        // For hourly sessions: show in Pending if not fully paid.\r\n` +
    `        // computeHourlySessionBaseCost() reverses the free-bonus so 4hr+1hr-free\r\n` +
    `        // sessions correctly report ₱320 base (not ₱400).\r\n` +
    `        // Include sessions with ₱0 upfront (need to collect full amount at end).\r\n` +
    `        if ($sess['rental_mode'] === 'hourly' && !empty($sess['planned_minutes'])) {\r\n` +
    `            $baseCost = computeHourlySessionBaseCost((int)$sess['planned_minutes']);\r\n` +
    `            $extras   = (float)($sess['approved_extras'] ?? 0);\r\n` +
    `            if ($paidSoFar >= $baseCost + $extras - 0.01) {\r\n` +
    `                continue; // Fully paid — not a pending balance\r\n` +
    `            }\r\n` +
    `        } elseif ($sess['rental_mode'] === 'unlimited') {\r\n` +
    `            continue; // Unlimited: flat rate already handled, skip\r\n` +
    `        }\r\n` +
    `        // open_time always needs end-of-session payment\r\n` +
    `        $sess['paid_so_far'] = $paidSoFar;\r\n` +
    `        $pendingSessions[] = $sess;`;

const adminCount = admin.split(adminNeedle).length - 1;
console.log('admin.php needle count:', adminCount);
if (adminCount === 1) {
    admin = admin.replace(adminNeedle, adminReplacement);
    fs.writeFileSync(adminFile, admin, 'utf8');
    console.log('admin.php fixed!');
} else {
    console.error('admin.php needle not found');
}

// ── Fix live_section.php ───────────────────────────────────────────────────
const liveFile = 'c:/xampp/htdocs/GamingSpotHub/ajax/live_section.php';
let live = fs.readFileSync(liveFile, 'utf8');

// Find and replace the active sessions loop
const liveNeedle =
    `foreach ($recentSessions as $s) {\r\n` +
    `    $paidSoFar = (float)($s['upfront_paid'] ?? 0);\r\n` +
    `    if ($paidSoFar <= 0) continue;  // open_time walk-ins: pay at end, skip\r\n` +
    `    if ($s['rental_mode'] === 'hourly' && !empty($s['planned_minutes'])) {\r\n` +
    `        $baseCost = computeHourlySessionBaseCost((int)$s['planned_minutes']);\r\n` +
    `        $extras   = (float)($s['approved_extras'] ?? 0);\r\n` +
    `        if ($paidSoFar >= $baseCost + $extras - 0.01) continue; // fully paid\r\n` +
    `    }\r\n` +
    `    $s['paid_so_far'] = $paidSoFar;\r\n` +
    `    $pendingSessions[] = $s;\r\n` +
    `}`;

const liveReplacement =
    `foreach ($recentSessions as $s) {\r\n` +
    `    $paidSoFar = (float)($s['upfront_paid'] ?? 0);\r\n` +
    `    if ($s['rental_mode'] === 'hourly' && !empty($s['planned_minutes'])) {\r\n` +
    `        // Only skip if FULLY paid (₱0 upfront = full amount still owed = show)\r\n` +
    `        $baseCost = computeHourlySessionBaseCost((int)$s['planned_minutes']);\r\n` +
    `        $extras   = (float)($s['approved_extras'] ?? 0);\r\n` +
    `        if ($paidSoFar >= $baseCost + $extras - 0.01) continue; // fully paid\r\n` +
    `    } elseif ($s['rental_mode'] === 'unlimited') {\r\n` +
    `        continue; // unlimited flat rate: nothing to collect\r\n` +
    `    }\r\n` +
    `    // open_time and underpaid/unpaid hourly: needs collection\r\n` +
    `    $s['paid_so_far'] = $paidSoFar;\r\n` +
    `    $pendingSessions[] = $s;\r\n` +
    `}`;

const liveCount = live.split(liveNeedle).length - 1;
console.log('live_section.php needle count:', liveCount);
if (liveCount === 1) {
    live = live.replace(liveNeedle, liveReplacement);
    fs.writeFileSync(liveFile, live, 'utf8');
    console.log('live_section.php fixed!');
} else {
    console.error('live_section.php needle not found');
}
