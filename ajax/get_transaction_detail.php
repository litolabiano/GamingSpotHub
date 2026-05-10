<?php
/**
 * ajax/get_transaction_detail.php
 * Fetches detailed breakdown for a transaction and its associated session.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$txId = (int)($_GET['id'] ?? 0);
if (!$txId) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit;
}

// 1. Fetch Transaction
$stmt = $conn->prepare(
    "SELECT t.*, u.full_name AS customer_name, gs.rental_mode, gs.session_id, gs.start_time, gs.end_time, gs.total_cost AS session_total_cost,
            c.unit_number, gs.planned_minutes, gs.reservation_id, gs.rental_mode
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
     LEFT JOIN consoles c ON gs.console_id = c.console_id
     WHERE t.transaction_id = ?"
);
$stmt->bind_param('i', $txId);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();

if (!$tx) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

$sid = (int)($tx['session_id'] ?? 0);
$breakdown = [];

if ($sid > 0) {
    // 2. Fetch Controller Rentals for this session
    $stmt2 = $conn->prepare(
        "SELECT description, extra_cost, created_at, request_id
         FROM additional_requests 
         WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'"
    );
    $stmt2->bind_param('i', $sid);
    $stmt2->execute();
    $extras = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    $controllerAddons = [];
    $totalAddonFee = 0;

    foreach ($extras as $ex) {
        $desc = $ex['description'];
        $cost = (float)$ex['extra_cost'];
        
        // Extract info from description (IDs, Mins, etc.)
        $label = "Controller Rental";
        if (preg_match('/ID(?:s)?:\s*([\d,\s]+)/', $desc, $m)) {
            $ids = trim($m[1]);
            $label = "Controllers ($ids)";
        }
        
        $durationStr = "—";
        if (preg_match('/\[Mins:\s*([\d,\s]+)\]/', $desc, $m)) {
            $mins = explode(',', $m[1]);
            $totalMins = array_sum(array_map('intval', $mins));
            $h = floor($totalMins / 60);
            $m = $totalMins % 60;
            $durationStr = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
        } elseif (strpos($desc, '[ENDED]') !== false) {
             // If ended, the description often has more info, but we rely on the final cost
        }

        $controllerAddons[] = [
            'label'    => $label,
            'duration' => $durationStr,
            'fee'      => $cost
        ];
        $totalAddonFee += $cost;
    }

    // 3. Calculate Session Base Cost
    $baseCost = 0;
    $modeLabel = "Unknown";
    if ($tx['rental_mode'] === 'unlimited') {
        $baseCost = (float)getSetting('unlimited_rate', 400);
        $modeLabel = "Unlimited Session (Flat Rate)";
    } elseif ($tx['rental_mode'] === 'hourly') {
        $planned = (int)$tx['planned_minutes'];
        $baseCost = computeHourlySessionBaseCost($planned);
        $modeLabel = "Hourly Session ($planned mins)";
    } elseif ($tx['rental_mode'] === 'open_time') {
        // For completed sessions, use actual time
        if ($tx['end_time']) {
            $start = strtotime($tx['start_time']);
            $end = strtotime($tx['end_time']);
            $mins = floor(($end - $start) / 60);
            $baseCost = computeTimedCost($mins);
            $modeLabel = "Open Time Session ($mins mins)";
        } else {
             $modeLabel = "Open Time Session (Active)";
        }
    }

    // 4. Reservation Fee
    $reservationFee = 0;
    if ($tx['reservation_id']) {
        $stmt3 = $conn->prepare("SELECT downpayment_amount FROM reservations WHERE reservation_id = ?");
        $stmt3->bind_param('i', $tx['reservation_id']);
        $stmt3->execute();
        $res = $stmt3->get_result()->fetch_assoc();
        $reservationFee = (float)($res['downpayment_amount'] ?? 0);
    }

    $breakdown = [
        'session_mode'     => $modeLabel,
        'base_cost'        => $baseCost,
        'controllers'      => $controllerAddons,
        'total_addon_fee'  => $totalAddonFee,
        'reservation_fee'  => $reservationFee,
        'final_total'      => $baseCost + $totalAddonFee - $reservationFee
    ];
}

echo json_encode([
    'success'    => true,
    'tx'         => [
        'id'        => $tx['transaction_id'],
        'date'      => date('M d, Y h:i A', strtotime($tx['transaction_date'])),
        'customer'  => $tx['customer_name'],
        'console'   => $tx['unit_number'],
        'amount'    => (float)$tx['amount'],
        'method'    => ucfirst($tx['payment_method']),
        'status'    => ucfirst($tx['payment_status']),
        'note'      => $tx['payment_note']
    ],
    'breakdown'  => $breakdown
]);
