<?php
/**
 * AJAX — Admin proposes a reschedule for a reservation (pending, awaiting user confirmation)
 * POST: reservation_id, new_date, new_time, reason, reason_detail, console_type, console_id
 *
 * The reservation date/time is NOT changed immediately. A pending reschedule proposal is
 * inserted into reservation_reschedules (initiated_by='admin', status='pending').
 * The user will be shown the proposed date as the earliest option to confirm.
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

$valid_reasons = ['typhoon', 'power_outage', 'emergency', 'maintenance', 'other'];

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
    "SELECT r.reservation_id, r.user_id, r.reserved_date, r.reserved_time, r.status,
            r.console_id, r.console_type_id,
            ct.type_name AS console_type,
            u.email, u.full_name
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
       LEFT JOIN console_types ct ON r.console_type_id = ct.type_id
      WHERE r.reservation_id = ? AND r.status IN ('pending','reserved')"
);
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found or cannot be rescheduled in its current status.']);
    exit;
}

$old_date         = $res['reserved_date'];
$old_time         = $res['reserved_time'];
$old_console_type = $res['console_type'];
$old_console_id   = $res['console_id'];
$old_type_id      = $res['console_type_id'];
$user_id          = (int)$res['user_id'];

// Resolve new console_type name → ID
$new_type_id = $old_type_id; // default: keep old type
if ($new_console_type) {
    $tStmt = $conn->prepare("SELECT type_id FROM console_types WHERE type_name = ? AND is_archived = 0 LIMIT 1");
    $tStmt->bind_param('s', $new_console_type);
    $tStmt->execute();
    $tRow = $tStmt->get_result()->fetch_assoc();
    if ($tRow) $new_type_id = (int)$tRow['type_id'];
}

if (!$new_console_type) {
    $new_console_type = $res['console_type'];
}

if ($new_date === $old_date &&
    substr($new_time, 0, 5) === substr($old_time, 0, 5) &&
    (int)$new_console_id === (int)$res['console_id'] &&
    $new_type_id === $old_type_id) {
    echo json_encode(['success' => false, 'message' => 'New schedule/console cannot be the same as the current reservation.']);
    exit;
}

// Check if there's already a pending proposal for this reservation
$chk = $conn->prepare(
    "SELECT reschedule_id FROM reservation_reschedules
      WHERE reservation_id = ? AND status = 'pending' LIMIT 1"
);
$chk->bind_param('i', $reservation_id);
$chk->execute();
$existingRow = $chk->get_result()->fetch_assoc();
$existingId = $existingRow ? (int)$existingRow['reschedule_id'] : null;
$chk->close();

$conn->begin_transaction();
try {
    $staff_id = (int)$user['user_id'];

    if ($existingId) {
        // Update existing pending proposal
        $log = $conn->prepare(
            "UPDATE reservation_reschedules
                SET new_date              = ?,
                    new_time              = ?,
                    console_id            = ?,
                    console_type_id       = ?,
                    reason                = ?,
                    reason_detail         = ?,
                    rescheduled_by        = ?,
                    initiated_by          = 'admin',
                    seen_by_user          = 0,
                    created_at            = NOW()
              WHERE reschedule_id         = ?"
        );
        $log->bind_param(
            'ssiissii',
            $new_date, $new_time, $new_console_id, $new_type_id,
            $reason, $reason_detail, $staff_id, $existingId
        );
    } else {
        // Insert a NEW pending proposal
        $log = $conn->prepare(
            "INSERT INTO reservation_reschedules
                (reservation_id, user_id,
                 old_date, old_time, old_console_id, old_console_type_id,
                 new_date, new_time, console_id, console_type_id,
                 reason, reason_detail, rescheduled_by, initiated_by, status, seen_by_user)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin', 'pending', 0)"
        );
        $log->bind_param(
            'iississiisssi',
            $reservation_id, $user_id,
            $old_date, $old_time, $old_console_id, $old_type_id,
            $new_date, $new_time, $new_console_id, $new_type_id,
            $reason, $reason_detail,
            $staff_id
        );
    }
    $log->execute();
    $log->close();

    // Set the main reservation status to 'pending' immediately
    $upd = $conn->prepare("UPDATE reservations SET status = 'pending', updated_at = NOW() WHERE reservation_id = ?");
    $upd->bind_param('i', $reservation_id);
    $upd->execute();
    $upd->close();

    $conn->commit();

    logActivity($staff_id, "Reschedule Proposal", "Proposed new schedule for Reservation #{$reservation_id}: {$new_date} {$new_time}. Reason: " . ucfirst($reason));

    // Notify user by email that a reschedule proposal is pending their confirmation
    $fullName = trim($res['full_name']);
    try {
        sendRescheduleProposalEmail($res['email'], $fullName, $old_date, $old_time, $new_date, $new_time);
    } catch (Throwable $e) {
        // Email failure or fatal error in mail helper should not block success response
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reschedule proposal sent to the customer for Reservation #' . $reservation_id
                   . '. They will be asked to confirm a date from '
                   . date('M d, Y', strtotime($new_date)) . ' onwards. '
                   . 'The reservation date has NOT been changed yet.',
        'proposed_date' => $new_date,
        'proposed_time' => $new_time,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
