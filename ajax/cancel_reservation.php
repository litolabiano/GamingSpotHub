<?php
/**
 * ajax/cancel_reservation.php
 * Customer-side reservation cancellation.
 * POST: reservation_id
 * Returns JSON {success, message}
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

// Fetch the reservation — must belong to this user and be cancellable
$stmt = $conn->prepare(
    "SELECT reservation_id, status, downpayment_amount, refund_issued
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
    echo json_encode(['success' => false, 'message' => 'This reservation cannot be cancelled (status: ' . $r['status'] . ').']);
    exit;
}

// Cancel it — mark cancelled_by = 'user'
$stmt2 = $conn->prepare(
    "UPDATE reservations SET status = 'cancelled', cancelled_by = 'user' WHERE reservation_id = ?"
);
$stmt2->bind_param('i', $res_id);
$stmt2->execute();

$hadPayment = (float)$r['downpayment_amount'] > 0;
$msg = 'Reservation #' . $res_id . ' has been cancelled.';
if ($hadPayment) {
    $msg .= ' A refund of ₱' . number_format((float)$r['downpayment_amount'], 2) . ' will be processed by staff.';
}

echo json_encode([
    'success'     => true,
    'message'     => $msg,
    'had_payment' => $hadPayment,
    'amount'      => (float)$r['downpayment_amount'],
]);
