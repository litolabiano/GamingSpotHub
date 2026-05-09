<?php
require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$participant_id = (int)($_POST['participant_id'] ?? 0);
$status         = $_POST['payment_status'] ?? '';
$csrf_token     = $_POST['csrf_token'] ?? '';

if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please refresh the page.']);
    exit;
}

if (!$participant_id || !in_array($status, ['pending', 'paid'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// 1. Fetch participant & tournament details for logging
$infoStmt = $conn->prepare("
    SELECT tp.ign, t.tournament_id, t.tournament_name, u.full_name, tp.walkin_name
    FROM tournament_participants tp
    JOIN tournaments t ON tp.tournament_id = t.tournament_id
    LEFT JOIN users u ON tp.user_id = u.user_id
    WHERE tp.participant_id = ?
");
$infoStmt->bind_param('i', $participant_id);
$infoStmt->execute();
$pData = $infoStmt->get_result()->fetch_assoc();

if (!$pData) {
    echo json_encode(['success' => false, 'message' => 'Participant not found.']);
    exit;
}

$pName = $pData['walkin_name'] ?: ($pData['full_name'] ?: $pData['ign']);
$tName = $pData['tournament_name'];
$tId   = $pData['tournament_id'];

// 2. Perform update
$stmt = $conn->prepare("UPDATE tournament_participants SET payment_status = ? WHERE participant_id = ?");
$stmt->bind_param('si', $status, $participant_id);

if ($stmt->execute()) {
    // 3. Log activity
    $logAction = "Tournament Payment Updated";
    $logDetails = "Updated payment status to " . strtoupper($status) . " for Participant $pName in Tournament: $tName (#$tId)";
    
    $currentUser = getCurrentUser();
    logActivity($currentUser['user_id'], $logAction, $logDetails);

    echo json_encode(['success' => true, 'message' => 'Payment status updated to ' . $status . '.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
