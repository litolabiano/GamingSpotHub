<?php
/**
 * GamingSpotHub – AJAX: Change user password
 */
require_once __DIR__ . '/../includes/session_helper.php';
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$user_id        = (int)$_SESSION['user_id'];
$current_pass   = $_POST['current_password'] ?? '';
$new_pass       = $_POST['new_password']     ?? '';
$confirm_pass   = $_POST['confirm_password'] ?? '';

if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
    $response['message'] = 'Please fill in all password fields.';
    echo json_encode($response);
    exit;
}

if (strlen($new_pass) < 8) {
    $response['message'] = 'New password must be at least 8 characters long.';
    echo json_encode($response);
    exit;
}

if ($new_pass !== $confirm_pass) {
    $response['message'] = 'New passwords do not match.';
    echo json_encode($response);
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

// 1. Verify current password
$stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($current_pass, $row['password_hash'])) {
    $response['message'] = 'Incorrect current password.';
    echo json_encode($response);
    exit;
}

// 2. Hash and update
$new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$upd->bind_param('si', $new_hash, $user_id);

if ($upd->execute()) {
    $response['success'] = true;
    $response['message'] = 'Password updated successfully.';
} else {
    $response['message'] = 'Database error. Please try again.';
}

echo json_encode($response);
