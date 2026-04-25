<?php
/**
 * ajax/request_extension.php
 * Customer requests more time on their active session.
 *
 * POST params:
 *   session_id     int
 *   extra_minutes  int  (30 | 60 | 90 | 120)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

requireLogin();

$session_id    = (int)($_POST['session_id']    ?? 0);
$extra_minutes = (int)($_POST['extra_minutes'] ?? 0);

if (!$session_id || $extra_minutes <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$result  = requestExtension($session_id, $user_id, $extra_minutes);

echo json_encode($result);
