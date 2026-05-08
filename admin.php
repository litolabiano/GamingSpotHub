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
$consoleTypes         = getConsoleTypes(true, 'console');           // active console types only
$controllerTypes      = getControllerTypes(true);                   // active controller types only
$archivedConsoleTypes = array_filter(getConsoleTypes(false), fn($ct) => $ct['is_archived'] == 1 && $ct['category'] === 'console');
$archivedCtrlTypes    = array_filter(getConsoleTypes(false), fn($ct) => $ct['is_archived'] == 1 && $ct['category'] === 'controller');
$message = '';
$messageType = '';

// ------------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CSRF guard - all admin POST actions require a valid token ──────────
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
            $result = startSession($user_id, $console_id, $rental_mode, $user['user_id'], $planned_minutes);
      if ($result['success']) {

        // ── Persist controller rental fee to additional_requests (always, ──────
        // ── regardless of whether upfront was collected). endSession()    ──────
        // ── and the End Session modal both read from this table.          ──────
        if (!empty($_POST['controller_rental']) && $_POST['controller_rental'] == '1') {
            $ctrl_fee = (float)($_POST['controller_rental_fee_amt'] ?? getSetting('controller_rental_fee') ?? 20);
            if ($ctrl_fee > 0) {
                $arStmt = $conn->prepare(
                    "INSERT INTO additional_requests
                        (session_id, request_type, description, extra_cost, status)
                     VALUES (?, 'controller_rental', 'Controller rental fee', ?, 'approved')"
                );
                $arStmt->bind_param('id', $result['session_id'], $ctrl_fee);
                $arStmt->execute(); // execute once only

                // Mark the specific rented controller as in_use (from the dropdown selection)
                $rented_ctrl_id = (int)($_POST['rented_controller_id'] ?? 0);
                if ($rented_ctrl_id > 0) {
                    $ctrlUpd = $conn->prepare(
                        "UPDATE controllers SET status = 'in_use' WHERE controller_id = ? AND status = 'available'"
                    );
                    $ctrlUpd->bind_param('i', $rented_ctrl_id);
                    $ctrlUpd->execute();
                }
            }
        }

    if ($rental_mode === 'unlimited') {
        $unlimited_payment = $_POST['unlimited_payment_method'] ?? 'cash';
        $upfront_cost      = $unlim_rate;
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
        // Add controller rental fee to upfront total if checked
        if (!empty($_POST['controller_rental']) && $_POST['controller_rental'] == '1') {
            $ctrl_fee     = (float)($_POST['controller_rental_fee_amt'] ?? getSetting('controller_rental_fee') ?? 20);
            $upfront_cost += $ctrl_fee;
        }
        $tendered     = isset($_POST['start_tendered']) ? (float)$_POST['start_tendered'] : null;
        $shortfall    = ($tendered !== null && $tendered < $upfront_cost) ? $upfront_cost - $tendered : null;

        // Amount actually collected - if customer paid less, record only what they gave
        $actualCollected = ($tendered !== null) ? min((float)$tendered, $upfront_cost) : $upfront_cost;

        recordTransaction(
            $result['session_id'], $user_id, $actualCollected, $start_payment_method, $user['user_id'],
            $tendered,
            $shortfall,
            $shortfall ? 'Short payment at session start - short by ₱' . number_format($shortfall, 2) : null
        );
        $collected = ($tendered !== null) ? min($tendered, $upfront_cost) : $upfront_cost;
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
    if (!$messageType) $messageType = 'success';
}

  else {
                $message = 'Could not start session: ' . $result['message'];
                $messageType = 'error';
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

                // Fetch user_id
                $stmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ?");
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
        }
    }
    elseif ($action === 'add_console') {
        $name = trim($_POST['console_name'] ?? '');
        $type = $_POST['console_type'] ?? '';
        $unit_number = trim($_POST['unit_number'] ?? '');
        $rate = (float)($_POST['hourly_rate'] ?? 0);
        $ctrl_count = (int)($_POST['controller_count'] ?? 2);
        $compat_ctrl = $_POST['compatible_controller_type'] ?? null;
        
        if ($name && $type && $unit_number && $rate >= 0) {
            // ── DUPLICATE CHECK: Ensure Unit Number is unique ────────────────
            $checkStmt = $conn->prepare("SELECT console_id FROM consoles WHERE unit_number = ?");
            $checkStmt->bind_param("s", $unit_number);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = 'Failed to add console. Unit number "' . htmlspecialchars($unit_number) . '" is already in use.';
                $messageType = 'error';
            } else {
                if (addConsole($name, $type, $unit_number, $rate, $ctrl_count, $compat_ctrl)) {
                    $message = 'Console added successfully.';
                    $messageType = 'success';
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
        $type       = $_POST['console_type'] ?? '';
        $unit       = trim($_POST['unit_number'] ?? '');
        $rate       = (float)($_POST['hourly_rate'] ?? 0);
        $ctrl_count = (int)($_POST['controller_count'] ?? 2);
    
        if ($console_id && $name && $type && $unit && $rate >= 0) {
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
                $stmt = $conn->prepare(
                    "UPDATE consoles SET console_name = ?, console_type = ?, unit_number = ?, hourly_rate = ?, controller_count = ?
                      WHERE console_id = ?"
                );
                $stmt->bind_param('sssdi i', $name, $type, $unit, $rate, $ctrl_count, $console_id);
                if ($stmt->execute()) {
                    $message     = 'Console updated successfully.';
                    $messageType = 'success';
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
            } else {
                $message = 'Cannot delete console. It likely has existing sessions/reservations associated with it. Keep it archived instead.';
                $messageType = 'error';
            }
        }
    }

    // ADD CONSOLE TYPE
    elseif ($action === 'add_console_type') {
        $typeName      = trim($_POST['type_name'] ?? '');
        $category      = in_array($_POST['category'] ?? '', ['console', 'controller']) ? $_POST['category'] : 'console';
        $consoleTypeId = ($category === 'controller' && !empty($_POST['console_type_id']))
                         ? (int)$_POST['console_type_id'] : null;
        if ($typeName) {
            if (addConsoleType($typeName, $category, $consoleTypeId)) {
                $parentNote = '';
                if ($consoleTypeId) {
                    $pRes = $conn->prepare("SELECT type_name FROM console_types WHERE type_id = ?");
                    $pRes->bind_param('i', $consoleTypeId);
                    $pRes->execute();
                    $pRow = $pRes->get_result()->fetch_assoc();
                    if ($pRow) $parentNote = ' (for ' . htmlspecialchars($pRow['type_name']) . ')';
                }
                $message = ($category === 'controller' ? 'Controller' : 'Console') . ' type "' . htmlspecialchars($typeName) . '"' . $parentNote . ' added successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to add type. It might already exist.';
                $messageType = 'error';
            }
        }
    }

    // ARCHIVE CONSOLE TYPE
    elseif ($action === 'archive_console_type') {
        $typeId = (int)($_POST['type_id'] ?? 0);
        if ($typeId) {
            if (archiveConsoleType($typeId)) {
                $message = 'Console type archived. Associated consoles have been moved to the Archive section.';
                $messageType = 'success';
            } else {
                $message = 'Failed to archive console type.';
                $messageType = 'error';
            }
        }
    }

    // RESTORE CONSOLE TYPE
    elseif ($action === 'restore_console_type') {
        $typeId = (int)($_POST['type_id'] ?? 0);
        if ($typeId) {
            if (restoreConsoleType($typeId)) {
                $message = 'Console type restored successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to restore console type.';
                $messageType = 'error';
            }
        }
    }

    // PERMANENTLY DELETE CONSOLE TYPE
    elseif ($action === 'delete_console_type') {
        $typeId = (int)($_POST['type_id'] ?? 0);
        if ($typeId) {
            if (deleteConsoleType($typeId)) {
                $message = 'Console type permanently removed from the system.';
                $messageType = 'success';
            } else {
                $message = 'Failed to permanently delete console type.';
                $messageType = 'error';
            }
        }
    }

    // ── CONTROLLER ACTIONS ────────────────────────────────────────────────────
    elseif ($action === 'add_controller') {
        $ctrl_name  = trim($_POST['controller_name'] ?? '');
        $ctrl_type  = $_POST['controller_type'] ?? '';
        $ctrl_unit  = trim($_POST['ctrl_unit_number'] ?? '');
        $ctrl_notes = trim($_POST['controller_notes'] ?? '');
        // Validate against DB-driven controller types (category = 'controller')
        $validCtrlTypes = array_column(getControllerTypes(true), 'type_name');
        if ($ctrl_name && in_array($ctrl_type, $validCtrlTypes) && $ctrl_unit) {
            // Check for duplicate unit number first
            $dupCheck = $conn->prepare("SELECT controller_id FROM controllers WHERE unit_number = ?");
            $dupCheck->bind_param('s', $ctrl_unit);
            $dupCheck->execute();
            if ($dupCheck->get_result()->num_rows > 0) {
                $message = 'Unit number "' . htmlspecialchars($ctrl_unit) . '" already exists. Please use a different unit number (e.g. CTRL-02, CTRL-03).';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO controllers (controller_name, controller_type, unit_number, notes) VALUES (?,?,?,?)"
                );
                $stmt->bind_param('ssss', $ctrl_name, $ctrl_type, $ctrl_unit, $ctrl_notes);
                if ($stmt->execute()) {
                    $message = 'Controller added successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add controller: ' . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
            $dupCheck->close();
        } else {
            $message = 'Invalid input for new controller.';
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
            }
        }
    }

    // SAVE SETTINGS — owner only
    elseif ($action === 'save_settings') {
        if ($user['role'] !== 'owner') {
            $message = 'Access denied. Only the owner can change settings.';
            $messageType = 'error';
        } else {
        $keys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                 'business_hours_open','business_hours_close','shop_phone','contact_email',
                 'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge',
                 'brevo_api_key','sender_email'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                updateSetting($key, trim($_POST[$key]));
            }
        }

        // ── Sync consoles.hourly_rate from system_settings ──────────────────
        // This ensures the "Console" dropdown in Start Session always shows the
        // live rate from system settings, not a stale per-row value.
        $rateMap = [
            'PS5'          => (float)($_POST['ps5_hourly_rate']  ?? getSetting('ps5_hourly_rate')  ?? 80),
            'PS4'          => (float)($_POST['ps5_hourly_rate']  ?? getSetting('ps5_hourly_rate')  ?? 80), // PS4 shares PS5 rate
            'Xbox Series X'=> (float)($_POST['xbox_hourly_rate'] ?? getSetting('xbox_hourly_rate') ?? 80),
        ];
        foreach ($rateMap as $type => $rate) {
            $stmt = $conn->prepare("UPDATE consoles SET hourly_rate = ? WHERE console_type = ?");
            $stmt->bind_param('ds', $rate, $type);
            $stmt->execute();
        }

        $message = 'Settings saved and console rates updated.';
        $messageType = 'success';
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
        }
    }

    // CANCEL RESERVATION (admin-initiated → cancelled_by = 'admin')
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
                        cancelled_by         = 'admin',
                        cancel_reason_type   = ?,
                        cancel_reason_detail = ?
                  WHERE reservation_id = ?"
            );
            $stmt->bind_param('ssi', $reasonType, $reasonDetail, $res_id);
            $stmt->execute();

            // ── Log to reservation_cancellations audit table ──────────────
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
                $logStmt->execute([
                    $res_id,
                    $logRow['user_id'],
                    $reasonType,
                    $reasonDetail,
                ]);
            }

            $message     = 'Reservation #' . $res_id . ' cancelled.';
            $messageType = 'success';
        }
    }


    // NO-SHOW
    elseif ($action === 'noshow_reservation') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        if ($res_id) {
            updateReservationStatus($res_id, 'no_show');
            $message = 'Marked as no-show.';
            $messageType = 'warning';
        }
    }

    // CONVERT RESERVATION → SESSION
    elseif ($action === 'convert_reservation') {
        $res_id     = (int)($_POST['reservation_id'] ?? 0);
        $console_id = (int)($_POST['console_id'] ?? 0);
        if ($res_id && $console_id) {
            $result = convertReservationToSession($res_id, $console_id, $user['user_id']);
            if ($result['success']) {
                $message = 'Reservation converted to active session!';
                $messageType = 'success';
            } else {
                $message = 'Conversion failed: ' . $result['message'];
                $messageType = 'error';
            }
        } else {
            $message = 'Please select a console unit to assign.';
            $messageType = 'error';
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
        if ($uid && $ctype && $rmode && $rdate && $rtime) {
            $result = createReservation($uid, $ctype, $rmode, $pmins, $rdate, $rtime,
                                        $notes ?: null, $dp_amount, $dp_method);
            $message     = $result['success'] ? 'Reservation added.' : 'Error: ' . $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
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

    // PROCESS REFUND for cancelled reservations is handled at lines 266–306 above.

    // NOTE: Session extension is handled exclusively through ajax/extend_session.php
    // which calls extendSession() - applying bonus minutes and recording a transaction.
    // The old direct form-POST handler has been removed (Bug #4 fix) to prevent
    // bypassing the billing engine with a raw planned_minutes UPDATE.
    elseif ($action === 'extend_session') {
        $message     = 'Session extensions must be processed through the Extend modal.';
        $messageType = 'error';
    }

    // ── TOURNAMENT ACTIONS ──────────────────────────────────────────────────

    // CREATE TOURNAMENT
    elseif ($action === 'create_tournament') {
        $name         = trim($_POST['tournament_name'] ?? '');
        $game         = trim($_POST['game_name']       ?? '');
        $console_type = $_POST['console_type']         ?? '';
        $start_date   = $_POST['start_date']           ?? '';
        $end_date     = $_POST['end_date']             ?? '';
        $entry_fee    = (float)($_POST['entry_fee']         ?? 0);
        $prize_pool   = (float)($_POST['prize_pool']        ?? 0);
        $max_part     = (int)  ($_POST['max_participants']  ?? 16);
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
                $message = 'Tournament "' . htmlspecialchars($name) . '" created.';
                $messageType = 'success';
            } else {
                $message = 'Failed to create tournament: ' . $conn->error;
                $messageType = 'error';
            }
        }
    }

    // UPDATE TOURNAMENT STATUS
    elseif ($action === 'update_tournament_status') {
        $tid        = (int)($_POST['tournament_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        $allowed    = ['upcoming', 'scheduled', 'ongoing', 'completed', 'cancelled'];
        if ($tid && in_array($new_status, $allowed)) {
            $stmt = $conn->prepare("UPDATE tournaments SET status = ? WHERE tournament_id = ?");
            $stmt->bind_param('si', $new_status, $tid);
            $stmt->execute();
            $message = 'Tournament status updated to ' . ucfirst($new_status) . '.';
            $messageType = 'success';
        } else {
            $message = 'Invalid tournament or status.';
            $messageType = 'error';
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
            // ── Walk-in: no user account required ────────────────────────────
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
                } else {
                    $message     = 'Could not register walk-in: ' . $conn->error;
                    $messageType = 'error';
                }
            } else {
                $message     = 'Please provide a tournament and the walk-in name.';
                $messageType = 'error';
            }
        } else {
            // ── Registered customer ───────────────────────────────────────────
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

    // UPDATE PARTICIPANT PAYMENT STATUS
    elseif ($action === 'update_participant_payment') {
        $pid        = (int)($_POST['participant_id']  ?? 0);
        $pay_status = in_array($_POST['payment_status'] ?? '', ['pending','paid'])
                      ? $_POST['payment_status'] : 'pending';
        if ($pid) {
            $stmt = $conn->prepare("UPDATE tournament_participants SET payment_status = ? WHERE participant_id = ?");
            $stmt->bind_param('si', $pay_status, $pid);
            $stmt->execute();
            $message = 'Payment status updated.';
            $messageType = 'success';
        }

    // REMOVE TOURNAMENT PARTICIPANT
    elseif ($action === 'remove_participant') {
        $pid = (int)($_POST['participant_id'] ?? 0);
        if ($pid) {
            $stmt = $conn->prepare("DELETE FROM tournament_participants WHERE participant_id = ?");
            $stmt->bind_param('i', $pid);
            $stmt->execute();
            $message = 'Participant removed.';
            $messageType = 'success';
        }
    }

}

}


// ─── DATA FETCHING ──────────────────────────────────────────────────────────

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

// ── Controller Rental status per active console ──────────────────────────────
// Maps console_id => [ qty => int, total_cost => float, session_id => int ]
$ctrlRentalByConsole = [];
$crQ = $conn->query(
    "SELECT gs.console_id,
            gs.session_id,
            COUNT(ar.request_id)     AS qty,
            SUM(ar.extra_cost)       AS total_cost,
            MIN(ar.created_at)       AS rented_since
       FROM gaming_sessions gs
       JOIN additional_requests ar
         ON ar.session_id = gs.session_id
        AND ar.request_type = 'controller_rental'
        AND ar.status = 'approved'
      WHERE gs.status = 'active'
      GROUP BY gs.console_id, gs.session_id"
);
if ($crQ) {
    while ($row = $crQ->fetch_assoc()) {
        $ctrlRentalByConsole[(int)$row['console_id']] = [
            'qty'         => (int)$row['qty'],
            'total_cost'  => (float)$row['total_cost'],
            'session_id'  => (int)$row['session_id'],
            'rented_since'=> $row['rented_since'],
        ];
    }
}

// ── Controllers ──────────────────────────────────────────────────────────────
$allControllers      = [];
$archivedControllers = [];
$_res = $conn->query("SELECT * FROM controllers WHERE status != 'archived' ORDER BY unit_number");
if ($_res) $allControllers = $_res->fetch_all(MYSQLI_ASSOC);
$_res = $conn->query("SELECT * FROM controllers WHERE status = 'archived' ORDER BY unit_number");
if ($_res) $archivedControllers = $_res->fetch_all(MYSQLI_ASSOC);

// Available controllers for the Start Session rental dropdown (injected to JS)
$_avRes = $conn->query("SELECT controller_id, controller_name, controller_type, unit_number FROM controllers WHERE status = 'available' ORDER BY unit_number");
$availableControllers = $_avRes ? $_avRes->fetch_all(MYSQLI_ASSOC) : [];
unset($_res, $_avRes);

// Sessions: active/live first (sorted by urgency - closest booked end time), then completed newest-first
$stmt = $conn->prepare(
    "SELECT gs.*, u.full_name AS customer_name, c.console_name, c.unit_number, c.console_type,
            gs.source_reservation_id,
            COALESCE(r.downpayment_amount, 0) AS reservation_downpayment,
            COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount > 0), 0) AS upfront_paid,
            COALESCE((SELECT SUM(ABS(t.amount)) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount < 0), 0) AS refunded_amount,
            COALESCE((SELECT SUM(ar.extra_cost) FROM additional_requests ar WHERE ar.session_id = gs.session_id AND ar.status = 'approved'), 0) AS approved_extras
     FROM gaming_sessions gs
     JOIN users u ON gs.user_id = u.user_id
     JOIN consoles c ON gs.console_id = c.console_id
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
    "SELECT rs.*, r.console_type, u.full_name AS customer_name, c.unit_number
     FROM reservation_reschedules rs
     JOIN reservations r ON rs.reservation_id = r.reservation_id
     JOIN users u ON rs.user_id = u.user_id
     LEFT JOIN consoles c ON r.console_id = c.console_id
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
             WHEN t.amount < 0 THEN 'refund'
             ELSE COALESCE(gs.rental_mode, 'other')
           END AS rental_mode,
            r.paymongo_payment_id,
            r.paymongo_source_id
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
     LEFT JOIN consoles c ON gs.console_id = c.console_id
     LEFT JOIN reservations r
           ON t.payment_note LIKE '%reservation #%'
          AND r.reservation_id = CAST(
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
                continue; // Fully paid — not a pending balance
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

// ── Cancellation Analytics (for Reports tab) ──────────────────────────────────

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
    "SELECT r.console_type, COUNT(*) AS cnt
       FROM reservation_cancellations rc
       JOIN reservations r ON rc.reservation_id = r.reservation_id
      GROUP BY r.console_type
      ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

// Cancellations over last 30 days (line chart)
$nowTs = time();
$cancelTrend = [];
$cancelTrendLabels = [];
for ($i = 29; $i >= 0; $i--) {
    $targetOpDay = getOperatingDay(date('Y-m-d H:i:s', strtotime("-{$i} days", $nowTs)));
    [$sBound, $eBound] = getOperatingDayBounds($targetOpDay);
    
    $cancelTrendLabels[] = date('M d', strtotime($targetOpDay));
    $cs = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM reservation_cancellations WHERE (cancelled_at BETWEEN ? AND ?)"
    );
    $cs->bind_param('ss', $sBound, $eBound);
    $cs->execute();
    $cancelTrend[] = (int)$cs->get_result()->fetch_assoc()['cnt'];
}

// Cancelled-by breakdown (for doughnut)
$cancelByWho = $conn->query(
    "SELECT cancelled_by, COUNT(*) AS cnt FROM reservation_cancellations GROUP BY cancelled_by"
)->fetch_all(MYSQLI_ASSOC);

// Settings
$settingsKeys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                 'business_hours_open','business_hours_close','shop_name','shop_address','shop_phone',
                 'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge'];
