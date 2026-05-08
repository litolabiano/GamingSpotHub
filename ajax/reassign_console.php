<?php
/**
 * ajax/reassign_console.php — Reassign Console for an Active Session
 * ─────────────────────────────────────────────────────────────────────
 * Allows admin/shopkeeper to move an active gaming session to a different
 * console unit directly from the sessions table.
 *
 * POST params:
 *   session_id   int   — the active session to reassign
 *   console_id   int   — the new console unit to assign
 *
 * Behaviour:
 *   - Validates the session is active and the new console is available
 *   - Updates gaming_sessions.console_id
 *   - Sets old console status back to 'available'
 *   - Sets new console status to 'in_use'
 *   - Returns new unit_number and console_name for UI update
 *
 * Returns JSON: { success, message, unit_number, console_name }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$session_id = (int)($_POST['session_id'] ?? 0);
$console_id = (int)($_POST['console_id'] ?? 0);

if (!$session_id || !$console_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// ── Load the active session ───────────────────────────────────────────────────
$sessStmt = $conn->prepare(
    "SELECT gs.session_id, gs.console_id AS old_console_id, gs.status,
            c.unit_number AS old_unit
       FROM gaming_sessions gs
       JOIN consoles c ON gs.console_id = c.console_id
      WHERE gs.session_id = ?"
);
$sessStmt->bind_param('i', $session_id);
$sessStmt->execute();
$session = $sessStmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Session not found.']);
    exit;
}

if ((int)$session['old_console_id'] === $console_id) {
    echo json_encode(['success' => false, 'message' => 'Console is already assigned to this session.']);
    exit;
}

// ── Validate the new console is available ────────────────────────────────────
$conStmt = $conn->prepare(
    "SELECT console_id, unit_number, console_name, status FROM consoles WHERE console_id = ?"
);
$conStmt->bind_param('i', $console_id);
$conStmt->execute();
$newConsole = $conStmt->get_result()->fetch_assoc();

if (!$newConsole) {
    echo json_encode(['success' => false, 'message' => 'Console unit not found.']);
    exit;
}

if ($session['status'] === 'active' && $newConsole['status'] !== 'available') {
    echo json_encode([
        'success' => false,
        'message' => $newConsole['unit_number'] . ' is currently ' . $newConsole['status'] . ' and cannot be assigned to an active session.',
    ]);
    exit;
}

// ── Perform the reassignment in a transaction ─────────────────────────────────
$conn->begin_transaction();
try {
    // 1. Update session to point to new console
    $upd = $conn->prepare(
        "UPDATE gaming_sessions SET console_id = ? WHERE session_id = ?"
    );
    $upd->bind_param('ii', $console_id, $session_id);
    $upd->execute();

    if ($upd->affected_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Session update failed.']);
        exit;
    }

    // Only update console statuses if the session is currently active
    if ($session['status'] === 'active') {
        // 2. Release old console
        $rel = $conn->prepare("UPDATE consoles SET status = 'available' WHERE console_id = ?");
        $rel->bind_param('i', $session['old_console_id']);
        $rel->execute();

        // 3. Mark new console as in_use
        $mark = $conn->prepare("UPDATE consoles SET status = 'in_use' WHERE console_id = ?");
        $mark->bind_param('i', $console_id);
        $mark->execute();
    }

    $conn->commit();

    logActivity($_SESSION['user_id'], "Reassign Console", "Reassigned Session #{$session_id} from Unit {$session['old_unit']} to Unit {$newConsole['unit_number']}");

    echo json_encode([
        'success'      => true,
        'message'      => 'Console reassigned from ' . $session['old_unit'] . ' to ' . $newConsole['unit_number'] . '.',
        'unit_number'  => $newConsole['unit_number'],
        'console_name' => $newConsole['console_name'],
        'console_id'   => $console_id,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
