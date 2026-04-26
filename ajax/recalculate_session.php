<?php
/**
 * AJAX: Recalculate session cost after manual end-time edit.
 * POST params:
 *   session_id    (int)
 *   new_end_hhmm  (string, "HH:MM"  24-hour, e.g. "14:35")
 */
require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$session_id  = (int)($_POST['session_id']   ?? 0);
$new_end_hhmm = trim($_POST['new_end_hhmm'] ?? '');   // "HH:MM"

if (!$session_id || !$new_end_hhmm) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Validate HH:MM
if (!preg_match('/^\d{2}:\d{2}$/', $new_end_hhmm)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format (expected HH:MM)']);
    exit;
}

$tz = new DateTimeZone('Asia/Manila');

// Fetch the session (use Manila timezone throughout)
$stmt = $conn->prepare(
    "SELECT gs.*, s.setting_value AS unlimited_rate
     FROM gaming_sessions gs
     LEFT JOIN system_settings s ON s.setting_key = 'unlimited_rate'
     WHERE gs.session_id = ? AND gs.status = 'completed'"
);
$stmt->bind_param('i', $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Session not found or is still active']);
    exit;
}

// Build start DateTime in Manila tz
$startDt = new DateTime($session['start_time'], $tz);

// Build candidate end DateTime: same calendar date as start, user-supplied HH:MM
[$hh, $mm] = explode(':', $new_end_hhmm);
$endDt = clone $startDt;
$endDt->setTime((int)$hh, (int)$mm, 0);

// If end is not strictly after start → push to next day (midnight crossover)
if ($endDt <= $startDt) {
    $endDt->modify('+1 day');
}

$new_end_time = $endDt->format('Y-m-d H:i:s');   // e.g. "2026-04-14 14:35:00"

$duration = (int) round(($endDt->getTimestamp() - $startDt->getTimestamp()) / 60);

if ($duration <= 0) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit;
}

// Recalculate cost with the same billing engine used in endSession()
$total_cost = computeRentalFee(
    $session['rental_mode'],
    $duration,
    $session['hourly_rate'],
    $session['unlimited_rate'] ?? 300,
    $session['planned_minutes'] ?? null
);

// Add approved additional-request extras (unchanged)
$stmt2 = $conn->prepare("SELECT COALESCE(SUM(extra_cost),0) AS extras FROM additional_requests WHERE session_id = ? AND status = 'approved'");
$stmt2->bind_param('i', $session_id);
$stmt2->execute();
$extras = (float)$stmt2->get_result()->fetch_assoc()['extras'];
$total_cost += $extras;

// Persist — only end_time, duration, cost are touched; start_time is NEVER updated
$upd = $conn->prepare("UPDATE gaming_sessions SET end_time = ?, duration_minutes = ?, total_cost = ? WHERE session_id = ?");
$upd->bind_param('sddi', $new_end_time, $duration, $total_cost, $session_id);
if (!$upd->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
    exit;
}

// Bug #2 fix: only touch the last POSITIVE transaction (never a refund row).
// If no positive transaction exists yet (edge case: open-time session just ended),
// insert a new one rather than silently doing nothing.
$prevTxStmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS paid_before_last
     FROM transactions
     WHERE session_id = ? AND payment_status = 'completed' AND amount > 0
       AND transaction_id < (
           SELECT MAX(transaction_id) FROM transactions
           WHERE session_id = ? AND payment_status = 'completed' AND amount > 0
       )"
);
$prevTxStmt->bind_param('ii', $session_id, $session_id);
$prevTxStmt->execute();
$paidBeforeLast = (float)$prevTxStmt->get_result()->fetch_assoc()['paid_before_last'];

// Amount the last positive transaction should represent
$lastTxAmount = max(0, $total_cost - $paidBeforeLast);

// Try to update the last positive transaction
$txn = $conn->prepare(
    "UPDATE transactions SET amount = ?
     WHERE session_id = ? AND payment_status = 'completed' AND amount > 0
     ORDER BY transaction_id DESC
     LIMIT 1"
);
$txn->bind_param('di', $lastTxAmount, $session_id);
$txn->execute();

// If no positive transaction existed (e.g. open-time session with no upfront), insert one
if ($txn->affected_rows === 0 && $lastTxAmount > 0) {
    $sessUserStmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ?");
    $sessUserStmt->bind_param('i', $session_id);
    $sessUserStmt->execute();
    $sessUser = $sessUserStmt->get_result()->fetch_assoc();
    if ($sessUser) {
        $ins = $conn->prepare(
            "INSERT INTO transactions (session_id, user_id, amount, payment_method, payment_status, notes)
             VALUES (?, ?, ?, 'cash', 'completed', 'Balance from end-time correction')"
        );
        $ins->bind_param('iid', $session_id, $sessUser['user_id'], $lastTxAmount);
        $ins->execute();
    }
}

echo json_encode([
    'success'          => true,
    'end_time_display' => $endDt->format('h:i A'),         // e.g. "02:35 PM"
    'end_time_24'      => $endDt->format('H:i'),            // e.g. "14:35"
    'duration_minutes' => $duration,
    'duration_display' => $duration > 0 ? $duration . ' min' : '< 1 min',
    'total_cost'       => $total_cost,
    'cost_display'     => '₱' . number_format($total_cost, 2),
]);