$settings = [];
foreach ($settingsKeys as $k) {
    $settings[$k] = getSetting($k);
}

// Chart data: revenue last 7 days
$revChartData = [];
$revLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $targetOpDay = getOperatingDay(date('Y-m-d H:i:s', strtotime("-{$i} days", $nowTs)));
    [$sBound, $eBound] = getOperatingDayBounds($targetOpDay);
    
    $revLabels[] = date('M d', strtotime($targetOpDay));
    $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS rev FROM transactions WHERE (transaction_date BETWEEN ? AND ?) AND payment_status='completed'");
    $s->bind_param("ss", $sBound, $eBound);
    $s->execute();
    $revChartData[] = (float)$s->get_result()->fetch_assoc()['rev'];
}

// Chart data: console type usage
$typeUsage = $conn->query(
    "SELECT c.console_type, COUNT(gs.session_id) AS cnt
     FROM consoles c
     LEFT JOIN gaming_sessions gs ON c.console_id = gs.console_id AND gs.status = 'completed'
     GROUP BY c.console_type"
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
    <style>
        /* ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ 
           ADMIN DESIGN SYSTEM - CSS Custom Properties
        ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═  */
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

        /* ── Flash messages ── */
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

        /* ── Status dots ── */
        .status-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }
        .status-dot.available   { background:var(--clr-mint); box-shadow:0 0 6px rgba(32,200,161,.5); }
        .status-dot.in_use      { background:var(--clr-blue); }
        .status-dot.maintenance { background:var(--clr-coral); }

        /* ── Console type badges ── */
        .console-type-badge { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; letter-spacing:.3px; }
        .console-type-badge.ps5  { background:rgba(95,133,218,.18); color:#8aa4e8; border:1px solid rgba(95,133,218,.3); }
        .console-type-badge.ps4  { background:rgba(241,168,60,.15);  color:#f1a83c; border:1px solid rgba(241,168,60,.3); }
        .console-type-badge.xbox { background:rgba(32,200,161,.18);  color:#20c8a1; border:1px solid rgba(32,200,161,.3); }

        /* ── Session timer ── */
        .session-timer { font-family: monospace; font-size: 13px; color: var(--clr-cream); font-weight: 700; }
        .session-timer.stale { color: var(--clr-coral); font-size:11px; font-weight:500; }

        /* ── Page header pattern ── */
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

        /* ── Form layout ── */
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

        /* ── Stat cards ── */
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

        /* ── Console cards ── */
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

        /* ── Data table ── */
        .data-table thead tr { background:rgba(10,33,81,.6); }
        .data-table tbody tr { transition:background .15s; }
        .data-table tbody tr:hover { background:rgba(95,133,218,.06); }

        /* ── Badge ── */
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

        /* ── Empty state ── */
        .empty-state { text-align:center; padding:48px 20px; color:#444; }
        .empty-state i { font-size:40px; margin-bottom:14px; display:block; opacity:.5; }
        .empty-state p { margin:4px 0; font-size:14px; }

        /* ── Responsive ── */
        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
        @media (min-width:769px) {
            .menu-toggle { display:none !important; }
            .sidebar-close-btn { display:none !important; visibility:hidden !important; }
        }

        /* ── Sidebar hamburger ── */
        .sidebar-hamburger .sidebar-ham-icon {
            font-size: 14px; color: rgba(255,255,255,0.55); transition: color 0.2s ease; width: auto;
        }
        .sidebar-hamburger:hover .sidebar-ham-icon { color: var(--clr-mint); }

        /* ── Admin user dropdown ── */
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
        .admin-dropdown-danger:hover { background:rgba(251,86,107,.1) !important; }
        .user-avatar-lg { width:42px; height:42px; font-size:16px; flex-shrink:0; }
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


<!-- ── Sidebar ─────────────────────────────────────────────────────────────── -->
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
    <?php if ($user['role'] !== 'shopkeeper'): ?>
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
    <?php endif; ?>

    <?php if ($user['role'] === 'owner'): ?>
    <div class="nav-item" data-tooltip="Blocked Dates" onclick="showPage('blocked_dates', this)">
        <i class="fas fa-calendar-times"></i><span>Blocked Dates</span>
    </div>
    <div class="nav-item" onclick="showPage('settings', this)">
        <i class="fas fa-cog"></i><span>Settings</span>
    </div>
    <?php endif; ?>

</div>

<!-- ── Top Bar ──────────────────────────────────────────────────────────────── -->
<div class="topbar">
    <div class="topbar-left">
        <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
        <h3 id="pageTitle">Dashboard</h3>
    </div>
    <div class="topbar-right">

        <!-- ── Bell Notification Icon ──────────────────────────────────── -->
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
                <a href="auth/logout.php" class="admin-dropdown-item admin-dropdown-danger" id="logoutLink" onclick="window.location.href='auth/logout.php';">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Main Content ──────────────────────────────────────────────────────────── -->
<div class="main-content">

<?php include __DIR__ . '/admin_sections/dashboard.php'; ?>
<?php if ($user['role'] !== 'shopkeeper'): include __DIR__ . '/admin_sections/consoles.php'; endif; ?>
<?php include __DIR__ . '/admin_sections/sessions.php'; ?>
<?php include __DIR__ . '/admin_sections/reservations.php'; ?>
<?php include __DIR__ . '/admin_sections/transactions.php'; ?>
<?php include __DIR__ . '/admin_sections/reports.php'; ?>
<?php if ($user['role'] !== 'shopkeeper'): include __DIR__ . '/admin_sections/tournaments.php'; endif; ?>

<?php if ($user['role'] === 'owner'): include __DIR__ . '/admin_sections/blocked_dates.php'; endif; ?>
<?php if ($user['role'] === 'owner'): include __DIR__ . '/admin_sections/settings.php'; endif; ?>


</div><!-- /.main-content -->
<?php include __DIR__ . '/admin_sections/modals.php'; ?>
<!-- ── JavaScript ── -->
<script src="assets/libs/aos/aos.js"></script>
<script>
// ── Admin user dropdown (Moved to top for reliability) ──
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

// ── Navigation ──
function showPage(page, el) {
    // ── Role-Based Access Check ──
    const userRole = '<?= $user['role'] ?>';
    const restricted = ['consoles', 'tournaments'];
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
        blocked_dates: 'Blocked Dates'
    };

    document.getElementById('pageTitle').textContent = titles[page] || page;

    // Persist active page in URL hash so reloads stay on the same section
    history.replaceState(null, '', '#' + page);

    // Render charts lazily on first visit to reports
    if (page === 'reports' && !window.chartsRendered) {
        renderCharts();
        window.chartsRendered = true;
    }
}

// track which section is currently visible (for live-refresh)
var _currentSection = 'dashboard';

// ── Live Section Refresh ─────────────────────────────────────────────────────
// Every 12 seconds, re-fetches the active section's rendered HTML from the server
// and updates the DOM - keeps reservations, sessions, dashboard etc. live without reload.
(function () {
    var REFRESH_MS = 12000;
    // Sections we can safely auto-refresh (exclude settings to avoid mid-edit disruption)
    var refreshable = ['dashboard','sessions','reservations','consoles','transactions','tournaments','blocked_dates'];

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
        // Check any visible modal - don't refresh while admin is interacting
        var modals = document.querySelectorAll('.modal, [id$="Modal"], [id*="modal"]');
        for (var i = 0; i < modals.length; i++) {
            var s = modals[i].style;
            if (s.display === 'flex' || s.display === 'block') return true;
        }
        return false;
    }

    function isInputFocused() {
        var tag = document.activeElement && document.activeElement.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
    }

    function refreshSection() {
        var section = _currentSection;
        if (!refreshable.includes(section)) return;
        if (isModalOpen() || isInputFocused()) return; // don't interrupt user

        // Dim dot while fetching
        dot.style.background = '#888';

        fetch('ajax/live_section.php?section=' + encodeURIComponent(section), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                dot.style.background = '#20c8a1';
                if (!data.html) return;

                var container = document.getElementById(section);
                if (!container) return;

                // Don't replace if user just opened a modal or focused an input
                if (isModalOpen() || isInputFocused()) return;

                // Replace inner content
                container.innerHTML = data.html;

                // Subtle flash to signal update
                container.classList.add('live-refreshing');
                setTimeout(function() { container.classList.remove('live-refreshing'); }, 450);
            })
            .catch(function() {
                dot.style.background = '#fb566b'; // red on error
                setTimeout(function() { dot.style.background = '#20c8a1'; }, 3000);
            });
    }

    // Start after 5s, then every 12s
    setTimeout(function() {
        refreshSection();
        setInterval(refreshSection, REFRESH_MS);
    }, 5000);

    // Also refresh immediately when switching to a refreshable section
    // ── Start Session Modal ──
    var _origShowPage = window.showPage;
    window.showPage = function(page, el) {
        _currentSection = page;
        _origShowPage(page, el);
        // Refresh new section data immediately on tab switch (after 300ms for animation)
        if (refreshable.includes(page)) {
            setTimeout(refreshSection, 300);
        }
    };
})();


(function () {
    const hash = window.location.hash.replace('#', '');
    const userRole = '<?= $user['role'] ?>';
    const validPages = ['dashboard','consoles','sessions','reservations','transactions','financial','reports','settings','tournaments'];
    
    // Filter valid pages based on role
    const allowedPages = validPages.filter(p => {
        if (userRole === 'shopkeeper' && ['consoles', 'tournaments', 'settings'].includes(p)) return false;
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

// ── Start Session Modal ──
function onRentalModeChange() {
    const mode            = document.getElementById('rentalModeSelect').value;
    const group           = document.getElementById('durationPickerGroup');
    const preview         = document.getElementById('sessionPreview');
    const hourlyPayGroup  = document.getElementById('startPaymentGroup');
    const unlimPayGroup   = document.getElementById('unlimitedPaymentGroup');
    const openTimeNote    = document.getElementById('openTimeNote');
    const toggle          = document.getElementById('collectNowToggle');

    // Duration picker: only for hourly
    group.style.display   = (mode === 'hourly') ? 'block' : 'none';
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

    // Re-evaluate Start button for the new mode
    if (typeof _syncStartBtn === 'function') _syncStartBtn();
}

/* ── Controller Rental: Xbox-only ─────────────────────────────────────────────
Hides/shows the controller rental checkbox depending on the selected
console type. Only Xbox units support controller rentals.
*/
function onConsoleChange() {
    const sel    = document.getElementById('consoleSelect');
    const opt    = sel ? sel.options[sel.selectedIndex] : null;
    const type   = opt ? (opt.dataset.type || '') : '';
    const isXbox = type.toLowerCase().includes('xbox');
    const group  = document.getElementById('controllerRentalGroup');
    const toggle = document.getElementById('controllerRentalToggle');
    if (!group) return;
    if (isXbox) {
        group.style.display = 'block';
    } else {
        group.style.display = 'none';
        if (toggle && toggle.checked) {
            toggle.checked = false;
            if (typeof recalcSessionPreview === 'function') recalcSessionPreview();
        }
    }
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

/* ── Change calculator ──
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
            input.setAttribute('placeholder', '⚠ Enter amount tendered');
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
    if (!paid) { preview.style.display = 'none'; input.value = ''; return; }

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

        // ── Validation 1: hourly requires a duration ──────────────────────
        if (mode === 'hourly' && !document.getElementById('durationSelect').value) {
            e.preventDefault();
            showInlineToast('Please select a duration for the hourly session.', 'error');
            return;
        }

        // ── Validation 2: short payment guard ─────────────────────────────
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
    });

    // Wire up live re-validation to dismiss the error when user fixes the amount
    // ── Modals ──
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
 * _syncStartBtn — called live on every input, cost change, or mode change.
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

    if (isShort) {
        _showStartShortError(shortMsg);
    } else {
        _clearStartShortError();
    }
}


// Ã¢”â‚¬Ã¢”â‚¬ Modals Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬

function openModal(name) {
    document.getElementById(name + 'Modal').classList.add('active');
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

/* ── Billing helpers - all values driven from DB via getPricingRules() ──────── *
 * PRICING is injected by PHP so the JS always matches the backend.
 * _bracketCost / _timedCost are unchanged in shape - only their constants move.
 */
const PRICING = <?= json_encode(getPricingRules()) ?>;
// Available controllers for the rental dropdown — populated from DB on page load
const _availableControllers = <?= json_encode($availableControllers ?? []) ?>;

function _bracketCost(partialMin) {
    if (partialMin <= 0) return 0;
    const tiers = PRICING.pricing_tiers || [];
    for (let i = 0; i < tiers.length; i++) {
        if (partialMin >= tiers[i].min && partialMin <= tiers[i].max) {
            return tiers[i].charge;
        }
    }
    return PRICING.hourly_rate;
}
function _timedCost(totalMin) {
    if (totalMin <= 0) return 0;
    const bp       = PRICING.bonus_paid_minutes;         // e.g. 120
    const bf       = PRICING.bonus_free_minutes;         // e.g. 30
    const rate     = PRICING.hourly_rate;                // e.g. 80
    const cyclePay = bp / 60 * rate;                    // e.g. 160
    const cycleLen = bp + bf;                           // e.g. 150
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
        // Early or exact end — bill only actual elapsed time
        return duration <= 0 ? 0 : _timedCost(duration);
    }
    // Overtime — base (planned cost) + overtime brackets
    const base = planned <= 30 ? PRICING.session_min_charge : _timedCost(planned);
    return base + _timedCost(overtime);
}

let _endModalTimer = null;   // holds the live-update interval

// Stores refund-modal args when the admin triggers "Refund & End" from the early-end warning
let _pendingRefundArgs = null;

/* ── Session-end audio alert (Web Audio API - no file needed) ──────────────
Plays a short 3-beep chime when the admin confirms ending a session.
Uses the browser’s built-in synthesis - works offline, no CDN required.
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

function openEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate, sourceReservationId) {
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
                ex.extras || 0, ex.items || [], sourceReservationId);
        })
        .catch(function(){
            _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate, 0, [], sourceReservationId);
        });
}

function _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, reservationDownpayment, unlimitedRate, extras, extraItems, sourceReservationId) {
    extras                 = extras                 || 0;
    reservationDownpayment = reservationDownpayment || 0;
    sourceReservationId    = sourceReservationId    || 0;

    // ── Reservation-source notice ─────────────────────────────────────────
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

    // ── Early-end guard (hourly only) ────────────────────────────────────
    const earlyWarning    = document.getElementById('endEarlyWarning');
    const earlyRemStr     = document.getElementById('endEarlyRemainingStr');
    const earlyRefundBtn  = document.getElementById('endEarlyRefundBtn');
    const confirmBtn      = document.getElementById('endSessionConfirmBtn');

    // Reset guard UI first
    earlyWarning.style.display = 'none';
    confirmBtn.disabled        = false;
    confirmBtn.style.opacity   = '1';
    confirmBtn.style.cursor    = 'pointer';

    // ── Helper: drive the extras pill badge below the big cost number ─────
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

    if (mode === 'hourly' && plannedMinutes && startTs) {
        const nowSec    = Math.floor(Date.now() / 1000);
        const elapsed   = nowSec - startTs;           // total elapsed seconds
        const remaining = (plannedMinutes * 60) - elapsed; // seconds

        if (remaining > 0) {
            // ── Remaining time label ─────────────────────────────────────
            const remH = Math.floor(remaining / 3600);
            const remM = Math.floor((remaining % 3600) / 60);
            const remS = remaining % 60;
            earlyRemStr.textContent = (remH ? remH + 'h ' : '') +
                String(remM).padStart(2,'0') + ':' + String(remS).padStart(2,'0');

            // ── Consumed time & cost calculation ─────────────────────────
            // Elapsed label for the "Time Used (Xh YYm)" row in the modal
            const elapsedMin   = Math.floor(elapsed / 60);
            const elapsedHrs   = Math.floor(elapsedMin / 60);
            const elapsedMRem  = elapsedMin % 60;
            const elapsedLabel = (elapsedHrs ? elapsedHrs + 'h ' : '') +
                                 String(elapsedMRem).padStart(2, '0') + 'm';
            const elapsedEl = document.getElementById('endEarlyElapsedStr');
            if (elapsedEl) elapsedEl.textContent = '(' + elapsedLabel + ')';

            // ── Bracket-based billing (mirrors PHP computeTimedCost) ──────────
            // Uses _timedCost() which already matches backend logic exactly:
            //   1-4 min  = ₱0  (grace — avoids brutal tiny charges)
            //   5-19 min = ₱20, 20-34 = ₱40, 35-49 = ₱60, 50-59 = ₱80
            // Brackets automatically adjust when hourly_rate changes in Settings.
            const maxBillable  = Math.max(0, upfrontPaid - reservationDownpayment);
            const timeCost     = Math.min(maxBillable, _timedCost(elapsedMin));
            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)
            const nonRefundBase = reservationDownpayment;  // non-refundable portion
            const rawRefund    = upfrontPaid - consumedCost;
            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);
            const hasRefund    = refundAmt > 0;

            // Time Used row: show time-only cost (not extras)
            document.getElementById('endEarlyConsumedCost').textContent = '₱' + timeCost.toFixed(2);
            document.getElementById('endEarlyUpfrontStr').textContent  = '₱' + upfrontPaid.toFixed(2);
            document.getElementById('endEarlyRefundAmt').textContent   = '₱' + refundAmt.toFixed(2);
            document.getElementById('endEarlyRefundBtnAmt').textContent = '₱' + refundAmt.toFixed(2);

            // ── Show / hide Additional Fees row ──────────────────────────
            const extrasRow   = document.getElementById('endEarlyExtrasRow');
            const extrasAmt   = document.getElementById('endEarlyExtrasAmt');
            const extrasLabel = document.getElementById('endEarlyExtrasLabel');
            if (extrasRow) {
                if (extras > 0) {
                    extrasRow.style.display  = 'flex';
                    extrasAmt.textContent    = '+₱' + extras.toFixed(2);
                    // Build a compact label from extra items if available
                    const itemNames = (extraItems || []).map(function(i){ return i.description; }).join(', ');
                    extrasLabel.textContent  = itemNames ? '(' + itemNames + ')' : '';
                } else {
                    extrasRow.style.display = 'none';
                }
            }

            const noRefundNote   = document.getElementById('endEarlyNoRefundNote');
            const noRefundReason = document.getElementById('endEarlyNoRefundReason');
            if (noRefundNote) {
                noRefundNote.style.display = hasRefund ? 'none' : 'block';
                if (noRefundReason) {
                    if (upfrontPaid === 0) {
                        noRefundReason.textContent = 'Nothing was paid upfront — balance will be collected at check-out.';
                    } else if (consumedCost > upfrontPaid) {
                        // Customer owes MORE than they paid — flag this
                        const stillOwed = (consumedCost - upfrontPaid).toFixed(2);
                        noRefundReason.innerHTML =
                            '<span style="color:#f1a83c;font-weight:700;">' +
                            '\u20b1' + stillOwed + ' still owed</span> — consumed cost (\u20b1' +
                            consumedCost.toFixed(2) + ') exceeds upfront paid. ' +
                            'Collect via <strong>Pending Payments</strong> after session ends.';
                    } else if (nonRefundBase > 0 && (upfrontPaid - consumedCost) <= nonRefundBase) {
                        // Reservation downpayment absorbs any potential refund
                        noRefundReason.innerHTML =
                            'Reservation fee of <strong style="color:#fb566b;">\u20b1' + nonRefundBase.toFixed(2) +
                            '</strong> is non-refundable — no excess amount to return.';
                    } else {
                        noRefundReason.textContent = 'Additional fees consume the remaining balance — no refund needed.';
                    }
                }
            }

            // Colour the refund amount: green if 0, red if positive
            const refundEl = document.getElementById('endEarlyRefundAmt');
            refundEl.style.color = hasRefund ? '#fb566b' : '#888';

            // ── Show warning, disable confirm button ─────────────────────
            earlyWarning.style.display = 'block';

            if (hasRefund) {
                // Money to return — require Refund & End flow
                confirmBtn.disabled      = true;
                confirmBtn.style.opacity = '0.35';
                confirmBtn.style.cursor  = 'not-allowed';
                earlyRefundBtn.style.display = 'flex';
            } else {
                // No refund (reservation fee absorbed it, or consumed >= upfront)
                confirmBtn.disabled      = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor  = 'pointer';
                earlyRefundBtn.style.display = 'none';
            }

            // ── Wire up "Refund & End" button ────────────────────────────
            _pendingRefundArgs = { sessionId, customerName, unitNumber, upfrontPaid, refundAmt, consumedCost, elapsedLabel, nonRefundBase };

            earlyRefundBtn.onclick = function () {
                closeModal('endSession');

                // Open the refund modal
                openRefundModal(
                    _pendingRefundArgs.sessionId,
                    _pendingRefundArgs.customerName,
                    _pendingRefundArgs.unitNumber,
                    _pendingRefundArgs.upfrontPaid
                );

                // Set action_type for ajax/refund.php
                document.getElementById('refundActionField').value  = 'early_end';
                document.getElementById('refundEarlyEndFlag').value = '1';

                // Pre-fill refund amount - always locked for early-end flow
                const amtEl = document.getElementById('refundAmount');
                if (amtEl) {
                    amtEl.value           = _pendingRefundArgs.refundAmt.toFixed(2);
                    amtEl.readOnly        = true;   // always read-only; amount is pre-calculated
                    amtEl.style.opacity   = '1';
                    amtEl.style.background  = 'rgba(32,200,161,.06)';
                    amtEl.style.borderColor = 'rgba(32,200,161,.35)';
                    amtEl.style.cursor      = 'not-allowed';
                }

                // Show auto-calc breakdown hint
                const hintEl = document.getElementById('refundAutoCalcHint');
                if (hintEl) {
                    const paid     = _pendingRefundArgs.upfrontPaid;
                    const consumed = _pendingRefundArgs.consumedCost;
                    const refund   = _pendingRefundArgs.refundAmt;
                    hintEl.style.display = 'block';
                    if (refund > 0) {
                        hintEl.innerHTML =
                            '<i class="fas fa-calculator" style="margin-right:5px;color:#f1a83c;"></i>' +
                            '<strong>\u20b1' + paid.toFixed(2) + ' paid</strong> \u2212 ' +
                            '<strong>\u20b1' + consumed.toFixed(2) + ' consumed</strong> = ' +
                            '<strong style="color:#fb566b;">\u20b1' + refund.toFixed(2) + ' refund</strong>';
                        hintEl.style.color = '#f1e1aa';
                    } else if (consumed > paid) {
                        // Customer owes more - warn clearly
                        const owed = (consumed - paid).toFixed(2);
                        hintEl.innerHTML =
                            '<i class="fas fa-triangle-exclamation" style="margin-right:5px;color:#f1a83c;"></i>' +
                            'Consumed cost (\u20b1' + consumed.toFixed(2) + ') exceeds upfront paid (\u20b1' + paid.toFixed(2) + '). ' +
                            '<strong style="color:#f1a83c;">\u20b1' + owed + ' still owed</strong> - will appear in Pending Payments.';
                        hintEl.style.color = '#f1e1aa';
                    } else {
                        hintEl.innerHTML =
                            '<i class="fas fa-info-circle" style="margin-right:5px;color:#888;"></i>' +
                            'Consumed cost (\u20b1' + consumed.toFixed(2) + ') covers the full upfront amount \u2014 no refund.';
                        hintEl.style.color = '#888';
                    }
                }

                // Clear stale errors
                const errMsg = document.getElementById('refundErrorMsg');
                if (errMsg) errMsg.style.display = 'none';

                // Pre-fill reason
                const reasonEl = document.getElementById('refundReason');
                if (reasonEl) {
                    reasonEl.value =
                        'Early end \u2013 used ' + _pendingRefundArgs.elapsedLabel +
                        ' (\u20b1' + _pendingRefundArgs.consumedCost.toFixed(2) + ')' +
                        ', refunding unused time (\u20b1' + _pendingRefundArgs.refundAmt.toFixed(2) + ')';
                }

                // Show early-end notice inside refund modal
                const earlyNote = document.getElementById('refundEarlyEndNote');
                if (earlyNote) earlyNote.style.display = 'block';

                // Update confirm button label
                const lbl = document.getElementById('refundConfirmLabel');
                if (lbl) {
                    lbl.textContent = _pendingRefundArgs.refundAmt > 0
                        ? 'Refund \u20b1' + _pendingRefundArgs.refundAmt.toFixed(2) + ' & End Session'
                        : 'End Session (No Refund)';
                }
            };
        }
    }

    // ── End early-end guard ───────────────────────────────────────────────

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

    /* ── OPEN TIME: pay at end, show live ticking cost ── */
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

    /* ── HOURLY: charge actual elapsed time; overtime if beyond booked ── */
    } else if (mode === 'hourly' && plannedMinutes) {
        const elapsed  = Math.floor((Date.now() / 1000) - startTs);
        const minutes  = Math.floor(elapsed / 60);
        const overtime = minutes - plannedMinutes; // positive = over booked time

        // Actual cost = what we bill RIGHT NOW based on elapsed time
        // Early end  → _timedCost(elapsed minutes)   [matches PHP computeRentalFee early-end path]
        // Overtime   → planned base + overtime bracket charge
        let cost;
        if (overtime > 0) {
            const base = upfrontPaid > 0
                ? upfrontPaid
                : (plannedMinutes <= 30 ? PRICING.session_min_charge : _timedCost(plannedMinutes));
            cost = base + _timedCost(overtime) + extras;
        } else {
            // Early or exact end — charge only actual consumed time
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
                // Early end — collect actual time cost only
                setAmountDue(remaining, `Actual time used: ${minutes}m → ₱${cost.toFixed(2)} - Prepaid: ₱${upfrontPaid.toFixed(2)}`);
                noteEl.innerHTML = `<i class="fas fa-coins"></i> Early end — charged for <strong>${minutes} min</strong> used. Collect <strong>₱${remaining.toFixed(2)}</strong> now.`;
            }
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
        }

    /* ── UNLIMITED: flat rate was fully prepaid ── */
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

    } else {
        panel.style.display = 'none';
        payGroup.style.display = 'block';
        prepaidNote.style.display = 'none';
        confirmLbl.textContent = 'Confirm End & Record Payment';
    }

    openModal('endSession');
}

/* ── Pay Modal (collect outstanding balance, session continues) ──────── */
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

    /* ── Open Time: live-ticking balance ── */
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

    /* ── Hourly: snapshot at open time ── */
    } else if (mode === 'hourly' && plannedMinutes && startTs) {
        costPanel.style.display = 'block';
        const elapsed   = Math.floor((Date.now() / 1000) - startTs);
        const minutes   = Math.floor(elapsed / 60);
        const timeCost  = _hourlyCost(minutes, plannedMinutes);
        const totalCost = timeCost + extras;               // → extras included
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
            sublabel = 'Actual time used: ' + minutes + 'min → ₱' + timeCost.toFixed(2);
        }
        if (upfrontPaid > 0) sublabel += ' - Prepaid ₱' + upfrontPaid.toFixed(2);
        if (extras > 0) {
            const itemNames = (extraItems || []).map(function(i){ return i.description; }).join(', ');
            sublabel += ' - +₱' + extras.toFixed(2) + (itemNames ? ' (' + itemNames + ')' : ' extras');
        }
        setPayDue(due, sublabel);

    /* ── Unlimited: flat rate already paid; show extras if any ── */
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

/* ── Refund Modal ─────────────────────────────────────────────────────── */
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

/* ── Centralized Refund AJAX Submission ──────────────────────────────── */
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
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

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


/* ── Extend Modal ─────────────────────────────────────────────────────── */
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

// Ã¢”â‚¬Ã¢”â‚¬ Live Session Timers Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬
const STALE_THRESHOLD = 24 * 60 * 60; // 24 hours in seconds

function pad(n) { return String(n).padStart(2, '0'); }

// Tracks which timer elements already fired the overtime beep (once per element per load)
const overtimeBeeped = new WeakSet();
// Tracks which timer elements already fired the 15-second warning beep
const warningBeeped  = new WeakSet();

/* ── Shared AudioContext ────────────────────────────────────────────────────
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

/* Descending 3-tone alarm - fires when a session crosses into overtime.
   Square wave = more urgent/harsh than the sine-wave session-end chime. */
function playOvertimeBeep() {
    var ctx = _getAudioCtx();
    if (!ctx) return;
    ctx.resume().then(function() {
        [880, 660, 440].forEach(function(freq, i) {
            var delay = i * 0.22;
            var osc   = ctx.createOscillator();
            var gain  = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'square';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);
            gain.gain.setValueAtTime(0.25, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.20);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + 0.20);
        });
    });
}

