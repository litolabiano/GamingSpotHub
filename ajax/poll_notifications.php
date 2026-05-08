<?php
/**
 * ajax/poll_notifications.php — Admin Notification Poller
 * ─────────────────────────────────────────────────────────
 * Returns new activities since `last_time`.
 * Called by the admin dashboard JS every 8 seconds.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

requireRole(['owner', 'shopkeeper']);

$now = time();
$lastTime = (int)($_GET['last_time'] ?? $now);
$lastDate = date('Y-m-d H:i:s', $lastTime);

// 1. New Reservations (created by user)
$stmt1 = $conn->prepare("
    SELECT r.reservation_id AS id, 'new_reservation' AS event_type,
           u.full_name AS customer_name, r.console_type, r.rental_mode,
           r.reserved_date, r.reserved_time, r.created_at AS event_time
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.created_at > ? AND r.created_by = r.user_id
");
$stmt1->bind_param('s', $lastDate);
$stmt1->execute();
$resNew = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Reschedules (requested by user)
$stmt2 = $conn->prepare("
    SELECT rs.reschedule_id AS id, 'reschedule_request' AS event_type,
           u.full_name AS customer_name, r.console_type, r.rental_mode,
           rs.new_date AS reserved_date, rs.new_time AS reserved_time,
           rs.created_at AS event_time
    FROM reservation_reschedules rs
    JOIN users u ON rs.user_id = u.user_id
    JOIN reservations r ON rs.reservation_id = r.reservation_id
    WHERE rs.created_at > ? AND rs.initiated_by = 'user'
");
$stmt2->bind_param('s', $lastDate);
$stmt2->execute();
$resResched = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Cancellations (cancelled by user)
$stmt3 = $conn->prepare("
    SELECT c.cancel_id AS id, 'cancellation' AS event_type,
           u.full_name AS customer_name, r.console_type, r.rental_mode,
           r.reserved_date, r.reserved_time,
           c.cancelled_at AS event_time
    FROM reservation_cancellations c
    JOIN users u ON c.user_id = u.user_id
    JOIN reservations r ON c.reservation_id = r.reservation_id
    WHERE c.cancelled_at > ? AND c.cancelled_by = 'user'
");
$stmt3->bind_param('s', $lastDate);
$stmt3->execute();
$resCancel = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

$items = array_merge($resNew, $resResched, $resCancel);

usort($items, function($a, $b) {
    return strtotime($a['event_time']) <=> strtotime($b['event_time']);
});

echo json_encode([
    'new_count' => count($items),
    'max_time'  => $now,
    'items'     => array_slice($items, 0, 15),
]);
