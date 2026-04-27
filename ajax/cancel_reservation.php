<?php
/**
 * ajax/cancel_reservation.php
 * Customer-side reservation cancellation.
 *
 * POST: reservation_id, cancel_reason_type, cancel_reason_detail (optional)
 * Returns JSON {success, message, streak, banned_until}
 *
 * Business rules:
 *  - Cancellation reason is MANDATORY.
 *  - All payments are NON-REFUNDABLE — no refund logic executed here.
 *  - 3 consecutive user-cancels → 1-week reservation ban (automatically lifted).
 *  - Cancelling when reservation is not pending/confirmed → error.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$user   = getCurrentUser();
$uid    = (int)$user['user_id'];
$res_id = (int)($_POST['reservation_id'] ?? 0);

// ── Allowed reason types (must match the DB ENUM) ────────────────────────────
$allowedReasons = ['schedule_change', 'found_alternative', 'budget_issue',
                   'technical_issue', 'emergency', 'other'];

$reasonType   = trim($_POST['cancel_reason_type']   ?? '');
$reasonDetail = trim($_POST['cancel_reason_detail'] ?? '');

if (!$res_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation.']);
    exit;
}

if (!in_array($reasonType, $allowedReasons)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid reason for cancellation.']);
    exit;
}

// "Other" requires a written detail
if ($reasonType === 'other' && $reasonDetail === '') {
    echo json_encode(['success' => false, 'message' => 'Please describe your reason for cancellation.']);
    exit;
}

// Sanitize detail
$reasonDetail = $reasonDetail !== '' ? htmlspecialchars(strip_tags($reasonDetail), ENT_QUOTES, 'UTF-8') : null;

// ── Fetch the reservation — must belong to this user ─────────────────────────
$stmt = $conn->prepare(
    "SELECT reservation_id, status, downpayment_amount, refund_issued,
            reserved_date, reserved_time
       FROM reservations
      WHERE reservation_id = ? AND user_id = ?"
);
$stmt->bind_param('ii', $res_id, $uid);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
    exit;
}
if (!in_array($r['status'], ['pending', 'confirmed'])) {
    echo json_encode([
        'success' => false,
        'message' => 'This reservation cannot be cancelled (status: ' . $r['status'] . ').',
    ]);
    exit;
}

// ── Cancel the reservation and record the reason ─────────────────────────────
$stmt2 = $conn->prepare(
    "UPDATE reservations
        SET status             = 'cancelled',
            cancelled_by       = 'user',
            cancel_reason_type = ?,
            cancel_reason_detail = ?
      WHERE reservation_id = ?"
);
$stmt2->bind_param('ssi', $reasonType, $reasonDetail, $res_id);
$stmt2->execute();

// ── Update cancellation streak on the user ───────────────────────────────────
$su = $conn->prepare("SELECT consecutive_cancellations FROM users WHERE user_id = ?");
$su->bind_param('i', $uid);
$su->execute();
$uData  = $su->get_result()->fetch_assoc();
$streak = (int)($uData['consecutive_cancellations'] ?? 0) + 1;

$banUntil = null;
$banMsg   = '';

if ($streak >= 3) {
    // Apply 7-day reservation ban and reset streak
    $banUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
    $streak   = 0;
    $banMsg   = ' Your account has been placed on a 7-day reservation ban due to 3 consecutive cancellations. You can still walk in — only online reservations are suspended.';

    $ub = $conn->prepare(
        "UPDATE users SET consecutive_cancellations = 0, reservation_banned_until = ? WHERE user_id = ?"
    );
    $ub->bind_param('si', $banUntil, $uid);
    $ub->execute();
} else {
    $ub = $conn->prepare(
        "UPDATE users SET consecutive_cancellations = ? WHERE user_id = ?"
    );
    $ub->bind_param('ii', $streak, $uid);
    $ub->execute();
}

// ── Build response ───────────────────────────────────────────────────────────
$msg = 'Reservation #' . $res_id . ' has been cancelled.';

// Streak warning (only if not banned yet)
if ($streak === 2 && !$banUntil) {
    $msg .= ' ⚠️ Warning: 1 more cancellation will result in a 7-day reservation ban.';
} elseif ($banUntil) {
    $msg .= $banMsg;
}

echo json_encode([
    'success'      => true,
    'message'      => $msg,
    'streak'       => $streak,
    'banned_until' => $banUntil,
]);
