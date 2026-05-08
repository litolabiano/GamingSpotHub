<?php
/**
 * ajax/check_start_conflict.php
 * ──────────────────────────────────────────────────────────────────
 * Checks if a selected console unit has an upcoming reservation.
 * Returns the minutes remaining until that reservation, so the frontend
 * can cap the maximum selectable duration for a new session.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
    echo json_encode(['conflict' => false, 'message' => 'Unauthorized.']);
    exit;
}

$console_id = (int)($_GET['console_id'] ?? 0);

if (!$console_id) {
    echo json_encode(['conflict' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$tz  = new DateTimeZone('Asia/Manila');
$now = new DateTime('now', $tz);
$nowStr = $now->format('Y-m-d H:i:s');

// Find the very next reservation for this console
$resStmt = $conn->prepare(
    "SELECT r.reservation_id, r.reserved_date, r.reserved_time,
            u.full_name AS customer_name
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.console_id = ?
        AND r.status IN ('reserved', 'pending')
        AND CONCAT(r.reserved_date, ' ', r.reserved_time) > ?
      ORDER BY r.reserved_date ASC, r.reserved_time ASC
      LIMIT 1"
);
$resStmt->bind_param('is', $console_id, $nowStr);
$resStmt->execute();
$nextReservation = $resStmt->get_result()->fetch_assoc();

if ($nextReservation) {
    $resDt = new DateTime(
        $nextReservation['reserved_date'] . ' ' . $nextReservation['reserved_time'],
        $tz
    );
    $diff = $now->diff($resDt);
    // Convert diff to absolute minutes
    $mins = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
    $timeStr = date('g:i A', strtotime($nextReservation['reserved_date'] . ' ' . $nextReservation['reserved_time']));
    
    echo json_encode([
        'has_reservation' => true,
        'minutes_away'    => $mins,
        'message'         => "Upcoming reservation for " . htmlspecialchars($nextReservation['customer_name']) . " at " . $timeStr . " (" . $mins . " mins away)."
    ]);
} else {
    echo json_encode([
        'has_reservation' => false,
        'minutes_away'    => null,
    ]);
}
