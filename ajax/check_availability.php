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

// Enforce allowed reservation window: 12:00 PM – 11:00 PM
if ($time < '12:00' || $time > '23:00') {
    echo json_encode(['success' => false, 'message' => 'Reservations are only accepted between 12:00 PM and 11:00 PM.']);
    exit;
}

// A unit is conflicted only when an existing reservation's session window actually
// overlaps the requested start time:
//   reservation_start  <=  requested_time  <  reservation_start + planned_minutes
// This avoids false positives from the old ±2h blanket window.
// Fallback: treat rows with no planned_minutes as 60-minute sessions.
$requestedDT = $date . ' ' . $time . ':00';

$sql = "
    SELECT
        c.console_type,
        c.console_id,
        c.unit_number,
        c.status,
        -- Count pending+confirmed reservations whose session window overlaps the requested slot
        (SELECT COUNT(*) FROM reservations r
            WHERE r.console_id = c.console_id
              AND r.status IN ('pending','confirmed')
              AND r.reserved_date = ?
              AND CONCAT(r.reserved_date,' ',r.reserved_time) <= ?
              AND DATE_ADD(CONCAT(r.reserved_date,' ',r.reserved_time),
                          INTERVAL COALESCE(NULLIF(r.planned_minutes,0), 60) MINUTE) > ?
        ) AS has_reservation,
        -- Count ONLY confirmed reservations (slot is definitively locked)
        (SELECT COUNT(*) FROM reservations r
            WHERE r.console_id = c.console_id
              AND r.status = 'confirmed'
              AND r.reserved_date = ?
              AND CONCAT(r.reserved_date,' ',r.reserved_time) <= ?
              AND DATE_ADD(CONCAT(r.reserved_date,' ',r.reserved_time),
                          INTERVAL COALESCE(NULLIF(r.planned_minutes,0), 60) MINUTE) > ?
        ) AS confirmed_reservation
    FROM consoles c
    WHERE c.status != 'maintenance'
    ORDER BY c.console_type, c.unit_number
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssss', $date, $requestedDT, $requestedDT, $date, $requestedDT, $requestedDT);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$summary = [];
foreach ($rows as $r) {
    $type = $r['console_type'];
    if (!isset($summary[$type])) {
        $summary[$type] = ['total' => 0, 'available' => 0, 'confirmed_count' => 0, 'units' => []];
    }
    $summary[$type]['total']++;
    // For FUTURE dates: a unit is free if it has no overlapping reservation booked —
    // the console's current real-time status (in_use right now) is irrelevant for advance bookings.
    // For TODAY: also gate on the live status so we don't show an actively-used unit as free.
    $isToday  = ($date === $today);
    $isFree   = (!$isToday || $r['status'] !== 'in_use') && ((int)$r['has_reservation'] === 0);

    if ($isFree) {
        $summary[$type]['available']++;
        $summary[$type]['units'][] = $r['unit_number'];
    }
    // Accumulate confirmed-locked units
    if ((int)$r['confirmed_reservation'] > 0) {
        $summary[$type]['confirmed_count']++;
    }
}

echo json_encode(['success' => true, 'availability' => $summary]);
