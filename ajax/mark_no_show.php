<?php
/**
 * ajax/mark_no_show.php
 * Mark a reservation as 'no_show'.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['owner', 'shopkeeper']);

$res_id = (int)($_POST['reservation_id'] ?? 0);

if (!$res_id) {
    echo json_encode(['success' => false, 'message' => 'Reservation ID is required.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Get reservation details
    $stmt = $conn->prepare("
        SELECT r.status, r.console_id, r.user_id, r.walkin_name, u.full_name 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.user_id 
        WHERE r.reservation_id = ?
    ");
    $stmt->bind_param("i", $res_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) {
        throw new Exception('Reservation not found.');
    }

    $customer_name = ($res['user_id'] && $res['user_id'] != WALKIN_USER_ID) ? $res['full_name'] : ($res['walkin_name'] ?: 'Walk-in');

    // 2. Update reservation status
    $stmt = $conn->prepare("UPDATE reservations SET status = 'no_show', updated_at = NOW() WHERE reservation_id = ?");
    $stmt->bind_param("i", $res_id);
    $stmt->execute();

    // 3. Handle active session if any (for auto-started reservations)
    $stmt = $conn->prepare("SELECT session_id, console_id FROM gaming_sessions WHERE source_reservation_id = ? AND status = 'active'");
    $stmt->bind_param("i", $res_id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();

    if ($session) {
        $sid = $session['session_id'];
        $cid = $session['console_id'];
        
        // Mark session as cancelled (no show)
        $stmt = $conn->prepare("UPDATE gaming_sessions SET status = 'cancelled', end_time = NOW() WHERE session_id = ?");
        $stmt->bind_param("i", $sid);
        $stmt->execute();

        // Release console
        $stmt = $conn->prepare("UPDATE consoles SET status = 'available' WHERE console_id = ?");
        $stmt->bind_param("i", $cid);
        $stmt->execute();

        logActivity($_SESSION['user_id'], "No-Show", "Marked active Session #{$sid} as No-Show/Cancelled for {$customer_name}.");
    }

    logActivity($_SESSION['user_id'], "No-Show", "Marked Reservation #{$res_id} as No-Show for {$customer_name}.");

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation marked as No Show. Console released.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
