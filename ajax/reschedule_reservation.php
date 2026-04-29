<?php
/**
 * AJAX — Reschedule a reservation (staff/admin only)
 * POST: reservation_id, new_date, new_time, reason, reason_detail
 */
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

$reservation_id = (int)($_POST['reservation_id'] ?? 0);
$new_date       = trim($_POST['new_date']       ?? '');
$new_time       = trim($_POST['new_time']       ?? '');
$reason         = trim($_POST['reason']         ?? 'other');
$reason_detail  = trim($_POST['reason_detail']  ?? '') ?: null;

$valid_reasons  = ['typhoon','power_outage','emergency','maintenance','other'];
if (!$reservation_id || !$new_date || !$new_time) {
    echo json_encode(['success' => false, 'message' => 'Reservation ID, new date and new time are required.']);
    exit;
}
if (!in_array($reason, $valid_reasons)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reason.']);
    exit;
}

// Validate date not in past
$today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
if ($new_date < $today) {
    echo json_encode(['success' => false, 'message' => 'New date cannot be in the past.']);
    exit;
}

// Validate business hours (12:00–23:00)
if ($new_time < '12:00' || $new_time > '23:00') {
    echo json_encode(['success' => false, 'message' => 'New time must be between 12:00 PM and 11:00 PM.']);
    exit;
}

// Fetch current reservation
$stmt = $conn->prepare(
    "SELECT reservation_id, user_id, reserved_date, reserved_time, status
       FROM reservations
      WHERE reservation_id = ? AND status IN ('pending','confirmed')"
);
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found or cannot be rescheduled in its current status.']);
    exit;
}

$old_date = $res['reserved_date'];
$old_time = $res['reserved_time'];
$user_id  = (int)$res['user_id'];

$conn->begin_transaction();
try {
    // Update the reservation date/time
    $upd = $conn->prepare(
        "UPDATE reservations SET reserved_date = ?, reserved_time = ?, updated_at = NOW()
          WHERE reservation_id = ?"
    );
    $upd->bind_param('ssi', $new_date, $new_time, $reservation_id);
    $upd->execute();

    // Log the reschedule (also acts as user notification)
    $log = $conn->prepare(
        "INSERT INTO reservation_reschedules
            (reservation_id, user_id, old_date, old_time, new_date, new_time,
             reason, reason_detail, rescheduled_by, seen_by_user)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
    );
    $staff_id = (int)$user['user_id'];
    $log->bind_param(
        'iissssssi',
        $reservation_id, $user_id,
        $old_date, $old_time,
        $new_date, $new_time,
        $reason, $reason_detail,
        $staff_id
    );
    $log->execute();

    $conn->commit();

    $reason_labels = [
        'typhoon'       => 'Typhoon / Bad Weather',
        'power_outage'  => 'Power Outage',
        'emergency'     => 'Emergency',
        'maintenance'   => 'Equipment Maintenance',
        'other'         => 'Other',
    ];

    echo json_encode([
        'success'  => true,
        'message'  => 'Reservation #' . $reservation_id . ' rescheduled from '
                    . date('M d, Y g:i A', strtotime("$old_date $old_time"))
                    . ' to ' . date('M d, Y g:i A', strtotime("$new_date $new_time"))
                    . '. The user will be notified.',
        'new_date' => $new_date,
        'new_time' => $new_time,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
