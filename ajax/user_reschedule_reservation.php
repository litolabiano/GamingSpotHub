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
$res_id           = (int)trim($_POST['reservation_id'] ?? 0);
$new_date         = trim($_POST['new_date'] ?? '');
$new_time         = trim($_POST['new_time'] ?? '');
$new_console_type = trim($_POST['console_type'] ?? '');
$new_console_id   = (int)($_POST['console_id'] ?? 0) ?: null;



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
    "SELECT r.reservation_id, r.user_id, r.console_type_id,
            ct.type_name AS console_type,
            r.console_id, r.rental_mode, r.planned_minutes,
            r.reserved_date, r.reserved_time, r.status, r.downpayment_amount
       FROM reservations r
       LEFT JOIN console_types ct ON r.console_type_id = ct.type_id
      WHERE r.reservation_id = ? AND r.user_id = ?"
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
      WHERE reservation_id = ? AND rescheduled_by = ? AND status != 'rejected'
      LIMIT 1"
);
$chk->bind_param('ii', $res_id, $uid);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already used your one-time reschedule for this reservation.']);
    exit;
}

// ── Availability check — ensure the new slot is not already taken ─────────────
// We check for any pending/confirmed reservation on the NEW requested console_type
// whose time window overlaps the new slot (using planned_minutes for hourly,
// or a 2-hour window for open_time/unlimited).
$newSlotStart = $newDT->getTimestamp();
$newSlotEnd   = $newSlotStart + 7200; // 2h safety window for open/unlimited

$avail = $conn->prepare(
    "SELECT r.reservation_id FROM reservations r
       JOIN console_types ct ON r.console_type_id = ct.type_id
      WHERE ct.type_name = ?
        AND r.reserved_date = ?
        AND r.status IN ('pending','reserved')
        AND r.reservation_id != ?
        AND (
              ? BETWEEN UNIX_TIMESTAMP(CONCAT(r.reserved_date,' ',r.reserved_time))
                    AND UNIX_TIMESTAMP(CONCAT(r.reserved_date,' ',r.reserved_time))
                         + GREATEST(IFNULL(r.planned_minutes,120),120)*60
              OR
              ? BETWEEN UNIX_TIMESTAMP(CONCAT(r.reserved_date,' ',r.reserved_time))
                    AND UNIX_TIMESTAMP(CONCAT(r.reserved_date,' ',r.reserved_time))
                         + GREATEST(IFNULL(r.planned_minutes,120),120)*60
            )
      LIMIT 1"
);
$avail->bind_param('ssidd', $new_console_type, $new_date, $res_id, $newSlotStart, $newSlotEnd);
$avail->execute();
if ($avail->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "The selected time slot already has a reservation for $new_console_type. Please choose a different time or platform."]);
    exit;
}


// ── All checks pass — set to pending and create request ───────────────────────
$old_date         = $res['reserved_date'];
$old_time         = $res['reserved_time'];
$old_console_id   = $res['console_id'];
$old_type_id      = $res['console_type_id'];

// Resolve new console_type name → ID
$new_type_id = $old_type_id;
if ($new_console_type) {
    $tStmt = $conn->prepare("SELECT type_id FROM console_types WHERE type_name = ? AND is_archived = 0 LIMIT 1");
    $tStmt->bind_param('s', $new_console_type);
    $tStmt->execute();
    $tRow = $tStmt->get_result()->fetch_assoc();
    if ($tRow) $new_type_id = (int)$tRow['type_id'];
}

$conn->begin_transaction();
try {
    // 1. Update the reservation with the new details and set status to pending
    $upd = $conn->prepare(
        "UPDATE reservations 
            SET reserved_date     = ?, 
                reserved_time     = ?, 
                console_type_id   = ?, 
                console_id        = ?, 
                status            = 'pending', 
                updated_at        = NOW()
          WHERE reservation_id = ?"
    );
    $upd->bind_param('ssiiii', $new_date, $new_time, $new_type_id, $new_console_id, $res_id);
    $upd->execute();

    // 2. Log the reschedule request — initiated_by = 'user', status = 'pending'
    $reason        = 'user_request';
    $reason_detail = 'Customer self-reschedule request.';
    $log = $conn->prepare(
        "INSERT INTO reservation_reschedules
            (reservation_id, user_id, 
<<<<<<< Updated upstream
             old_date, old_time, old_console_id, old_console_type,
             new_date, new_time, console_id, console_type,
=======
             old_date, old_time, old_console_id, old_console_type_id,
             new_date, new_time, console_id, new_console_type_id,
>>>>>>> Stashed changes
             reason, reason_detail, rescheduled_by, initiated_by, status, seen_by_user)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 'pending', 1)"
    );
    $log->bind_param(
        'iississsisssi',
        $res_id, $uid,
        $old_date, $old_time, $old_console_id, $old_type_id,
        $new_date, $new_time, $new_console_id, $new_type_id,
        $reason, $reason_detail,
        $uid
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
