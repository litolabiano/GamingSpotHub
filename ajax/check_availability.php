<?php
/**
 * Good Spot Gaming Hub – Check Console Availability
 * Returns JSON list of available console counts for a date + time.
 *
 * GET params: date (YYYY-MM-DD), time (HH:MM)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_config.php';

$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';

if (!$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'date and time are required']);
    exit;
}

// Validate date is today or future
$today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
if ($date < $today) {
    echo json_encode(['success' => false, 'message' => 'Cannot reserve a past date']);
    exit;
}

// For each console type, count how many consoles are NOT reserved or active at that date/time
// A 2-hour window is assumed for conflict detection.
$requestedDT = $date . ' ' . $time . ':00';

$sql = "
    SELECT
        c.console_type,
        c.console_id,
        c.unit_number,
        c.status,
        -- Check for conflicting confirmed reservations within ±2h window
        (SELECT COUNT(*) FROM reservations r
            WHERE r.console_id = c.console_id
              AND r.status IN ('pending','confirmed')
              AND r.reserved_date = ?
              AND ABS(TIMESTAMPDIFF(MINUTE, CONCAT(r.reserved_date,' ',r.reserved_time), ?)) < 120
        ) AS has_reservation
    FROM consoles c
    WHERE c.status != 'maintenance'
    ORDER BY c.console_type, c.unit_number
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $date, $requestedDT);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$summary = [];
foreach ($rows as $r) {
    $type = $r['console_type'];
    if (!isset($summary[$type])) {
        $summary[$type] = ['total' => 0, 'available' => 0, 'units' => []];
    }
    $summary[$type]['total']++;
    $isFree = ($r['status'] !== 'in_use') && ((int)$r['has_reservation'] === 0);
    if ($isFree) {
        $summary[$type]['available']++;
        $summary[$type]['units'][] = $r['unit_number'];
    }
}

echo json_encode(['success' => true, 'availability' => $summary]);
