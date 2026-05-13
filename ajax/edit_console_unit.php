<?php
/**
 * ajax/edit_console_unit.php — AJAX Handler for Editing Console Units
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$console_id = (int)($_POST['console_id'] ?? 0);
$name       = trim($_POST['console_name'] ?? '');
$type_id    = (int)($_POST['console_type_id'] ?? 0);
$unit       = trim($_POST['unit_number'] ?? '');
$ctrl_count = (int)($_POST['controller_count'] ?? 2);

if (!$console_id || !$name || !$type_id || !$unit) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

// 1. Check for duplicate unit number
$dup = $conn->prepare("SELECT console_id FROM consoles WHERE unit_number = ? AND console_id != ?");
$dup->bind_param('si', $unit, $console_id);
$dup->execute();
if ($dup->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Unit number "' . $unit . '" is already in use by another console.']);
    exit;
}

// 2. Perform update
$stmt = $conn->prepare(
    "UPDATE consoles SET console_name = ?, console_type_id = ?, unit_number = ?, controller_count = ? WHERE console_id = ?"
);
$stmt->bind_param('sisii', $name, $type_id, $unit, $ctrl_count, $console_id);

if ($stmt->execute()) {
    // Log activity
    logActivity($_SESSION['user_id'], "Edit Console", "Updated Console ID #{$console_id}: {$name} ({$unit})");
    
    // Fetch fresh data for UI update (join with types for type name)
    $fresh = $conn->prepare(
        "SELECT c.*, ct.type_name AS console_type, ct.hourly_rate AS effective_rate 
         FROM consoles c 
         LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id 
         WHERE c.console_id = ?"
    );
    $fresh->bind_param('i', $console_id);
    $fresh->execute();
    $data = $fresh->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Console updated successfully.',
        'data'    => $data
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $conn->error]);
}
