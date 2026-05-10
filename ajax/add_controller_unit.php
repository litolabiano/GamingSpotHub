<?php
/**
 * ajax/add_controller_unit.php — AJAX Handler for Adding Controller Units
 */
ob_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/session_helper.php';
    require_once __DIR__ . '/../includes/db_config.php';
    require_once __DIR__ . '/../includes/db_functions.php';

    if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $ctrl_typeId = !empty($_POST['controller_type_id']) ? (int)$_POST['controller_type_id'] : null;
    $ctrl_unit   = trim($_POST['ctrl_unit_number'] ?? '');
    $ctrl_notes  = trim($_POST['controller_notes'] ?? '');
    $hourly_rate = (float)($_POST['hourly_rate'] ?? 20.00);
    $console_typeId = !empty($_POST['console_type_id']) ? (int)$_POST['console_type_id'] : null;

    if (!$ctrl_typeId || !$ctrl_unit) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Unit number and type are required.']);
        exit;
    }

    // 1. Validate Controller Type
    $validTypes = getControllerTypes(true);
    $typeRow = null;
    foreach ($validTypes as $t) {
        if ((int)$t['controller_type_id'] === $ctrl_typeId) {
            $typeRow = $t;
            break;
        }
    }

    if (!$typeRow) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid controller type selected.']);
        exit;
    }

    // Ensure we have the correct console_type_id
    if (!$console_typeId) {
        $console_typeId = $typeRow['console_type_id'];
    }

    // 2. Check for duplicate unit number
    $dup = $conn->prepare("SELECT controller_id FROM controllers WHERE unit_number = ?");
    $dup->bind_param('s', $ctrl_unit);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Unit number "' . $ctrl_unit . '" is already in use.']);
        exit;
    }

    // 3. Perform Insert
    $stmt = $conn->prepare(
        "INSERT INTO controllers (controller_type_id, console_type_id, unit_number, hourly_rate, notes, status) 
         VALUES (?, ?, ?, ?, ?, 'available')"
    );
    $stmt->bind_param('iisds', $ctrl_typeId, $console_typeId, $ctrl_unit, $hourly_rate, $ctrl_notes);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        logActivity($_SESSION['user_id'], "Add Controller", "Added new controller: {$ctrl_unit} ({$typeRow['type_name']})");

        // Fetch fresh stats for UI update
        $statsRes = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) AS available FROM controllers WHERE status != 'archived'");
        $stats = $statsRes ? $statsRes->fetch_assoc() : ['total' => 0, 'available' => 0];

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Controller added successfully.',
            'data' => [
                'controller_id' => $newId,
                'unit_number' => $ctrl_unit,
                'type_name' => $typeRow['type_name'],
                'hourly_rate' => $hourly_rate,
                'notes' => $ctrl_notes,
                'status' => 'available',
                'stats' => $stats
            ]
        ]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Server Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
