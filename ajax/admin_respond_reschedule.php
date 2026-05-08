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
    "SELECT rs.reservation_id, rs.status as rs_status, 
<<<<<<< Updated upstream
             rs.new_date, rs.new_time, rs.console_id, rs.console_type,
             rs.old_date, rs.old_time, rs.old_console_id, rs.old_console_type,
=======
             rs.new_date, rs.new_time, rs.console_id, rs.new_console_type_id,
             rs.old_date, rs.old_time, rs.old_console_id, rs.old_console_type_id,
>>>>>>> Stashed changes
             r.status as r_status
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
        // Approve the reschedule: update reservation date/time/console/type and set status to reserved
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'approved', seen_by_user = 0 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        $upd_r = $conn->prepare("UPDATE reservations SET reserved_date = ?, reserved_time = ?, console_type = ?, console_id = ?, status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('sssii', $res['new_date'], $res['new_time'], $res['console_type'], $res['console_id'], $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule request approved. The reservation schedule, console type, and unit assignment have been updated.';

        logActivity($user['user_id'], "Reschedule Approve", "Approved reschedule request #{$reschedule_id} for Reservation #{$reservation_id}");
    } else {
        // Reject the reschedule: revert to OLD date/time/console and set status to reserved
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'rejected', seen_by_user = 0 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        $upd_r = $conn->prepare("UPDATE reservations SET reserved_date = ?, reserved_time = ?, console_type_id = ?, console_id = ?, status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('ssiii', $res['old_date'], $res['old_time'], $res['old_console_type_id'], $res['old_console_id'], $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule request rejected. The reservation remains on its original schedule.';

        logActivity($user['user_id'], "Reschedule Reject", "Rejected reschedule request #{$reschedule_id} for Reservation #{$reservation_id}");
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
