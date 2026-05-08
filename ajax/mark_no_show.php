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
        SELECT r.status, r.console_id, r.user_id, r.downpayment_amount, 
               u.full_name AS customer_name, c.unit_number
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.user_id 
        LEFT JOIN consoles c ON r.console_id = c.console_id
        WHERE r.reservation_id = ?
    ");
    $stmt->bind_param("i", $res_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) {
        throw new Exception('Reservation not found.');
    }

    $customer_name = $res['customer_name'] ?? 'Unknown User';
    $console_unit  = $res['unit_number'] ?? 'Unknown Console';
    $forfeited     = (float)$res['downpayment_amount'];

    // 2. Update reservation status
    $stmt = $conn->prepare("UPDATE reservations SET status = 'no_show', updated_at = NOW() WHERE reservation_id = ?");
    $stmt->bind_param("i", $res_id);
    $stmt->execute();

    // 3. Forfeit reservation fee
    if ($forfeited > 0) {
        $oldNote1 = 'Downpayment for reservation #' . $res_id;
        $oldNote2 = 'Downpayment transferred from reservation #' . $res_id;
        $newNote  = 'Forfeited downpayment for No-Show reservation #' . $res_id;

        $updTx = $conn->prepare("
            UPDATE transactions 
            SET payment_note = ? 
            WHERE (payment_note = ? OR payment_note = ?) 
              AND user_id = ? 
            LIMIT 1
        ");
        $updTx->bind_param("sssi", $newNote, $oldNote1, $oldNote2, $res['user_id']);
        $updTx->execute();
    }

    // 4. Handle active session if any (for auto-started reservations)
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

        logActivity(
            $_SESSION['user_id'], 
            "No-Show", 
            "Marked active Session #{$sid} (from Reservation #{$res_id}) as No-Show/Cancelled. Customer: {$customer_name}. Console: {$console_unit}. Forfeited Fee: ₱" . number_format($forfeited, 2)
        );
    } else {
        // Just the reservation
        // Release console if reservation was bound to one (usually only if active session, but just in case)
        if (!empty($res['console_id'])) {
            $stmt = $conn->prepare("UPDATE consoles SET status = 'available' WHERE console_id = ?");
            $stmt->bind_param("i", $res['console_id']);
            $stmt->execute();
        }

        logActivity(
            $_SESSION['user_id'], 
            "No-Show", 
            "Marked Reservation #{$res_id} as No-Show. Customer: {$customer_name}. Console: {$console_unit}. Forfeited Fee: ₱" . number_format($forfeited, 2)
        );
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation marked as No Show. Console released.']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    // Prevent raw SQL error codes from showing to the user
    echo json_encode(['success' => false, 'message' => 'A database error occurred while processing the No-Show action. Please try again or contact support if the issue persists.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
