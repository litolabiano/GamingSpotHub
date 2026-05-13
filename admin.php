<?php
/**
 * Good Spot Gaming Hub - Admin Dashboard
 * Live database-connected management panel for Owner & Shopkeeper roles.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/includes/db_functions.php';

// Prevent back-button access after logout by disabling browser cache
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

$user = getCurrentUser();
$consoleTypes         = getConsoleTypes(true);              // active console types (console_types table)
$controllerTypes      = getControllerTypes(true);           // active controller types (controller_types table)
$archivedConsoleTypes = array_filter(getConsoleTypes(false),    fn($ct) => $ct['is_archived'] == 1);
$archivedCtrlTypes    = array_filter(getControllerTypes(false), fn($ct) => $ct['is_archived'] == 1);
$message = '';
$messageType = '';

// ------------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // â”€â”€ CSRF guard - all admin POST actions require a valid token â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if (!verifyCsrf($message, $messageType)) {
        // verifyCsrf() has already populated $message/$messageType; skip all actions
        $action = '';
    }

    // START SESSION
    if ($action === 'start_session') {
        $user_id         = (int)($_POST['user_id'] ?? 0);
        $console_id      = (int)($_POST['console_id'] ?? 0);
        $rental_mode     = $_POST['rental_mode'] ?? '';
        $planned_minutes = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;
        $start_payment_method = $_POST['start_payment_method'] ?? 'cash';
        $unlim_rate      = (float)(getSetting('unlimited_rate') ?? 400);

        if (!$console_id || !in_array($rental_mode, ['hourly','open_time','unlimited'])) {
            $message = 'Please select a console and rental mode.';
            $messageType = 'error';
        } elseif ($rental_mode === 'unlimited' && (!isset($_POST['unlimited_tendered']) || (float)$_POST['unlimited_tendered'] < $unlim_rate)) {
            $message = 'Payment of ₱' . number_format($unlim_rate, 2) . ' is required upfront for Unlimited sessions. Please ensure sufficient amount is tendered.';
            $messageType = 'error';
        } elseif ($rental_mode === 'hourly' && (!$planned_minutes || $planned_minutes <= 0)) {
            $message = 'Please select a duration for the hourly session.';
            $messageType = 'error';
        } elseif ($rental_mode === 'hourly' && $planned_minutes > getPricingRules()['max_hourly_minutes']) {
            $pr = getPricingRules();
            $message = 'Hourly sessions are capped at ' . ($pr['max_hourly_minutes'] / 60) . ' hours. Use Unlimited mode (flat ₱' . getSetting('unlimited_rate') . ') for longer sessions.';
            $messageType = 'error';
        } else {
            if (!$skip_start_session) {
                $result = startSession($user_id, $console_id, $rental_mode, $user['user_id'], $planned_minutes);
                if ($result['success']) {
                    $controller_upfront_addon = 0.0;

                    if (!empty($_POST['controller_rental']) && $_POST['controller_rental'] === '1') {
                        $rented_1    = (int)($_POST['rented_controller_id'] ?? 0);
                        $rented_2    = (int)($_POST['rented_controller_id_2'] ?? 0);
                        $ctrl_count  = (int)($_POST['controller_count'] ?? 1);

                        $ids = [];
                        if ($rented_1 > 0) $ids[] = $rented_1;
                        if ($ctrl_count > 1 && $rented_2 > 0) $ids[] = $rented_2;

                        if (!empty($ids)) {
                            // Controllers are now exclusively Open Time (billed at end of session)
                            $desc = 'Controller rental (IDs: ' . implode(', ', $ids) . ') [OT_IDS:' . implode(',', $ids) . ']';
                            $arStmt = $conn->prepare(
                                "INSERT INTO additional_requests (session_id, request_type, description, extra_cost, status)
                                 VALUES (?, 'controller_rental', ?, 0.00, 'approved')"
                            );
                            $arStmt->bind_param('is', $result['session_id'], $desc);
                            $arStmt->execute();

                            foreach ($ids as $cid) {
                                $ctrlUpd = $conn->prepare("UPDATE controllers SET status = 'in_use' WHERE controller_id = ? AND status = 'available'");
                                $ctrlUpd->bind_param('i', $cid);
                                $ctrlUpd->execute();
                            }
                        }
                    }

                    if ($rental_mode === 'unlimited') {
                        $unlimited_payment = $_POST['unlimited_payment_method'] ?? 'cash';
                        $upfront_cost      = $unlim_rate;
                        if (!empty($_POST['controller_rental']) && $_POST['controller_rental'] == '1') {
                            $upfront_cost += $controller_upfront_addon;
                        }
                        $tendered          = (float)$_POST['unlimited_tendered'];

                        recordTransaction(
                            $result['session_id'], $user_id, $upfront_cost, $unlimited_payment, $user['user_id'],
                            $tendered,
                            0,
                            null
                        );
                        $cost = number_format($upfront_cost, 2);
                        $message = "Session #" . $result['session_id'] . " started. ₱{$cost} flat rate collected via " . ucfirst($unlimited_payment) . ".";

                    } elseif ($rental_mode === 'hourly' && isset($_POST['collect_upfront']) && $planned_minutes) {
                        $pr           = getPricingRules();
                        $upfront_cost = computeHourlySessionBaseCost(paidToTotalMinutes($planned_minutes));
                        if (!empty($_POST['controller_rental']) && $_POST['controller_rental'] == '1') {
                            $upfront_cost += $controller_upfront_addon;
                        }
                        $tendered     = isset($_POST['start_tendered']) ? (float)$_POST['start_tendered'] : null;
                        $shortfall    = ($tendered !== null && $tendered < $upfront_cost) ? $upfront_cost - $tendered : null;

                        $actualCollected = ($tendered !== null) ? min((float)$tendered, $upfront_cost) : $upfront_cost;

                        recordTransaction(
                            $result['session_id'], $user_id, $actualCollected, $start_payment_method, $user['user_id'],
                            $tendered,
                            $shortfall,
                            $shortfall ? 'Short payment at session start - short by ₱' . number_format($shortfall, 2) : null
                        );
                        $cost      = number_format($upfront_cost, 2);
                        if ($shortfall !== null && $shortfall > 0) {
                            $tendFmt  = number_format($tendered, 2);
                            $shortFmt = number_format($shortfall, 2);
                            $message  = "Session #" . $result['session_id'] . " started. ₱{$tendFmt} collected upfront via "
                                      . ucfirst($start_payment_method) . " (short by ₱{$shortFmt}).";
                            $messageType = 'warning';
                        } else {
                            $message = "Session #" . $result['session_id'] . " started. ₱{$cost} collected upfront via " . ucfirst($start_payment_method) . ".";
                        }

                    } else {
                        $message = 'Session #' . $result['session_id'] . ' started. Payment will be collected at the end.';
                    }

                    // Activity Log
                    $custLabel = $user_id > 0 ? ("User #" . $user_id) : "Walk-in";
                    $logDet = "Started Session #{$result['session_id']} for {$custLabel}. Console: " . ($_POST['unit_number'] ?? 'Unknown') . ". Mode: " . ucfirst($rental_mode);
                    if ($rental_mode === 'hourly') $logDet .= " ({$planned_minutes} min)";
                    logActivity($user['user_id'], "Start Session", $logDet);

                    if (!$messageType) $messageType = 'success';
                } else {
                    $message = 'Could not start session: ' . $result['message'];
                    $messageType = 'error';
                }
            }
        }
    }

    // END SESSION + RECORD OUTSTANDING BALANCE

    elseif ($action === 'end_session') {
        $session_id      = (int)($_POST['session_id'] ?? 0);
        $payment_method  = $_POST['payment_method'] ?? 'cash';
        // Tendered amount entered by the cashier in the modal (may be 0 or less than due)
        $tendered_raw    = $_POST['tendered_amount'] ?? '';
        $tendered_amount = ($tendered_raw !== '') ? (float)$tendered_raw : null;

        if (!$session_id) {
            $message = 'Invalid session ID.';
            $messageType = 'error';
        } else {
            $result = endSession($session_id);
            if ($result['success']) {
                // How much has already been paid (e.g. upfront for hourly/unlimited)
                $paidStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS paid FROM transactions WHERE session_id = ?");
                $paidStmt->bind_param('i', $session_id);
                $paidStmt->execute();
                $alreadyPaid = (float)$paidStmt->get_result()->fetch_assoc()['paid'];

                $remaining = round($result['total_cost'] - $alreadyPaid, 2);

                // Fetch user_id and console unit
                $stmt = $conn->prepare("SELECT gs.user_id, c.unit_number FROM gaming_sessions gs JOIN consoles c ON gs.console_id = c.console_id WHERE gs.session_id = ?");
                $stmt->bind_param('i', $session_id);
                $stmt->execute();
                $sess_row = $stmt->get_result()->fetch_assoc();

                // Determine shortfall
                $shortfall   = null;
                $paymentNote = null;
                $actualCollected = $remaining; // default: assume full payment

                if ($tendered_amount !== null && $remaining > 0) {
                    if ($tendered_amount < $remaining) {
                        // Short payment - record only what was tendered
                        $actualCollected = $tendered_amount;
                        $shortfall       = round($remaining - $tendered_amount, 2);
                        $paymentNote     = 'Short payment - collected ₱' . number_format($tendered_amount, 2)
                                         . ', short by ₱' . number_format($shortfall, 2);
                    } else {
                        $paymentNote = 'Balance payment collected at session end';
                    }
                }

                if ($sess_row && $remaining > 0 && $actualCollected > 0) {
                    recordTransaction(
                        $session_id, $sess_row['user_id'], $actualCollected, $payment_method,
                        $user['user_id'], $tendered_amount, $shortfall, $paymentNote
                    );
                }

                $mins  = $result['duration_minutes'];
                $total = number_format($result['total_cost'], 2);
                $paid  = number_format($alreadyPaid, 2);

                if ($shortfall !== null && $shortfall > 0) {
                    $shortFmt    = number_format($shortfall, 2);
                    $tenderedFmt = number_format($tendered_amount, 2);
                    $message     = "Session ended. Total: ₱{$total}. Collected ₱{$tenderedFmt} - still ₱{$shortFmt} outstanding.";
                    $messageType = 'warning';
                } elseif ($remaining > 0) {
                    $due     = number_format($remaining, 2);
                    $message = "Session ended. Duration: {$mins} min. Total: ₱{$total} (prepaid ₱{$paid} + collected ₱{$due}).";
                    $messageType = 'success';
                } else {
                    $message     = "Session ended. Duration: {$mins} min. Total: ₱{$total}. Fully paid upfront - no extra charge.";
                    $messageType = 'success';
                }

                // Activity Log
                $logDet = "Ended Session #{$session_id}. Console: " . ($sess_row['unit_number'] ?? 'Unknown') . ". Duration: {$mins} min. Total Cost: ₱{$total}.";
                logActivity($user['user_id'], "End Session", $logDet);
            } else {
                $message     = 'Could not end session: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }



    // UPDATE CONSOLE STATUS
    elseif ($action === 'update_console_status') {
        $console_id = (int)($_POST['console_id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        $allowed    = ['available', 'in_use', 'maintenance', 'archived'];
        if ($console_id && in_array($status, $allowed)) {
            updateConsoleStatus($console_id, $status);
            $message = 'Console status updated.';
            $messageType = 'success';
            
            // Activity Log
            logActivity($user['user_id'], "Console Status", "Updated Console ID #{$console_id} status to " . ucfirst($status));
        }
    }
    elseif ($action === 'add_console') {
        $name = trim($_POST['console_name'] ?? '');
        $type_id = (int)($_POST['console_type_id'] ?? 0);
        $unit_number = trim($_POST['unit_number'] ?? '');
        
        if ($name && $type_id && $unit_number) {
            // â”€â”€ DUPLICATE CHECK: Ensure Unit Number is unique â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $checkStmt = $conn->prepare("SELECT console_id FROM consoles WHERE unit_number = ?");
            $checkStmt->bind_param("s", $unit_number);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = 'Failed to add console. Unit number "' . htmlspecialchars($unit_number) . '" is already in use.';
                $messageType = 'error';
            } else {
                $controller_count = (int)($_POST['controller_count'] ?? 2);
                if (addConsole($name, $type_id, $unit_number, $controller_count)) {
                    $message = 'Console added successfully.';
                    $messageType = 'success';
                    
                    // Activity Log
                    logActivity($user['user_id'], "Add Console", "Added new console: {$name}, Unit: {$unit_number}");
                } else {
                    $message = 'Failed to add console. An unexpected database error occurred.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Invalid input for new console.';
            $messageType = 'error';
        }
    }
    elseif ($action === 'edit_console') {
        $console_id = (int)($_POST['console_id'] ?? 0);
        $name       = trim($_POST['console_name'] ?? '');
        $type_id    = (int)($_POST['console_type_id'] ?? 0);
        $unit       = trim($_POST['unit_number'] ?? '');
    
        if ($console_id && $name && $type_id && $unit) {
            // Check for duplicate unit number (exclude current console)
            $dupCheck = $conn->prepare(
                "SELECT console_id FROM consoles WHERE unit_number = ? AND console_id != ?"
            );
            $dupCheck->bind_param('si', $unit, $console_id);
            $dupCheck->execute();
            if ($dupCheck->get_result()->num_rows > 0) {
                $message     = 'Unit number "' . htmlspecialchars($unit) . '" is already used by another console.';
                $messageType = 'error';
            } else {
                $controller_count = (int)($_POST['controller_count'] ?? 2);

                $stmt = $conn->prepare(
                    "UPDATE consoles SET console_name = ?, console_type_id = ?, unit_number = ?, controller_count = ? WHERE console_id = ?"
                );
                $stmt->bind_param('sisii', $name, $type_id, $unit, $controller_count, $console_id);
                if ($stmt->execute()) {
                    $message     = 'Console updated successfully.';
                    $messageType = 'success';

                    // Activity Log
                    logActivity($user['user_id'], "Edit Console", "Updated Console ID #{$console_id}: {$name}, Unit: {$unit}");
                } else {
                    $message     = 'Failed to update console: ' . $conn->error;
                    $messageType = 'error';
                }
            }
        } else {
            $message     = 'Invalid input for console update.';
            $messageType = 'error';
        }
    }

    elseif ($action === 'delete_console') {
        $console_id = (int)($_POST['console_id'] ?? 0);
        if ($console_id) {
            $res = deleteConsole($console_id);
            if ($res['success']) {
                $message = 'Console deleted permanently.';
                $messageType = 'success';
                
                // Activity Log with detailed info
                $cName = $res['console_name'] ?? 'Unknown';
                $cUnit = $res['unit_number'] ?? '#'.$console_id;
                $cType = $res['console_type'] ?? 'Unknown Type';
                logActivity($user['user_id'], "Delete Console", "Permanently deleted Console: {$cName} (Unit: {$cUnit}, Type: {$cType})");
            } else {
                $message = $res['message'] ?? 'Cannot delete console.';
                $messageType = 'error';
            }
        }
    }
    // â”€â”€ CONSOLE TYPE ACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // ADD CONSOLE TYPE
    elseif ($action === 'add_console_type') {
        $typeName = trim($_POST['type_name'] ?? '');
        $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
        if ($typeName && $hourlyRate >= 0) {
            if (addConsoleType($typeName, $hourlyRate)) {
                $message = 'Console type "' . htmlspecialchars($typeName) . '" added successfully.';
                $messageType = 'success';
                logActivity($user['user_id'], "Console Type", "Added new console type: {$typeName}");
            } else {
                $message = 'Failed to add console type. It might already exist.';
                $messageType = 'error';
            }
        }
    }

    // EDIT CONSOLE TYPE
    elseif ($action === 'edit_console_type') {
        $typeId = (int)($_POST['console_type_id'] ?? 0);
        $typeName = trim($_POST['type_name'] ?? '');
        $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
        if ($typeId && $typeName && $hourlyRate >= 0) {
            $stmt = $conn->prepare("UPDATE console_types SET type_name = ?, hourly_rate = ? WHERE console_type_id = ?");
            $stmt->bind_param('sdi', $typeName, $hourlyRate, $typeId);
            if ($stmt->execute()) {
                $message = 'Console type updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update console type: ' . $conn->error;
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid input for console type update.';
            $messageType = 'error';
        }
    }

    // ARCHIVE CONSOLE TYPE
    elseif ($action === 'archive_console_type') {
        $typeId = (int)($_POST['console_type_id'] ?? 0);
        if ($typeId && archiveConsoleType($typeId)) {
            $message = 'Console type archived. Associated consoles have been moved to the Archive section.';
            $messageType = 'success';
            logActivity($user['user_id'], "Console Type", "Archived Console Type ID #{$typeId}");
        } else {
            $message = 'Failed to archive console type.';
            $messageType = 'error';
        }
    }

    // RESTORE CONSOLE TYPE
    elseif ($action === 'restore_console_type') {
        $typeId = (int)($_POST['console_type_id'] ?? 0);
        if ($typeId && restoreConsoleType($typeId)) {
            $message = 'Console type restored successfully.';
            $messageType = 'success';
            logActivity($user['user_id'], "Console Type", "Restored Console Type ID #{$typeId}");
        } else {
            $message = 'Failed to restore console type.';
            $messageType = 'error';
        }
    }

    // PERMANENTLY DELETE CONSOLE TYPE
    elseif ($action === 'delete_console_type') {
        $typeId = (int)($_POST['console_type_id'] ?? 0);
        if ($typeId && deleteConsoleType($typeId)) {
            $message = 'Console type permanently removed.';
            $messageType = 'success';
            logActivity($user['user_id'], "Console Type", "Permanently deleted Console Type ID #{$typeId}");
        } else {
            $message = 'Failed to permanently delete console type.';
            $messageType = 'error';
        }
    }

    // â”€â”€ CONTROLLER TYPE ACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // ADD CONTROLLER TYPE
    elseif ($action === 'add_controller_type') {
        $typeName      = trim($_POST['type_name'] ?? '');
        $consoleTypeId = !empty($_POST['console_type_id']) ? (int)$_POST['console_type_id'] : null;
        if ($typeName) {
            if (addControllerType($typeName, $consoleTypeId)) {
                $parentNote = '';
                if ($consoleTypeId) {
                    $pRes = $conn->prepare("SELECT type_name FROM console_types WHERE console_type_id = ?");
                    $pRes->bind_param('i', $consoleTypeId); $pRes->execute();
                    $pRow = $pRes->get_result()->fetch_assoc();
                    if ($pRow) $parentNote = ' (for ' . htmlspecialchars($pRow['type_name']) . ')';
                }
                $message = 'Controller type "' . htmlspecialchars($typeName) . '"' . $parentNote . ' added.';
                $messageType = 'success';
                logActivity($user['user_id'], "Controller Type", "Added new controller type: {$typeName}{$parentNote}");
            } else {
                $message = 'Failed to add controller type. It might already exist.';
                $messageType = 'error';
            }
        }
    }

    // ARCHIVE CONTROLLER TYPE
    elseif ($action === 'archive_controller_type') {
        $typeId = (int)($_POST['controller_type_id'] ?? 0);
        if ($typeId && archiveControllerType($typeId)) {
            $message = 'Controller type archived successfully.';
            $messageType = 'success';
            logActivity($user['user_id'], "Controller Type", "Archived Controller Type ID #{$typeId}");
        } else {
            $message = 'Failed to archive controller type.';
            $messageType = 'error';
        }
    }

    // RESTORE CONTROLLER TYPE
    elseif ($action === 'restore_controller_type') {
        $typeId = (int)($_POST['controller_type_id'] ?? 0);
        if ($typeId && restoreControllerType($typeId)) {
            $message = 'Controller type restored successfully.';
            $messageType = 'success';
            logActivity($user['user_id'], "Controller Type", "Restored Controller Type ID #{$typeId}");
        } else {
            $message = 'Failed to restore controller type.';
            $messageType = 'error';
        }
    }

    // PERMANENTLY DELETE CONTROLLER TYPE
    elseif ($action === 'delete_controller_type') {
        $typeId = (int)($_POST['controller_type_id'] ?? 0);
        if ($typeId && deleteControllerType($typeId)) {
            $message = 'Controller type permanently removed.';
            $messageType = 'success';
            logActivity($user['user_id'], "Controller Type", "Permanently deleted Controller Type ID #{$typeId}");
        } else {
            $message = 'Failed to permanently delete controller type.';
            $messageType = 'error';
        }
    }

    // â”€â”€ CONTROLLER (UNIT) ACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    elseif ($action === 'add_controller') {
        $ctrl_type   = trim($_POST['controller_type'] ?? '');  // legacy text field
        $ctrl_typeId = !empty($_POST['controller_type_id']) ? (int)$_POST['controller_type_id'] : null;
        $ctrl_unit   = trim($_POST['ctrl_unit_number'] ?? '');
        $ctrl_notes  = trim($_POST['controller_notes'] ?? '');
        $hourly_rate = (float)($_POST['hourly_rate'] ?? 20.00);

        // Validate: must match a real controller type by ID
        $validTypeIds = array_column(getControllerTypes(true), 'controller_type_id');
        $console_typeId = !empty($_POST['console_type_id']) ? (int)$_POST['console_type_id'] : null;

        if ($ctrl_typeId && in_array($ctrl_typeId, $validTypeIds) && $ctrl_unit) {
            // If console_typeId was not passed in hidden field, fetch it from DB
            if (!$console_typeId) {
                $pRes = $conn->prepare("SELECT console_type_id FROM controller_types WHERE controller_type_id = ?");
                $pRes->bind_param('i', $ctrl_typeId); $pRes->execute();
                $pRow = $pRes->get_result()->fetch_assoc();
                if ($pRow) $console_typeId = $pRow['console_type_id'];
            }

            $dupCheck = $conn->prepare("SELECT controller_id FROM controllers WHERE unit_number = ?");
            $dupCheck->bind_param('s', $ctrl_unit); $dupCheck->execute();
            if ($dupCheck->get_result()->num_rows > 0) {
                $message = 'Unit number "' . htmlspecialchars($ctrl_unit) . '" already exists.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO controllers (controller_type_id, console_type_id, unit_number, hourly_rate, notes, status) VALUES (?,?,?,?,?,'available')"
                );
                $stmt->bind_param('iisds', $ctrl_typeId, $console_typeId, $ctrl_unit, $hourly_rate, $ctrl_notes);
                if ($stmt->execute()) {
                    $message = 'Controller added successfully.';
                    $messageType = 'success';
                    logActivity($user['user_id'], "Controller unit", "Added new controller: {$ctrl_unit} (ID: {$ctrl_typeId})");
                } else {
                    $message = 'Failed to add controller: ' . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
            $dupCheck->close();
        } else {
            $message = 'Invalid input for new controller. Ensure all fields are filled and a valid controller type is selected.';
            $messageType = 'error';
        }
    }
    elseif ($action === 'edit_controller') {
        $ctrl_id     = (int)($_POST['controller_id'] ?? 0);
        $ctrl_unit   = trim($_POST['ctrl_unit_number'] ?? '');
        $ctrl_notes  = trim($_POST['controller_notes'] ?? '');
        $hourly_rate = (float)($_POST['hourly_rate'] ?? 20.00);

        if ($ctrl_id && $ctrl_unit) {
            $dupCheck = $conn->prepare("SELECT controller_id FROM controllers WHERE unit_number = ? AND controller_id != ?");
            $dupCheck->bind_param('si', $ctrl_unit, $ctrl_id); 
            $dupCheck->execute();
            if ($dupCheck->get_result()->num_rows > 0) {
                $message = 'Unit number "' . htmlspecialchars($ctrl_unit) . '" already exists.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare(
                    "UPDATE controllers SET unit_number=?, hourly_rate=?, notes=? WHERE controller_id=?"
                );
                $stmt->bind_param('sdsi', $ctrl_unit, $hourly_rate, $ctrl_notes, $ctrl_id);
                if ($stmt->execute()) {
                    $message = 'Controller updated successfully.';
                    $messageType = 'success';
                    logActivity($user['user_id'], "Controller unit", "Edited controller ID #{$ctrl_id}");
                } else {
                    $message = 'Failed to update controller: ' . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
            $dupCheck->close();
        } else {
            $message = 'Invalid input for controller update.';
            $messageType = 'error';
        }
    }
    elseif ($action === 'update_controller_status') {
        $ctrl_id = (int)($_POST['controller_id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        $allowed = ['available', 'in_use', 'maintenance', 'archived'];
        // Archive is owner-only
        if ($status === 'archived' && $user['role'] !== 'owner') {
            $message = 'Only the owner can archive controllers.';
            $messageType = 'error';
        } elseif ($ctrl_id && in_array($status, $allowed)) {
            $stmt = $conn->prepare("UPDATE controllers SET status=? WHERE controller_id=?");
            $stmt->bind_param('si', $status, $ctrl_id);
            $stmt->execute();
            $stmt->close();
            $message = 'Controller status updated.';
            $messageType = 'success';
            logActivity($user['user_id'], "Controller Status", "Updated Controller ID #{$ctrl_id} status to " . ucfirst($status));
        }
    }
    elseif ($action === 'delete_controller') {
        if ($user['role'] !== 'owner') {
            $message = 'Only the owner can permanently delete controllers.';
            $messageType = 'error';
        } else {
            $ctrl_id = (int)($_POST['controller_id'] ?? 0);
            if ($ctrl_id) {
                $stmt = $conn->prepare("DELETE FROM controllers WHERE controller_id=?");
                $stmt->bind_param('i', $ctrl_id);
                $stmt->execute();
                $stmt->close();
                $message = 'Controller deleted permanently.';
                $messageType = 'success';
                logActivity($user['user_id'], "Controller Unit", "Permanently deleted Controller ID #{$ctrl_id}");
            }
        }
    }

    // SAVE SETTINGS —” owner only
    elseif ($action === 'save_settings') {
        if ($user['role'] !== 'owner') {
            $message = 'Access denied. Only the owner can change settings.';
            $messageType = 'error';
        } else {
        $keys = ['unlimited_rate',
                 'business_hours_open','business_hours_close','shop_phone','contact_email',
                 'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge',
                 'brevo_api_key','sender_email','base_url'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                updateSetting($key, trim($_POST[$key]));
            }
        }

        $message = 'Settings saved successfully.';
        $messageType = 'success';
        
        // Activity Log
        logActivity($user['user_id'], "System Settings", "Updated system settings and synced console rates.");
        } // end owner check
    }

    // CONFIRM RESERVATION
    elseif ($action === 'confirm_reservation') {
        $res_id    = (int)($_POST['reservation_id'] ?? 0);
        $console_id = (int)($_POST['console_id'] ?? 0) ?: null;
        if ($res_id) {
            updateReservationStatus($res_id, 'reserved', $console_id ?: null);
            $message = 'Reservation confirmed.';
            $messageType = 'success';
            
            // Activity Log
            logActivity($user['user_id'], "Confirm Reservation", "Confirmed Reservation #{$res_id}" . ($console_id ? " and assigned Console Unit #{$console_id}" : ""));
        }
    }

    // CANCEL RESERVATION (admin-initiated â†’ cancelled_by = 'admin')
    elseif ($action === 'cancel_reservation') {
        $res_id       = (int)($_POST['reservation_id'] ?? 0);
        $allowedCancelReasons = ['schedule_change','found_alternative','budget_issue',
                                  'technical_issue','emergency','other','admin_decision'];
        $reasonType   = trim($_POST['cancel_reason_type']   ?? '');
        $reasonDetail = trim($_POST['cancel_reason_detail'] ?? '') ?: null;

        if (!$res_id) {
            $message     = 'Invalid reservation ID.';
            $messageType = 'error';
        } elseif (!in_array($reasonType, $allowedCancelReasons)) {
            $message     = 'Please select a valid reason for cancellation.';
            $messageType = 'error';
        } elseif ($reasonType === 'other' && empty($reasonDetail)) {
            $message     = 'Please describe the reason for cancellation.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare(
                "UPDATE reservations
                    SET status               = 'cancelled',
                        cancelled_by         = 'admin'
                  WHERE reservation_id = ?"
            );
            $stmt->bind_param('i', $res_id);
            $stmt->execute();

            // â”€â”€ Log to reservation_cancellations audit table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $logFetch = $conn->prepare(
                "SELECT user_id FROM reservations WHERE reservation_id = ?"
            );
            $logFetch->bind_param('i', $res_id);
            $logFetch->execute();
            $logRow = $logFetch->get_result()->fetch_assoc();
            if ($logRow) {
                $logStmt = $conn->prepare(
                    "INSERT INTO reservation_cancellations
                         (reservation_id, user_id, cancelled_by, cancel_reason_type, cancel_reason_detail, cancelled_at)
                     VALUES (?, ?, 'admin', ?, ?, NOW())"
                );
                $logStmt->bind_param('iiss',
                    $res_id,
                    $logRow['user_id'],
                    $reasonType,
                    $reasonDetail
                );
                $logStmt->execute();
            }

            $message     = 'Reservation #' . $res_id . ' cancelled.';
            $messageType = 'success';

            // Activity Log
            logActivity($user['user_id'], "Cancel Reservation", "Cancelled Reservation #{$res_id}. Reason: " . ucfirst($reasonType) . ($reasonDetail ? " ({$reasonDetail})" : ""));
        }
    }


    // NO-SHOW
    elseif ($action === 'noshow_reservation') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        if ($res_id) {
            updateReservationStatus($res_id, 'no_show');
            $message = 'Marked as no-show.';
            $messageType = 'warning';

            // Activity Log
            logActivity($user['user_id'], "No-Show", "Marked Reservation #{$res_id} as No-Show.");
        }
    }

    // ADD RESERVATION (admin side)
    elseif ($action === 'add_reservation') {
        $uid          = (int)($_POST['user_id'] ?? 0);
        $ctype        = $_POST['console_type'] ?? '';
        $rmode        = $_POST['rental_mode']  ?? '';
        $pmins        = $rmode === 'hourly' ? (int)($_POST['planned_minutes'] ?? 0) : null;
        $rdate        = $_POST['reserved_date'] ?? '';
        $rtime        = $_POST['reserved_time'] ?? '';
        $notes        = trim($_POST['notes'] ?? '');
        $dp_amount    = (float)($_POST['downpayment_amount'] ?? 0);
        $dp_method    = $dp_amount > 0 ? ($_POST['downpayment_method'] ?? null) : null;
        $console_id   = (int)($_POST['console_id'] ?? 0) ?: null;
        
        $controller_id = (int)($_POST['controller_id'] ?? 0) ?: null;
        $controller_id_2 = (int)($_POST['controller_id_2'] ?? 0) ?: null;
        
        $controller_fee = 0.0;
        if ($controller_id || $controller_id_2) {
            $base_fee = (float)getSetting('extra_controller_fee');
            if ($controller_id) $controller_fee += $base_fee;
            if ($controller_id_2) $controller_fee += $base_fee;
        }

        if ($uid && $ctype && $rmode && $rdate && $rtime) {
            $result = createReservation($uid, $ctype, $rmode, $pmins, $rdate, $rtime,
                                        $notes ?: null, $dp_amount, $dp_method, $console_id,
                                        null, $controller_id, $controller_id_2, $controller_fee);
            $message     = $result['success'] ? 'Reservation added.' : 'Error: ' . $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';

            if ($result['success']) {
                $logMsg = "Created new Reservation #{$result['reservation_id']} for User #{$uid} on {$rdate} {$rtime}. Console Type: {$ctype}";
                if ($console_id) $logMsg .= " (Console Unit #{$console_id})";
                if ($controller_id) $logMsg .= " w/ Controller 1 (#$controller_id)";
                if ($controller_id_2) $logMsg .= " w/ Controller 2 (#$controller_id_2)";
                logActivity($user['user_id'], "Add Reservation", $logMsg);
            }
        } else {
            $message = 'Please fill in all required fields.';
            $messageType = 'error';
        }
    }

    // BLOCK DATE
    elseif ($action === 'block_date') {
        if ($user['role'] !== 'owner') {
            $message = 'Only the owner can block dates.';
            $messageType = 'error';
        } else {
            $date   = $_POST['blocked_date'] ?? '';
            $reason = trim($_POST['reason'] ?? '');
            if ($date) {
                if (blockDate($date, $reason)) {
                    $message     = 'Date blocked: ' . date('M d, Y', strtotime($date));
                    $messageType = 'success';

                    logActivity($user['user_id'], "Block Date", "Blocked system for date: {$date}. Reason: {$reason}");
                } else {
                    $message     = 'Failed to block date or date already blocked.';
                    $messageType = 'error';
                }
            }
        }
    }
    // UNBLOCK DATE
    elseif ($action === 'unblock_date') {
        if ($user['role'] !== 'owner') {
            $message = 'Only the owner can unblock dates.';
            $messageType = 'error';
        } else {
            $date = $_POST['blocked_date'] ?? '';
            if ($date) {
                if (unblockDate($date)) {
                    $message     = 'Date unblocked: ' . date('M d, Y', strtotime($date));
                    $messageType = 'success';

                    logActivity($user['user_id'], "Unblock Date", "Unblocked system for date: {$date}");
                } else {
                    $message     = 'Failed to unblock date.';
                    $messageType = 'error';
                }
            }
        }
    }

    // COLLECT PAYMENT (mid-session, does NOT end the session)

    elseif ($action === 'collect_payment') {
        $session_id     = (int)($_POST['session_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $balanceDue     = (float)($_POST['amount'] ?? 0);   // full balance owed
        $tendered_raw   = $_POST['tendered_amount'] ?? '';
        $tendered       = ($tendered_raw !== '') ? (float)$tendered_raw : null;

        // What was ACTUALLY handed over - capped at the balance due
        // (if no tendered entered, assume exact payment of balance due)
        $actualCollected = ($tendered !== null) ? min($tendered, $balanceDue) : $balanceDue;
        $shortfall       = ($tendered !== null && $tendered < $balanceDue)
                            ? round($balanceDue - $tendered, 2) : null;

        if (!$session_id || $balanceDue <= 0) {
            $message = 'Invalid payment - balance must be greater than ₱0.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ? AND status IN ('active','completed')");
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            $sess_row = $stmt->get_result()->fetch_assoc();
            if ($sess_row) {
                recordTransaction(
                    $session_id, $sess_row['user_id'], $actualCollected, $payment_method,
                    $user['user_id'], $tendered, $shortfall,
                    $shortfall
                        ? 'Partial payment - collected ₱' . number_format($actualCollected, 2)
                          . ', short by ₱' . number_format($shortfall, 2)
                          . ' of ₱' . number_format($balanceDue, 2) . ' balance'
                        : 'Balance payment collected'
                );
                if ($shortfall !== null && $shortfall > 0) {
                    $message = 'Collected ₱' . number_format($actualCollected, 2) . ' via ' . ucfirst($payment_method)
                             . '. Still short by ₱' . number_format($shortfall, 2) . '.';
                    $messageType = 'warning';
                } else {
                    $message = 'Payment of ₱' . number_format($actualCollected, 2) . ' recorded via ' . ucfirst($payment_method) . '.';
                    $messageType = 'success';
                }

                // Activity Log
                $payNote = "Recorded payment of ₱" . number_format($actualCollected, 2) . " via " . ucfirst($payment_method) . " for Session #{$session_id}";
                if ($shortfall) $payNote .= ". Short by ₱" . number_format($shortfall, 2);
                logActivity($user['user_id'], "Record Payment", $payNote);
            } else {
                $message = 'Session not found or already ended.';
                $messageType = 'error';
            }
        }
    }

    // ISSUE REFUND and EARLY END SESSION
    // These actions are now handled exclusively by ajax/refund.php.
    // Direct POST to these actions is rejected to prevent bypassing the AJAX layer.
    elseif ($action === 'issue_refund' || $action === 'early_end_session') {
        $message     = 'Refunds must be submitted through the Refund modal (AJAX).';
        $messageType = 'error';
    }

    // PROCESS REFUND for cancelled reservations is handled at lines 266—“306 above.

    // NOTE: Session extension is handled exclusively through ajax/extend_session.php
    // which calls extendSession() - applying bonus minutes and recording a transaction.
    // The old direct form-POST handler has been removed (Bug #4 fix) to prevent
    // bypassing the billing engine with a raw planned_minutes UPDATE.
    elseif ($action === 'extend_session') {
        $message     = 'Session extensions must be processed through the Extend modal.';
        $messageType = 'error';
    }

    // â”€â”€ BULK ACTIONS (ARCHIVE MANAGEMENT) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    elseif (strpos($action, 'bulk_') === 0) {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            $message = 'No items selected for bulk action.';
            $messageType = 'error';
        } else {
            $count = count($ids);
            $idList = implode(',', array_map('intval', $ids));
            $successCount = 0;
            
            // BULK CONSOLES
            if ($action === 'bulk_restore_consoles') {
                $stmt = $conn->prepare("UPDATE consoles SET status = 'available' WHERE console_id IN ($idList)");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Restored {$successCount} archived consoles.");
                    $message = "Successfully restored {$successCount} consoles.";
                    $messageType = 'success';
                }
            }
            elseif ($action === 'bulk_delete_consoles') {
                $stmt = $conn->prepare("DELETE FROM consoles WHERE console_id IN ($idList) AND status = 'archived'");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Permanently deleted {$successCount} archived consoles.");
                    $message = "Permanently deleted {$successCount} consoles.";
                    $messageType = 'success';
                }
            }
            
            // BULK CONTROLLERS
            elseif ($action === 'bulk_restore_controllers') {
                $stmt = $conn->prepare("UPDATE controllers SET status = 'available' WHERE controller_id IN ($idList)");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Restored {$successCount} archived controllers.");
                    $message = "Successfully restored {$successCount} controllers.";
                    $messageType = 'success';
                }
            }
            elseif ($action === 'bulk_delete_controllers') {
                $stmt = $conn->prepare("DELETE FROM controllers WHERE controller_id IN ($idList) AND status = 'archived'");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Permanently deleted {$successCount} archived controllers.");
                    $message = "Permanently deleted {$successCount} controllers.";
                    $messageType = 'success';
                }
            }
            
            // BULK CONSOLE TYPES
            elseif ($action === 'bulk_restore_console_types') {
                $stmt = $conn->prepare("UPDATE console_types SET is_archived = 0 WHERE console_type_id IN ($idList)");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Restored {$successCount} archived console types.");
                    $message = "Successfully restored {$successCount} console types.";
                    $messageType = 'success';
                }
            }
            elseif ($action === 'bulk_delete_console_types') {
                $stmt = $conn->prepare("DELETE FROM console_types WHERE console_type_id IN ($idList) AND is_archived = 1");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Permanently deleted {$successCount} archived console types.");
                    $message = "Permanently deleted {$successCount} console types.";
                    $messageType = 'success';
                }
            }
            
            // BULK CONTROLLER TYPES
            elseif ($action === 'bulk_restore_controller_types') {
                $stmt = $conn->prepare("UPDATE controller_types SET is_archived = 0 WHERE controller_type_id IN ($idList)");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Restored {$successCount} archived controller types.");
                    $message = "Successfully restored {$successCount} controller types.";
                    $messageType = 'success';
                }
            }
            elseif ($action === 'bulk_delete_controller_types') {
                $stmt = $conn->prepare("DELETE FROM controller_types WHERE controller_type_id IN ($idList) AND is_archived = 1");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Permanently deleted {$successCount} archived controller types.");
                    $message = "Permanently deleted {$successCount} controller types.";
                    $messageType = 'success';
                }
            }
            
            // BULK TOURNAMENT PARTICIPANTS
            elseif ($action === 'bulk_restore_participants') {
                $stmt = $conn->prepare("UPDATE tournament_participants SET status = 'active', removed_at = NULL WHERE participant_id IN ($idList)");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Restored {$successCount} archived tournament participants.");
                    $message = "Successfully restored {$successCount} participants.";
                    $messageType = 'success';
                }
            }
            elseif ($action === 'bulk_delete_participants') {
                $stmt = $conn->prepare("DELETE FROM tournament_participants WHERE participant_id IN ($idList) AND status = 'archived'");
                if ($stmt->execute()) {
                    $successCount = $conn->affected_rows;
                    logActivity($user['user_id'], "Bulk Action", "Permanently deleted {$successCount} archived tournament participants.");
                    $message = "Permanently deleted {$successCount} participants.";
                    $messageType = 'success';
                }
            }
            
            if ($successCount === 0 && !isset($message)) {
                $message = "Bulk action failed or no changes made.";
                $messageType = 'error';
            }
        }
    }

    // â”€â”€ TOURNAMENT ACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    // CREATE TOURNAMENT (Admin Only)
    elseif ($action === 'create_tournament') {
        if ($user['role'] === 'shopkeeper') {
            $message = 'Access Denied: Shopkeepers cannot create tournaments.';
            $messageType = 'error';
        } else {
        $name         = trim($_POST['tournament_name'] ?? '');
        $game         = trim($_POST['game_name']       ?? '');
        $console_type = $_POST['console_type']         ?? '';
        $start_date   = $_POST['start_date']           ?? '';
        $end_date     = $_POST['end_date']             ?? '';
        $entry_fee    = (float)($_POST['entry_fee']         ?? 0);
        $prize_pool   = 0; // Fixed prize removed, now dependent on registration count
        $max_part     = 0; // Participant limit removed, now unlimited by default
        $announcement = trim($_POST['announcement']    ?? '');

        if (!$name || !$game || !$console_type || !$start_date || !$end_date) {
            $message = 'Please fill in all required tournament fields.';
            $messageType = 'error';
        } else {
            // Normalize datetime-local value to MySQL DATETIME
            $start_dt = (new DateTime($start_date))->format('Y-m-d H:i:s');
            $end_dt   = (new DateTime($end_date  ))->format('Y-m-d H:i:s');
            $stmt = $conn->prepare(
                "INSERT INTO tournaments
                    (tournament_name, game_name, console_type, start_date, end_date,
                     entry_fee, prize_pool, max_participants, announcement, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?)"
            );
            $stmt->bind_param('sssssddisi',
                $name, $game, $console_type, $start_dt, $end_dt,
                $entry_fee, $prize_pool, $max_part, $announcement, $user['user_id']
            );
            if ($stmt->execute()) {
                $newTid = $conn->insert_id;
                $message = 'Tournament "' . htmlspecialchars($name) . '" created.';
                $messageType = 'success';

                logActivity($user['user_id'], "Create Tournament", "Created Tournament #{$newTid}: {$name} ({$game}) on {$console_type}");
            } else {
                $message = 'Failed to create tournament: ' . $conn->error;
                $messageType = 'error';
            }
            }
        }
    }

    // EDIT TOURNAMENT (Admin Only)
    elseif ($action === 'edit_tournament') {
        if ($user['role'] === 'shopkeeper') {
            $message = 'Access Denied: Shopkeepers cannot edit tournaments.';
            $messageType = 'error';
        } else {
        $tid          = (int)($_POST['tournament_id'] ?? 0);
        $name         = trim($_POST['tournament_name'] ?? '');
        $game         = trim($_POST['game_name']       ?? '');
        $console_type = $_POST['console_type']         ?? '';
        $start_date   = $_POST['start_date']           ?? '';
        $end_date     = $_POST['end_date']             ?? '';
        $entry_fee    = (float)($_POST['entry_fee']         ?? 0);
        $announcement = trim($_POST['announcement']    ?? '');

        if (!$tid || !$name || !$game || !$console_type || !$start_date || !$end_date) {
            $message = 'Please fill in all required tournament fields.';
            $messageType = 'error';
        } else {
            $start_dt = (new DateTime($start_date))->format('Y-m-d H:i:s');
            $end_dt   = (new DateTime($end_date  ))->format('Y-m-d H:i:s');
            $stmt = $conn->prepare(
                "UPDATE tournaments SET
                    tournament_name = ?, game_name = ?, console_type = ?,
                    start_date = ?, end_date = ?, entry_fee = ?,
                    announcement = ?
                 WHERE tournament_id = ?"
            );
            $stmt->bind_param('sssssdsi',
                $name, $game, $console_type, $start_dt, $end_dt,
                $entry_fee, $announcement, $tid
            );
            if ($stmt->execute()) {
                $message = 'Tournament "' . htmlspecialchars($name) . '" updated.';
                $messageType = 'success';

                logActivity($user['user_id'], "Edit Tournament", "Updated Tournament #{$tid}: {$name}");
            } else {
                $message = 'Failed to update tournament: ' . $conn->error;
                $messageType = 'error';
            }
            }
        }
    }

    // UPDATE TOURNAMENT STATUS (Admin Only)
    elseif ($action === 'update_tournament_status') {
        if ($user['role'] === 'shopkeeper') {
            $message = 'Access Denied: Shopkeepers cannot change tournament status.';
            $messageType = 'error';
        } else {
        $tid        = (int)($_POST['tournament_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        $allowed    = ['upcoming', 'scheduled', 'ongoing', 'completed', 'cancelled'];
        if ($tid && in_array($new_status, $allowed)) {
            $stmt = $conn->prepare("UPDATE tournaments SET status = ? WHERE tournament_id = ?");
            $stmt->bind_param('si', $new_status, $tid);
            $stmt->execute();
            $message = 'Tournament status updated to ' . ucfirst($new_status) . '.';
            $messageType = 'success';

            logActivity($user['user_id'], "Tournament Status", "Updated Tournament #{$tid} status to " . ucfirst($new_status));
        } else {
            $message = 'Invalid tournament or status.';
            $messageType = 'error';
            }
        }
    }

    // ADMIN REGISTER PARTICIPANT (registered customer OR walk-in)
    elseif ($action === 'admin_register_participant') {
        $tid          = (int)($_POST['tournament_id']   ?? 0);
        $mode         = $_POST['participant_mode']      ?? 'registered'; // 'registered' | 'walkin'
        $pay_status   = in_array($_POST['payment_status'] ?? '', ['pending','paid'])
                        ? $_POST['payment_status'] : 'pending';
        $ign          = trim($_POST['ign']              ?? '');
        $contact      = trim($_POST['contact_number']   ?? '');
        $notes        = trim($_POST['notes']            ?? '');
        $staff_id     = (int)($user['user_id'] ?? 0);

        if ($mode === 'walkin') {
            // â”€â”€ Walk-in: no user account required â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $walkin_name = trim($_POST['walkin_name'] ?? '');
            if ($tid && $walkin_name) {
                $uid = 0; // walk-in system user
                $stmt = $conn->prepare(
                    "INSERT INTO tournament_participants
                         (tournament_id, user_id, payment_status, ign, contact_number, walkin_name, notes, registered_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('iisssssi', $tid, $uid, $pay_status, $ign, $contact, $walkin_name, $notes, $staff_id);
                if ($stmt->execute()) {
                    $message     = 'Walk-in participant registered.';
                    $messageType = 'success';

                    logActivity($user['user_id'], "Tournament Join", "Registered walk-in participant '{$walkin_name}' for Tournament #{$tid}");
                } else {
                    $message     = 'Could not register walk-in: ' . $conn->error;
                    $messageType = 'error';
                }
            } else {
                $message     = 'Please provide a tournament and the walk-in name.';
                $messageType = 'error';
            }
        } else {
            // â”€â”€ Registered customer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $uid = (int)($_POST['user_id'] ?? 0);
            if ($tid && $uid) {
                // Application-level duplicate check (uk_tp_entry was removed to support multiple walk-ins)
                $dupChk = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ? AND user_id != 0");
                $dupChk->bind_param('ii', $tid, $uid);
                $dupChk->execute();
                if ($dupChk->get_result()->num_rows > 0) {
                    $message     = 'This customer is already registered for this tournament.';
                    $messageType = 'error';
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO tournament_participants
                             (tournament_id, user_id, payment_status, ign, contact_number, notes, registered_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('iissssi', $tid, $uid, $pay_status, $ign, $contact, $notes, $staff_id);
                    if ($stmt->execute()) {
                        $message     = 'Participant registered.';
                        $messageType = 'success';

                        logActivity($user['user_id'], "Tournament Join", "Registered User #{$uid} for Tournament #{$tid}");
                    } else {
                        $message     = 'Could not register participant: ' . $conn->error;
                        $messageType = 'error';
                    }
                }
            } else {
                $message     = 'Invalid tournament or customer.';
                $messageType = 'error';
            }
        }
    }

    // END TOURNAMENT ACTIONS

}


// â”€â”€â”€ DATA FETCHING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// Dashboard stats
$today = getOperatingDay();
$todayStats = getDailySalesReport($today);
$activeSessions  = getActiveSessions();
$activeCount     = count($activeSessions);
$todayRevenue    = $todayStats['total_revenue'] ?? 0;
$todayBookings   = $todayStats['total_sessions'] ?? 0;

// All consoles
$allConsoles = getConsoles();
$archivedConsoles = getConsoles('archived');
$availableCount  = count(array_filter($allConsoles, fn($c) => $c['status'] === 'available'));
$inUseCount      = count(array_filter($allConsoles, fn($c) => $c['status'] === 'in_use'));
$maintenanceCount= count(array_filter($allConsoles, fn($c) => $c['status'] === 'maintenance'));

// â”€â”€ Controllers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$allControllers      = [];
$archivedControllers = [];
$_res = $conn->query("
    SELECT c.*, ct.type_name AS controller_type 
    FROM controllers c 
    LEFT JOIN controller_types ct ON c.controller_type_id = ct.controller_type_id 
    WHERE c.status != 'archived' 
    ORDER BY c.unit_number
");
if ($_res) $allControllers = $_res->fetch_all(MYSQLI_ASSOC);

$_res = $conn->query("
    SELECT c.*, ct.type_name AS controller_type 
    FROM controllers c 
    LEFT JOIN controller_types ct ON c.controller_type_id = ct.controller_type_id 
    WHERE c.status = 'archived' 
    ORDER BY c.unit_number
");
if ($_res) $archivedControllers = $_res->fetch_all(MYSQLI_ASSOC);

// Available controllers for the Start Session rental dropdown (injected to JS)
$_avRes = $conn->query("
    SELECT c.controller_id, ct.type_name AS controller_type, c.unit_number 
    FROM controllers c
    LEFT JOIN controller_types ct ON c.controller_type_id = ct.controller_type_id 
    WHERE c.status = 'available' 
    ORDER BY c.unit_number
");
$availableControllers = $_avRes ? $_avRes->fetch_all(MYSQLI_ASSOC) : [];
unset($_res, $_avRes);

// Sessions: active/live first (sorted by urgency - closest booked end time), then completed newest-first
$stmt = $conn->prepare(
    "SELECT gs.*, u.full_name AS customer_name, u.email AS customer_email, c.console_name, c.unit_number, ct.type_name AS console_type, COALESCE(gs.hourly_rate, ct.hourly_rate) AS hourly_rate,
            gs.source_reservation_id,
            COALESCE(r.downpayment_amount, 0) AS reservation_downpayment,
            COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount > 0), 0) AS upfront_paid,
            COALESCE((SELECT SUM(ABS(t.amount)) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount < 0), 0) AS refunded_amount,
            COALESCE((SELECT SUM(ar.extra_cost) FROM additional_requests ar WHERE ar.session_id = gs.session_id AND ar.status = 'approved'), 0) AS approved_extras
     FROM gaming_sessions gs
     JOIN users u ON gs.user_id = u.user_id
     JOIN consoles c ON gs.console_id = c.console_id
     LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id
     LEFT JOIN reservations r ON r.reservation_id = gs.source_reservation_id
     ORDER BY
         CASE WHEN gs.status = 'active' THEN 0 ELSE 1 END ASC,
         CASE
             WHEN gs.status = 'active' AND gs.planned_minutes IS NOT NULL
                 THEN DATE_ADD(gs.start_time, INTERVAL gs.planned_minutes MINUTE)
             WHEN gs.status = 'active'
                 THEN DATE_ADD(gs.start_time, INTERVAL 9999 MINUTE)
             ELSE NULL
         END ASC,
         gs.created_at DESC
     LIMIT 50"
);
$stmt->execute();
$recentSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// All customers for session start dropdown
$customersResult = $conn->query("SELECT user_id, full_name, email FROM users WHERE role = 'customer' AND status = 'active' ORDER BY full_name");
$customers = $customersResult->fetch_all(MYSQLI_ASSOC);

// Available consoles for start session
$availableConsoles = getAvailableConsoles();

// Reservations - upcoming (pending/reserved) + cancelled (for refund management)
$upcomingReservations  = getUpcomingReservations();
$cancelledReservations = getCancelledReservations();
$pendingResCount       = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'pending'));

// Pending User-Initiated Reschedule Requests
$purStmt = $conn->query(
    "SELECT rs.*, ct.type_name AS console_type, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, c.unit_number
     FROM reservation_reschedules rs
     JOIN reservations r ON rs.reservation_id = r.reservation_id
     JOIN users u ON rs.user_id = u.user_id
     LEFT JOIN consoles c ON rs.console_id = c.console_id
     LEFT JOIN console_types ct ON rs.new_console_type_id = ct.console_type_id
     WHERE rs.status = 'pending' AND rs.initiated_by = 'user'
     ORDER BY rs.created_at ASC"
);
$pendingUserReschedules = $purStmt ? $purStmt->fetch_all(MYSQLI_ASSOC) : [];


// Financial stats
[$opStart, $opEnd] = getOperatingDayBounds($today);
$finStmt = $conn->prepare(
    "SELECT
        SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) AND payment_status='completed' THEN amount ELSE 0 END) AS monthly_revenue,
        SUM(CASE WHEN (transaction_date BETWEEN ? AND ?) AND payment_status='completed' THEN amount ELSE 0 END) AS today_revenue,
        SUM(CASE WHEN payment_status='completed' THEN amount ELSE 0 END) AS total_revenue,
        COUNT(CASE WHEN payment_status='completed' THEN 1 END) AS total_transactions
     FROM transactions"
);
$finStmt->bind_param("ss", $opStart, $opEnd);
$finStmt->execute();
$finStats = $finStmt->get_result()->fetch_assoc();

// Transaction history (last 30)
// NOTE: LEFT JOINs used because session_id can be NULL for reservation refunds.
// Extract reservation_id from payment_note to JOIN reservations for PayMongo IDs.
$transResult = $conn->query(
    "SELECT t.*,
            u.full_name AS customer_name,
            COALESCE(c.unit_number, '-') AS unit_number,
            CASE
             WHEN t.payment_note LIKE 'Downpayment%' THEN 'reservation'
             WHEN t.payment_note LIKE 'Tournament registration%' THEN 'tournament'
             WHEN t.amount < 0 THEN 'refund'
             ELSE COALESCE(gs.rental_mode, 'other')
           END AS rental_mode,
            COALESCE(tp.paymongo_payment_id, r.paymongo_payment_id) AS paymongo_payment_id,
            COALESCE(tp.paymongo_source_id, r.paymongo_source_id) AS paymongo_source_id
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
     LEFT JOIN consoles c ON gs.console_id = c.console_id
     LEFT JOIN reservations r
           ON t.payment_note LIKE '%reservation #%'
          AND r.reservation_id = CAST(
                SUBSTRING_INDEX(SUBSTRING_INDEX(t.payment_note, '#', -1), ' ', 1)
              AS UNSIGNED)
     LEFT JOIN tournament_participants tp
           ON t.payment_note LIKE '%Reg #%'
          AND tp.participant_id = CAST(
                SUBSTRING_INDEX(SUBSTRING_INDEX(t.payment_note, '#', -1), ' ', 1)
              AS UNSIGNED)
     ORDER BY t.transaction_date DESC LIMIT 30"
);
$transSessions = $transResult ? $transResult->fetch_all(MYSQLI_ASSOC) : [];

// Unlimited rate constant (used by sessions.php pending balance display)
$unlimitedRateVal = (float)(getSetting('unlimited_rate') ?? 300);

// Pending sessions: active sessions with upfront payment OR completed sessions with outstanding balance
$pendingSessions = [];
foreach ($recentSessions as $sess) {
    $paidSoFar      = (float)($sess['upfront_paid']    ?? 0); // positive payments only
    $refundedAmount = (float)($sess['refunded_amount'] ?? 0); // total refunded

    if ($sess['status'] === 'active') {
        // For hourly sessions: show in Pending if not fully paid.
        // computeHourlySessionBaseCost() reverses the free-bonus so 4hr+1hr-free
        // sessions correctly report ₱320 base (not ₱400).
        // Include sessions with ₱0 upfront (need to collect full amount at end).
        if ($sess['rental_mode'] === 'hourly' && !empty($sess['planned_minutes'])) {
            $baseCost = computeHourlySessionBaseCost((int)$sess['planned_minutes']);
            $extras   = (float)($sess['approved_extras'] ?? 0);
            if ($paidSoFar >= $baseCost + $extras - 0.01) {
                continue; // Fully paid —” not a pending balance
            }
        } elseif ($sess['rental_mode'] === 'unlimited') {
            continue; // Unlimited: flat rate already handled, skip
        }
        // open_time always needs end-of-session payment
        $sess['paid_so_far'] = $paidSoFar;
        $pendingSessions[] = $sess;
    } elseif ($sess['status'] === 'completed' && $sess['total_cost'] > 0) {
        // Completed session: check if total paid is less than total cost
        // Note: Do not subtract refundedAmount. If they paid enough upfront and were refunded the difference, it's settled.
        $expected = round((float)$sess['total_cost'], 2);
        $paid     = round($paidSoFar, 2);
        
        if ($paid < $expected) {
            $sess['paid_so_far'] = $paidSoFar;
            $pendingSessions[] = $sess;
        }
    }
    // Walk-in open_time sessions (nothing paid upfront): handled at end-of-session.
}


// Console usage (all time)
$usageReport = getConsoleUsageReport('2020-01-01', $today);

// â”€â”€ Cancellation Analytics (for Reports tab) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// Overall counts
$cancelStatsRow = $conn->query(
    "SELECT
        COUNT(*)                                                      AS total_cancels,
        SUM(rc.cancelled_by = 'user')                                 AS user_cancels,
        SUM(rc.cancelled_by = 'admin')                                AS admin_cancels,
        COALESCE(SUM(r.downpayment_amount),0)                         AS total_downpayments
     FROM reservation_cancellations rc
     JOIN reservations r ON rc.reservation_id = r.reservation_id"
)->fetch_assoc();

// Reasons breakdown (for doughnut chart)
$cancelReasons = $conn->query(
    "SELECT cancel_reason_type AS reason, COUNT(*) AS cnt
       FROM reservation_cancellations
      GROUP BY cancel_reason_type
      ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

// Cancellations by console type
$cancelByConsole = $conn->query(
    "SELECT ct.type_name AS console_type, COUNT(*) AS cnt
       FROM reservation_cancellations rc
       JOIN reservations r ON rc.reservation_id = r.reservation_id
       LEFT JOIN console_types ct ON r.console_type_id = ct.console_type_id
      GROUP BY ct.type_name
      ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

// Cancellations over last 30 days (line chart)
$nowTs = time();
$cancelTrend = [];
$cancelTrendLabels = [];
$cancelTrendContext = [];
for ($i = 29; $i >= 0; $i--) {
    $targetOpDay = getOperatingDay(date('Y-m-d H:i:s', strtotime("-{$i} days", $nowTs)));
    [$sBound, $eBound] = getOperatingDayBounds($targetOpDay);
    
    $cancelTrendLabels[] = date('M d', strtotime($targetOpDay));
    
    $cs = $conn->prepare("SELECT COUNT(*) AS cnt FROM reservation_cancellations WHERE (cancelled_at BETWEEN ? AND ?)");
    $cs->bind_param('ss', $sBound, $eBound);
    $cs->execute();
    $cnt = (int)$cs->get_result()->fetch_assoc()['cnt'];
    $cancelTrend[] = $cnt;

    // Context for spike annotation
    if ($cnt > 0) {
        $rs = $conn->prepare("SELECT cancel_reason_type, COUNT(*) as c FROM reservation_cancellations WHERE (cancelled_at BETWEEN ? AND ?) GROUP BY cancel_reason_type ORDER BY c DESC LIMIT 1");
        $rs->bind_param('ss', $sBound, $eBound);
        $rs->execute();
        $topReason = $rs->get_result()->fetch_assoc();
        $reasonText = $topReason ? ($reasonLabels[$topReason['cancel_reason_type']] ?? $topReason['cancel_reason_type']) : 'Unknown';
        $cancelTrendContext[] = $cnt > 1 ? "Batch: $reasonText" : $reasonText;
    } else {
        $cancelTrendContext[] = "";
    }
}

// Cancelled-by breakdown (for doughnut)
$cancelByWho = $conn->query(
    "SELECT cancelled_by, COUNT(*) AS cnt FROM reservation_cancellations GROUP BY cancelled_by"
)->fetch_all(MYSQLI_ASSOC);

// Settings
$settingsKeys = ['unlimited_rate',
                 'business_hours_open','business_hours_close','shop_name','shop_address','shop_phone',
                 'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge'];
$settings = [];
foreach ($settingsKeys as $k) {
    $settings[$k] = getSetting($k);
}

// Chart data: revenue last 7 days
$revChartData = [];
$revLabels = [];
$revContext = [];
for ($i = 6; $i >= 0; $i--) {
    $targetOpDay = getOperatingDay(date('Y-m-d H:i:s', strtotime("-{$i} days", $nowTs)));
    [$sBound, $eBound] = getOperatingDayBounds($targetOpDay);
    
    $revLabels[] = date('M d', strtotime($targetOpDay));
    
    // Revenue
    $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS rev FROM transactions WHERE (transaction_date BETWEEN ? AND ?) AND payment_status='completed'");
    $s->bind_param("ss", $sBound, $eBound);
    $s->execute();
    $rev = (float)$s->get_result()->fetch_assoc()['rev'];
    $revChartData[] = $rev;

    // Context (Sessions & Reservations)
    $sc = $conn->prepare("SELECT COUNT(*) as c FROM gaming_sessions WHERE (start_time BETWEEN ? AND ?)");
    $sc->bind_param("ss", $sBound, $eBound);
    $sc->execute();
    $sessCnt = $sc->get_result()->fetch_assoc()['c'];

    $rc = $conn->prepare("SELECT COUNT(*) as c FROM reservations WHERE (created_at BETWEEN ? AND ?) AND status='confirmed'");
    $rc->bind_param("ss", $sBound, $eBound);
    $rc->execute();
    $resCnt = $rc->get_result()->fetch_assoc()['c'];

    $revContext[] = ($sessCnt > 0 || $resCnt > 0) ? "$sessCnt sessions, $resCnt bookings" : "";
}

// Chart data: console type usage
$typeUsage = $conn->query(
    "SELECT ct.type_name AS console_type, COUNT(gs.session_id) AS cnt
     FROM consoles c
     LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id
     LEFT JOIN gaming_sessions gs ON c.console_id = gs.console_id AND gs.status = 'completed'
     GROUP BY ct.type_name"
)->fetch_all(MYSQLI_ASSOC);
$typeLabels = array_column($typeUsage, 'console_type');
$typeCounts = array_column($typeUsage, 'cnt');

// Baseline reservation_id for the notification poller
// JS will use this so it never alerts on reservations that existed at page load.
$initResRow = $conn->query("SELECT COALESCE(MAX(reservation_id), 0) AS max_id FROM reservations");
$initMaxResId = (int)$initResRow->fetch_assoc()['max_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Good Spot Gaming Hub</title>
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="assets/libs/chartjs/chart.min.js"></script>
    <script src="assets/js/admin_search.js"></script>
    <script>window.GSPOT_CSRF = <?= json_encode(csrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
    <style>
        /* â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• 
           ADMIN DESIGN SYSTEM - CSS Custom Properties
        â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â•  */
        :root {
            --clr-mint:    #20c8a1;
            --clr-blue:    #5f85da;
            --clr-coral:   #fb566b;
            --clr-gold:    #f1a83c;
            --clr-cream:   #f1e1aa;
            --clr-purple:  #b37bec;
            --clr-bg:      #0a0f1c;
            --clr-surface: rgba(10,33,81,.55);
            --clr-border:  rgba(95,133,218,.18);
            --clr-text:    #f0f0f0;
            --clr-muted:   #888;
            --radius-sm:   8px;
            --radius-md:   12px;
            --radius-lg:   16px;
            --shadow-card: 0 4px 24px rgba(0,0,0,.35);
        }

        /* â”€â”€ Global Dark Selects â”€â”€ */
        select, .res-input {
            background-color: #0d1b3e !important;
            color: #fff !important;
            border: 1.5px solid rgba(255,255,255,.12) !important;
        }
        select option {
            background-color: #0d1b3e !important;
            color: #fff !important;
        }

        /* Force page visibility */
        .page.active {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        #endSessionForm button[type="submit"]:disabled {
            opacity: 0.35 !important;
            filter: grayscale(40%);
            cursor: not-allowed !important;
            pointer-events: none !important;
        }
        #endEarlyWarning button {
            pointer-events: auto !important;
            opacity: 1 !important;
            filter: none !important;
            cursor: pointer !important;
        }

        /* â”€â”€ Flash messages â”€â”€ */
        .flash-msg {
            position: fixed; top: 80px; right: 20px; z-index: 9999;
            padding: 14px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            animation: slideInRight .3s ease; max-width: 400px;
            box-shadow: 0 8px 40px rgba(0,0,0,.5);
            backdrop-filter: blur(8px);
        }
        .flash-msg.success { background: rgba(32,200,161,.15); border: 1px solid rgba(32,200,161,.4); color: var(--clr-mint); }
        .flash-msg.error   { background: rgba(251,86,107,.15); border: 1px solid rgba(251,86,107,.4); color: var(--clr-coral); }
        .flash-msg.warning { background: rgba(241,168,60,.15);  border: 1px solid rgba(241,168,60,.4);  color: var(--clr-gold); }
        @keyframes slideInRight { from { transform: translateX(120%); opacity:0; } to { transform: translateX(0); opacity:1; } }

        /* â”€â”€ Status dots â”€â”€ */
        .status-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }
        .status-dot.available   { background:var(--clr-mint); box-shadow:0 0 6px rgba(32,200,161,.5); }
        .status-dot.in_use      { background:var(--clr-blue); }
        .status-dot.maintenance { background:var(--clr-coral); }

        /* â”€â”€ Console type badges â”€â”€ */
        .console-type-badge { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; letter-spacing:.3px; }
        .console-type-badge.ps5  { background:rgba(95,133,218,.18); color:#8aa4e8; border:1px solid rgba(95,133,218,.3); }
        .console-type-badge.ps4  { background:rgba(241,168,60,.15);  color:#f1a83c; border:1px solid rgba(241,168,60,.3); }
        .console-type-badge.xbox { background:rgba(32,200,161,.18);  color:#20c8a1; border:1px solid rgba(32,200,161,.3); }

        /* â”€â”€ Session timer â”€â”€ */
        .session-timer { font-family: monospace; font-size: 13px; color: var(--clr-cream); font-weight: 700; }
        .session-timer.stale { color: var(--clr-coral); font-size:11px; font-weight:500; }

        /* â”€â”€ Page header pattern â”€â”€ */
        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
        }
        .page-header .page-title-group .page-title {
            font-size: 22px; font-weight: 800; color: var(--clr-text);
            margin: 0 0 4px; line-height: 1.2;
        }
        .page-header .page-title-group .page-subtitle {
            font-size: 13px; color: var(--clr-muted); margin: 0;
        }

        /* â”€â”€ Form layout â”€â”€ */
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-group { margin-bottom:16px; }
        .form-group label {
            display:block; font-size:12px; color:#aaa; margin-bottom:6px;
            font-weight:700; text-transform:uppercase; letter-spacing:.5px;
        }
        .form-group select, .form-group input[type=text], .form-group input[type=number],
        .form-group input[type=time], .form-group textarea, .form-group input[type=datetime-local] {
            width:100%; background:rgba(10,33,81,.7); border:1px solid rgba(95,133,218,.25);
            color:var(--clr-text); padding:10px 14px; border-radius:var(--radius-sm); font-size:14px;
            font-family:inherit; outline:none; box-sizing:border-box; transition:.2s;
        }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            border-color:var(--clr-mint); box-shadow:0 0 0 3px rgba(32,200,161,.12);
        }
        .form-group textarea { resize:vertical; min-height:80px; }
        .form-check { display:flex; align-items:center; gap:8px; margin-top:6px; }
        .form-check input { width:auto; accent-color:var(--clr-mint); }
        .form-hint { font-size:11px; color:#666; margin-top:5px; }

        /* â”€â”€ Stat cards â”€â”€ */
        .stat-card-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
        .stat-change.up { color:var(--clr-mint); font-size:12px; }
        .stat-icon {
            width:44px; height:44px; border-radius:var(--radius-sm);
            display:flex; align-items:center; justify-content:center;
            font-size:20px; flex-shrink:0;
        }
        .stat-icon.revenue  { background:rgba(32,200,161,.15); color:var(--clr-mint); }
        .stat-icon.sessions { background:rgba(95,133,218,.15); color:var(--clr-blue); }
        .stat-icon.bookings { background:rgba(179,123,236,.15); color:var(--clr-purple); }
        .stat-icon.consoles { background:rgba(241,225,170,.15); color:var(--clr-cream); }

        /* â”€â”€ Console cards â”€â”€ */
        .console-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
        .console-card {
            background:var(--clr-surface); border:1px solid var(--clr-border);
            border-radius:var(--radius-md); padding:18px; position:relative;
            transition:transform .2s, box-shadow .2s;
            box-shadow: var(--shadow-card);
        }
        .console-card:hover { transform:translateY(-4px); box-shadow:0 12px 40px rgba(0,0,0,.4); }
        .console-card.available  { border-left:3px solid var(--clr-mint); }
        .console-card.in_use     { border-left:3px solid var(--clr-blue); }
        .console-card.maintenance{ border-left:3px solid var(--clr-coral); }
        .console-unit  { font-size:24px; font-weight:800; margin-bottom:2px; color:#fff; font-family:'Outfit',sans-serif; }
        .console-name  { font-size:12px; color:#666; margin-bottom:10px; }
        .console-rate  { font-size:13px; color:var(--clr-cream); margin-bottom:14px; font-weight:600; }
        .console-actions { display:flex; gap:6px; flex-wrap:wrap; }

        /* â”€â”€ Data table â”€â”€ */
        .data-table thead tr { background:rgba(10,33,81,.6); }
        .data-table tbody tr { transition:background .15s; }
        .data-table tbody tr:hover { background:rgba(95,133,218,.06); }

        /* â”€â”€ Badge â”€â”€ */
        .badge {
            display:inline-block; padding:3px 10px; border-radius:20px;
            font-size:11px; font-weight:700; letter-spacing:.4px; white-space:nowrap;
        }
        .badge.active     { background:rgba(95,133,218,.2);  color:#8aa4e8; }
        .badge.completed  { background:rgba(32,200,161,.2);  color:var(--clr-mint); }
        .badge.cancelled  { background:rgba(251,86,107,.2);  color:var(--clr-coral); }
        .badge.pending    { background:rgba(241,225,170,.18); color:var(--clr-cream); }
        .badge.available  { background:rgba(32,200,161,.2);  color:var(--clr-mint); }
        .badge.in_use     { background:rgba(95,133,218,.2);  color:#8aa4e8; }
        .badge.maintenance{ background:rgba(251,86,107,.2);  color:var(--clr-coral); }
        .badge.installed  { background:rgba(179,123,236,.2); color:var(--clr-purple); }

        /* â”€â”€ Empty state â”€â”€ */
        .empty-state { text-align:center; padding:48px 20px; color:#444; }
        .empty-state i { font-size:40px; margin-bottom:14px; display:block; opacity:.5; }
        .empty-state p { margin:4px 0; font-size:14px; }

        /* â”€â”€ Responsive â”€â”€ */
        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
        @media (min-width:769px) {
            .menu-toggle { display:none !important; }
            .sidebar-close-btn { display:none !important; visibility:hidden !important; }
        }

        /* â”€â”€ Sidebar hamburger â”€â”€ */
        .sidebar-hamburger .sidebar-ham-icon {
            font-size: 14px; color: rgba(255,255,255,0.55); transition: color 0.2s ease; width: auto;
        }
        .sidebar-hamburger:hover .sidebar-ham-icon { color: var(--clr-mint); }

        /* â”€â”€ Admin user dropdown â”€â”€ */
        .admin-user-dropdown { position:relative; }
        .admin-user-toggle {
            display:flex; align-items:center; gap:10px;
            background:none; border:none; cursor:pointer;
            color:inherit; padding:6px 10px;
            border-radius:var(--radius-sm); transition:background .2s;
        }
        .admin-user-toggle:hover { background:rgba(255,255,255,.07); }
        .admin-user-dropdown.open .admin-user-toggle .fa-chevron-down { transform:rotate(180deg); }
        .admin-user-menu {
            display:none; position:absolute; right:0; top:calc(100% + 8px);
            min-width:220px; background:#0d1b3e;
            border:1px solid rgba(95,133,218,.25); border-radius:var(--radius-md);
            box-shadow:0 16px 48px rgba(0,0,0,.5); z-index:10000;
            overflow:hidden; animation:fadeInDown .18s ease;
        }
        .admin-user-dropdown.open .admin-user-menu { display:block; }
        @keyframes fadeInDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .admin-dropdown-header { display:flex; align-items:center; gap:12px; padding:16px 16px 12px; }
        .admin-dropdown-name  { font-weight:700; font-size:14px; color:var(--clr-text); }
        .admin-dropdown-email { font-size:12px; color:#718096; margin-top:2px; }
        .admin-dropdown-divider { height:1px; background:rgba(95,133,218,.15); margin:0 12px; }
        .admin-dropdown-item {
            display:flex; align-items:center; gap:10px;
            padding:11px 16px; font-size:14px; color:#ccc;
            text-decoration:none; transition:background .15s, color .15s;
        }
        .admin-dropdown-item:hover { background:rgba(255,255,255,.06); color:#fff; }
        .admin-dropdown-danger { color:var(--clr-coral) !important; }
        .admin-dropdown-danger:hover { background:rgba(251,86,107,1) !important; }
        .user-avatar-lg { width:42px; height:42px; font-size:16px; flex-shrink:0; }

        /* Bulk Action Bar */
        .bulk-bar {
            position: fixed;
            bottom: -80px;
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            min-width: 320px;
            background: linear-gradient(135deg, #0d1b3e, #08101c);
            border: 1px solid rgba(32, 200, 161, .4);
            border-radius: 20px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 12px 40px rgba(0,0,0,.6);
            z-index: 100000;
            transition: bottom .35s cubic-bezier(.175, .885, .32, 1.275), opacity .3s;
            opacity: 0;
            pointer-events: none;
        }
        .bulk-bar.active {
            bottom: 30px;
            opacity: 1;
            pointer-events: auto;
        }
        .bulk-count-badge {
            background: rgba(32, 200, 161, .15);
            color: #20c8a1;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
            border: 1px solid rgba(32, 200, 161, .3);
        }
        .bulk-actions {
            display: flex;
            gap: 10px;
        }
        .bulk-btn {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: .2s;
        }
        .bulk-btn-restore { background: #20c8a1; color: #0a0f1c; }
        .bulk-btn-restore:hover { background: #1ab38f; transform: translateY(-2px); }
        .bulk-btn-delete { background: rgba(251, 86, 107, .15); color: #fb566b; border: 1px solid rgba(251, 86, 107, .3); }
        .bulk-btn-delete:hover { background: #fb566b; color: #fff; transform: translateY(-2px); }
    </style>
</head>
<body>

<?php if ($message): ?>
<div class="flash-msg <?= $messageType ?>" id="flashMsg">
    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<script>setTimeout(() => document.getElementById('flashMsg')?.remove(), 4500);</script>
<?php endif; ?>


<!-- â”€â”€ Sidebar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="navbar-brand sidebar-logo" >
            <div class="logo-container">
                <span class="logo-g">G</span><span class="logo-s">s</span><span class="logo-p">p</span><span class="logo-o">o</span><span class="logo-t">t</span>
                <span class="logo-text">GAMING HUB</span>
            </div>
        </a>
        <button class="sidebar-hamburger" onclick="toggleSidebar()" aria-label="Toggle sidebar" id="sidebarHamburger">
            <i class="fas fa-bars sidebar-ham-icon"></i>
        </button>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" onclick="closeSidebar()" aria-label="Close sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php $navBadge = 'style="background:#fb566b;color:#fff;font-size:10px;font-weight:800;padding:1px 7px;border-radius:10px;margin-left:auto;min-width:18px;text-align:center;"'; ?>
    <div class="nav-item active" onclick="showPage('dashboard', this)">
        <i class="fas fa-chart-line"></i><span>Dashboard</span>
    </div>
    <?php if ($user['role'] !== 'shopkeeper'): ?>
    <div class="nav-item" onclick="showPage('consoles', this)">
        <i class="fas fa-desktop"></i><span>Consoles</span>
        <?php if ($maintenanceCount > 0): ?>
        <span <?= $navBadge ?>><?= $maintenanceCount ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="nav-item" data-tooltip="Sessions" onclick="showPage('sessions', this)">
        <i class="fas fa-play-circle"></i><span>Sessions</span>
        <?php if ($activeCount > 0): ?>
        <span <?= $navBadge ?>><?= $activeCount ?></span>
        <?php endif; ?>
    </div>
    <div class="nav-item" data-tooltip="Reservations" onclick="showPage('reservations', this)">
        <i class="fas fa-calendar-check"></i><span>Reservations</span>
        <?php if ($pendingResCount > 0): ?>
        <span <?= $navBadge ?>><?= $pendingResCount ?></span>
        <?php endif; ?>
    </div>
    <div class="nav-item" data-tooltip="Transactions" onclick="showPage('transactions', this)">
        <i class="fas fa-exchange-alt"></i><span>Transactions</span>
        <?php if (count($pendingSessions) > 0): ?>
        <span <?= $navBadge ?>><?= count($pendingSessions) ?></span>
        <?php endif; ?>
    </div>
    <div class="nav-item" data-tooltip="Reports" onclick="showPage('reports', this)">
        <i class="fas fa-chart-bar"></i><span>Reports</span>
    </div>
    <div class="nav-item" data-tooltip="Tournaments" onclick="showPage('tournaments', this)">
        <i class="fas fa-trophy"></i><span>Tournaments</span>
        <?php
        $openTourCount = 0;
        $tourCountStmt = $conn->query("SELECT COUNT(*) AS n FROM tournaments WHERE status IN ('scheduled','ongoing')");
        if ($tourCountStmt) $openTourCount = (int)$tourCountStmt->fetch_assoc()['n'];
        if ($openTourCount > 0): ?>
        <span <?= $navBadge ?>><?= $openTourCount ?></span>
        <?php endif; ?>
    </div>

    <?php if ($user['role'] === 'owner'): ?>
    <div class="nav-item" data-tooltip="Activity Logs" onclick="showPage('activity_logs', this)">
        <i class="fas fa-history"></i><span>Activity Logs</span>
    </div>
    <div class="nav-item" data-tooltip="Blocked Dates" onclick="showPage('blocked_dates', this)">
        <i class="fas fa-calendar-times"></i><span>Blocked Dates</span>
    </div>
    <div class="nav-item" onclick="showPage('settings', this)">
        <i class="fas fa-cog"></i><span>Settings</span>
    </div>
    <?php endif; ?>

</div>

<!-- â”€â”€ Top Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="topbar">
    <div class="topbar-left">
        <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
        <h3 id="pageTitle">Dashboard</h3>
    </div>
    <div class="topbar-right">

        <!-- â”€â”€ Bell Notification Icon â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div class="notif-bell-wrap" id="notifBellWrap" style="position:relative;">
            <button id="notifBellBtn" onclick="toggleNotifDropdown()"
                title="Reservations"
                style="position:relative;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);
                       border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;
                       cursor:pointer;transition:background .2s,border-color .2s;color:rgba(255,255,255,.75);font-size:16px;">
                <i class="fas fa-bell"></i>
                <!-- red badge - hidden until there are new reservations -->
                <span id="notifBellBadge"
                      style="display:none;position:absolute;top:-5px;right:-5px;
                             background:#fb566b;color:#fff;border-radius:50%;
                             min-width:18px;height:18px;font-size:10px;font-weight:700;
                             line-height:18px;text-align:center;padding:0 3px;
                             box-shadow:0 0 0 2px #0a0f1c;"></span>
            </button>

            <!-- Dropdown panel -->
            <div id="notifDropdown"
                 style="display:none;position:absolute;top:calc(100% + 10px);right:0;width:320px;
                        background:linear-gradient(135deg,#0d1b3e,#08101c);
                        border:1px solid rgba(32,200,161,.3);border-radius:14px;
                        box-shadow:0 16px 48px rgba(0,0,0,.65),0 0 0 1px rgba(32,200,161,.08);
                        z-index:10000;overflow:hidden;animation:dropIn .2s ease;">

                <!-- Header -->
                <div style="padding:14px 18px 10px;border-bottom:1px solid rgba(255,255,255,.07);
                            display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-weight:700;font-size:14px;color:#f0f0f0;">
                        <i class="fas fa-calendar-check" style="color:#20c8a1;margin-right:7px;"></i>
                        New Reservations
                    </span>
                    <span id="notifHeaderBadge"
                          style="background:rgba(251,86,107,.2);color:#fb566b;border:1px solid rgba(251,86,107,.35);
                                 border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;display:none;"></span>
                </div>

                <!-- List of notifications -->
                <div id="notifList" style="max-height:280px;overflow-y:auto;padding:8px 0;"></div>

                <!-- Empty state -->
                <div id="notifEmpty"
                     style="padding:28px 18px;text-align:center;color:#555;font-size:13px;">
                    <i class="fas fa-bell-slash" style="font-size:1.6rem;display:block;margin-bottom:8px;color:#333;"></i>
                    No new reservations
                </div>

                <!-- Footer -->
                <div style="padding:10px 14px;border-top:1px solid rgba(255,255,255,.07);text-align:center;">
                    <button class="btn-prim" style="width:100%;font-size:12px;padding:8px 18px;"
                            onclick="showPage('reservations', document.querySelector('.nav-item[onclick*=\'reservations\']')); closeNotifDropdown();">
                        <i class="fas fa-list" style="margin-right:5px;"></i> View All Reservations
                    </button>
                </div>

            </div>
        </div>

        <!-- Admin user dropdown -->
        <div class="admin-user-dropdown" id="adminUserDropdown" style="margin-left:12px; cursor:pointer;">
            <button class="admin-user-toggle" id="adminUserBtn" style="cursor:pointer; pointer-events:auto;">
                <div class="user-avatar"><?= getUserInitials() ?></div>
                <div>
                    <div style="font-weight:600;font-size:14px;line-height:1.2"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div style="font-size:11px;color:#718096"><?= getRoleBadge() ?></div>
                </div>
                <i class="fas fa-chevron-down" style="font-size:11px;color:#718096;margin-left:6px;transition:transform .2s"></i>
            </button>
            <div class="admin-user-menu" id="adminUserMenu">
                <div class="admin-dropdown-header">
                    <div class="user-avatar user-avatar-lg"><?= getUserInitials() ?></div>
                    <div>
                        <div class="admin-dropdown-name"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="admin-dropdown-email"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
                <div class="admin-dropdown-divider"></div>
                <?php if ($user['role'] === 'owner'): ?>
                <a href="auth/register_shopkeeper.php" class="admin-dropdown-item">
                    <i class="fas fa-user-plus"></i> Create Shopkeeper Account
                </a>
                <?php endif; ?>
                <a href="auth/logout.php" class="admin-dropdown-item admin-dropdown-danger" id="logoutLink" onclick="window.location.href='auth/logout.php';">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- â”€â”€ Main Content â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="main-content">

<?php include __DIR__ . '/admin_sections/dashboard.php'; ?>
<?php if ($user['role'] !== 'shopkeeper'): include __DIR__ . '/admin_sections/consoles.php'; endif; ?>
<?php include __DIR__ . '/admin_sections/sessions.php'; ?>
<?php include __DIR__ . '/admin_sections/reservations.php'; ?>
<?php include __DIR__ . '/admin_sections/transactions.php'; ?>
<?php include __DIR__ . '/admin_sections/reports.php'; ?>
<?php include __DIR__ . '/admin_sections/tournaments.php'; ?>

<?php if ($user['role'] === 'owner'): include __DIR__ . '/admin_sections/activity_logs.php'; endif; ?>
<?php if ($user['role'] === 'owner'): include __DIR__ . '/admin_sections/blocked_dates.php'; endif; ?>
<?php if ($user['role'] === 'owner'): include __DIR__ . '/admin_sections/settings.php'; endif; ?>


</div><!-- /.main-content -->
<?php include __DIR__ . '/admin_sections/modals.php'; ?>
<!-- â”€â”€ JavaScript â”€â”€ -->
<script src="assets/libs/aos/aos.js"></script>
<script>
window.GSPOT_CSRF = '<?= csrfToken() ?>';
window._CSRF = window.GSPOT_CSRF;

// â”€â”€ Admin user dropdown (Moved to top for reliability) â”€â”€
(function () {
    const btn      = document.getElementById('adminUserBtn');
    const dropdown = document.getElementById('adminUserDropdown');
    if (!btn || !dropdown) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) dropdown.classList.remove('open');
    });
})();

// â”€â”€ Navigation â”€â”€
function showPage(page, el) {
    // Clear any active bulk selection when switching pages
    if (window.BulkManager) window.BulkManager.init('');
    // â”€â”€ Role-Based Access Check â”€â”€
    const userRole = '<?= $user['role'] ?>';
    const restricted = ['consoles', 'activity_logs', 'blocked_dates', 'settings'];
    if (userRole === 'shopkeeper' && restricted.includes(page)) {
        console.warn('[GSpot Access] Shopkeeper access denied to:', page);
        // Redirect to dashboard if attempting restricted page
        showPage('dashboard', document.querySelector('.nav-item[onclick*="dashboard"]'));
        // Optionally show a toast/alert
        if (typeof showInlineToast === 'function') {
            showInlineToast('Access Denied: You do not have permission to view this section.', 'error');
        } else {
            alert('Access Denied: You do not have permission to view this section.');
        }
        return;
    }

    document.querySelectorAll('.page').forEach(p => {
        p.classList.remove('active');
        p.style.opacity = '';
        p.style.transform = '';
    });
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const target = document.getElementById(page);
    if (target) {
        target.classList.add('active');
        // Force re-trigger CSS animation by removing and re-adding the element's animation
        target.style.animation = 'none';
        target.offsetHeight; // force reflow
        target.style.animation = '';
        // Ensure opacity is always 1 after animation
        setTimeout(() => { target.style.opacity = '1'; target.style.transform = 'translateY(0)'; }, 550);
    }
    if (el) el.classList.add('active');
    const titles = {
        dashboard: 'Dashboard', consoles: 'Console Management', reservations: 'Reservations',
        sessions: 'Session Management', transactions: 'Transactions',
        financial: 'Financial', reports: 'Analytics & Reports',
        settings: 'Settings', tournaments: 'Tournaments',
        activity_logs: 'Activity Logs',
        blocked_dates: 'Blocked Dates'
    };

    document.getElementById('pageTitle').textContent = titles[page] || page;

    // Persist active page in URL hash so reloads stay on the same section
    history.replaceState(null, '', '#' + page);

    // â”€â”€ Unified Console Colors (global) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Used by Charts. Must be defined BEFORE renderCharts() can run.
    if (typeof window.getConsoleColor !== 'function') {
        window.getConsoleColor = function(label) {
            if (!label) return '#888';
            const normalized = String(label).trim();
            const brand = {
                'PS5': '#5f85da',
                'Xbox Series X': '#20c8a1',
                'Nintendo Switch': '#fb566b',
                'PS4': '#b37bec',
                'Xbox One': '#0ea5e9',
                'PC': '#f1a83c',
            };
            if (brand[normalized]) return brand[normalized];
            const l = normalized.toLowerCase();
            if (l.includes('ps5')) return brand['PS5'];
            if (l.includes('xbox series x') || l.includes('xbox sx')) return brand['Xbox Series X'];
            if (l.includes('switch')) return brand['Nintendo Switch'];
            if (l.includes('ps4')) return brand['PS4'];
            if (l.includes('xbox one')) return brand['Xbox One'];
            if (l.includes('pc')) return brand['PC'];
            const fallbacks = ['#38bdf8', '#fb923c', '#f1e1aa', '#a78bfa', '#f472b6', '#4ade80'];
            let hash = 0;
            for (let i = 0; i < normalized.length; i++) {
                hash = normalized.charCodeAt(i) + ((hash << 5) - hash);
            }
            return fallbacks[Math.abs(hash) % fallbacks.length];
        };
    }

    // Render charts lazily on first visit to reports
    if (page === 'reports' && !window.chartsRendered) {
        renderCharts();
        window.chartsRendered = true;
    }
}

// track which section is currently visible (for live-refresh)
var _currentSection = 'dashboard';

// â”€â”€ Live Section Refresh â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Every 12 seconds, re-fetches the active section's rendered HTML from the server
// and updates the DOM - keeps reservations, sessions, dashboard etc. live without reload.
(function () {
    var REFRESH_MS = 12000;
    // Sections we can safely auto-refresh (exclude settings to avoid mid-edit disruption)
    var refreshable = ['dashboard','sessions','reservations','consoles','transactions','tournaments','blocked_dates'];
    var _refreshInFlight = false;
    var _refreshTimerId  = null;

    // CSS flash keyframe for subtle update feedback
    var styleEl = document.createElement('style');
    styleEl.textContent = '@keyframes liveFlash{0%{opacity:.6}50%{opacity:.9}100%{opacity:1}} .live-refreshing{animation:liveFlash .4s ease;}';
    document.head.appendChild(styleEl);

    // Live indicator dot in topbar
    var dot = document.createElement('span');
    dot.id = 'liveIndicator';
    dot.title = 'Live data - auto-refreshing';
    dot.style.cssText = 'width:7px;height:7px;border-radius:50%;background:#20c8a1;display:inline-block;box-shadow:0 0 0 0 rgba(32,200,161,.5);animation:livePulse 2s ease infinite;flex-shrink:0;';
    var pulseStyle = document.createElement('style');
    pulseStyle.textContent = '@keyframes livePulse{0%{box-shadow:0 0 0 0 rgba(32,200,161,.5)}70%{box-shadow:0 0 0 6px rgba(32,200,161,0)}100%{box-shadow:0 0 0 0 rgba(32,200,161,0)}}';
    document.head.appendChild(pulseStyle);
    var topbarLeft = document.querySelector('.topbar-left');
    if (topbarLeft) topbarLeft.appendChild(dot);

    function isModalOpen() {
        // Prevent refresh if items are selected for bulk action
        if (window.BulkManager && window.BulkManager.selectedIds.length > 0) return true;

        // Check any visible modal - don't refresh while admin is interacting
        var modals = document.querySelectorAll('.modal, [id$="Modal"], [id*="modal"]');
        for (var i = 0; i < modals.length; i++) {
            var m = modals[i];
            if (m.classList.contains('active')) return true;
            var s = window.getComputedStyle(m);
            if (s.display === 'flex' || s.display === 'block') return true;
        }
        return false;
    }

    function isInputFocused() {
        var tag = document.activeElement && document.activeElement.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
    }

    function refreshSection(sectionOverride) {
        var section = sectionOverride || _currentSection;
        if (!refreshable.includes(section)) return;
        
        // When forcing a refresh (override), we skip modal/input checks
        if (!sectionOverride && (isModalOpen() || isInputFocused())) return; 
        
        if (_refreshInFlight) return; // prevent request pile-ups

        dot.style.background = '#888';
        _refreshInFlight = true;

        var container = document.getElementById(section);
        if (!container) {
            _refreshInFlight = false;
            return;
        }

        // â”€â”€ Preserve UI State (Filters, Search, etc.) â”€â”€
        // We find all "admin search bar" inputs and selects within this section.
        var stateMap = {};
        container.querySelectorAll('.asb-input, .asb-select').forEach(function(el) {
            if (el.id) {
                stateMap[el.id] = {
                    value: el.value,
                    focused: (document.activeElement === el)
                };
            }
        });

        fetch('ajax/live_section.php?section=' + encodeURIComponent(section), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                dot.style.background = '#20c8a1';
                if (!data.html) return;

                // When forcing refresh, we still avoid replacing if input is focused to be safe
                // (Unless it's a specific manual override refresh)
                if (!sectionOverride && isInputFocused()) return;

                container.innerHTML = data.html;

                // â”€â”€ Restore UI State â”€â”€
                for (var id in stateMap) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.value = stateMap[id].value;
                        // Trigger events so the filter logic (in sessions.php) re-runs on the new HTML
                        var eventType = el.tagName === 'SELECT' ? 'change' : 'input';
                        el.dispatchEvent(new Event(eventType));
                        if (stateMap[id].focused) el.focus();
                    }
                }

                container.classList.add('live-refreshing');
                setTimeout(function() { container.classList.remove('live-refreshing'); }, 450);
            })
            .catch(function() {
                dot.style.background = '#fb566b';
                setTimeout(function() { dot.style.background = '#20c8a1'; }, 3000);
            })
            .finally(function() {
                _refreshInFlight = false;
            });
    }

    // Expose globally
    window.gspotRefreshSection = refreshSection;

    // Start after 5s, then every 12s
    setTimeout(function() {
        refreshSection();
        if (_refreshTimerId) clearInterval(_refreshTimerId);
        _refreshTimerId = setInterval(refreshSection, REFRESH_MS);
    }, 5000);

    // Also refresh immediately when switching to a refreshable section
    // â”€â”€ Start Session Modal â”€â”€
    var _origShowPage = window.showPage;
    window.showPage = function(page, el) {
        _currentSection = page;
        _origShowPage(page, el);
        // Refresh new section data immediately on tab switch (after 300ms for animation)
        if (refreshable.includes(page)) {
            setTimeout(refreshSection, 300);
        }
    };

    window.refreshSection = refreshSection;
})();


(function () {
    const hash = window.location.hash.replace('#', '');
    const userRole = '<?= $user['role'] ?>';
    const validPages = ['dashboard','consoles','sessions','reservations','transactions','financial','reports','settings','tournaments'];
    
    // Filter valid pages based on role
    const allowedPages = validPages.filter(p => {
        if (userRole === 'shopkeeper' && ['consoles', 'settings'].includes(p)) return false;
        return true;
    });

    if (hash && allowedPages.includes(hash)) {
        const navItems = document.querySelectorAll('.nav-item[onclick]');
        let matchEl = null;
        navItems.forEach(n => {
            if (n.getAttribute('onclick') && n.getAttribute('onclick').includes("'" + hash + "'")) {
                matchEl = n;
            }
        });
        showPage(hash, matchEl);
    }
})();

function toggleSidebar() {
    const sidebar     = document.getElementById('sidebar');
    const overlay     = document.getElementById('sidebarOverlay');
    const isMobile    = window.innerWidth <= 768;

    if (isMobile) {
        // Mobile: slide in/out
        const isOpen = sidebar.classList.contains('mobile-open');
        if (isOpen) {
            closeSidebar();
        } else {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
        }
    } else {
        // Desktop: collapse to icon-only rail
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        // Let CSS sibling selectors handle topbar/main-content shifts;
        // inline styles override them, so sync manually:
        document.querySelector('.topbar').style.left             = isCollapsed ? '70px' : '260px';
        document.querySelector('.main-content').style.marginLeft = isCollapsed ? '70px' : '260px';
        // Persist preference
        localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
}

// Restore desktop collapsed state on load
(function () {
    if (window.innerWidth > 768 && localStorage.getItem('sidebarCollapsed') === '1') {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.add('collapsed');
        document.querySelector('.topbar').style.left             = '70px';
        document.querySelector('.main-content').style.marginLeft = '70px';
    }
})();

// Close sidebar when pressing Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
});

// â”€â”€ Start Session Modal â”€â”€
function onRentalModeChange() {
    const mode            = document.getElementById('rentalModeSelect').value;
    const group           = document.getElementById('durationPickerGroup');
    const preview         = document.getElementById('sessionPreview');
    const hourlyPayGroup  = document.getElementById('startPaymentGroup');
    const unlimPayGroup   = document.getElementById('unlimitedPaymentGroup');
    const openTimeNote    = document.getElementById('openTimeNote');
    const toggle          = document.getElementById('collectNowToggle');

    // Strict Rule: Unlimited not allowed at 7:00 PM or later
    const now = new Date();
    const h = now.getHours();
    const unlimOpt = document.getElementById('ssUnlimOption');
    const unlimMsg = document.getElementById('ssUnlimRestrictedMsg');
    
    if (h >= 19) {
        if (unlimOpt) unlimOpt.disabled = true;
        if (unlimMsg) unlimMsg.style.display = 'block';
        if (mode === 'unlimited') {
            alert('Unlimited mode is not available at 7:00 PM or later.');
            document.getElementById('rentalModeSelect').value = 'hourly';
            onRentalModeChange();
            return;
        }
    } else {
        if (unlimOpt) unlimOpt.disabled = false;
        if (unlimMsg) unlimMsg.style.display = 'none';
    }

    // Duration picker: only for hourly
    group.style.display   = (mode === 'hourly') ? 'block' : 'none';
    if (mode === 'hourly') {
        restrictStartSessionDuration();
    }
    
    if (mode !== 'hourly') {
        preview.style.display = 'none';
        document.getElementById('plannedMinutesInput').value = '';
        document.getElementById('durationSelect').value      = '';
    }

    // Show the right payment section per mode
    hourlyPayGroup.style.display  = (mode === 'hourly')     ? 'block' : 'none';
    unlimPayGroup.style.display   = (mode === 'unlimited')  ? 'block' : 'none';
    openTimeNote.style.display    = (mode === 'open_time')  ? 'block' : 'none';

    // Reset optional checkbox each time mode switches to hourly
    if (mode === 'hourly') {
        toggle.checked = false;
        document.getElementById('startPaymentFields').style.display = 'none';
        document.getElementById('startTendered').value = '';
    }

    // Pre-fill unlimTendered with flat rate and lock it when switching to unlimited
    if (mode === 'unlimited') {
        const cost = parseFloat(document.getElementById('unlimCostAmt').textContent) || 0;
        const inp  = document.getElementById('unlimTendered');
        if (inp) {
            inp.value    = cost > 0 ? cost.toFixed(2) : '';
            inp.readOnly = true;
        }
        const tog     = document.getElementById('unlimTenderedToggle');
        const wrapper = document.getElementById('unlimTenderedWrapper');
        const icon    = document.getElementById('unlimTenderedIcon');
        const hint    = document.getElementById('unlimTenderedHintText');
        if (tog)     tog.checked = false;
        if (wrapper) { wrapper.classList.remove('tendered-wrapper-unlocked'); wrapper.classList.add('tendered-wrapper-locked'); }
        if (icon)    { icon.className = 'fas fa-lock tendered-lock'; }
        if (hint)    { hint.style.display = 'block'; }
        document.getElementById('unlimChangeDisplay').style.display = 'none';
    }

    if (typeof syncControllerRentalModesFromSession === 'function') syncControllerRentalModesFromSession();
    if (typeof syncAdminControllerDurationCaps === 'function') syncAdminControllerDurationCaps();
    if (typeof applyTwelveHourForUnlimitedConsoleSession === 'function') {
        applyTwelveHourForUnlimitedConsoleSession(true);
    }
    // Re-evaluate Start button for the new mode
    if (typeof _syncStartBtn === 'function') _syncStartBtn();
}

/**
 * Restricts duration options in Start Session modal based on current time
 */
function restrictStartSessionDuration() {
    const sel = document.getElementById('durationSelect');
    if (!sel) return;

    const now = new Date();
    const currentMins = now.getHours() * 60 + now.getMinutes();
    const midnightMins = 24 * 60;
    const maxMinsAllowed = midnightMins - currentMins;

    let firstValid = null;
    Array.from(sel.options).forEach(opt => {
        if (opt.value === "" || opt.disabled) return;
        const val = parseInt(opt.value);
        const total = parseInt(opt.dataset.total || val);
        if (total > maxMinsAllowed) {
            opt.disabled = true;
            opt.style.color = '#555';
        } else {
            opt.disabled = false;
            opt.style.color = '';
            if (!firstValid) firstValid = opt.value;
        }
    });

    if (sel.value && sel.options[sel.selectedIndex].disabled) {
        sel.value = firstValid || "";
        updateSessionPreview();
    }
}

/** Paid minutes to total play (paid + bonus) —” mirrors PHP paidToTotalMinutes; requires global PRICING. */
function jsPaidToTotalMinutes(paid) {
    if (typeof PRICING === 'undefined') return paid;
    const p = parseInt(paid, 10) || 0;
    if (p <= 0) return p;
    const bp = parseInt(PRICING.bonus_paid_minutes, 10) || 120;
    const bf = parseInt(PRICING.bonus_free_minutes, 10) || 0;
    if (bp <= 0) return p;
    return p + Math.floor(p / bp) * bf;
}

/** Open Time / Unlimited: max selectable controller rental (minutes) in Start Session. */
var ADMIN_CTRL_MAX_MINS_OPEN_OR_UNLI = 720;

/**
 * Same bonus/bracket timing as console open-time (_timedCost): use controller ₱/hr on paid blocks
 * and scale tier/bracket peso amounts vs the selected console reference rate.
 */
function _controllerOpenTimeFee(totalMin, controllerHourlyRate) {
    if (totalMin <= 0) return 0;
    const ctrlR = parseFloat(controllerHourlyRate) || 0;
    if (ctrlR <= 0) return 0;
    if (typeof PRICING === 'undefined') return ctrlR * (totalMin / 60);

    const bp = PRICING.bonus_paid_minutes;
    const bf = PRICING.bonus_free_minutes;
    const consoleR = typeof getConsoleRate === 'function' ? getConsoleRate() : 0;
    const ref = consoleR > 0 ? consoleR : parseFloat(PRICING.hourly_rate || 0);
    const brScale = ref > 0 ? (ctrlR / ref) : 1;

    const cyclePay = bp / 60 * ctrlR;
    const cycleLen = bp + bf;
    const full = Math.floor(totalMin / cycleLen);
    let cost = full * cyclePay;
    const rem = totalMin % cycleLen;

    if (rem > bp) {
        cost += cyclePay;
    } else {
        const sub = typeof _bracketCost === 'function' ? _bracketCost(rem % 60) * brScale : 0;
        cost += Math.floor(rem / 60) * ctrlR + sub;
    }
    return cost;
}



/* â”€â”€ Controller Rental: Xbox-only â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Hides/shows the controller rental checkbox depending on the selected
console type. Only Xbox units support controller rentals.
*/
function onConsoleChange() {
    const sel    = document.getElementById('consoleSelect');
    const opt    = sel ? sel.options[sel.selectedIndex] : null;
    const type   = opt ? (opt.dataset.type || '') : '';
    const unit   = opt ? (opt.text.split(' \u2014')[0].trim()) : '';
    const group  = document.getElementById('controllerRentalGroup');

    // Sync unit number to hidden field for logging
    const ssUnit = document.getElementById('ssUnitNumber');
    if (ssUnit) ssUnit.value = unit;

    const toggle = document.getElementById('controllerRentalToggle');
    const label  = document.getElementById('controllerRentalLabel');
    const icon   = document.getElementById('ctrlRentalIcon');
    const text   = document.getElementById('ctrlAvailText');
    const cSelect = document.getElementById('controllerSelect');
    if (!group) return;

    // Look up availability for this console type
    const ctrlList = (typeof CTRL_LIST_BY_TYPE !== 'undefined' && type) ? (CTRL_LIST_BY_TYPE[type] || []) : [];
    const available = ctrlList.length;

    if (available > 0) {
        group.style.display = 'block';
        if (toggle) {
            toggle.disabled = false;
            if (toggle.checked) {
                toggle.checked = false;
                onControllerToggle();
            }
        }
        if (label) label.style.cursor = 'pointer';
        if (icon)  icon.style.color   = 'var(--clr-mint)';
        if (text) {
            text.innerHTML = `<i class="fas fa-check-circle" style="color:#20c8a1;margin-right:3px;"></i>`
                           + `<strong style="color:#20c8a1;">${available}</strong>`
                           + ` controller${available !== 1 ? 's' : ''} available`;
            text.style.color = '#888';
        }
        
        // Populate specific controller select
        if (cSelect) {
            cSelect.innerHTML = '<option value="" disabled selected>—” Select Controller 1 —”</option>';
            const cSelect2 = document.getElementById('controllerSelect2');
            if (cSelect2) cSelect2.innerHTML = '<option value="" disabled selected>—” Select Controller 2 —”</option>';
            ctrlList.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.dataset.rate = c.rate;
                opt.textContent = `${c.unit} (+₱${c.rate}/hr)`;
                cSelect.appendChild(opt);
                
                if (cSelect2) {
                    const opt2 = document.createElement('option');
                    opt2.value = c.id;
                    opt2.dataset.rate = c.rate;
                    opt2.textContent = `${c.unit} (+₱${c.rate}/hr)`;
                    cSelect2.appendChild(opt2);
                }
            });
            onControllerSelectChange();
        }
    } else {
        group.style.display = 'none';
        if (toggle && toggle.checked) {
            toggle.checked = false;
            onControllerToggle();
        }
    }

    // Refresh duration option labels to reflect this console's per-type rate
    if (typeof _refreshDurationLabels === 'function') _refreshDurationLabels();
}

function onControllerToggle() {
    const toggle    = document.getElementById('controllerRentalToggle');
    const container = document.getElementById('controllerSelectContainer');
    const qtyWrap   = document.getElementById('ctrlQtyWrap');
    const countSel  = document.getElementById('adminControllerCount');
    const ctrl2     = document.getElementById('adminCtrl2Block');

    if (toggle && container) {
        if (toggle.checked) {
            container.style.display = 'block';
            if (qtyWrap)  qtyWrap.style.display  = 'block';
            if (countSel) countSel.disabled = false;
            if (ctrl2) ctrl2.style.display = countSel?.value === '2' ? 'block' : 'none';
        } else {
            container.style.display = 'none';
            if (qtyWrap)  qtyWrap.style.display  = 'none';
            if (countSel) countSel.disabled = true;

            // Reset controller selections
            const s1 = document.getElementById('controllerSelect'); if (s1) s1.value = '';
            const s2 = document.getElementById('controllerSelect2'); if (s2) s2.value = '';
        }
        recalcAdminControllerFee();
    }
}

function onAdminControllerToggle() {
    const countSel = document.getElementById('adminControllerCount');
    const ctrl2    = document.getElementById('adminCtrl2Block');
    if (ctrl2) ctrl2.style.display = countSel?.value === '2' ? 'block' : 'none';
    
    if (countSel?.value !== '2') {
        const s2 = document.getElementById('controllerSelect2'); if (s2) s2.value = '';
    }
    recalcAdminControllerFee();
}



function onControllerSelectChange() {
    onAdminCtrl1Change();
    onAdminCtrl2Change();
}

function onAdminCtrl1Change() {
    syncAdminControllerDropdowns();
    recalcAdminControllerFee();
}

function onAdminCtrl2Change() {
    syncAdminControllerDropdowns();
    recalcAdminControllerFee();
}

function syncAdminControllerDropdowns() {
    const sel1 = document.getElementById('controllerSelect');
    const sel2 = document.getElementById('controllerSelect2');
    if (!sel1 || !sel2) return;
    
    const val1 = sel1.value;
    const val2 = sel2.value;
    
    Array.from(sel1.options).forEach(opt => {
        if (opt.value !== "" && opt.value === val2) opt.style.display = "none";
        else opt.style.display = "";
    });
    Array.from(sel2.options).forEach(opt => {
        if (opt.value !== "" && opt.value === val1) opt.style.display = "none";
        else opt.style.display = "";
    });
}

function recalcAdminControllerFee() {
    const toggle    = document.getElementById('controllerRentalToggle');
    const feeInput  = document.getElementById('controllerFeeAmt');
    const rateDisp  = document.getElementById('ctrlRateDisplay');
    
    // Controller rentals are now exclusively Open Time.
    // Upfront fee is always 0.
    if (feeInput) feeInput.value = 0;
    
    if (rateDisp) {
        if (toggle?.checked) {
            const sel1 = document.getElementById('controllerSelect');
            const rate = (sel1 && sel1.selectedIndex > 0) 
                ? (sel1.options[sel1.selectedIndex].dataset.rate || 0) : 0;
            rateDisp.textContent = rate > 0 ? `(₱${Math.round(rate)}/hr Open Time)` : '';
        } else {
            rateDisp.textContent = '';
        }
    }
    updateSessionPreview();
}


/**
 * Recompute cost labels in any duration dropdown using a given hourly rate.
 */
function refreshDurationLabels(selectId, rate) {
    const minChg  = PRICING.session_min_charge;
    const bp      = PRICING.bonus_paid_minutes;
    const bf      = PRICING.bonus_free_minutes;
    const sel     = document.getElementById(selectId);
    if (!sel) return;

    Array.from(sel.options).forEach(function(opt) {
        const paid = parseInt(opt.value);
        if (!paid) return;

        const bonus = Math.floor(paid / bp) * bf;
        const total = paid + bonus;
        opt.dataset.total = String(total);
        const cost  = paid <= 30 ? minChg : (paid / 60 * rate);
        opt.dataset.cost = cost.toFixed(2);

        // Human-readable format: "1h 30m" or "1h" or "30m"
        const h = Math.floor(paid / 60), m = paid % 60;
        let label = (h ? h + 'h' : '') + (h && m ? ' ' : '') + (m ? m + 'm' : '');

        let bonusLabel = '';
        if (bonus > 0) {
            const bH = Math.floor(bonus / 60), bM = bonus % 60;
            const bStr = (bH ? bH + 'h' : '') + (bH && bM ? ' ' : '') + (bM ? bM + 'm' : '');
            bonusLabel = ' (+' + bStr + ')';
        }
        opt.textContent = label + ' \u2014 \u20b1' + Math.round(cost) + bonusLabel;
    });
}

/* Recompute cost labels in the Start Session duration dropdown. */
function _refreshDurationLabels() {
    refreshDurationLabels('durationSelect', getConsoleRate());
    // Refresh the preview if a duration is already selected
    const sel = document.getElementById('durationSelect');
    if (sel && sel.value) updateSessionPreview();
}

/* Show/hide payment method when the optional checkbox is toggled */
function toggleStartPaymentFields(checkbox) {
    const fields = document.getElementById('startPaymentFields');
    fields.style.display = checkbox.checked ? 'block' : 'none';
    if (checkbox.checked) {
        // Pre-fill startTendered with current session cost and lock it
        const cost = parseFloat(document.getElementById('startCostAmt').textContent) || 0;
        const inp  = document.getElementById('startTendered');
        if (inp) {
            inp.value    = cost > 0 ? cost.toFixed(2) : '';
            inp.readOnly = true;
        }
        // Ensure toggle checkbox is unchecked (locked state)
        const tog = document.getElementById('startTenderedToggle');
        if (tog) tog.checked = false;
        const wrapper = document.getElementById('startTenderedWrapper');
        const icon    = document.getElementById('startTenderedIcon');
        const hint    = document.getElementById('startTenderedHintText');
        if (wrapper) { wrapper.classList.remove('tendered-wrapper-unlocked'); wrapper.classList.add('tendered-wrapper-locked'); }
        if (icon)    { icon.className = 'fas fa-lock tendered-lock'; }
        if (hint)    { hint.style.display = 'block'; }
        document.getElementById('startChangeDisplay').style.display = 'none';
    } else {
        document.getElementById('startTendered').value = '';
        document.getElementById('startChangeDisplay').style.display = 'none';
    }
    // Re-evaluate button state whenever checkbox changes
    _syncStartBtn();
}

/* â”€â”€ Change calculator â”€â”€
   tenderedId  : id of the amount-tendered input
   displayId   : id of the change display div
   costHolderId: id of element whose textContent/value holds the amount due
*/
function calcChange(tenderedId, displayId, costHolderId) {
    const el   = document.getElementById(costHolderId);
    const due  = parseFloat(el.value !== undefined ? el.value : el.textContent) || 0;
    const paid = parseFloat(document.getElementById(tenderedId).value) || 0;
    const disp = document.getElementById(displayId);
    // Short-payment notices - end modal and pay modal
    const endShortNotice = document.getElementById('endShortNotice');
    const payShortNotice = document.getElementById('payShortNotice');

    if (!paid) {
        disp.style.display = 'none';
        if (endShortNotice) endShortNotice.style.display = 'none';
        if (payShortNotice) payShortNotice.style.display = 'none';
        return;
    }

    const change = paid - due;
    disp.style.display = 'block';
    if (change >= 0) {
        disp.style.background = 'rgba(32,200,161,.15)';
        disp.style.border     = '1px solid rgba(32,200,161,.3)';
        disp.style.color      = '#20c8a1';
        disp.innerHTML        = `<i class="fas fa-coins"></i> Change: <strong>₱${change.toFixed(2)}</strong>`;
        if (endShortNotice) endShortNotice.style.display = 'none';
        if (payShortNotice) payShortNotice.style.display = 'none';
    } else {
        disp.style.background = 'rgba(251,86,107,.15)';
        disp.style.border     = '1px solid rgba(251,86,107,.3)';
        disp.style.color      = '#fb566b';
        disp.innerHTML        = `<i class="fas fa-exclamation-circle"></i> Insufficient - short by <strong>₱${Math.abs(change).toFixed(2)}</strong>`;
        if (endShortNotice) endShortNotice.style.display = 'block';
        if (payShortNotice) payShortNotice.style.display = 'block';
    }
}

/**
 * toggleTendered - generic lock/unlock for pre-filled tendered fields.
 * Used by End Session, Collect Balance modals.
 * @param {string} inputId       - id of the number input
 * @param {string} cbId          - id of the checkbox
 * @param {string} costHolderId  - id of the hidden cost holder
 * @param {string} changeDispId  - id of the change display div
 */
function toggleTendered(inputId, cbId, costHolderId, changeDispId) {
    const inp     = document.getElementById(inputId);
    const cb      = document.getElementById(cbId);
    const icon    = document.getElementById(inputId + 'Icon');
    const wrapper = document.getElementById(inputId + 'Wrapper');
    const hint    = document.getElementById(inputId + 'HintText');
    if (!inp) return;

    if (cb && cb.checked) {
        inp.readOnly = false;
        if (wrapper) { wrapper.classList.remove('tendered-wrapper-locked'); wrapper.classList.add('tendered-wrapper-unlocked'); }
        if (icon) { icon.className = 'fas fa-unlock tendered-lock'; }
        if (hint) hint.style.display = 'none';
        inp.focus(); inp.select();
    } else {
        const el   = document.getElementById(costHolderId);
        const cost = el ? (parseFloat(el.value || el.textContent) || 0) : 0;
        inp.value    = cost > 0 ? cost.toFixed(2) : '';
        inp.readOnly = true;
        if (wrapper) { wrapper.classList.remove('tendered-wrapper-unlocked'); wrapper.classList.add('tendered-wrapper-locked'); }
        if (icon) { icon.className = 'fas fa-lock tendered-lock'; }
        if (hint) hint.style.display = 'block';
        const disp = document.getElementById(changeDispId);
        if (disp) disp.style.display = 'none';
    }
}

/* preFillTendered removed - setAmountDue/setPayDue handle initial pre-fill */


/**
 * Called by the End Session confirm button.
 * Copies the visible tendered input into the hidden POST field, then lets the form submit.
 * No blocking - a short payment is always allowed through.
 */
function syncTenderedAndSubmit(e) {
    // Block if the early-end warning is active (confirm button disabled)
    const confirmBtn = document.getElementById('endSessionConfirmBtn');
    if (confirmBtn && confirmBtn.disabled) {
        e.preventDefault();
        // Pulse the warning banner to draw attention
        const warn = document.getElementById('endEarlyWarning');
        if (warn) {
            warn.style.transition = 'box-shadow .15s';
            warn.style.boxShadow  = '0 0 0 3px rgba(241,168,60,.5)';
            setTimeout(() => { warn.style.boxShadow = ''; }, 600);
        }
        return false;
    }

    const tenderedVal = document.getElementById('endTendered').value;
    const payGroup    = document.getElementById('endPaymentMethodGroup');

    // If the payment section is visible (balance due) and no amount entered, block submission
    if (payGroup && payGroup.style.display !== 'none') {
        if (!tenderedVal || parseFloat(tenderedVal) <= 0) {
            e.preventDefault();
            // Highlight the tendered input
            const input = document.getElementById('endTendered');
            input.style.borderColor = '#fb566b';
            input.style.boxShadow   = '0 0 0 3px rgba(251,86,107,.25)';
            input.focus();
            input.setAttribute('placeholder', 'âš  Enter amount tendered');
            return false;
        }
    }

    document.getElementById('endTenderedHidden').value = tenderedVal;
    playSessionEndSound();  // Audio cue just before form submits
    // Form submits normally after this (no e.preventDefault())
}

function updateSessionPreview() {
    const sel     = document.getElementById('durationSelect');
    const paid    = parseInt(sel.value);
    const input   = document.getElementById('plannedMinutesInput');
    const preview = document.getElementById('sessionPreview');
    if (!paid) {
        preview.style.display = 'none';
        input.value = '';
        if (typeof syncAdminControllerDurationCaps === 'function') syncAdminControllerDurationCaps();
        return;
    }

    input.value = paid;

    // Read cost and total play time from data-* set by PHP (getHourlyDurationOptions - DB-driven)
    const opt        = sel.options[sel.selectedIndex];
    let   cost       = parseFloat(opt.dataset.cost  || 0);
    const totalMin   = parseInt(opt.dataset.total   || paid);   // paid + bonus

    // Controller rental fee (DB-driven, echoed by PHP)
    const ctrlToggle = document.getElementById('controllerRentalToggle');
    const ctrlFee    = parseFloat(document.getElementById('controllerFeeAmt')?.value || 0);
    if (ctrlToggle?.checked) cost += ctrlFee;

    // Scheduled end uses TOTAL play minutes (paid + bonus)
    const now    = new Date();
    const endAt  = new Date(now.getTime() + totalMin * 60000);
    const endStr = endAt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });

    // Update the change calculator
    const costHolder = document.getElementById('startCostAmt');
    if (costHolder) costHolder.textContent = cost.toFixed(2);

    document.getElementById('previewEndTime').textContent = endStr;
    document.getElementById('previewCost').textContent    = '₱' + cost.toFixed(2);
    document.getElementById('previewOvertime').style.display = 'block';
    preview.style.display = 'block';
    if (typeof syncAdminControllerDurationCaps === 'function') syncAdminControllerDurationCaps();
    // Re-evaluate Start button whenever cost changes
    if (typeof _syncStartBtn === 'function') _syncStartBtn();
}

// Alias - called by controller rental checkbox onchange
const recalcSessionPreview = updateSessionPreview;

// Form validation: require duration for hourly
document.addEventListener('DOMContentLoaded', function () {
    // Show duration picker by default (hourly is default selected)
    onRentalModeChange();
    // Hide controller rental until an Xbox console is selected
    onConsoleChange();

    document.getElementById('startSessionForm').addEventListener('submit', function (e) {
        const mode = document.getElementById('rentalModeSelect').value;

        // â”€â”€ Validation 1: hourly requires a duration â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if (mode === 'hourly' && !document.getElementById('durationSelect').value) {
            e.preventDefault();
            showInlineToast('Please select a duration for the hourly session.', 'error');
            return;
        }

        // â”€â”€ Validation 2: short payment guard â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        // Hourly optional collect-now
        if (mode === 'hourly') {
            const collectNow = document.getElementById('collectNowToggle');
            if (collectNow && collectNow.checked) {
                const tendered = parseFloat(document.getElementById('startTendered').value) || 0;
                const due      = parseFloat(document.getElementById('startCostAmt').textContent) || 0;
                if (due > 0 && tendered < due) {
                    e.preventDefault();
                    _showStartShortError('short by \u20b1' + (due - tendered).toFixed(2) + ' - please collect the full amount or uncheck payment.');
                    return;
                }
            }
        }

        // Unlimited - always mandatory
        if (mode === 'unlimited') {
            const tendered = parseFloat(document.getElementById('unlimTendered').value) || 0;
            const due      = parseFloat(document.getElementById('unlimCostAmt').textContent) || 0;
            if (due > 0 && tendered < due) {
                e.preventDefault();
                _showStartShortError('Flat rate of \u20b1' + due.toFixed(2) + ' must be collected in full before starting.');
                return;
            }
        }

        const ctrlDurErr = typeof getStartSessionControllerDurationError === 'function'
            ? getStartSessionControllerDurationError() : '';
        if (ctrlDurErr) {
            e.preventDefault();
            if (typeof showInlineToast === 'function') {
                showInlineToast(ctrlDurErr, 'error');
            } else {
                alert(ctrlDurErr);
            }
            return;
        }
    });

    // Wire up live re-validation to dismiss the error when user fixes the amount
    // â”€â”€ Modals â”€â”€
    ['startTendered','unlimTendered'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() { _clearStartShortError(); });
    });
});

function _showStartShortError(msg) {
    let banner = document.getElementById('startShortErrorBanner');
    if (!banner) {
        banner = document.createElement('div');
        banner.id = 'startShortErrorBanner';
        banner.style.cssText = 'display:flex;align-items:center;gap:10px;background:rgba(251,86,107,.13);border:1.5px solid rgba(251,86,107,.45);border-radius:12px;padding:12px 16px;margin-top:12px;font-size:13px;font-weight:600;color:#fb566b;animation:shakeX .35s;';
        // inject before the submit button
        const btn = document.querySelector('#startSessionForm .btn-prim');
        if (btn) btn.parentNode.insertBefore(banner, btn);
    }
    banner.innerHTML = '<i class="fas fa-circle-exclamation"></i><span>' + msg + '</span>';
    banner.style.display = 'flex';
    // Disable submit button briefly
    const btn = document.querySelector('#startSessionForm .btn-prim');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.animation = 'shakeX .35s';
        setTimeout(function() { btn.style.animation = ''; }, 400);
    }
}

