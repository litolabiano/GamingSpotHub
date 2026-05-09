<?php
/**
 * ajax/session_extras.php
 * Returns the total approved additional_requests cost for a session,
 * plus per-controller breakdown for the End Session modal.
 *
 * GET params:
 *   session_id   int
 *
 * Returns JSON:
 *   {
 *     success: true,
 *     extras: 20.00,
 *     items: [{ description, extra_cost }],
 *     controller_rows: [{ controller_id, label, hourly_rate, original_cost, is_ended }]
 *   }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['shopkeeper', 'owner'])) {
    echo json_encode(['success' => false, 'extras' => 0, 'items' => [], 'controller_rows' => [], 'message' => 'Unauthorized.']);
    exit;
}

$session_id = (int)($_GET['session_id'] ?? 0);
if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT description, extra_cost, request_type
     FROM additional_requests
     WHERE session_id = ? AND status = 'approved'
     ORDER BY created_at ASC"
);
$stmt->bind_param('i', $session_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_column($rows, 'extra_cost'));

// ── Build per-controller rows for modal UI ──────────────────────────────────
$ctrlRows = [];
foreach ($rows as $row) {
    if (($row['request_type'] ?? '') !== 'controller_rental') {
        continue;
    }
    $desc    = (string) $row['description'];
    $rowCost = (float)  $row['extra_cost'];
    $isEnded = strpos($desc, '[ENDED]') !== false;

    // Extract controller IDs
    $ids = [];
    if (preg_match('/IDs:\s*([\d,\s]+)/', $desc, $m)) {
        $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($m[1])))));
    }
    if ($ids === []) {
        continue;
    }

    // Parse fixed costs per ID
    $fixParts = [];
    if (preg_match('/\[FIX:([^\]]+)\]/', $desc, $fm)) {
        foreach (explode('|', $fm[1]) as $pair) {
            $pair = trim($pair);
            if ($pair === '') continue;
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $fixParts[(int)$parts[0]] = (float)$parts[1];
            }
        }
    }

    foreach ($ids as $cid) {
        if ($cid <= 0) continue;

        $cr = $conn->prepare('SELECT unit_number, hourly_rate FROM controllers WHERE controller_id = ?');
        $cr->bind_param('i', $cid);
        $cr->execute();
        $ctrlData = $cr->get_result()->fetch_assoc();

        $unitNum  = $ctrlData['unit_number'] ?? ('C' . $cid);
        $rate     = (float)($ctrlData['hourly_rate'] ?? 0);

        if (isset($fixParts[$cid])) {
            $ctrlCost = $fixParts[$cid];
        } elseif (count($ids) === 1) {
            $ctrlCost = $rowCost;
        } else {
            $ctrlCost = round($rowCost / count($ids), 2);
        }

        $ctrlRows[] = [
            'controller_id' => $cid,
            'label'         => 'Controller ' . $unitNum,
            'hourly_rate'   => $rate,
            'original_cost' => $ctrlCost,
            'is_ended'      => $isEnded,
        ];
    }
}

// Strip request_type from items for backward compatibility
$items = array_map(function($r) {
    return ['description' => $r['description'], 'extra_cost' => $r['extra_cost']];
}, $rows);

echo json_encode([
    'success'         => true,
    'extras'          => (float)$total,
    'items'           => $items,
    'controller_rows' => $ctrlRows,
]);
