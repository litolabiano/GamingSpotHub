<?php
/**
 * ajax/admin_respond_reschedule.php
 * Handles admin Approve / Reject of customer-initiated reschedule requests.
 *
 * POST params:
 *   reschedule_id  (int)    – ID from reservation_reschedules table
 *   action         (string) – 'approve' or 'reject'
 */

// Buffer output so any stray PHP notices don't corrupt the JSON response
ob_start();

require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/mail_helper.php';

ob_clean();
header('Content-Type: application/json');

function jsonOut(bool $success, string $message): void
{
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// ── Input validation ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Invalid request method.');
}

$rescheduleId = (int)($_POST['reschedule_id'] ?? 0);
$action       = trim($_POST['action'] ?? '');

if (!$rescheduleId) {
    jsonOut(false, 'Invalid reschedule ID.');
}
if (!in_array($action, ['approve', 'reject'], true)) {
    jsonOut(false, 'Invalid action. Must be "approve" or "reject".');
}

// ── Fetch the pending reschedule row ─────────────────────────────────────────
// Column names confirmed from the INSERT in reschedule_reservation.php:
//   new values stored as: new_date, new_time, console_id, console_type
//   initiated_by = 'user', status = 'pending'
$stmt = $conn->prepare(
    "SELECT rs.reschedule_id, rs.reservation_id, rs.user_id,
            rs.new_date, rs.new_time,
            rs.console_id    AS new_console_id,
            rs.console_type  AS new_console_type,
            rs.old_date, rs.old_time,
            u.email, u.full_name
     FROM reservation_reschedules rs
     JOIN users u ON rs.user_id = u.user_id
     WHERE rs.reschedule_id = ?
       AND rs.status        = 'pending'
       AND rs.initiated_by  = 'user'"
);
$stmt->bind_param('i', $rescheduleId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    jsonOut(false, 'Reschedule request not found or already processed.');
}

$reservationId  = (int)$row['reservation_id'];
$newDate        = $row['new_date'];
$newTime        = $row['new_time'];
$newConsoleId   = !empty($row['new_console_id'])  ? (int)$row['new_console_id']  : null;
$newConsoleType = !empty($row['new_console_type']) ? $row['new_console_type']      : null;

$adminUser = getCurrentUser();
$adminId   = (int)$adminUser['user_id'];

// ── Process ───────────────────────────────────────────────────────────────────
$conn->begin_transaction();

try {
    if ($action === 'approve') {

        // 1. Apply the customer's requested changes to the reservation
        if ($newConsoleId && $newConsoleType) {
            $upd = $conn->prepare(
                "UPDATE reservations
                    SET reserved_date = ?,
                        reserved_time = ?,
                        console_id    = ?,
                        console_type  = ?,
                        status        = 'reserved',
                        updated_at    = NOW()
                  WHERE reservation_id = ?"
            );
            $upd->bind_param('ssisi', $newDate, $newTime, $newConsoleId, $newConsoleType, $reservationId);
        } elseif ($newConsoleId) {
            $upd = $conn->prepare(
                "UPDATE reservations
                    SET reserved_date = ?,
                        reserved_time = ?,
                        console_id    = ?,
                        status        = 'reserved',
                        updated_at    = NOW()
                  WHERE reservation_id = ?"
            );
            $upd->bind_param('ssii', $newDate, $newTime, $newConsoleId, $reservationId);
        } else {
            $upd = $conn->prepare(
                "UPDATE reservations
                    SET reserved_date = ?,
                        reserved_time = ?,
                        status        = 'reserved',
                        updated_at    = NOW()
                  WHERE reservation_id = ?"
            );
            $upd->bind_param('ssi', $newDate, $newTime, $reservationId);
        }
        $upd->execute();
        $upd->close();

        // 2. Mark the reschedule log row as approved
        $mark = $conn->prepare(
            "UPDATE reservation_reschedules
                SET status         = 'approved',
                    rescheduled_by = ?
              WHERE reschedule_id  = ?"
        );
        $mark->bind_param('ii', $adminId, $rescheduleId);
        $mark->execute();
        $mark->close();

        $conn->commit();

        // 3. Notify the customer by email (same helper used elsewhere)
        try {
            sendRescheduleNotificationEmail(
                $row['email'],
                trim($row['full_name']),
                $newDate,
                $newTime
            );
        } catch (Exception $mailEx) {
            // Email failure should not block the response
        }

        jsonOut(true,
            'Reschedule approved. Reservation #' . $reservationId .
            ' updated to ' . date('M d, Y', strtotime($newDate)) .
            ' at ' . date('h:i A', strtotime($newTime)) . '.'
        );

    } else {
        // action === 'reject' — reservation stays unchanged

        $mark = $conn->prepare(
            "UPDATE reservation_reschedules
                SET status         = 'rejected',
                    rescheduled_by = ?
              WHERE reschedule_id  = ?"
        );
        $mark->bind_param('ii', $adminId, $rescheduleId);
        $mark->execute();
        $mark->close();

        $conn->commit();

        jsonOut(true,
            'Reschedule request rejected. Reservation #' . $reservationId . ' remains unchanged.'
        );
    }

} catch (Exception $e) {
    $conn->rollback();
    jsonOut(false, 'Database error: ' . $e->getMessage());
}