function _clearStartShortError() {
    const banner = document.getElementById('startShortErrorBanner');
    if (banner) banner.style.display = 'none';
    const btn = document.querySelector('#startSessionForm .btn-prim');
    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
}

/**
 * _syncStartBtn —” called live on every input, cost change, or mode change.
 * Disables the Start button if the tendered amount is empty or below the session cost.
 */
function _syncStartBtn() {
    const mode = document.getElementById('rentalModeSelect') ?
                 document.getElementById('rentalModeSelect').value : '';
    const btn  = document.querySelector('#startSessionForm .btn-prim');
    if (!btn) return;

    let isShort  = false;
    let shortMsg = '';

    if (mode === 'hourly') {
        const collectNow = document.getElementById('collectNowToggle');
        if (collectNow && collectNow.checked) {
            const tenderedVal = document.getElementById('startTendered').value;
            const tendered    = parseFloat(tenderedVal) || 0;
            const due         = parseFloat(document.getElementById('startCostAmt').textContent) || 0;
            if (due > 0 && (tenderedVal === '' || tendered < due)) {
                isShort  = true;
                shortMsg = tenderedVal === ''
                    ? 'Enter the amount tendered to start the session.'
                    : 'Short by \u20b1' + (due - tendered).toFixed(2) + ' \u2014 collect the full amount or uncheck payment.';
            }
        }
    } else if (mode === 'unlimited') {
        const tenderedVal = document.getElementById('unlimTendered').value;
        const tendered    = parseFloat(tenderedVal) || 0;
        const due         = parseFloat(document.getElementById('unlimCostAmt').textContent) || 0;
        if (due > 0 && (tenderedVal === '' || tendered < due)) {
            isShort  = true;
            shortMsg = tenderedVal === ''
                ? 'Flat rate of \u20b1' + due.toFixed(2) + ' must be collected before starting.'
                : 'Short by \u20b1' + (due - tendered).toFixed(2) + ' \u2014 flat rate must be paid in full.';
        }
    }

    const ctrlErr = typeof getStartSessionControllerDurationError === 'function'
        ? getStartSessionControllerDurationError() : '';
    if (!isShort && ctrlErr) {
        isShort = true;
        shortMsg = ctrlErr;
    }

    if (isShort) {
        _showStartShortError(shortMsg);
    } else {
        _clearStartShortError();
    }
}




