<?php
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser();
if (!in_array($user['role'], ['shopkeeper', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$reschedule_id = (int)($_POST['reschedule_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'

if (!$reschedule_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT rs.reservation_id, rs.status as rs_status, rs.new_date, rs.new_time, r.status as r_status
     FROM reservation_reschedules rs
     JOIN reservations r ON rs.reservation_id = r.reservation_id
     WHERE rs.reschedule_id = ?"
);
$stmt->bind_param('i', $reschedule_id);
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
    if ($action === 'approve') {
        // Approve the reschedule: update reservation date/time and set status to reserved
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'approved', seen_by_user = 0 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        $upd_r = $conn->prepare("UPDATE reservations SET reserved_date = ?, reserved_time = ?, status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('ssi', $res['new_date'], $res['new_time'], $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule request approved. The reservation schedule has been updated.';
    } else {
        // Reject the reschedule: keep old date/time, revert status to reserved
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'rejected', seen_by_user = 0 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        $upd_r = $conn->prepare("UPDATE reservations SET status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('i', $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule request rejected. The reservation remains on its original schedule.';
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
