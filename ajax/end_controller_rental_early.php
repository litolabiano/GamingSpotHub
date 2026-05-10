<?php
/**
 * ajax/end_controller_rental_early.php
 * Staff ends ALL active controller rentals for a session early with a confirmed final fee.
 * Ends every non-ended controller_rental row for the session simultaneously.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Auth guard
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired — please log in again.']);
    exit;
}
if (!in_array($_SESSION['role'] ?? '', ['shopkeeper', 'owner'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh the page.']);
    exit;
}

$session_id = (int)($_POST['session_id'] ?? 0);
$final_fee  = (float)($_POST['final_fee'] ?? 0);

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID.']);
    exit;
}

$user = getCurrentUser();
$uid  = (int)($user['user_id'] ?? 0);

$conn->begin_transaction();
try {
    // 1. Fetch the session's customer user_id
    $sesStmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ? AND status = 'active' LIMIT 1");
    $sesStmt->bind_param('i', $session_id);
    $sesStmt->execute();
    $sessionRow = $sesStmt->get_result()->fetch_assoc();
    if (!$sessionRow) {
        throw new Exception("Active session not found.");
    }
    $customerId = (int)$sessionRow['user_id'];

    // 2. Fetch ALL active (non-ended) controller rental rows for this session
    $stmt = $conn->prepare(
        "SELECT request_id, description, extra_cost, created_at
         FROM additional_requests
         WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'
           AND description NOT LIKE '%[ENDED]%'"
    );
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($requests)) {
        throw new Exception("No active controller rentals found for this session.");
    }

    $endTs       = time();
    $allCtrlIds  = [];
    $logParts    = [];

    foreach ($requests as $request) {
        $rid      = (int)$request['request_id'];
        $desc     = $request['description'];
        $rentedAt = $request['created_at'];

        // Extract controller IDs from this row
        $rowCtrlIds = [];
        if (preg_match('/ID(?:s)?:\s*([\d, ]+)/', $desc, $m)) {
            foreach (explode(',', $m[1]) as $idRaw) {
                $id = (int)trim($idRaw);
                if ($id > 0) {
                    $rowCtrlIds[]  = $id;
                    $allCtrlIds[]  = $id;
                }
            }
        }

        // Calculate elapsed time for this row's controllers
        $startTs     = strtotime($rentedAt);
        $elapsedMins = max(0, (int)floor(($endTs - $startTs) / 60));
        $h           = (int)floor($elapsedMins / 60);
        $m           = $elapsedMins % 60;
        $durStr      = ($h > 0 ? $h . 'h ' : '') . $m . 'm';

        // Mark the row as ended (fee is the pre-confirmed amount stored in extra_cost)
        $newDesc = $desc . " [ENDED] [Final Duration: $durStr]";
        $updReq  = $conn->prepare(
            "UPDATE additional_requests SET description = ?, status = 'completed' WHERE request_id = ?"
        );
        $updReq->bind_param('si', $newDesc, $rid);
        $updReq->execute();

        $logParts[] = 'IDs [' . implode(',', $rowCtrlIds) . '] (' . $durStr . ')';
    }

    // 3. Store confirmed final fee on FIRST row for accounting (extra_cost is the total)
    if (!empty($requests)) {
        $firstRid = (int)$requests[0]['request_id'];
        $feeUpd = $conn->prepare("UPDATE additional_requests SET extra_cost = ? WHERE request_id = ?");
        $feeUpd->bind_param('di', $final_fee, $firstRid);
        $feeUpd->execute();
    }

    // 4. Release ALL controller IDs back to inventory
    if (!empty($allCtrlIds)) {
        $idList = implode(',', array_map('intval', $allCtrlIds));
        $conn->query("UPDATE controllers SET status = 'available' WHERE controller_id IN ($idList)");
    }

    // 5. Record payment in Transaction History (only if fee > 0)
    if ($final_fee > 0) {
        $notes = "Controller Rental Fee — Session #$session_id (Ended: " . implode('; ', $logParts) . ')';
        recordTransaction($session_id, $customerId, $final_fee, 'cash', $uid, null, null, $notes);
    }

    // 6. Audit log
    $logMsg = "Ended ALL controller rentals for Session #$session_id. "
            . implode('; ', $logParts)
            . ". Final Fee: ₱" . number_format($final_fee, 0);
    logActivity($uid, 'End All Controller Rentals', $logMsg);

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'All controller rentals ended. Payment of ₱' . number_format($final_fee, 0) . ' recorded.',
        'ended_count' => count($requests),
        'released_ids' => $allCtrlIds,
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
