<?php
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser();
$user_id = (int)$user['user_id'];

$reschedule_id = (int)($_POST['reschedule_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'confirm' or 'cancel'

if (!$reschedule_id || !in_array($action, ['confirm', 'cancel'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT rs.reservation_id, rs.status as rs_status, r.status as r_status
     FROM reservation_reschedules rs
     JOIN reservations r ON rs.reservation_id = r.reservation_id
     WHERE rs.reschedule_id = ? AND rs.user_id = ?"
);
$stmt->bind_param('ii', $reschedule_id, $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Reschedule request not found.']);
    exit;
}

if ($res['rs_status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'This request has already been processed.']);
    exit;
}

$reservation_id = $res['reservation_id'];

$conn->begin_transaction();
try {
    if ($action === 'confirm') {
        // Approve the reschedule
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'approved', seen_by_user = 1 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        // Reactivate reservation
        $upd_r = $conn->prepare("UPDATE reservations SET status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('i', $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule confirmed! Your reservation has been updated.';
    } else {
        // Cancel the reschedule AND forfeit the reservation
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'cancelled', seen_by_user = 1 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        $reason = 'User rejected admin reschedule';
        $upd_r = $conn->prepare("UPDATE reservations SET status = 'cancelled', cancelled_by = 'user', cancellation_reason = ?, cancel_reason_type = 'schedule_change', refund_issued = 0, updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('si', $reason, $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule cancelled. The reservation has been forfeited in accordance with the no-refund policy.';
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
