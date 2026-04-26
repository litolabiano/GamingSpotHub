<?php
/**
 * ajax/refund.php  — Centralized Refund Handler
 * ─────────────────────────────────────────────
 * Handles ALL refund scenarios:
 *   1. Standard session refund        (session_id, action_type=standard)
 *   2. Early-end session refund       (session_id, action_type=early_end)
 *   3. Reservation downpayment refund (reservation_id, action_type=reservation)
 *
 * POST params:
 *   session_id      int     Required for session refunds (0 for reservation refunds)
 *   reservation_id  int     Required for reservation refunds (0 for session refunds)
 *   refund_amount   float   Amount to refund (> 0)
 *   refund_reason   string  Optional note stored in transaction
 *   action_type     string  'standard' | 'early_end' | 'reservation'
 *
 * Returns JSON:
 *   { success: true,  message, refunded, max_refundable }
 *   { success: false, message }
 *
 * Security:
 *   - Requires shopkeeper or owner role.
 *   - Session refunds are capped at (payments - prior refunds).
 *   - Reservation refunds are capped at the downpayment_amount.
 *   - Reservation rows are marked refund_issued = 1 after success.
 *   - For early_end, endSession() is called FIRST (atomic).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

requireRole(['shopkeeper', 'owner']);

$session_id     = (int)  ($_POST['session_id']     ?? 0);
$reservation_id = (int)  ($_POST['reservation_id'] ?? 0);
$refund_amount  = (float) ($_POST['refund_amount']  ?? 0);
$refund_reason  = trim(   $_POST['refund_reason']   ?? '');
$action_type    = trim(   $_POST['action_type']     ?? 'standard');
$staff_id       = (int)   $_SESSION['user_id'];

// ── Basic validation ──────────────────────────────────────────────────────────
if (!in_array($action_type, ['standard', 'early_end', 'reservation'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action_type.']);
    exit;
}
if ($refund_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Refund amount must be greater than ₱0.']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// PATH A: RESERVATION REFUND
// ══════════════════════════════════════════════════════════════════════════════
if ($action_type === 'reservation') {
    if (!$reservation_id) {
        echo json_encode(['success' => false, 'message' => 'Missing reservation_id.']);
        exit;
    }

    // Fetch the reservation
    $stmt = $conn->prepare(
        "SELECT r.reservation_id, r.user_id, r.downpayment_amount, r.refund_issued, u.user_id AS owner_id
           FROM reservations r
           JOIN users u ON r.user_id = u.user_id
          WHERE r.reservation_id = ? AND r.status = 'cancelled'"
    );
    $stmt->bind_param('i', $reservation_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Cancelled reservation not found.']);
        exit;
    }
    if ((int)$res['refund_issued'] === 1) {
        echo json_encode(['success' => false, 'message' => 'Refund has already been issued for this reservation.']);
        exit;
    }

    $max_refundable = (float)$res['downpayment_amount'];
    if ($max_refundable <= 0) {
        echo json_encode(['success' => false, 'message' => 'No downpayment on record — nothing to refund.']);
        exit;
    }
    if ($refund_amount > $max_refundable) {
        echo json_encode([
            'success'        => false,
            'message'        => 'Refund of ₱' . number_format($refund_amount, 2)
                              . ' exceeds downpayment of ₱' . number_format($max_refundable, 2) . '.',
            'max_refundable' => $max_refundable,
        ]);
        exit;
    }

    // Clamp to max
    $refund_amount = min($refund_amount, $max_refundable);

    // Record negative transaction (session_id = NULL for reservation-only refunds)
    $note = 'Reservation #' . $reservation_id . ' cancellation refund'
          . ($refund_reason ? ': ' . $refund_reason : '');

    recordTransaction(
        null,                  // session_id — nullable for reservation refunds
        $res['user_id'],
        -abs($refund_amount),
        'refund',
        $staff_id,
        null,
        null,
        $note
    );

    // Mark reservation as refunded
    $upd = $conn->prepare("UPDATE reservations SET refund_issued = 1 WHERE reservation_id = ?");
    $upd->bind_param('i', $reservation_id);
    $upd->execute();

    echo json_encode([
        'success'        => true,
        'message'        => 'Reservation refund of ₱' . number_format($refund_amount, 2) . ' issued successfully.',
        'refunded'       => $refund_amount,
        'max_refundable' => $max_refundable,
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// PATH B: SESSION REFUND (standard or early_end)
// ══════════════════════════════════════════════════════════════════════════════
if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id.']);
    exit;
}

// Fetch session
$stmt = $conn->prepare("SELECT user_id, status FROM gaming_sessions WHERE session_id = ?");
$stmt->bind_param('i', $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Session not found.']);
    exit;
}
if ($action_type === 'early_end' && $session['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Session is not active — cannot early-end.']);
    exit;
}

// Compute max refundable (paid - already refunded)
$capStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) AS paid,
        COALESCE(SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END), 0) AS refunded
     FROM transactions WHERE session_id = ?"
);
$capStmt->bind_param('i', $session_id);
$capStmt->execute();
$cap            = $capStmt->get_result()->fetch_assoc();
$max_refundable = max(0, (float)$cap['paid'] - (float)$cap['refunded']);

if ($refund_amount > $max_refundable) {
    echo json_encode([
        'success'        => false,
        'message'        => 'Refund of ₱' . number_format($refund_amount, 2)
                          . ' exceeds maximum refundable of ₱'
                          . number_format($max_refundable, 2) . '.',
        'max_refundable' => $max_refundable,
    ]);
    exit;
}

// Early-end: close session FIRST, then re-clamp
if ($action_type === 'early_end') {
    $endResult = endSession($session_id);
    if (!$endResult['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not end session: ' . $endResult['message'],
        ]);
        exit;
    }
    // Re-clamp after billing is finalised
    $capStmt->execute();
    $cap            = $capStmt->get_result()->fetch_assoc();
    $max_refundable = max(0, (float)$cap['paid'] - (float)$cap['refunded']);
    $refund_amount  = min($refund_amount, $max_refundable);
}

// Record refund transaction
if ($refund_amount > 0) {
    $note = ($action_type === 'early_end' ? 'Early end – refund for unused time' : 'Refund issued')
          . ($refund_reason ? ': ' . $refund_reason : '');

    recordTransaction(
        $session_id,
        $session['user_id'],
        -abs($refund_amount),
        'refund',
        $staff_id,
        null,
        null,
        $note
    );
}

$msg = $action_type === 'early_end'
    ? 'Session ended. Refund of ₱' . number_format($refund_amount, 2) . ' issued.'
    : 'Refund of ₱' . number_format($refund_amount, 2) . ' issued successfully.';

if ($refund_reason) {
    $msg .= ' Reason: ' . htmlspecialchars($refund_reason);
}

echo json_encode([
    'success'        => true,
    'message'        => $msg,
    'refunded'       => $refund_amount,
    'max_refundable' => $max_refundable,
]);
