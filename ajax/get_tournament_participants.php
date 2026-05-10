<?php
require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

// Ensure no warnings/notices break the JSON output
error_reporting(0);
ini_set('display_errors', 0);

try {
    $tournament_id = (int)($_GET['tournament_id'] ?? 0);
    $status        = $_GET['status'] ?? 'active';
    if (!in_array($status, ['active', 'archived'])) $status = 'active';

    if (!$tournament_id) {
        echo json_encode(['success' => true, 'participants' => []]);
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
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param('is', $tournament_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);

    // Format dates
    foreach ($rows as &$r) {
        if ($r['registration_date']) {
            $ts = strtotime($r['registration_date']);
            $r['registration_date'] = ($ts) ? date('M d, Y h:i A', $ts) : $r['registration_date'];
        }
    }

    echo json_encode([
        'success' => true,
        'participants' => $rows
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'participants' => []
    ]);
}
