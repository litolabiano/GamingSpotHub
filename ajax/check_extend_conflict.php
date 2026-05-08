<?php
/**
 * ajax/check_extend_conflict.php — Reservation Conflict Checker for Session Extension
 * ─────────────────────────────────────────────────────────────────────────────────────
 * Checks if extending a given active session by N minutes would overlap with an
 * upcoming reservation for the same console unit.
 *
 * GET params:
 *   session_id     int   — the active session being extended
 *   extra_minutes  int   — how many minutes the admin wants to add
 *
 * Returns JSON:
 *   { conflict: bool, message: string, next_reservation: { ... } | null }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
    echo json_encode(['conflict' => false, 'message' => 'Unauthorized.']);
    exit;
}

$session_id    = (int)($_GET['session_id']    ?? 0);
$extra_minutes = (int)($_GET['extra_minutes'] ?? 0);

if (!$session_id || $extra_minutes <= 0) {
    echo json_encode(['conflict' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// ── Load the active session's console and current projected end time ──────────
$sessStmt = $conn->prepare(
    "SELECT gs.session_id, gs.console_id, gs.start_time, gs.rental_mode, gs.planned_minutes,
            c.unit_number, c.console_type
       FROM gaming_sessions gs
       JOIN consoles c ON gs.console_id = c.console_id
      WHERE gs.session_id = ? AND gs.status = 'active'"
);
$sessStmt->bind_param('i', $session_id);
$sessStmt->execute();
$session = $sessStmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['conflict' => false, 'message' => 'Session not found.']);
    exit;
}

$tz       = new DateTimeZone('Asia/Manila');
$now      = new DateTime('now', $tz);
$startDt  = new DateTime($session['start_time'], $tz);

// Calculate the projected end time AFTER the extension
if ($session['rental_mode'] === 'hourly' && $session['planned_minutes']) {
    // Hourly: current end = start + planned_minutes; new end = start + planned + extra
    $projectedEndDt = clone $startDt;
    $projectedEndDt->modify('+' . ($session['planned_minutes'] + $extra_minutes) . ' minutes');
} else {
    // Open time: current "end" is now; new end = now + extra_minutes
    $projectedEndDt = clone $now;
    $projectedEndDt->modify('+' . $extra_minutes . ' minutes');
}

$projectedEndStr = $projectedEndDt->format('Y-m-d H:i:s');
$nowStr          = $now->format('Y-m-d H:i:s');

// ── Check for upcoming reservations on the same console within the extended window ──
// A conflict exists if a reservation starts before the new projected end time
// AND the reservation is after NOW (it's still upcoming)
$resStmt = $conn->prepare(
    "SELECT r.reservation_id, r.reserved_date, r.reserved_time,
            r.rental_mode, r.planned_minutes,
            u.full_name AS customer_name
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.console_id = ?
        AND r.status IN ('reserved', 'pending')
        AND CONCAT(r.reserved_date, ' ', r.reserved_time) > ?
        AND CONCAT(r.reserved_date, ' ', r.reserved_time) < ?
      ORDER BY r.reserved_date ASC, r.reserved_time ASC
      LIMIT 1"
);
$resStmt->bind_param('iss', $session['console_id'], $nowStr, $projectedEndStr);
$resStmt->execute();
$nextReservation = $resStmt->get_result()->fetch_assoc();

if ($nextReservation) {
    $resDt = new DateTime(
        $nextReservation['reserved_date'] . ' ' . $nextReservation['reserved_time'],
        $tz
    );
    $diff = $now->diff($resDt);
    $mins = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
    $timeStr = date('g:i A', strtotime($nextReservation['reserved_date'] . ' ' . $nextReservation['reserved_time']));
    $dateStr = date('M d', strtotime($nextReservation['reserved_date']));

    echo json_encode([
        'conflict'          => true,
        'message'           => 'Cannot extend by ' . $extra_minutes . ' min — '
            . htmlspecialchars($nextReservation['customer_name'])
            . ' has a reservation for ' . $session['unit_number']
            . ' starting at ' . $timeStr . ' on ' . $dateStr . '.',
        'next_reservation'  => [
            'reservation_id'  => $nextReservation['reservation_id'],
            'customer_name'   => $nextReservation['customer_name'],
            'reserved_at'     => $dateStr . ' ' . $timeStr,
            'minutes_away'    => $mins,
        ],
    ]);
} else {
    echo json_encode([
        'conflict'         => false,
        'message'          => 'No conflicts — extension is safe.',
        'next_reservation' => null,
    ]);
}
