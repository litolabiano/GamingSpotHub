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

//  Data fetching 

// Session history (all, ordered newest-first)
$sessionHistory = getUserSessionHistory($user_id, 50);

// My reservations
$myReservations = getMyReservations($user_id);

// Build sets of rescheduled reservations
$userRescheduledIds = [];
$allRescheduledIds = [];
if (!empty($myReservations)) {
    $rStmt = $conn->prepare(
        "SELECT reservation_id, rescheduled_by FROM reservation_reschedules
          WHERE user_id = ? AND status != 'rejected'"
    );
    $rStmt->bind_param('i', $user_id);
    $rStmt->execute();
    foreach ($rStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $allRescheduledIds[$row['reservation_id']] = true;
        if ((int)$row['rescheduled_by'] === (int)$user_id) {
            $userRescheduledIds[$row['reservation_id']] = true;
        }
    }
}

$banStmt = $conn->prepare("SELECT consecutive_cancellations, reservation_banned_until FROM users WHERE user_id = ?");
$banStmt->bind_param('i', $user_id);
$banStmt->execute();
$banData = $banStmt->get_result()->fetch_assoc();
$isBanned  = !empty($banData['reservation_banned_until']) && strtotime($banData['reservation_banned_until']) > time();
$banExpiry = $isBanned ? date('F j, Y \a\t g:i A', strtotime($banData['reservation_banned_until'])) : '';
$cancelStreak = (int)($banData['consecutive_cancellations'] ?? 0);

$rescheduleStmt = $conn->prepare(
    "SELECT rs.reschedule_id, rs.reservation_id, rs.old_date, rs.old_time,
            rs.new_date, rs.new_time, rs.reason, rs.reason_detail, rs.created_at, rs.status, rs.initiated_by,
            r.console_type, c.unit_number
       FROM reservation_reschedules rs
       JOIN reservations r ON rs.reservation_id = r.reservation_id
       LEFT JOIN consoles c ON r.console_id = c.console_id
      WHERE rs.user_id = ? AND rs.seen_by_user = 0
      ORDER BY rs.created_at DESC"
);
$rescheduleStmt->bind_param('i', $user_id);
$rescheduleStmt->execute();
$unseenReschedules = $rescheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$rescheduleReasonLabels = [
    'typhoon'       => 'ðŸŒ€ Typhoon / Bad Weather',
    'power_outage'  => 'âš¡ Power Outage',
    'emergency'     => 'ðŸš¨ Emergency',
    'maintenance'   => 'ðŸ”§ Equipment Maintenance',
    'other'         => 'ðŸ“‹ Other Reason',
];

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

// Pending extension request for the active session (if any)
$pendingExtension = null;
if ($activeSession) {
    $extStmt = $conn->prepare(
        "SELECT extension_id, extra_minutes, extra_cost, status, requested_at
           FROM session_extensions
          WHERE session_id = ? AND requested_by = ? AND status = 'pending'
          ORDER BY requested_at DESC LIMIT 1"
    );
    $extStmt->bind_param('ii', $activeSession['session_id'], $user_id);
    $extStmt->execute();
    $pendingExtension = $extStmt->get_result()->fetch_assoc();
}

// Total spending (sum of transactions)
$opDay = getOperatingDay();
[$opStart, $opEnd] = getOperatingDayBounds($opDay);
$spendStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END),0) AS today,
        COALESCE(SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) THEN amount ELSE 0 END),0) AS this_month,
        COALESCE(SUM(amount),0) AS total,
        COUNT(*) AS tx_count
     FROM transactions
     WHERE user_id = ? AND payment_status = 'completed'"
);
$spendStmt->bind_param('ssi', $opStart, $opEnd, $user_id);
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
$upcomingRes = array_filter($myReservations, fn($r) => in_array($r['status'], ['pending','reserved']));

// Sessions by day (last 14 days) â€” for activity chart
$activityData = [];
$activityLabels = [];
$nowTs = time();
for ($i = 13; $i >= 0; $i--) {
    // We want the operating day that was $i days before the current operating day
    $targetOpDay = getOperatingDay(date('Y-m-d H:i:s', strtotime("-{$i} days", $nowTs)));
    [$sBound, $eBound] = getOperatingDayBounds($targetOpDay);
    
    $activityLabels[] = date('M d', strtotime($targetOpDay));
    $s = $conn->prepare(
        "SELECT COALESCE(SUM(duration_minutes),0) AS mins
           FROM gaming_sessions
          WHERE user_id=? AND (start_time BETWEEN ? AND ?) AND status='completed'"
    );
    $s->bind_param('iss', $user_id, $sBound, $eBound);
    $s->execute();
    $activityData[] = (int) $s->get_result()->fetch_assoc()['mins'];
}

// Spending by day (last 14 days) â€” for spend chart
$spendData  = [];
for ($i = 13; $i >= 0; $i--) {
    $targetOpDay = getOperatingDay(date('Y-m-d H:i:s', strtotime("-{$i} days", $nowTs)));
    [$sBound, $eBound] = getOperatingDayBounds($targetOpDay);
    
    $s = $conn->prepare(
        "SELECT COALESCE(SUM(t.amount),0) AS rev
           FROM transactions t
          WHERE t.user_id=? AND (t.transaction_date BETWEEN ? AND ?) AND t.payment_status='completed'
            AND t.amount > 0"
    );
    $s->bind_param('iss', $user_id, $sBound, $eBound);
    $s->execute();
    $spendData[] = (float) $s->get_result()->fetch_assoc()['rev'];
}

//  My Payments (all transactions for this user) 
$myPaymentsStmt = $conn->prepare(
    "SELECT t.*,
            CASE
              WHEN t.payment_note LIKE 'Downpayment%' THEN 'reservation'
              WHEN t.amount < 0 THEN 'refund'
              ELSE COALESCE(gs.rental_mode, 'other')
            END AS tx_type,
            c.unit_number,
            r.reserved_date, r.paymongo_payment_id, r.paymongo_source_id
       FROM transactions t
       LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
       LEFT JOIN consoles c ON gs.console_id = c.console_id
       LEFT JOIN reservations r
             ON t.payment_note LIKE '%reservation #%'
            AND r.reservation_id = CAST(
                  SUBSTRING_INDEX(SUBSTRING_INDEX(t.payment_note, '#', -1), ' ', 1)
                AS UNSIGNED)
      WHERE t.user_id = ?
      ORDER BY t.transaction_date DESC
      LIMIT 50"
);
$myPaymentsStmt->bind_param('i', $user_id);
$myPaymentsStmt->execute();
$myPayments = $myPaymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$myPaymentsCount = count($myPayments);

//  My Cancellations 
$myCancels = [];
$cancelLog = $conn->prepare(
    "SELECT rc.cancel_id, rc.reservation_id, rc.cancelled_by,
            rc.cancel_reason_type, rc.cancel_reason_detail,
            rc.cancelled_at, rc.refund_issued,
            r.console_type, r.rental_mode, r.reserved_date,
            r.downpayment_amount
       FROM reservation_cancellations rc
       JOIN reservations r ON rc.reservation_id = r.reservation_id
      WHERE rc.user_id = ?
      ORDER BY rc.cancelled_at DESC
      LIMIT 50"
);
$cancelLog->bind_param('i', $user_id);
$cancelLog->execute();
$myCancels = $cancelLog->get_result()->fetch_all(MYSQLI_ASSOC);

$myCancelCount = count($myCancels);

//  My Tournament Registrations 
$myTournaments = [];
$tStmt = $conn->prepare(
    "SELECT tp.participant_id, tp.tournament_id, tp.registration_date,
            tp.payment_status, tp.ign, tp.contact_number, tp.notes,
            tp.placement, tp.prize_amount,
            t.tournament_name, t.game_name, t.console_type,
            t.start_date, t.end_date, t.entry_fee, t.prize_pool,
            t.status AS tournament_status
       FROM tournament_participants tp
       JOIN tournaments t ON tp.tournament_id = t.tournament_id
      WHERE tp.user_id = ?
      ORDER BY tp.registration_date DESC"
);
$tStmt->bind_param('i', $user_id);
$tStmt->execute();
$myTournaments     = $tStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$myTournamentCount = count($myTournaments);
$myActiveTournamentCount = count(array_filter($myTournaments, fn($t) => in_array($t['tournament_status'], ['scheduled','ongoing'])));

