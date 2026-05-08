<?php
/**
 * Good Spot Gaming Hub – Per-Unit Reservation Availability
 *
 * Logic: A unit is RESERVED if ANY pending/confirmed reservation exists
 * in the `reservations` table matching:
 *   reserved_date = $date  AND  console_id = $unit_id
 *
 * No time-window math — if a unit has a booking for that date, it's taken.
 * Unassigned reservations (console_id IS NULL) are returned separately
 * so the UI can show a general notice.
 *
 * GET params:
 *   date         YYYY-MM-DD
 *   time         HH:MM  (used only for the "in_use" status today check)
 *   console_type PS5 | PS4 | Xbox Series X  (type_name from console_types)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_config.php';

$date         = trim($_GET['date']         ?? '');
$time         = trim($_GET['time']         ?? '');
$console_type = trim($_GET['console_type'] ?? '');

if (!$date || !$time || !$console_type) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
if ($date < $today) {
    echo json_encode(['success' => false, 'message' => 'Cannot check past dates.']);
    exit;
}

// ── 1. Get all non-maintenance consoles of this type (join console_types) ─────
$stmt = $conn->prepare(
    "SELECT c.console_id, c.unit_number, c.console_name, c.status
       FROM consoles c
       JOIN console_types ct ON c.console_type_id = ct.type_id
      WHERE ct.type_name = ? AND c.status != 'maintenance'
      ORDER BY c.unit_number"
);
$stmt->bind_param('s', $console_type);
$stmt->execute();
$consoles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── 2. For each unit, check reservations table by date + console_id ──
$stmtRes = $conn->prepare(
    "SELECT r.reservation_id, r.reserved_time, u.full_name AS reserved_by
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.console_id   = ?
        AND r.reserved_date = ?
        AND r.status IN ('pending','reserved')
      LIMIT 1"
);

// ── 3. Count unassigned reservations (console_id NULL) for this type+date ──
$stmtUnassigned = $conn->prepare(
    "SELECT r.reservation_id, r.reserved_time, u.full_name AS reserved_by
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
       JOIN console_types ct ON r.console_type_id = ct.type_id
      WHERE ct.type_name    = ?
        AND r.reserved_date  = ?
        AND r.console_id     IS NULL
        AND r.status IN ('pending','reserved')"
);
$stmtUnassigned->bind_param('ss', $console_type, $date);
$stmtUnassigned->execute();
$unassignedRows = $stmtUnassigned->get_result()->fetch_all(MYSQLI_ASSOC);
$unassigned = array_map(fn($r) => [
    'reservation_id' => (int)$r['reservation_id'],
    'reserved_time'  => $r['reserved_time'],
    'reserved_by'    => $r['reserved_by'],
], $unassignedRows);

// ── 4. Build per-unit response ────────────────────────────
$units = [];
foreach ($consoles as $c) {
    $cid = (int)$c['console_id'];

    $stmtRes->bind_param('is', $cid, $date);
    $stmtRes->execute();
    $resRow = $stmtRes->get_result()->fetch_assoc();

    $conflict = null;
    $status   = 'available';

    if ($resRow) {
        $status   = 'reserved';
        $conflict = [
            'reservation_id' => (int)$resRow['reservation_id'],
            'reserved_time'  => $resRow['reserved_time'],
            'reserved_by'    => $resRow['reserved_by'],
        ];
    } elseif ($c['status'] === 'in_use' && $date === $today) {
        $status = 'in_use';
    }

    $units[] = [
        'id'          => $cid,
        'unit'        => $c['unit_number'],
        'name'        => $c['console_name'],
        'status'      => $status,
        'controllers' => 2, // default; controller_count was removed
        'conflict'    => $conflict,
    ];
}

echo json_encode([
    'success'                 => true,
    'units'                   => $units,
    'unassigned_count'        => count($unassigned),
    'unassigned_reservations' => $unassigned,
]);
