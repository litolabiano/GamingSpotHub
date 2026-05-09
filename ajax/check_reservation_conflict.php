<?php
/**
 * ajax/check_reservation_conflict.php
 * ──────────────────────────────────────────────────────────────────
 * Checks for conflicts for a new reservation being added by an admin.
 * Checks against both active sessions and confirmed reservations.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$console_id = (int)($_GET['console_id'] ?? 0);
$date       = $_GET['date'] ?? '';
$time       = $_GET['time'] ?? '';
$planned_mins = (int)($_GET['planned_minutes'] ?? 0);
$rental_mode = $_GET['rental_mode'] ?? 'hourly';

if (!$console_id || !$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

// 1. Calculate the requested interval
// For open_time/unlimited, we assume a reasonable block (e.g. 4 hours) for conflict checking, 
// or just check if anything starts at exactly that time.
// Store policy might vary, but let's assume a minimum window.
if ($rental_mode !== 'hourly' || $planned_mins <= 0) {
    $planned_mins = 60; // default 1hr block for checking overlaps of non-hourly modes
}

$requestedStart = new DateTime("$date $time");
$requestedEnd   = clone $requestedStart;
$requestedEnd->modify("+$planned_mins minutes");

$requestedStartStr = $requestedStart->format('Y-m-d H:i:s');
$requestedEndStr   = $requestedEnd->format('Y-m-d H:i:s');

// 2. Check against Active Sessions (only if date is today)
$today = date('Y-m-d');
if ($date === $today) {
    $sessStmt = $conn->prepare(
        "SELECT session_id, start_time, planned_minutes, rental_mode
           FROM gaming_sessions
          WHERE console_id = ? AND status = 'active'"
    );
    $sessStmt->bind_param('i', $console_id);
    $sessStmt->execute();
    $activeSess = $sessStmt->get_result()->fetch_assoc();

    if ($activeSess) {
        $sessStart = new DateTime($activeSess['start_time']);
        $sessEnd   = null;
        if ($activeSess['rental_mode'] === 'hourly' && $activeSess['planned_minutes']) {
            $sessEnd = clone $sessStart;
            $sessEnd->modify("+{$activeSess['planned_minutes']} minutes");
        }

        // Check for overlap
        // If sessEnd is null (open_time), any future reservation today on this console is a potential conflict 
        // until the session ends. But admins often know when it will end.
        // For simplicity: if it's currently active, we check if requested start is BEFORE current time + buffer.
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        if ($requestedStart < $now) {
             echo json_encode(['success' => true, 'conflict' => true, 'message' => 'Requested time is in the past.']);
             exit;
        }

        if ($sessEnd && $requestedStart < $sessEnd) {
             echo json_encode(['success' => true, 'conflict' => true, 'message' => 'Conflict with an active session ending at ' . $sessEnd->format('g:i A') . '.']);
             exit;
        } elseif (!$sessEnd && $requestedStart < $now->modify('+30 minutes')) {
             // If open_time, suggest a buffer
             echo json_encode(['success' => true, 'conflict' => true, 'message' => 'Console is currently in an Open Time session.']);
             exit;
        }
    }
}

// 3. Check against other Reservations
$resStmt = $conn->prepare(
    "SELECT reservation_id, reserved_date, reserved_time, planned_minutes, rental_mode,
            u.full_name AS customer_name
       FROM reservations r
       JOIN users u ON r.user_id = u.user_id
      WHERE r.console_id = ?
        AND r.status IN ('pending', 'reserved')
        AND r.reserved_date = ?
        AND r.reservation_id != 0"
);
$resStmt->bind_param('is', $console_id, $date);
$resStmt->execute();
$reservations = $resStmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($reservations as $res) {
    $resStart = new DateTime($res['reserved_date'] . ' ' . $res['reserved_time']);
    $resMins  = $res['rental_mode'] === 'hourly' ? (int)$res['planned_minutes'] : 60;
    $resEnd   = clone $resStart;
    $resEnd->modify("+$resMins minutes");

    // Check overlap: (StartA < EndB) AND (EndA > StartB)
    if ($requestedStart < $resEnd && $requestedEnd > $resStart) {
        $timeStr = $resStart->format('g:i A');
        echo json_encode(['success' => true, 'conflict' => true, 'message' => "Conflict with Reservation #{$res['reservation_id']} ({$res['customer_name']}) at {$timeStr}."]);
        exit;
    }
}

echo json_encode(['success' => true, 'conflict' => false, 'message' => 'No conflicts found.']);
