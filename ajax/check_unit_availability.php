<?php
/**
 * ajax/check_unit_availability.php
 * ──────────────────────────────────────────────────────────────────
 * Returns a list of available console units for a given date, time, and duration.
 * Checks against both active gaming sessions and existing reservations.
 * 
 * GET params:
 *   date           YYYY-MM-DD
 *   time           HH:MM (24h)
 *   planned_minutes (optional, default 60)
 *   console_type   (optional, filter by type_name)
 *   exclude_res_id (optional, exclude a specific reservation from conflict check - e.g. for rescheduling)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_config.php';

$date            = trim($_GET['date']            ?? '');
$time            = trim($_GET['time']            ?? '');
$planned_minutes = (int)($_GET['planned_minutes'] ?? 60); // Default to 1hr if not specified
$console_type    = trim($_GET['console_type']    ?? '');
$exclude_res_id  = (int)($_GET['exclude_res_id']  ?? 0);

if (!$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'Missing date or time.']);
    exit;
}

// Ensure 24h format for time
$time = date('H:i', strtotime($time));

$requestedStart = new DateTime("$date $time");
$requestedEnd   = clone $requestedStart;
$requestedEnd->modify("+$planned_minutes minutes");

$requestedStartStr = $requestedStart->format('Y-m-d H:i:s');
$requestedEndStr   = $requestedEnd->format('Y-m-d H:i:s');

$today = date('Y-m-d');

// 1. Get all active (non-archived, non-maintenance) consoles
$sql = "SELECT c.console_id, c.unit_number, c.console_name, c.status, c.controller_count, ct.type_name AS console_type, ct.hourly_rate
        FROM consoles c
        JOIN console_types ct ON c.console_type_id = ct.type_id
        WHERE c.status != 'archived' AND c.status != 'maintenance'";
if ($console_type) {
    $sql .= " AND ct.type_name = '" . $conn->real_escape_string($console_type) . "'";
}
$sql .= " ORDER BY ct.type_name, c.unit_number";

$res = $conn->query($sql);
$allConsoles = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// 2. Fetch all potentially conflicting sessions (active sessions on the same day)
$activeSessions = [];
if ($date === $today) {
    $sessQ = $conn->query("SELECT console_id, start_time, planned_minutes, rental_mode FROM gaming_sessions WHERE status = 'active'");
    if ($sessQ) {
        while ($s = $sessQ->fetch_assoc()) {
            $start = new DateTime($s['start_time']);
            $end   = null;
            if ($s['rental_mode'] === 'hourly' && $s['planned_minutes']) {
                $end = clone $start;
                $end->modify("+{$s['planned_minutes']} minutes");
            }
            $activeSessions[] = [
                'console_id' => (int)$s['console_id'],
                'start'      => $start,
                'end'        => $end, // if null, it's open_time/unlimited
                'mode'       => $s['rental_mode']
            ];
        }
    }
}

// 3. Fetch all potentially conflicting reservations on that day
$sqlRes = "SELECT reservation_id, console_id, reserved_time, planned_minutes, rental_mode, u.full_name AS customer_name
           FROM reservations r
           JOIN users u ON r.user_id = u.user_id
           WHERE r.reserved_date = '$date' 
             AND r.status IN ('pending', 'reserved')
             AND r.console_id IS NOT NULL";
if ($exclude_res_id) {
    $sqlRes .= " AND r.reservation_id != $exclude_res_id";
}
$resQ = $conn->query($sqlRes);
$existingReservations = [];
if ($resQ) {
    while ($r = $resQ->fetch_assoc()) {
        $start = new DateTime("$date {$r['reserved_time']}");
        $mins  = $r['rental_mode'] === 'hourly' ? (int)$r['planned_minutes'] : 60; // Assume 1hr for others
        $end   = clone $start;
        $end->modify("+$mins minutes");
        
        $existingReservations[] = [
            'reservation_id' => (int)$r['reservation_id'],
            'console_id'     => (int)$r['console_id'],
            'start'          => $start,
            'end'            => $end,
            'customer'       => $r['customer_name']
        ];
    }
}

// 4. Filter consoles
$units = [];
foreach ($allConsoles as $c) {
    $cid = (int)$c['console_id'];
    $isAvailable = true;
    $conflictMsg = null;

    // Check sessions
    foreach ($activeSessions as $sess) {
        if ($sess['console_id'] === $cid) {
            // If open_time, any future booking today is "at risk", but let's check overlap
            if ($sess['end']) {
                if ($requestedStart < $sess['end'] && $requestedEnd > $sess['start']) {
                    $isAvailable = false;
                    $conflictMsg = "Console in use until " . $sess['end']->format('g:i A');
                    break;
                }
            } else {
                // Open Time - assume it conflicts if requested start is before "now + some buffer"
                // Actually, let's just check if requested start is before "now"
                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                if ($requestedStart < $now) {
                    $isAvailable = false;
                    $conflictMsg = "Console currently in use (Open Time)";
                    break;
                }
            }
        }
    }

    if (!$isAvailable) {
        // Skip or mark as unavailable? User wants "only display", but for Step 2b (Grid) we might show as unavailable.
        // But for Admin dropdown, we should just exclude.
        // Actually, let's include all but with a flag.
        $units[] = [
            'id' => $cid,
            'unit' => $c['unit_number'],
            'name' => $c['console_name'],
            'type' => $c['console_type'],
            'rate' => (float)$c['hourly_rate'],
            'controllers' => (int)$c['controller_count'],
            'status' => 'in_use',
            'conflict' => $conflictMsg
        ];
        continue;
    }

    // Check reservations
    foreach ($existingReservations as $res) {
        if ($res['console_id'] === $cid) {
            if ($requestedStart < $res['end'] && $requestedEnd > $res['start']) {
                $isAvailable = false;
                $conflictMsg = "Reserved by {$res['customer']} at " . $res['start']->format('g:i A');
                break;
            }
        }
    }

    $units[] = [
        'id' => $cid,
        'unit' => $c['unit_number'],
        'name' => $c['console_name'],
        'type' => $c['console_type'],
        'rate' => (float)$c['hourly_rate'],
        'controllers' => (int)$c['controller_count'],
        'status' => $isAvailable ? 'available' : 'reserved',
        'conflict' => $conflictMsg
    ];
}

echo json_encode([
    'success' => true,
    'units' => $units
]);
