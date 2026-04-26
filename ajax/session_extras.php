<?php
/**
 * ajax/session_extras.php
 * Returns the total approved additional_requests cost for a session.
 * Used by the End Session modal to show extras (e.g. controller rental) in the cost estimate.
 *
 * GET params:
 *   session_id   int
 *
 * Returns JSON:
 *   { success: true, extras: 20.00, items: [{ description, extra_cost }] }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

requireRole(['shopkeeper', 'owner']);

$session_id = (int)($_GET['session_id'] ?? 0);
if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT description, extra_cost
     FROM additional_requests
     WHERE session_id = ? AND status = 'approved'
     ORDER BY created_at ASC"
);
$stmt->bind_param('i', $session_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_column($rows, 'extra_cost'));

echo json_encode([
    'success' => true,
    'extras'  => (float)$total,
    'items'   => $rows,
]);