function openModal(name) {
    document.getElementById(name + 'Modal').classList.add('active');
    if (name === 'startSession' && typeof onRentalModeChange === 'function') {
        onRentalModeChange();
    }
}
function closeModal(name) {
    document.getElementById(name + 'Modal').classList.remove('active');
    if (name === 'endSession' && typeof _endModalTimer !== 'undefined' && _endModalTimer) {
        clearInterval(_endModalTimer);
        _endModalTimer = null;
    }
}
// Close on outside click
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
});

/* â”€â”€ Billing helpers - all values driven from DB via getPricingRules() â”€â”€â”€â”€â”€â”€â”€â”€ *
 * PRICING is injected by PHP so the JS always matches the backend.
 * _bracketCost / _timedCost are unchanged in shape - only their constants move.
 */
const PRICING = <?= json_encode(getPricingRules()) ?>;
// Available controllers for the rental dropdown —” populated from DB on page load
const _availableControllers = <?= json_encode($availableControllers ?? []) ?>;

/**
 * Return the hourly rate for the currently selected console in the
 * Start Session modal, falling back to the global PRICING default.
 */
var _currentSessionRate = null;

function getConsoleRate() {
    if (_currentSessionRate !== null) return _currentSessionRate;
    
    const sel = document.getElementById('consoleSelect');
    const opt = sel && sel.options[sel.selectedIndex];
    const rate = opt ? parseFloat(opt.dataset.rate || 0) : 0;
    if (rate > 0) return rate;

    const type = opt ? (opt.dataset.type || '') : '';
    return (PRICING.console_rates_by_name && PRICING.console_rates_by_name[type])
        || PRICING.hourly_rate;
}

