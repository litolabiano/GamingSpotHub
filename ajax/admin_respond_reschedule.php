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
             rs.new_date, rs.new_time, rs.console_id, rs.new_console_type_id,
             rs.new_controller_id, rs.new_controller_id_2,
             rs.old_date, rs.old_time, rs.old_console_id, rs.old_console_type_id,
             rs.old_controller_id, rs.old_controller_id_2,
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

        $newCtrl1 = $res['new_controller_id'] ?? $res['old_controller_id'];
        $newCtrl2 = $res['new_controller_id_2'] ?? $res['old_controller_id_2'];

        $num_controllers = ($newCtrl1 ? 1 : 0) + ($newCtrl2 ? 1 : 0);
        $with_controller = $num_controllers > 0 ? 1 : 0;
        
        $setStmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'controller_rental_fee'");
        $ctrlRate = $setStmt && $setStmt->num_rows > 0 ? (float)$setStmt->fetch_assoc()['setting_value'] : 20.0;
        $controller_fee = $num_controllers * $ctrlRate;

        $upd_r = $conn->prepare("UPDATE reservations SET reserved_date = ?, reserved_time = ?, console_type_id = ?, console_id = ?, controller_id = ?, controller_id_2 = ?, with_controller = ?, controller_fee = ?, status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('ssiiiiidi', $res['new_date'], $res['new_time'], $res['new_console_type_id'], $res['console_id'], $newCtrl1, $newCtrl2, $with_controller, $controller_fee, $reservation_id);
        $upd_r->execute();

        $message = 'Reschedule request approved. The reservation schedule, console type, and unit assignments have been updated.';

        logActivity($user['user_id'], "Reschedule Approve", "Approved reschedule request #{$reschedule_id} for Reservation #{$reservation_id}");
    } else {
        // Reject the reschedule: revert to OLD date/time/console and set status to reserved
        $upd_rs = $conn->prepare("UPDATE reservation_reschedules SET status = 'rejected', seen_by_user = 0 WHERE reschedule_id = ?");
        $upd_rs->bind_param('i', $reschedule_id);
        $upd_rs->execute();

        $upd_r = $conn->prepare("UPDATE reservations SET reserved_date = ?, reserved_time = ?, console_type_id = ?, console_id = ?, controller_id = ?, controller_id_2 = ?, status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd_r->bind_param('ssiiiii', $res['old_date'], $res['old_time'], $res['old_console_type_id'], $res['old_console_id'], $res['old_controller_id'], $res['old_controller_id_2'], $reservation_id);
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
