<?php
/**
 * API: Returns real-time console statuses as JSON.
 * Used by the units section to poll for live updates.
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../includes/db_config.php';

$result = $conn->query(
    "SELECT console_id, status FROM consoles ORDER BY console_type, unit_number"
);

$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[] = [
        'id'     => (int) $row['console_id'],
        'status' => $row['status']
    ];
}

echo json_encode(['consoles' => $statuses, 'timestamp' => time()]);