function _bracketCost(partialMin) {
    if (partialMin <= 0) return 0;
    const rate = getConsoleRate();
    
    // Formula: 1-4 mins = ₱0 grace period
    if (partialMin < 5) return 0;
    
    // Bracket width: 15 min = 25% of hourly_rate
    const bracket = Math.min(Math.ceil((partialMin - 4) / 15), 4);
    return Math.round(bracket * (rate / 4));
}
function _timedCost(totalMin) {
    if (totalMin <= 0) return 0;
    const bp       = PRICING.bonus_paid_minutes;  // e.g. 120
    const bf       = PRICING.bonus_free_minutes;  // e.g. 30
    const rate     = getConsoleRate();            // per-type rate from console_types
    const cyclePay = bp / 60 * rate;
    const cycleLen = bp + bf;
    const full     = Math.floor(totalMin / cycleLen);
    const rem      = totalMin % cycleLen;
    let cost       = full * cyclePay;
    if (rem > bp) {
        cost += cyclePay;  // inside the free window - charge the full paid block
    } else {
        cost += Math.floor(rem / 60) * rate + _bracketCost(rem % 60);
    }
    return cost;
}
function _hourlyCost(duration, planned) {
    const overtime = duration - planned;
    if (overtime <= 0) {
        // Early or exact end —” bill only actual elapsed time
        return duration <= 0 ? 0 : _timedCost(duration);
    }
    // Overtime —” base (planned cost) + overtime brackets
    const base = planned <= 30 ? PRICING.session_min_charge : _timedCost(planned);
    return base + _timedCost(overtime);
}

