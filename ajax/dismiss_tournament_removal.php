<?php
require_once __DIR__ . '/../includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$participant_id = (int)($_POST['participant_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$participant_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid participant ID.']);
    exit;
}

$stmt = $conn->prepare("UPDATE tournament_participants SET seen_by_user = 1 WHERE participant_id = ? AND user_id = ?");
$stmt->bind_param('ii', $participant_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
