<?php
require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

$tournament_id = (int)($_GET['tournament_id'] ?? 0);
$status        = $_GET['status'] ?? 'active';
if (!in_array($status, ['active', 'archived'])) $status = 'active';

if (!$tournament_id) {
    echo json_encode(['participants' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT tp.participant_id, tp.payment_status, tp.registration_date,
           tp.ign, tp.contact_number, tp.walkin_name, tp.notes,
           tp.paymongo_source_id, tp.paymongo_status,
           u.full_name, u.email,
           t.entry_fee
    FROM tournament_participants tp
    LEFT JOIN users u ON tp.user_id = u.user_id
    JOIN tournaments t ON tp.tournament_id = t.tournament_id
    WHERE tp.tournament_id = ? AND tp.status = ?
    ORDER BY tp.registration_date ASC
");
$stmt->bind_param('is', $tournament_id, $status);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format dates
foreach ($rows as &$r) {
    $r['registration_date'] = date('M d, Y h:i A', strtotime($r['registration_date']));
}

echo json_encode(['participants' => $rows]);
