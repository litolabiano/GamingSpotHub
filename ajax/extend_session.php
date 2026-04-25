<?php
/**
 * ajax/extend_session.php
 * Staff directly extends an active session (no approval step).
 *
 * POST params:
 *   session_id     int
 *   extra_minutes  int  (30 | 60 | 90 | 120)
 *   payment_method string (cash | gcash | credit_card)
 *   tendered       float  (optional — amount customer gave)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

requireRole(['shopkeeper', 'owner']);

$session_id     = (int)   ($_POST['session_id']     ?? 0);
$extra_minutes  = (int)   ($_POST['extra_minutes']  ?? 0);
$payment_method = trim(    $_POST['payment_method'] ?? 'cash');
$tendered       = isset($_POST['tendered']) ? (float)$_POST['tendered'] : null;

$allowed_methods = ['cash', 'gcash', 'credit_card'];
if (!$session_id || $extra_minutes <= 0 || !in_array($payment_method, $allowed_methods)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$processed_by = $_SESSION['user_id'];
$result = extendSession($session_id, $extra_minutes, $payment_method, $processed_by, $tendered);

// Surface the bonus so the admin UI can show it in the toast
if ($result['success'] && ($result['bonus_earned'] ?? 0) > 0) {
    $result['message'] = '+' . $extra_minutes . ' min applied'
        . ' (+ ' . $result['bonus_earned'] . ' min free bonus!). Total added: '
        . $result['total_added'] . ' min.';
}

echo json_encode($result);
