<?php
/**
 * GamingSpotHub – AJAX: Update user profile (name + phone)
 * Requires the user to confirm their current password (Gmail account password)
 * before any changes are saved.
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

$user_id        = (int)($_SESSION['user_id'] ?? 0);
$full_name      = trim($_POST['full_name']      ?? '');
$phone          = trim($_POST['phone']          ?? '');
$current_pass   = $_POST['current_password']    ?? '';

// ── Validation ────────────────────────────────────────────────────────────────

if (empty($full_name)) {
    $response['message'] = 'Full name is required.';
    echo json_encode($response);
    exit;
}

// Phone: digits only, 10-11 digits (PH format), REQUIRED
if (empty($phone)) {
    $response['message'] = 'Phone number is required.';
    echo json_encode($response);
    exit;
}

if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
    $response['message'] = 'Please enter a valid Philippine mobile number (e.g., 09123456789 or +639123456789).';
    echo json_encode($response);
    exit;
}

if (empty($current_pass)) {
    $response['message'] = 'Current password is required to save changes.';
    echo json_encode($response);
    exit;
}

// ── Fetch user from DB ────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/db_config.php';

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ? AND status = 'active'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    $response['message'] = 'User not found.';
    echo json_encode($response);
    exit;
}

// ── Verify password ───────────────────────────────────────────────────────────
if (!password_verify($current_pass, $row['password_hash'])) {
    $response['message'] = 'Incorrect password. Changes were not saved.';
    echo json_encode($response);
    exit;
}

// ── Update the profile ────────────────────────────────────────────────────────
$upd = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
$upd->bind_param('ssi', $full_name, $phone, $user_id);

if ($upd->execute()) {
    // Keep session in sync
    $_SESSION['full_name'] = $full_name;

    $response['success']   = true;
    $response['message']   = 'Profile updated successfully.';
    $response['full_name'] = htmlspecialchars($full_name);
    $response['phone']     = htmlspecialchars($phone);
} else {
    $response['message'] = 'Database error. Please try again.';
}

echo json_encode($response);
