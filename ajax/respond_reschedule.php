<?php
/**
 * ajax/respond_reschedule.php
 * User-facing: confirms or cancels an admin-proposed reschedule.
 *
 * POST params:
 *   reschedule_id  (int)    – ID from reservation_reschedules (admin-initiated, pending)
 *   action         (string) – 'confirm' or 'cancel'
 *   chosen_date    (string) – Y-m-d, required when action='confirm' (must be >= admin's proposed date)
 *   chosen_time    (string) – H:i, required when action='confirm'
 */
ob_start();

require_once __DIR__ . '/../includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/mail_helper.php';

ob_clean();
header('Content-Type: application/json');

function jsonOut(bool $ok, string $msg): void {
    ob_clean();
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(false, 'Invalid request method.');

$user         = getCurrentUser();
$user_id      = (int)$user['user_id'];
$rescheduleId = (int)($_POST['reschedule_id'] ?? 0);
$action       = trim($_POST['action'] ?? '');
$chosenDate   = trim($_POST['chosen_date'] ?? '');
$chosenTime   = trim($_POST['chosen_time'] ?? '');

if (!$rescheduleId)                                jsonOut(false, 'Invalid reschedule ID.');
if (!in_array($action, ['confirm', 'cancel'], true)) jsonOut(false, 'Invalid action.');

// Fetch the pending admin-initiated reschedule for THIS user
$stmt = $conn->prepare(
    "SELECT rs.reschedule_id, rs.reservation_id, rs.user_id,
            rs.new_date, rs.new_time,
            rs.console_id   AS new_console_id,
            rs.new_console_type_id,
            ct.type_name    AS new_console_type,
            rs.old_date, rs.old_time,
            u.email, u.full_name
       FROM reservation_reschedules rs
       JOIN users u ON rs.user_id = u.user_id
       LEFT JOIN console_types ct ON rs.new_console_type_id = ct.type_id
      WHERE rs.reschedule_id = ?
        AND rs.user_id       = ?
        AND rs.status        = 'pending'
        AND rs.initiated_by  = 'admin'"
);
$stmt->bind_param('ii', $rescheduleId, $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) jsonOut(false, 'Reschedule proposal not found or already processed.');

$reservationId       = (int)$row['reservation_id'];
$proposedDate        = $row['new_date'];
$proposedTime        = $row['new_time'];
$newConsoleId        = !empty($row['new_console_id'])       ? (int)$row['new_console_id']       : null;
$newConsoleTypeId    = !empty($row['new_console_type_id'])  ? (int)$row['new_console_type_id']  : null;
$newConsoleType      = !empty($row['new_console_type'])     ? $row['new_console_type']           : null;

$conn->begin_transaction();
try {
    if ($action === 'confirm') {
        // Validate user's chosen date/time
        if (!$chosenDate || !$chosenTime) jsonOut(false, 'Please select a date and time to confirm.');

        if ($chosenDate < $proposedDate) {
            jsonOut(false, 'Your chosen date must be on or after the proposed date (' . date('M d, Y', strtotime($proposedDate)) . ').');
        }
        if ($chosenTime < '12:00' || $chosenTime > '23:00') {
            jsonOut(false, 'Time must be between 12:00 PM and 11:00 PM.');
        }

        // Apply user's confirmed date to the reservation
        $finalDate = $chosenDate;
        $finalTime = $chosenTime;

        // ── COUNTER-PROPOSAL CHECK ──────────────────────────────────────────
        // Normalize time strings for comparison (remove seconds if present)
        $normChosenTime   = substr($chosenTime, 0, 5);
        $normProposedTime = substr($proposedTime, 0, 5);

        // If the user changed the date or time from what the admin proposed,
        // it must go back to the admin for review.
        if ($chosenDate !== $proposedDate || $normChosenTime !== $normProposedTime) {
            $userReason = trim($_POST['reason'] ?? '');
            if (!$userReason) {
                jsonOut(false, 'Please provide a reason for changing the proposed schedule.');
            }

            $mark = $conn->prepare(
                "UPDATE reservation_reschedules
                    SET status        = 'pending',
                        new_date      = ?,
                        new_time      = ?,
                        reason        = 'user_request',
                        reason_detail = ?,
                        initiated_by  = 'user',
                        seen_by_user  = 1
                  WHERE reschedule_id = ?"
            );
            $mark->bind_param('sssi', $finalDate, $finalTime, $userReason, $rescheduleId);
            if (!$mark->execute()) {
                throw new Exception('Failed to update reschedule request.');
            }
            $mark->close();

            $conn->commit();
            logActivity($user_id, "Reschedule Counter", "User sent counter-proposal for Reservation #{$reservationId} to {$finalDate} {$finalTime}");
            
            ob_clean();
            echo json_encode([
                'success' => true, 
                'counter_proposal' => true, 
                'message' => 'Your counter-proposal has been sent back to the admin for review.'
            ]);
            exit;
        }

        // ── DIRECT CONFIRMATION ─────────────────────────────────────────────
        // If the user accepted the admin's exact proposal, finalize it.
        $upd = $conn->prepare(
            "UPDATE reservations
                SET reserved_date = ?, reserved_time = ?,
                    console_type_id = IFNULL(?, console_type_id),
                    console_id    = ?,
                    status        = 'reserved', updated_at = NOW()
              WHERE reservation_id = ?"
        );
        $upd->bind_param('ssiii', $finalDate, $finalTime, $newConsoleTypeId, $newConsoleId, $reservationId);
        $upd->execute();
        $upd->close();

        // Mark the reschedule log as approved, store the user's final chosen date
        $mark = $conn->prepare(
            "UPDATE reservation_reschedules
                SET status       = 'approved',
                    new_date     = ?,
                    new_time     = ?,
                    seen_by_user = 1
              WHERE reschedule_id = ?"
        );
        $mark->bind_param('ssi', $finalDate, $finalTime, $rescheduleId);
        $mark->execute();
        $mark->close();

        $conn->commit();
        logActivity($user_id, "Reschedule Confirm", "User confirmed reschedule for Reservation #{$reservationId} to {$finalDate} {$finalTime}");

        jsonOut(true,
            'Your reservation #' . $reservationId . ' has been confirmed for '
            . date('M d, Y', strtotime($finalDate))
            . ' at ' . date('g:i A', strtotime($finalTime)) . '. See you then!'
        );

    } else {
        // action === 'cancel' — user declines, mark seen and leave reservation as-is
        $mark = $conn->prepare(
            "UPDATE reservation_reschedules
                SET status       = 'rejected',
                    seen_by_user = 1
              WHERE reschedule_id = ?"
        );
        $mark->bind_param('i', $rescheduleId);
        $mark->execute();
        $mark->close();

        // Revert reservation status to 'reserved' since user declined the change
        $upd = $conn->prepare("UPDATE reservations SET status = 'reserved', updated_at = NOW() WHERE reservation_id = ?");
        $upd->bind_param('i', $reservationId);
        $upd->execute();
        $upd->close();

        $conn->commit();
        logActivity($user_id, "Reschedule Decline", "User declined reschedule proposal for Reservation #{$reservationId}");

        jsonOut(true, 'You have declined the reschedule. Your reservation remains on its original date. Please contact the shop if you have questions.');
    }

} catch (Exception $e) {
    $conn->rollback();
    jsonOut(false, 'Database error: ' . $e->getMessage());
}