let _endModalTimer = null;   // holds the live-update interval

// Stores refund-modal args when the admin triggers "Refund & End" from the early-end warning
let _pendingRefundArgs = null;

/* â”€â”€ Session-end audio alert (Web Audio API - no file needed) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Plays a short 3-beep chime when the admin confirms ending a session.
Uses the browser—™s built-in synthesis - works offline, no CDN required.
*/
function playSessionEndSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [0, 0.20, 0.40].forEach(function(delay) {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(880, ctx.currentTime + delay);
            gain.gain.setValueAtTime(0.38, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.18);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + 0.18);
        });
    } catch(e) { /* AudioContext unavailable - silently ignore */ }
}

function openEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate, sourceReservationId, consoleHourlyRate) {
    _currentSessionRate = parseFloat(consoleHourlyRate) || null;
    upfrontPaid           = upfrontPaid           || 0;
    reservationDownpayment = reservationDownpayment || 0;
    sourceReservationId   = sourceReservationId   || 0;
    document.getElementById('endSessionId').value = sessionId;

    // Fetch approved extras (controller rental etc.) FIRST, then render modal
    fetch('ajax/session_extras.php?session_id=' + sessionId, { credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(ex){
            _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate,
                ex.extras || 0, ex.items || [], sourceReservationId, ex, consoleHourlyRate);
        })
        .catch(function(){
            _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate, 0, [], sourceReservationId, {}, consoleHourlyRate);
        });
}

