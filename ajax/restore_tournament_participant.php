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

// 1. Fetch participant & tournament details for logging & notification
$infoStmt = $conn->prepare("
    SELECT tp.ign, t.tournament_id, t.tournament_name, u.full_name, u.user_id, tp.walkin_name
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
$targetUserId = (int)$pData['user_id'];

// 2. Perform Restore (update status back to 'active')
$stmt = $conn->prepare("UPDATE tournament_participants SET status = 'active', removed_at = NULL WHERE participant_id = ?");
$stmt->bind_param('i', $participant_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $timestamp = date('M d, Y h:i A');
        $logDetails = "Restored Participant $pName to Tournament: $tName (#$tId) at $timestamp";
        
        $currentUser = getCurrentUser();
        logActivity($currentUser['user_id'], "Tournament Participant Restored", $logDetails);

        // 3. Notify the user if it's not a walk-in
        if ($targetUserId > 0) {
            $notifMsg = "Your entry for the tournament '$tName' has been restored by the admin.";
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'tournament_restore', ?)");
            $notifStmt->bind_param('is', $targetUserId, $notifMsg);
            $notifStmt->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Participant restored successfully. Entry is now active again.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No record updated. It might have been already active.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