/* ── SIREN ALARM - plays for 15 seconds ─────────────────────────────────────
   Simulates an emergency-siren sweep: oscillator frequency glides up and
   down between 800 Hz (low) and 1400 Hz (high) repeatedly, like a real
   ambulance / police siren. Uses sawtooth wave for maximum urgency.
   All notes are scheduled up-front so the sound plays even if the tab loses focus. */
function playWarningBeep() {
    var ctx = _getAudioCtx();
    if (!ctx) return;
    ctx.resume().then(function() {
        var now       = ctx.currentTime;
        var DURATION  = 15;      // total seconds of siren
        var CYCLE     = 0.80;    // one up-down sweep = 0.80 s
        var LOW_FREQ  = 800;
        var HIGH_FREQ = 1400;
        var VOLUME    = 0.50;

        for (var i = 0; i < DURATION; i += CYCLE) {
            var t   = now + i;
            var osc = ctx.createOscillator();
            var g   = ctx.createGain();
            osc.connect(g);
            g.connect(ctx.destination);
            osc.type = 'sawtooth';

            // Sweep up for first half, sweep down for second half
            osc.frequency.setValueAtTime(LOW_FREQ,  t);
            osc.frequency.linearRampToValueAtTime(HIGH_FREQ, t + CYCLE * 0.5);
            osc.frequency.linearRampToValueAtTime(LOW_FREQ,  t + CYCLE);

            g.gain.setValueAtTime(VOLUME, t);
            g.gain.setValueAtTime(VOLUME, t + CYCLE - 0.04);
            g.gain.linearRampToValueAtTime(0, t + CYCLE); // click-free crossfade

            osc.start(t);
            osc.stop(t + CYCLE);
        }
    });
}

