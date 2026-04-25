<?php
/**
 * ajax/approve_extension.php
 * Staff approves or denies a pending customer extension request.
 *
 * GET params (for modal load):
 *   get_pending=1&session_id=X  → returns pending extensions for that session
 *
 * POST params:
 *   action         string  'approve' | 'deny'
 *   extension_id   int
 *   payment_method string  (required for approve: cash | gcash | credit_card)
 *   tendered       float   (optional — for approve)
 *   note           string  (optional — for deny)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

requireRole(['shopkeeper', 'owner']);

// ── GET: return pending requests for a session ────────────────────────────
if (isset($_GET['get_pending'])) {
    $sid = (int)($_GET['session_id'] ?? 0);
    if (!$sid) { echo json_encode(['pending' => []]); exit; }

    global $conn;
    $stmt = $conn->prepare(
        "SELECT se.extension_id, se.extra_minutes, se.extra_cost,
                u.full_name AS requested_by_name
           FROM session_extensions se
           JOIN users u ON se.requested_by = u.user_id
          WHERE se.session_id = ? AND se.status = 'pending'
          ORDER BY se.requested_at ASC"
    );
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['pending' => $rows]);
    exit;
}

// ── POST: approve or deny ─────────────────────────────────────────────────
$action       = trim($_POST['action']        ?? '');
$extension_id = (int)($_POST['extension_id'] ?? 0);
$staff_id     = $_SESSION['user_id'];

if (!$extension_id || !in_array($action, ['approve', 'deny'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

if ($action === 'approve') {
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    $tendered       = isset($_POST['tendered']) ? (float)$_POST['tendered'] : null;
    $result = approveExtension($extension_id, $staff_id, $payment_method, $tendered);
} else {
    $note   = trim($_POST['note'] ?? 'Denied by staff');
    $result = denyExtension($extension_id, $staff_id, $note);
}

echo json_encode($result);
