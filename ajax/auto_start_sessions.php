<?php
/**
 * ajax/auto_start_sessions.php — Automatic Reservation-to-Session Converter
 * ─────────────────────────────────────────────────────────────────────────────
 * Polls for reservations whose reserved date+time has been reached and
 * automatically converts them to active gaming sessions.
 *
 * Triggered by:
 *  - Admin panel JS poller (every 60 s while admin is logged in)
 *  - Customer dashboard JS poller (every 60 s while customer is logged in)
 *
 * Business Rules:
 *  - Only converts reservations with status = 'reserved'
 *  - The reserved_date + reserved_time must be ≤ NOW()
 *  - Only looks back 2 hours (stale missed reservations are handled separately)
 *  - If reservation has a pre-assigned console_id, uses that console
 *  - If no console assigned, picks the first available unit of the correct type
 *  - If no console is available at all, logs a skip (no crash)
 *  - Uses the shop owner's user_id as the system shopkeeper for audit logs
 *  - Idempotent: re-running on already-converted reservations is a no-op
 *
 * Returns JSON:
 *  { success, started, skipped, sessions: [...], skip_reasons: [...], errors: [...] }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Require any authenticated user (admin or customer can trigger this)
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'started' => 0]);
    exit;
}

$tz  = new DateTimeZone('Asia/Manila');
$now = new DateTime('now', $tz);

$nowStr     = $now->format('Y-m-d H:i:s');
$cutoffBack = (clone $now)->modify('-2 hours')->format('Y-m-d H:i:s');

// ── Find all due 'reserved' reservations ─────────────────────────────────────
// "due" = reserved_date + reserved_time is in the past (up to 2 hours ago)
$stmt = $conn->prepare(
    "SELECT r.reservation_id, r.user_id, r.console_id, r.console_type_id,
            ct.type_name AS console_type,
            r.rental_mode, r.planned_minutes, r.reserved_date, r.reserved_time,
            r.downpayment_amount
       FROM reservations r
       LEFT JOIN console_types ct ON r.console_type_id = ct.type_id
      WHERE r.status = 'reserved'
        AND CONCAT(r.reserved_date, ' ', r.reserved_time) <= ?
        AND CONCAT(r.reserved_date, ' ', r.reserved_time) >= ?
      ORDER BY r.reserved_date ASC, r.reserved_time ASC"
);
$stmt->bind_param('ss', $nowStr, $cutoffBack);
$stmt->execute();
$dueReservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($dueReservations)) {
    echo json_encode([
        'success' => true,
        'started' => 0,
        'skipped' => 0,
        'message' => 'No reservations due for auto-start.',
    ]);
    exit;
}

// ── Resolve system shopkeeper ID (use shop owner for audit trail) ─────────────
$ownerRow     = $conn->query("SELECT user_id FROM users WHERE role = 'owner' LIMIT 1")?->fetch_assoc();
$systemUserId = $ownerRow ? (int)$ownerRow['user_id'] : (int)$_SESSION['user_id'];

// ── Process each due reservation ─────────────────────────────────────────────
$started = [];
$skipped = [];
$errors  = [];

foreach ($dueReservations as $res) {
    $rid       = (int)$res['reservation_id'];
    $consoleId = (int)($res['console_id'] ?? 0);

    // If no specific console was pre-assigned, find an available one of the right type
    if (!$consoleId) {
        $avail = $conn->prepare(
            "SELECT console_id FROM consoles
              WHERE console_type_id = ? AND status = 'available'
              ORDER BY console_id ASC LIMIT 1"
        );
        $avail->bind_param('i', $res['console_type_id']);
        $avail->execute();
        $row = $avail->get_result()->fetch_assoc();
        if ($row) {
            $consoleId = (int)$row['console_id'];
        }
    } else {
        // Verify the pre-assigned console is still available
        $chk = $conn->prepare("SELECT status FROM consoles WHERE console_id = ?");
        $chk->bind_param('i', $consoleId);
        $chk->execute();
        $chkRow = $chk->get_result()->fetch_assoc();
        if (!$chkRow || $chkRow['status'] !== 'available') {
            // Pre-assigned console not available — try to find another of the same type
            $fallback = $conn->prepare(
                "SELECT console_id FROM consoles
                  WHERE console_type_id = ? AND status = 'available'
                    AND console_id != ?
                  ORDER BY console_id ASC LIMIT 1"
            );
            $fallback->bind_param('ii', $res['console_type_id'], $consoleId);
            $fallback->execute();
            $fbRow = $fallback->get_result()->fetch_assoc();
            $consoleId = $fbRow ? (int)$fbRow['console_id'] : 0;
        }
    }

    if (!$consoleId) {
        $skipped[] = [
            'reservation_id' => $rid,
            'reason'         => 'No available console of type: ' . $res['console_type'],
        ];
        continue;
    }

    // ── Convert the reservation to an active gaming session ───────────────────
    $result = convertReservationToSession($rid, $consoleId, $systemUserId);

    if ($result['success']) {
        $started[] = [
            'reservation_id' => $rid,
            'session_id'     => $result['session_id'],
            'console_id'     => $consoleId,
            'console_type'   => $res['console_type'],
            'user_id'        => $res['user_id'],
        ];
    } else {
        $errors[] = [
            'reservation_id' => $rid,
            'error'          => $result['message'],
        ];
    }
}

echo json_encode([
    'success'      => true,
    'started'      => count($started),
    'skipped'      => count($skipped),
    'errors'       => $errors,
    'sessions'     => $started,
    'skip_reasons' => $skipped,
    'message'      => count($started) > 0
        ? count($started) . ' reservation(s) automatically started as active session(s).'
        : (count($skipped) > 0
            ? 'No sessions started — no available consoles for ' . count($skipped) . ' due reservation(s).'
            : 'No sessions started.'),
]);