/* ── SESSION ENDING ALARM MODAL ─────────────────────────────────────────────
   Fires at 15 s remaining for any hourly session.
   • Covers the full screen (backdrop blocks all interaction)
   • Cannot be dismissed by clicking outside or pressing Escape
   • Auto-navigates the admin to the Sessions tab
   • Offers two actions: Extend Session or End Session Now
   • Countdown inside the modal ticks down every second
   • Auto-dismissed when the session crosses into overtime              */
var sessionEndingAlerts = {}; // key: el.dataset.start → modal element

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

    // ── Read session data from the timer element ─────────────────────────
    var customer     = el.dataset.customer     || 'Session';
    var unit         = el.dataset.unit         || '';
    var sessionId    = el.dataset.sessionId    || 0;
    var mode         = el.dataset.mode         || 'hourly';
    var startTs      = parseInt(el.dataset.startTs   || 0);
    var upfrontPaid  = parseFloat(el.dataset.upfrontPaid  || 0);
    var unlimRate    = parseFloat(el.dataset.unlimitedRate || 300);
    var bookedMin    = parseInt(el.dataset.bookedMinutes   || 0);

    // ── Navigate to Sessions tab ──────────────────────────────────────────
    var sessNavEl = document.querySelector('.nav-item[onclick*="\'sessions\'"]');
    if (sessNavEl) showPage('sessions', sessNavEl);

    // ── Build the locked full-screen modal ───────────────────────────────
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
            '🚨 ALERT</div>' +
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

    // Extend button → open extend modal, close siren
    document.getElementById('gspotSirenExtendBtn').addEventListener('click', function() {
        _closeSirenModal(key);
        openExtendModal(sessionId, customer, unit, bookedMin, mode);
    });

    // End Now button → open end session modal, close siren
    document.getElementById('gspotSirenEndBtn').addEventListener('click', function() {
        _closeSirenModal(key);
        // Open the modal in locked mode (prevent outside-click close)
        _sirenTriggeredEnd = true;
        openEndSessionModal(sessionId, customer, unit, mode, startTs, bookedMin, upfrontPaid, unlimRate);
    });
}