function _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate, extras, extraItems, sourceReservationId, extrasData, consoleHourlyRate) {
    extras                 = extras                 || 0;
    extrasData             = extrasData             || {};
    reservationDownpayment = reservationDownpayment || 0;

    // Helper: Update the itemized billing breakdown UI
    function updateBreakdown(currentMins, grossTimeCost, finalDue) {
        const b = document.getElementById('endSessionTransparentBreakdown');
        if (!b) return;
        b.style.display = 'block';

        const h = Math.floor(currentMins / 60), m = currentMins % 60;
        document.getElementById('ebd-time-label').textContent = h ? `${h}h ${m}m` : `${m}m`;
        document.getElementById('ebd-gross-cost').textContent = '₱' + grossTimeCost.toFixed(2);
        
        const extrasRow = document.getElementById('ebd-extras-row');
        if (extras > 0) {
            extrasRow.style.display = 'flex';
            document.getElementById('ebd-extras-cost').textContent = '₱' + extras.toFixed(2);
        } else {
            extrasRow.style.display = 'none';
        }

        const upfrontRow = document.getElementById('ebd-upfront-row');
        // Upfront paid is the TOTAL paid so far. For breakdown, we show 
        // pure upfront (non-reservation) and reservation credit separately.
        const pureUpfront = Math.max(0, upfrontPaid - reservationDownpayment);
        if (pureUpfront > 0) {
            upfrontRow.style.display = 'flex';
            document.getElementById('ebd-upfront-paid').textContent = '-₱' + pureUpfront.toFixed(2);
        } else {
            upfrontRow.style.display = 'none';
        }

        const resRow = document.getElementById('ebd-res-row');
        if (reservationDownpayment > 0) {
            resRow.style.display = 'flex';
            document.getElementById('ebd-res-credit').textContent = '-₱' + reservationDownpayment.toFixed(2);
        } else {
            resRow.style.display = 'none';
        }

        document.getElementById('ebd-final-due').textContent = '₱' + Math.max(0, finalDue).toFixed(2);
    }
    sourceReservationId    = sourceReservationId    || 0;

    // â”€â”€ Reservation-source notice â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Show a pill inside the modal if this session was started from a reservation
    const resNotice = document.getElementById('endSessionResNotice');
    if (resNotice) {
        if (sourceReservationId > 0) {
            resNotice.style.display = 'flex';
            resNotice.querySelector('.res-notice-id').textContent = '#' + sourceReservationId;
            // Update the non-refundable note
            const nrNote = resNotice.querySelector('.res-nonrefundable-note');
            if (nrNote) {
                nrNote.textContent = reservationDownpayment > 0
                    ? '\u20b1' + reservationDownpayment.toFixed(2) + ' reservation fee is non-refundable and will be deducted from any refund.'
                    : 'The reservation fee is credited toward this session\'s total cost.';
            }
        } else {
            resNotice.style.display = 'none';
        }
    }

    // â”€â”€ Confirm button always enabled (no early-end refund flow) â”€â”€
    const confirmBtn = document.getElementById('endSessionConfirmBtn');
    if (confirmBtn) {
        confirmBtn.disabled      = false;
        confirmBtn.style.opacity = '1';
        confirmBtn.style.cursor  = 'pointer';
    }

    // â”€â”€ End early-end guard â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    // â”€â”€ Helper: drive the extras pill badge below the big cost number â”€â”€â”€â”€â”€
    function updateExtrasTag(extrasVal, items) {
        const tag     = document.getElementById('endExtrasTag');
        const tagText = document.getElementById('endExtrasTagText');
        if (!tag) return;
        if (extrasVal > 0) {
            const names = (items || []).map(function(i){ return i.description; }).join(', ');
            tagText.textContent = (names || 'extras') + ' +\u20b1' + extrasVal.toFixed(2);
            tag.style.display = 'block';
        } else {
            tag.style.display = 'none';
        }
    }


    // â”€â”€ End early-end guard â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€





    // â”€â”€ End early-end guard â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    const panel       = document.getElementById('endCostPanel');
    const elapsedEl   = document.getElementById('endElapsed');
    const costEl      = document.getElementById('endEstCost');
    const noteEl      = document.getElementById('endCostNote');
    const payGroup    = document.getElementById('endPaymentMethodGroup');
    const payLabel    = document.getElementById('endPaymentMethodLabel');
    const prepaidNote = document.getElementById('endPrepaidNote');
    const confirmLbl  = document.getElementById('endSessionConfirmLabel');
    const titleEl     = document.getElementById('endSessionModalTitle');

    // Clear any previous live timer
    if (_endModalTimer) { clearInterval(_endModalTimer); _endModalTimer = null; }

    // Reset tendered field to locked state on every open
    // (setAmountDue will pre-fill value once cost is known)
    const tenderedEl  = document.getElementById('endTendered');
    const changeDisp  = document.getElementById('endChangeDisplay');
    const costHolder  = document.getElementById('endCostAmtHolder');
    const amountDueEl = document.getElementById('endAmountDueDisplay');
    const amountDueLbl= document.getElementById('endAmountDueLabel');
    const amountDueBox= document.getElementById('endAmountDueBox');
    const cb = document.getElementById('endTenderedToggle');
    const endWrapper = document.getElementById('endTenderedWrapper');
    if (cb) cb.checked = false;
    tenderedEl.value    = '';
    tenderedEl.readOnly = true;
    if (endWrapper) { endWrapper.classList.remove('tendered-wrapper-unlocked'); endWrapper.classList.add('tendered-wrapper-locked'); }
    const tendIconEl = document.getElementById('endTenderedIcon');
    if (tendIconEl) { tendIconEl.className = 'fas fa-lock tendered-lock'; }
    const endHint = document.getElementById('endTenderedHintText');
    if (endHint) endHint.style.display = 'block';
    changeDisp.style.display = 'none';
    costHolder.value         = '0';
    document.getElementById('endTenderedHidden').value = '';
    const shortNotice = document.getElementById('endShortNotice');
    if (shortNotice) shortNotice.style.display = 'none';

    // Helper: update the big amount-due display + sync cost holder + pre-fill tendered
    function setAmountDue(amount, sublabel) {
        costHolder.value        = amount.toFixed(2);
        amountDueEl.textContent = '\u20b1' + amount.toFixed(2);
        if (sublabel !== undefined) amountDueLbl.textContent = sublabel;
        amountDueBox.style.display = 'block';
        // Auto pre-fill tendered if still in locked state
        const cbEl = document.getElementById('endTenderedToggle');
        if (!cbEl || !cbEl.checked) {
            tenderedEl.value = amount > 0 ? amount.toFixed(2) : '';
        }
    }
    function hideAmountDue() {
        amountDueBox.style.display = 'none';
        tenderedEl.value = '';
        changeDisp.style.display = 'none';
    }

    const modeLabel = mode === 'open_time' ? 'Open Time'
                    : mode === 'unlimited' ? 'Unlimited'
                    : 'Hourly';

    document.getElementById('endSessionSummary').textContent =
        `Ending session #${sessionId} - ${customerName} on ${unitNumber} (${modeLabel})`;

    /* â”€â”€ OPEN TIME: pay at end, show live ticking cost â”€â”€ */
    if (mode === 'open_time' && startTs) {
        titleEl.innerHTML     = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session & Collect Payment';
        panel.style.display   = 'block';
        payGroup.style.display = 'block';
        prepaidNote.style.display = 'none';
        payLabel.textContent  = 'Payment Method';
        confirmLbl.textContent = 'Confirm End & Record Payment';
        noteEl.innerHTML = '<i class="fas fa-info-circle"></i> Cost is calculated at end - collect from customer after confirming.';

        function tick() {
            const elapsed = Math.floor((Date.now() / 1000) - startTs);
            const minutes = Math.floor(elapsed / 60);
            const secs    = elapsed % 60;
            const h = Math.floor(minutes / 60), m = minutes % 60;
            elapsedEl.textContent = (h ? h + 'h ' : '') + String(m).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
            const dueCost = _timedCost(minutes) + extras;
            costEl.textContent = '\u20b1' + dueCost.toFixed(2);
            updateExtrasTag(extras, extraItems);
            const remaining = Math.max(0, dueCost - upfrontPaid);
            
            // Update breakdown
            updateBreakdown(minutes, dueCost - extras, remaining);

            // Sync cost holder + big display
            if (remaining > 0) {
                setAmountDue(remaining, `${String(h ? h + 'h ' : '')}${String(m).padStart(2,'0')}:${String(secs).padStart(2,'0')} elapsed${upfrontPaid > 0 ? ' (Prepaid: ₱' + upfrontPaid.toFixed(2) + ')' : ''}`);
            } else {
                hideAmountDue();
                costHolder.value = '0';
            }
            if (tenderedEl.value) calcChange('endTendered','endChangeDisplay','endCostAmtHolder');
        }
        tick();
        _endModalTimer = setInterval(tick, 1000);

    /* â”€â”€ HOURLY: charge actual elapsed time; overtime if beyond booked â”€â”€ */
    } else if (mode === 'hourly' && plannedMinutes) {
        const elapsed  = Math.floor((Date.now() / 1000) - startTs);
        const minutes  = Math.floor(elapsed / 60);
        const overtime = minutes - plannedMinutes; // positive = over booked time

        // Actual cost = what we bill RIGHT NOW based on elapsed time
        // Early end  â†’ _timedCost(elapsed minutes)   [matches PHP computeRentalFee early-end path]
        // Overtime   â†’ planned base + overtime bracket charge
        let cost;
        if (overtime > 0) {
            const base = upfrontPaid > 0
                ? upfrontPaid
                : (plannedMinutes <= 30 ? PRICING.session_min_charge : _timedCost(plannedMinutes));
            cost = base + _timedCost(overtime) + extras;
        } else {
            // Early or exact end —” charge only actual consumed time
            cost = (minutes <= 0 ? 0 : _timedCost(minutes)) + extras;
        }

        const ph = Math.floor(plannedMinutes / 60), pm = plannedMinutes % 60;
        const bookedStr = ph ? (pm ? `${ph}h ${pm}m` : `${ph}h`) : `${pm}m`;

        panel.style.display = 'block';
        elapsedEl.textContent = String(Math.floor(minutes/60)).padStart(2,'0') + 'h ' + String(minutes%60).padStart(2,'0') + 'm';
        costEl.textContent    = '\u20b1' + cost.toFixed(2);
        updateExtrasTag(extras, extraItems);

        const remaining = Math.max(0, cost - upfrontPaid);

        if (remaining > 0) {
            if (overtime > 0) {
                const base = upfrontPaid > 0
                    ? upfrontPaid
                    : (plannedMinutes <= 30 ? PRICING.session_min_charge : _timedCost(plannedMinutes));
                setAmountDue(remaining, `Total base + overtime: ₱${cost.toFixed(2)} - Prepaid: ₱${upfrontPaid.toFixed(2)}`);
                noteEl.innerHTML = `<i class="fas fa-clock"></i> Booked: <strong>${bookedStr}</strong> (₱${base.toFixed(2)}).<br>`
                                 + `<span style="color:#fb566b">Overtime: +${overtime} min. Total remaining due: ₱${remaining.toFixed(2)}.</span>`;
            } else {
                // Early end —” collect actual time cost only
                setAmountDue(remaining, `Actual time used: ${minutes}m â†’ ₱${cost.toFixed(2)} - Prepaid: ₱${upfrontPaid.toFixed(2)}`);
                noteEl.innerHTML = `<i class="fas fa-coins"></i> Early end —” charged for <strong>${minutes} min</strong> used. Collect <strong>₱${remaining.toFixed(2)}</strong> now.`;
            }
            
            // Update breakdown
            updateBreakdown(minutes, cost - extras, remaining);

            titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session - Collect Payment';
            payGroup.style.display    = 'block';
            prepaidNote.style.display = 'none';
            payLabel.textContent      = 'Payment Method';
            confirmLbl.textContent    = `Confirm End & Collect ₱${remaining.toFixed(2)}`;
        } else {
            // Session fully paid (upfront ≥ actual cost)
            hideAmountDue();
            costHolder.value = '0';
            titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session - Paid in Full';
            noteEl.innerHTML  = `<i class="fas fa-check-circle" style="color:#20c8a1"></i> Total cost ₱${cost.toFixed(2)} already paid. No additional charge.`;
            payGroup.style.display    = 'none';
            prepaidNote.style.display = 'block';
            confirmLbl.textContent    = 'Confirm End (No Additional Charge)';
            
            // Update breakdown even if paid in full
            updateBreakdown(minutes, cost - extras, 0);
        }

    /* â”€â”€ UNLIMITED: flat rate was fully prepaid â”€â”€ */
    } else if (mode === 'unlimited') {
        titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session - Paid in Full';
        panel.style.display       = 'block';
        elapsedEl.textContent     = '-';
        costEl.textContent        = 'Flat rate';
        noteEl.innerHTML          = '<i class="fas fa-infinity"></i> Unlimited session - flat rate already collected at start.';
        hideAmountDue();
        payGroup.style.display    = 'none';
        prepaidNote.style.display = 'block';
        confirmLbl.textContent    = 'Confirm End (No Additional Charge)';

        // Update breakdown for unlimited
        updateBreakdown(0, unlimitedRate, 0);

    } else {
        panel.style.display = 'none';
        payGroup.style.display = 'block';
        prepaidNote.style.display = 'none';
        confirmLbl.textContent = 'Confirm End & Record Payment';
    }

    /* â”€â”€ Controller add-on: per-controller end-early panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    const ctrlEarlyPanel = document.getElementById('endCtrlRentalEarlyPanel');
    const ctrlEarlyMsg   = document.getElementById('endCtrlRentalEarlyMsg');
    const endModalEl     = document.getElementById('endSessionModal');
    if (endModalEl) {
        endModalEl.dataset.endSessionCtx = JSON.stringify({
            sessionId: sessionId,
            customerName: customerName,
            unitNumber: unitNumber,
            mode: mode,
            startTs: startTs,
            plannedMinutes: plannedMinutes,
            upfrontPaid: upfrontPaid,
            reservationDownpayment: reservationDownpayment,
            unlimitedRate: unlimitedRate,
            sourceReservationId: sourceReservationId,
            hourlyRate: consoleHourlyRate
        });
    }

    const ctrlRows = (extrasData && extrasData.controller_rows) ? extrasData.controller_rows : [];
    const activeCtrlRows = ctrlRows.filter(r => !r.is_ended);

    if (ctrlEarlyPanel) {
        if (activeCtrlRows.length > 0) {
            ctrlEarlyPanel.style.display = 'block';
            if (ctrlEarlyMsg) { ctrlEarlyMsg.textContent = ''; }

            // Build header row
            let html = '<div style="font-weight:700;color:#8aa4e8;font-size:13px;margin-bottom:10px;">'
                     + '<i class="fas fa-gamepad" style="margin-right:6px;"></i> Controller add-on'
                     + '<span style="font-size:11px;font-weight:400;color:#888;margin-left:8px;">Return individual controllers early —” fee prorated to elapsed time.</span>'
                     + '</div>';

            // Per-controller rows
            html += '  <div id="ctrlPerRowList" style="display:flex; flex-direction:column; gap:8px;">';
            activeCtrlRows.forEach(function(cr) {
                // Real-time calculation
                const rentedAt = new Date(cr.rented_at.replace(/-/g, "/"));
                const elapsedMins = Math.max(0, Math.floor((Date.now() - rentedAt) / 60000));
                const fee = Math.floor((elapsedMins + 25) / 30) * 10;
                
                const durH = Math.floor(elapsedMins / 60);
                const durM = elapsedMins % 60;
                const durStr = (durH > 0 ? durH + 'h ' : '') + durM + 'm';
                const rentedStr = rentedAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                const endedStr  = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

                html += '<div id="ctrlRow_' + cr.controller_id + '" style="display:flex;flex-direction:column;gap:10px;background:rgba(95,133,218,.07);border:1px solid rgba(95,133,218,.2);border-radius:10px;padding:12px 14px;">'
                      + '  <div style="display:flex;align-items:center;gap:10px;">'
                      + '    <i class="fas fa-gamepad" style="color:#8aa4e8;font-size:14px;flex-shrink:0;"></i>'
                      + '    <div style="flex:1;min-width:0;">'
                      + '      <div style="font-weight:700;color:#f0f0f0;font-size:13px;">' + cr.label + '</div>'
                      + '      <div style="font-size:11px;color:#20c8a1;margin-top:2px;font-weight:600;">Actual fee: ₱' + fee + ' <span style="color:#666;font-weight:400;">(Used ' + durStr + ')</span></div>'
                      + '    </div>'
                      + '    <button type="button" class="btn-sec btn-sm ctrl-single-prepare-btn" '
                      + '       data-cid="' + cr.controller_id + '" '
                      + '       data-label="' + cr.label + '" '
                      + '       data-rented="' + rentedStr + '" '
                      + '       data-ended="' + endedStr + '" '
                      + '       data-fee="' + fee + '" '
                      + '       data-duration="' + durStr + '" '
                      + '       style="white-space:nowrap;font-size:11px;padding:7px 12px;">'
                      + '      <i class="fas fa-hand-holding"></i> End Now'
                      + '    </button>'
                      + '  </div>'
                      + '  <div id="ctrlSummary_' + cr.controller_id + '" style="display:none; margin-top:5px; padding-top:10px; border-top:1px dashed rgba(255,255,255,.1);">'
                      + '     <div style="display:grid; grid-template-columns:1fr 1fr; gap:5px; font-size:10px; color:#aaa; text-transform:uppercase;">'
                      + '        <div>Rented At</div><div>Ended At</div>'
                      + '        <div style="color:#eee; font-weight:600;">' + rentedStr + '</div><div style="color:#eee; font-weight:600;">' + endedStr + '</div>'
                      + '     </div>'
                      + '     <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">'
                      + '        <button type="button" class="ctrl-single-cancel-btn" data-cid="' + cr.controller_id + '" style="background:none; border:none; color:#fb566b; font-size:11px; cursor:pointer; padding:0;">Cancel</button>'
                      + '        <button type="button" class="btn-prim btn-sm ctrl-single-confirm-btn" data-cid="' + cr.controller_id + '" data-fee="' + fee + '" style="font-size:11px; padding:5px 15px;">Confirm End · ₱' + fee + '</button>'
                      + '     </div>'
                      + '  </div>'
                      + '</div>';
            });
            html += '</div>';

            if (activeCtrlRows.length > 1) {
                html += '<div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.08);">'
                      + '  <button type="button" id="endAllCtrlBtn" class="btn-dang" style="width:100%; padding:10px; font-size:12px; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:8px;">'
                      + '    <i class="fas fa-power-off"></i> End ALL controllers now'
                      + '  </button>'
                      + '</div>';
            }



            ctrlEarlyPanel.innerHTML = html;

            // Wire up per-controller buttons
            ctrlEarlyPanel.querySelectorAll('.ctrl-single-prepare-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const cid = btn.dataset.cid;
                    const summary = document.getElementById('ctrlSummary_' + cid);
                    if (summary) {
                        btn.style.display = 'none';
                        summary.style.display = 'block';
                    }
                });
            });

            ctrlEarlyPanel.querySelectorAll('.ctrl-single-cancel-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const cid = btn.dataset.cid;
                    const summary = document.getElementById('ctrlSummary_' + cid);
                    const prepareBtn = ctrlEarlyPanel.querySelector('.ctrl-single-prepare-btn[data-cid="' + cid + '"]');
                    if (summary && prepareBtn) {
                        summary.style.display = 'none';
                        prepareBtn.style.display = 'block';
                    }
                });
            });

            ctrlEarlyPanel.querySelectorAll('.ctrl-single-confirm-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const cid = parseInt(btn.dataset.cid, 10);
                    const fee = btn.dataset.fee;
                    if (!cid) return;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    
                    const fd = new FormData();
                    fd.append('csrf_token', window.GSPOT_CSRF || '');
                    fd.append('session_id', String(sessionId));
                    fd.append('controller_id', String(cid));
                    
                    fetch('ajax/end_single_controller_early.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            const row = document.getElementById('ctrlRow_' + cid);
                            if (data.success) {
                                if (row) {
                                    row.style.opacity = '0.5';
                                    row.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:40px;width:100%;color:#20c8a1;font-weight:700;font-size:12px;">'
                                                  + '<i class="fas fa-check-circle" style="margin-right:6px;"></i> Controller Ended & Payment Recorded</div>';
                                }
                                // Refresh modal context after short delay
                                let ctx = {};
                                try { ctx = JSON.parse(endModalEl.dataset.endSessionCtx || '{}'); } catch(e) {}
                                setTimeout(function() {
                                    openEndSessionModal(
                                        ctx.sessionId, ctx.customerName, ctx.unitNumber,
                                        ctx.mode, ctx.startTs, ctx.plannedMinutes,
                                        ctx.upfrontPaid, ctx.reservationDownpayment,
                                        ctx.unlimitedRate, ctx.sourceReservationId, ctx.hourlyRate
                                    );
                                }, 1500);
                            } else {
                                btn.disabled = false;
                                btn.innerHTML = 'Confirm End · ₱' + fee;
                                alert(data.message || 'Failed.');
                            }
                        })
                        .catch(function() {
                            btn.disabled = false;
                            btn.innerHTML = 'Confirm End · ₱' + fee;
                            alert('Network error.');
                        });
                });
            });

            // Wire up "End ALL" button
            const endAllBtn = document.getElementById('endAllCtrlBtn');
            if (endAllBtn) {
                endAllBtn.addEventListener('click', function() {
                    // Recalculate fees at the exact moment of click
                    const now = new Date();
                    const endedStr = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                    
                    const items = activeCtrlRows.map(function(cr) {
                        const rentedAt = new Date(cr.rented_at.replace(/-/g, '/'));
                        const elapsed  = Math.max(0, Math.floor((now - rentedAt) / 60000));
                        const fee      = elapsed < 5 ? 0 : Math.floor((elapsed + 25) / 30) * 10;
                        const h = Math.floor(elapsed / 60), m = elapsed % 60;
                        const durStr = (h > 0 ? h + 'h ' : '') + m + 'm';
                        const rentedStr = rentedAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                        return { label: cr.label, fee: fee, durStr: durStr, rentedStr: rentedStr, elapsed: elapsed };
                    });
                    
                    const totalFee = items.reduce(function(sum, it) { return sum + it.fee; }, 0);
                    
                    // Populate Payment Summary modal
                    const set = function(elId, val) { const el = document.getElementById(elId); if (el) el.textContent = val; };
                    set('ecs-controllers', items.length + 'x Controller' + (items.length > 1 ? 's' : ''));
                    set('ecs-console', unitNumber);
                    set('ecs-rented', items.length === 1 ? items[0].rentedStr : 'Multiple');
                    set('ecs-ended', endedStr);
                    set('ecs-duration', items.length === 1 ? items[0].durStr : '—”');
                    set('ecs-total-fee', '₱' + totalFee);
                    
                    // Show per-controller breakdown
                    const breakdown = document.getElementById('ecs-breakdown');
                    const breakdownList = document.getElementById('ecs-breakdown-list');
                    if (breakdown && breakdownList) {
                        breakdown.style.display = 'block';
                        breakdownList.innerHTML = items.map(function(it) {
                            return '<div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">' +
                                   '  <span style="color:#aaa;"><i class="fas fa-gamepad" style="font-size:10px;margin-right:5px;"></i>' + it.label + ' <span style="color:#666;">(' + it.durStr + ')</span></span>' +
                                   '  <span style="color:#fff; font-weight:600;">₱' + it.fee + '</span>' +
                                   '</div>';
                        }).join('');
                    }
                    
                    // Wire Confirm button to fire the AJAX end for ALL controllers
                    const confirmBtn = document.getElementById('ecsConfirmBtn');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm End All · ₱' + totalFee;
                        confirmBtn.onclick = function() {
                            confirmEndControllerRental(sessionId, totalFee);
                        };
                    }
                    
                    openModal('endControllerSummary');
                });
            }

        } else {
            ctrlEarlyPanel.style.display = 'none';
        }
    }


    openModal('endSession');
}

/** Active Controller Rentals table + quick actions */
function gspotEndControllerRentalEarly(sid, items) {
    const id = parseInt(sid, 10) || 0;
    if (!id) return;

    // Find the row to get current data
    const row = document.getElementById('cr-row-' + id);
    if (!row) return;

    const rentedTs = parseInt(row.getAttribute('data-start'), 10) || 0;
    const qty      = parseInt(row.getAttribute('data-qty'), 10) || 1;
    const consoleUnit = row.cells[2].innerText.trim();
    const customer    = row.cells[1].innerText.trim();
    const rentedStr   = row.cells[5].innerText.trim(); // Rented At column

    if (!rentedTs) return;

    const now = new Date();
    const nowTs = Math.floor(now.getTime() / 1000);
    const elapsedMins = Math.max(0, Math.floor((nowTs - rentedTs) / 60));

    // Billing rule: floor((mins + 25) / 30) * 10 per controller
    // 5-34m = 10, 35-64m = 20...
    const feePerCtrl = Math.floor((elapsedMins + 25) / 30) * 10;
    const totalFee   = items ? items.reduce((sum, it) => sum + it.fee, 0) : (qty * feePerCtrl);

    // Formatting duration
    const h = Math.floor(elapsedMins / 60);
    const m = elapsedMins % 60;
    const durStr = (h > 0 ? h + 'h ' : '') + m + 'm';

    // Formatting current time
    const endedStr = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

    // Populate Modal
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('ecs-controllers', qty + 'x Controller' + (qty > 1 ? 's' : ''));
    set('ecs-console', consoleUnit);
    set('ecs-rented', rentedStr);
    set('ecs-ended', endedStr);
    set('ecs-duration', durStr);
    set('ecs-total-fee', '₱' + totalFee);

    // Breakdown display
    const breakdown = document.getElementById('ecs-breakdown');
    const breakdownList = document.getElementById('ecs-breakdown-list');
    if (breakdown && breakdownList) {
        if (items && items.length > 1) {
            breakdown.style.display = 'block';
            breakdownList.innerHTML = items.map(function(it) {
                return '<div style="display:flex; justify-content:space-between; font-size:12px;">' +
                       '  <span style="color:#aaa;">' + it.label + '</span>' +
                       '  <span style="color:#fff; font-weight:600;">₱' + it.fee + '</span>' +
                       '</div>';
            }).join('');
        } else if (!items && qty > 1) {
            breakdown.style.display = 'block';
            breakdownList.innerHTML = '';
            for (let i=0; i<qty; i++) {
                breakdownList.innerHTML += 
                    '<div style="display:flex; justify-content:space-between; font-size:12px;">' +
                    '  <span style="color:#aaa;">Controller ' + (i+1) + '</span>' +
                    '  <span style="color:#fff; font-weight:600;">₱' + feePerCtrl + '</span>' +
                    '</div>';
            }
        } else {
            breakdown.style.display = 'none';
        }
    }

    // Update Confirm Button
    const btn = document.getElementById('ecsConfirmBtn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm End';
        btn.onclick = function() { confirmEndControllerRental(id, totalFee); };
    }

    openModal('endControllerSummary');
}

/* â”€â”€ Pay Modal (collect outstanding balance, session continues) â”€â”€â”€â”€â”€â”€â”€â”€ */
let _payModalTimer = null;

function openPayModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimitedRate) {
    upfrontPaid   = upfrontPaid   || 0;
    unlimitedRate = unlimitedRate || 300;

    document.getElementById('paySessionId').value = sessionId;
    document.getElementById('paySessionSummary').textContent =
        'Session #' + sessionId + ' - ' + customerName + ' on ' + unitNumber +
        ' (' + (mode === 'open_time' ? 'Open Time' : mode === 'unlimited' ? 'Unlimited' : 'Hourly') + ')';

    // Reset pay modal tendered field to locked state
    const payTendInp = document.getElementById('payTendered');
    const payTendCb  = document.getElementById('payTenderedToggle');
    const payWrapper = document.getElementById('payTenderedWrapper');
    if (payTendCb) payTendCb.checked = false;
    if (payTendInp) { payTendInp.value = ''; payTendInp.readOnly = true; }
    if (payWrapper) { payWrapper.classList.remove('tendered-wrapper-unlocked'); payWrapper.classList.add('tendered-wrapper-locked'); }
    const payTendIcon = document.getElementById('payTenderedIcon');
    if (payTendIcon) { payTendIcon.className = 'fas fa-lock tendered-lock'; }
    const payHint = document.getElementById('payTenderedHintText');
    if (payHint) payHint.style.display = 'block';
    document.getElementById('payChangeDisplay').style.display = 'none';
    document.getElementById('payShortNotice').style.display   = 'none';
    document.getElementById('payAmountDueDisplay').style.color = '#20c8a1';

    if (_payModalTimer) { clearInterval(_payModalTimer); _payModalTimer = null; }

    // Fetch approved extras FIRST (controller rental etc.), then render
    fetch('ajax/session_extras.php?session_id=' + sessionId, { credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(ex){
            _renderPayModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, unlimitedRate,
                ex.extras || 0, ex.items || []);
        })
        .catch(function(){
            _renderPayModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, unlimitedRate, 0, []);
        });
}

