<?php
/**
 * ajax/auto_end_unlimited.php — Automatic Session Termination at 12:00 AM
 * ─────────────────────────────────────────────────────────────────────────
 * Called by the admin dashboard JS poller every ~30 s around midnight.
 *
 * Behaviour (Unlimited sessions ONLY):
 *  • Finds every gaming session where rental_mode = 'unlimited' AND status = 'active'
 *    that started BEFORE today's midnight (00:00:00).
 *  • Ends each session with end_time = midnight, duration = start→midnight,
 *    total_cost = unlimited_rate (₱400 flat, regardless of actual duration).
 *  • Marks the console available, restores any rented controller.
 *  • Does NOT touch Hourly or Open Time sessions.
 *  • Idempotent: re-running after sessions are already completed is a no-op.
 *
 * Returns JSON:
 *  { success: true, ended: <count>, sessions: [{ session_id, customer, unit, duration_minutes, total_cost }] }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Only authenticated staff may trigger this
requireRole(['owner', 'shopkeeper']);

// ── Midnight boundary ─────────────────────────────────────────────────────────
// "Today's midnight" = 00:00:00 of the current calendar day (Asia/Manila).
// Any unlimited session that started before this timestamp is eligible.
$tz       = new DateTimeZone('Asia/Manila');
$now      = new DateTime('now', $tz);

// We only process if the current time is within the 00:00:00–00:02:00 window
// (the JS poller checks every 30 s, so this gives a 2-minute grace window).
// We also accept calls that arrive slightly late (up to 10 minutes past midnight)
// in case the browser tab was sleeping or the network was slow.
$midnight = (clone $now)->setTime(0, 0, 0);   // today 00:00:00
$cutoff   = (clone $midnight)->modify('+10 minutes'); // accept calls until 00:10:00

$currentHour   = (int)$now->format('H');
$currentMinute = (int)$now->format('i');

// Only proceed if we're within the allowed window (00:00 – 00:10)
// OR if we're being called from a server-side cron (no time restriction).
$isMidnightWindow = ($currentHour === 0 && $currentMinute <= 10);
$forcedByCron     = isset($_GET['cron']) && $_GET['cron'] === '1';

if (!$isMidnightWindow && !$forcedByCron) {
    echo json_encode([
        'success'  => true,
        'ended'    => 0,
        'sessions' => [],
        'message'  => 'Not yet closing time — no action taken.',
    ]);
    exit;
}

// The exact end timestamp we will stamp on all auto-terminated sessions
$midnightStr = $midnight->format('Y-m-d H:i:s'); // e.g. "2026-05-07 00:00:00"

// ── Fetch all active Unlimited sessions that started before midnight ──────────
$stmt = $conn->prepare(
    "SELECT gs.session_id, gs.user_id, gs.console_id, gs.start_time,
            gs.rental_mode, gs.hourly_rate,
            COALESCE(s.setting_value, '400') AS unlimited_rate,
            u.full_name AS customer_name,
            c.unit_number
       FROM gaming_sessions gs
       JOIN consoles c ON gs.console_id = c.console_id
       JOIN users u    ON gs.user_id    = u.user_id
       LEFT JOIN system_settings s ON s.setting_key = 'unlimited_rate'
      WHERE gs.status      = 'active'
        AND gs.rental_mode = 'unlimited'
        AND gs.start_time  < ?"
);
$stmt->bind_param('s', $midnightStr);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($sessions)) {
    echo json_encode([
        'success'  => true,
        'ended'    => 0,
        'sessions' => [],
        'message'  => 'No active Unlimited sessions to terminate.',
    ]);
    exit;
}

// ── Process each eligible session ────────────────────────────────────────────
$ended   = [];
$errors  = [];

foreach ($sessions as $session) {
    $sessionId    = (int)$session['session_id'];
    $unlimitedRate = (float)$session['unlimited_rate'];

    // Duration = session start → midnight (in minutes)
    $startDt   = new DateTime($session['start_time'], $tz);
    $duration  = (int) floor(($midnight->getTimestamp() - $startDt->getTimestamp()) / 60);
    if ($duration < 0) $duration = 0;

    // Total cost = unlimited flat rate (always ₱400, already paid upfront)
    $totalCost = $unlimitedRate;

    // Check for approved extras (should be rare for unlimited, but handle anyway)
    $extStmt = $conn->prepare(
        "SELECT COALESCE(SUM(extra_cost), 0) AS extras
           FROM additional_requests
          WHERE session_id = ? AND status = 'approved'"
    );
    $extStmt->bind_param('i', $sessionId);
    $extStmt->execute();
    $extras = (float)$extStmt->get_result()->fetch_assoc()['extras'];
    // Note: extras are NOT added to total_cost for unlimited — the ₱400 flat rate
    // is the session cost. Extras would have been collected separately via Pay modal.
    // We intentionally keep total_cost = unlimited_rate to maintain billing consistency.

    $conn->begin_transaction();
    try {
        // 1. Close the session at midnight
        $upd = $conn->prepare(
            "UPDATE gaming_sessions
                SET end_time         = ?,
                    duration_minutes = ?,
                    total_cost       = ?,
                    status           = 'completed'
              WHERE session_id = ? AND status = 'active'"
        );
        $upd->bind_param('sidi', $midnightStr, $duration, $totalCost, $sessionId);
        $upd->execute();

        if ($upd->affected_rows === 0) {
            // Already ended by staff between fetch and update — skip silently
            $conn->rollback();
            continue;
        }

        // 2. Mark console available
        updateConsoleStatus((int)$session['console_id'], 'available');

        // 3. Restore rented controller (if any)
        $ctrlChk = $conn->prepare(
            "SELECT COUNT(*) AS n FROM additional_requests
              WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'"
        );
        $ctrlChk->bind_param('i', $sessionId);
        $ctrlChk->execute();
        $hadCtrl = (int)$ctrlChk->get_result()->fetch_assoc()['n'];
        if ($hadCtrl > 0) {
            $conn->query(
                "UPDATE controllers SET status = 'available'
                  WHERE status = 'in_use' AND controller_type = 'Xbox Controller'
                  ORDER BY unit_number ASC LIMIT 1"
            );
        }

        $conn->commit();

        $ended[] = [
            'session_id'       => $sessionId,
            'customer'         => $session['customer_name'],
            'unit'             => $session['unit_number'],
            'duration_minutes' => $duration,
            'total_cost'       => $totalCost,
            'end_time'         => $midnightStr,
        ];

        logActivity($_SESSION['user_id'], "Auto-End Session", "Auto-ended Unlimited Session #{$sessionId} for {$session['customer_name']} on Console: {$session['unit_number']} at 12:00 AM");

    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = 'Session #' . $sessionId . ': ' . $e->getMessage();
    }
}

echo json_encode([
    'success'  => count($errors) === 0,
    'ended'    => count($ended),
    'sessions' => $ended,
    'errors'   => $errors,
    'message'  => count($ended) > 0
        ? count($ended) . ' Unlimited session(s) automatically ended at closing time (12:00 AM).'
        : 'No sessions were terminated.',
]);
