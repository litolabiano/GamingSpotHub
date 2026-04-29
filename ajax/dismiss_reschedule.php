<?php
/**
 * AJAX — Mark a reschedule notification as seen by the user
 * POST: reschedule_id
 */
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

requireLogin();
$user         = getCurrentUser();
$reschedule_id = (int)($_POST['reschedule_id'] ?? 0);

if (!$reschedule_id) {
    echo json_encode(['success' => false, 'message' => 'Missing reschedule_id.']);
    exit;
}

// Only allow the user who owns the reschedule to dismiss it
$stmt = $conn->prepare(
    "UPDATE reservation_reschedules
        SET seen_by_user = 1
      WHERE reschedule_id = ? AND user_id = ?"
);
$stmt->bind_param('ii', $reschedule_id, $user['user_id']);
$stmt->execute();

echo json_encode(['success' => true]);
