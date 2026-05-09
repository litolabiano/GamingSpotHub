<?php
/**
 * ajax/check_availability.php
 * ──────────────────────────────────────────────────────────────────
 * Returns a summary of available console counts per type for a date + time.
 * Uses full time-window overlap logic against sessions and reservations.
 * 
 * GET params:
 *   date           YYYY-MM-DD
 *   time           HH:MM (24h)
 *   planned_minutes (optional, default 60)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_config.php';

$date            = trim($_GET['date']            ?? '');
$time            = trim($_GET['time']            ?? '');
$planned_minutes = (int)($_GET['planned_minutes'] ?? 60);

if (!$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'date and time are required']);
    exit;
}

// Validation logic (12 PM - 11 PM)
if ($time < '12:00' || $time > '23:00') {
    echo json_encode(['success' => false, 'message' => 'Reservations are only accepted between 12:00 PM and 11:00 PM.']);
    exit;
}

$requestedStart = new DateTime("$date $time");
$requestedEnd   = clone $requestedStart;
$requestedEnd->modify("+$planned_minutes minutes");

$today = date('Y-m-d');

// 1. Get all consoles and types
$sql = "SELECT c.console_id, c.status, ct.type_name AS console_type
        FROM consoles c
        JOIN console_types ct ON c.console_type_id = ct.type_id
        WHERE c.status != 'archived' AND c.status != 'maintenance'
        ORDER BY ct.type_name, c.unit_number";
$res = $conn->query($sql);
$allConsoles = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// 2. Fetch potentially conflicting active sessions
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
            $activeSessions[(int)$s['console_id']][] = ['start' => $start, 'end' => $end];
        }
    }
}

// 3. Fetch potentially conflicting reservations
$resQ = $conn->query("SELECT console_id, reserved_time, planned_minutes, rental_mode, status
                      FROM reservations 
                      WHERE reserved_date = '$date' AND status IN ('pending', 'reserved') AND console_id IS NOT NULL");
$existingReservations = [];
if ($resQ) {
    while ($r = $resQ->fetch_assoc()) {
        $start = new DateTime("$date {$r['reserved_time']}");
        $mins  = $r['rental_mode'] === 'hourly' ? (int)$r['planned_minutes'] : 60;
        $end   = clone $start;
        $end->modify("+$mins minutes");
        $existingReservations[(int)$r['console_id']][] = ['start' => $start, 'end' => $end, 'is_confirmed' => ($r['status'] === 'reserved')];
    }
}

// 4. Summarize
$summary = [];
foreach ($allConsoles as $c) {
    $cid  = (int)$c['console_id'];
    $type = $c['console_type'];
    
    if (!isset($summary[$type])) {
        $summary[$type] = ['total' => 0, 'available' => 0, 'confirmed_count' => 0];
    }
    $summary[$type]['total']++;

    $isConflicted = false;
    $isConfirmedConflict = false;

    // Check sessions
    if (isset($activeSessions[$cid])) {
        foreach ($activeSessions[$cid] as $sess) {
            if ($sess['end']) {
                if ($requestedStart < $sess['end'] && $requestedEnd > $sess['start']) {
                    $isConflicted = true;
                    break;
                }
            } else {
                // Open Time active session on today's date
                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                if ($requestedStart < $now) {
                    $isConflicted = true;
                    break;
                }
            }
        }
    }

    // Check reservations
    if (!$isConflicted && isset($existingReservations[$cid])) {
        foreach ($existingReservations[$cid] as $res) {
            if ($requestedStart < $res['end'] && $requestedEnd > $res['start']) {
                $isConflicted = true;
                if ($res['is_confirmed']) $isConfirmedConflict = true;
                break;
            }
        }
    }

    if (!$isConflicted) {
        $summary[$type]['available']++;
    }
    if ($isConfirmedConflict) {
        $summary[$type]['confirmed_count']++;
    }
}

echo json_encode(['success' => true, 'availability' => $summary]);
