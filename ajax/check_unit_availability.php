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
 *   console_type PS5 | PS4 | Xbox Series X
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

// ── 1. Get all non-maintenance consoles of this type ─────
$stmt = $conn->prepare(
    "SELECT console_id, unit_number, console_name, status
       FROM consoles
      WHERE console_type = ? AND status != 'maintenance'
      ORDER BY unit_number"
);
$stmt->bind_param('s', $console_type);
$stmt->execute();
$consoles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── 2. For each unit, check reservations table by date + console_id ──
//    Separate query is simplest and most accurate.
$stmtRes = $conn->prepare(
    "SELECT r.reservation_id, r.reserved_time, u.full_name AS reserved_by
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.console_id   = ?
        AND r.reserved_date = ?
        AND r.status IN ('pending','confirmed')
      LIMIT 1"
);

// ── 3. Count unassigned reservations (console_id NULL) for this type+date ──
$stmtUnassigned = $conn->prepare(
    "SELECT r.reservation_id, r.reserved_time, u.full_name AS reserved_by
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.console_type  = ?
        AND r.reserved_date  = ?
        AND r.console_id     IS NULL
        AND r.status IN ('pending','confirmed')"
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

    // Check reservation table: is there any booking for this unit on this date?
    $stmtRes->bind_param('is', $cid, $date);
    $stmtRes->execute();
    $resRow = $stmtRes->get_result()->fetch_assoc();

    $conflict = null;
    $status   = 'available';

    if ($resRow) {
        // A reservation exists for this unit on this date
        $status   = 'reserved';
        $conflict = [
            'reservation_id' => (int)$resRow['reservation_id'],
            'reserved_time'  => $resRow['reserved_time'],
            'reserved_by'    => $resRow['reserved_by'],
        ];
    } elseif ($c['status'] === 'in_use' && $date === $today) {
        // Live in-use status only relevant for today
        $status = 'in_use';
    }

    $units[] = [
        'id'       => $cid,
        'unit'     => $c['unit_number'],
        'name'     => $c['console_name'],
        'status'   => $status,
        'conflict' => $conflict,
    ];
}

echo json_encode([
    'success'                 => true,
    'units'                   => $units,
    'unassigned_count'        => count($unassigned),
    'unassigned_reservations' => $unassigned,
]);
