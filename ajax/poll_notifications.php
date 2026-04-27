<?php
/**
 * ajax/poll_notifications.php — Admin Notification Poller
 * ─────────────────────────────────────────────────────────
 * Returns new pending reservations created since `last_id`.
 * Called by the admin dashboard JS every 30 seconds.
 *
 * GET params:
 *   last_id  int   The highest reservation_id the client has already seen.
 *
 * Returns JSON:
 *   { new_count, max_id, items[] }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

requireRole(['owner', 'shopkeeper']);

$lastId = (int)($_GET['last_id'] ?? 0);

// Fetch new pending reservations since last_id
$stmt = $conn->prepare(
    "SELECT r.reservation_id, u.full_name AS customer_name,
            r.console_type, r.rental_mode,
            r.reserved_date, r.reserved_time
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.reservation_id > ? AND r.status = 'pending'
      ORDER BY r.reservation_id ASC
      LIMIT 10"
);
$stmt->bind_param('i', $lastId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Current highest reservation_id (client stores this as its new baseline)
$maxRow = $conn->query("SELECT COALESCE(MAX(reservation_id), 0) AS max_id FROM reservations");
$maxId  = (int)$maxRow->fetch_assoc()['max_id'];

echo json_encode([
    'new_count' => count($items),
    'max_id'    => $maxId,
    'items'     => $items,
]);
