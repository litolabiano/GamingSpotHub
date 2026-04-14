<?php
/**
 * Good Spot Gaming Hub - Admin Dashboard
 * Live database-connected management panel for Owner & Shopkeeper roles.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/includes/db_functions.php';

$user = getCurrentUser();
$message = '';
$messageType = '';

// ─── POST ACTION HANDLERS ──────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // START SESSION
    if ($action === 'start_session') {
        $user_id      = (int)($_POST['user_id'] ?? 0);
        $console_id   = (int)($_POST['console_id'] ?? 0);
        $rental_mode  = $_POST['rental_mode'] ?? '';
        $planned_minutes = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;

        if (!$user_id || !$console_id || !in_array($rental_mode, ['hourly','open_time','unlimited'])) {
            $message = 'Please fill in all session fields correctly.';
            $messageType = 'error';
        } elseif ($rental_mode === 'hourly' && (!$planned_minutes || $planned_minutes <= 0)) {
            $message = 'Please select a duration for the hourly session.';
            $messageType = 'error';
        } else {
            $result = startSession($user_id, $console_id, $rental_mode, $user['user_id'], $planned_minutes);
            if ($result['success']) {
                $message = 'Session started successfully! Session ID: #' . $result['session_id'];
                $messageType = 'success';
            } else {
                $message = 'Could not start session: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }

    // END SESSION + RECORD PAYMENT
    elseif ($action === 'end_session') {
        $session_id     = (int)($_POST['session_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';

        if (!$session_id) {
            $message = 'Invalid session ID.';
            $messageType = 'error';
        } else {
            $result = endSession($session_id);
            if ($result['success']) {
                // Fetch the user_id for the session to record transaction
                $stmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ?");
                $stmt->bind_param("i", $session_id);
                $stmt->execute();
                $sess_row = $stmt->get_result()->fetch_assoc();

                if ($sess_row) {
                    recordTransaction($session_id, $sess_row['user_id'], $result['total_cost'], $payment_method, $user['user_id']);
                }
                $mins = $result['duration_minutes'];
                $cost = number_format($result['total_cost'], 2);
                $message = "Session ended. Duration: {$mins} min. Total: ₱{$cost}. Payment recorded.";
                $messageType = 'success';
            } else {
                $message = 'Could not end session: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }



    // UPDATE CONSOLE STATUS
    elseif ($action === 'update_console_status') {
        $console_id = (int)($_POST['console_id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        $allowed    = ['available', 'in_use', 'maintenance'];
        if ($console_id && in_array($status, $allowed)) {
            updateConsoleStatus($console_id, $status);
            $message = 'Console status updated.';
            $messageType = 'success';
        }
    }

    // SAVE SETTINGS
    elseif ($action === 'save_settings') {
        $keys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                 'business_hours_open','business_hours_close','shop_phone'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                updateSetting($key, trim($_POST[$key]));
            }
        }
        $message = 'Settings saved successfully.';
        $messageType = 'success';
    }
}

// ─── DATA FETCHING ─────────────────────────────────────────────────────────

// Dashboard stats
$today = date('Y-m-d');
$todayStats = getDailySalesReport($today);
$activeSessions  = getActiveSessions();
$activeCount     = count($activeSessions);
$todayRevenue    = $todayStats['total_revenue'] ?? 0;
$todayBookings   = $todayStats['total_sessions'] ?? 0;

// All consoles
$allConsoles = getConsoles();
$availableCount  = count(array_filter($allConsoles, fn($c) => $c['status'] === 'available'));
$inUseCount      = count(array_filter($allConsoles, fn($c) => $c['status'] === 'in_use'));
$maintenanceCount= count(array_filter($allConsoles, fn($c) => $c['status'] === 'maintenance'));

// Recent 20 completed sessions
$stmt = $conn->prepare(
    "SELECT gs.*, u.full_name AS customer_name, c.console_name, c.unit_number, c.console_type
     FROM gaming_sessions gs
     JOIN users u ON gs.user_id = u.user_id
     JOIN consoles c ON gs.console_id = c.console_id
     ORDER BY gs.created_at DESC LIMIT 20"
);
$stmt->execute();
$recentSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// All customers for session start dropdown
$customersResult = $conn->query("SELECT user_id, full_name, email FROM users WHERE role = 'customer' AND status = 'active' ORDER BY full_name");
$customers = $customersResult->fetch_all(MYSQLI_ASSOC);

// Available consoles for start session
$availableConsoles = getAvailableConsoles();



// Financial stats
$finStmt = $conn->query(
    "SELECT
        SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) AND payment_status='completed' THEN amount ELSE 0 END) AS monthly_revenue,
        SUM(CASE WHEN DATE(transaction_date)=CURDATE() AND payment_status='completed' THEN amount ELSE 0 END) AS today_revenue,
        SUM(CASE WHEN payment_status='completed' THEN amount ELSE 0 END) AS total_revenue,
        COUNT(CASE WHEN payment_status='completed' THEN 1 END) AS total_transactions
     FROM transactions"
);
$finStats = $finStmt->fetch_assoc();

// Transaction history (last 30)
$transSessions = $conn->query(
    "SELECT t.*, u.full_name AS customer_name, c.unit_number, gs.rental_mode
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     JOIN gaming_sessions gs ON t.session_id = gs.session_id
     JOIN consoles c ON gs.console_id = c.console_id
     ORDER BY t.transaction_date DESC LIMIT 30"
)->fetch_all(MYSQLI_ASSOC);


// Console usage (all time)
$usageReport = getConsoleUsageReport('2020-01-01', $today);

// Settings
$settingsKeys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                 'business_hours_open','business_hours_close','shop_name','shop_address','shop_phone'];
$settings = [];
foreach ($settingsKeys as $k) {
    $settings[$k] = getSetting($k);
}

// Chart data: revenue last 7 days
$revChartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS rev FROM transactions WHERE DATE(transaction_date)=? AND payment_status='completed'");
    $s->bind_param("s", $d);
    $s->execute();
    $revChartData[] = (float)$s->get_result()->fetch_assoc()['rev'];
}
$revLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $revLabels[] = date('M d', strtotime("-{$i} days"));
}

// Chart data: console type usage
$typeUsage = $conn->query(
    "SELECT c.console_type, COUNT(gs.session_id) AS cnt
     FROM consoles c
     LEFT JOIN gaming_sessions gs ON c.console_id = gs.console_id AND gs.status = 'completed'
     GROUP BY c.console_type"
)->fetch_all(MYSQLI_ASSOC);
$typeLabels = array_column($typeUsage, 'console_type');
$typeCounts = array_column($typeUsage, 'cnt');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Good Spot Gaming Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ── Extra admin overrides ────────────────── */
        .flash-msg {
            position: fixed; top: 80px; right: 20px; z-index: 9999;
            padding: 14px 20px; border-radius: 10px; font-size: 14px; font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            animation: slideInRight .3s ease; max-width: 380px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }
        .flash-msg.success { background: rgba(32,200,161,.15); border: 1px solid rgba(32,200,161,.4); color: #20c8a1; }
        .flash-msg.error   { background: rgba(251,86,107,.15); border: 1px solid rgba(251,86,107,.4); color: #fb566b; }
        @keyframes slideInRight { from { transform: translateX(120%); opacity:0; } to { transform: translateX(0); opacity:1; } }

        .status-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }
        .status-dot.available  { background:#20c8a1; }
        .status-dot.in_use     { background:#5f85da; }
        .status-dot.maintenance{ background:#fb566b; }

        .console-type-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
        .console-type-badge.ps5  { background:rgba(95,133,218,.2); color:#5f85da; border:1px solid rgba(95,133,218,.3); }
        .console-type-badge.xbox { background:rgba(32,200,161,.2); color:#20c8a1; border:1px solid rgba(32,200,161,.3); }

        /* ── Session timer ────────────────────────── */
        .session-timer { font-family: monospace; font-size: 13px; color: #f1e1aa; font-weight: 600; }
        .session-timer.stale { color: #fb566b; font-size:11px; font-weight:500; }

        /* ── Form layout ──────────────────────────── */
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:13px; color:#aaa; margin-bottom:6px; font-weight:600; }
        .form-group select, .form-group input[type=text], .form-group input[type=number],
        .form-group input[type=time], .form-group textarea {
            width:100%; background:rgba(10,33,81,.6); border:1px solid rgba(95,133,218,.25);
            color:#f0f0f0; padding:10px 14px; border-radius:8px; font-size:14px;
            font-family:inherit; outline:none; box-sizing:border-box; transition:.2s; }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            border-color:#20c8a1; box-shadow:0 0 0 3px rgba(32,200,161,.1); }
        .form-group textarea { resize:vertical; min-height:80px; }
        .form-check { display:flex; align-items:center; gap:8px; margin-top:6px; }
        .form-check input { width:auto; accent-color:#20c8a1; }

        /* ── Stat cards ───────────────────────────── */
        .stat-card-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
        .stat-change.up { color:#20c8a1; }
        .stat-icon.revenue  { background:rgba(32,200,161,.15); color:#20c8a1; }
        .stat-icon.sessions { background:rgba(95,133,218,.15); color:#5f85da; }
        .stat-icon.bookings { background:rgba(179,123,236,.15); color:#b37bec; }
        .stat-icon.consoles { background:rgba(241,225,170,.15); color:#f1e1aa; }

        /* ── Console cards ────────────────────────── */
        .console-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:16px; }
        .console-card { background:rgba(10,33,81,.55); border:1px solid rgba(95,133,218,.15);
            border-radius:12px; padding:18px; position:relative; transition:.2s; }
        .console-card:hover { transform:translateY(-3px); }
        .console-card.available  { border-left:3px solid #20c8a1; }
        .console-card.in_use     { border-left:3px solid #5f85da; }
        .console-card.maintenance{ border-left:3px solid #fb566b; }
        .console-unit  { font-size:22px; font-weight:800; margin-bottom:4px; color:#fff; }
        .console-name  { font-size:13px; color:#888; margin-bottom:10px; }
        .console-rate  { font-size:12px; color:#f1e1aa; margin-bottom:12px; }
        .console-actions { display:flex; gap:6px; flex-wrap:wrap; }



        /* ── Badge ────────────────────────────────── */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge.active     { background:rgba(95,133,218,.2);  color:#5f85da; }
        .badge.completed  { background:rgba(32,200,161,.2);  color:#20c8a1; }
        .badge.cancelled  { background:rgba(251,86,107,.2);  color:#fb566b; }
        .badge.pending    { background:rgba(241,225,170,.2); color:#f1e1aa; }
        .badge.available  { background:rgba(32,200,161,.2);  color:#20c8a1; }
        .badge.in_use     { background:rgba(95,133,218,.2);  color:#5f85da; }
        .badge.maintenance{ background:rgba(251,86,107,.2);  color:#fb566b; }
        .badge.installed  { background:rgba(179,123,236,.2); color:#b37bec; }

        /* ── Empty state ──────────────────────────── */
        .empty-state { text-align:center; padding:40px; color:#555; }
        .empty-state i { font-size:36px; margin-bottom:12px; display:block; }

        /* ── Responsive form ──────────────────────── */
        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>

<?php if ($message): ?>
<div class="flash-msg <?= $messageType ?>" id="flashMsg">
    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<script>setTimeout(() => document.getElementById('flashMsg')?.remove(), 4500);</script>
<?php endif; ?>

<!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
<div class="sidebar" id="sidebar" style="display:flex;flex-direction:column;">
    <div class="sidebar-header">
        <a class="navbar-brand" href="index.php">
            <div class="logo-container">
                <span class="logo-g">G</span><span class="logo-s">s</span><span class="logo-p">p</span><span class="logo-o">o</span><span class="logo-t">t</span>
                <span class="logo-text">GAMING HUB</span>
            </div>
        </a>
    </div>
    <div class="nav-item active" onclick="showPage('dashboard', this)">
        <i class="fas fa-chart-line"></i><span>Dashboard</span>
    </div>
    <div class="nav-item" onclick="showPage('consoles', this)">
        <i class="fas fa-desktop"></i><span>Consoles</span>
    </div>
    <div class="nav-item" onclick="showPage('sessions', this)">
        <i class="fas fa-play-circle"></i><span>Sessions</span>
    </div>


    <div class="nav-item" onclick="showPage('financial', this)">
        <i class="fas fa-peso-sign"></i><span>Financial</span>
    </div>
    <div class="nav-item" onclick="showPage('reports', this)">
        <i class="fas fa-chart-bar"></i><span>Reports</span>
    </div>
    <div class="nav-item" onclick="showPage('settings', this)">
        <i class="fas fa-cog"></i><span>Settings</span>
    </div>
    <div style="flex:1"></div>
    <a href="index.php" class="nav-item" style="text-decoration:none;color:inherit;border-top:1px solid rgba(255,255,255,.1);padding-top:15px">
        <i class="fas fa-arrow-left"></i><span>Back to Site</span>
    </a>
</div>

<!-- ── Top Bar ──────────────────────────────────────────────────────────── -->
<div class="topbar">
    <div class="topbar-left">
        <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
        <h3 id="pageTitle">Dashboard</h3>
    </div>
    <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('startSession')">
            <i class="fas fa-plus"></i> New Session
        </button>
        <div class="user-profile" style="margin-left:12px">
            <div class="user-avatar"><?= getUserInitials() ?></div>
            <div>
                <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($user['full_name']) ?></div>
                <div style="font-size:12px;color:#718096"><?= getRoleBadge() ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Main Content ─────────────────────────────────────────────────────── -->
<div class="main-content">

<?php include __DIR__ . '/admin_sections/dashboard.php'; ?>
<?php include __DIR__ . '/admin_sections/consoles.php'; ?>
<?php include __DIR__ . '/admin_sections/sessions.php'; ?>
<?php include __DIR__ . '/admin_sections/financial.php'; ?>
<?php include __DIR__ . '/admin_sections/reports.php'; ?>
<?php include __DIR__ . '/admin_sections/settings.php'; ?>

</div><!-- /.main-content -->

<?php include __DIR__ . '/admin_sections/modals.php'; ?>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value">₱<?= number_format($todayRevenue, 2) ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
                <div class="stat-icon revenue"><i class="fas fa-peso-sign"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-calendar-day"></i> <?= date('F d, Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $activeCount ?></div>
                    <div class="stat-label">Active Sessions</div>
                </div>
                <div class="stat-icon sessions"><i class="fas fa-play-circle"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-circle" style="color:#20c8a1;font-size:8px"></i> Live right now</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $todayBookings ?></div>
                    <div class="stat-label">Sessions Today</div>
                </div>
                <div class="stat-icon bookings"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-check"></i> Completed today</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $availableCount ?>/<?= count($allConsoles) ?></div>
                    <div class="stat-label">Consoles Available</div>
                </div>
                <div class="stat-icon consoles"><i class="fas fa-desktop"></i></div>
            </div>
            <div class="stat-change up">
                <span style="color:#5f85da"><?= $inUseCount ?> in use</span> &nbsp;
                <span style="color:#fb566b"><?= $maintenanceCount ?> maintenance</span>
            </div>
        </div>
    </div>

    <!-- Active Sessions Right Now -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-circle" style="color:#20c8a1;font-size:10px;margin-right:8px"></i>Live Sessions</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('startSession')"><i class="fas fa-plus"></i> Start Session</button>
        </div>
        <?php if (empty($activeSessions)): ?>
            <div class="empty-state"><i class="fas fa-couch"></i>No active sessions right now</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Session #</th><th>Customer</th><th>Console</th><th>Mode</th><th>Started</th><th>Booked Until</th><th>Elapsed / Remaining</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($activeSessions as $sess): ?>
            <tr>
                <td>#<?= $sess['session_id'] ?></td>
                <td><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td>
                    <span class="console-type-badge <?= strtolower(str_replace(' ','-',$sess['console_type'])) === 'ps5' ? 'ps5' : 'xbox' ?>">
                        <?= $sess['console_type'] ?>
                    </span>
                    <?= htmlspecialchars($sess['unit_number']) ?>
                </td>
                <td><span class="badge pending"><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></span></td>
                <td><?= date('h:i A', strtotime($sess['start_time'])) ?></td>
                <td>
                    <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']): ?>
                        <span style="color:#f1e1aa;font-weight:600"><?= date('h:i A', strtotime($sess['start_time']) + ($sess['planned_minutes'] * 60)) ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="session-timer" data-start="<?= $sess['start_time'] ?>" data-planned="<?= $sess['planned_minutes'] ?? '' ?>">—</span></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="openEndSessionModal(<?= $sess['session_id'] ?>, '<?= htmlspecialchars($sess['customer_name']) ?>', '<?= htmlspecialchars($sess['unit_number']) ?>')">
                        <i class="fas fa-stop-circle"></i> End &amp; Pay
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Recent Sessions -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Recent Sessions</h3></div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Duration</th><th>Cost</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($recentSessions, 0, 8) as $sess): ?>
            <tr>
                <td>#<?= $sess['session_id'] ?></td>
                <td><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td><?= htmlspecialchars($sess['unit_number']) ?></td>
                <td><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></td>
                <td><?= $sess['duration_minutes'] !== null ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'].' min' : '< 1 min') : '—' ?></td>
                <td><?= $sess['total_cost'] ? '₱'.number_format($sess['total_cost'],2) : '—' ?></td>
                <td><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/admin_sections/consoles.php'; ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div>
            <span style="margin-right:16px;font-size:13px"><span class="status-dot available"></span><?= $availableCount ?> Available</span>
            <span style="margin-right:16px;font-size:13px"><span class="status-dot in_use"></span><?= $inUseCount ?> In Use</span>
            <span style="font-size:13px"><span class="status-dot maintenance"></span><?= $maintenanceCount ?> Maintenance</span>
        </div>
    </div>
    <div class="console-grid">
    <?php foreach ($allConsoles as $con): ?>
        <div class="console-card <?= $con['status'] ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                <span class="console-type-badge <?= $con['console_type'] === 'PS5' ? 'ps5' : 'xbox' ?>">
                    <i class="fab fa-<?= $con['console_type'] === 'PS5' ? 'playstation' : 'xbox' ?>"></i>
                    <?= $con['console_type'] ?>
                </span>
                <span class="badge <?= $con['status'] ?>"><?= ucfirst(str_replace('_',' ',$con['status'])) ?></span>
            </div>
            <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
            <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
            <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
            <div class="console-actions">
                <?php if ($con['status'] !== 'available'): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="update_console_status">
                    <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                    <input type="hidden" name="status" value="available">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Set Available</button>
                </form>
                <?php endif; ?>
                <?php if ($con['status'] !== 'maintenance'): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="update_console_status">
                    <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                    <input type="hidden" name="status" value="maintenance">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-wrench"></i> Maintenance</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/admin_sections/sessions.php'; ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Sessions</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('startSession')"><i class="fas fa-plus"></i> New Session</button>
        </div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Booked</th><th>Start</th><th>End</th><th>Duration</th><th>Cost</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recentSessions as $sess): ?>
            <tr>
                <td>#<?= $sess['session_id'] ?></td>
                <td><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td><?= htmlspecialchars($sess['unit_number']) ?></td>
                <td><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></td>
                <td>
                    <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']):
                        $ph = intdiv($sess['planned_minutes'], 60);
                        $pm = $sess['planned_minutes'] % 60;
                        echo $ph ? ($pm ? "{$ph}h {$pm}m" : "{$ph}h") : "{$pm}m";
                    else: ?>—<?php endif; ?>
                </td>
                <td><?= date('M d h:i A', strtotime($sess['start_time'])) ?></td>
                <td><?= $sess['end_time'] ? date('h:i A', strtotime($sess['end_time'])) : '<span style="color:#20c8a1">Live</span>' ?></td>
                <td><?= $sess['duration_minutes'] !== null ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'].' min' : '< 1 min') : '—' ?></td>
                <td><?= $sess['total_cost'] ? '₱'.number_format($sess['total_cost'],2) : '—' ?></td>
                <td><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
                <td>
                <?php if ($sess['status'] === 'active'): ?>
                    <button class="btn btn-danger btn-sm" onclick="openEndSessionModal(<?= $sess['session_id'] ?>, '<?= htmlspecialchars($sess['customer_name']) ?>', '<?= htmlspecialchars($sess['unit_number']) ?>')">
                        <i class="fas fa-stop"></i> End
                    </button>
                <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>





<?php include __DIR__ . '/admin_sections/financial.php'; ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">₱<?= number_format($finStats['today_revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">₱<?= number_format($finStats['monthly_revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">This Month's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">₱<?= number_format($finStats['total_revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">All-Time Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $finStats['total_transactions'] ?? 0 ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Transaction History</h3></div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($transSessions as $t): ?>
            <tr>
                <td>#<?= $t['transaction_id'] ?></td>
                <td><?= htmlspecialchars($t['customer_name']) ?></td>
                <td><?= htmlspecialchars($t['unit_number']) ?></td>
                <td><?= match($t['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($t['rental_mode']) } ?></td>
                <td style="color:#20c8a1;font-weight:700">₱<?= number_format($t['amount'],2) ?></td>
                <td><span class="badge pending"><?= ucfirst($t['payment_method']) ?></span></td>
                <td><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                <td><span class="badge <?= $t['payment_status'] === 'completed' ? 'completed' : 'cancelled' ?>"><?= ucfirst($t['payment_status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/admin_sections/reports.php'; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Revenue — Last 7 Days</h3></div>
            <canvas id="revChart" height="200"></canvas>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Sessions by Console Type</h3></div>
            <canvas id="typeChart" height="200"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Console Usage Report (All Time)</h3></div>
        <table class="data-table">
            <thead><tr><th>Unit</th><th>Type</th><th>Total Sessions</th><th>Total Hours</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($usageReport as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['unit_number']) ?></td>
                <td><?= htmlspecialchars($u['console_type']) ?></td>
                <td><?= $u['total_sessions'] ?></td>
                <td><?= number_format($u['total_minutes']/60, 1) ?> hrs</td>
                <td style="color:#20c8a1">₱<?= number_format($u['total_revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/admin_sections/settings.php'; ?>
    <form method="POST">
        <input type="hidden" name="action" value="save_settings">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Pricing</h3></div>
                <div class="form-group">
                    <label>PS5 Hourly Rate (₱)</label>
                    <input type="number" step="0.01" name="ps5_hourly_rate" value="<?= htmlspecialchars($settings['ps5_hourly_rate'] ?? '80') ?>">
                </div>
                <div class="form-group">
                    <label>Xbox Hourly Rate (₱)</label>
                    <input type="number" step="0.01" name="xbox_hourly_rate" value="<?= htmlspecialchars($settings['xbox_hourly_rate'] ?? '80') ?>">
                </div>
                <div class="form-group">
                    <label>Unlimited (whole day) Rate (₱)</label>
                    <input type="number" step="0.01" name="unlimited_rate" value="<?= htmlspecialchars($settings['unlimited_rate'] ?? '300') ?>">
                </div>
                <div class="form-group">
                    <label>Controller Rental Fee (₱)</label>
                    <input type="number" step="0.01" name="controller_rental_fee" value="<?= htmlspecialchars($settings['controller_rental_fee'] ?? '20') ?>">
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Shop Information</h3></div>
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" name="shop_name" value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>" readonly style="opacity:.6">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="shop_address" value="<?= htmlspecialchars($settings['shop_address'] ?? '') ?>" readonly style="opacity:.6">
                </div>
                <div class="form-group">
                    <label>Opening Time</label>
                    <input type="time" name="business_hours_open" value="<?= htmlspecialchars($settings['business_hours_open'] ?? '09:00') ?>">
                </div>
                <div class="form-group">
                    <label>Closing Time</label>
                    <input type="time" name="business_hours_close" value="<?= htmlspecialchars($settings['business_hours_close'] ?? '23:00') ?>">
                </div>
                <div class="form-group">
                    <label>Shop Phone</label>
                    <input type="text" name="shop_phone" value="<?= htmlspecialchars($settings['shop_phone'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div style="margin-top:10px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        </div>
    </form>
</div>

</div><!-- /.main-content -->

<?php include __DIR__ . '/admin_sections/modals.php'; ?>

<!-- Start Session Modal -->
<div class="modal" id="startSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-play-circle" style="color:#20c8a1;margin-right:8px"></i>Start New Session</h3>
            <button class="modal-close" onclick="closeModal('startSession')">&times;</button>
        </div>
        <form method="POST" id="startSessionForm">
            <input type="hidden" name="action" value="start_session">
            <input type="hidden" name="planned_minutes" id="plannedMinutesInput" value="">
            <div class="form-group">
                <label>Customer *</label>
                <select name="user_id" required>
                    <option value="">— Select customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Console *</label>
                    <select name="console_id" required>
                        <option value="">— Select console —</option>
                        <?php foreach ($availableConsoles as $con): ?>
                        <option value="<?= $con['console_id'] ?>"><?= htmlspecialchars($con['unit_number']) ?> — <?= $con['console_type'] ?> (₱<?= $con['hourly_rate'] ?>/hr)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rental Mode *</label>
                    <select name="rental_mode" id="rentalModeSelect" required onchange="onRentalModeChange()">
                        <option value="hourly">Hourly (pre-booked)</option>
                        <option value="open_time">Open Time (bracket pricing)</option>
                        <option value="unlimited">Unlimited (flat ₱<?= htmlspecialchars($settings['unlimited_rate'] ?? '300') ?>)</option>
                    </select>
                </div>
            </div>

            <!-- Duration picker — shown only for Hourly mode -->
            <div class="form-group" id="durationPickerGroup">
                <label>Duration *</label>
                <select id="durationSelect" onchange="updateSessionPreview()">
                    <option value="">— Select duration —</option>
                    <option value="30">30 minutes — ₱50</option>
                    <option value="60">1 hour — ₱80</option>
                    <option value="90">1 hr 30 min — ₱120</option>
                    <option value="120">2 hours — ₱160</option>
                    <option value="150">2 hrs 30 min — ₱200</option>
                    <option value="180">3 hours — ₱240</option>
                    <option value="210">3 hrs 30 min — ₱280</option>
                    <option value="240">4 hours — ₱320</option>
                    <option value="270">4 hrs 30 min — ₱360</option>
                    <option value="300">5 hours — ₱400</option>
                    <option value="330">5 hrs 30 min — ₱440</option>
                    <option value="360">6 hours — ₱480</option>
                    <option value="390">6 hrs 30 min — ₱520</option>
                    <option value="420">7 hours — ₱560</option>
                    <option value="450">7 hrs 30 min — ₱600</option>
                    <option value="480">8 hours — ₱640</option>
                </select>
            </div>

            <!-- Preview card -->
            <div id="sessionPreview" style="display:none;background:rgba(32,200,161,.08);border:1px solid rgba(32,200,161,.25);border-radius:10px;padding:16px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Scheduled End</div>
                        <div id="previewEndTime" style="font-size:20px;font-weight:700;color:#20c8a1;">—</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Session Cost</div>
                        <div id="previewCost" style="font-size:20px;font-weight:700;color:#f1e1aa;">—</div>
                    </div>
                </div>
                <div id="previewOvertime" style="margin-top:10px;font-size:12px;color:#fb566b;display:none;">
                    <i class="fas fa-exclamation-triangle"></i> Overtime charges apply after scheduled end time
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                <i class="fas fa-play"></i> Start Session
            </button>
        </form>
    </div>
</div>

<!-- End Session Modal -->
<div class="modal" id="endSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session &amp; Collect Payment</h3>
            <button class="modal-close" onclick="closeModal('endSession')">&times;</button>
        </div>
        <div style="background:rgba(251,86,107,.08);border:1px solid rgba(251,86,107,.2);border-radius:10px;padding:14px;margin-bottom:20px;font-size:14px">
            <strong id="endSessionSummary">—</strong>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="session_id" id="endSessionId">
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
                    <option value="credit_card">💳 Credit Card</option>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">
                <i class="fas fa-check-circle"></i> Confirm End &amp; Record Payment
            </button>
        </form>
    </div>
</div>



<!-- ── JavaScript ────────────────────────────────────────────────────────── -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
// ── Navigation ──────────────────────────────────────────────────────────────
function showPage(page, el) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById(page).classList.add('active');
    if (el) el.classList.add('active');
    const titles = {
        dashboard: 'Dashboard', consoles: 'Console Management',
        sessions: 'Session Management',
        financial: 'Financial', reports: 'Analytics & Reports',
        settings: 'Settings'
    };
    document.getElementById('pageTitle').textContent = titles[page] || page;

    // Render charts lazily on first visit to reports
    if (page === 'reports' && !window.chartsRendered) {
        renderCharts();
        window.chartsRendered = true;
    }
}

function toggleSidebar() {
    const sidebar     = document.getElementById('sidebar');
    const topbar      = document.querySelector('.topbar');
    const mainContent = document.querySelector('.main-content');
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    topbar.style.left      = isCollapsed ? '70px'  : '260px';
    mainContent.style.marginLeft = isCollapsed ? '70px'  : '260px';
}

// ── Start Session Modal ─────────────────────────────────────────────────────
function onRentalModeChange() {
    const mode    = document.getElementById('rentalModeSelect').value;
    const group   = document.getElementById('durationPickerGroup');
    const preview = document.getElementById('sessionPreview');
    if (mode === 'hourly') {
        group.style.display = 'block';
    } else {
        group.style.display = 'none';
        preview.style.display = 'none';
        document.getElementById('plannedMinutesInput').value = '';
        document.getElementById('durationSelect').value = '';
    }
}

function updateSessionPreview() {
    const minutes = parseInt(document.getElementById('durationSelect').value);
    const input   = document.getElementById('plannedMinutesInput');
    const preview = document.getElementById('sessionPreview');
    if (!minutes) { preview.style.display = 'none'; input.value = ''; return; }

    input.value = minutes;

    // Estimated end time
    const now    = new Date();
    const endAt  = new Date(now.getTime() + minutes * 60000);
    const endStr = endAt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });

    // Base cost (mirrors PHP logic)
    const cost = (minutes <= 30) ? 50 : (minutes / 60 * 80);

    document.getElementById('previewEndTime').textContent = endStr;
    document.getElementById('previewCost').textContent    = '₱' + cost.toFixed(2);
    document.getElementById('previewOvertime').style.display = 'block';
    preview.style.display = 'block';
}

// Form validation: require duration for hourly
document.addEventListener('DOMContentLoaded', function () {
    // Show duration picker by default (hourly is default selected)
    onRentalModeChange();

    document.getElementById('startSessionForm').addEventListener('submit', function (e) {
        const mode = document.getElementById('rentalModeSelect').value;
        if (mode === 'hourly' && !document.getElementById('durationSelect').value) {
            e.preventDefault();
            alert('Please select a duration for the hourly session.');
        }
    });
});

// ── Modals ──────────────────────────────────────────────────────────────────

function openModal(name) {
    document.getElementById(name + 'Modal').classList.add('active');
}
function closeModal(name) {
    document.getElementById(name + 'Modal').classList.remove('active');
}
// Close on outside click
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
});

function openEndSessionModal(sessionId, customerName, unitNumber) {
    document.getElementById('endSessionId').value = sessionId;
    document.getElementById('endSessionSummary').textContent =
        `Ending session #${sessionId} — ${customerName} on ${unitNumber}. Cost will be calculated automatically.`;
    openModal('endSession');
}

// ── Live Session Timers ─────────────────────────────────────────────────────
const STALE_THRESHOLD = 24 * 60 * 60; // 24 hours in seconds

function pad(n) { return String(n).padStart(2, '0'); }

function updateTimers() {
    document.querySelectorAll('.session-timer[data-start]').forEach(el => {
        const start   = new Date(el.dataset.start.replace(' ', 'T') + '+08:00');
        const planned = el.dataset.planned ? parseInt(el.dataset.planned) : null;
        const now     = new Date();
        const elapsed = Math.floor((now - start) / 1000); // seconds

        // Stale session guard (>24h open — likely test/orphan data)
        if (elapsed > STALE_THRESHOLD) {
            el.classList.add('stale');
            el.textContent = `⚠️ ${Math.floor(elapsed / 86400)}d old — end session`;
            return;
        }

        if (planned) {
            // Hourly: show countdown; flip to overtime when past booked time
            const remaining = (planned * 60) - elapsed;
            if (remaining > 0) {
                const h = Math.floor(remaining / 3600);
                const m = Math.floor((remaining % 3600) / 60);
                const s = remaining % 60;
                el.style.color = '#20c8a1';
                el.textContent = (h ? h + 'h ' : '') + `${pad(m)}:${pad(s)} left`;
            } else {
                const over = -remaining;
                const m = Math.floor(over / 60);
                const s = over % 60;
                el.style.color = '#fb566b';
                el.textContent = `+${pad(m)}:${pad(s)} OVERTIME`;
            }
        } else {
            // Open Time / Unlimited: show elapsed
            const h = Math.floor(elapsed / 3600);
            const m = Math.floor((elapsed % 3600) / 60);
            const s = elapsed % 60;
            el.style.color = '';
            el.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
        }
    });
}
updateTimers();
setInterval(updateTimers, 1000);

// ── Charts ──────────────────────────────────────────────────────────────────
function renderCharts() {
    const revLabels = <?= json_encode($revLabels) ?>;
    const revData   = <?= json_encode($revChartData) ?>;
    const typeLabels= <?= json_encode($typeLabels) ?>;
    const typeCounts= <?= json_encode($typeCounts) ?>;

    const chartOpts = { responsive: true, plugins: { legend: { labels: { color: '#ccc' } } },
                        scales: { x: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,.05)' } },
                                  y: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,.05)' } } } };

    new Chart(document.getElementById('revChart'), {
        type: 'bar',
        data: {
            labels: revLabels,
            datasets: [{ label: 'Revenue (₱)', data: revData,
                backgroundColor: 'rgba(32,200,161,.5)', borderColor: '#20c8a1',
                borderWidth: 2, borderRadius: 6 }]
        },
        options: { ...chartOpts, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{ data: typeCounts,
                backgroundColor: ['rgba(95,133,218,.7)', 'rgba(32,200,161,.7)'],
                borderColor: ['#5f85da','#20c8a1'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#ccc' } } } }
    });
}

AOS.init({ duration: 600, once: true });
</script>
</body>
</html>
