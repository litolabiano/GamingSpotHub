<?php
/**
 * DEBUG PAGE — Diagnoses why Transactions / Reports / Settings show blank.
 * Access: http://localhost/GamingSpotHub/debug_admin.php
 * DELETE THIS FILE after debugging!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/includes/db_functions.php';

$user = getCurrentUser();
$errors = [];
$warnings = [];
$ok = [];

// ═══════════════════════════════════════════════════════════════════════════
// 1. DATABASE CHECKS
// ═══════════════════════════════════════════════════════════════════════════
global $conn;

// Check connection
if (!$conn || $conn->connect_error) {
    $errors[] = "DB connection failed: " . ($conn ? $conn->connect_error : 'null $conn');
} else {
    $ok[] = "DB connection OK (server: {$conn->server_info})";
}

// Check transactions table
$tRes = $conn->query("SELECT COUNT(*) AS cnt FROM transactions");
if ($tRes) {
    $tCount = $tRes->fetch_assoc()['cnt'];
    $ok[] = "transactions table: {$tCount} rows";
} else {
    $errors[] = "transactions table query failed: " . $conn->error;
}

// Check the specific JOIN query that was causing issues
$joinRes = $conn->query(
    "SELECT t.transaction_id, t.session_id, t.user_id, u.full_name,
            COALESCE(c.unit_number, '—') AS unit_number,
            COALESCE(gs.rental_mode, 'refund') AS rental_mode
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
     LEFT JOIN consoles c ON gs.console_id = c.console_id
     ORDER BY t.transaction_date DESC LIMIT 5"
);
if ($joinRes) {
    $joinRows = $joinRes->fetch_all(MYSQLI_ASSOC);
    $ok[] = "Transaction JOIN query OK — {$joinRes->num_rows} rows returned";
    $nullSessionRows = array_filter($joinRows, fn($r) => $r['session_id'] === null);
    if (count($nullSessionRows) > 0) {
        $warnings[] = "Found " . count($nullSessionRows) . " transaction(s) with NULL session_id (reservation refunds)";
    }
} else {
    $errors[] = "Transaction JOIN query FAILED: " . $conn->error;
}

// Check financial stats
$finRes = $conn->query("SELECT COUNT(*) AS cnt, SUM(amount) AS total FROM transactions WHERE payment_status='completed'");
if ($finRes) {
    $fin = $finRes->fetch_assoc();
    $ok[] = "Financial stats: {$fin['cnt']} completed transactions, ₱" . number_format($fin['total'] ?? 0, 2);
} else {
    $errors[] = "Financial stats query FAILED: " . $conn->error;
}

// Check console usage report
$usageRes = $conn->query(
    "SELECT c.unit_number, c.console_type, COUNT(gs.session_id) AS total_sessions
     FROM consoles c
     LEFT JOIN gaming_sessions gs ON c.console_id = gs.console_id
     GROUP BY c.console_id LIMIT 5"
);
if ($usageRes) {
    $ok[] = "Console usage query OK — {$usageRes->num_rows} rows";
} else {
    $errors[] = "Console usage query FAILED: " . $conn->error;
}

// Check settings
$settingsKeys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','session_min_charge','controller_rental_fee'];
$settingsResults = [];
foreach ($settingsKeys as $k) {
    $val = getSetting($k);
    $settingsResults[$k] = $val;
    if ($val === null || $val === '') {
        $warnings[] = "Setting '{$k}' is NULL or empty";
    }
}
$ok[] = "Settings loaded: " . count(array_filter($settingsResults, fn($v) => $v !== null)) . "/" . count($settingsKeys) . " have values";

// ═══════════════════════════════════════════════════════════════════════════
// 2. PHP INCLUDE / RENDER CHECKS
// ═══════════════════════════════════════════════════════════════════════════

// Check that section files exist and are readable
$sectionFiles = [
    'transactions.php' => __DIR__ . '/admin_sections/transactions.php',
    'reports.php'      => __DIR__ . '/admin_sections/reports.php',
    'settings.php'     => __DIR__ . '/admin_sections/settings.php',
    'dashboard.php'    => __DIR__ . '/admin_sections/dashboard.php',
    'sessions.php'     => __DIR__ . '/admin_sections/sessions.php',
    'consoles.php'     => __DIR__ . '/admin_sections/consoles.php',
];
foreach ($sectionFiles as $name => $path) {
    if (file_exists($path) && is_readable($path)) {
        $size = filesize($path);
        $ok[] = "{$name}: exists, {$size} bytes";
    } else {
        $errors[] = "{$name}: MISSING or not readable at {$path}";
    }
}

// Try to capture the output of including each broken section
// We need all the variables that admin.php normally provides
$finStmt = $conn->query("SELECT
    SUM(CASE WHEN payment_status='completed' THEN amount ELSE 0 END) AS total_revenue,
    SUM(CASE WHEN DATE(transaction_date)=CURDATE() AND payment_status='completed' THEN amount ELSE 0 END) AS today_revenue,
    SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) AND payment_status='completed' THEN amount ELSE 0 END) AS monthly_revenue,
    COUNT(CASE WHEN payment_status='completed' THEN 1 END) AS total_transactions
FROM transactions");
$finStats = $finStmt ? $finStmt->fetch_assoc() : [];

$transResult = $conn->query(
    "SELECT t.*, u.full_name AS customer_name,
            COALESCE(c.unit_number, '—') AS unit_number,
            COALESCE(gs.rental_mode, 'refund') AS rental_mode
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
     LEFT JOIN consoles c ON gs.console_id = c.console_id
     ORDER BY t.transaction_date DESC LIMIT 30"
);
$transSessions = $transResult ? $transResult->fetch_all(MYSQLI_ASSOC) : [];

$unlimitedRateVal = (float)(getSetting('unlimited_rate') ?? 300);
$pendingSessions = []; // empty for debug

$settingsKeys2 = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                   'business_hours_open','business_hours_close','shop_name','shop_address','shop_phone',
                   'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge'];
$settings = [];
foreach ($settingsKeys2 as $k) $settings[$k] = getSetting($k);

$today = date('Y-m-d');
$usageReport = getConsoleUsageReport('2020-01-01', $today);

// Now try rendering each section in isolation
$renderTests = [];
foreach (['transactions', 'reports', 'settings'] as $section) {
    ob_start();
    $renderError = null;
    try {
        include __DIR__ . "/admin_sections/{$section}.php";
    } catch (\Throwable $e) {
        $renderError = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    $html = ob_get_clean();
    $len = strlen($html);

    if ($renderError) {
        $errors[] = "{$section}.php RENDER ERROR: {$renderError}";
    } elseif ($len === 0) {
        $errors[] = "{$section}.php rendered EMPTY (0 bytes)";
    } else {
        $ok[] = "{$section}.php rendered OK — {$len} bytes of HTML";
        // Check if the page div ID is present
        if (strpos($html, "id=\"{$section}\"") !== false) {
            $ok[] = "{$section}.php contains id=\"{$section}\" ✓";
        } else {
            $errors[] = "{$section}.php MISSING id=\"{$section}\" div!";
        }
        // Check if it has class="page"
        if (strpos($html, 'class="page"') !== false) {
            $ok[] = "{$section}.php contains class=\"page\" ✓";
        } else {
            $errors[] = "{$section}.php MISSING class=\"page\"!";
        }
    }
    $renderTests[$section] = ['html_length' => $len, 'error' => $renderError, 'first_200' => substr($html, 0, 200)];
}

// ═══════════════════════════════════════════════════════════════════════════
// 3. CSS / JS FILE CHECKS
// ═══════════════════════════════════════════════════════════════════════════

$cssPath = __DIR__ . '/assets/css/admin.css';
if (file_exists($cssPath)) {
    $cssContent = file_get_contents($cssPath);
    // Check if .page.active has animation: forwards
    if (strpos($cssContent, 'forwards') !== false) {
        $ok[] = "admin.css contains 'forwards' keyword ✓";
    } else {
        $errors[] = "admin.css MISSING 'forwards' in animation — opacity will revert to 0!";
    }
    // Check what .page.active looks like
    if (preg_match('/\.page\.active\s*\{([^}]+)\}/s', $cssContent, $m)) {
        $ok[] = ".page.active CSS rule: " . trim($m[1]);
    }
    // Check .page base rule
    if (preg_match('/\.page\s*\{([^}]+)\}/s', $cssContent, $m)) {
        $ok[] = ".page CSS rule: " . trim($m[1]);
    }
} else {
    $errors[] = "admin.css FILE NOT FOUND at {$cssPath}";
}

// Check admin.php for the inline style override
$adminPhp = file_get_contents(__DIR__ . '/admin.php');
if (strpos($adminPhp, 'opacity: 1 !important') !== false) {
    $ok[] = "admin.php has inline opacity:1 !important override ✓";
} else {
    $warnings[] = "admin.php missing inline opacity:1 override";
}

// Check showPage function
if (preg_match('/function showPage\(page, el\)\s*\{/', $adminPhp)) {
    $ok[] = "showPage() function found in admin.php ✓";
} else {
    $errors[] = "showPage() function NOT FOUND in admin.php!";
}

// Check if transactions is in validPages
if (strpos($adminPhp, "'transactions'") !== false &&
    strpos($adminPhp, "validPages") !== false) {
    $ok[] = "'transactions' in validPages array ✓";
} else {
    $errors[] = "'transactions' MISSING from validPages!";
}

// Check that PHP include lines exist
foreach (['transactions', 'reports', 'settings'] as $sec) {
    if (strpos($adminPhp, "admin_sections/{$sec}.php") !== false) {
        $ok[] = "admin.php includes {$sec}.php ✓";
    } else {
        $errors[] = "admin.php DOES NOT include {$sec}.php!";
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 4. RENDERED PAGE SIZE CHECK  
// ═══════════════════════════════════════════════════════════════════════════
// Check total admin.php file size
$adminSize = filesize(__DIR__ . '/admin.php');
$ok[] = "admin.php file size: " . number_format($adminSize) . " bytes";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug: Admin Pages</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0a0e1a; color: #e0e0e0; font-family: 'Consolas', monospace; padding: 30px; }
        h1 { color: #20c8a1; margin-bottom: 20px; font-size: 24px; }
        h2 { color: #5f85da; margin: 25px 0 12px; font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 6px; }
        .error { background: rgba(251,86,107,.15); border: 1px solid rgba(251,86,107,.4); color: #fb566b; padding: 8px 14px; border-radius: 8px; margin: 4px 0; font-size: 13px; }
        .warning { background: rgba(241,168,60,.12); border: 1px solid rgba(241,168,60,.3); color: #f1a83c; padding: 8px 14px; border-radius: 8px; margin: 4px 0; font-size: 13px; }
        .ok { background: rgba(32,200,161,.1); border: 1px solid rgba(32,200,161,.25); color: #20c8a1; padding: 8px 14px; border-radius: 8px; margin: 4px 0; font-size: 13px; }
        .section { background: rgba(10,33,81,.5); border: 1px solid rgba(95,133,218,.2); border-radius: 12px; padding: 20px; margin: 15px 0; }
        .summary { font-size: 20px; font-weight: 700; margin: 15px 0; }
        .summary.pass { color: #20c8a1; }
        .summary.fail { color: #fb566b; }
        pre { background: rgba(0,0,0,.4); padding: 12px; border-radius: 8px; overflow-x: auto; font-size: 12px; margin: 8px 0; color: #ccc; white-space: pre-wrap; word-break: break-all; }
        .test-render { margin: 10px 0; }
        .test-render iframe { width: 100%; height: 300px; border: 2px solid #333; border-radius: 8px; background: #0d1117; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .badge-error { background: #fb566b; color: #fff; }
        .badge-ok { background: #20c8a1; color: #000; }
        .badge-warn { background: #f1a83c; color: #000; }
    </style>
</head>
<body>
<h1>🛠 Admin Page Debug Report</h1>
<p style="color:#888;margin-bottom:20px;">Generated: <?= date('Y-m-d H:i:s') ?> | User: <?= htmlspecialchars($user['full_name']) ?> (<?= $user['role'] ?>) | PHP <?= PHP_VERSION ?></p>

<div class="summary <?= count($errors) === 0 ? 'pass' : 'fail' ?>">
    <?= count($errors) === 0 ? '✅ ALL CHECKS PASSED' : '❌ ' . count($errors) . ' ERROR(S) FOUND' ?>
    — <?= count($warnings) ?> warning(s), <?= count($ok) ?> passed
</div>

<?php if ($errors): ?>
<h2>❌ Errors (<?= count($errors) ?>)</h2>
<div class="section">
    <?php foreach ($errors as $e): ?>
    <div class="error">🔴 <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($warnings): ?>
<h2>⚠️ Warnings (<?= count($warnings) ?>)</h2>
<div class="section">
    <?php foreach ($warnings as $w): ?>
    <div class="warning">🟡 <?= htmlspecialchars($w) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<h2>✅ Passed Checks (<?= count($ok) ?>)</h2>
<div class="section">
    <?php foreach ($ok as $o): ?>
    <div class="ok">✓ <?= htmlspecialchars($o) ?></div>
    <?php endforeach; ?>
</div>

<h2>📄 Rendered HTML Test (each section in isolation)</h2>
<div class="section">
    <?php foreach ($renderTests as $name => $data): ?>
    <div class="test-render">
        <strong style="color:#f1e1aa;">
            <?= $name ?>.php
            <?php if ($data['error']): ?>
                <span class="badge badge-error">RENDER ERROR</span>
            <?php elseif ($data['html_length'] === 0): ?>
                <span class="badge badge-error">EMPTY</span>
            <?php else: ?>
                <span class="badge badge-ok"><?= number_format($data['html_length']) ?> bytes</span>
            <?php endif; ?>
        </strong>
        <?php if ($data['error']): ?>
        <pre style="color:#fb566b;"><?= htmlspecialchars($data['error']) ?></pre>
        <?php endif; ?>
        <pre><?= htmlspecialchars($data['first_200']) ?>…</pre>
    </div>
    <?php endforeach; ?>
</div>

<h2>🧪 Live Render Test (isolated in iframes)</h2>
<p style="color:#888;margin-bottom:10px;">Each section is rendered below in an iframe. If you see content, the PHP/data side is fine — the bug is in admin.php's JS or CSS.</p>
<div class="section">
    <?php foreach (['transactions', 'reports', 'settings'] as $sec): ?>
    <div style="margin-bottom:20px;">
        <strong style="color:#5f85da;"><?= ucfirst($sec) ?></strong>
        <iframe srcdoc="<?= htmlspecialchars(
            '<!DOCTYPE html><html><head><meta charset=UTF-8>'
            . '<link rel=stylesheet href=http://localhost/GamingSpotHub/assets/css/style.css>'
            . '<link rel=stylesheet href=http://localhost/GamingSpotHub/assets/css/admin.css>'
            . '<link rel=stylesheet href=http://localhost/GamingSpotHub/assets/libs/fontawesome/css/all.min.css>'
            . '<style>.page{display:block!important;opacity:1!important;transform:none!important;}'
            . 'body{background:#0d1117;color:#e0e0e0;font-family:Inter,sans-serif;padding:20px;}</style></head>'
            . '<body>' . $renderTests[$sec]['first_200'] . '…<br><strong style="color:#20c8a1;">(' . $renderTests[$sec]['html_length'] . ' bytes total)</strong></body></html>'
        ) ?>"></iframe>
    </div>
    <?php endforeach; ?>
</div>

<h2>🔍 Sample Transaction Data (first 5)</h2>
<div class="section">
<?php if (!empty($joinRows)): ?>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tr style="color:#5f85da;border-bottom:1px solid #333;"><th style="padding:6px;text-align:left;">ID</th><th style="text-align:left;">Session</th><th style="text-align:left;">Customer</th><th style="text-align:left;">Unit</th><th style="text-align:left;">Mode</th></tr>
<?php foreach ($joinRows as $r): ?>
<tr style="border-bottom:1px solid #1a1a2e;">
    <td style="padding:6px;color:#f1e1aa;">#<?= $r['transaction_id'] ?></td>
    <td><?= $r['session_id'] ?? '<span style="color:#fb566b">NULL</span>' ?></td>
    <td><?= htmlspecialchars($r['full_name']) ?></td>
    <td><?= htmlspecialchars($r['unit_number']) ?></td>
    <td><?= $r['rental_mode'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<div class="warning">No transaction data to display</div>
<?php endif; ?>
</div>

<h2>⚙️ Settings Values</h2>
<div class="section">
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<?php foreach ($settingsResults as $k => $v): ?>
<tr style="border-bottom:1px solid #1a1a2e;">
    <td style="padding:6px;color:#5f85da;font-weight:600;"><?= $k ?></td>
    <td style="color:<?= $v !== null ? '#20c8a1' : '#fb566b' ?>;"><?= $v ?? 'NULL' ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<h2>🌐 JS showPage() Diagnostic</h2>
<div class="section">
<p style="color:#888;margin-bottom:10px;">Open the <strong>actual admin.php</strong> page, paste this in the browser console (F12), and report what it says:</p>
<pre style="color:#f1e1aa;">
// Paste this entire block in the browser console on admin.php
(function(){
    var pages = document.querySelectorAll('.page');
    console.log('=== PAGE DEBUG ===');
    console.log('Total .page elements:', pages.length);
    pages.forEach(function(p){
        var cs = getComputedStyle(p);
        console.log(
            'ID:', p.id,
            '| display:', cs.display,
            '| opacity:', cs.opacity,
            '| visibility:', cs.visibility,
            '| height:', cs.height,
            '| children:', p.children.length,
            '| innerHTML:', p.innerHTML.length, 'chars',
            '| class:', p.className,
            '| inline:', p.style.cssText || '(none)'
        );
    });
    // Check if any .page elements are nested inside another hidden element
    var transactions = document.getElementById('transactions');
    if (transactions) {
        var parent = transactions.parentElement;
        console.log('--- transactions parent chain ---');
        while (parent && parent !== document.body) {
            var pcs = getComputedStyle(parent);
            console.log(
                'tag:', parent.tagName,
                '| id:', parent.id || '(none)',
                '| class:', parent.className,
                '| display:', pcs.display,
                '| opacity:', pcs.opacity,
                '| overflow:', pcs.overflow,
                '| height:', pcs.height
            );
            parent = parent.parentElement;
        }
    } else {
        console.log('ERROR: #transactions element NOT FOUND in DOM!');
    }
    console.log('=== END DEBUG ===');
})();
</pre>
</div>

<div style="margin-top:30px;padding:20px;background:rgba(251,86,107,.08);border:1px solid rgba(251,86,107,.3);border-radius:10px;">
    <strong style="color:#fb566b;">⚠ Delete this file after debugging!</strong>
    <code style="color:#888;display:block;margin-top:4px;">rm <?= __FILE__ ?></code>
</div>

</body>
</html>