function _renderPayModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimitedRate, extras, extraItems) {
    extras = extras || 0;

    const costPanel   = document.getElementById('payCostPanel');
    const elapsedEl   = document.getElementById('payElapsed');
    const costEl      = document.getElementById('payEstCost');
    const breakdownEl = document.getElementById('payCostBreakdown');
    const dueBigEl    = document.getElementById('payAmountDueDisplay');
    const dueLblEl    = document.getElementById('payAmountDueLabel');
    const amtHidden   = document.getElementById('payAmount');
    const confirmBtn  = document.getElementById('payConfirmBtn');
    const confirmLbl  = document.getElementById('payConfirmLabel');

    function setPayDue(due, sublabel) {
        dueBigEl.textContent   = '\u20b1' + due.toFixed(2);
        dueLblEl.textContent   = sublabel || '';
        amtHidden.value        = due.toFixed(2);
        if (due > 0) {
            confirmLbl.textContent = 'Collect \u20b1' + due.toFixed(2) + ' Balance';
            confirmBtn.disabled    = false;
            confirmBtn.style.opacity = '1';
        } else {
            confirmLbl.textContent = 'No Balance Due';
            confirmBtn.disabled    = true;
            confirmBtn.style.opacity = '0.5';
        }
        // Auto pre-fill payTendered if still in locked state
        const payCb = document.getElementById('payTenderedToggle');
        const payInp = document.getElementById('payTendered');
        if (payInp && (!payCb || !payCb.checked)) {
            payInp.value = due > 0 ? due.toFixed(2) : '';
        }
        // Refresh change display if tendered already manually entered
        if (document.getElementById('payTendered').value && payCb && payCb.checked)
            calcChange('payTendered','payChangeDisplay','payAmount');
    }

    /* â”€â”€ Open Time: live-ticking balance â”€â”€ */
    if (mode === 'open_time' && startTs) {
        costPanel.style.display = 'block';
        var payTick = function() {
            const elapsed  = Math.floor((Date.now() / 1000) - startTs);
            const minutes  = Math.floor(elapsed / 60);
            const h = Math.floor(minutes / 60), m = minutes % 60, s = elapsed % 60;
            elapsedEl.textContent = (h ? h + 'h ' : '') + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
            const timeCost  = _timedCost(minutes);
            const totalCost = timeCost + extras;
            costEl.textContent  = '₱' + totalCost.toFixed(2);
            const due = Math.max(0, totalCost - upfrontPaid);
            let sublabel = upfrontPaid > 0
                ? 'Running cost ₱' + totalCost.toFixed(2) + ' - Already paid ₱' + upfrontPaid.toFixed(2)
                : 'Cost accumulating - pay at any time';
            setPayDue(due, sublabel);
        };
        payTick();
        _payModalTimer = setInterval(payTick, 1000);

    /* â”€â”€ Hourly: snapshot at open time â”€â”€ */
    } else if (mode === 'hourly' && plannedMinutes && startTs) {
        costPanel.style.display = 'block';
        const elapsed   = Math.floor((Date.now() / 1000) - startTs);
        const minutes   = Math.floor(elapsed / 60);
        const timeCost  = _hourlyCost(minutes, plannedMinutes);
        const totalCost = timeCost + extras;               // â†’Â extras included
        const due       = Math.max(0, totalCost - upfrontPaid);
        const h = Math.floor(minutes / 60), m = minutes % 60;
        elapsedEl.textContent = (h ? h + 'h ' : '') + String(m).padStart(2,'0') + 'm';
        costEl.textContent    = '₱' + totalCost.toFixed(2);
        const overtime  = Math.max(0, minutes - plannedMinutes);
        const ph = Math.floor(plannedMinutes / 60), pm = plannedMinutes % 60;
        const bookedStr = ph ? (pm ? ph + 'h ' + pm + 'm' : ph + 'h') : pm + 'm';
        let sublabel;
        if (overtime > 0) {
            const baseCost = plannedMinutes <= 30 ? PRICING.session_min_charge : _timedCost(plannedMinutes);
            sublabel = 'Booked ' + bookedStr + ' (₱' + baseCost.toFixed(0) + ') + ' + overtime + 'min overtime';
        } else {
            sublabel = 'Actual time used: ' + minutes + 'min â†’ ₱' + timeCost.toFixed(2);
        }
        if (upfrontPaid > 0) sublabel += ' - Prepaid ₱' + upfrontPaid.toFixed(2);
        if (extras > 0) {
            const itemNames = (extraItems || []).map(function(i){ return i.description; }).join(', ');
            sublabel += ' - +₱' + extras.toFixed(2) + (itemNames ? ' (' + itemNames + ')' : ' extras');
        }
        setPayDue(due, sublabel);

    /* â”€â”€ Unlimited: flat rate already paid; show extras if any â”€â”€ */
    } else if (mode === 'unlimited') {
        costPanel.style.display = 'none';
        dueBigEl.textContent = extras > 0 ? '₱' + extras.toFixed(2) : '₱0.00';
        dueBigEl.style.color = extras > 0 ? '#20c8a1' : '#888';
        dueLblEl.textContent = extras > 0
            ? 'Flat rate collected - extras outstanding'
            : 'Unlimited session - flat rate already collected at start';
        amtHidden.value = extras > 0 ? extras.toFixed(2) : '0';
        if (extras > 0) {
            confirmLbl.textContent = 'Collect ₱' + extras.toFixed(2) + ' Balance';
            confirmBtn.disabled    = false;
            confirmBtn.style.opacity = '1';
        } else {
            confirmLbl.textContent = 'No Balance Due';
            confirmBtn.disabled    = true;
            confirmBtn.style.opacity = '0.5';
        }

    } else {
        costPanel.style.display = 'none';
        setPayDue(extras > 0 ? extras : 0, extras > 0 ? 'Extras outstanding' : 'Enter amount if needed');
    }

    openModal('paySession');
}

function closePayModal() {
    if (_payModalTimer) { clearInterval(_payModalTimer); _payModalTimer = null; }
    closeModal('paySession');
}

/* Updates the Collect button label to match what will actually be recorded
   (the smaller of tendered vs balance due) */
function syncPayBtn() {
    const balanceDue = parseFloat(document.getElementById('payAmount').value) || 0;
    const tenderedEl = document.getElementById('payTendered');
    const tendered   = parseFloat(tenderedEl.value);
    const confirmLbl = document.getElementById('payConfirmLabel');
    const confirmBtn = document.getElementById('payConfirmBtn');
    if (!tenderedEl.value || isNaN(tendered)) {
        // No tendered value - revert to full balance label
        if (balanceDue > 0) {
            confirmLbl.textContent   = 'Collect \u20b1' + balanceDue.toFixed(2) + ' Balance';
            confirmBtn.disabled      = false;
            confirmBtn.style.opacity = '1';
        }
        return;
    }
    const collect = Math.min(tendered, balanceDue);
    if (collect > 0) {
        confirmLbl.textContent   = 'Collect \u20b1' + collect.toFixed(2);
        confirmBtn.disabled      = false;
        confirmBtn.style.opacity = '1';
    }
}

/* â”€â”€ Refund Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openRefundModal(sessionId, customerName, unitNumber, upfrontPaid, reservationId) {
    const isRes = !!reservationId;
    const paid  = parseFloat(upfrontPaid || 0).toFixed(2);

    // Hidden control fields
    document.getElementById('refundSessionId').value     = sessionId || '';
    document.getElementById('refundReservationId').value = reservationId || '';
    // Map to ajax/refund.php action_type values
    document.getElementById('refundActionField').value   = isRes ? 'reservation' : 'standard';
    document.getElementById('refundEarlyEndFlag').value  = '0';

    // Summary banner text
    document.getElementById('refundSessionSummary').textContent = isRes
        ? 'Reservation #' + reservationId + ' - ' + customerName
        : 'Session #'     + sessionId     + ' - ' + customerName + ' on ' + unitNumber;
    document.getElementById('refundPaidSoFar').textContent = '₱' + paid;

    // Amount input - locked + pre-filled for reservation
    const amtInput = document.getElementById('refundAmount');
    const maxNote  = document.getElementById('refundMaxNote');
    const hintEl   = document.getElementById('refundAutoCalcHint');
    amtInput.readOnly       = isRes;
    amtInput.style.opacity  = isRes ? '0.7' : '1';
    amtInput.style.background   = isRes ? 'rgba(32,200,161,.06)' : '';
    amtInput.style.borderColor  = isRes ? 'rgba(32,200,161,.35)' : '';
    amtInput.style.cursor       = isRes ? 'not-allowed' : '';
    amtInput.value = isRes ? parseFloat(upfrontPaid || 0) : '';
    if (hintEl) hintEl.style.display = 'none';  // hide early-end hint for normal opens
    maxNote.textContent = isRes
        ? 'Full payment amount \u2014 will be returned to customer.'
        : 'Max refundable: \u20b1' + paid;

    // Reason input - pre-filled for reservation
    const reasonInput = document.getElementById('refundReason');
    reasonInput.readOnly      = isRes;
    reasonInput.style.opacity = isRes ? '0.7' : '1';
    reasonInput.value = isRes ? 'Customer cancelled reservation #' + reservationId : '';

    // Confirm label & early-end note
    const lbl = document.getElementById('refundConfirmLabel');
    if (lbl) lbl.textContent = isRes ? 'Issue Reservation Refund' : 'Confirm Refund';
    const earlyNote = document.getElementById('refundEarlyEndNote');
    if (earlyNote) earlyNote.style.display = 'none';

    openModal('refundSession');
}

/* â”€â”€ Centralized Refund AJAX Submission â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function _submitRefundAjax() {
    const sessionId     = document.getElementById('refundSessionId').value;
    const reservationId = document.getElementById('refundReservationId').value;
    const actionField   = document.getElementById('refundActionField').value;
    const isEarlyEnd    = document.getElementById('refundEarlyEndFlag').value === '1';
    const refundAmt     = parseFloat(document.getElementById('refundAmount').value) || 0;
    const reason        = document.getElementById('refundReason').value.trim();

    // Determine action_type for refund.php
    let action_type = actionField; // 'standard' | 'reservation'
    if (isEarlyEnd) action_type = 'early_end';

    // Standard/manual refunds require a positive amount.
    // early_end with ₱0 is allowed - the session ends with no refund transaction.
    if (action_type !== 'reservation' && action_type !== 'early_end' && refundAmt <= 0) {
        _showRefundError('Please enter a refund amount greater than ₱0.');
        return;
    }

    const confirmMsg = isEarlyEnd
        ? (refundAmt > 0
            ? 'Issue a refund of \u20b1' + refundAmt.toFixed(2) + ' and end the session? This cannot be undone.'
            : 'End the session now? No refund will be issued. This cannot be undone.')
        : 'Issue this refund of \u20b1' + refundAmt.toFixed(2) + '? This cannot be undone.';

    gspotConfirm(confirmMsg, function () {
        const btn = document.getElementById('refundConfirmBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing—¦';

        const body = new URLSearchParams({
            session_id:     sessionId     || '0',
            reservation_id: reservationId || '0',
            refund_amount:  refundAmt.toFixed(2),
            refund_reason:  reason,
            action_type:    action_type,
        });

        fetch('ajax/refund.php', { method: 'POST', credentials: 'same-origin', body })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.success) {
                    closeModal('refundSession');
                    // Show success banner then reload
                    if (window.showToast) {
                        window.showToast(data.message, 'success');
                    }
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    _showRefundError(data.message || 'An unknown error occurred.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-undo-alt"></i> <span id="refundConfirmLabel">Confirm Refund</span>';
                }
            })
            .catch(function() {
                _showRefundError('Network error - please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-undo-alt"></i> <span id="refundConfirmLabel">Confirm Refund</span>';
            });
    }, { danger: true, yesLabel: isEarlyEnd && refundAmt > 0 ? 'Yes, Refund & End' : (isEarlyEnd ? 'Yes, End Session' : 'Yes, Refund') });
}

function _showRefundError(msg) {
    const box  = document.getElementById('refundErrorMsg');
    const text = document.getElementById('refundErrorText');
    if (box && text) {
        text.textContent = msg;
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        alert(msg);
    }
}


/* â”€â”€ Extend Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openExtendModal(sessionId, customerName, unitNumber, bookedMinutes) {
    document.getElementById('extendSessionId').value = sessionId;
    document.getElementById('extendSessionSummary').textContent =
        'Session #' + sessionId + ' - ' + customerName + ' on ' + unitNumber;
    const h = Math.floor(bookedMinutes / 60), m = bookedMinutes % 60;
    document.getElementById('extendCurrentDuration').textContent =
        bookedMinutes > 0
            ? (h ? (m ? h + 'h ' + m + 'm' : h + 'h') : m + 'm')
            : 'Open / Unlimited';
    document.getElementById('extendMinutes').value = '';
    openModal('extendSession');
}

// Stop live timer when modal is closed
document.addEventListener('DOMContentLoaded', function () {
    const endModal = document.getElementById('endSessionModal');
    if (endModal) {
        endModal.addEventListener('click', function (e) {
            if (e.target === endModal && _endModalTimer) {
                clearInterval(_endModalTimer); _endModalTimer = null;
            }
        });
        const closeBtn = endModal.querySelector('.modal-close');
        if (closeBtn) closeBtn.addEventListener('click', function () {
            if (_endModalTimer) { clearInterval(_endModalTimer); _endModalTimer = null; }
        });
    }
});

// ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ Live Session Timers ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬ÃƒÂ¢—ÂÃ¢—šÂ¬
const STALE_THRESHOLD = 24 * 60 * 60; // 24 hours in seconds

function pad(n) { return String(n).padStart(2, '0'); }

// Tracks which timer elements already fired the overtime beep (once per element per load)
const overtimeBeeped = new WeakSet();
// Tracks which timer elements already fired the 15-second warning beep
const warningBeeped  = new WeakSet();

/* â”€â”€ Shared AudioContext â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   Browsers suspend AudioContext when it isn't created inside a user gesture.
   Keep one shared instance and call resume() before every sound so that
   setInterval-driven beeps (overtime, 15-sec warning) can always play.
*/
let _sharedAudioCtx = null;
function _getAudioCtx() {
    if (!_sharedAudioCtx) {
        try { _sharedAudioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
        catch(e) { return null; }
    }
    return _sharedAudioCtx;
}
// Pre-unlock on any user interaction
['click', 'keydown', 'touchstart'].forEach(function(evt) {
    document.addEventListener(evt, function() {
        var c = _getAudioCtx();
        if (c && c.state === 'suspended') c.resume();
    }, { passive: true });
});

/** 
 * â”€â”€ LEVEL UP CHIME (Session Ending Alert) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 * A dedicated, premium ascending arpeggio for session-related alerts.
 * Isolated from general notification sounds to ensure consistency.
 */
function playLevelUpChime() {
    var ctx = _getAudioCtx();
    if (!ctx) return;
    ctx.resume().then(function() {
        var now = ctx.currentTime;
        // Ascending major arpeggio (C5, E5, G5, C6) for a "Level Up" feel
        var notes = [523.25, 659.25, 783.99, 1046.50];
        notes.forEach(function(freq, i) {
            var delay = i * 0.11;
            var osc   = ctx.createOscillator();
            var gain  = ctx.createGain();
            
            osc.connect(gain);
            gain.connect(ctx.destination);
            
            // Use 'triangle' or 'sine' for a soft but clear chime
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, now + delay);
            
            // Note velocity
            var vol = (i === 3) ? 0.22 : 0.15; // Final note slightly louder
            gain.gain.setValueAtTime(vol, now + delay);
            
            // Exponential decay for "bell" effect
            var decay = (i === 3) ? 1.0 : 0.4; // Final note rings longer
            gain.gain.exponentialRampToValueAtTime(0.001, now + delay + decay);
            
            osc.start(now + delay);
            osc.stop(now + delay + decay + 0.1);
        });
    });
}

/* Gentle triple-ding - fires when a session crosses into overtime.
   Uses sine wave for a clean, non-disruptive tone. */
function playOvertimeBeep() {
    // Dedicated Level Up Chime for session ending alerts
    playLevelUpChime();
}

/* â”€â”€ SOFT CHIME - plays for session-end warnings â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   Replaces the emergency siren with a gentle, professional double-chime.
   Uses a pure sine wave with exponential decay for a 'bell-like' feel. */
function playWarningBeep() {
    // Dedicated Level Up Chime for session ending alerts
    playLevelUpChime();
}

/* â”€â”€ SESSION ENDING ALARM MODAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   Fires at 15 s remaining for any hourly session.
   —¢ Covers the full screen (backdrop blocks all interaction)
   —¢ Cannot be dismissed by clicking outside or pressing Escape
   —¢ Auto-navigates the admin to the Sessions tab
   —¢ Offers two actions: Extend Session or End Session Now
   —¢ Countdown inside the modal ticks down every second
   —¢ Auto-dismissed when the session crosses into overtime              */
var sessionEndingAlerts = {}; // key: el.dataset.start â†’ modal element

