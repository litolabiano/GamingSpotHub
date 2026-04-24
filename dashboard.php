<?php
/**
 * GamingSpotHub - Customer Dashboard
 * Lets logged-in customers track reservations, session history, spending, and more.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireLogin(); // only for logged-in users

// Redirect admin/owner to the admin panel instead
if (in_array($_SESSION['role'] ?? '', ['owner','shopkeeper'])) {
    header('Location: ' . getBaseUrl() . '/admin.php');
    exit;
}

require_once __DIR__ . '/includes/db_functions.php';

$user    = getCurrentUser();
$user_id = $user['user_id'];

// ── Data fetching ──────────────────────────────────────────────────────────────

// Session history (all, ordered newest-first)
$sessionHistory = getUserSessionHistory($user_id, 50);

// My reservations
$myReservations = getMyReservations($user_id);

// Active session for THIS user (if any)
$activeSession = null;
$stmt = $conn->prepare(
    "SELECT gs.*, c.console_name, c.console_type, c.unit_number
       FROM gaming_sessions gs
       JOIN consoles c ON gs.console_id = c.console_id
      WHERE gs.user_id = ? AND gs.status = 'active'
      ORDER BY gs.start_time DESC LIMIT 1"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$activeSession = $stmt->get_result()->fetch_assoc();

// Total spending (sum of transactions)
$spendStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN DATE(transaction_date) = CURDATE() THEN amount ELSE 0 END),0) AS today,
        COALESCE(SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) THEN amount ELSE 0 END),0) AS this_month,
        COALESCE(SUM(amount),0) AS total,
        COUNT(*) AS tx_count
     FROM transactions
     WHERE user_id = ? AND payment_status = 'completed'"
);
$spendStmt->bind_param('i', $user_id);
$spendStmt->execute();
$spending = $spendStmt->get_result()->fetch_assoc();

// Time played stats
$timeStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(duration_minutes),0) AS total_minutes,
        COUNT(*) AS total_sessions,
        COALESCE(AVG(duration_minutes),0) AS avg_minutes,
        COALESCE(MAX(duration_minutes),0) AS longest_session
     FROM gaming_sessions
     WHERE user_id = ? AND status = 'completed'"
);
$timeStmt->bind_param('i', $user_id);
$timeStmt->execute();
$timeStats = $timeStmt->get_result()->fetch_assoc();

// Favourite console type
$favStmt = $conn->prepare(
    "SELECT c.console_type, COUNT(*) AS cnt
       FROM gaming_sessions gs
       JOIN consoles c ON gs.console_id = c.console_id
      WHERE gs.user_id = ? AND gs.status = 'completed'
      GROUP BY c.console_type
      ORDER BY cnt DESC LIMIT 1"
);
$favStmt->bind_param('i', $user_id);
$favStmt->execute();
$favourite = $favStmt->get_result()->fetch_assoc();

// Preferred rental mode
$modStmt = $conn->prepare(
    "SELECT rental_mode, COUNT(*) AS cnt
       FROM gaming_sessions
      WHERE user_id = ? AND status = 'completed'
      GROUP BY rental_mode
      ORDER BY cnt DESC LIMIT 1"
);
$modStmt->bind_param('i', $user_id);
$modStmt->execute();
$favMode = $modStmt->get_result()->fetch_assoc();

// Upcoming reservations (pending/confirmed) for this user
$upcomingRes = array_filter($myReservations, fn($r) => in_array($r['status'], ['pending','confirmed']));

// Sessions by day (last 14 days) — for activity chart
$activityData = [];
$activityLabels = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $activityLabels[] = date('M d', strtotime($d));
    $s = $conn->prepare(
        "SELECT COALESCE(SUM(duration_minutes),0) AS mins
           FROM gaming_sessions
          WHERE user_id=? AND DATE(start_time)=? AND status='completed'"
    );
    $s->bind_param('is', $user_id, $d);
    $s->execute();
    $activityData[] = (int) $s->get_result()->fetch_assoc()['mins'];
}

// Spending by day (last 14 days) — for spend chart
$spendData  = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $s = $conn->prepare(
        "SELECT COALESCE(SUM(t.amount),0) AS rev
           FROM transactions t
           JOIN gaming_sessions gs ON t.session_id = gs.session_id
          WHERE t.user_id=? AND DATE(t.transaction_date)=? AND t.payment_status='completed'"
    );
    $s->bind_param('is', $user_id, $d);
    $s->execute();
    $spendData[] = (float) $s->get_result()->fetch_assoc()['rev'];
}

// Helper
function fmtMins(int $m): string {
    if ($m <= 0) return '0m';
    $h = intdiv($m, 60);
    $r = $m % 60;
    return $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$m}m";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard – GSpot Gaming Hub</title>
    <meta name="description" content="Track your gaming sessions, reservations, and spending at GSpot Gaming Hub.">

    <!-- Bootstrap CSS (required by the shared navbar) -->
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts -->
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">

    <!-- AOS -->
    <link href="assets/libs/aos/aos.css" rel="stylesheet">

    <!-- Main site CSS (CSS variables we need) -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Chart.js -->
    <script src="assets/libs/chartjs/chart.min.js"></script>

    <style>
        /* ══════════════════════════════════════════════════════════════════
           CUSTOMER DASHBOARD – STYLES
        ══════════════════════════════════════════════════════════════════ */
        :root {
            --mint   : #20c8a1;
            --blue   : #5f85da;
            --coral  : #fb566b;
            --purple : #b37bec;
            --gold   : #f1a83c;
            --dark   : #080e1a;
            --dark2  : #0d1117;
            --panel  : rgba(10,33,81,0.6);
            --border : rgba(95,133,218,0.18);
            --text   : #e8eaf0;
            --muted  : rgba(255,255,255,0.45);
            --radius : 14px;
            --shadow : 0 8px 32px rgba(0,0,0,0.35);
            --trans  : .22s ease;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--dark);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            min-height: 100vh;
            padding-top: 70px; /* for the site navbar */
        }

        /* ── Page wrapper ─────────────────────────────────────────────── */
        .cd-wrapper {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: calc(100vh - 70px);
        }

        /* ── Sidebar ──────────────────────────────────────────────────── */
        .cd-sidebar {
            background: linear-gradient(180deg, rgba(10,33,81,0.97) 0%, rgba(8,14,26,0.98) 100%);
            border-right: 1px solid var(--border);
            padding: 30px 16px;
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cd-sidebar-avatar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            margin-bottom: 20px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }
        .cd-avatar-circle {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--mint), var(--blue));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 18px; color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(32,200,161,0.35);
        }
        .cd-avatar-name { font-weight: 700; font-size: 14px; line-height: 1.3; }
        .cd-avatar-sub  { font-size: 11px; color: var(--mint); }

        .cd-nav-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--muted);
            font-weight: 500;
            transition: var(--trans);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
            font-family: inherit;
        }
        .cd-nav-btn i { width: 18px; text-align: center; font-size: 15px; }
        .cd-nav-btn:hover {
            background: rgba(32,200,161,0.08);
            color: var(--mint);
            transform: translateX(4px);
        }
        .cd-nav-btn.active {
            background: linear-gradient(135deg, rgba(32,200,161,0.18), rgba(95,133,218,0.12));
            color: var(--mint);
            border: 1px solid rgba(32,200,161,0.25);
            transform: translateX(4px);
        }
        .cd-nav-spacer {
            height: 1px;
            background: var(--border);
            margin: 10px 0;
        }
        .cd-sidebar-bottom { margin-top: auto; }

        /* ── Main area ────────────────────────────────────────────────── */
        .cd-main {
            padding: 30px;
            overflow-y: auto;
        }

        /* ── Page switching ───────────────────────────────────────────── */
        .cd-page { display: none; animation: cdFade .35s ease; }
        .cd-page.active { display: block; }
        @keyframes cdFade {
            from { opacity:0; transform:translateY(12px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── Section title ────────────────────────────────────────────── */
        .cd-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cd-section-title i { color: var(--mint); font-size: 20px; }

        /* ── Stats grid ───────────────────────────────────────────────── */
        .cd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; margin-bottom: 24px; }
        .cd-stat-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            transition: var(--trans);
            position: relative;
            overflow: hidden;
        }
        .cd-stat-card::before {
            content:'';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(32,200,161,0.06), transparent);
            pointer-events: none;
        }
        .cd-stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow); border-color: var(--mint); }
        .cd-stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .cd-stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .cd-stat-icon.mint   { background: rgba(32,200,161,0.15); color: var(--mint); }
        .cd-stat-icon.blue   { background: rgba(95,133,218,0.15); color: var(--blue); }
        .cd-stat-icon.coral  { background: rgba(251,86,107,0.15); color: var(--coral); }
        .cd-stat-icon.purple { background: rgba(179,123,236,0.15); color: var(--purple); }
        .cd-stat-icon.gold   { background: rgba(241,168,60,0.15); color: var(--gold); }
        .cd-stat-value { font-size: 28px; font-weight: 800; font-family: 'Outfit', sans-serif; color: #fff; }
        .cd-stat-label { font-size: 12px; color: var(--muted); margin-top: 4px; }

        /* ── Card ─────────────────────────────────────────────────────── */
        .cd-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            margin-bottom: 20px;
        }
        .cd-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .cd-card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cd-card-title i { color: var(--mint); }

        /* ── Table ────────────────────────────────────────────────────── */
        .cd-table { width: 100%; border-collapse: collapse; }
        .cd-table th {
            background: rgba(10,33,81,0.5);
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }
        .cd-table td {
            padding: 13px 12px;
            border-bottom: 1px solid rgba(95,133,218,0.08);
            font-size: 13px;
            color: var(--text);
        }
        .cd-table tr:hover td { background: rgba(32,200,161,0.04); }
        .cd-table tr:last-child td { border-bottom: none; }

        /* ── Badge ────────────────────────────────────────────────────── */
        .cd-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .cd-badge.mint    { background: rgba(32,200,161,0.15); color: var(--mint); }
        .cd-badge.blue    { background: rgba(95,133,218,0.15); color: var(--blue); }
        .cd-badge.coral   { background: rgba(251,86,107,0.15); color: var(--coral); }
        .cd-badge.purple  { background: rgba(179,123,236,0.15); color: var(--purple); }
        .cd-badge.gold    { background: rgba(241,168,60,0.15); color: var(--gold); }
        .cd-badge.gray    { background: rgba(150,150,150,0.15); color: #aaa; }

        /* ── Active session live card ─────────────────────────────────── */
        .cd-live-card {
            background: linear-gradient(135deg, rgba(32,200,161,0.14), rgba(95,133,218,0.07));
            border: 1px solid rgba(32,200,161,0.35);
            border-radius: var(--radius);
            padding: 22px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .cd-live-card::before {
            content:'';
            position: absolute; inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(32,200,161,0.08), transparent 60%);
            pointer-events: none;
        }
        .cd-live-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .cd-live-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--mint);
            box-shadow: 0 0 0 3px rgba(32,200,161,0.3);
            animation: cdPulse 1.5s infinite;
        }
        @keyframes cdPulse {
            0%,100% { box-shadow: 0 0 0 3px rgba(32,200,161,0.3); }
            50%      { box-shadow: 0 0 0 7px rgba(32,200,161,0.1); }
        }
        .cd-live-title { font-size: 14px; font-weight: 700; color: var(--mint); letter-spacing: .4px; text-transform: uppercase; }
        .cd-live-meta  { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: 12px; }
        .cd-live-item  { }
        .cd-live-label { font-size: 11px; color: var(--muted); margin-bottom: 3px; }
        .cd-live-val   { font-size: 15px; font-weight: 700; color: #fff; }
        .cd-live-timer { font-family: monospace; font-size: 22px; font-weight: 800; color: var(--mint); }

        /* ── Empty state ──────────────────────────────────────────────── */
        .cd-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        .cd-empty i { font-size: 36px; margin-bottom: 12px; display: block; opacity: .5; }
        .cd-empty p { font-size: 13px; }

        /* ── Reservation row glow ─────────────────────────────────────── */
        tr.res-today td { background: rgba(32,200,161,0.04) !important; }

        /* ── Chart container ──────────────────────────────────────────── */
        .cd-chart-wrap { position: relative; height: 180px; }

        /* ── Charts grid ──────────────────────────────────────────────── */
        .cd-charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }

        /* ── Progress bar ─────────────────────────────────────────────── */
        .cd-progress-bar {
            height: 6px;
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }
        .cd-progress-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--mint), var(--blue));
            transition: width 1s ease;
        }

        /* ── Btn ──────────────────────────────────────────────────────── */
        .cd-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: var(--trans);
            text-decoration: none;
        }
        .cd-btn-primary {
            background: linear-gradient(135deg, var(--mint), var(--blue));
            color: #fff;
            box-shadow: 0 4px 14px rgba(32,200,161,0.3);
        }
        .cd-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(32,200,161,0.4); color:#fff; }
        .cd-btn-ghost {
            background: rgba(95,133,218,0.12);
            color: var(--blue);
            border: 1px solid rgba(95,133,218,0.25);
        }
        .cd-btn-ghost:hover { background: rgba(95,133,218,0.22); transform: translateY(-2px); color:var(--blue); }

        /* ── Responsive ───────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .cd-wrapper { grid-template-columns: 1fr; }
            .cd-sidebar  { display: none; }
            .cd-charts-grid { grid-template-columns: 1fr; }
            .cd-mobile-nav {
                display: flex;
                overflow-x: auto;
                gap: 8px;
                padding: 12px 16px;
                background: var(--panel);
                border-bottom: 1px solid var(--border);
                margin: -30px -30px 24px;
            }
        }
        @media (min-width: 901px) {
            .cd-mobile-nav { display: none; }
        }

        /* Flash */
        .cd-flash {
            position: fixed; top: 80px; right: 20px; z-index: 9999;
            padding: 13px 18px; border-radius: 10px; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            animation: slideInRight .3s ease; max-width: 360px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
            backdrop-filter: blur(8px);
        }
        .cd-flash.success { background: rgba(32,200,161,.15); border: 1px solid rgba(32,200,161,.4); color: #20c8a1; }
        .cd-flash.error   { background: rgba(251,86,107,.15); border: 1px solid rgba(251,86,107,.4); color: #fb566b; }
        @keyframes slideInRight { from { transform: translateX(110%); opacity:0; } to { transform: translateX(0); opacity:1; } }

        /* scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(95,133,218,0.3); border-radius: 3px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="cd-wrapper">

    <!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
    <aside class="cd-sidebar">
        <div class="cd-sidebar-avatar">
            <div class="cd-avatar-circle"><?= getUserInitials() ?></div>
            <div>
                <div class="cd-avatar-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="cd-avatar-sub"><i class="fas fa-gamepad" style="margin-right:4px"></i>Gamer</div>
            </div>
        </div>

        <button class="cd-nav-btn active" onclick="cdShowPage('overview',this)" id="navOverview">
            <i class="fas fa-chart-line"></i> Overview
        </button>
        <button class="cd-nav-btn" onclick="cdShowPage('sessions',this)" id="navSessions">
            <i class="fas fa-play-circle"></i> Session History
        </button>
        <button class="cd-nav-btn" onclick="cdShowPage('reservations',this)" id="navReservations">
            <i class="fas fa-calendar-check"></i> My Reservations
            <?php if (count($upcomingRes) > 0): ?>
            <span style="margin-left:auto;background:var(--mint);color:#000;font-size:10px;font-weight:800;
                padding:1px 7px;border-radius:10px;min-width:18px;text-align:center;">
                <?= count($upcomingRes) ?>
            </span>
            <?php endif; ?>
        </button>
        <button class="cd-nav-btn" onclick="cdShowPage('stats',this)" id="navStats">
            <i class="fas fa-trophy"></i> My Stats
        </button>

        <div class="cd-nav-spacer"></div>

        <div class="cd-sidebar-bottom">
            <a href="reserve.php" class="cd-btn cd-btn-primary" style="width:100%;justify-content:center;margin-bottom:10px;">
                <i class="fas fa-calendar-plus"></i> New Reservation
            </a>
            <a href="index.php" class="cd-nav-btn" style="text-decoration:none;">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="auth/logout.php" class="cd-nav-btn" style="color:var(--coral);text-decoration:none;">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- ══ MAIN ═════════════════════════════════════════════════════════════ -->
    <main class="cd-main">

        <!-- Mobile nav -->
        <div class="cd-mobile-nav">
            <button class="cd-nav-btn active" onclick="cdShowPage('overview',this)" style="white-space:nowrap"><i class="fas fa-chart-line"></i> Overview</button>
            <button class="cd-nav-btn" onclick="cdShowPage('sessions',this)" style="white-space:nowrap"><i class="fas fa-play-circle"></i> Sessions</button>
            <button class="cd-nav-btn" onclick="cdShowPage('reservations',this)" style="white-space:nowrap"><i class="fas fa-calendar-check"></i> Reservations</button>
            <button class="cd-nav-btn" onclick="cdShowPage('stats',this)" style="white-space:nowrap"><i class="fas fa-trophy"></i> My Stats</button>
        </div>


        <!-- ══ PAGE: OVERVIEW ═════════════════════════════════════════════ -->
        <div class="cd-page active" id="page-overview">
            <h2 class="cd-section-title"><i class="fas fa-chart-line"></i> Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</h2>

            <?php if ($activeSession): ?>
            <!-- Live session banner -->
            <div class="cd-live-card">
                <div class="cd-live-header">
                    <div class="cd-live-dot"></div>
                    <span class="cd-live-title">You're Currently Playing</span>
                </div>
                <div class="cd-live-meta">
                    <div class="cd-live-item">
                        <div class="cd-live-label">Console</div>
                        <div class="cd-live-val"><?= htmlspecialchars($activeSession['console_type']) ?> – <?= htmlspecialchars($activeSession['unit_number']) ?></div>
                    </div>
                    <div class="cd-live-item">
                        <div class="cd-live-label">Mode</div>
                        <div class="cd-live-val">
                            <?= match($activeSession['rental_mode']) {
                                'open_time' => 'Open Time',
                                'unlimited' => 'Unlimited',
                                default => 'Hourly'
                            } ?>
                        </div>
                    </div>
                    <?php if ($activeSession['rental_mode'] === 'hourly' && $activeSession['planned_minutes']): ?>
                    <div class="cd-live-item">
                        <div class="cd-live-label">Booked Duration</div>
                        <div class="cd-live-val"><?= fmtMins((int)$activeSession['planned_minutes']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="cd-live-item">
                        <div class="cd-live-label">Elapsed</div>
                        <div class="cd-live-timer" id="liveTimer"
                             data-start="<?= $activeSession['start_time'] ?>"
                             data-planned="<?= $activeSession['planned_minutes'] ?? 0 ?>">
                            –:––
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats row -->
            <div class="cd-stats">
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value"><?= fmtMins((int)$timeStats['total_minutes']) ?></div>
                            <div class="cd-stat-label">Total Time Played</div>
                        </div>
                        <div class="cd-stat-icon mint"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value"><?= (int)$timeStats['total_sessions'] ?></div>
                            <div class="cd-stat-label">Total Sessions</div>
                        </div>
                        <div class="cd-stat-icon blue"><i class="fas fa-gamepad"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value">₱<?= number_format($spending['total'], 2) ?></div>
                            <div class="cd-stat-label">Total Spent</div>
                        </div>
                        <div class="cd-stat-icon coral"><i class="fas fa-peso-sign"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value"><?= count(array_values($upcomingRes)) ?></div>
                            <div class="cd-stat-label">Upcoming Reservations</div>
                        </div>
                        <div class="cd-stat-icon purple"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="cd-charts-grid">
                <div class="cd-card">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-clock"></i> Time Played (14 days)</div>
                    </div>
                    <div class="cd-chart-wrap">
                        <canvas id="chartActivity"></canvas>
                    </div>
                </div>
                <div class="cd-card">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-peso-sign"></i> Spending (14 days)</div>
                    </div>
                    <div class="cd-chart-wrap">
                        <canvas id="chartSpend"></canvas>
                    </div>
                </div>
            </div>

            <!-- Upcoming reservations snippet -->
            <?php if (!empty($upcomingRes)): ?>
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-calendar-check"></i> Upcoming Reservations</div>
                    <button class="cd-btn cd-btn-ghost" onclick="cdShowPage('reservations', document.getElementById('navReservations'))">See all</button>
                </div>
                <table class="cd-table">
                    <thead><tr>
                        <th>Date &amp; Time</th><th>Console</th><th>Mode</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach (array_slice(array_values($upcomingRes), 0, 3) as $r):
                        $isToday = ($r['reserved_date'] === date('Y-m-d'));
                    ?>
                    <tr class="<?= $isToday ? 'res-today' : '' ?>">
                        <td>
                            <?php if ($isToday): ?>
                            <span style="color:var(--mint);font-size:10px;font-weight:700;display:block;">TODAY</span>
                            <?php endif; ?>
                            <strong><?= date('M d, Y', strtotime($r['reserved_date'])) ?></strong><br>
                            <span style="color:var(--muted)"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                        </td>
                        <td><?= htmlspecialchars($r['console_type']) ?><?= $r['unit_number'] ? ' – <span style="color:var(--mint)">' . htmlspecialchars($r['unit_number']) . '</span>' : '' ?></td>
                        <td><?= match($r['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly'.($r['planned_minutes']?' ('.($r['planned_minutes']/60).'h)':'') } ?></td>
                        <td>
                            <?php $statusMap = ['pending'=>['gold','clock'],'confirmed'=>['mint','check-circle']]; $sm=$statusMap[$r['status']]??['gray','circle']; ?>
                            <span class="cd-badge <?= $sm[0] ?>"><i class="fas fa-<?= $sm[1] ?>" style="margin-right:4px"></i><?= ucfirst($r['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Recent sessions snippet -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-history"></i> Recent Sessions</div>
                    <button class="cd-btn cd-btn-ghost" onclick="cdShowPage('sessions', document.getElementById('navSessions'))">See all</button>
                </div>
                <?php if (empty($sessionHistory)): ?>
                <div class="cd-empty"><i class="fas fa-couch"></i><p>No sessions yet. Go play!</p></div>
                <?php else: ?>
                <table class="cd-table">
                    <thead><tr><th>#</th><th>Console</th><th>Mode</th><th>Duration</th><th>Cost</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($sessionHistory, 0, 5) as $s): ?>
                    <tr>
                        <td style="color:var(--muted)">#<?= $s['session_id'] ?></td>
                        <td><?= htmlspecialchars($s['console_type']) ?></td>
                        <td><?= match($s['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly' } ?></td>
                        <td><?= $s['duration_minutes'] !== null ? fmtMins((int)$s['duration_minutes']) : '—' ?></td>
                        <td style="color:var(--mint);font-weight:700"><?= $s['total_cost'] ? '₱'.number_format($s['total_cost'],2) : '—' ?></td>
                        <td style="color:var(--muted)"><?= date('M d, Y', strtotime($s['start_time'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>


        <!-- ══ PAGE: SESSION HISTORY ═══════════════════════════════════════ -->
        <div class="cd-page" id="page-sessions">
            <h2 class="cd-section-title"><i class="fas fa-play-circle"></i> Session History</h2>

            <!-- Quick stats row -->
            <div class="cd-stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value"><?= (int)$timeStats['total_sessions'] ?></div><div class="cd-stat-label">Total Sessions</div></div>
                        <div class="cd-stat-icon blue"><i class="fas fa-gamepad"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value"><?= fmtMins((int)$timeStats['total_minutes']) ?></div><div class="cd-stat-label">Total Play Time</div></div>
                        <div class="cd-stat-icon mint"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value"><?= fmtMins((int)$timeStats['avg_minutes']) ?></div><div class="cd-stat-label">Avg Session Length</div></div>
                        <div class="cd-stat-icon purple"><i class="fas fa-stopwatch"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value"><?= fmtMins((int)$timeStats['longest_session']) ?></div><div class="cd-stat-label">Longest Session</div></div>
                        <div class="cd-stat-icon gold"><i class="fas fa-trophy"></i></div>
                    </div>
                </div>
            </div>

            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-list"></i> All Sessions</div>
                </div>
                <?php if (empty($sessionHistory)): ?>
                <div class="cd-empty"><i class="fas fa-couch"></i><p>No sessions logged yet.</p></div>
                <?php else: ?>
                <div style="overflow-x:auto">
                <table class="cd-table">
                    <thead><tr>
                        <th>#</th><th>Console</th><th>Mode</th><th>Start</th>
                        <th>Duration</th><th>Cost</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($sessionHistory as $s): ?>
                    <tr>
                        <td style="color:var(--muted)">#<?= $s['session_id'] ?></td>
                        <td>
                            <span style="font-weight:600"><?= htmlspecialchars($s['console_type']) ?></span><br>
                            <span style="color:var(--muted);font-size:11px"><?= htmlspecialchars($s['console_name']) ?></span>
                        </td>
                        <td>
                            <?php $modeMap = ['open_time'=>['blue','Open Time'],'unlimited'=>['purple','Unlimited'],'hourly'=>['gold','Hourly']]; $mm=$modeMap[$s['rental_mode']]??['gray',ucfirst($s['rental_mode'])]; ?>
                            <span class="cd-badge <?= $mm[0] ?>"><?= $mm[1] ?></span>
                        </td>
                        <td>
                            <span style="font-weight:600"><?= date('M d, Y', strtotime($s['start_time'])) ?></span><br>
                            <span style="color:var(--muted);font-size:11px"><?= date('h:i A', strtotime($s['start_time'])) ?></span>
                        </td>
                        <td><?= $s['duration_minutes'] !== null ? fmtMins((int)$s['duration_minutes']) : '—' ?></td>
                        <td style="color:var(--mint);font-weight:700"><?= $s['total_cost'] ? '₱'.number_format($s['total_cost'],2) : '—' ?></td>
                        <td>
                            <?php $stMap=['active'=>['blue','Active'],'completed'=>['mint','Done'],'cancelled'=>['coral','Cancelled']]; $st=$stMap[$s['status']]??['gray',ucfirst($s['status'])]; ?>
                            <span class="cd-badge <?= $st[0] ?>"><?= $st[1] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>


        <!-- ══ PAGE: RESERVATIONS ══════════════════════════════════════════ -->
        <div class="cd-page" id="page-reservations">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                <h2 class="cd-section-title" style="margin:0"><i class="fas fa-calendar-check"></i> My Reservations</h2>
                <a href="reserve.php" class="cd-btn cd-btn-primary"><i class="fas fa-calendar-plus"></i> New Reservation</a>
            </div>

            <?php if (empty($myReservations)): ?>
            <div class="cd-card">
                <div class="cd-empty">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>No reservations yet.</p>
                    <a href="reserve.php" class="cd-btn cd-btn-primary" style="margin-top:16px;display:inline-flex"><i class="fas fa-plus"></i> Make a Reservation</a>
                </div>
            </div>
            <?php else: ?>

            <!-- Upcoming -->
            <?php $upcoming = array_filter($myReservations, fn($r) => in_array($r['status'],['pending','confirmed']));
                  $past     = array_filter($myReservations, fn($r) => !in_array($r['status'],['pending','confirmed']));
            ?>
            <?php if (!empty($upcoming)): ?>
            <div class="cd-card" style="border-color:rgba(32,200,161,0.35)">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-hourglass-half"></i> Upcoming</div>
                </div>
                <div style="overflow-x:auto">
                <table class="cd-table">
                    <thead><tr><th>Date &amp; Time</th><th>Console</th><th>Mode</th><th>Downpayment</th><th>Status</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $r):
                        $isToday = ($r['reserved_date'] === date('Y-m-d'));
                    ?>
                    <tr class="<?= $isToday ? 'res-today' : '' ?>">
                        <td>
                            <?php if ($isToday): ?>
                            <span style="color:var(--mint);font-size:10px;font-weight:700;display:block;">TODAY</span>
                            <?php endif; ?>
                            <strong><?= date('M d, Y', strtotime($r['reserved_date'])) ?></strong><br>
                            <span style="color:var(--muted)"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                        </td>
                        <td>
                            <?= htmlspecialchars($r['console_type']) ?>
                            <?php if ($r['unit_number']): ?>
                            <br><span style="color:var(--mint);font-size:11px;font-weight:700"><?= htmlspecialchars($r['unit_number']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= match($r['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly'.($r['planned_minutes']?' ('.($r['planned_minutes']/60).'h)':'') } ?></td>
                        <td><?= $r['downpayment_amount'] > 0 ? '<span style="color:var(--mint);font-weight:700">₱'.number_format($r['downpayment_amount'],2).'</span>' : '<span style="color:var(--muted)">–</span>' ?></td>
                        <td>
                            <?php $sm=['pending'=>['gold','clock'],'confirmed'=>['mint','check-circle']]; $sc=$sm[$r['status']]??['gray','circle']; ?>
                            <span class="cd-badge <?= $sc[0] ?>"><i class="fas fa-<?= $sc[1] ?>" style="margin-right:4px"></i><?= ucfirst($r['status']) ?></span>
                        </td>
                        <td style="color:var(--muted)"><?= $r['notes'] ? htmlspecialchars($r['notes']) : '–' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Past -->
            <?php if (!empty($past)): ?>
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-history"></i> Past Reservations</div>
                </div>
                <div style="overflow-x:auto">
                <table class="cd-table">
                    <thead><tr><th>Date &amp; Time</th><th>Console</th><th>Mode</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($past as $r): ?>
                    <tr>
                        <td>
                            <strong><?= date('M d, Y', strtotime($r['reserved_date'])) ?></strong><br>
                            <span style="color:var(--muted)"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                        </td>
                        <td><?= htmlspecialchars($r['console_type']) ?></td>
                        <td><?= match($r['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly' } ?></td>
                        <td>
                            <?php $stMap2=['converted'=>['mint','play'],'cancelled'=>['coral','times-circle'],'no_show'=>['gray','ghost']]; $sc2=$stMap2[$r['status']]??['gray','circle']; ?>
                            <span class="cd-badge <?= $sc2[0] ?>"><i class="fas fa-<?= $sc2[1] ?>" style="margin-right:4px"></i><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>


        <!-- ══ PAGE: MY STATS ══════════════════════════════════════════════ -->
        <div class="cd-page" id="page-stats">
            <h2 class="cd-section-title"><i class="fas fa-trophy"></i> My Gaming Stats</h2>

            <!-- Big stat cards -->
            <div class="cd-stats">
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value"><?= fmtMins((int)$timeStats['total_minutes']) ?></div><div class="cd-stat-label">Total Time Played</div></div>
                        <div class="cd-stat-icon mint"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value"><?= (int)$timeStats['total_sessions'] ?></div><div class="cd-stat-label">Total Sessions</div></div>
                        <div class="cd-stat-icon blue"><i class="fas fa-gamepad"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value">₱<?= number_format($spending['total'],2) ?></div><div class="cd-stat-label">Total Spent (All Time)</div></div>
                        <div class="cd-stat-icon coral"><i class="fas fa-peso-sign"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value">₱<?= number_format($spending['this_month'],2) ?></div><div class="cd-stat-label">Spent This Month</div></div>
                        <div class="cd-stat-icon gold"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
            </div>

            <!-- Personality row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                <div class="cd-card">
                    <div class="cd-card-title" style="margin-bottom:16px"><i class="fas fa-star"></i> Favourite Console</div>
                    <?php if ($favourite): ?>
                    <div style="font-size:32px;font-weight:800;color:#fff;font-family:'Outfit',sans-serif"><?= htmlspecialchars($favourite['console_type']) ?></div>
                    <div style="color:var(--muted);font-size:12px;margin-top:6px"><?= $favourite['cnt'] ?> session<?= $favourite['cnt']!==1?'s':'' ?> played</div>
                    <?php else: ?>
                    <div style="color:var(--muted)">No data yet.</div>
                    <?php endif; ?>
                </div>
                <div class="cd-card">
                    <div class="cd-card-title" style="margin-bottom:16px"><i class="fas fa-tag"></i> Preferred Mode</div>
                    <?php if ($favMode): $modeLabel = match($favMode['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly' }; ?>
                    <div style="font-size:32px;font-weight:800;color:#fff;font-family:'Outfit',sans-serif"><?= $modeLabel ?></div>
                    <div style="color:var(--muted);font-size:12px;margin-top:6px"><?= $favMode['cnt'] ?> session<?= $favMode['cnt']!==1?'s':'' ?></div>
                    <?php else: ?>
                    <div style="color:var(--muted)">No data yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Milestones -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-medal"></i> Gaming Milestones</div>
                </div>
                <?php
                    $totalH = (int)$timeStats['total_minutes'];
                    $milestones = [
                        ['label'=>'First Session',   'target'=>1,    'unit'=>'sessions', 'val'=>(int)$timeStats['total_sessions'], 'icon'=>'fa-gamepad'],
                        ['label'=>'1 Hour Played',   'target'=>60,   'unit'=>'mins',     'val'=>$totalH, 'icon'=>'fa-clock'],
                        ['label'=>'5 Hours Played',  'target'=>300,  'unit'=>'mins',     'val'=>$totalH, 'icon'=>'fa-fire'],
                        ['label'=>'10 Hours Played', 'target'=>600,  'unit'=>'mins',     'val'=>$totalH, 'icon'=>'fa-bolt'],
                        ['label'=>'50 Hours Played', 'target'=>3000, 'unit'=>'mins',     'val'=>$totalH, 'icon'=>'fa-trophy'],
                        ['label'=>'10 Sessions',     'target'=>10,   'unit'=>'sessions', 'val'=>(int)$timeStats['total_sessions'], 'icon'=>'fa-star'],
                        ['label'=>'25 Sessions',     'target'=>25,   'unit'=>'sessions', 'val'=>(int)$timeStats['total_sessions'], 'icon'=>'fa-crown'],
                    ];
                ?>
                <div style="display:grid;gap:14px">
                <?php foreach ($milestones as $ms):
                    $pct = $ms['target'] > 0 ? min(100, round($ms['val'] / $ms['target'] * 100)) : 0;
                    $done = $pct >= 100;
                ?>
                <div style="display:flex;align-items:center;gap:14px">
                    <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
                        background:<?= $done ? 'rgba(32,200,161,0.2)' : 'rgba(255,255,255,0.05)' ?>;
                        color:<?= $done ? 'var(--mint)' : 'var(--muted)' ?>;flex-shrink:0;font-size:16px">
                        <i class="fas <?= $ms['icon'] ?>"></i>
                    </div>
                    <div style="flex:1">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <span style="font-size:13px;font-weight:600;color:<?= $done ? 'var(--mint)' : 'var(--text)' ?>"><?= $ms['label'] ?></span>
                            <span style="font-size:11px;color:var(--muted)"><?= $ms['unit']==='mins' ? fmtMins($ms['val']).' / '.fmtMins($ms['target']) : $ms['val'].' / '.$ms['target'] ?></span>
                        </div>
                        <div class="cd-progress-bar">
                            <div class="cd-progress-fill" style="width:<?= $pct ?>%;background:<?= $done ? 'linear-gradient(90deg,var(--mint),var(--blue))' : 'linear-gradient(90deg,var(--blue),var(--purple))' ?>"></div>
                        </div>
                    </div>
                    <?php if ($done): ?>
                    <span style="font-size:16px">🏆</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Spending breakdown -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-peso-sign"></i> Spending Breakdown</div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">Today</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--mint)">₱<?= number_format($spending['today'],2) ?></div>
                    </div>
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">This Month</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--blue)">₱<?= number_format($spending['this_month'],2) ?></div>
                    </div>
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">All Time</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--purple)">₱<?= number_format($spending['total'],2) ?></div>
                    </div>
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">Avg / Session</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--gold)">
                            ₱<?= $timeStats['total_sessions'] > 0 ? number_format($spending['total'] / $timeStats['total_sessions'], 2) : '0.00' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
/* ══ Navigation ══════════════════════════════════════════════════════════════ */
function cdShowPage(page, el) {
    document.querySelectorAll('.cd-page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.cd-nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    if (el) el.classList.add('active');
    history.replaceState(null, '', '#' + page);
}

// Restore page from hash
(function () {
    const hash = location.hash.replace('#', '');
    const valid = ['overview','sessions','reservations','stats'];
    if (hash && valid.includes(hash)) {
        const navId = { overview:'navOverview', sessions:'navSessions', reservations:'navReservations', stats:'navStats' };
        cdShowPage(hash, document.getElementById(navId[hash]));
    }
})();

/* ══ Live session timer ══════════════════════════════════════════════════════ */
(function () {
    const el = document.getElementById('liveTimer');
    if (!el) return;
    const startUtc = new Date(el.dataset.start.replace(' ', 'T') + '+08:00').getTime();
    const planned  = parseInt(el.dataset.planned, 10) || 0;

    function tick() {
        const elapsed = Math.floor((Date.now() - startUtc) / 1000);
        const h = Math.floor(elapsed / 3600);
        const m = Math.floor((elapsed % 3600) / 60);
        const s = elapsed % 60;
        const pad = n => String(n).padStart(2, '0');
        el.textContent = (h ? pad(h) + ':' : '') + pad(m) + ':' + pad(s);

        if (planned > 0) {
            const remaining = planned * 60 - elapsed;
            if (remaining < 0) el.style.color = '#fb566b';  // overtime
        }
    }
    tick();
    setInterval(tick, 1000);
})();

/* ══ Charts ══════════════════════════════════════════════════════════════════ */
const activityLabels = <?= json_encode($activityLabels) ?>;
const activityData   = <?= json_encode($activityData) ?>;
const spendData      = <?= json_encode($spendData) ?>;

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#666', font: { size: 10 }, maxRotation: 45, minRotation: 0 } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#666', font: { size: 10 } }, beginAtZero: true }
    }
};

// Activity chart
new Chart(document.getElementById('chartActivity'), {
    type: 'bar',
    data: {
        labels: activityLabels,
        datasets: [{
            data: activityData,
            backgroundColor: 'rgba(32,200,161,0.35)',
            borderColor: '#20c8a1',
            borderWidth: 2,
            borderRadius: 6,
            hoverBackgroundColor: 'rgba(32,200,161,0.55)',
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            ...chartDefaults.scales,
            y: { ...chartDefaults.scales.y, ticks: { ...chartDefaults.scales.y.ticks,
                callback: val => val >= 60 ? Math.floor(val/60)+'h' : val+'m'
            }}
        }
    }
});

// Spend chart
new Chart(document.getElementById('chartSpend'), {
    type: 'line',
    data: {
        labels: activityLabels,
        datasets: [{
            data: spendData,
            borderColor: '#5f85da',
            backgroundColor: 'rgba(95,133,218,0.12)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#5f85da',
            pointHoverRadius: 6
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            ...chartDefaults.scales,
            y: { ...chartDefaults.scales.y, ticks: { ...chartDefaults.scales.y.ticks,
                callback: val => '₱' + val
            }}
        }
    }
});
</script>
<!-- Bootstrap JS (navbar mobile toggler) -->
<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>

<script>
// ── Navbar scroll effect (same as index.php) ──────────────────────────────
AOS.init({ duration: 800, once: true, offset: 80 });

const mainNav = document.getElementById('mainNav');
if (mainNav) {
    window.addEventListener('scroll', function () {
        if (window.scrollY > 100) {
            mainNav.classList.add('scrolled');
        } else {
            mainNav.classList.remove('scrolled');
        }
    });
    // Apply immediately in case page is already scrolled on load
    if (window.scrollY > 100) mainNav.classList.add('scrolled');
}
</script></body>
</html>
