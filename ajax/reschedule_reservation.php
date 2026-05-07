<?php
/**
 * AJAX — Reschedule a reservation (staff/admin only)
 * POST: reservation_id, new_date, new_time, reason, reason_detail
 */
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/mail_helper.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser();
if (!in_array($user['role'], ['shopkeeper', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$reservation_id   = (int)($_POST['reservation_id'] ?? 0);
$new_date         = trim($_POST['new_date']       ?? '');
$new_time         = trim($_POST['new_time']       ?? '');
$new_console_type = trim($_POST['console_type']    ?? '');
$new_console_id   = (int)($_POST['console_id']    ?? 0) ?: null;
$reason           = trim($_POST['reason']         ?? 'other');
$reason_detail    = trim($_POST['reason_detail']  ?? '') ?: null;

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
    "SELECT r.reservation_id, r.user_id, r.reserved_date, r.reserved_time, r.status, u.email, u.full_name, r.console_id, r.console_type
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.reservation_id = ? AND r.status IN ('pending','reserved')"
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

// If console type not passed, keep current
if (!$new_console_type) {
    $new_console_type = $res['console_type'];
}

if ($new_date === $old_date && 
    substr($new_time, 0, 5) === substr($old_time, 0, 5) && 
    (int)$new_console_id === (int)$res['console_id'] &&
    $new_console_type === $res['console_type']) {
    echo json_encode(['success' => false, 'message' => 'New schedule/console cannot be the same as the current reservation.']);
    exit;
}

$conn->begin_transaction();
try {
    // Update the reservation date/time/console and set status to pending
    $upd = $conn->prepare(
        "UPDATE reservations SET reserved_date = ?, reserved_time = ?, console_type = ?, console_id = ?, status = 'pending', updated_at = NOW()
          WHERE reservation_id = ?"
    );
    $upd->bind_param('sssii', $new_date, $new_time, $new_console_type, $new_console_id, $reservation_id);
    $upd->execute();

    // Log the reschedule (also acts as user notification)
    $log = $conn->prepare(
        "INSERT INTO reservation_reschedules
            (reservation_id, user_id, old_date, old_time, new_date, new_time, console_id, console_type,
             reason, reason_detail, rescheduled_by, initiated_by, status, seen_by_user)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin', 'pending', 0)"
    );
    $staff_id = (int)$user['user_id'];
    $log->bind_param(
        'iissssisssi',
        $reservation_id, $user_id,
        $old_date, $old_time,
        $new_date, $new_time,
        $new_console_id,
        $new_console_type,
        $reason, $reason_detail,
        $staff_id
    );
    $log->execute();


    $reschedule_id = $conn->insert_id;

    $conn->commit();

    $fullName = trim($res['full_name']);
    sendRescheduleNotificationEmail($res['email'], $fullName, $new_date, $new_time);

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