// Block Escape key while siren modal is open
function _sirenEscBlock(e) {
    if (e.key === 'Escape' && document.getElementById('gspotSirenModal')) {
        e.preventDefault(); e.stopPropagation();
    }
}

// Flag: when true, the End Session modal was opened by the siren → prevent outside-click close
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
    document.querySelectorAll('.session-timer[data-start]').forEach(el => {
        const start   = new Date(el.dataset.start.replace(' ', 'T') + '+08:00');
        const planned = el.dataset.planned ? parseInt(el.dataset.planned) : null;
        const now     = new Date();
        const elapsed = Math.floor((now - start) / 1000); // seconds

        // Stale session guard (>24h open —  likely test/orphan data)
        if (elapsed > STALE_THRESHOLD) {
            el.classList.add('stale');
            el.textContent = `⚠️ ${Math.floor(elapsed / 86400)}d old —  end session`;
            return;
        }

        if (planned) {
            // Hourly: show countdown; flip to overtime when past booked time
            const remaining = (planned * 60) - elapsed;
            if (remaining > 0) {
                const h = Math.floor(remaining / 3600);
                const m = Math.floor((remaining % 3600) / 60);
                const s = remaining % 60;

                // ── 15-second warning beep + popup (fires once per element) ───────
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
                // ─ OVERTIME ─ beep once when the element first crosses the threshold
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

// Ã¢”â‚¬Ã¢”â‚¬ Charts Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬Ã¢”â‚¬
function renderCharts() {
    const revLabels = <?= json_encode($revLabels) ?>;
    const revData   = <?= json_encode($revChartData) ?>;
    const typeLabels= <?= json_encode($typeLabels) ?>;
    const typeCounts= <?= json_encode($typeCounts) ?>;

    const chartOpts = { responsive: true, plugins: { legend: { labels: { color: '#ccc' } } },
                        scales: { x: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,.05)' } },
                                  y: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,.05)' } } } };

    new Chart(document.getElementById('revChart'), {
        type: 'bar',
        data: {
            labels: revLabels,
            datasets: [{ label: 'Revenue (₱)', data: revData,
                backgroundColor: 'rgba(32,200,161,.5)', borderColor: '#20c8a1',
                borderWidth: 2, borderRadius: 6 }]
        },
        options: { ...chartOpts, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{ data: typeCounts,
                backgroundColor: ['rgba(95,133,218,.7)', 'rgba(32,200,161,.7)'],
                borderColor: ['#5f85da','#20c8a1'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#ccc' } } } }
    });
}

AOS.init({ duration: 600, once: true });

// ── Bell notification icon - styles ──
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

// ── Reservation notification poller ───────────────────────────────────
// Polls every 8 s. Baseline from PHP is ALWAYS authoritative at page load -
// localStorage is only used to avoid re-alerting the same IDs within one session,
// but NEVER to INCREASE the baseline above what the server reported.
(function () {
    const POLL_MS = 8000;

    // ── BUG FIX #1: Never let localStorage INCREASE the baseline.
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

    // ── BUG FIX #3: First poll at 3 s, then every 8 s (was 15 s / 30 s)
    setTimeout(function() {
        poll();
        setInterval(poll, POLL_MS);
    }, 3000);
})();

// ── Unlimited Session Auto-Termination at 12:00 AM ────────────────────────
// Monitors the clock every 30 s. When midnight (00:00 - 00:10) is detected,
// calls ajax/auto_end_unlimited.php once to close all active Unlimited sessions.
// Strictly Unlimited only — Hourly and Open Time sessions are unaffected.
(function () {
    var _midnightJobFired = false;   // prevent double-firing within the same midnight window
    var POLL_MS = 30000;             // check every 30 seconds

    function _checkMidnight() {
        var now = new Date();
        var h   = now.getHours();
        var m   = now.getMinutes();

        // Trigger window: 00:00 – 00:10 (covers late tab wake-ups)
        if (h !== 0 || m > 10) {
            // Outside the midnight window — reset the flag so next midnight fires again
            if (_midnightJobFired && (h !== 0 || m > 10)) {
                _midnightJobFired = false;
            }
            return;
        }

        // Already fired this midnight window — skip
        if (_midnightJobFired) return;
        _midnightJobFired = true;

        console.log('[GSpot] Midnight detected — triggering auto-end for Unlimited sessions…');

        fetch('ajax/auto_end_unlimited.php', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success && data.ended === undefined) return;

                var count = data.ended || 0;
                if (count === 0) {
                    console.log('[GSpot] Midnight auto-end: No active Unlimited sessions found.');
                    return;
                }

                // ── Build a rich notification toast ──────────────────────────
                var sessionLines = (data.sessions || []).map(function(s) {
                    var h = Math.floor(s.duration_minutes / 60);
                    var m = s.duration_minutes % 60;
                    var dur = (h ? h + 'h ' : '') + (m ? m + 'm' : (h ? '' : '0m'));
                    return '• ' + s.customer + ' (' + s.unit + ') — ' + dur + ' — ₱' + parseFloat(s.total_cost).toFixed(2);
                }).join('\n');

                var toastMsg = count + ' Unlimited session' + (count > 1 ? 's' : '') +
                    ' automatically ended at 12:00 AM (shop closing).\n' + sessionLines;

                console.log('[GSpot] Midnight auto-end complete:', data);

                // Show toast if available, otherwise use a non-blocking banner
                if (window.showToast) {
                    window.showToast(
                        count + ' Unlimited session' + (count > 1 ? 's' : '') +
                        ' auto-ended at 12:00 AM — ₱400.00 flat rate applied.',
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
                        'Shop Closing — Unlimited Sessions Ended</div>' +
                        '<div style="color:#aaa;font-size:12px;">' +
                        count + ' session' + (count > 1 ? 's' : '') +
                        ' ended at 12:00 AM · ₱400.00 flat rate applied each</div>' +
                        '</div>' +
                        '<button onclick="this.parentElement.parentElement.remove()" ' +
                        'style="background:none;border:none;color:#555;font-size:16px;' +
                        'cursor:pointer;margin-left:auto;flex-shrink:0;">×</button>' +
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

// ── Reservation Auto-Start Poller ─────────────────────────────────────────────
// Checks every 60 s for reservations whose reserved date+time has been reached
// and automatically converts them to active gaming sessions.
// Paired with ajax/auto_start_sessions.php on the backend.
(function () {
    var POLL_MS = 60000; // every 60 seconds

    function _autoStartSessions() {
        fetch('ajax/auto_start_sessions.php', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success) return;
                var count = data.started || 0;
                if (count === 0) return;

                console.log('[GSpot] Auto-start: ' + count + ' session(s) started.', data.sessions);

                // Show admin toast notification
                var sessionLines = (data.sessions || []).map(function(s) {
                    return '• Reservation #' + s.reservation_id + ' → Session #' + s.session_id + ' (' + s.console_type + ')';
                }).join('\n');

                if (window.showToast) {
                    window.showToast(
                        count + ' reservation(s) auto-started as active session(s).',
                        'success'
                    );
                } else {
                    var banner = document.createElement('div');
                    banner.style.cssText =
                        'position:fixed;top:20px;left:50%;transform:translateX(-50%);' +
                        'z-index:999999;background:linear-gradient(135deg,#0a2218,#0d2e22);' +
                        'border:1px solid rgba(32,200,161,.5);border-radius:14px;' +
                        'padding:16px 24px;max-width:500px;width:92%;' +
                        'box-shadow:0 8px 32px rgba(0,0,0,.6);color:#eee;font-size:13px;';
                    banner.innerHTML =
                        '<div style="display:flex;align-items:center;gap:12px;">' +
                        '<div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;' +
                        'background:rgba(32,200,161,.15);border:1px solid rgba(32,200,161,.4);' +
                        'display:flex;align-items:center;justify-content:center;font-size:16px;">' +
                        '<i class="fas fa-play-circle" style="color:#20c8a1;"></i></div>' +
                        '<div>' +
                        '<div style="font-weight:700;color:#20c8a1;margin-bottom:3px;">' +
                        count + ' Session' + (count > 1 ? 's' : '') + ' Auto-Started</div>' +
                        '<div style="color:#aaa;font-size:12px;">Reservations converted to active sessions at their scheduled time.</div>' +
                        '</div>' +
                        '<button onclick="this.parentElement.parentElement.remove()" ' +
                        'style="background:none;border:none;color:#555;font-size:16px;cursor:pointer;margin-left:auto;flex-shrink:0;">×</button>' +
                        '</div>';
                    document.body.appendChild(banner);
                    setTimeout(function() {
                        if (banner.parentNode) banner.parentNode.removeChild(banner);
                    }, 10000);
                }

                // Refresh the live section so new sessions appear immediately
                setTimeout(function() { location.reload(); }, 2000);
            })
            .catch(function(err) {
                console.warn('[GSpot] Auto-start fetch error:', err);
            });
    }

    // Run once at page load (after 10s) then every 60s
    setTimeout(function() {
        _autoStartSessions();
        setInterval(_autoStartSessions, POLL_MS);
    }, 10000);
})();
</script>
</body>
</html>