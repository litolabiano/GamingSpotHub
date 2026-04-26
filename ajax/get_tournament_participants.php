<?php
require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

$tournament_id = (int)($_GET['tournament_id'] ?? 0);
if (!$tournament_id) {
    echo json_encode(['participants' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT tp.participant_id, tp.payment_status, tp.registration_date,
           u.full_name, u.email
    FROM tournament_participants tp
    JOIN users u ON tp.user_id = u.user_id
    WHERE tp.tournament_id = ?
    ORDER BY tp.registration_date ASC
");
$stmt->bind_param('i', $tournament_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format dates
foreach ($rows as &$r) {
    $r['registration_date'] = date('M d, Y h:i A', strtotime($r['registration_date']));
}

echo json_encode(['participants' => $rows]);
