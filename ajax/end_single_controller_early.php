<?php
/**
 * Staff: end a single controller's rental early — prorate its cost to elapsed
 * session time, split multi-controller rows, release the hardware unit.
 *
 * POST params:
 *   session_id     int
 *   controller_id  int
 *   csrf_token     string
 *
 * Returns JSON:
 *   { success, message, refund_amount, prorated_cost, original_cost, elapsed_minutes, controller_label }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['shopkeeper', 'owner'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh the page.']);
    exit;
}

$session_id    = (int) ($_POST['session_id']    ?? 0);
$controller_id = (int) ($_POST['controller_id'] ?? 0);

if ($session_id <= 0 || $controller_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id or controller_id.']);
    exit;
}

$user = getCurrentUser();
$uid  = (int) ($user['user_id'] ?? 0);
if ($uid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$result = endSingleControllerEarly($session_id, $controller_id, $uid);
echo json_encode($result);
