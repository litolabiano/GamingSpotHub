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
$csrf_token     = $_POST['csrf_token'] ?? '';

if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please refresh the page.']);
    exit;
}

if (!$participant_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid participant ID.']);
    exit;
}

// 1. Fetch participant & tournament details for logging
$infoStmt = $conn->prepare("
    SELECT tp.ign, tp.status, t.tournament_id, t.tournament_name, u.full_name, tp.walkin_name
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

if ($pData['status'] !== 'archived') {
    echo json_encode(['success' => false, 'message' => 'Participant must be archived before permanent deletion.']);
    exit;
}

$pName = $pData['walkin_name'] ?: ($pData['full_name'] ?: $pData['ign']);
$tName = $pData['tournament_name'];
$tId   = $pData['tournament_id'];

// 2. Perform Permanent Delete
$stmt = $conn->prepare("DELETE FROM tournament_participants WHERE participant_id = ?");
$stmt->bind_param('i', $participant_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $timestamp = date('M d, Y h:i A');
        $logDetails = "Permanently Deleted Participant $pName from Tournament: $tName (#$tId) at $timestamp";
        
        $currentUser = getCurrentUser();
        logActivity($currentUser['user_id'], "Tournament Participant Permanently Deleted", $logDetails);

        echo json_encode(['success' => true, 'message' => 'Participant permanently deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No record deleted.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
