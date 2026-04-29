<?php
/**
 * ajax/live_section.php — Live Section Renderer
 * ─────────────────────────────────────────────
 * Returns the inner HTML of a specific admin section so the
 * JS live-refresh poller can update it without a full page reload.
 *
 * GET params:
 *   section   string  One of: dashboard, sessions, reservations,
 *                             consoles, transactions, reports, tournaments, settings
 *
 * Returns: JSON { html: "<rendered section HTML>" }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

requireRole(['owner', 'shopkeeper']);

$allowed = ['dashboard', 'sessions', 'reservations', 'consoles',
            'transactions', 'reports', 'tournaments', 'settings'];
$section = $_GET['section'] ?? '';

if (!in_array($section, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid section']);
    exit;
}

// ── Load all data that admin.php populates ──────────────────────────────
$user = getCurrentUser();

// Settings
$settings = [];
$sRes = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($sRes) {
    while ($row = $sRes->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Consoles
$consolesResult = $conn->query("SELECT * FROM consoles ORDER BY console_type, unit_number");
$consoles = $consolesResult ? $consolesResult->fetch_all(MYSQLI_ASSOC) : [];

// Active sessions (with extras)
$recentSessions = [];
$rsQ = $conn->query(
    "SELECT s.*, u.full_name AS customer_name, u.phone AS customer_phone,
            c.console_type, c.unit_number,
            COALESCE((SELECT SUM(ar.extra_cost)
                        FROM additional_requests ar
                       WHERE ar.session_id = s.session_id
                         AND ar.status = 'approved'), 0) AS approved_extras
       FROM sessions s
       JOIN users u ON s.user_id = u.user_id
       JOIN consoles c ON s.console_id = c.console_id
      WHERE s.status IN ('active','paused')
      ORDER BY s.start_time DESC"
);
if ($rsQ) $recentSessions = $rsQ->fetch_all(MYSQLI_ASSOC);

// Completed sessions (recent)
$completedSessions = [];
$csQ = $conn->query(
    "SELECT s.*, u.full_name AS customer_name, c.console_type, c.unit_number
       FROM sessions s
       JOIN users u ON s.user_id = u.user_id
       JOIN consoles c ON s.console_id = c.console_id
      WHERE s.status = 'completed'
      ORDER BY s.end_time DESC LIMIT 50"
);
if ($csQ) $completedSessions = $csQ->fetch_all(MYSQLI_ASSOC);

// Reservations
$upcomingReservations = [];
$urQ = $conn->query(
    "SELECT r.*, u.full_name AS customer_name, u.phone AS customer_phone
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.status IN ('pending','confirmed')
        AND r.reserved_date >= CURDATE()
      ORDER BY r.reserved_date ASC, r.reserved_time ASC"
);
if ($urQ) $upcomingReservations = $urQ->fetch_all(MYSQLI_ASSOC);

$cancelledReservations = [];
$crQ = $conn->query(
    "SELECT r.*, u.full_name AS customer_name, u.phone AS customer_phone
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.status = 'cancelled'
      ORDER BY r.updated_at DESC LIMIT 30"
);
if ($crQ) $cancelledReservations = $crQ->fetch_all(MYSQLI_ASSOC);

// Customers
$customers = [];
$custQ = $conn->query(
    "SELECT u.user_id, u.full_name, u.email, u.phone, u.created_at, u.is_banned,
            COUNT(DISTINCT s.session_id) AS total_sessions,
            COALESCE(SUM(t.amount),0) AS total_spent
       FROM users u
       LEFT JOIN sessions s ON s.user_id = u.user_id AND s.status = 'completed'
       LEFT JOIN transactions t ON t.user_id = u.user_id AND t.type = 'payment'
      WHERE u.role = 'customer'
      GROUP BY u.user_id
      ORDER BY u.created_at DESC"
);
if ($custQ) $customers = $custQ->fetch_all(MYSQLI_ASSOC);

// Transactions
$transactions = [];
$txQ = $conn->query(
    "SELECT t.*, u.full_name AS customer_name
       FROM transactions t
       JOIN users u ON t.user_id = u.user_id
      ORDER BY t.created_at DESC LIMIT 100"
);
if ($txQ) $transactions = $txQ->fetch_all(MYSQLI_ASSOC);

// Tournaments
$tournaments = [];
$tourQ = $conn->query("SELECT * FROM tournaments ORDER BY start_date DESC");
if ($tourQ) $tournaments = $tourQ->fetch_all(MYSQLI_ASSOC);

// Pending sessions (used by sessions.php)
$pendingSessions = [];
foreach ($recentSessions as $s) {
    $pendingSessions[] = $s;
}
foreach ($completedSessions as $s) {
    $expected = (float)($s['total_cost'] ?? 0);
    $paid     = (float)($s['amount_paid'] ?? 0);
    if ($expected > 0 && $paid < $expected) {
        $pendingSessions[] = $s;
    }
}

// Dashboard stats (needed for dashboard section)
$todayRevenue  = 0;
$trQ = $conn->query(
    "SELECT COALESCE(SUM(amount),0) AS rev FROM transactions
      WHERE type='payment' AND DATE(created_at)=CURDATE()"
);
if ($trQ) $todayRevenue = (float)$trQ->fetch_assoc()['rev'];

$totalRevenue = 0;
$trQ2 = $conn->query("SELECT COALESCE(SUM(amount),0) AS rev FROM transactions WHERE type='payment'");
if ($trQ2) $totalRevenue = (float)$trQ2->fetch_assoc()['rev'];

$totalCustomers = 0;
$tcQ = $conn->query("SELECT COUNT(*) AS n FROM users WHERE role='customer'");
if ($tcQ) $totalCustomers = (int)$tcQ->fetch_assoc()['n'];

$totalSessions = 0;
$tsQ = $conn->query("SELECT COUNT(*) AS n FROM sessions WHERE status='completed'");
if ($tsQ) $totalSessions = (int)$tsQ->fetch_assoc()['n'];

$monthRevenue = 0;
$mrQ = $conn->query(
    "SELECT COALESCE(SUM(amount),0) AS rev FROM transactions
      WHERE type='payment' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"
);
if ($mrQ) $monthRevenue = (float)$mrQ->fetch_assoc()['rev'];

// Reports data
$revenueByConsole = [];
$rbcQ = $conn->query(
    "SELECT c.console_type, COALESCE(SUM(t.amount),0) AS total
       FROM transactions t
       JOIN sessions s ON t.session_id = s.session_id
       JOIN consoles c ON s.console_id = c.console_id
      WHERE t.type = 'payment'
      GROUP BY c.console_type"
);
if ($rbcQ) $revenueByConsole = $rbcQ->fetch_all(MYSQLI_ASSOC);

$typeUsage = [];
$tuQ = $conn->query(
    "SELECT c.console_type, COUNT(*) AS cnt
       FROM sessions s JOIN consoles c ON s.console_id = c.console_id
      WHERE s.status = 'completed'
      GROUP BY c.console_type"
);
if ($tuQ) $typeUsage = $tuQ->fetch_all(MYSQLI_ASSOC);

$revenueLabels = array_column($revenueByConsole, 'console_type');
$revenueData   = array_column($revenueByConsole, 'total');
$typeLabels    = array_column($typeUsage, 'console_type');
$typeCounts    = array_column($typeUsage, 'cnt');

// navBadge style (used by some sections)
$navBadge = 'style="display:inline-flex;align-items:center;justify-content:center;background:#fb566b;color:#fff;border-radius:50%;min-width:18px;height:18px;font-size:10px;font-weight:700;padding:0 3px;margin-left:auto;"';

// ── Render the section and capture output ─────────────────────────────
ob_start();
include __DIR__ . '/../admin_sections/' . $section . '.php';
$html = ob_get_clean();

// Strip any <script> tags to avoid re-registering event listeners
// (keep data-only; action buttons still work via onclick attributes)
// We keep inline onclick= handlers but strip <script>…</script> blocks
$html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

echo json_encode(['html' => $html]);