function showSessionEndingAlert(el, remaining) {
    var key       = el.dataset.start;
    var MODAL_ID  = 'gspotSirenModal';

    // If modal already open for this key, just update countdown
    if (sessionEndingAlerts[key] === 'dismissed') return; // user already acted - never recreate
    if (sessionEndingAlerts[key] === true) {
        // Modal is open - just tick the countdown
        var cdEl = document.getElementById(MODAL_ID + '_cd');
        if (cdEl) cdEl.textContent = remaining + 's';
        return;
    }
    sessionEndingAlerts[key] = true;

    // â”€â”€ Read session data from the timer element â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var customer     = el.dataset.customer     || 'Session';
    var unit         = el.dataset.unit         || '';
    var sessionId    = el.dataset.sessionId    || 0;
    var mode         = el.dataset.mode         || 'hourly';
    var startTs      = parseInt(el.dataset.startTs   || 0);
    var upfrontPaid  = parseFloat(el.dataset.upfrontPaid  || 0);
    var unlimRate    = parseFloat(el.dataset.unlimitedRate || 300);
    var bookedMin    = parseInt(el.dataset.bookedMinutes   || 0);
    var hourlyRate   = parseFloat(el.dataset.hourlyRate    || 0);

    // â”€â”€ Navigate to Sessions tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var sessNavEl = document.querySelector('.nav-item[onclick*="\'sessions\'"]');
    if (sessNavEl) showPage('sessions', sessNavEl);

    // â”€â”€ Build the locked full-screen modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var overlay = document.createElement('div');
    overlay.id  = MODAL_ID;
    overlay.style.cssText =
        'position:fixed;inset:0;z-index:999999;' +
        'background:rgba(8,5,0,.88);backdrop-filter:blur(8px);' +
        'display:flex;align-items:center;justify-content:center;' +
        'animation:gspotSirenFadeIn .25s ease;';

    // Prevent outside-click dismiss - stop all pointer events on backdrop
    overlay.addEventListener('click', function(e) { e.stopPropagation(); });
    document.addEventListener('keydown', _sirenEscBlock, true);

    overlay.innerHTML =
        '<div style="' +
            'background:linear-gradient(160deg,#1c0808,#2a0a0a,#0d0505);' +
            'border:2px solid rgba(251,86,107,.7);border-radius:22px;' +
            'padding:36px 34px 30px;max-width:440px;width:92%;' +
            'box-shadow:0 0 80px rgba(251,86,107,.45),0 24px 64px rgba(0,0,0,.7);' +
            'animation:gspotSirenIn .35s cubic-bezier(.34,1.56,.64,1);' +
            'position:relative;text-align:center;">' +

            /* Pulsing icon */
            '<div style="width:64px;height:64px;border-radius:16px;margin:0 auto 18px;' +
            'background:rgba(251,86,107,.2);border:2px solid rgba(251,86,107,.6);' +
            'display:flex;align-items:center;justify-content:center;font-size:28px;' +
            'animation:gspotSirenPulse .7s ease-in-out infinite;">' +
            '<i class="fas fa-siren-on" style="color:#fb566b;"></i>' +
            '<i class="fas fa-bell" style="color:#fb566b;display:none;" id="gspotSirenIcon"></i></div>' +

            /* Headline */
            '<div style="font-size:10px;font-weight:800;letter-spacing:2.5px;' +
            'color:rgba(251,86,107,.7);text-transform:uppercase;margin-bottom:8px;">' +
            'ðŸš¨ ALERT</div>' +
            '<div style="font-size:22px;font-weight:900;color:#ff6060;margin-bottom:6px;' +
            'letter-spacing:-.3px;">Session Ending!</div>' +
            '<div style="font-size:14px;color:#f0c0c0;margin-bottom:20px;line-height:1.5;">' +
            '<strong style="color:#fff;">' + customer + '</strong>' +
            (unit ? ' &mdash; <span style="color:#f1a83c;">' + unit + '</span>' : '') +
            '</div>' +

            /* Countdown */
            '<div style="background:rgba(251,86,107,.15);border:1px solid rgba(251,86,107,.4);' +
            'border-radius:14px;padding:14px 24px;margin:0 auto 24px;display:inline-block;">' +
            '<div style="font-size:11px;color:#aaa;text-transform:uppercase;letter-spacing:1px;' +
            'margin-bottom:6px;">Time Remaining</div>' +
            '<div id="' + MODAL_ID + '_cd" style="font-size:52px;font-weight:900;' +
            'color:#fb566b;font-family:monospace;line-height:1;letter-spacing:-2px;">' +
            remaining + 's</div></div>' +

            /* Buttons */
            '<div style="display:flex;gap:12px;margin-top:4px;">' +

            /* Extend button */
            '<button id="gspotSirenExtendBtn" ' +
            'style="flex:1;padding:14px 10px;border-radius:12px;' +
            'background:linear-gradient(135deg,rgba(95,133,218,.25),rgba(95,133,218,.15));' +
            'border:1px solid rgba(95,133,218,.55);color:#8aa4e8;' +
            'font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;' +
            'display:flex;align-items:center;justify-content:center;gap:8px;transition:.18s;">' +
            '<i class="fas fa-clock"></i> Extend Session</button>' +

            /* End Now button */
            '<button id="gspotSirenEndBtn" ' +
            'style="flex:1;padding:14px 10px;border-radius:12px;' +
            'background:linear-gradient(135deg,#fb566b,#c0392b);' +
            'border:none;color:#fff;font-size:14px;font-weight:700;cursor:pointer;' +
            'font-family:inherit;' +
            'display:flex;align-items:center;justify-content:center;gap:8px;' +
            'box-shadow:0 4px 20px rgba(251,86,107,.4);transition:.18s;">' +
            '<i class="fas fa-stop-circle"></i> End Session Now</button>' +

            '</div>' +
        '</div>';

    document.body.appendChild(overlay);

    // Fix the siren icon - fa-siren-on might not exist in FA free, use bell as fallback
    var sirenIcon = overlay.querySelector('.fa-siren-on');
    if (!sirenIcon || getComputedStyle(sirenIcon, ':before').content === 'none' ||
        getComputedStyle(sirenIcon, ':before').content === '') {
        if (sirenIcon) sirenIcon.style.display = 'none';
        var bellIcon = overlay.querySelector('.fa-bell');
        if (bellIcon) bellIcon.style.display = '';
    }

    // Extend button â†’ open extend modal, close siren
    document.getElementById('gspotSirenExtendBtn').addEventListener('click', function() {
        _closeSirenModal(key);
        openExtendModal(sessionId, customer, unit, bookedMin, mode, hourlyRate);
    });

    // End Now button â†’ open end session modal, close siren
    document.getElementById('gspotSirenEndBtn').addEventListener('click', function() {
        _closeSirenModal(key);
        // Open the modal in locked mode (prevent outside-click close)
        _sirenTriggeredEnd = true;
        openEndSessionModal(sessionId, customer, unit, mode, startTs, bookedMin, upfrontPaid, 0, unlimRate, 0, hourlyRate);
    });
}

// Block Escape key while siren modal is open
function _sirenEscBlock(e) {
    if (e.key === 'Escape' && document.getElementById('gspotSirenModal')) {
        e.preventDefault(); e.stopPropagation();
    }
}

// Flag: when true, the End Session modal was opened by the siren â†’ prevent outside-click close
var _sirenTriggeredEnd = false;

function _closeSirenModal(key) {
    var modal = document.getElementById('gspotSirenModal');
    if (modal) modal.remove();
    // Mark as 'dismissed' so updateTimers never recreates the modal for this countdown window
    sessionEndingAlerts[key] = 'dismissed';
    _sirenTriggeredEnd = false;
    document.removeEventListener('keydown', _sirenEscBlock, true);
    // Immediately silence all pre-scheduled siren oscillators.
    // ctx.resume() is called again by playOvertimeBeep() / user gestures if needed.
    var ctx = _getAudioCtx();
    if (ctx && ctx.state === 'running') ctx.suspend();
}

// Patch the existing outside-click listener for #endSessionModal to respect _sirenTriggeredEnd
document.addEventListener('DOMContentLoaded', function() {
    var endModal = document.getElementById('endSessionModal');
    if (!endModal) return;
    // Remove the original generic outside-click listener (already applied in admin.php)
    // and replace with one that checks _sirenTriggeredEnd
    endModal.addEventListener('click', function(e) {
        if (e.target === endModal && !_sirenTriggeredEnd) {
            endModal.classList.remove('active');
        }
    });
});

// CSS animations (injected once)
(function() {
    if (document.getElementById('gspotSirenStyle')) return;
    var s = document.createElement('style');
    s.id = 'gspotSirenStyle';
    s.textContent =
        '@keyframes gspotSirenFadeIn{from{opacity:0}to{opacity:1}}' +
        '@keyframes gspotSirenIn{from{opacity:0;transform:scale(.88) translateY(16px)}' +
            'to{opacity:1;transform:scale(1) translateY(0)}}' +
        '@keyframes gspotSirenPulse{' +
            '0%,100%{box-shadow:0 0 0 0 rgba(251,86,107,.7);background:rgba(251,86,107,.2)}' +
            '50%{box-shadow:0 0 0 14px rgba(251,86,107,0);background:rgba(251,86,107,.35)}}';
    document.head.appendChild(s);
})();


function updateTimers() {
    document.querySelectorAll('.no-show-btn[data-start]').forEach(btn => {
        const start = new Date(btn.dataset.start.replace(' ', 'T') + '+08:00');
        const now = new Date();
        if (now >= start) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.title = "No Show";
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.4';
            btn.style.cursor = 'not-allowed';
            btn.title = "No Show will be available once the session has started";
        }
    });

    document.querySelectorAll('.session-timer[data-start]').forEach(el => {
        const start   = new Date(el.dataset.start.replace(' ', 'T') + '+08:00');
        const planned = el.dataset.planned ? parseInt(el.dataset.planned) : null;
        const now     = new Date();
        const elapsed = Math.floor((now - start) / 1000); // seconds

        // Stale session guard (>24h open —”  likely test/orphan data)
        if (elapsed > STALE_THRESHOLD) {
            el.classList.add('stale');
            el.textContent = `âš ï¸ ${Math.floor(elapsed / 86400)}d old —”  end session`;
            return;
        }

        if (planned) {
            // Hourly: show countdown; flip to overtime when past booked time
            const remaining = (planned * 60) - elapsed;
            if (remaining > 0) {
                const h = Math.floor(remaining / 3600);
                const m = Math.floor((remaining % 3600) / 60);
                const s = remaining % 60;

                // â”€â”€ 15-second warning beep + popup (fires once per element) â”€â”€â”€â”€â”€â”€â”€
                if (remaining <= 15 && !warningBeeped.has(el)) {
                    warningBeeped.add(el);
                    playWarningBeep();
                }

                // Update (or create) the ending-soon popup while countdown is active
                if (remaining <= 15) {
                    showSessionEndingAlert(el, remaining);
                }

                // Colour shift: amber when ≤ 60 s, red when ≤ 15 s, green otherwise
                el.style.color = remaining <= 15 ? '#fb566b'
                               : remaining <= 60  ? '#f1a83c'
                               : '#20c8a1';
                el.textContent = (h ? h + 'h ' : '') + `${pad(m)}:${pad(s)} left`;
            } else {
                // â”€ OVERTIME â”€ beep once when the element first crosses the threshold
                if (!overtimeBeeped.has(el)) {
                    overtimeBeeped.add(el);
                    playOvertimeBeep();
                    // Dismiss the siren alarm modal on overtime
                    _closeSirenModal(el.dataset.start);
                }
                const over = -remaining;
                const m = Math.floor(over / 60);
                const s = over % 60;
                el.style.color = '#fb566b';
                el.textContent = `+${pad(m)}:${pad(s)} OVERTIME`;
            }
        } else {
            // Open Time / Unlimited: show elapsed
            const h = Math.floor(elapsed / 3600);
            const m = Math.floor((elapsed % 3600) / 60);
            const s = elapsed % 60;
            el.style.color = '';
            el.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
        }
    });
}
updateTimers();
setInterval(updateTimers, 1000);

function renderCharts() {
    // Guard: charts libs/elements might not exist depending on role/section render.
    if (typeof Chart === 'undefined') return;
    const revEl  = document.getElementById('revChart');
    const typeEl = document.getElementById('typeChart');
    if (!revEl || !typeEl) return;

    Chart.defaults.color = '#fff';
    Chart.defaults.borderColor = 'rgba(255,255,255,.1)';
    const revLabels = <?= json_encode($revLabels) ?>;
    const revData   = <?= json_encode($revChartData) ?>;
    const typeLabels= <?= json_encode($typeLabels) ?>;
    const typeCounts= <?= json_encode($typeCounts) ?>;

    const chartOpts = { 
        responsive: true, 
        plugins: { 
            legend: { labels: { color: '#fff' } },
            tooltip: {
                titleColor: '#fff',
                bodyColor: '#fff',
                footerColor: '#fff'
            }
        },
        scales: { 
            x: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,.05)' } },
            y: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,.05)' } } 
        } 
    };

    const revContext = <?= json_encode($revContext) ?>;
    new Chart(revEl, {
        type: 'bar',
        data: {
            labels: revLabels,
            datasets: [{ label: 'Revenue (₱)', data: revData,
                backgroundColor: 'rgba(32,200,161,.5)', borderColor: '#20c8a1',
                borderWidth: 2, borderRadius: 6 }]
        },
        options: { 
            ...chartOpts, 
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const ctxText = revContext[context.dataIndex];
                            return ctxText ? '\n' + ctxText : '';
                        }
                    }
                }
            } 
        }
    });

    new Chart(typeEl, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeCounts,
                backgroundColor: (Array.isArray(typeLabels) ? typeLabels : []).map(l => {
                    return (typeof window.getConsoleColor === 'function') ? window.getConsoleColor(l) : '#888';
                }),
                borderWidth: 2,
                borderColor: '#0d1117'
            }]
        },
        options: {
            ...chartOpts,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff',
                        font: { size: 11 },
                        padding: 15,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return {
                                        text: `${label} —” ${value} (${percentage}%)`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].borderColor,
                                        fontColor: '#fff',
                                        color: '#fff',
                                        lineWidth: data.datasets[0].borderWidth,
                                        hidden: isNaN(data.datasets[0].data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                }
            }
        }
    });
}

AOS.init({ duration: 600, once: true });

// â”€â”€ Bell notification icon - styles â”€â”€
(function injectNotifStyles() {
    const s = document.createElement('style');
    s.textContent = `
    @keyframes bellPop {
        0%   { transform: scale(0); }
        70%  { transform: scale(1.25); }
        100% { transform: scale(1); }
    }
    @keyframes bellShake {
        0%,100% { transform: rotate(0deg); }
        20%     { transform: rotate(-14deg); }
        40%     { transform: rotate(12deg); }
        60%     { transform: rotate(-8deg); }
        80%     { transform: rotate(6deg); }
    }
    @keyframes dropIn {
        from { opacity:0; transform:translateY(-8px); }
        to   { opacity:1; transform:translateY(0); }
    }
    #notifBellBtn:hover { background:rgba(32,200,161,.15) !important; border-color:rgba(32,200,161,.4) !important; color:#20c8a1 !important; }
    #notifBellBtn.has-notif i { animation:bellShake .5s ease; }
    #notifList::-webkit-scrollbar { width:4px; }
    #notifList::-webkit-scrollbar-track { background:transparent; }
    #notifList::-webkit-scrollbar-thumb { background:rgba(255,255,255,.12); border-radius:4px; }
    `;
    document.head.appendChild(s);
})();

// Bell state
var _notifItems = [];
var _notifDropdownOpen = false;

function toggleNotifDropdown() {
    var drop = document.getElementById('notifDropdown');
    if (!drop) return;
    _notifDropdownOpen = !_notifDropdownOpen;
    drop.style.display = _notifDropdownOpen ? 'block' : 'none';
    // Clear badge when opened
    if (_notifDropdownOpen) {
        document.getElementById('notifBellBadge').style.display = 'none';
        document.getElementById('notifBellBtn').classList.remove('has-notif');
    }
}

function closeNotifDropdown() {
    _notifDropdownOpen = false;
    var drop = document.getElementById('notifDropdown');
    if (drop) drop.style.display = 'none';
}

// Close when clicking outside
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('notifBellWrap');
    if (wrap && !wrap.contains(e.target) && _notifDropdownOpen) closeNotifDropdown();
});

function _addNotifItems(newItems) {
    _notifItems = newItems.concat(_notifItems).slice(0, 20);
    var list   = document.getElementById('notifList');
    var empty  = document.getElementById('notifEmpty');
    var badge  = document.getElementById('notifBellBadge');
    var hBadge = document.getElementById('notifHeaderBadge');
    var btn    = document.getElementById('notifBellBtn');

    console.log('[GSpot Notif] _addNotifItems called. newItems:', newItems.length, '| badge el:', !!badge, '| list el:', !!list);
    if (!list || !badge || !btn) {
        console.warn('[GSpot Notif] Missing DOM elements - bell notification cannot display.');
        return;
    }

    // Rebuild list
    list.innerHTML = '';
    _notifItems.forEach(function(r) {
        var dateStr = '';
        if (r.reserved_date) {
            try { dateStr = new Date(r.reserved_date).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}); } catch(e) { dateStr = r.reserved_date; }
        }
        var timeStr = r.reserved_time ? r.reserved_time.substring(0, 5) : '';
        var mode    = r.rental_mode === 'open_time' ? 'Open Time' : r.rental_mode === 'unlimited' ? 'Unlimited' : 'Hourly';
        var row     = document.createElement('div');
        row.style.cssText = 'padding:10px 18px;border-bottom:1px solid rgba(255,255,255,.05);cursor:pointer;transition:background .15s;';
        row.innerHTML =
            '<div style="display:flex;align-items:center;gap:10px;">' +
            '<div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;background:rgba(32,200,161,.12);' +
            'border:1px solid rgba(32,200,161,.25);display:flex;align-items:center;justify-content:center;color:#20c8a1;font-size:13px;">' +
            '<i class="fas fa-calendar-check"></i></div>' +
            '<div style="min-width:0;flex:1;">' +
            '<div style="font-weight:600;font-size:13px;color:#f0f0f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
            (r.customer_name || 'A customer') + '</div>' +
            '<div style="font-size:11px;color:#888;margin-top:1px;">' +
            (r.console_type || '') + ' · ' + mode + (dateStr ? ' · ' + dateStr : '') + (timeStr ? ' ' + timeStr : '') +
            '</div></div>' +
            '<span style="background:rgba(241,168,60,.15);color:#f1a83c;border:1px solid rgba(241,168,60,.3);' +
            'border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;flex-shrink:0;">Pending</span>' +
            '</div>';
        row.addEventListener('mouseover',  function() { this.style.background = 'rgba(32,200,161,.06)'; });
        row.addEventListener('mouseout',   function() { this.style.background = ''; });
        row.addEventListener('click', function() {
            showPage('reservations', document.querySelector('.nav-item[onclick*="reservations"]'));
            closeNotifDropdown();
        });
        list.appendChild(row);
    });

    if (empty) empty.style.display = _notifItems.length > 0 ? 'none' : 'block';

    if (newItems.length > 0) {
        var count = _notifItems.length;
        badge.textContent = count > 9 ? '9+' : String(count);
        // Force-set display using setAttribute to bypass any inline style conflict
        badge.setAttribute('style',
            'display:flex !important;align-items:center;justify-content:center;' +
            'position:absolute;top:-5px;right:-5px;' +
            'background:#fb566b;color:#fff;border-radius:50%;' +
            'min-width:18px;height:18px;font-size:10px;font-weight:700;' +
            'line-height:18px;text-align:center;padding:0 3px;' +
            'box-shadow:0 0 0 2px #0a0f1c;animation:bellPop .3s ease;'
        );
        if (hBadge) { hBadge.textContent = count + ' new'; hBadge.style.display = 'inline-block'; }
        btn.classList.add('has-notif');
        var bellI = btn.querySelector('i');
        if (bellI) {
            bellI.style.animation = 'none';
            void bellI.offsetWidth;
            bellI.style.animation = 'bellShake .5s ease';
        }
        console.log('[GSpot Notif] Badge shown. count=', count);
    }
}

// â”€â”€ Reservation notification poller â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Polls every 8 s. Baseline from PHP is ALWAYS authoritative at page load -
// localStorage is only used to avoid re-alerting the same IDs within one session,
// but NEVER to INCREASE the baseline above what the server reported.
(function () {
    const POLL_MS = 8000;

    // â”€â”€ BUG FIX #1: Never let localStorage INCREASE the baseline.
    // Old localStorage values from past sessions would block all future alerts.
    let lastTime = <?= time() ?>;
    
    // Only use localStorage to SKIP re-alerting IDs already seen THIS session,
    // but only if stored is between our PHP baseline and max - not to raise it above PHP.
    // Simplest correct fix: always trust PHP baseline, ignore localStorage override.
    

    function poll() {
        fetch('ajax/poll_notifications.php?last_time=' + lastTime, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.new_count > 0) {
                    _addNotifItems(data.items);
                    // Ping sound
                    try {
                        var ctx  = new (window.AudioContext || window.webkitAudioContext)();
                        var osc  = ctx.createOscillator();
                        var gain = ctx.createGain();
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.type = 'sine'; osc.frequency.value = 660;
                        gain.gain.setValueAtTime(0.3, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                        osc.start(); osc.stop(ctx.currentTime + 0.5);
                    } catch(e) {}
                }
                if (data.max_time > lastTime) {
                    lastTime = data.max_time;
                    
                }
            })
            .catch(function() {});
    }

    // â”€â”€ BUG FIX #3: First poll at 3 s, then every 8 s (was 15 s / 30 s)
    setTimeout(function() {
        poll();
        setInterval(poll, POLL_MS);
    }, 3000);
})();

// â”€â”€ Unlimited Session Auto-Termination at 12:00 AM â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Monitors the clock every 30 s. When midnight (00:00 - 00:10) is detected,
// calls ajax/auto_end_unlimited.php once to close all active Unlimited sessions.
// Strictly Unlimited only —” Hourly and Open Time sessions are unaffected.
(function () {
    var _midnightJobFired = false;   // prevent double-firing within the same midnight window
    var POLL_MS = 30000;             // check every 30 seconds

    function _checkMidnight() {
        var now = new Date();
        var h   = now.getHours();
        var m   = now.getMinutes();

        // Trigger window: 00:00 —“ 00:10 (covers late tab wake-ups)
        if (h !== 0 || m > 10) {
            // Outside the midnight window —” reset the flag so next midnight fires again
            if (_midnightJobFired && (h !== 0 || m > 10)) {
                _midnightJobFired = false;
            }
            return;
        }

        // Already fired this midnight window —” skip
        if (_midnightJobFired) return;
        _midnightJobFired = true;

        console.log('[GSpot] Midnight detected —” triggering auto-end for Unlimited sessions—¦');

        fetch('ajax/auto_end_unlimited.php', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success && data.ended === undefined) return;

                var count = data.ended || 0;
                if (count === 0) {
                    console.log('[GSpot] Midnight auto-end: No active Unlimited sessions found.');
                    return;
                }

                // â”€â”€ Build a rich notification toast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                var sessionLines = (data.sessions || []).map(function(s) {
                    var h = Math.floor(s.duration_minutes / 60);
                    var m = s.duration_minutes % 60;
                    var dur = (h ? h + 'h ' : '') + (m ? m + 'm' : (h ? '' : '0m'));
                    return '—¢ ' + s.customer + ' (' + s.unit + ') —” ' + dur + ' —” ₱' + parseFloat(s.total_cost).toFixed(2);
                }).join('\n');

                var toastMsg = count + ' Unlimited session' + (count > 1 ? 's' : '') +
                    ' automatically ended at 12:00 AM (shop closing).\n' + sessionLines;

                console.log('[GSpot] Midnight auto-end complete:', data);

                // Show toast if available, otherwise use a non-blocking banner
                if (window.showToast) {
                    window.showToast(
                        count + ' Unlimited session' + (count > 1 ? 's' : '') +
                        ' auto-ended at 12:00 AM —” ₱400.00 flat rate applied.',
                        'success'
                    );
                } else {
                    // Fallback: inline status banner at top of admin panel
                    var banner = document.createElement('div');
                    banner.style.cssText =
                        'position:fixed;top:20px;left:50%;transform:translateX(-50%);' +
                        'z-index:999999;background:linear-gradient(135deg,#0d2e22,#102e1a);' +
                        'border:1px solid rgba(32,200,161,.5);border-radius:14px;' +
                        'padding:16px 24px;max-width:480px;width:92%;' +
                        'box-shadow:0 8px 32px rgba(0,0,0,.6);color:#eee;font-size:13px;' +
                        'animation:gspotSirenFadeIn .3s ease;';
                    banner.innerHTML =
                        '<div style="display:flex;align-items:center;gap:12px;">' +
                        '<div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;' +
                        'background:rgba(32,200,161,.15);border:1px solid rgba(32,200,161,.4);' +
                        'display:flex;align-items:center;justify-content:center;font-size:16px;">' +
                        '<i class="fas fa-moon" style="color:#20c8a1;"></i></div>' +
                        '<div>' +
                        '<div style="font-weight:700;color:#20c8a1;margin-bottom:3px;">' +
                        'Shop Closing —” Unlimited Sessions Ended</div>' +
                        '<div style="color:#aaa;font-size:12px;">' +
                        count + ' session' + (count > 1 ? 's' : '') +
                        ' ended at 12:00 AM · ₱400.00 flat rate applied each</div>' +
                        '</div>' +
                        '<button onclick="this.parentElement.parentElement.remove()" ' +
                        'style="background:none;border:none;color:#555;font-size:16px;' +
                        'cursor:pointer;margin-left:auto;flex-shrink:0;">Ã—</button>' +
                        '</div>';
                    document.body.appendChild(banner);
                    setTimeout(function() {
                        if (banner.parentNode) banner.parentNode.removeChild(banner);
                    }, 12000);
                }

                // Refresh the dashboard view so ended sessions disappear from Live Sessions
                setTimeout(function() {
                    location.reload();
                }, 2000);
            })
            .catch(function(err) {
                console.warn('[GSpot] Midnight auto-end fetch error:', err);
                // Reset flag so it retries on the next poll if something went wrong
                _midnightJobFired = false;
            });
    }

    // First check at 5 s after page load (catches admin pages open past midnight)
    setTimeout(function() {
        _checkMidnight();
        setInterval(_checkMidnight, POLL_MS);
    }, 5000);
})();
</script>

<div class="bulk-bar" id="bulkBar">
    <div class="bulk-count-badge"><span id="bulkCount">0</span> Selected</div>
    <div class="bulk-actions">
        <button class="bulk-btn bulk-btn-restore" onclick="BulkManager.execute('restore')">
            <i class="fas fa-rotate-left"></i> Restore Selected
        </button>
        <button class="bulk-btn bulk-btn-delete" onclick="BulkManager.execute('delete')">
            <i class="fas fa-trash-alt"></i> Delete Permanently
        </button>
    </div>
</div>

</body>
</html>
