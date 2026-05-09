<?php
/**
 * Staff: end controller rental early — re-rate additional_requests to elapsed time, release controllers.
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

$session_id = (int) ($_POST['session_id'] ?? 0);
if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing session.']);
    exit;
}

$user = getCurrentUser();
$uid  = (int) ($user['user_id'] ?? 0);
if ($uid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$result = endControllerRentalEarly($session_id, $uid);
echo json_encode($result);
