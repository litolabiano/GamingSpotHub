<?php
/**
 * ajax/user_reschedule_reservation.php
 * Customer-initiated reservation reschedule.
 *
 * POST: reservation_id, new_date, new_time
 * Returns JSON {success, message, new_date_display, new_time_display}
 *
 * Business rules:
 *  - Only the owner of the reservation may reschedule it.
 *  - Reservation must be pending or confirmed.
 *  - One reschedule per reservation — tracked via reservation_reschedules (rescheduled_by = user_id).
 *  - New date: today → +1 month (same window as original booking).
 *  - New time: 12:00–23:00, must be at least 1 hour from now.
 *  - Availability (slot conflict) check runs on the new date/time.
 *  - Does NOT alter consecutive_cancellations — this is not a cancel.
 *  - Original downpayment carries over automatically (no new payment).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$user   = getCurrentUser();
$uid    = (int)$user['user_id'];

// ── Input ─────────────────────────────────────────────────────────────────────
$res_id   = (int)trim($_POST['reservation_id'] ?? 0);
$new_date = trim($_POST['new_date'] ?? '');
$new_time = trim($_POST['new_time'] ?? '');

// Basic presence check
if (!$res_id || !$new_date || !$new_time) {
    echo json_encode(['success' => false, 'message' => 'Reservation ID, new date, and new time are required.']);
    exit;
}

// ── Date/time validations ─────────────────────────────────────────────────────
$tz    = new DateTimeZone('Asia/Manila');
$now   = new DateTime('now', $tz);
$today = $now->format('Y-m-d');

// Must not be in the past
if ($new_date < $today) {
    echo json_encode(['success' => false, 'message' => 'New date cannot be in the past.']);
    exit;
}

// Must be within 1 month (same rule as original booking)
$maxDate = (new DateTime('+1 month', $tz))->format('Y-m-d');
if ($new_date > $maxDate) {
    echo json_encode(['success' => false, 'message' => 'Reservations can only be scheduled up to 1 month in advance.']);
    exit;
}

// Operating hours: 12:00–23:00
if ($new_time < '12:00' || $new_time > '23:00') {
    echo json_encode(['success' => false, 'message' => 'Reservation time must be between 12:00 PM and 11:00 PM.']);
    exit;
}

// Must be at least 1 hour from now
$newDT = DateTime::createFromFormat('Y-m-d H:i', "$new_date $new_time", $tz);
if (!$newDT || $newDT->getTimestamp() < ($now->getTimestamp() + 3600)) {
    echo json_encode(['success' => false, 'message' => 'Reservation must be at least 1 hour from now.']);
    exit;
}

// ── Fetch reservation — must belong to this user ──────────────────────────────
$stmt = $conn->prepare(
    "SELECT reservation_id, user_id, console_type, rental_mode, planned_minutes,
            reserved_date, reserved_time, status, downpayment_amount
       FROM reservations
      WHERE reservation_id = ? AND user_id = ?"
);
$stmt->bind_param('ii', $res_id, $uid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
    exit;
}
if (!in_array($res['status'], ['pending', 'reserved'])) {
    echo json_encode(['success' => false, 'message' => 'Only pending or confirmed reservations can be rescheduled.']);
    exit;
}

// ── One-reschedule-per-reservation guard ──────────────────────────────────────
// A customer-initiated reschedule is logged with rescheduled_by = uid (the customer).
// Staff reschedules use the staff's user_id (which differs from the reservation's user_id).
// We detect "already rescheduled by the customer" by checking if any row for this
// reservation_id was created by the customer themselves.
$chk = $conn->prepare(
    "SELECT reschedule_id FROM reservation_reschedules
      WHERE reservation_id = ? AND rescheduled_by = ?
      LIMIT 1"
);
$chk->bind_param('ii', $res_id, $uid);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already used your one-time reschedule for this reservation.']);
    exit;
}

// ── Availability check — ensure the new slot is not already taken ─────────────
// We check for any pending/confirmed reservation on the same console_type
// whose time window overlaps the new slot (using planned_minutes for hourly,
// or a 2-hour window for open_time/unlimited).
$newSlotStart = $newDT->getTimestamp();
$newSlotEnd   = $newSlotStart + 7200; // 2h safety window for open/unlimited

$avail = $conn->prepare(
    "SELECT reservation_id FROM reservations
      WHERE console_type = ?
        AND reserved_date = ?
        AND status IN ('pending','reserved')
        AND reservation_id != ?
        AND (
              -- New slot starts inside an existing reservation window
              ? BETWEEN UNIX_TIMESTAMP(CONCAT(reserved_date,' ',reserved_time))
                    AND UNIX_TIMESTAMP(CONCAT(reserved_date,' ',reserved_time))
                         + GREATEST(IFNULL(planned_minutes,120),120)*60
              OR
              -- New slot ends inside an existing reservation window
              ? BETWEEN UNIX_TIMESTAMP(CONCAT(reserved_date,' ',reserved_time))
                    AND UNIX_TIMESTAMP(CONCAT(reserved_date,' ',reserved_time))
                         + GREATEST(IFNULL(planned_minutes,120),120)*60
            )
      LIMIT 1"
);
$avail->bind_param('ssidd', $res['console_type'], $new_date, $res_id, $newSlotStart, $newSlotEnd);
$avail->execute();
if ($avail->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'That time slot already has a reservation for the same console type. Please choose a different time.']);
    exit;
}

// ── All checks pass — set to pending and create request ───────────────────────
$old_date = $res['reserved_date'];
$old_time = $res['reserved_time'];

$conn->begin_transaction();
try {
    // 1. Set the reservation to pending (do not change date/time yet)
    $upd = $conn->prepare(
        "UPDATE reservations SET status = 'pending', updated_at = NOW()
          WHERE reservation_id = ?"
    );
    $upd->bind_param('i', $res_id);
    $upd->execute();

    // 2. Log the reschedule request — initiated_by = 'user', status = 'pending'
    $reason        = 'user_request';
    $reason_detail = 'Customer self-reschedule request.';
    $log = $conn->prepare(
        "INSERT INTO reservation_reschedules
            (reservation_id, user_id, old_date, old_time, new_date, new_time,
             reason, reason_detail, rescheduled_by, initiated_by, status, seen_by_user)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 'pending', 1)"
    );
    $log->bind_param(
        'iissssssi',
        $res_id, $uid,
        $old_date, $old_time,
        $new_date, $new_time,
        $reason, $reason_detail,
        $uid   // rescheduled_by = the customer
    );
    $log->execute();

    $conn->commit();

    // ── Format for response ───────────────────────────────────────────────────
    $newDateDisplay = date('M d, Y', strtotime($new_date));
    $newTimeDisplay = date('g:i A', strtotime("2000-01-01 $new_time"));

    echo json_encode([
        'success'          => true,
        'message'          => 'Your reschedule request to ' . $newDateDisplay . ' at ' . $newTimeDisplay . ' has been submitted and is pending admin approval.',
        'new_date_display' => $newDateDisplay,
        'new_time_display' => $newTimeDisplay,
        'new_date'         => $new_date,
        'new_time'         => $new_time,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
