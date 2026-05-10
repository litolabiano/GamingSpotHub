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

try {
    if (!$date || !$time) {
        echo json_encode(['success' => false, 'message' => 'Please select a date and time first.']);
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
            JOIN console_types ct ON c.console_type_id = ct.console_type_id
            WHERE c.status != 'archived' AND c.status != 'maintenance'";
    if ($console_type) {
        $sql .= " AND ct.type_name = '" . $conn->real_escape_string($console_type) . "'";
    }
    $sql .= " ORDER BY ct.type_name, c.unit_number";

    $res = $conn->query($sql);
    if (!$res) {
        throw new Exception("Console query failed: " . $conn->error);
    }
    $allConsoles = $res->fetch_all(MYSQLI_ASSOC);

    // 2. Fetch all potentially conflicting sessions (active sessions on the same day)
    $activeSessions = [];
    if ($date === $today) {
        $sessQ = $conn->query("SELECT console_id, start_time, planned_minutes, rental_mode FROM gaming_sessions WHERE status = 'active'");
        if (!$sessQ) {
            throw new Exception("Session query failed: " . $conn->error);
        }
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

    // ── Unlimited sessions: collect console IDs blocked for the ENTIRE day ──
    // An active Unlimited session occupies the unit until shop closing (no fixed end).
    $unlimitedBlockedIds = [];
    foreach ($activeSessions as $sess) {
        if ($sess['mode'] === 'unlimited') {
            $unlimitedBlockedIds[] = $sess['console_id'];
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
    if (!$resQ) {
        throw new Exception("Reservation query failed: " . $conn->error);
    }
    $existingReservations = [];
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

    // 4. Filter consoles
    $units = [];
    foreach ($allConsoles as $c) {
        $cid = (int)$c['console_id'];

        // ── Units with active Unlimited session: visible but unselectable ──
        // They appear as cards labeled "In Use (Unlimited)" so users know why
        // the unit is occupied, but they cannot be selected.
        if (in_array($cid, $unlimitedBlockedIds, true)) {
            $units[] = [
                'id'          => $cid,
                'unit'        => $c['unit_number'],
                'name'        => $c['console_name'],
                'type'        => $c['console_type'],
                'rate'        => (float)$c['hourly_rate'],
                'controllers' => (int)$c['controller_count'],
                'status'      => 'unlimited',   // special sentinel
                'conflict'    => 'Occupied all day — active Unlimited session in progress'
            ];
            continue;
        }

        $isAvailable = true;
        $conflictMsg = null;

        // Check sessions
        foreach ($activeSessions as $sess) {
            if ($sess['console_id'] === $cid) {
                if ($sess['end']) {
                    if ($requestedStart < $sess['end'] && $requestedEnd > $sess['start']) {
                        $isAvailable = false;
                        $conflictMsg = 'Console in use until ' . $sess['end']->format('g:i A');
                        break;
                    }
                } else {
                    // Open Time: block only if requested slot overlaps current moment
                    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                    if ($requestedStart < $now) {
                        $isAvailable = false;
                        $conflictMsg = 'Console currently in use (Open Time)';
                        break;
                    }
                }
            }
        }

        if (!$isAvailable) {
            $units[] = [
                'id'          => $cid,
                'unit'        => $c['unit_number'],
                'name'        => $c['console_name'],
                'type'        => $c['console_type'],
                'rate'        => (float)$c['hourly_rate'],
                'controllers' => (int)$c['controller_count'],
                'status'      => 'in_use',
                'conflict'    => $conflictMsg
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

    // 5. Fetch all controllers and filter availability
    $sqlCtrl = "SELECT c.controller_id, c.unit_number,
                       ct.type_name AS type_name, ct.console_type_id, c.hourly_rate,
                       cs.type_name AS console_type_name
                  FROM controllers c
                  JOIN controller_types ct ON ct.controller_type_id = c.controller_type_id
                  JOIN console_types cs ON cs.console_type_id = ct.console_type_id
                 WHERE c.status = 'available'
                 ORDER BY cs.type_name, ct.type_name, c.unit_number";
    $ctrlRes = $conn->query($sqlCtrl);
    if (!$ctrlRes) {
        throw new Exception("Controller query failed: " . $conn->error);
    }
    $allControllers = $ctrlRes->fetch_all(MYSQLI_ASSOC);

    $sqlResCtrl = "SELECT reservation_id, controller_id, controller_id_2, reserved_time, planned_minutes, rental_mode
                   FROM reservations r
                   WHERE r.reserved_date = '$date' 
                     AND r.status IN ('pending', 'reserved')
                     AND (r.controller_id IS NOT NULL OR r.controller_id_2 IS NOT NULL)";
    if ($exclude_res_id) {
        $sqlResCtrl .= " AND r.reservation_id != $exclude_res_id";
    }
    $resQC = $conn->query($sqlResCtrl);
    if (!$resQC) {
        throw new Exception("Reservation controller query failed: " . $conn->error);
    }
    $reservedControllers = [];
    while ($r = $resQC->fetch_assoc()) {
        $start = new DateTime("$date {$r['reserved_time']}");
        $mins  = $r['rental_mode'] === 'hourly' ? (int)$r['planned_minutes'] : 60;
        $end   = clone $start;
        $end->modify("+$mins minutes");
        
        if (!empty($r['controller_id'])) {
            $reservedControllers[] = [
                'controller_id' => (int)$r['controller_id'],
                'start'         => $start,
                'end'           => $end,
            ];
        }
        if (!empty($r['controller_id_2'])) {
            $reservedControllers[] = [
                'controller_id' => (int)$r['controller_id_2'],
                'start'         => $start,
                'end'           => $end,
            ];
        }
    }

    $controllers = [];
    foreach ($allControllers as $c) {
        $cid = (int)$c['controller_id'];
        $isAvailable = true;

        foreach ($reservedControllers as $res) {
            if ($res['controller_id'] === $cid) {
                if ($requestedStart < $res['end'] && $requestedEnd > $res['start']) {
                    $isAvailable = false;
                    break;
                }
            }
        }

        if ($isAvailable) {
            $controllers[] = $c;
        }
    }

    echo json_encode([
        'success' => true,
        'units' => $units,
        'controllers' => $controllers
    ]);

} catch (Exception $e) {
    error_log("Availability Check Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Could not check unit availability at this time.',
        'debug' => $e->getMessage()
    ]);
}