// Reason label map (shared with JS)
$cancelReasonLabelMap = [
    'schedule_change'   => 'Schedule Changed',
    'found_alternative' => 'Found Alternative',
    'budget_issue'      => 'Budget Issue',
    'technical_issue'   => 'Technical Issue',
    'emergency'         => 'Emergency',
    'other'             => 'Other',
    'admin_decision'    => 'Admin Decision',
];

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
    <title>My Dashboard â€“ GSpot Gaming Hub</title>
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
        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           CUSTOMER DASHBOARD â€“ STYLES
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
        html { scroll-behavior: smooth; overflow-x: hidden; }
        body {
            background: var(--dark);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /*  Page wrapper (not a grid â€” sidebar is fixed)  */
        .cd-wrapper {
            display: block;
            min-height: 100vh;
        }

        /*  Sidebar (fixed, like admin)  */
        .cd-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #07101f;
            border-right: 1px solid rgba(255,255,255,0.06);
            box-shadow: 4px 0 24px rgba(0,0,0,0.35);
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .cd-sidebar::-webkit-scrollbar { width: 3px; }
        .cd-sidebar::-webkit-scrollbar-track { background: transparent; }
        .cd-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        /*  Sidebar header (logo area â€” mirrors admin sidebar-header)  */
        .cd-sidebar-header {
            display: flex;
            align-items: center;
            padding: 0 16px;
            height: 64px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
            gap: 8px;
            text-decoration: none;
        }
        .cd-sidebar-header .logo-container {
            display: flex;
            align-items: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 1.5rem;
            white-space: nowrap;
        }
        .cd-sidebar-header .logo-text {
            font-size: 0.52rem;
            letter-spacing: 2px;
            color: var(--gold);
            margin-left: 7px;
            white-space: nowrap;
        }

        /*  Sidebar user section (below logo)  */

        .cd-sidebar-avatar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin: 8px 8px 4px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius);
            flex-shrink: 0;
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
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--muted);
            font-weight: 500;
            transition: var(--trans);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 13.5px;
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

        /*  Topbar (fixed, right of sidebar â€” mirrors admin topbar)  */
        .cd-topbar {
            position: fixed;
            left: 240px;
            top: 0;
            right: 0;
            height: 60px;
            background: #0a0f1c;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 999;
        }
        .cd-topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cd-topbar-title {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cd-topbar-title .cd-live-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--mint);
            box-shadow: 0 0 0 3px rgba(32,200,161,.25);
            animation: cdPulse 1.5s infinite;
            display: inline-block;
        }
        .cd-topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        /* user pill in topbar */
        .cd-topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 50px;
            padding: 5px 14px 5px 6px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            position: relative;
        }
        .cd-topbar-user:hover {
            background: rgba(255,255,255,0.09);
            border-color: rgba(255,255,255,0.14);
        }
        .cd-topbar-user .cd-avatar-sm {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--mint), var(--blue));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 12px; color: #fff;
            flex-shrink: 0;
        }
        .cd-topbar-user .cd-tu-name {
            font-size: 13px; font-weight: 600; color: var(--text);
            line-height: 1.2;
        }
        .cd-topbar-user .cd-tu-role {
            font-size: 10px; color: var(--mint); font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .cd-topbar-user .fa-chevron-down {
            font-size: 9px; color: rgba(255,255,255,0.35); margin-left: 2px;
            transition: transform 0.2s;
        }
        .cd-topbar-user.open .fa-chevron-down { transform: rotate(180deg); }
        /* topbar dropdown */
        .cd-tu-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 220px;
            background: rgba(10,15,30,0.97);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 6px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.55);
            opacity: 0; visibility: hidden;
            transform: translateY(-8px) scale(0.96);
            transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
            z-index: 9999;
        }
        .cd-topbar-user.open .cd-tu-menu {
            opacity: 1; visibility: visible;
            transform: translateY(0) scale(1);
        }
        .cd-tu-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.65);
            text-decoration: none; font-size: 13px; font-weight: 500;
            transition: background 0.18s, color 0.18s;
        }
        .cd-tu-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .cd-tu-item.danger { color: rgba(251,86,107,0.8); }
        .cd-tu-item.danger:hover { background: rgba(251,86,107,0.1); color: #fb566b; }
        .cd-tu-divider { height: 1px; background: rgba(255,255,255,0.08); margin: 4px 0; }

        /* Nav items: inside padded area */
        .cd-sidebar-nav { padding: 8px; flex: 1; display: flex; flex-direction: column; gap: 2px; }

        /*  Main area (margin-based, like admin .main-content)  */
        .cd-main {
            margin-left: 240px;
            margin-top: 60px;
            padding: 28px;
            min-height: calc(100vh - 60px);
            min-width: 0;
        }

        /*  Account page: two-column layout  */
        .cd-account-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 1100px) {
            .cd-account-grid { grid-template-columns: 1fr; }
        }

        /*  Page switching  */
        .cd-page { display: none; animation: cdFade .35s ease; }
        .cd-page.active { display: block; }
        @keyframes cdFade {
            from { opacity:0; transform:translateY(12px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /*  Section title  */
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

        /*  Stats grid  */
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

        /*  Card  */
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

        /*  Table  */
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

        /*  Badge  */
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

        /*  Cancel reservation button  */
        .cd-cancel-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 8px; border: 1px solid rgba(251,86,107,.35);
            background: rgba(251,86,107,.06); color: #fb566b;
            font-size: 11px; font-weight: 700; cursor: pointer;
            transition: background .2s, border-color .2s;
            white-space: nowrap;
        }
        .cd-cancel-btn:hover {
            background: rgba(251,86,107,.15); border-color: rgba(251,86,107,.6);
        }

        /*  Active session live card  */
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

        /*  Empty state  */
        .cd-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        .cd-empty i { font-size: 36px; margin-bottom: 12px; display: block; opacity: .5; }
        .cd-empty p { font-size: 13px; }

        /*  Reservation row glow  */
        tr.res-today td { background: rgba(32,200,161,0.04) !important; }

        /*  Chart container  */
        .cd-chart-wrap { position: relative; height: 180px; }

        /*  Charts grid  */
        .cd-charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .cd-2col-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /*  Responsive  */        @media (max-width: 900px) {
            /* Hard-clip the viewport â€” nothing bleeds horizontally */
            html, body { overflow-x: hidden; max-width: 100vw; }

            /* Topbar & sidebar stack on mobile */
            .cd-topbar { left: 0; }
            .cd-sidebar { display: none; }

            /* Main content area: no left margin on mobile, pad bottom for bottom nav */
            .cd-main {
                margin-left: 0;
                margin-top: 60px;
                padding: 20px 16px 90px;
                overflow-x: hidden;
                max-width: 100%;
                box-sizing: border-box;
            }

            /* Old horizontal scroll nav â€” completely hidden on mobile now */
            .cd-mobile-nav { display: none !important; }

            /* Topbar welcome text: shorter on small screens */
            .cd-topbar-title { font-size: 14px; }
            .cd-topbar-title .cd-live-dot { display: none; }

            /*  Bottom navigation bar  */
            .cd-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0; left: 0; right: 0; width: 100%; height: 64px;
                background: rgba(10, 20, 45, 0.97);
                border-top: 1px solid rgba(95,133,218,0.2);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                z-index: 1000;
                align-items: stretch;
                box-shadow: 0 -4px 24px rgba(0,0,0,0.4);
            }
            .cd-bnav-btn {
                flex: 1; display: flex; flex-direction: column;
                align-items: center; justify-content: center;
                gap: 3px; background: none; border: none; cursor: pointer;
                color: rgba(255,255,255,0.45); font-family: inherit;
                font-size: 10px; font-weight: 600; letter-spacing: .3px;
                padding: 8px 4px; transition: color .2s; position: relative;
                -webkit-tap-highlight-color: transparent;
            }
            .cd-bnav-btn i { font-size: 18px; transition: color .2s, transform .2s; }
            .cd-bnav-btn.active { color: var(--mint); }
            .cd-bnav-btn.active i { transform: translateY(-2px); }
            .cd-bnav-btn.active::after {
                content: ''; position: absolute; top: 0; left: 50%;
                transform: translateX(-50%); width: 32px; height: 3px;
                background: var(--mint); border-radius: 0 0 4px 4px;
            }
            .cd-bnav-badge {
                position: absolute; top: 6px; right: calc(50% - 14px);
                background: var(--mint); color: #000;
                font-size: 9px; font-weight: 800;
                min-width: 16px; height: 16px; border-radius: 8px;
                display: flex; align-items: center; justify-content: center;
                padding: 0 4px; pointer-events: none;
            }
            .cd-bnav-badge.coral { background: var(--coral); color: #fff; }

            /* Content grids on mobile */
            .cd-stats { grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; }
            .cd-charts-grid, .cd-2col-grid { grid-template-columns: 1fr; }
            .cd-live-meta { grid-template-columns: 1fr 1fr; }
            .cd-section-title { font-size: 18px; margin-bottom: 16px; }
            .cd-card { padding: 16px; }
            .cd-account-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .cd-stats    { grid-template-columns: 1fr 1fr; gap: 10px; }
            .cd-live-meta { grid-template-columns: 1fr; }
            .cd-stat-value { font-size: 22px; }
        }
        @media (min-width: 901px) {
            .cd-mobile-nav  { display: none; }
            .cd-bottom-nav  { display: none !important; }
        }


        /*  Profile Edit Form  */
        .pf-field-group { display: flex; flex-direction: column; gap: 6px; }
        .pf-label {
            font-size: 12px; font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase; letter-spacing: .5px;
            display: flex; align-items: center; gap: 6px;
        }
        .pf-input {
            background: rgba(10,33,81,0.4);
            border: 1px solid rgba(95,133,218,0.25);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
            box-sizing: border-box;
        }
        .pf-input:focus {
            border-color: rgba(32,200,161,0.55);
            box-shadow: 0 0 0 3px rgba(32,200,161,0.08);
        }
        .pf-input::placeholder { color: rgba(255,255,255,0.25); }

        /* Flash â€” sit below the new topbar */
        .cd-flash {
            position: fixed; top: 72px; right: 20px; z-index: 9999;
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

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           LOGO & NAVBAR ALIGNMENT â€” dashboard-specific
           Shifts the Bootstrap .container left-padding so the navbar brand
           (GSpot logo) visually aligns with the sidebar/content boundary
           rather than the raw viewport edge.
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
        @media (min-width: 901px) {
            #mainNav .container {
                padding-left: calc(240px + 16px);
            }
        }

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           UI CONSISTENCY â€” align user dashboard sidebar with admin panel
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

        /* Sidebar background: match admin's #07101f deep dark */
        .cd-sidebar {
            background: linear-gradient(180deg, #07101f 0%, #080e1a 100%) !important;
            box-shadow: 4px 0 24px rgba(0,0,0,0.35);
        }

        /* Avatar block: tighter, matches admin's compact header feel */
        .cd-sidebar-avatar {
            background: rgba(255,255,255,0.03) !important;
            border-color: rgba(255,255,255,0.06) !important;
        }
        .cd-avatar-name { font-size: 13px; }
        .cd-avatar-sub  { font-size: 10.5px; letter-spacing: 0.3px; }

        /* Nav buttons: match admin .nav-item height of 44px */
        .cd-nav-btn {
            height: 40px;
            padding: 0 14px !important;
            border-radius: 10px;
            color: rgba(255,255,255,0.55) !important;
        }
        .cd-nav-btn:hover {
            background: rgba(255,255,255,0.06) !important;
            color: rgba(255,255,255,0.9) !important;
            transform: none !important;
        }
        .cd-nav-btn:hover i { color: var(--mint); }
        .cd-nav-btn.active {
            background: rgba(32,200,161,0.12) !important;
            color: #fff !important;
            border: 1px solid rgba(32,200,161,0.2) !important;
            transform: none !important;
        }
        .cd-nav-btn.active i { color: var(--mint); }

        /* Nav spacer: match admin divider */
        .cd-nav-spacer {
            background: rgba(255,255,255,0.06) !important;
            margin: 6px 0 !important;
        }

        /* Cards: align border opacity with admin */
        .cd-card {
            border-color: rgba(95,133,218,0.1) !important;
        }
        .cd-card:hover { border-color: rgba(95,133,218,0.18) !important; }

        /* Main content: match admin .main-content padding */
        @media (min-width: 901px) {
            .cd-main { padding: 28px !important; }
        }
    </style>
</head>
<?php
    $base_url  = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/GamingSpotHub';
    $firstName = htmlspecialchars(explode(' ', $user['full_name'])[0]);
?>
<body>

<!-- â•â• SIDEBAR â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<aside class="cd-sidebar">

    <!-- Logo header (mirrors admin sidebar-header) -->
    <a class="cd-sidebar-header" href="<?= $base_url ?>/#home">
        <div class="logo-container">
            <span class="logo-g">G</span><span class="logo-s">s</span><span class="logo-p">p</span><span class="logo-o">o</span><span class="logo-t">t</span>
            <span class="logo-text">GAMING HUB</span>
        </div>
    </a>

    <!-- User avatar / identity block (below logo) -->
    <div class="cd-sidebar-avatar">
        <div class="cd-avatar-circle"><?= getUserInitials() ?></div>
        <div>
            <div class="cd-avatar-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="cd-avatar-sub"><i class="fas fa-gamepad" style="margin-right:4px"></i>Gamer</div>
        </div>
    </div>

    <!-- Nav items -->
    <div class="cd-sidebar-nav">
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
        <button class="cd-nav-btn" onclick="cdShowPage('payments',this)" id="navPayments">
            <i class="fas fa-receipt"></i> My Payments
            <?php if ($myPaymentsCount > 0): ?>
            <span style="margin-left:auto;background:rgba(32,200,161,.9);color:#000;font-size:10px;font-weight:800;
                padding:1px 7px;border-radius:10px;min-width:18px;text-align:center;">
                <?= $myPaymentsCount ?>
            </span>
            <?php endif; ?>
        </button>
        <button class="cd-nav-btn" onclick="cdShowPage('cancellations',this)" id="navCancellations">
            <i class="fas fa-ban"></i> My Cancellations
            <?php if ($myCancelCount > 0): ?>
            <span style="margin-left:auto;background:rgba(251,86,107,.9);color:#fff;font-size:10px;font-weight:800;
                padding:1px 7px;border-radius:10px;min-width:18px;text-align:center;">
                <?= $myCancelCount ?>
            </span>
            <?php endif; ?>
        </button>
        <button class="cd-nav-btn" onclick="cdShowPage('tournaments',this)" id="navTournaments">
            <i class="fas fa-trophy"></i> My Tournaments
            <?php if ($myActiveTournamentCount > 0): ?>
            <span style="margin-left:auto;background:linear-gradient(135deg,#f1a83c,#fb566b);color:#fff;font-size:10px;font-weight:800;
                padding:1px 7px;border-radius:10px;min-width:18px;text-align:center;">
                <?= $myActiveTournamentCount ?>
            </span>
            <?php endif; ?>
        </button>

        <div class="cd-nav-spacer"></div>

        <button class="cd-nav-btn" onclick="cdShowPage('account',this)" id="navAccount">
            <i class="fas fa-user-cog"></i> My Account
        </button>
    </div>

</aside>

<!-- â•â• TOPBAR â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="cd-topbar" id="cdTopbar">
    <!-- Left: Welcome message (dynamic, from session) -->
    <div class="cd-topbar-left">
        <h3 class="cd-topbar-title">
            Welcome back, <strong style="color:var(--mint);margin-left:5px;"><?= $firstName ?>!</strong>
            <span class="cd-live-dot" style="margin-left:6px;"></span>
        </h3>
    </div>

    <!-- Right: User dropdown pill -->
    <div class="cd-topbar-right">
        <div class="cd-topbar-user" id="cdTopbarUserBtn">
            <div class="cd-avatar-sm"><?= getUserInitials() ?></div>
            <div>
                <div class="cd-tu-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="cd-tu-role">Gamer</div>
            </div>
            <i class="fas fa-chevron-down"></i>

            <!-- Dropdown menu -->
            <div class="cd-tu-menu" id="cdTopbarMenu">
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;margin-bottom:2px;">
                    <div class="cd-avatar-sm" style="width:38px;height:38px;font-size:14px;flex-shrink:0;"><?= getUserInitials() ?></div>
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#e8eaed;"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.4);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
                <div class="cd-tu-divider"></div>
                <a href="<?= $base_url ?>/index.php" class="cd-tu-item">
                    <i class="fas fa-home" style="width:16px;text-align:center;"></i> Go to Homepage
                </a>
                <a href="<?= $base_url ?>/reserve.php" class="cd-tu-item">
                    <i class="fas fa-calendar-plus" style="width:16px;text-align:center;"></i> Reserve a Console
                </a>
                <div class="cd-tu-divider"></div>
                <a href="<?= $base_url ?>/auth/logout.php" class="cd-tu-item danger">
                    <i class="fas fa-sign-out-alt" style="width:16px;text-align:center;"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- â•â• MAIN CONTENT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<main class="cd-main">

    <!-- Mobile nav placeholder (hidden on desktop) -->
    <div class="cd-mobile-nav"></div>


        <!-- â•â• PAGE: OVERVIEW â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="cd-page active" id="page-overview">
            <h2 class="cd-section-title"><i class="fas fa-chart-line"></i> Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</h2>

            <?php if (!empty($unseenReschedules)): ?>
            <!--  Reschedule notification banners  -->
            <div id="reschedule-notifications">
                <?php foreach ($unseenReschedules as $rs): ?>
                <div id="rn-<?= $rs['reschedule_id'] ?>" style="
                    background: linear-gradient(135deg, rgba(32,200,161,.1), rgba(95,133,218,.08));
                    border: 1px solid rgba(32,200,161,.4);
                    border-radius: 16px;
                    padding: 18px 22px;
                    margin-bottom: 14px;
                    display: flex;
                    gap: 16px;
                    align-items: flex-start;
                    position: relative;">
                    <div style="width:42px;height:42px;border-radius:12px;flex-shrink:0;
                        background:rgba(32,200,161,.15);color:#20c8a1;
                        display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;font-size:14px;margin-bottom:4px;
                            color:<?= ($rs['status']==='pending' && $rs['initiated_by']==='admin') ? '#f1a83c' : '#20c8a1' ?>;">
                            <?php if ($rs['status']==='pending' && $rs['initiated_by']==='admin'): ?>
                                â³ Action Required: Confirm Your New Schedule
                            <?php else: ?>
                                ðŸ“… Your Reservation Has Been Rescheduled
                            <?php endif; ?>
                        </div>
                        <div style="font-size:13px;color:#ccc;line-height:1.7;">
                            <strong style="color:#fff;">Reservation #<?= $rs['reservation_id'] ?></strong>
                            (<?= htmlspecialchars($rs['console_type']) ?><?= $rs['unit_number'] ? ' - ' . htmlspecialchars($rs['unit_number']) : '' ?>) has been rescheduled by staff.<br>
                            <span style="color:#888;">From:</span>
                            <strong style="color:#fff;"><?= date('M d, Y', strtotime($rs['old_date'])) ?> at <?= date('g:i A', strtotime($rs['old_time'])) ?></strong><br>
                            <span style="color:#888;">To:</span>
                            <strong style="color:#20c8a1;"><?= date('M d, Y', strtotime($rs['new_date'])) ?> at <?= date('g:i A', strtotime($rs['new_time'])) ?></strong><br>
                            <span style="color:#888;">Reason:</span>
                            <span style="color:#f1a83c;"><?= $rescheduleReasonLabels[$rs['reason']] ?? ucfirst($rs['reason']) ?></span>
                            <?php if ($rs['reason_detail']): ?>
                            â€” <em style="color:#aaa;"><?= htmlspecialchars($rs['reason_detail']) ?></em>
                            <?php endif; ?>
                            <?php if ($rs['status'] === 'pending' && $rs['initiated_by'] === 'admin'): ?>
                                <div style="display:flex;gap:10px;margin-top:12px;">
                                    <button onclick="openRescheduleConfirmModal(<?= $rs['reschedule_id'] ?>, '<?= $rs['new_date'] ?>', '<?= substr($rs['new_time'],0,5) ?>')"
                                        style="flex:1;background:linear-gradient(135deg,rgba(32,200,161,.2),rgba(32,200,161,.1));border:1px solid rgba(32,200,161,.45);color:#20c8a1;border-radius:8px;padding:9px 14px;cursor:pointer;font-size:13px;font-weight:700;transition:all .2s;">
                                        <i class="fas fa-calendar-check"></i> Choose My Date
                                    </button>
                                    <button onclick="respondReschedule(<?= $rs['reschedule_id'] ?>, 'cancel')"
                                        style="background:rgba(251,86,107,.15);border:1px solid rgba(251,86,107,.4);color:#fb566b;border-radius:8px;padding:9px 14px;cursor:pointer;font-size:13px;font-weight:700;transition:all .2s;">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </div>
                            <?php endif; ?>
                    </div>
                    </div>
                    <?php if ($rs['status'] !== 'pending'): ?>
                    <button onclick="dismissReschedule(<?= $rs['reschedule_id'] ?>)"
                        style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
                        color:#aaa;border-radius:8px;padding:6px 10px;cursor:pointer;font-size:12px;
                        flex-shrink:0;transition:all .2s;"
                        onmouseover="this.style.background='rgba(251,86,107,.15)';this.style.color='#fb566b'"
                        onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.color='#aaa'"
                        title="Dismiss this notification">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <script>
            function dismissReschedule(id) {
                fetch('ajax/dismiss_reschedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'reschedule_id=' + id
                }).then(() => {
                    const el = document.getElementById('rn-' + id);
                    if (el) { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }
                });
            }
            function openRescheduleConfirmModal(id, minDate, preTime) {
                document.getElementById('rcmRescheduleId').value = id;
                const dateInput  = document.getElementById('rcmDate');
                const timeSelect = document.getElementById('rcmTime');
                dateInput.min    = minDate;
                dateInput.value  = minDate;
                if (timeSelect && preTime) timeSelect.value = preTime;
                const d = new Date(minDate + 'T00:00:00');
                document.getElementById('rcmProposedDate').textContent =
                    d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
                document.getElementById('rescheduleConfirmModal').style.display = 'flex';
            }
            function closeRescheduleConfirmModal() {
                document.getElementById('rescheduleConfirmModal').style.display = 'none';
            }
            function submitRescheduleConfirm() {
                const id   = document.getElementById('rcmRescheduleId').value;
                const date = document.getElementById('rcmDate').value;
                const time = document.getElementById('rcmTime').value;
                if (!date) { alert('Please select a date.'); return; }
                const btn = document.getElementById('rcmSubmitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';
                fetch('ajax/respond_reschedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'reschedule_id=' + id + '&action=confirm&chosen_date=' + encodeURIComponent(date) + '&chosen_time=' + encodeURIComponent(time)
                }).then(r => r.json()).then(d => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Date';
                    if (d.success) { closeRescheduleConfirmModal(); location.reload(); }
                    else alert(d.message);
                }).catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Date';
                    alert('Network error. Please try again.');
                });
            }
            function respondReschedule(id, action) {
                if (action === 'cancel' && !confirm('Are you sure you want to decline this reschedule? Your reservation will remain on its original date.')) return;
                fetch('ajax/respond_reschedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'reschedule_id=' + id + '&action=' + action
                }).then(r => r.json()).then(d => {
                    alert(d.message);
                    if (d.success) location.reload();
                });
            }
            </script>
            <?php endif; ?>

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
                        <div class="cd-live-val"><?= htmlspecialchars($activeSession['console_type']) ?> â€“ <?= htmlspecialchars($activeSession['unit_number']) ?></div>
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
                            â€“:â€“â€“
                        </div>
                    </div>
                </div>

                <?php if ($activeSession['rental_mode'] === 'hourly'): ?>
                <!-- Extend Time action row -->
                <div style="margin-top:18px;padding-top:16px;border-top:1px solid rgba(32,200,161,0.15);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <?php if ($pendingExtension): ?>
                    <div style="display:flex;align-items:center;gap:8px;background:rgba(241,168,60,.1);border:1px solid rgba(241,168,60,.3);border-radius:9px;padding:8px 14px;font-size:13px;color:#f1a83c;">
                        <i class="fas fa-hourglass-half"></i>
                        Extension request for <strong>+<?= $pendingExtension['extra_minutes'] ?> min</strong> is <strong>pending staff approval</strong>
                    </div>
                    <?php else: ?>
                    <button class="cd-btn cd-btn-blue" id="reqExtBtn" onclick="openReqExtModal()">
                        <i class="fas fa-clock"></i> Request More Time
                    </button>
                    <span style="font-size:12px;color:var(--muted);">Ask staff to approve extra gaming time</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
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
                            <div class="cd-stat-value">₱ <?= number_format($spending['total'], 2) ?></div>
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
                        <td><?= htmlspecialchars($r['console_type']) ?><?= $r['unit_number'] ? ' â€“ <span style="color:var(--mint)">' . htmlspecialchars($r['unit_number']) . '</span>' : '' ?></td>
                        <td><?= match($r['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly'.($r['planned_minutes']?' ('.($r['planned_minutes']/60).'h)':'') } ?></td>
                        <td>
                            <?php $statusMap = ['pending'=>['gold','clock'],'reserved'=>['mint','check-circle']]; $sm=$statusMap[$r['status']]??['gray','circle']; ?>
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
                        <td><?= $s['duration_minutes'] !== null ? fmtMins((int)$s['duration_minutes']) : 'â€”' ?></td>
                        <td style="color:var(--mint);font-weight:700"><?= $s['total_cost'] ? '₱ '.number_format($s['total_cost'],2) : 'â€”' ?></td>
                        <td style="color:var(--muted)"><?= date('M d, Y', strtotime($s['start_time'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Tournament snippet (show if registered) -->
            <?php if (!empty($myTournaments)): $overviewTour = array_slice($myTournaments, 0, 2); ?>
            <div class="cd-card" style="border-color:rgba(241,168,60,0.3);background:linear-gradient(135deg,rgba(10,33,81,0.6),rgba(8,14,26,0.7));">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-trophy" style="color:#f1a83c;"></i> My Tournaments</div>
                    <button class="cd-btn cd-btn-ghost" onclick="cdShowPage('tournaments', document.getElementById('navTournaments'))">See all</button>
                </div>
                <div style="display:grid;gap:10px;">
                <?php foreach ($overviewTour as $ot):
                    $otStatus = $ot['tournament_status'];
                    $otColor  = match($otStatus) { 'scheduled' => '#20c8a1', 'ongoing' => '#20c8a1', 'completed' => '#5f85da', default => '#fb566b' };
                ?>
                <div style="display:flex;align-items:center;gap:14px;padding:12px 14px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid rgba(241,168,60,0.12);">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(241,168,60,0.12);border:1px solid rgba(241,168,60,0.25);
                                display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">ðŸ†</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:13px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($ot['tournament_name']) ?>
                        </div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.45);margin-top:2px;">
                            IGN: <span style="color:var(--mint);"><?= htmlspecialchars($ot['ign'] ?? 'â€”') ?></span>
                            &nbsp;&middot;&nbsp;<?= date('M d, Y', strtotime($ot['start_date'])) ?>
                        </div>
                    </div>
                    <span style="background:<?= $otColor ?>22;color:<?= $otColor ?>;border:1px solid <?= $otColor ?>44;border-radius:20px;padding:3px 10px;font-size:10px;font-weight:700;white-space:nowrap;">
                        <?= ucfirst($otStatus) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                </div>
                <?php if ($myTournamentCount > 2): ?>
                <div style="padding:10px 0 0;font-size:12px;color:var(--muted);text-align:right;">
                    +<?= $myTournamentCount - 2 ?> more &mdash;
                    <button onclick="cdShowPage('tournaments', document.getElementById('navTournaments'))" style="background:none;border:none;color:#f1a83c;font-size:12px;cursor:pointer;padding:0;">View all &rarr;</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>


        <!-- â•â• PAGE: SESSION HISTORY â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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
                        <td><?= $s['duration_minutes'] !== null ? fmtMins((int)$s['duration_minutes']) : 'â€”' ?></td>
                        <td style="color:var(--mint);font-weight:700"><?= $s['total_cost'] ? '₱ '.number_format($s['total_cost'],2) : 'â€”' ?></td>
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


        <!-- â•â• PAGE: RESERVATIONS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="cd-page" id="page-reservations">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                <h2 class="cd-section-title" style="margin:0"><i class="fas fa-calendar-check"></i> My Reservations</h2>
                <?php if (!$isBanned): ?>
                <a href="reserve.php" class="cd-btn cd-btn-primary"><i class="fas fa-calendar-plus"></i> New Reservation</a>
                <?php endif; ?>
            </div>

            <?php if ($isBanned): ?>
            <!--  Reservation Ban Alert  -->
            <div style="background:rgba(251,86,107,.1);border:1px solid rgba(251,86,107,.4);border-radius:16px;
                        padding:18px 22px;margin-bottom:24px;display:flex;align-items:flex-start;gap:16px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(251,86,107,.2);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:#fb566b;">
                    <i class="fas fa-ban"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#fb566b;font-size:15px;margin-bottom:4px;">
                        Online Reservations Suspended
                    </div>
                    <div style="font-size:13px;color:rgba(255,255,255,.7);line-height:1.7;">
                        Your account has been placed on a temporary <strong>1-week reservation ban</strong> due to 3 consecutive cancellations.
                        This restriction will be <strong style="color:#fb566b;">automatically lifted on <?= $banExpiry ?></strong>.
                        <br>You are still welcome to walk in and use any available unit â€” only online reservations are restricted.
                    </div>
                </div>
            </div>
            <?php elseif ($cancelStreak === 2): ?>
            <!--  2nd-Strike Warning  -->
            <div style="background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.35);border-radius:16px;
                        padding:16px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;">
                <div style="width:40px;height:40px;border-radius:12px;background:rgba(241,168,60,.18);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px;color:#f1a83c;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#f1a83c;font-size:14px;margin-bottom:3px;">
                         Final Warning 2 of 3 Cancellations Used
                    </div>
                    <div style="font-size:13px;color:rgba(255,255,255,.7);line-height:1.6;">
                        You have <strong>2 consecutive cancellations</strong> on record. One more cancellation will result in a
                        <strong style="color:#fb566b;">7-day reservation ban</strong>. Successfully completing your next reservation will reset this counter.
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
            <?php 
                $upcoming = array_filter($myReservations, fn($r) => in_array($r['status'],['pending','reserved']));
                $past     = array_filter($myReservations, fn($r) => !in_array($r['status'],['pending','reserved']));
                
                $adminReschedReqs = [];
                if (!empty($unseenReschedules)) {
                    foreach ($unseenReschedules as $rs) {
                        if ($rs['status'] === 'pending' && $rs['initiated_by'] === 'admin') {
                            $adminReschedReqs[$rs['reservation_id']] = $rs;
                        }
                    }
                }
            ?>
            <?php if (!empty($upcoming)): ?>
            <div class="cd-card" style="border-color:rgba(32,200,161,0.35)">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-hourglass-half"></i> Upcoming</div>
                </div>
                <div style="overflow-x:auto">
                <table class="cd-table">
                    <thead><tr><th>Date &amp; Time</th><th>Console</th><th>Mode</th><th>Payment</th><th>Status</th><th>Notes</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $r):
                        $isToday = ($r['reserved_date'] === date('Y-m-d'));
                        $rid = (int)$r['reservation_id'];
                        $pendingAdminResched = $adminReschedReqs[$rid] ?? null;
                    ?>
                    <tr class="<?= $isToday ? 'res-today' : '' ?>">
                        <td>
                            <?php if ($isToday && !$pendingAdminResched): ?>
                            <span style="color:var(--mint);font-size:10px;font-weight:700;display:block;">TODAY</span>
                            <?php endif; ?>
                            <?php if ($pendingAdminResched): ?>
                                <div style="text-decoration:line-through;color:var(--muted);font-size:11px;margin-bottom:4px;">
                                    <?= date('M d, Y', strtotime($pendingAdminResched['old_date'])) ?> at <?= date('h:i A', strtotime($pendingAdminResched['old_time'])) ?>
                                </div>
                                <div style="color:var(--gold);">
                                    <strong><?= date('M d, Y', strtotime($pendingAdminResched['new_date'])) ?></strong><br>
                                    <span style="font-size:11px;"><?= date('h:i A', strtotime($pendingAdminResched['new_time'])) ?></span>
                                </div>
                            <?php else: ?>
                                <strong><?= date('M d, Y', strtotime($r['reserved_date'])) ?></strong><br>
                                <span style="color:var(--muted)"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($r['console_type']) ?>
                            <?php if ($r['unit_number']): ?>
                            <br><span style="color:var(--mint);font-size:11px;font-weight:700"><?= htmlspecialchars($r['unit_number']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= match($r['rental_mode']) { 'open_time'=>'Open Time','unlimited'=>'Unlimited', default=>'Hourly'.($r['planned_minutes']?' ('.($r['planned_minutes']/60).'h)':'') } ?></td>
                        <td><?= $r['downpayment_amount'] > 0 ? '<span style="color:var(--mint);font-weight:700">₱ '.number_format($r['downpayment_amount'],2).'</span>' : '<span style="color:var(--muted)">â€“</span>' ?></td>
                        <td>
                            <?php $sm=['pending'=>['gold','clock'],'reserved'=>['mint','check-circle']]; $sc=$sm[$r['status']]??['gray','circle']; ?>
                            <span class="cd-badge <?= $sc[0] ?>"><i class="fas fa-<?= $sc[1] ?>" style="margin-right:4px"></i><?= ucfirst($r['status']) ?></span>
                        </td>
                        <td style="color:var(--muted)">
                            <?php if ($pendingAdminResched): ?>
                                <span style="color:var(--gold);font-weight:700;font-size:11px;display:block;margin-bottom:2px;">Admin Reschedule</span>
                                <span style="font-size:10px;"><?= $rescheduleReasonLabels[$pendingAdminResched['reason']] ?? ucfirst($pendingAdminResched['reason']) ?></span>
                                <?php if ($pendingAdminResched['reason_detail']): ?>
                                    <br><span style="font-size:10px;color:var(--muted);">- <?= htmlspecialchars($pendingAdminResched['reason_detail']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= $r['notes'] ? htmlspecialchars($r['notes']) : 'â€“' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $rDate        = htmlspecialchars($r['reserved_date']);
                                $rTime        = substr($r['reserved_time'], 0, 5);
                                $rConsole     = htmlspecialchars($r['console_type']);
                                $alreadyResched = !empty($userRescheduledIds[$rid]);
                            ?>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                <?php if ($pendingAdminResched): ?>
                                    <button onclick="openRescheduleConfirmModal(<?= $pendingAdminResched['reschedule_id'] ?>, '<?= $pendingAdminResched['new_date'] ?>', '<?= substr($pendingAdminResched['new_time'],0,5) ?>')" class="cd-btn" style="background:rgba(32,200,161,.15);border:1px solid rgba(32,200,161,.4);color:#20c8a1;font-size:11px;padding:4px 10px;">
                                        <i class="fas fa-calendar-check"></i> Confirm
                                    </button>
                                    <button onclick="respondReschedule(<?= $pendingAdminResched['reschedule_id'] ?>, 'cancel')" class="cd-btn" style="background:rgba(251,86,107,.15);border:1px solid rgba(251,86,107,.4);color:#fb566b;font-size:11px;padding:4px 10px;">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                <?php elseif ($r['status'] === 'reserved'): ?>
                                    <?php if (!$alreadyResched): ?>
                                        <button onclick="openUserRescheduleModal(<?= $rid ?>, '<?= $rDate ?>', '<?= $rTime ?>', '<?= $rConsole ?>')" class="cd-btn" style="background:rgba(95,133,218,.15);border:1px solid rgba(95,133,218,.4);color:#5f85da;font-size:11px;padding:4px 10px;">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="openCancelModal(this)" data-id="<?= $rid ?>" class="cd-btn" style="background:rgba(251,86,107,.15);border:1px solid rgba(251,86,107,.4);color:#fb566b;font-size:11px;padding:4px 10px;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php else: ?>
                                    <?php if (!$alreadyResched): ?>
                                <button onclick="openUserRescheduleModal(<?= $rid ?>, '<?= $rDate ?>', '<?= $rTime ?>', '<?= addslashes($rConsole) ?>')" class="cd-btn" style="background:rgba(95,133,218,.15);border:1px solid rgba(95,133,218,.4);color:#5f85da;font-size:11px;padding:4px 10px;">
                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                </button>
                                <?php else: ?>
                                <span style="font-size:10px;color:var(--muted);font-style:italic;"><i class="fas fa-info-circle"></i> Rescheduled once</span>
                                <?php endif; ?>
                                <button onclick="openCancelModal(this)" data-id="<?= $rid ?>" data-type="<?= addslashes($rConsole) ?>" data-date="<?= $rDate ?>" data-time="<?= $rTime ?>" class="cd-btn" style="background:rgba(251,86,107,.15);border:1px solid rgba(251,86,107,.4);color:#fb566b;font-size:11px;padding:4px 10px;">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            <?php endif; ?>
                            </div>
                        
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
                            <?php
                              $stMap2 = [
                                'converted' => ['mint','play'],
                                'cancelled' => ['coral','times-circle'],
                                'no_show'   => ['gray','ghost'],
                              ];
                              $sc2 = $stMap2[$r['status']] ?? ['gray','circle'];
                              
                              $displayStatus = ucfirst(str_replace('_',' ',$r['status']));
                              if ($r['status'] === 'converted') {
                                  if (!empty($allRescheduledIds[$r['reservation_id']])) {
                                      $displayStatus = 'Rescheduled';
                                  } else {
                                      $displayStatus = 'Reserved';
                                  }
                              }
                            ?>
                            <span class="cd-badge <?= $sc2[0] ?>">
                                <i class="fas fa-<?= $sc2[1] ?>" style="margin-right:4px"></i>
                                <?= $displayStatus ?>
                            </span>
                            <?php if ($r['status'] === 'cancelled' && !empty($r['cancelled_by'])): ?>
                            <div style="font-size:10px;color:#888;margin-top:3px;">
                                <?php if ($r['cancelled_by'] === 'user'): ?>
                                    <i class="fas fa-user" style="margin-right:3px;"></i>Cancelled by you
                                <?php else: ?>
                                    <i class="fas fa-user-shield" style="margin-right:3px;"></i>Cancelled by staff
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
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

        <!--  Cancel Reservation Modal (two-step: Reason â†’ Confirm)  -->
        <div id="cancelResModal" style="display:none;position:fixed;inset:0;z-index:9999;
             background:rgba(0,0,0,.7);backdrop-filter:blur(6px);
             align-items:center;justify-content:center;">
            <div style="background:#0e1d36;border:1px solid rgba(251,86,107,.35);border-radius:18px;
                        padding:28px 28px 24px;max-width:460px;width:94%;box-shadow:0 20px 60px rgba(0,0,0,.6);">

                <!-- Modal header -->
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(251,86,107,.15);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-triangle-exclamation" style="color:#fb566b;"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;color:#fff;font-size:15px;">Cancel Reservation</div>
                        <div style="font-size:12px;color:#888;" id="cancelResSubtitle">Reservation #...</div>
                    </div>
                </div>

                <!-- STEP 1: Reason picker -->
                <div id="cancelStep1">
                    <p style="color:#ccc;font-size:13px;line-height:1.6;margin-bottom:14px;">
                        Please tell us why you want to cancel. A reason is required.
                    </p>

                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">Reason *</label>
                        <select id="cancelReasonType" style="
                            width:100%;background:rgba(10,33,81,.7);
                            border:1px solid rgba(95,133,218,.3);
                            color:#f0f0f0;padding:11px 14px;border-radius:10px;
                            font-size:14px;font-family:inherit;outline:none;">
                            <option value="" disabled selected>-- Select a reason --</option>
                            <option value="schedule_change">My schedule changed</option>
                            <option value="found_alternative">Found a better alternative</option>
                            <option value="budget_issue">Budget / financial reason</option>
                            <option value="technical_issue">Technical or system issue</option>
                            <option value="emergency">Personal emergency</option>
                            <option value="other">Other reason&hellip;</option>
                        </select>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;"
                               id="cancelDetailLabel">Additional Details (Optional)</label>
                        <textarea id="cancelReasonDetail" rows="3" placeholder="Describe your reason..." style="
                            width:100%;background:rgba(10,33,81,.7);
                            border:1px solid rgba(95,133,218,.3);
                            color:#f0f0f0;padding:11px 14px;border-radius:10px;
                            font-size:13px;font-family:inherit;outline:none;
                            resize:vertical;box-sizing:border-box;"></textarea>
                    </div>

                    <!-- No-refund reminder -->
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;
                                border-radius:10px;background:rgba(251,86,107,.07);
                                border:1px solid rgba(251,86,107,.2);margin-bottom:16px;">
                        <i class="fas fa-ban" style="color:#fb566b;margin-top:2px;flex-shrink:0;"></i>
                        <span style="font-size:12px;color:#e0a0a8;line-height:1.5;">
                            <strong style="color:#fb566b;">No-Refund Policy:</strong>
                            Payments are non-refundable. No refund will be issued for this cancellation.
                        </span>
                    </div>

                    <div style="display:flex;gap:10px;">
                        <button id="cancelNextBtn"
                            onclick="cancelStep1Next()"
                            style="flex:1;padding:11px;border-radius:10px;border:none;
                                   background:linear-gradient(135deg,#fb566b,#e03050);color:#fff;
                                   font-weight:700;font-size:13px;cursor:pointer;transition:.2s;">
                            <i class="fas fa-arrow-right"></i> Next
                        </button>
                        <button onclick="closeCancelModal()"
                            style="flex:1;padding:11px;border-radius:10px;
                                   border:1px solid rgba(255,255,255,.15);background:transparent;
                                   color:#aaa;font-weight:700;font-size:13px;cursor:pointer;">
                            Keep Reservation
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Confirmation -->
                <div id="cancelStep2" style="display:none;">
                    <p style="color:#ccc;font-size:13px;line-height:1.6;margin-bottom:14px;">
                        Are you sure you want to cancel this reservation?
                    </p>

                    <!-- Selected reason display -->
                    <div style="padding:12px 14px;border-radius:10px;
                                background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.2);
                                margin-bottom:12px;">
                        <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;">Reason for Cancellation</div>
                        <div id="cancelReasonDisplay" style="font-size:13px;color:#d0d8f0;font-weight:600;"></div>
                        <div id="cancelDetailDisplay" style="font-size:12px;color:#888;margin-top:4px;display:none;"></div>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:16px;">
                        <button id="cancelResConfirmBtn"
                            onclick="submitCancelReservation()"
                            style="flex:1;padding:11px;border-radius:10px;border:none;
                                   background:linear-gradient(135deg,#fb566b,#e03050);color:#fff;
                                   font-weight:700;font-size:13px;cursor:pointer;transition:.2s;">
                            <i class="fas fa-times"></i> Yes, Cancel It
                        </button>
                        <button onclick="cancelStep2Back()"
                            style="flex:1;padding:11px;border-radius:10px;
                                   border:1px solid rgba(255,255,255,.15);background:transparent;
                                   color:#aaa;font-weight:700;font-size:13px;cursor:pointer;">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>

            </div>
        </div>


        <!--  User-Initiated Reschedule Modal  -->
        <div id="userRescheduleModal" style="display:none;position:fixed;inset:0;z-index:9999;
             background:rgba(0,0,0,.7);backdrop-filter:blur(6px);
             align-items:center;justify-content:center;">
            <div style="background:#0e1d36;border:1px solid rgba(95,133,218,.35);border-radius:18px;
                        padding:28px 28px 24px;max-width:460px;width:94%;box-shadow:0 20px 60px rgba(0,0,0,.6);">
                
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(95,133,218,.15);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-calendar-alt" style="color:#5f85da;"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;color:#fff;font-size:15px;">Reschedule Reservation</div>
                        <div style="font-size:12px;color:#888;" id="urmSubtitle">Reservation #...</div>
                    </div>
                </div>

                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;margin-bottom:18px;">
                    <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Current Schedule</div>
                    <div style="font-size:13px;color:#fff;font-weight:600;" id="urmCurrentSched">...</div>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">New Date</label>
                    <input type="date" id="urmDate" style="
                        width:100%;background:rgba(10,33,81,.7);
                        border:1px solid rgba(95,133,218,.3);
                        color:#f0f0f0;padding:11px 14px;border-radius:10px;
                        font-size:14px;font-family:inherit;outline:none;"
                        min="<?= date('Y-m-d') ?>"
                        max="<?= date('Y-m-d', strtotime('+1 month')) ?>">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">New Time</label>
                    <select id="urmTime" style="
                        width:100%;background:rgba(10,33,81,.7);
                        border:1px solid rgba(95,133,218,.3);
                        color:#f0f0f0;padding:11px 14px;border-radius:10px;
                        font-size:14px;font-family:inherit;outline:none;">
                        <?php for($h=12;$h<=23;$h++): ?>
                            <?php 
                                $val = sprintf("%02d:00", $h);
                                $lbl = date("g:i A", strtotime($val));
                            ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                            <?php if($h<23): ?>
                                <?php 
                                    $val2 = sprintf("%02d:30", $h);
                                    $lbl2 = date("g:i A", strtotime($val2));
                                ?>
                                <option value="<?= $val2 ?>"><?= $lbl2 ?></option>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </select>
                </div>

                <div style="display:flex;gap:10px;">
                    <button id="urmSubmitBtn"
                        onclick="submitUserReschedule()"
                        style="flex:1;padding:11px;border-radius:10px;border:none;
                               background:linear-gradient(135deg,#5f85da,#2b59c3);color:#fff;
                               font-weight:700;font-size:13px;cursor:pointer;transition:.2s;">
                        <i class="fas fa-calendar-check"></i> Request Reschedule
                    </button>
                    <button onclick="closeUserRescheduleModal()"
                        style="flex:1;padding:11px;border-radius:10px;
                               border:1px solid rgba(255,255,255,.15);background:transparent;
                               color:#aaa;font-weight:700;font-size:13px;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <div id="dashToast" style="display:none;position:fixed;bottom:24px;right:24px;z-index:10000;
             padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;
             box-shadow:0 8px 32px rgba(0,0,0,.4);max-width:360px;transition:opacity .3s;"></div>



        <!-- â•â• PAGE: MY STATS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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
                        <div><div class="cd-stat-value">₱ <?= number_format($spending['total'],2) ?></div><div class="cd-stat-label">Total Spent (All Time)</div></div>
                        <div class="cd-stat-icon coral"><i class="fas fa-peso-sign"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div><div class="cd-stat-value">₱ <?= number_format($spending['this_month'],2) ?></div><div class="cd-stat-label">Spent This Month</div></div>
                        <div class="cd-stat-icon gold"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
            </div>

            <!-- Personality row -->
            <div class="cd-2col-grid">
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
                    <span style="font-size:16px">ðŸ†</span>
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
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:14px">
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">Today</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--mint)">₱ <?= number_format($spending['today'],2) ?></div>
                    </div>
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">This Month</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--blue)">₱ <?= number_format($spending['this_month'],2) ?></div>
                    </div>
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">All Time</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--purple)">₱ <?= number_format($spending['total'],2) ?></div>
                    </div>
                    <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px">
                        <div style="font-size:13px;color:var(--muted);margin-bottom:6px">Avg / Session</div>
                        <div style="font-size:24px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--gold)">
                            ₱ <?= $timeStats['total_sessions'] > 0 ? number_format($spending['total'] / $timeStats['total_sessions'], 2) : '0.00' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â• PAGE: MY CANCELLATIONS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php
            // Aggregate per-reason counts for mini doughnut
            $myReasonCounts = [];
            foreach ($myCancels as $mc) {
                $rt = $mc['cancel_reason_type'] ?? 'other';
                $myReasonCounts[$rt] = ($myReasonCounts[$rt] ?? 0) + 1;
            }
            $myReasonChartLabels = array_map(fn($k) => $cancelReasonLabelMap[$k] ?? ucfirst($k), array_keys($myReasonCounts));
            $myReasonChartCounts = array_values($myReasonCounts);

            // Count user vs admin cancellations
            $myUserCancelCnt  = count(array_filter($myCancels, fn($c) => $c['cancelled_by'] === 'user'));
            $myAdminCancelCnt = count(array_filter($myCancels, fn($c) => $c['cancelled_by'] === 'admin'));
        ?>
        <div class="cd-page" id="page-cancellations">
            <h2 class="cd-section-title"><i class="fas fa-ban" style="color:var(--coral)"></i> My Cancellations</h2>

            <!-- Stat cards -->
            <div class="cd-stats" style="margin-bottom:24px">
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value" style="color:#fb566b"><?= $myCancelCount ?></div>
                            <div class="cd-stat-label">Total Cancellations</div>
                        </div>
                        <div class="cd-stat-icon coral"><i class="fas fa-ban"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value" style="color:#f1a83c"><?= $myUserCancelCnt ?></div>
                            <div class="cd-stat-label">You Cancelled</div>
                        </div>
                        <div class="cd-stat-icon gold"><i class="fas fa-user-times"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value" style="color:#b37bec"><?= $myAdminCancelCnt ?></div>
                            <div class="cd-stat-label">Admin Cancelled</div>
                        </div>
                        <div class="cd-stat-icon purple"><i class="fas fa-shield-alt"></i></div>
                    </div>
                </div>
            </div>

            <?php if ($myCancelCount > 0): ?>
            <!-- Charts row -->
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:24px">
                <div class="cd-card">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-chart-pie"></i> Reasons</div>
                    </div>
                    <div style="position:relative;height:200px;display:flex;align-items:center;justify-content:center">
                        <canvas id="myCancelReasonChart"></canvas>
                    </div>
                </div>
                <div class="cd-card">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-info-circle"></i> What This Means</div>
                    </div>
                    <div style="padding:8px 0">
                        <?php
                        $streak = (int)($banData['consecutive_cancellations'] ?? 0);
                        $streakPct = min(100, $streak / 3 * 100);
                        ?>
                        <div style="margin-bottom:16px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                                <span style="font-size:13px;color:var(--text)">Cancellation Streak</span>
                                <span style="font-size:13px;font-weight:700;color:<?= $streak >= 2 ? 'var(--coral)' : 'var(--mint)' ?>">
                                    <?= $streak ?>/3
                                </span>
                            </div>
                            <div class="cd-progress-bar">
                                <div class="cd-progress-fill" style="width:<?= $streakPct ?>%;background:<?= $streak >= 2 ? 'linear-gradient(90deg,#f1a83c,#fb566b)' : 'linear-gradient(90deg,var(--mint),var(--blue))' ?>"></div>
                            </div>
                            <div style="font-size:11px;color:var(--muted);margin-top:6px">
                                <?php if ($isBanned): ?>
                                <span style="color:var(--coral)"><i class="fas fa-ban" style="margin-right:4px"></i>You are currently banned from reservations until <?= $banExpiry ?>.</span>
                                <?php elseif ($streak >= 2): ?>
                                <span style="color:#f1a83c"><i class="fas fa-exclamation-triangle" style="margin-right:4px"></i>Warning â€” 1 more cancellation will trigger a 7-day ban.</span>
                                <?php else: ?>
                                <span style="color:var(--muted)">Good standing â€” no restrictions.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="background:rgba(251,86,107,.06);border:1px solid rgba(251,86,107,.15);border-radius:10px;padding:12px 14px;font-size:12px;color:rgba(255,255,255,.6);line-height:1.7">
                            <i class="fas fa-lightbulb" style="color:#f1a83c;margin-right:6px"></i>
                            Consistent cancellations can lead to a reservation ban. Walk-in access is never affected.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancellations table -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-history"></i> Cancellation History</div>
                </div>
                <div style="overflow-x:auto">
                <table class="cd-table">
                    <thead><tr>
                        <th>Date Cancelled</th>
                        <th>Console</th>
                        <th>Mode</th>
                        <th>Reserved For</th>
                        <th>Reason</th>
                        <th>Cancelled By</th>
                        <th>Downpayment</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($myCancels as $mc):
                        $rtLabel = $cancelReasonLabelMap[$mc['cancel_reason_type']] ?? ucfirst(str_replace('_',' ',$mc['cancel_reason_type']));
                        $modeLabel = match($mc['rental_mode']) {
                            'open_time' => 'Open Time',
                            'unlimited' => 'Unlimited',
                            default     => 'Hourly'
                        };
                    ?>
                    <tr>
                        <td>
                            <strong><?= date('M d, Y', strtotime($mc['cancelled_at'])) ?></strong><br>
                            <span style="color:var(--muted);font-size:11px"><?= date('h:i A', strtotime($mc['cancelled_at'])) ?></span>
                        </td>
                        <td><?= htmlspecialchars($mc['console_type']) ?></td>
                        <td><?= $modeLabel ?></td>
                        <td><?= date('M d, Y', strtotime($mc['reserved_date'])) ?></td>
                        <td>
                            <span class="cd-badge coral" style="font-size:10px"><?= htmlspecialchars($rtLabel) ?></span>
                            <?php if (!empty($mc['cancel_reason_detail'])): ?>
                            <div style="font-size:11px;color:var(--muted);margin-top:3px;max-width:180px;white-space:normal">
                                <?= htmlspecialchars(mb_strimwidth($mc['cancel_reason_detail'], 0, 60, 'â€¦')) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($mc['cancelled_by'] === 'user'): ?>
                            <span class="cd-badge gold"><i class="fas fa-user" style="margin-right:4px"></i>You</span>
                            <?php else: ?>
                            <span class="cd-badge purple"><i class="fas fa-shield-alt" style="margin-right:4px"></i>Staff</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((float)$mc['downpayment_amount'] > 0): ?>
                            <span style="color:var(--coral)">₱ <?= number_format($mc['downpayment_amount'], 2) ?></span>
                            <?php else: ?>
                            <span style="color:var(--muted)">â€”</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php else: ?>
            <div class="cd-empty">
                <i class="fas fa-check-circle" style="color:var(--mint)"></i>
                <p>No cancellations on record â€” great track record! ðŸŽ‰</p>
            </div>
            <?php endif; ?>
        </div><!-- /page-cancellations -->

        <!-- Mini reason chart for cancellations page -->
        <script>
        (function(){
            <?php if ($myCancelCount > 0 && !empty($myReasonChartLabels)): ?>
            const mcrCtx = document.getElementById('myCancelReasonChart');
            if (mcrCtx) {
                new Chart(mcrCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($myReasonChartLabels) ?>,
                        datasets: [{
                            data: <?= json_encode($myReasonChartCounts) ?>,
                            backgroundColor: ['#fb566b','#f1a83c','#5f85da','#20c8a1','#b37bec','#38bdf8','#fb923c'],
                            borderWidth: 2,
                            borderColor: '#0d1117',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,.55)', font: { size: 10 }, padding: 8 } }
                        }
                    }
                });
            }
            <?php endif; ?>
        })();
        </script>

        <!-- â•â• PAGE: MY TOURNAMENTS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php
            $upcomingTournaments  = array_filter($myTournaments, fn($t) => in_array($t['tournament_status'], ['scheduled','ongoing']));
            $completedTournaments = array_filter($myTournaments, fn($t) => in_array($t['tournament_status'], ['completed','cancelled']));
            $paidTournaments      = array_filter($myTournaments, fn($t) => $t['payment_status'] === 'paid');
            $tournamentStatusMap  = [
                'scheduled' => ['gold',   'clock',       'Scheduled'],
                'ongoing'   => ['mint',   'circle',      'Ongoing'],
                'completed' => ['blue',   'check-circle','Completed'],
                'cancelled' => ['coral',  'times-circle','Cancelled'],
                'upcoming'  => ['purple', 'calendar',    'Upcoming'],
            ];
        ?>
        <div class="cd-page" id="page-tournaments">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                <h2 class="cd-section-title" style="margin:0"><i class="fas fa-trophy"></i> My Tournaments</h2>
                <a href="tournament_register.php" class="cd-btn cd-btn-primary">
                    <i class="fas fa-plus"></i> Register for Tournament
                </a>
            </div>

            <!-- Stat cards -->
            <div class="cd-stats" style="margin-bottom:24px;">
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value" style="color:#f1a83c;"><?= $myTournamentCount ?></div>
                            <div class="cd-stat-label">Total Registered</div>
                        </div>
                        <div class="cd-stat-icon gold"><i class="fas fa-trophy"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value" style="color:#20c8a1;"><?= count($paidTournaments) ?></div>
                            <div class="cd-stat-label">Entry Fees Paid</div>
                        </div>
                        <div class="cd-stat-icon mint"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="cd-stat-card">
                    <div class="cd-stat-top">
                        <div>
                            <div class="cd-stat-value" style="color:#b37bec;"><?= count($upcomingTournaments) ?></div>
                            <div class="cd-stat-label">Active / Upcoming</div>
                        </div>
                        <div class="cd-stat-icon purple"><i class="fas fa-gamepad"></i></div>
                    </div>
                </div>
            </div>

            <?php if (empty($myTournaments)): ?>
            <!-- Empty state -->
            <div class="cd-card" style="border-color:rgba(241,168,60,0.3);">
                <div class="cd-empty" style="padding:50px 20px;">
                    <div style="width:70px;height:70px;border-radius:50%;background:rgba(241,168,60,0.12);border:2px solid rgba(241,168,60,0.3);
                                display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:28px;color:#f1a83c;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <p style="font-size:15px;font-weight:600;color:rgba(255,255,255,0.7);margin-bottom:8px;">No Tournament Registrations Yet</p>
                    <p style="font-size:13px;margin-bottom:20px;">Join a tournament to compete for prizes and glory!</p>
                    <a href="tournament_register.php" class="cd-btn cd-btn-primary" style="display:inline-flex;">
                        <i class="fas fa-gamepad"></i> View Open Tournaments
                    </a>
                </div>
            </div>

            <?php else: ?>

            <!-- Active / Upcoming Tournaments -->
            <?php if (!empty($upcomingTournaments)): ?>
            <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px;">
                <div style="width:8px;height:8px;border-radius:50%;background:#20c8a1;box-shadow:0 0 0 3px rgba(32,200,161,0.3);animation:cdPulse 1.5s infinite;"></div>
                <span style="font-size:13px;font-weight:700;color:#20c8a1;text-transform:uppercase;letter-spacing:.5px;">Active &amp; Upcoming</span>
            </div>
            <div style="display:grid;gap:16px;margin-bottom:28px;">
            <?php foreach ($upcomingTournaments as $tr):
                $tsm = $tournamentStatusMap[$tr['tournament_status']] ?? ['gray','circle',ucfirst($tr['tournament_status'])];
            ?>
            <div class="cd-card" style="border-color:rgba(32,200,161,0.3);background:linear-gradient(135deg,rgba(10,33,81,0.7),rgba(8,14,26,0.8));padding:0;overflow:hidden;">
                <!-- Gold accent top bar -->
                <div style="height:3px;background:linear-gradient(90deg,#f1a83c,#fb566b,#b37bec);"></div>
                <div style="padding:20px 22px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">
                                <span style="font-size:16px;font-weight:800;color:#fff;font-family:'Outfit',sans-serif;">
                                    <?= htmlspecialchars($tr['tournament_name']) ?>
                                </span>
                                <span class="cd-badge <?= $tsm[0] ?>">
                                    <i class="fas fa-<?= $tsm[1] ?>" style="margin-right:4px;"></i><?= $tsm[2] ?>
                                </span>
                            </div>
                            <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:14px;">
                                <?php if ($tr['game_name']): ?>
                                <span><i class="fas fa-gamepad" style="color:#5f85da;margin-right:4px;"></i><?= htmlspecialchars($tr['game_name']) ?></span>
                                <?php endif; ?>
                                <span>
                                    <?php
                                    $cType = $tr['console_type'];
                                    $cColor = match($cType) { 'PS5' => '#00439c', 'PS4' => '#003087', 'Xbox Series X' => '#107c10', default => '#5f85da' };
                                    ?>
                                    <span style="background:<?= $cColor ?>22;color:<?= $cColor ?>;border:1px solid <?= $cColor ?>55;border-radius:6px;padding:2px 8px;font-weight:700;font-size:11px;">
                                        <?= htmlspecialchars($cType) ?>
                                    </span>
                                </span>
                                <span><i class="fas fa-calendar" style="color:#f1a83c;margin-right:4px;"></i><?= date('M d, Y', strtotime($tr['start_date'])) ?></span>
                                <?php if ($tr['prize_pool'] > 0): ?>
                                <span><i class="fas fa-gift" style="color:#20c8a1;margin-right:4px;"></i>Prize: ₱ <?= number_format($tr['prize_pool'],0) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Player details row -->
                            <div style="display:flex;gap:20px;flex-wrap:wrap;">
                                <div style="background:rgba(32,200,161,0.08);border:1px solid rgba(32,200,161,0.2);border-radius:8px;padding:8px 14px;">
                                    <div style="font-size:10px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Your IGN</div>
                                    <div style="font-size:13px;font-weight:700;color:#20c8a1;"><?= htmlspecialchars($tr['ign'] ?? 'â€”') ?></div>
                                </div>
                                <div style="background:rgba(95,133,218,0.08);border:1px solid rgba(95,133,218,0.2);border-radius:8px;padding:8px 14px;">
                                    <div style="font-size:10px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Entry Fee</div>
                                    <div style="font-size:13px;font-weight:700;color:#5f85da;">₱ <?= number_format($tr['entry_fee'],0) ?></div>
                                </div>
                                <div style="background:rgba(32,200,161,0.06);border:1px solid rgba(32,200,161,0.15);border-radius:8px;padding:8px 14px;">
                                    <div style="font-size:10px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Payment</div>
                                    <div style="font-size:13px;font-weight:700;">
                                        <?php if ($tr['payment_status'] === 'paid'): ?>
                                        <span style="color:#20c8a1;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Paid</span>
                                        <?php else: ?>
                                        <span style="color:#f1a83c;"><i class="fas fa-clock" style="margin-right:4px;"></i>Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="background:rgba(179,123,236,0.08);border:1px solid rgba(179,123,236,0.2);border-radius:8px;padding:8px 14px;">
                                    <div style="font-size:10px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Registered</div>
                                    <div style="font-size:13px;font-weight:700;color:#b37bec;"><?= date('M d, Y', strtotime($tr['registration_date'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <!-- Trophy icon -->
                        <div style="width:52px;height:52px;border-radius:14px;background:rgba(241,168,60,0.12);border:1px solid rgba(241,168,60,0.3);
                                    display:flex;align-items:center;justify-content:center;font-size:22px;color:#f1a83c;flex-shrink:0;">
                            ðŸ†
                        </div>
                    </div>
                    <?php if ($tr['notes']): ?>
                    <div style="margin-top:12px;padding:8px 12px;background:rgba(255,255,255,0.03);border-radius:8px;font-size:12px;color:rgba(255,255,255,0.45);">
                        <i class="fas fa-sticky-note" style="margin-right:5px;"></i><?= htmlspecialchars($tr['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Past / Completed Tournaments -->
            <?php if (!empty($completedTournaments)): ?>
            <div style="margin-bottom:12px;">
                <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px;">
                    <i class="fas fa-history" style="margin-right:6px;"></i>Past Tournaments
                </span>
            </div>
            <div class="cd-card">
                <div style="overflow-x:auto;">
                <table class="cd-table">
                    <thead><tr>
                        <th>Tournament</th><th>Game</th><th>Date</th><th>IGN</th><th>Entry Fee</th><th>Status</th><th>Placement</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($completedTournaments as $tr):
                        $tsm = $tournamentStatusMap[$tr['tournament_status']] ?? ['gray','circle',ucfirst($tr['tournament_status'])];
                    ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($tr['tournament_name']) ?></td>
                        <td style="color:var(--muted);"><?= htmlspecialchars($tr['game_name'] ?: 'â€”') ?></td>
                        <td><?= date('M d, Y', strtotime($tr['start_date'])) ?></td>
                        <td><span style="color:var(--mint);font-weight:600;"><?= htmlspecialchars($tr['ign'] ?? 'â€”') ?></span></td>
                        <td style="color:var(--mint);font-weight:700;">₱ <?= number_format($tr['entry_fee'],0) ?></td>
                        <td><span class="cd-badge <?= $tsm[0] ?>"><i class="fas fa-<?= $tsm[1] ?>" style="margin-right:4px;"></i><?= $tsm[2] ?></span></td>
                        <td>
                            <?php if ($tr['placement']): ?>
                            <span style="font-weight:700;color:#f1a83c;">#<?= $tr['placement'] ?></span>
                            <?php if ($tr['prize_amount'] > 0): ?>
                            <span style="font-size:11px;color:#20c8a1;margin-left:4px;">+₱ <?= number_format($tr['prize_amount'],0) ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span style="color:var(--muted);">â€”</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div><!-- /page-tournaments -->

        <!-- â•â• PAGE: MY PAYMENTS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="cd-page" id="page-payments">
            <h2 class="cd-section-title"><i class="fas fa-receipt"></i> My Payments</h2>

            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-list-ul"></i> Transaction History</div>
                    <span style="font-size:12px;color:var(--muted);">Last 50 transactions</span>
                </div>

                <?php if (empty($myPayments)): ?>
                <div class="cd-empty">
                    <i class="fas fa-receipt"></i>
                    <p>No payment records yet.</p>
                    <p style="margin-top:6px;">Payments will appear here after your first reservation or gaming session.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="cd-table">
                    <thead><tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Ref ID</th>
                        <th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($myPayments as $pay):
                        $isRefund   = (float)$pay['amount'] < 0;
                        $pmId       = $pay['paymongo_payment_id'] ?? null;
                        $pmSrc      = $pay['paymongo_source_id']  ?? null;
                        $refDisplay = $pmId ?: $pmSrc;
                        // Build description from payment_note or type
                        $desc = $pay['payment_note'] ?? '';
                        if (!$desc) {
                            $desc = match(true) {
                                $isRefund                         => 'Refund',
                                $pay['tx_type'] === 'reservation' => 'Reservation Downpayment',
                                $pay['tx_type'] === 'hourly'      => 'Hourly Session',
                                $pay['tx_type'] === 'open_time'   => 'Open Time Session',
                                $pay['tx_type'] === 'unlimited'   => 'Unlimited Session',
                                default                           => 'Payment',
                            };
                        }
                    ?>
                    <tr>
                        <td style="color:var(--muted);font-size:11px;">#<?= $pay['transaction_id'] ?></td>
                        <td style="white-space:nowrap;font-size:12px;">
                            <?= date('M d, Y', strtotime($pay['transaction_date'])) ?><br>
                            <span style="color:var(--muted);font-size:11px;"><?= date('h:i A', strtotime($pay['transaction_date'])) ?></span>
                        </td>
                        <td style="font-size:13px;max-width:220px;"><?= htmlspecialchars($desc) ?></td>
                        <td><span class="cd-badge blue"><?= ucfirst($pay['payment_method']) ?></span></td>
                        <td style="font-weight:700;white-space:nowrap;
                            <?= $isRefund ? 'color:var(--coral);' : 'color:var(--mint);' ?>">
                            <?= $isRefund ? 'âˆ’' : '+' ?>₱ <?= number_format(abs((float)$pay['amount']), 2) ?>
                        </td>
                        <td style="font-size:11px;font-family:monospace;">
                            <?php if ($refDisplay): ?>
                                <span style="color:var(--mint);" title="<?= htmlspecialchars($refDisplay) ?>">
                                    <?= htmlspecialchars(substr($refDisplay, 0, 20)) . (strlen($refDisplay) > 20 ? 'â€¦' : '') ?>
                                </span>
                            <?php else: ?>
                                <span style="color:var(--muted);">â€”</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cd-badge <?= $pay['payment_status'] === 'completed' ? 'mint' : 'coral' ?>">
                                <?= ucfirst($pay['payment_status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div><!-- /page-payments -->


        <!-- â•â• PAGE: MY ACCOUNT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="cd-page" id="page-account">
            <h2 class="cd-section-title"><i class="fas fa-user-cog"></i> My Account</h2>

            <div class="cd-account-grid">

            <!-- Profile Information Card -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-id-card"></i> Profile Information</div>
                </div>

                <!-- Current info display -->
                <div id="profileDisplay" style="margin-bottom:22px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;">Full Name</div>
                            <div id="dispName" style="font-weight:600;color:#fff;font-size:14px;"><?= htmlspecialchars($user['full_name']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;">Phone Number</div>
                            <div id="dispPhone" style="font-weight:600;color:#fff;font-size:14px;"><?= htmlspecialchars($user['phone'] ?? 'â€”') ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;">Email</div>
                            <div style="font-weight:600;color:var(--blue);font-size:14px;"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;">Role</div>
                            <div style="font-weight:600;color:var(--mint);font-size:14px;">Gamer</div>
                        </div>
                    </div>
                    <div style="margin-top:18px;">
                        <button class="cd-btn cd-btn-primary" id="startEditBtn" onclick="startProfileEdit()">
                            <i class="fas fa-pen"></i> Edit Profile
                        </button>
                    </div>
                </div>

                <!-- Edit form (hidden by default) -->
                <form id="profileEditForm" style="display:none;" onsubmit="submitProfileEdit(event)">
                    <div style="display:grid;gap:16px;">

                        <div class="pf-field-group">
                            <label class="pf-label" for="pf_name"><i class="fas fa-user" style="color:var(--mint);width:14px;"></i> Full Name</label>
                            <input class="pf-input" type="text" id="pf_name" name="full_name"
                                value="<?= htmlspecialchars($user['full_name']) ?>" required placeholder="Juan Dela Cruz">
                        </div>

                        <div class="pf-field-group">
                            <label class="pf-label" for="pf_phone"><i class="fas fa-phone" style="color:var(--mint);width:14px;"></i> Phone Number <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                            <input class="pf-input" type="tel" id="pf_phone" name="phone"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                placeholder="09171234567"
                                inputmode="numeric"
                                pattern="[0-9]{10,11}"
                                maxlength="11"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            <div style="font-size:11px;color:var(--muted);margin-top:4px;">Digits only &mdash; 10 or 11 numbers (e.g. 09171234567)</div>
                        </div>

                        <div class="pf-field-group" style="padding-top:4px;border-top:1px solid rgba(95,133,218,0.12);">
                            <label class="pf-label" for="pf_curpass">
                                <i class="fas fa-lock" style="color:var(--coral);width:14px;"></i>
                                Current Password <span style="color:var(--coral);font-size:11px;font-weight:400;">required to save</span>
                            </label>
                            <div style="position:relative;">
                                <input class="pf-input" type="password" id="pf_curpass" name="current_password"
                                    placeholder="Enter your current password" required
                                    autocomplete="current-password" style="padding-right:42px;">
                                <button type="button" onclick="pfTogglePass()" id="pfToggleBtn"
                                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                                    background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.45);font-size:14px;padding:4px;">
                                    <i class="fas fa-eye" id="pfEyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div id="pf_alert" style="display:none;border-radius:9px;padding:11px 14px;font-size:13px;font-weight:500;display:none;align-items:center;gap:10px;"></div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="submit" class="cd-btn cd-btn-primary" id="pfSaveBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="cd-btn" onclick="cancelProfileEdit()"
                                style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.6);">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div><!-- /profile card -->

            <!-- Change Password Card -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <div class="cd-card-title"><i class="fas fa-key"></i> Security & Password</div>
                </div>

                <form id="passwordChangeForm" onsubmit="submitPasswordChange(event)">
                    <div style="display:grid;gap:20px;">
                        <div class="pf-field-group">
                            <label class="pf-label" for="pw_current" style="color:rgba(255,255,255,0.7); font-size:12px; margin-bottom:8px;">
                                <i class="fas fa-lock" style="color:var(--muted);width:14px;"></i> CURRENT PASSWORD
                            </label>
                            <div style="position:relative;">
                                <input class="pf-input" type="password" id="pw_current" name="current_password" required placeholder="Verify your identity"
                                    style="padding-right:44px; background:rgba(10,33,81,0.4); border-color:rgba(95,133,218,0.25); font-size:15px;">
                                <button type="button" onclick="togglePwVisibility('pw_current', 'pw_curr_toggle')" id="pw_curr_toggle"
                                    style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; padding:4px;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                            <div class="pf-field-group">
                                <label class="pf-label" for="pw_new" style="color:rgba(255,255,255,0.7); font-size:12px; margin-bottom:8px;">
                                    <i class="fas fa-plus-circle" style="color:var(--mint);width:14px;"></i> NEW PASSWORD
                                </label>
                                <div style="position:relative;">
                                    <input class="pf-input" type="password" id="pw_new" name="new_password" required minlength="8" placeholder="At least 8 chars"
                                        style="padding-right:44px; background:rgba(10,33,81,0.4); border-color:rgba(95,133,218,0.25); font-size:15px;">
                                    <button type="button" onclick="togglePwVisibility('pw_new', 'pw_new_toggle')" id="pw_new_toggle"
                                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; padding:4px;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="pf-field-group">
                                <label class="pf-label" for="pw_confirm" style="color:rgba(255,255,255,0.7); font-size:12px; margin-bottom:8px;">
                                    <i class="fas fa-check-circle" style="color:var(--mint);width:14px;"></i> CONFIRM
                                </label>
                                <div style="position:relative;">
                                    <input class="pf-input" type="password" id="pw_confirm" name="confirm_password" required placeholder="Match new password"
                                        style="padding-right:44px; background:rgba(10,33,81,0.4); border-color:rgba(95,133,218,0.25); font-size:15px;">
                                    <button type="button" onclick="togglePwVisibility('pw_confirm', 'pw_conf_toggle')" id="pw_conf_toggle"
                                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; padding:4px;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="pw_alert" style="display:none;border-radius:9px;padding:12px 16px;font-size:13.5px;font-weight:500;align-items:center;gap:10px;"></div>

                        <div style="margin-top:4px;">
                            <button type="submit" class="cd-btn cd-btn-primary" id="pwSaveBtn" style="padding:14px 24px; font-size:15px; font-weight:800;">
                                <i class="fas fa-shield-halved"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div><!-- /password card -->

            </div><!-- /cd-account-grid -->
        </div><!-- /page-account -->

    </main>

<!-- â•â• MOBILE BOTTOM NAVIGATION BAR â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<nav class="cd-bottom-nav" id="cdBottomNav">
    <button class="cd-bnav-btn active" id="bnavOverview" onclick="cdShowPage('overview', this)">
        <i class="fas fa-home"></i>
        <span>Overview</span>
    </button>
    <button class="cd-bnav-btn" id="bnavSessions" onclick="cdShowPage('sessions', this)">
        <i class="fas fa-play-circle"></i>
        <span>Sessions</span>
    </button>
    <button class="cd-bnav-btn" id="bnavReservations" onclick="cdShowPage('reservations', this)">
        <i class="fas fa-calendar-check"></i>
        <span>Reserve</span>
        <?php if (count($upcomingRes) > 0): ?>
        <span class="cd-bnav-badge"><?= count($upcomingRes) ?></span>
        <?php endif; ?>
    </button>
    <button class="cd-bnav-btn" id="bnavStats" onclick="cdShowPage('stats', this)">
        <i class="fas fa-trophy"></i>
        <span>Rank</span>
    </button>
    <button class="cd-bnav-btn" id="bnavPayments" onclick="cdShowPage('payments', this)">
        <i class="fas fa-receipt"></i>
        <span>Payments</span>
        <?php if ($myPaymentsCount > 0): ?>
        <span class="cd-bnav-badge"><?= $myPaymentsCount ?></span>
        <?php endif; ?>
    </button>
    <button class="cd-bnav-btn" id="bnavTournaments" onclick="cdShowPage('tournaments', this)">
        <i class="fas fa-trophy"></i>
        <span>Events</span>
        <?php if ($myActiveTournamentCount > 0): ?>
        <span class="cd-bnav-badge" style="background:linear-gradient(135deg,#f1a83c,#fb566b);color:#fff;"><?= $myActiveTournamentCount ?></span>
        <?php endif; ?>
    </button>
    <button class="cd-bnav-btn" id="bnavAccount" onclick="cdShowPage('account', this)">
        <i class="fas fa-user-cog"></i>
        <span>Account</span>
    </button>
</nav>

<script>
/* â•â• Navigation â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const BNAV_MAP = {
    overview:      'bnavOverview',
    sessions:      'bnavSessions',
    reservations:  'bnavReservations',
    stats:         'bnavStats',
    payments:      'bnavPayments',
    cancellations: 'bnavCancellations',
    tournaments:   'bnavTournaments',
    account:       'bnavAccount',
};
const SIDEBAR_MAP = {
    overview: 'navOverview', sessions: 'navSessions',
    reservations: 'navReservations', stats: 'navStats',
    payments: 'navPayments', cancellations: 'navCancellations',
    tournaments: 'navTournaments', account: 'navAccount'
};

function cdShowPage(page, el) {
    // Deactivate all pages
    document.querySelectorAll('.cd-page').forEach(p => p.classList.remove('active'));
    // Deactivate all nav buttons (sidebar + bottom nav)
    document.querySelectorAll('.cd-nav-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.cd-bnav-btn').forEach(b => b.classList.remove('active'));

    // Show target page
    document.getElementById('page-' + page).classList.add('active');

    // Activate the button that was clicked
    if (el) el.classList.add('active');

    // Sync bottom nav button
    const bnavBtn = document.getElementById(BNAV_MAP[page]);
    if (bnavBtn && bnavBtn !== el) bnavBtn.classList.add('active');

    // Sync sidebar button
    const sideBtn = document.getElementById(SIDEBAR_MAP[page]);
    if (sideBtn && sideBtn !== el) sideBtn.classList.add('active');

    history.replaceState(null, '', '#' + page);

    // Scroll content back to top on page switch (mobile)
    document.querySelector('.cd-main')?.scrollTo({ top: 0, behavior: 'smooth' });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Restore page from hash on load
(function () {
    const hash = location.hash.replace('#', '');
    const valid = ['overview','sessions','reservations','stats','payments','cancellations','tournaments'];
    if (hash && valid.includes(hash)) {
        cdShowPage(hash, null);
    }
})();

/* â•â• Live session timer â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â• Charts â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
                callback: val => '₱ ' + val
            }}
        }
    }
});

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   CANCEL RESERVATION LOGIC (two-step: reason â†’ confirm)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
let _cancelResId = null;

const CANCEL_REASON_LABELS = {
    schedule_change:    'My schedule changed',
    found_alternative:  'Found a better alternative',
    budget_issue:       'Budget / financial reason',
    technical_issue:    'Technical or system issue',
    emergency:          'Personal emergency',
    other:              'Other reason',
};

function openCancelModal(btn) {
    _cancelResId = btn.dataset.id;
    document.getElementById('cancelResSubtitle').textContent = 'Reservation #' + _cancelResId;
    // Reset modal to step 1
    document.getElementById('cancelReasonType').value   = '';
    document.getElementById('cancelReasonDetail').value = '';
    document.getElementById('cancelDetailLabel').textContent = 'Additional Details (Optional)';
    document.getElementById('cancelStep1').style.display = 'block';
    document.getElementById('cancelStep2').style.display = 'none';
    const modal = document.getElementById('cancelResModal');
    modal.style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelResModal').style.display = 'none';
    _cancelResId = null;
}

// Toggle "Other" detail label/required
document.getElementById('cancelReasonType')?.addEventListener('change', function() {
    const isOther = this.value === 'other';
    const lbl = document.getElementById('cancelDetailLabel');
    lbl.textContent = isOther ? 'Please describe your reason *' : 'Additional Details (Optional)';
});

function cancelStep1Next() {
    const reasonType   = document.getElementById('cancelReasonType').value;
    const reasonDetail = document.getElementById('cancelReasonDetail').value.trim();

    if (!reasonType) {
        showDashToast('Please select a reason for cancellation.', 'error');
        return;
    }
    if (reasonType === 'other' && !reasonDetail) {
        showDashToast('Please describe your reason for cancellation.', 'error');
        document.getElementById('cancelReasonDetail').focus();
        return;
    }

    // Populate step 2 display
    document.getElementById('cancelReasonDisplay').textContent = CANCEL_REASON_LABELS[reasonType] || reasonType;
    document.getElementById('cancelDetailDisplay').textContent = reasonDetail || '';
    document.getElementById('cancelDetailDisplay').style.display = reasonDetail ? 'block' : 'none';

    document.getElementById('cancelStep1').style.display = 'none';
    document.getElementById('cancelStep2').style.display = 'block';
}

function cancelStep2Back() {
    document.getElementById('cancelStep1').style.display = 'block';
    document.getElementById('cancelStep2').style.display = 'none';
}

function submitCancelReservation() {
    if (!_cancelResId) return;

    // Capture the ID NOW â€” closeCancelModal() will null _cancelResId
    const resId        = _cancelResId;
    const reasonType   = document.getElementById('cancelReasonType').value;
    const reasonDetail = document.getElementById('cancelReasonDetail').value.trim();

    const btn = document.getElementById('cancelResConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancellingâ€¦';

    const fd = new FormData();
    fd.append('reservation_id',      resId);
    fd.append('cancel_reason_type',  reasonType);
    fd.append('cancel_reason_detail', reasonDetail);

    fetch('ajax/cancel_reservation.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            closeCancelModal();
            if (data.success) {
                // Fade-out the cancelled row in the Reservations table
                const row = document.querySelector(`[data-cancel-row="${resId}"]`)
                         || document.querySelector(`button[data-id="${resId}"]`)?.closest('tr');
                if (row) {
                    row.style.transition = 'opacity .4s, transform .4s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => { row.remove(); }, 420);
                }
                showDashToast(data.message, 'success');
                // Store message in sessionStorage so it survives the reload
                sessionStorage.setItem('dashToastMsg',  data.message);
                sessionStorage.setItem('dashToastType', 'success');
                // Reload to #cancellations â€” message will re-appear for 10s after load
                setTimeout(() => {
                    location.href = location.pathname + '#cancellations';
                    location.reload();
                }, 800);
            } else {
                showDashToast(data.message || 'Could not cancel reservation.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i> Yes, Cancel It';
            }
        })
        .catch(() => {
            closeCancelModal();
            showDashToast('Network error â€” please try again.', 'error');
        });
}

function showDashToast(msg, type, duration) {
    const t = document.getElementById('dashToast');
    t.style.background = type === 'success'
        ? 'linear-gradient(135deg,#0d3d2e,#0a2218)'
        : 'linear-gradient(135deg,#3d0d0d,#220a0a)';
    t.style.border = '1px solid ' + (type === 'success' ? 'rgba(32,200,161,.4)' : 'rgba(251,86,107,.4)');
    t.style.color  = type === 'success' ? '#20c8a1' : '#fb566b';
    t.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle" style="margin-right:8px;"></i>' : '<i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>') + msg;
    t.style.display = 'block';
    t.style.opacity = '1';
    clearTimeout(t._timer);
    const ms = duration || 3500;
    t._timer = setTimeout(() => { t.style.opacity = '0'; setTimeout(() => { t.style.display = 'none'; }, 320); }, ms);
}

//  Re-display toast saved before page reload 
document.addEventListener('DOMContentLoaded', function () {
    const msg  = sessionStorage.getItem('dashToastMsg');
    const type = sessionStorage.getItem('dashToastType');
    if (msg && type) {
        sessionStorage.removeItem('dashToastMsg');
        sessionStorage.removeItem('dashToastType');
        // Show for 10 seconds on the reloaded page
        showDashToast(msg, type, 10000);
    }
});

// Close cancel modal on backdrop click
document.getElementById('cancelResModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

/* ═══════════════════════════════════════════════════════════
   USER-INITIATED RESCHEDULE LOGIC
═══════════════════════════════════════════════════════════ */
let _urmResId = null;
let _urmConsole = null;

function openUserRescheduleModal(rid, curDate, curTime, consoleType) {
    _urmResId = rid;
    _urmConsole = consoleType;
    
    document.getElementById('urmSubtitle').textContent = 'Reservation #' + rid;
    
    // Format current schedule display
    const d = new Date(curDate + 'T00:00:00');
    const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const timeDisplay = formatTime(curTime);
    
    document.getElementById('urmCurrentSched').textContent = dateStr + ' at ' + timeDisplay;
    
    // Reset inputs
    const dateInput = document.getElementById('urmDate');
    dateInput.value = curDate;
    document.getElementById('urmTime').value = curTime.substring(0, 5);
    
    document.getElementById('userRescheduleModal').style.display = 'flex';
}

function formatTime(t) {
    let [h, m] = t.split(':');
    h = parseInt(h);
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
}

function closeUserRescheduleModal() {
    document.getElementById('userRescheduleModal').style.display = 'none';
    _urmResId = null;
    _urmConsole = null;
}

function submitUserReschedule() {
    if (!_urmResId) return;
    
    const newDate = document.getElementById('urmDate').value;
    const newTime = document.getElementById('urmTime').value;
    
    if (!newDate || !newTime) {
        showDashToast('Please select both date and time.', 'error');
        return;
    }
    
    const btn = document.getElementById('urmSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Requesting...';
    
    const fd = new FormData();
    fd.append('reservation_id', _urmResId);
    fd.append('new_date', newDate);
    fd.append('new_time', newTime);
    fd.append('console_type', _urmConsole);
    
    fetch('ajax/user_reschedule_reservation.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeUserRescheduleModal();
                showDashToast(data.message, 'success');
                sessionStorage.setItem('dashToastMsg', data.message);
                sessionStorage.setItem('dashToastType', 'success');
                setTimeout(() => { location.reload(); }, 1500);
            } else {
                showDashToast(data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-calendar-check"></i> Request Reschedule';
            }
        })
        .catch(() => {
            showDashToast('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-calendar-check"></i> Request Reschedule';
        });
}

// Close user reschedule modal on backdrop click
document.getElementById('userRescheduleModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeUserRescheduleModal();
});
</script>
<!-- Bootstrap JS (navbar mobile toggler) -->
<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>

<script>
//  Navbar scroll effect (same as index.php) 
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
</script>

<?php if ($activeSession && $activeSession['rental_mode'] === 'hourly'): ?>
<!-- â•â• REQUEST EXTENSION MODAL â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div id="reqExtModal" style="
    display:none;position:fixed;inset:0;z-index:99999;
    background:rgba(0,0,0,0.75);backdrop-filter:blur(7px);
    align-items:center;justify-content:center;">
    <div style="
        background:linear-gradient(145deg,#0d1b3e,#08101c);
        border:1px solid rgba(95,133,218,0.3);
        border-radius:18px;padding:28px 26px;
        max-width:420px;width:90%;
        box-shadow:0 24px 64px rgba(0,0,0,0.6);
        animation:reqExtIn .22s cubic-bezier(.34,1.56,.64,1);">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(95,133,218,.15);
                            border:1px solid rgba(95,133,218,.3);display:flex;align-items:center;
                            justify-content:center;font-size:18px;color:#8aa4e8;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:15px;color:#fff;">Request More Time</div>
                    <div style="font-size:12px;color:var(--muted);">Staff will approve &amp; collect payment</div>
                </div>
            </div>
            <button onclick="closeReqExtModal()" style="background:none;border:none;color:#888;font-size:20px;cursor:pointer;padding:4px;">Ã—</button>
        </div>

        <!-- Current session info -->
        <div style="background:rgba(32,200,161,.06);border:1px solid rgba(32,200,161,.2);
                    border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;">
            <strong style="color:#fff;">
                <?= htmlspecialchars($activeSession['console_type']) ?> â€“ <?= htmlspecialchars($activeSession['unit_number']) ?>
            </strong>
            <span style="color:var(--muted);margin-left:8px;">
                Booked: <?= fmtMins((int)($activeSession['planned_minutes'] ?? 0)) ?>
            </span>
        </div>

        <!-- Duration picker -->
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                How much extra time?
            </label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="reqExtBtns">
                <?php
                // DB-driven extension options â€” reads rate and bonus rule from system_settings
                $extRules = getPricingRules();
                $extRate  = $extRules['hourly_rate'];   // ₱ /hr for extensions
                // Extension options: 30 min steps up to 2 hrs (no minimum charge for extensions)
                $extSteps = [30, 60, 90, 120];
                foreach ($extSteps as $extMins):
                    $extCost  = round($extMins / 60 * $extRate, 2);
                    $extBonus = calcBonusMinutes($extMins, $extRules);
                    $extLabel = $extMins < 60
                        ? "+{$extMins} min"
                        : (($extMins % 60 === 0)
                            ? '+' . ($extMins / 60) . ' hour' . ($extMins / 60 > 1 ? 's' : '')
                            : '+' . intdiv($extMins, 60) . 'h ' . ($extMins % 60) . 'm');
                ?>
                <button type="button"
                        class="req-ext-opt"
                        data-mins="<?= $extMins ?>"
                        data-cost="<?= $extCost ?>"
                        onclick="selectExtOpt(this)"
                        style="padding:12px;border-radius:10px;
                               background:rgba(95,133,218,.08);
                               border:1px solid rgba(95,133,218,.2);
                               color:#8aa4e8;font-family:inherit;cursor:pointer;
                               transition:.18s;text-align:left;">
                    <div style="font-weight:700;font-size:14px;"><?= $extLabel ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;">
                        &#8369;<?= number_format($extCost, 0) ?> estimated
                        <?php if ($extBonus > 0): ?>
                        <span style="color:#20c8a1;font-weight:700;"> +<?= $extBonus ?>m free!</span>
                        <?php endif; ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cost preview -->
        <div id="reqExtCostBox" style="display:none;
             background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.25);
             border-radius:10px;padding:14px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:#8aa4e8;">
                    <i class="fas fa-receipt" style="margin-right:5px;"></i> Estimated Cost
                </span>
                <span id="reqExtCostDisplay" style="font-size:20px;font-weight:800;color:#f1e1aa;">₱ 0</span>
            </div>
            <div style="margin-top:6px;font-size:11px;color:var(--muted);">
                <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                Final amount collected by staff on approval.
            </div>
        </div>

        <input type="hidden" id="reqExtMins" value="">

        <button id="reqExtSubmitBtn" onclick="submitReqExt()"
                disabled
                style="width:100%;padding:13px;border-radius:10px;
                       background:linear-gradient(135deg,rgba(95,133,218,.18),rgba(32,200,161,.1));
                       border:1px solid rgba(95,133,218,.3);color:#8aa4e8;
                       font-size:14px;font-weight:700;cursor:pointer;opacity:.5;
                       display:flex;align-items:center;justify-content:center;gap:8px;
                       transition:.2s;font-family:inherit;">
            <i class="fas fa-paper-plane"></i> Send Request to Staff
        </button>
    </div>
</div>
<style>
@keyframes reqExtIn {
    from { opacity:0; transform:scale(.88) translateY(14px); }
    to   { opacity:1; transform:scale(1)  translateY(0); }
}
.req-ext-opt.selected {
    background: rgba(95,133,218,.25) !important;
    border-color: rgba(95,133,218,.6) !important;
    color: #fff !important;
}
</style>
<script>
const REQ_EXT_SESSION_ID = <?= (int)$activeSession['session_id'] ?>;

function openReqExtModal() {
    // Reset state
    document.querySelectorAll('.req-ext-opt').forEach(b => b.classList.remove('selected'));
    document.getElementById('reqExtMins').value = '';
    document.getElementById('reqExtCostBox').style.display = 'none';
    const btn = document.getElementById('reqExtSubmitBtn');
    btn.disabled = true;
    btn.style.opacity = '.5';

    const m = document.getElementById('reqExtModal');
    m.style.display = 'flex';
    m.firstElementChild.style.animation = 'none';
    requestAnimationFrame(() => { m.firstElementChild.style.animation = ''; });
}

function closeReqExtModal() {
    document.getElementById('reqExtModal').style.display = 'none';
}

function selectExtOpt(el) {
    document.querySelectorAll('.req-ext-opt').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    const mins = el.dataset.mins;
    const cost = el.dataset.cost;
    document.getElementById('reqExtMins').value = mins;
    document.getElementById('reqExtCostDisplay').textContent = '₱ ' + cost;
    document.getElementById('reqExtCostBox').style.display = 'block';
    const btn = document.getElementById('reqExtSubmitBtn');
    btn.disabled = false;
    btn.style.opacity = '1';
}

function submitReqExt() {
    const mins = document.getElementById('reqExtMins').value;
    if (!mins) return;

    const btn = document.getElementById('reqExtSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sendingâ€¦';

    const fd = new FormData();
    fd.append('session_id',   REQ_EXT_SESSION_ID);
    fd.append('extra_minutes', mins);

    fetch('ajax/request_extension.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Request to Staff';
            if (data.success) {
                closeReqExtModal();
                showDashToast('Extension request sent! Staff will approve it shortly.', 'success');
                // Swap button to pending badge
                document.getElementById('reqExtBtn')?.replaceWith((() => {
                    const d = document.createElement('div');
                    d.style.cssText = 'display:flex;align-items:center;gap:8px;background:rgba(241,168,60,.1);border:1px solid rgba(241,168,60,.3);border-radius:9px;padding:8px 14px;font-size:13px;color:#f1a83c;';
                    d.innerHTML = '<i class="fas fa-hourglass-half"></i> Extension request for <strong>+' + mins + ' min</strong> is <strong>pending staff approval</strong>';
                    return d;
                })());
            } else {
                showDashToast(data.message || 'Could not send request.', 'error');
                btn.style.opacity = '1';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Request to Staff';
            showDashToast('Network error â€” please try again.', 'error');
        });
}

// Close on backdrop click
document.getElementById('reqExtModal').addEventListener('click', function(e) {
    if (e.target === this) closeReqExtModal();
});
</script>
<?php endif; ?>

<!--  Reschedule Modal  -->
<div id="userRescheduleModal" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(0,0,0,.7);backdrop-filter:blur(6px);
     align-items:center;justify-content:center;">
    <div style="background:#0d1425;border:1px solid rgba(255,255,255,.1);border-radius:16px;
                padding:24px;max-width:440px;width:94%;position:relative;box-shadow:0 10px 40px rgba(0,0,0,.5);">
        
        <button onclick="closeUserRescheduleModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;color:#888;font-size:1.2rem;cursor:pointer;transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#888'"><i class="fas fa-times"></i></button>
        
        <h3 style="margin-top:0;font-size:1.2rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <i class="fas fa-calendar-alt" style="color:#20c8a1;"></i> Reschedule Reservation
        </h3>
        
        <div style="font-size:13px;color:#ccc;line-height:1.5;margin-bottom:20px;">
            <div style="background:rgba(32,200,161,.1);border:1px solid rgba(32,200,161,.3);color:#20c8a1;padding:12px;border-radius:8px;font-size:12px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;">
                <i class="fas fa-check-circle" style="margin-top:2px;"></i>
                <div>
                    <strong>One-Time Reschedule:</strong> You may change the date and time of this reservation without losing your fee. This can only be done <strong>once</strong> per reservation.
                </div>
            </div>
            
            <form id="userRescheduleForm" onsubmit="submitUserReschedule(event)">
                <input type="hidden" id="reschedResId">
                
                <div style="margin-bottom:12px;">
                    <label style="display:block;font-size:11px;color:#888;margin-bottom:4px;text-transform:uppercase;font-weight:700;">Console Type</label>
                    <select id="reschedConsole" style="width:100%;background:rgba(10,33,81,.6);border:1px solid rgba(95,133,218,.25);color:#f0f0f0;padding:11px 12px;border-radius:10px;font-size:13px;font-family:inherit;outline:none;" required>
                        <?php foreach (getConsoleTypes(true) as $ct): ?>
                            <option value="<?= htmlspecialchars($ct['type_name']) ?>"><?= htmlspecialchars($ct['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;gap:12px;margin-bottom:20px;">
                    <div style="flex:1;">
                        <label style="display:block;font-size:11px;color:#888;margin-bottom:4px;text-transform:uppercase;font-weight:700;">New Date</label>
                        <input type="date" id="reschedDate" style="width:100%;background:rgba(10,33,81,.6);border:1px solid rgba(95,133,218,.25);color:#f0f0f0;padding:11px 12px;border-radius:10px;font-size:13px;font-family:inherit;outline:none;" required 
                               min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 month')) ?>"
                               onchange="buildReschedTimeSelect()">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block;font-size:11px;color:#888;margin-bottom:4px;text-transform:uppercase;font-weight:700;">New Time</label>
                        <select id="reschedTime" style="width:100%;background:rgba(10,33,81,.6);border:1px solid rgba(95,133,218,.25);color:#f0f0f0;padding:11px 12px;border-radius:10px;font-size:13px;font-family:inherit;outline:none;" required>
                            <!-- options built via js -->
                        </select>
                    </div>
                </div>

                <div id="reschedError" style="display:none; color:#fb566b; font-size:12px; margin-bottom:14px; background:rgba(251,86,107,.1); padding:10px; border-radius:8px; border:1px solid rgba(251,86,107,.2);"></div>

                <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" onclick="closeUserRescheduleModal()" style="padding:10px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.05);color:#ddd;transition:all .2s;" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='rgba(255,255,255,.05)'">Cancel</button>
                    <button type="submit" id="reschedSubmitBtn" style="padding:10px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;border:none;background:#20c8a1;color:#000;transition:all .2s;" onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">Confirm Reschedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Valid slots 12:00â€“23:00 in 30-min steps
const DASH_TIME_SLOTS = (function () {
    const s = [];
    for (let h = 12; h <= 23; h++) {
        s.push(String(h).padStart(2,'0') + ':00');
        if (h < 23) s.push(String(h).padStart(2,'00') + ':30');
    }
    return s;
})();

function fmtDashSlot(t) {
    const [h, m] = t.split(':');
    const hh = parseInt(h);
    return `${hh % 12 || 12}:${m} ${hh >= 12 ? 'PM' : 'AM'}`;
}

function getDashMinTimeForDate(dateStr) {
    const today = new Date();
    const todayStr = today.toISOString().slice(0, 10);
    const OPEN_TIME = '12:00';
    
    if (dateStr === todayStr) {
        const earliest = new Date(today.getTime() + 3600 * 1000); // +1 hour
        const lead = String(earliest.getHours()).padStart(2,'0') + ':' + String(earliest.getMinutes()).padStart(2,'0');
        return lead > OPEN_TIME ? lead : OPEN_TIME;
    }
    return OPEN_TIME;
}

function buildReschedTimeSelect() {
    const sel = document.getElementById('reschedTime');
    const dateVal = document.getElementById('reschedDate').value || new Date().toISOString().slice(0,10);
    const minT = getDashMinTimeForDate(dateVal);
    
    sel.innerHTML = '<option value="" disabled selected>Select a time...</option>';
    DASH_TIME_SLOTS.forEach(slot => {
        const opt = document.createElement('option');
        opt.value = slot;
        opt.textContent = fmtDashSlot(slot);
        if (slot < minT) {
            opt.disabled = true;
            opt.style.color = 'rgba(255,255,255,.22)';
            opt.style.background = '#0a1628';
        }
        sel.appendChild(opt);
    });
}

function openUserRescheduleModal(id, date, time, consoleType) {
    document.getElementById('reschedResId').value = id;
    document.getElementById('reschedConsole').value = consoleType;
    document.getElementById('reschedDate').value = date;
    
    buildReschedTimeSelect();
    
    const sel = document.getElementById('reschedTime');
    const tVal = time.length === 5 ? time : time.substr(0,5);
    for(let i=0; i<sel.options.length; i++){
        if(sel.options[i].value === tVal && !sel.options[i].disabled) {
            sel.options[i].selected = true;
            break;
        }
    }

    document.getElementById('reschedError').style.display = 'none';
    document.getElementById('userRescheduleModal').style.display = 'flex';
}

function closeUserRescheduleModal() {
    document.getElementById('userRescheduleModal').style.display = 'none';
}

function submitUserReschedule(e) {
    e.preventDefault();
    const btn = document.getElementById('reschedSubmitBtn');
    const err = document.getElementById('reschedError');
    
    const rid  = document.getElementById('reschedResId').value;
    const date = document.getElementById('reschedDate').value;
    const time = document.getElementById('reschedTime').value;
    const consoleType = document.getElementById('reschedConsole').value;

    if (!rid || !date || !time) {
        err.textContent = 'Please fill out all required fields.';
        err.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    err.style.display = 'none';

    const fd = new FormData();
    fd.append('reservation_id', rid);
    fd.append('new_date', date);
    fd.append('new_time', time);
    fd.append('console_type', consoleType);

    fetch('ajax/user_reschedule_reservation.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            err.textContent = data.message;
            err.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = 'Confirm Reschedule';
        }
    })
    .catch(ex => {
        err.textContent = 'Network error. Please try again.';
        err.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = 'Confirm Reschedule';
    });
}
</script>

<script>
/* â•â• My Account â€“ Profile Edit â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

function startProfileEdit() {
    document.getElementById('profileDisplay').style.display = 'none';
    const form = document.getElementById('profileEditForm');
    form.style.display = 'block';
    pfShowAlert(false);
    document.getElementById('pf_curpass').value = '';
    document.getElementById('pf_name').focus();
}

function cancelProfileEdit() {
    document.getElementById('profileDisplay').style.display = 'block';
    document.getElementById('profileEditForm').style.display = 'none';
    pfShowAlert(false);
    document.getElementById('pf_curpass').value = '';
}

function pfTogglePass() {
    const inp  = document.getElementById('pf_curpass');
    const icon = document.getElementById('pfEyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function pfShowAlert(show, success, msg) {
    const el = document.getElementById('pf_alert');
    if (!show) { el.style.display = 'none'; return; }
    el.style.display = 'flex';
    el.style.background = success
        ? 'rgba(32,200,161,0.12)' : 'rgba(251,86,107,0.12)';
    el.style.border = success
        ? '1px solid rgba(32,200,161,0.35)' : '1px solid rgba(251,86,107,0.35)';
    el.style.color  = success ? '#20c8a1' : '#fb566b';
    el.innerHTML = (success
        ? '<i class="fas fa-check-circle"></i>'
        : '<i class="fas fa-exclamation-circle"></i>') + ' <span>' + msg + '</span>';
}

function submitProfileEdit(e) {
    e.preventDefault();
    pfShowAlert(false);

    const phone = document.getElementById('pf_phone').value;
    if (phone !== '' && !/^\d{10,11}$/.test(phone)) {
        pfShowAlert(true, false, 'Phone must be 10â€“11 digits (numbers only).');
        return;
    }

    const btn = document.getElementById('pfSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Savingâ€¦';

    const form = document.getElementById('profileEditForm');
    const data = new FormData(form);

    fetch('ajax/update_profile.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            if (res.success) {
                // Update display values
                document.getElementById('dispName').textContent  = res.full_name;
                document.getElementById('dispPhone').textContent = res.phone || 'â€”';
                
                // Also update the sidebar header
                const sidebarName = document.querySelector('.cd-avatar-name');
                if (sidebarName) sidebarName.textContent = res.full_name;

                pfShowAlert(true, true, res.message);
                // Auto-close form after short delay
                setTimeout(() => cancelProfileEdit(), 1600);
            } else {
                pfShowAlert(true, false, res.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            pfShowAlert(true, false, 'Network error. Please try again.');
        });
}

function submitPasswordChange(e) {
    e.preventDefault();
    const btn = document.getElementById('pwSaveBtn');
    const alertEl = document.getElementById('pw_alert');
    
    function showPwAlert(show, success, msg) {
        if (!show) { alertEl.style.display = 'none'; return; }
        alertEl.style.display = 'flex';
        alertEl.style.background = success ? 'rgba(32,200,161,0.12)' : 'rgba(251,86,107,0.12)';
        alertEl.style.border = success ? '1px solid rgba(32,200,161,0.35)' : '1px solid rgba(251,86,107,0.35)';
        alertEl.style.color  = success ? '#20c8a1' : '#fb566b';
        alertEl.innerHTML = (success ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>') + ' <span>' + msg + '</span>';
    }

    const newPw = document.getElementById('pw_new').value;
    const confPw = document.getElementById('pw_confirm').value;

    if (newPw.length < 8) {
        showPwAlert(true, false, 'New password must be at least 8 characters.');
        return;
    }
    if (newPw !== confPw) {
        showPwAlert(true, false, 'New passwords do not match.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updatingâ€¦';
    showPwAlert(false);

    const fd = new FormData(document.getElementById('passwordChangeForm'));
    fetch('ajax/change_password.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shield-halved"></i> Update Password';
        if (res.success) {
            showPwAlert(true, true, res.message);
            document.getElementById('passwordChangeForm').reset();
        } else {
            showPwAlert(true, false, res.message);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shield-halved"></i> Update Password';
        showPwAlert(true, false, 'Network error. Please try again.');
    });
}
function togglePwVisibility(inputId, btnId) {
    const inp = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        btn.style.color = 'var(--mint)';
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        btn.style.color = 'rgba(255,255,255,0.4)';
    }
}
</script>

<!--  Reschedule Confirm Modal  -->
<div id="rescheduleConfirmModal" style="display:none;position:fixed;inset:0;z-index:99999;
     background:rgba(0,0,0,.82);backdrop-filter:blur(10px);
     align-items:center;justify-content:center;padding:20px;">
    <div style="background:linear-gradient(145deg,#0e1d36,#0a1628);
                border:1px solid rgba(32,200,161,.35);border-radius:22px;
                padding:32px 28px 26px;max-width:440px;width:100%;
                box-shadow:0 28px 72px rgba(0,0,0,.75);position:relative;
                animation:rcmFadeIn .25s ease;">
        <style>@keyframes rcmFadeIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}</style>

        <button onclick="closeRescheduleConfirmModal()"
            style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,.07);
                   border:1px solid rgba(255,255,255,.1);color:#aaa;border-radius:8px;
                   width:32px;height:32px;cursor:pointer;font-size:14px;
                   display:flex;align-items:center;justify-content:center;transition:all .2s;"
            onmouseover="this.style.background='rgba(251,86,107,.15)';this.style.color='#fb566b'"
            onmouseout="this.style.background='rgba(255,255,255,.07)';this.style.color='#aaa'">
            <i class="fas fa-times"></i>
        </button>

        <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(32,200,161,.12);
                        border:1px solid rgba(32,200,161,.25);flex-shrink:0;
                        display:flex;align-items:center;justify-content:center;
                        box-shadow:0 0 20px rgba(32,200,161,.15);">
                <i class="fas fa-calendar-check" style="color:#20c8a1;font-size:19px;"></i>
            </div>
            <div>
                <div style="font-weight:800;color:#fff;font-size:16px;margin-bottom:3px;">Confirm Reschedule</div>
                <div style="font-size:12px;color:#888;">Earliest date: <span id="rcmProposedDate" style="color:#f1a83c;font-weight:600;"></span></div>
            </div>
        </div>

        <input type="hidden" id="rcmRescheduleId">

        <div style="background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.18);
                    border-radius:10px;padding:10px 14px;font-size:12px;color:#f1a83c;
                    margin-bottom:18px;display:flex;gap:8px;align-items:flex-start;">
            <i class="fas fa-info-circle" style="margin-top:2px;flex-shrink:0;"></i>
            <span>Choose any date <strong>on or after</strong> the proposed date. Your reservation is only updated after you confirm.</span>
        </div>

        <div style="margin-bottom:14px;">
            <label style="font-size:11px;font-weight:700;color:#888;display:block;margin-bottom:6px;
                          text-transform:uppercase;letter-spacing:.6px;">Your Preferred Date *</label>
            <input type="date" id="rcmDate"
                style="width:100%;background:rgba(10,33,81,.7);border:1px solid rgba(95,133,218,.3);
                       color:#f0f0f0;padding:11px 14px;border-radius:10px;font-size:14px;
                       font-family:inherit;outline:none;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:24px;">
            <label style="font-size:11px;font-weight:700;color:#888;display:block;margin-bottom:6px;
                          text-transform:uppercase;letter-spacing:.6px;">Preferred Time *</label>
            <select id="rcmTime"
                style="width:100%;background:rgba(10,33,81,.7);border:1px solid rgba(95,133,218,.3);
                       color:#f0f0f0;padding:11px 14px;border-radius:10px;font-size:14px;
                       font-family:inherit;outline:none;">
                <?php for($h=12;$h<=23;$h++) foreach(['00','30'] as $m){
                    $v=sprintf('%02d:%s',$h,$m);
                    echo '<option value="'.$v.'">'.date('g:i A',strtotime('2000-01-01 '.$v)).'</option>'.PHP_EOL;
                } ?>
            </select>
        </div>

        <div style="display:flex;gap:10px;">
            <button id="rcmSubmitBtn" onclick="submitRescheduleConfirm()"
                style="flex:1;background:linear-gradient(135deg,#20c8a1,#5f85da);color:#fff;
                       border:none;border-radius:10px;padding:13px 18px;cursor:pointer;
                       font-size:14px;font-weight:700;font-family:inherit;
                       display:flex;align-items:center;justify-content:center;gap:8px;
                       box-shadow:0 4px 18px rgba(32,200,161,.3);transition:opacity .2s;">
                <i class="fas fa-calendar-check"></i> Confirm Date
            </button>
            <button onclick="closeRescheduleConfirmModal()"
                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
                       color:#aaa;border-radius:10px;padding:13px 22px;cursor:pointer;
                       font-size:14px;font-weight:600;font-family:inherit;transition:all .2s;"
                onmouseover="this.style.background='rgba(255,255,255,.12)'"
                onmouseout="this.style.background='rgba(255,255,255,.07)'">
                Cancel
            </button>
        </div>
    </div>
</div>
<script>
document.getElementById('rescheduleConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeRescheduleConfirmModal();
});
</script>

<!--  Auto-Start Sessions Poller  -->
<script>
(function() {
    var POLL_MS   = 60000; // every 60 seconds
    var CURRENT_UID = <?= (int)$user_id ?>;

    function _autoStartSessions() {
        fetch('ajax/auto_start_sessions.php', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success || !data.started) return;

                // Check if any started session belongs to the current user
                var mySession = (data.sessions || []).find(function(s) {
                    return parseInt(s.user_id) === CURRENT_UID;
                });

                if (mySession) {
                    // Store a toast message for after reload
                    sessionStorage.setItem('dashToastMsg', 'Your reservation has been automatically started! Your gaming session is now active.');
                    sessionStorage.setItem('dashToastType', 'success');
                    setTimeout(function() { location.reload(); }, 500);
                }
            })
            .catch(function() {}); // fail silently — background task
    }

    // Run once immediately, then poll every 60s
    _autoStartSessions();
    setInterval(_autoStartSessions, POLL_MS);
})();
</script>

</html>
