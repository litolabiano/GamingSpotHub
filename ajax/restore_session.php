<?php
/**
 * ajax/restore_session.php
 * Reverts a completed session back to 'active' status if within the 5-minute window.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['owner', 'shopkeeper']);

$user = getCurrentUser();
$staff_id = (int)$user['user_id'];
$session_id = (int)($_POST['session_id'] ?? 0);

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Session ID is required.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Get session details
    $stmt = $conn->prepare("SELECT * FROM gaming_sessions WHERE session_id = ?");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $sess = $stmt->get_result()->fetch_assoc();

    if (!$sess) throw new Exception('Session not found.');
    if ($sess['status'] !== 'completed') throw new Exception('Only completed sessions can be restored.');

    // 2. Validate 5-minute window
    $endTs = strtotime($sess['end_time']);
    $nowTs = time();
    $elapsedSec = $nowTs - $endTs;

    if ($elapsedSec > 300) {
        throw new Exception('Restore window (5 minutes) has expired.');
    }

    // 3. Check if console is available
    $cid = (int)$sess['console_id'];
    $stmt = $conn->prepare("SELECT status, unit_number FROM consoles WHERE console_id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $console = $stmt->get_result()->fetch_assoc();

    if (!$console) throw new Exception('Associated console not found.');
    if ($console['status'] !== 'available') {
        throw new Exception("Cannot restore: Console {$console['unit_number']} is currently {$console['status']}.");
    }

    // 4. Perform Restore
    // Update session: status='active', end_time=NULL, total_cost=NULL, duration_minutes=NULL
    $upd = $conn->prepare("UPDATE gaming_sessions SET status = 'active', end_time = NULL, total_cost = NULL, duration_minutes = NULL WHERE session_id = ?");
    $upd->bind_param("i", $session_id);
    $upd->execute();

    // Update console: status='in_use'
    $updC = $conn->prepare("UPDATE consoles SET status = 'in_use' WHERE console_id = ?");
    $updC->bind_param("i", $cid);
    $updC->execute();

    // 5. Audit Log
    $action = "Restore Session";
    $details = "Restored session #{$session_id} for " . ($sess['is_walkin'] ? 'Walk-in' : "User #{$sess['user_id']}") . ". "
             . "Time elapsed since end: " . floor($elapsedSec / 60) . "m " . ($elapsedSec % 60) . "s. "
             . "Console: {$console['unit_number']}. "
             . "Original End Time: {$sess['end_time']}.";
    
    $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $log->bind_param("iss", $staff_id, $action, $details);
    $log->execute();

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Session successfully restored. Timer continues from current time.',
        'elapsed_formatted' => floor($elapsedSec / 60) . "m " . ($elapsedSec % 60) . "s"
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
