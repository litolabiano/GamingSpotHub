<?php
/**
 * ajax/cancel_reservation.php
 * Customer-side reservation cancellation.
 *
 * POST: reservation_id
 * Returns JSON {success, message, had_payment, amount, is_late_cancel}
 *
 * Business rules (per FAQ):
 *  - Cancel BEFORE reserved start time → full refund flagged
 *  - Cancel AFTER  reserved start time → flagged as late; staff applies inconvenience fee
 *  - 3 consecutive user-cancels → 1-week reservation ban (automatically lifted)
 *  - Cancelling when reservation is not pending/confirmed → error
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$user   = getCurrentUser();
$uid    = (int)$user['user_id'];
$res_id = (int)($_POST['reservation_id'] ?? 0);

if (!$res_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation.']);
    exit;
}

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

// ── Timing check: before or after reserved slot start? ───────────────────────
$reservedAt  = strtotime($r['reserved_date'] . ' ' . $r['reserved_time']);
$now         = time();
$isLateCancel = ($now >= $reservedAt);   // true = inconvenience fee applies

// ── Cancel the reservation ───────────────────────────────────────────────────
$stmt2 = $conn->prepare(
    "UPDATE reservations SET status = 'cancelled', cancelled_by = 'user' WHERE reservation_id = ?"
);
$stmt2->bind_param('i', $res_id);
$stmt2->execute();

// ── Update cancellation streak on the user ───────────────────────────────────
// Fetch current streak
$su = $conn->prepare("SELECT consecutive_cancellations FROM users WHERE user_id = ?");
$su->bind_param('i', $uid);
$su->execute();
$uData = $su->get_result()->fetch_assoc();
$streak = (int)($uData['consecutive_cancellations'] ?? 0) + 1;

$banUntil = null;
$banMsg   = '';

if ($streak >= 3) {
    // Apply 7-day reservation ban and reset streak
    $banUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
    $streak   = 0;   // reset after ban is applied
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
$hadPayment   = (float)$r['downpayment_amount'] > 0;
$grossAmount  = (float)$r['downpayment_amount'];

// Resolve inconvenience fee from settings (default ₱50 if not set)
require_once __DIR__ . '/../includes/db_functions.php';
$inconvenienceFee = (float)(getSetting('inconvenience_fee') ?? 50);
$netAmount        = $hadPayment && $isLateCancel
    ? max(0, $grossAmount - $inconvenienceFee)
    : $grossAmount;

$msg = 'Reservation #' . $res_id . ' has been cancelled.';

if ($hadPayment) {
    if ($isLateCancel) {
        $feeStr  = '₱' . number_format($inconvenienceFee, 2);
        $netStr  = '₱' . number_format($netAmount, 2);
        $msg .= ' You cancelled after your reserved start time. An inconvenience fee of ' . $feeStr
              . ' has been deducted. Your refund will be ' . $netStr . ' — processed by staff.';
    } else {
        $msg .= ' A full refund of ₱' . number_format($grossAmount, 2) . ' will be processed by staff.';
    }
}

// Streak warning (only if not banned yet)
if ($streak === 2 && !$banUntil) {
    $msg .= ' ⚠️ Warning: 1 more cancellation will result in a 7-day reservation ban.';
} elseif ($banUntil) {
    $msg .= $banMsg;
}

echo json_encode([
    'success'           => true,
    'message'           => $msg,
    'had_payment'       => $hadPayment,
    'gross_amount'      => $grossAmount,
    'inconvenience_fee' => $isLateCancel ? $inconvenienceFee : 0,
    'amount'            => $netAmount,        // net refund due to customer
    'is_late_cancel'    => $isLateCancel,
    'streak'            => $streak,
    'banned_until'      => $banUntil,
]);

