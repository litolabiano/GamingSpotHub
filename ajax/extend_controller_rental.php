<?php
/**
 * ajax/extend_controller_rental.php
 * Staff extends an active controller rental.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Auth guard
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired — please log in again.']);
    exit;
}
if (!in_array($_SESSION['role'] ?? '', ['shopkeeper', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// CSRF check
$message = ''; $messageType = '';
if (!verifyCsrf($message, $messageType)) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$session_id        = (int)($_POST['session_id']        ?? 0);
$additional_minutes = (int)($_POST['additional_minutes'] ?? 0);

if (!$session_id || $additional_minutes <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// ── Duplicate Request Prevention ──────────────────────────────────────────
// Prevent multiple submissions of the same request within a 5-second window.
$request_key = "ext_ctrl_{$session_id}_{$additional_minutes}";
if (isset($_SESSION['last_ext_ctrl_req']) && $_SESSION['last_ext_ctrl_req'] === $request_key) {
    $last_time = $_SESSION['last_ext_ctrl_time'] ?? 0;
    if (time() - $last_time < 5) {
        echo json_encode(['success' => false, 'message' => 'Duplicate request detected. Please wait.']);
        exit;
    }
}
$_SESSION['last_ext_ctrl_req']  = $request_key;
$_SESSION['last_ext_ctrl_time'] = time();

$conn->begin_transaction();
try {
    // 1. Fetch active controller rental for this session
    // We only extend rows that are currently approved and not ended.
    $stmt = $conn->prepare(
        "SELECT request_id, description, extra_cost, created_at FROM additional_requests
         WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'
           AND description NOT LIKE '%[ENDED]%'
         LIMIT 1"
    );
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'No active controller rental found for this session.']);
        exit;
    }

    $rid      = (int)$row['request_id'];
    $old_desc = (string)$row['description'];
    $old_cost = (float)$row['extra_cost'];
    
    // ── 12:00 AM Closing Check ──────────────────────────────────────────────
    // Calculate current end time
    $max_mins = 0;
    if (preg_match('/\[Mins:\s*([\d, ]+)\]/', $old_desc, $m2)) {
        $minsRaw = explode(',', $m2[1]);
        foreach ($minsRaw as $minRaw) {
            $mVal = (int)trim($minRaw);
            if ($mVal > $max_mins) $max_mins = $mVal;
        }
    }
    
    $rentedTs = strtotime($row['created_at']);
    $currentEndTs = $rentedTs + ($max_mins * 60);
    $newEndTs = $currentEndTs + ($additional_minutes * 60);
    
    // Operating day 12:00 AM (next calendar day relative to operating day start)
    $operatingDay = getOperatingDay();
    $midnightTs = strtotime($operatingDay . ' +1 day');
    
    if ($newEndTs > $midnightTs) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Extension would exceed the 12:00 AM closing time.']);
        exit;
    }

    // 2. Extract controller IDs to calculate rate
    $ids = [];
    if (preg_match('/ID(?:s)?:\s*([\d, ]+)/', $old_desc, $m)) {
        $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($m[1])))));
    }
    
    if (empty($ids)) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Could not identify controllers in rental.']);
        exit;
    }

    $qty = count($ids);
    $total_mins = $max_mins + $additional_minutes;
    
    // ── New Billing Rule: ₱10 per 30-minute block per controller ──
    // Formula: qty * 10 * floor((total_mins + 25) / 30)
    $new_cost = $qty * 10 * floor(($total_mins + 25) / 30);

    // 3. Update Description
    // We update the [Mins: ...] metadata which tracks the booked duration per controller
    $new_desc = $old_desc;
    if (preg_match('/\[Mins:\s*([\d, ]+)\]/', $old_desc, $mm)) {
        $minsArr = array_map('intval', preg_split('/\s*,\s*/', trim($mm[1])));
        // Add additional_minutes to each controller in the line
        foreach ($minsArr as &$mVal) {
            $mVal += $additional_minutes;
        }
        $newMinsStr = implode(',', $minsArr);
        $new_desc = preg_replace('/\[Mins:\s*[^\]]+\]/', "[Mins: $newMinsStr]", $new_desc);
    } else {
        // If no [Mins: ...] exists (unusual for fixed-time), we append it
        $new_desc .= " [Mins: " . implode(',', array_fill(0, count($ids), $additional_minutes)) . "]";
    }
    
    // Update [FIX: ...] part if it exists (tracks prepaid fixed amounts per controller)
    // We update this to match the new total cost for record keeping
    if (preg_match('/\[FIX:([^\]]+)\]/', $new_desc, $fm)) {
        $pairs = explode('|', $fm[1]);
        $newPairs = [];
        $costPerCtrl = (float)($new_cost / $qty);
        foreach ($pairs as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) === 2) {
                $cid = (int)$parts[0];
                $newPairs[] = $cid . ':' . number_format($costPerCtrl, 0, '.', '');
            }
        }
        $new_desc = preg_replace('/\[FIX:[^\]]+\]/', '[FIX:' . implode('|', $newPairs) . ']', $new_desc);
    }

    $upd = $conn->prepare("UPDATE additional_requests SET description = ?, extra_cost = ? WHERE request_id = ?");
    $new_cost_int = (int)$new_cost;
    $upd->bind_param('sdi', $new_desc, $new_cost_int, $rid);
    if (!$upd->execute()) {
        throw new Exception("Database update failed: " . $conn->error);
    }

    $conn->commit();

    logActivity($_SESSION['user_id'], "Extend Controller", "Extended Controller Rental for Session #{$session_id} by {$additional_minutes} min (Total Fee: ₱{$new_cost_int})");

    // 5. Build success response with new details for surgical UI update
    $ph = $total_mins > 0 ? intdiv($total_mins, 60) : 0;
    $pm = $total_mins > 0 ? $total_mins % 60 : 0;
    $new_dur_str = $total_mins > 0 ? ($ph ? ($pm ? "{$ph}h {$pm}m" : "{$ph}h") : "{$pm}m") : '—';
    $new_ends_at_str = date('h:i A', $newEndTs);
    $new_total_cost_fmt = number_format($new_cost_int, 0);

    echo json_encode([
        'success'            => true,
        'message'            => "Rental extended by {$additional_minutes} mins.",
        'new_ends_ts'        => $newEndTs,
        'new_dur_str'        => $new_dur_str,
        'new_ends_at_str'    => $new_ends_at_str,
        'new_total_cost_fmt' => $new_total_cost_fmt,
        'new_total_cost'     => $new_cost_int,
        'new_total_mins'     => $total_mins
    ]);

} catch (Exception $e) {
    if ($conn->connect_errno === 0) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
