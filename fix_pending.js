const fs = require('fs');

// ── Fix admin.php pendingSessions filter ──────────────────────────────────
const adminFile = 'c:/xampp/htdocs/GamingSpotHub/admin.php';
let admin = fs.readFileSync(adminFile, 'utf8');

const needle = `    if ($sess['status'] === 'active' && $paidSoFar > 0) {\r\n        // Active session with upfront payment - balance pending at end\r\n        $sess['paid_so_far'] = $paidSoFar;\r\n        $pendingSessions[] = $sess;\r\n    } elseif ($sess['status'] === 'completed'\r\n        && $sess['total_cost'] > 0\r\n        && $paidSoFar < (float)$sess['total_cost']  // paid less than consumed cost\r\n        && $refundedAmount < $paidSoFar              // hasn't been fully refunded back\r\n    ) {\r\n        // Completed session where total paid < total cost - outstanding balance\r\n        // This covers: short payments at session start AND early-ends where\r\n        // consumed cost exceeded the upfront amount (no refund, balance owed).\r\n        $sess['paid_so_far'] = $paidSoFar;\r\n        $pendingSessions[] = $sess;\r\n    }\r\n    // Walk-in sessions where nothing was paid at all (open_time/walk-in): skip.\r\n    // They are fully handled at end-of-session payment.`;

const replacement = `    if ($sess['status'] === 'active' && $paidSoFar > 0) {\r\n        // For hourly sessions: only flag as pending if there is a REAL shortfall.\r\n        // planned_minutes includes free bonus time — use computeHourlySessionBaseCost()\r\n        // to get the true base cost (avoids false ₱80 owed on 4hr+1hr-free sessions).\r\n        if ($sess['rental_mode'] === 'hourly' && !empty($sess['planned_minutes'])) {\r\n            $baseCost = computeHourlySessionBaseCost((int)$sess['planned_minutes']);\r\n            $extras   = (float)($sess['approved_extras'] ?? 0);\r\n            if ($paidSoFar >= $baseCost + $extras - 0.01) {\r\n                continue; // Fully paid — not a pending balance\r\n            }\r\n        }\r\n        // open_time always needs end-of-session payment; unlimited shouldn't reach here\r\n        $sess['paid_so_far'] = $paidSoFar;\r\n        $pendingSessions[] = $sess;\r\n    } elseif ($sess['status'] === 'completed'\r\n        && $sess['total_cost'] > 0\r\n        && $paidSoFar < (float)$sess['total_cost']  // paid less than consumed cost\r\n        && $refundedAmount < $paidSoFar              // hasn't been fully refunded back\r\n    ) {\r\n        // Completed session where total paid < total cost — outstanding balance.\r\n        $sess['paid_so_far'] = $paidSoFar;\r\n        $pendingSessions[] = $sess;\r\n    }\r\n    // Walk-in open_time sessions (nothing paid upfront): handled at end-of-session.`;

const count = admin.split(needle).length - 1;
console.log('admin.php needle count:', count);
if (count === 1) {
    admin = admin.replace(needle, replacement);
    fs.writeFileSync(adminFile, admin, 'utf8');
    console.log('admin.php fixed!');
} else {
    console.error('admin.php needle not found');
}

// ── Fix live_section.php pendingSessions filter ───────────────────────────
const liveFile = 'c:/xampp/htdocs/GamingSpotHub/ajax/live_section.php';
let live = fs.readFileSync(liveFile, 'utf8');

const liveNeedle = `// Pending sessions (used by sessions.php)\r\n$pendingSessions = [];\r\nforeach ($recentSessions as $s) {\r\n    $pendingSessions[] = $s;\r\n}\r\nforeach ($completedSessions as $s) {\r\n    $expected = (float)($s['total_cost'] ?? 0);\r\n    $paid     = (float)($s['amount_paid'] ?? 0);\r\n    if ($expected > 0 && $paid < $expected) {\r\n        $pendingSessions[] = $s;\r\n    }\r\n}`;

const liveReplacement = `// Pending sessions (used by sessions.php)\r\n// Mirrors the filter in admin.php — only includes sessions with a genuine shortfall.\r\n$pendingSessions = [];\r\nforeach ($recentSessions as $s) {\r\n    $paidSoFar = (float)($s['upfront_paid'] ?? 0);\r\n    if ($paidSoFar <= 0) continue;  // open_time walk-ins: pay at end, skip\r\n    if ($s['rental_mode'] === 'hourly' && !empty($s['planned_minutes'])) {\r\n        $baseCost = computeHourlySessionBaseCost((int)$s['planned_minutes']);\r\n        $extras   = (float)($s['approved_extras'] ?? 0);\r\n        if ($paidSoFar >= $baseCost + $extras - 0.01) continue; // fully paid\r\n    }\r\n    $s['paid_so_far'] = $paidSoFar;\r\n    $pendingSessions[] = $s;\r\n}\r\nforeach ($completedSessions as $s) {\r\n    $paidSoFar = (float)($s['upfront_paid'] ?? $s['amount_paid'] ?? 0);\r\n    $expected  = (float)($s['total_cost'] ?? 0);\r\n    $refunded  = (float)($s['refunded_amount'] ?? 0);\r\n    if ($expected > 0 && $paidSoFar < $expected && $refunded < $paidSoFar) {\r\n        $s['paid_so_far'] = $paidSoFar;\r\n        $pendingSessions[] = $s;\r\n    }\r\n}`;

const liveCount = live.split(liveNeedle).length - 1;
console.log('live_section.php needle count:', liveCount);
if (liveCount === 1) {
    live = live.replace(liveNeedle, liveReplacement);
    fs.writeFileSync(liveFile, live, 'utf8');
    console.log('live_section.php fixed!');
} else {
    console.error('live_section.php needle not found. Trying \\n-only version...');
    const liveNeedle2 = liveNeedle.replace(/\r\n/g, '\n');
    const liveCount2 = live.split(liveNeedle2).length - 1;
    console.log('live_section.php LF needle count:', liveCount2);
    if (liveCount2 === 1) {
        live = live.replace(liveNeedle2, liveReplacement.replace(/\r\n/g, '\n'));
        fs.writeFileSync(liveFile, live, 'utf8');
        console.log('live_section.php fixed (LF)!');
    } else {
        console.error('live_section.php could not be fixed automatically');
    }
}
