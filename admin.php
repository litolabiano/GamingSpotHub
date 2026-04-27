<?php
/**
 * Good Spot Gaming Hub - Admin Dashboard
 * Live database-connected management panel for Owner & Shopkeeper roles.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/includes/db_functions.php';

$user = getCurrentUser();
$message = '';
$messageType = '';

// ——————————————————————————————————————————————————————————————————————————————

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CSRF guard — all admin POST actions require a valid token ──────────
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

        if (!$console_id || !in_array($rental_mode, ['hourly','open_time','unlimited'])) {
            $message = 'Please select a console and rental mode.';
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
                $arStmt->execute();
            }
        }

    if ($rental_mode === 'unlimited') {
        $unlimited_payment = $_POST['unlimited_payment_method'] ?? 'cash';
        $upfront_cost      = (float)(getSetting('unlimited_rate') ?? 300);
        $tendered          = isset($_POST['unlimited_tendered']) ? (float)$_POST['unlimited_tendered'] : null;
        $shortfall         = ($tendered !== null && $tendered < $upfront_cost) ? $upfront_cost - $tendered : null;

        recordTransaction(
            $result['session_id'], $user_id, $upfront_cost, $unlimited_payment, $user['user_id'],
            $tendered,
            $shortfall,
            $shortfall ? 'Short payment at session start — short by ₱' . number_format($shortfall, 2) : null
        );
        $cost = number_format($upfront_cost, 2);
        $message = "Session #" . $result['session_id'] . " started. ₱{$cost} flat rate collected via " . ucfirst($unlimited_payment) . ".";

    } elseif ($rental_mode === 'hourly' && isset($_POST['collect_upfront']) && $planned_minutes) {
        $pr           = getPricingRules();
        $upfront_cost = ($planned_minutes <= 30)
                        ? $pr['session_min_charge']
                        : (float)($planned_minutes / 60 * $pr['hourly_rate']);
        // Add controller rental fee to upfront total if checked
        if (!empty($_POST['controller_rental']) && $_POST['controller_rental'] == '1') {
            $ctrl_fee     = (float)($_POST['controller_rental_fee_amt'] ?? getSetting('controller_rental_fee') ?? 20);
            $upfront_cost += $ctrl_fee;
        }
        $tendered     = isset($_POST['start_tendered']) ? (float)$_POST['start_tendered'] : null;
        $shortfall    = ($tendered !== null && $tendered < $upfront_cost) ? $upfront_cost - $tendered : null;

        // Amount actually collected — if customer paid less, record only what they gave
        $actualCollected = ($tendered !== null) ? min((float)$tendered, $upfront_cost) : $upfront_cost;

        recordTransaction(
            $result['session_id'], $user_id, $actualCollected, $start_payment_method, $user['user_id'],
            $tendered,
            $shortfall,
            $shortfall ? 'Short payment at session start — short by ₱' . number_format($shortfall, 2) : null
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
                        // Short payment — record only what was tendered
                        $actualCollected = $tendered_amount;
                        $shortfall       = round($remaining - $tendered_amount, 2);
                        $paymentNote     = 'Short payment — collected ₱' . number_format($tendered_amount, 2)
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
                    $message     = "Session ended. Total: ₱{$total}. Collected ₱{$tenderedFmt} — still ₱{$shortFmt} outstanding.";
                    $messageType = 'warning';
                } elseif ($remaining > 0) {
                    $due     = number_format($remaining, 2);
                    $message = "Session ended. Duration: {$mins} min. Total: ₱{$total} (prepaid ₱{$paid} + collected ₱{$due}).";
                    $messageType = 'success';
                } else {
                    $message     = "Session ended. Duration: {$mins} min. Total: ₱{$total}. Fully paid upfront — no extra charge.";
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
        $allowed    = ['available', 'in_use', 'maintenance'];
        if ($console_id && in_array($status, $allowed)) {
            updateConsoleStatus($console_id, $status);
            $message = 'Console status updated.';
            $messageType = 'success';
        }
    }

    // SAVE SETTINGS
    elseif ($action === 'save_settings') {
        $keys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                 'business_hours_open','business_hours_close','shop_phone',
                 'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge'];
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
    }

    // CONFIRM RESERVATION
    elseif ($action === 'confirm_reservation') {
        $res_id    = (int)($_POST['reservation_id'] ?? 0);
        $console_id = (int)($_POST['console_id'] ?? 0) ?: null;
        if ($res_id) {
            updateReservationStatus($res_id, 'confirmed', $console_id ?: null);
            $message = 'Reservation confirmed.';
            $messageType = 'success';
        }
    }

    // CANCEL RESERVATION (admin-initiated → cancelled_by = 'admin')
    elseif ($action === 'cancel_reservation') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        if ($res_id) {
            $stmt = $conn->prepare("UPDATE reservations SET status='cancelled', cancelled_by='admin' WHERE reservation_id=?");
            $stmt->bind_param('i', $res_id);
            $stmt->execute();
            $message = 'Reservation cancelled.';
            $messageType = 'success';
        }
    }

    // PROCESS REFUND for a customer-cancelled reservation
    elseif ($action === 'process_refund') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        if ($res_id) {
            $stmt = $conn->prepare(
                "SELECT user_id, downpayment_amount, downpayment_method
                   FROM reservations
                  WHERE reservation_id = ?
                    AND status         = 'cancelled'
                    AND (cancelled_by  = 'user' OR cancelled_by IS NULL)
                    AND downpayment_amount > 0
                    AND refund_issued = 0"
            );
            $stmt->bind_param('i', $res_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if ($res) {
                $refundAmt = (float)$res['downpayment_amount'];
                $method    = $res['downpayment_method'] ?? 'cash';
                $note      = 'Refund for cancelled reservation #' . $res_id;

                // recordTransaction() handles NULL session_id correctly
                recordTransaction(null, $res['user_id'], -$refundAmt, $method,
                                  $user['user_id'], null, null, $note);

                $stmt2 = $conn->prepare("UPDATE reservations SET refund_issued = 1 WHERE reservation_id = ?");
                $stmt2->bind_param('i', $res_id);
                $stmt2->execute();

                $message     = '₱' . number_format($refundAmt, 2) . ' refund issued for reservation #' . $res_id . '.';
                $messageType = 'success';
            } else {
                $message     = 'Reservation not eligible for refund (already processed or no payment on record).';
                $messageType = 'error';
            }
        } else {
            $message     = 'Invalid reservation ID.';
            $messageType = 'error';
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

    // COLLECT PAYMENT (mid-session, does NOT end the session)
    elseif ($action === 'collect_payment') {
        $session_id     = (int)($_POST['session_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $balanceDue     = (float)($_POST['amount'] ?? 0);   // full balance owed
        $tendered_raw   = $_POST['tendered_amount'] ?? '';
        $tendered       = ($tendered_raw !== '') ? (float)$tendered_raw : null;

        // What was ACTUALLY handed over — capped at the balance due
        // (if no tendered entered, assume exact payment of balance due)
        $actualCollected = ($tendered !== null) ? min($tendered, $balanceDue) : $balanceDue;
        $shortfall       = ($tendered !== null && $tendered < $balanceDue)
                            ? round($balanceDue - $tendered, 2) : null;

        if (!$session_id || $balanceDue <= 0) {
            $message = 'Invalid payment — balance must be greater than ₱0.';
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
                        ? 'Partial payment — collected ₱' . number_format($actualCollected, 2)
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
    // which calls extendSession() — applying bonus minutes and recording a transaction.
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

    // ADMIN REGISTER PARTICIPANT
    elseif ($action === 'admin_register_participant') {
        $tid            = (int)($_POST['tournament_id']  ?? 0);
        $uid            = (int)($_POST['user_id']        ?? 0);
        $pay_status     = in_array($_POST['payment_status'] ?? '', ['pending','paid'])
                          ? $_POST['payment_status'] : 'pending';
        if ($tid && $uid) {
            $stmt = $conn->prepare(
                "INSERT INTO tournament_participants
                    (tournament_id, user_id, payment_status, registered_by)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status)"
            );
            $stmt->bind_param('iisi', $tid, $uid, $pay_status, $user['user_id']);
            if ($stmt->execute()) {
                $message = 'Participant registered.';
                $messageType = 'success';
            } else {
                $message = 'Could not register participant: ' . $conn->error;
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid tournament or user.';
            $messageType = 'error';
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
$today = date('Y-m-d');
$todayStats = getDailySalesReport($today);
$activeSessions  = getActiveSessions();
$activeCount     = count($activeSessions);
$todayRevenue    = $todayStats['total_revenue'] ?? 0;
$todayBookings   = $todayStats['total_sessions'] ?? 0;

// All consoles
$allConsoles = getConsoles();
$availableCount  = count(array_filter($allConsoles, fn($c) => $c['status'] === 'available'));
$inUseCount      = count(array_filter($allConsoles, fn($c) => $c['status'] === 'in_use'));
$maintenanceCount= count(array_filter($allConsoles, fn($c) => $c['status'] === 'maintenance'));

// Sessions: active/live first (sorted by urgency — closest booked end time), then completed newest-first
$stmt = $conn->prepare(
    "SELECT gs.*, u.full_name AS customer_name, c.console_name, c.unit_number, c.console_type,
            COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount > 0), 0) AS upfront_paid,
            COALESCE((SELECT SUM(ABS(t.amount)) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount < 0), 0) AS refunded_amount
     FROM gaming_sessions gs
     JOIN users u ON gs.user_id = u.user_id
     JOIN consoles c ON gs.console_id = c.console_id
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

// Reservations — upcoming (pending/confirmed) + cancelled (for refund management)
$upcomingReservations  = getUpcomingReservations();
$cancelledReservations = getCancelledReservations();
$pendingResCount       = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'pending'));


// Financial stats
$finStmt = $conn->query(
    "SELECT
        SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) AND payment_status='completed' THEN amount ELSE 0 END) AS monthly_revenue,
        SUM(CASE WHEN DATE(transaction_date)=CURDATE() AND payment_status='completed' THEN amount ELSE 0 END) AS today_revenue,
        SUM(CASE WHEN payment_status='completed' THEN amount ELSE 0 END) AS total_revenue,
        COUNT(CASE WHEN payment_status='completed' THEN 1 END) AS total_transactions
     FROM transactions"
);
$finStats = $finStmt ? $finStmt->fetch_assoc() : [];

// Transaction history (last 30)
// NOTE: LEFT JOINs used because session_id can be NULL for reservation refunds.
$transResult = $conn->query(
    "SELECT t.*, u.full_name AS customer_name,
            COALESCE(c.unit_number, '—') AS unit_number,
            COALESCE(gs.rental_mode, 'refund') AS rental_mode
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
     LEFT JOIN consoles c ON gs.console_id = c.console_id
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

    if ($sess['status'] === 'active' && $paidSoFar > 0) {
        // Active session with upfront payment — balance pending at end
        $sess['paid_so_far'] = $paidSoFar;
        $pendingSessions[] = $sess;
    } elseif ($sess['status'] === 'completed'
        && $sess['total_cost'] > 0
        && $refundedAmount == 0               // no refund was issued
        && $paidSoFar > 0                     // customer DID pay something upfront
        && $paidSoFar < (float)$sess['total_cost'] // still genuinely short
    ) {
        // Completed session where total paid < total cost — genuine short payment
        $sess['paid_so_far'] = $paidSoFar;
        $pendingSessions[] = $sess;
    }
    // Early-end with nothing paid (walk-in, no upfront): fully settled, skip.
    // Sessions with refunds issued are also fully settled — skip them entirely
}


// Console usage (all time)
$usageReport = getConsoleUsageReport('2020-01-01', $today);

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
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS rev FROM transactions WHERE DATE(transaction_date)=? AND payment_status='completed'");
    $s->bind_param("s", $d);
    $s->execute();
    $revChartData[] = (float)$s->get_result()->fetch_assoc()['rev'];
}
$revLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $revLabels[] = date('M d', strtotime("-{$i} days"));
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
    <style>
        /* Force page visibility — overrides cached animation issue in admin.css */
        .page.active {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        /* Disabled submit buttons inside the end-session form (early-end guard) */
        #endSessionForm button[type="submit"]:disabled {
            opacity: 0.35 !important;
            filter: grayscale(40%);
            cursor: not-allowed !important;
            pointer-events: none !important;
        }
        /* Keep the Refund & End button always clickable inside the warning banner */
        #endEarlyWarning button {
            pointer-events: auto !important;
            opacity: 1 !important;
            filter: none !important;
            cursor: pointer !important;
        }

        /* ── Extra admin overrides ── */
        .flash-msg {
            position: fixed; top: 80px; right: 20px; z-index: 9999;
            padding: 14px 20px; border-radius: 10px; font-size: 14px; font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            animation: slideInRight .3s ease; max-width: 380px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }
        .flash-msg.success { background: rgba(32,200,161,.15); border: 1px solid rgba(32,200,161,.4); color: #20c8a1; }
        .flash-msg.error   { background: rgba(251,86,107,.15); border: 1px solid rgba(251,86,107,.4); color: #fb566b; }
        .flash-msg.warning { background: rgba(241,168,60,.15);  border: 1px solid rgba(241,168,60,.4);  color: #f1a83c; }
        @keyframes slideInRight { from { transform: translateX(120%); opacity:0; } to { transform: translateX(0); opacity:1; } }

        .status-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }
        .status-dot.available  { background:#20c8a1; }
        .status-dot.in_use     { background:#5f85da; }
        .status-dot.maintenance{ background:#fb566b; }

        .console-type-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
        .console-type-badge.ps5  { background:rgba(95,133,218,.2); color:#5f85da; border:1px solid rgba(95,133,218,.3); }
        .console-type-badge.ps4  { background:rgba(241,168,60,.15); color:#f1a83c; border:1px solid rgba(241,168,60,.3); }
        .console-type-badge.xbox { background:rgba(32,200,161,.2); color:#20c8a1; border:1px solid rgba(32,200,161,.3); }

        /* ── Session timer ── */
        .session-timer { font-family: monospace; font-size: 13px; color: #f1e1aa; font-weight: 600; }
        .session-timer.stale { color: #fb566b; font-size:11px; font-weight:500; }

        /* ── Form layout ── */
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:13px; color:#aaa; margin-bottom:6px; font-weight:600; }
        .form-group select, .form-group input[type=text], .form-group input[type=number],
        .form-group input[type=time], .form-group textarea {
            width:100%; background:rgba(10,33,81,.6); border:1px solid rgba(95,133,218,.25);
            color:#f0f0f0; padding:10px 14px; border-radius:8px; font-size:14px;
            font-family:inherit; outline:none; box-sizing:border-box; transition:.2s; }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            border-color:#20c8a1; box-shadow:0 0 0 3px rgba(32,200,161,.1); }
        .form-group textarea { resize:vertical; min-height:80px; }
        .form-check { display:flex; align-items:center; gap:8px; margin-top:6px; }
        .form-check input { width:auto; accent-color:#20c8a1; }

        /* ── Stat cards ── */
        .stat-card-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
        .stat-change.up { color:#20c8a1; }
        .stat-icon.revenue  { background:rgba(32,200,161,.15); color:#20c8a1; }
        .stat-icon.sessions { background:rgba(95,133,218,.15); color:#5f85da; }
        .stat-icon.bookings { background:rgba(179,123,236,.15); color:#b37bec; }
        .stat-icon.consoles { background:rgba(241,225,170,.15); color:#f1e1aa; }

        /* ── Console cards ── */
        .console-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:16px; }
        .console-card { background:rgba(10,33,81,.55); border:1px solid rgba(95,133,218,.15);
            border-radius:12px; padding:18px; position:relative; transition:.2s; }
        .console-card:hover { transform:translateY(-3px); }
        .console-card.available  { border-left:3px solid #20c8a1; }
        .console-card.in_use     { border-left:3px solid #5f85da; }
        .console-card.maintenance{ border-left:3px solid #fb566b; }
        .console-unit  { font-size:22px; font-weight:800; margin-bottom:4px; color:#fff; }
        .console-name  { font-size:13px; color:#888; margin-bottom:10px; }
        .console-rate  { font-size:12px; color:#f1e1aa; margin-bottom:12px; }
        .console-actions { display:flex; gap:6px; flex-wrap:wrap; }



        /* ── Badge ── */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge.active     { background:rgba(95,133,218,.2);  color:#5f85da; }
        .badge.completed  { background:rgba(32,200,161,.2);  color:#20c8a1; }
        .badge.cancelled  { background:rgba(251,86,107,.2);  color:#fb566b; }
        .badge.pending    { background:rgba(241,225,170,.2); color:#f1e1aa; }
        .badge.available  { background:rgba(32,200,161,.2);  color:#20c8a1; }
        .badge.in_use     { background:rgba(95,133,218,.2);  color:#5f85da; }
        .badge.maintenance{ background:rgba(251,86,107,.2);  color:#fb566b; }
        .badge.installed  { background:rgba(179,123,236,.2); color:#b37bec; }

        /* ── Empty state ── */
        .empty-state { text-align:center; padding:40px; color:#555; }
        .empty-state i { font-size:36px; margin-bottom:12px; display:block; }

        /* ── Responsive form ── */
        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }

        /* ── Topbar hamburger: hidden on desktop (sidebar has its own) ── */
        @media (min-width:769px) {
            .menu-toggle { display:none !important; }
            .sidebar-close-btn { display:none !important; visibility:hidden !important; }
        }

        /* ── Sidebar hamburger FA icon ── */
        .sidebar-hamburger .sidebar-ham-icon {
            font-size: 14px;
            color: rgba(255,255,255,0.55);
            transition: color 0.2s ease;
            width: auto;
        }
        .sidebar-hamburger:hover .sidebar-ham-icon { color: #20c8a1; }

        /* ── Admin topbar user dropdown ── */
        .admin-user-dropdown { position:relative; }
        .admin-user-toggle {
            display:flex; align-items:center; gap:10px;
            background:none; border:none; cursor:pointer;
            color:inherit; padding:6px 10px;
            border-radius:10px; transition:background .2s;
        }
        .admin-user-toggle:hover { background:rgba(255,255,255,.07); }
        .admin-user-dropdown.open .admin-user-toggle .fa-chevron-down { transform:rotate(180deg); }
        .admin-user-menu {
            display:none; position:absolute; right:0; top:calc(100% + 8px);
            min-width:220px; background:#0d1b3e;
            border:1px solid rgba(95,133,218,.25); border-radius:14px;
            box-shadow:0 16px 48px rgba(0,0,0,.5); z-index:9999;
            overflow:hidden; animation:fadeInDown .18s ease;
        }
        .admin-user-dropdown.open .admin-user-menu { display:block; }
        @keyframes fadeInDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .admin-dropdown-header {
            display:flex; align-items:center; gap:12px;
            padding:16px 16px 12px;
        }
        .admin-dropdown-name  { font-weight:700; font-size:14px; color:#f0f0f0; }
        .admin-dropdown-email { font-size:12px; color:#718096; margin-top:2px; }
        .admin-dropdown-divider { height:1px; background:rgba(95,133,218,.15); margin:0 12px; }
        .admin-dropdown-item {
            display:flex; align-items:center; gap:10px;
            padding:11px 16px; font-size:14px; color:#ccc;
            text-decoration:none; transition:background .15s, color .15s;
        }
        .admin-dropdown-item:hover { background:rgba(255,255,255,.06); color:#fff; }
        .admin-dropdown-danger { color:#fb566b !important; }
        .admin-dropdown-danger:hover { background:rgba(251,86,107,.1) !important; color:#fb566b !important; }
        .user-avatar-lg {
            width:42px; height:42px; font-size:16px; flex-shrink:0;
        }
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
    <div class="nav-item" onclick="showPage('consoles', this)">
        <i class="fas fa-desktop"></i><span>Consoles</span>
        <?php if ($maintenanceCount > 0): ?>
        <span <?= $navBadge ?>><?= $maintenanceCount ?></span>
        <?php endif; ?>
    </div>
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

    <div class="nav-item" onclick="showPage('settings', this)">
        <i class="fas fa-cog"></i><span>Settings</span>
    </div>
</div>

<!-- ── Top Bar ──────────────────────────────────────────────────────────────── -->
<div class="topbar">
    <div class="topbar-left">
        <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
        <h3 id="pageTitle">Dashboard</h3>
    </div>
    <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('startSession')">
            <i class="fas fa-plus"></i> New Session
        </button>
        <!-- Admin user dropdown -->
        <div class="admin-user-dropdown" id="adminUserDropdown" style="margin-left:12px">
            <button class="admin-user-toggle" id="adminUserBtn">
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
                <a href="<?= getBaseUrl() ?>/auth/logout.php" class="admin-dropdown-item admin-dropdown-danger">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Main Content ──────────────────────────────────────────────────────────── -->
<div class="main-content">

<?php include __DIR__ . '/admin_sections/dashboard.php'; ?>
<?php include __DIR__ . '/admin_sections/consoles.php'; ?>
<?php include __DIR__ . '/admin_sections/sessions.php'; ?>
<?php include __DIR__ . '/admin_sections/reservations.php'; ?>
<?php include __DIR__ . '/admin_sections/transactions.php'; ?>
<?php include __DIR__ . '/admin_sections/reports.php'; ?>
<?php include __DIR__ . '/admin_sections/tournaments.php'; ?>

<?php include __DIR__ . '/admin_sections/settings.php'; ?>

</div><!-- /.main-content -->
<?php include __DIR__ . '/admin_sections/modals.php'; ?>
<!-- â”€â”€ JavaScript â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<script src="assets/libs/aos/aos.js"></script>
<script>
// â”€â”€ Navigation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showPage(page, el) {
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
        settings: 'Settings', tournaments: 'Tournaments'
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

// ── Restore active page from URL hash on load ──
(function () {
    const hash = window.location.hash.replace('#', '');
    const validPages = ['dashboard','consoles','sessions','reservations','transactions','financial','reports','settings','tournaments'];
    if (hash && validPages.includes(hash)) {
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

// â”€â”€ Start Session Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
    }
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
    if (!checkbox.checked) {
        document.getElementById('startTendered').value = '';
        document.getElementById('startChangeDisplay').style.display = 'none';
    }
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
    // Short-payment notices — end modal and pay modal
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
        disp.innerHTML        = `<i class="fas fa-exclamation-circle"></i> Insufficient — short by <strong>₱${Math.abs(change).toFixed(2)}</strong>`;
        if (endShortNotice) endShortNotice.style.display = 'block';
        if (payShortNotice) payShortNotice.style.display = 'block';
    }
}

/**
 * Called by the End Session confirm button.
 * Copies the visible tendered input into the hidden POST field, then lets the form submit.
 * No blocking — a short payment is always allowed through.
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

    // Read cost and total play time from data-* set by PHP (getHourlyDurationOptions — DB-driven)
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
}
// Alias — called by controller rental checkbox onchange
const recalcSessionPreview = updateSessionPreview;

// Form validation: require duration for hourly
document.addEventListener('DOMContentLoaded', function () {
    // Show duration picker by default (hourly is default selected)
    onRentalModeChange();
    // Hide controller rental until an Xbox console is selected
    onConsoleChange();

    document.getElementById('startSessionForm').addEventListener('submit', function (e) {
        const mode = document.getElementById('rentalModeSelect').value;
        if (mode === 'hourly' && !document.getElementById('durationSelect').value) {
            e.preventDefault();
            alert('Please select a duration for the hourly session.');
        }
    });
});

// â”€â”€ Modals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

/* ── Billing helpers — all values driven from DB via getPricingRules() ──────── *
 * PRICING is injected by PHP so the JS always matches the backend.
 * _bracketCost / _timedCost are unchanged in shape — only their constants move.
 */
const PRICING = <?= json_encode(getPricingRules()) ?>;

function _bracketCost(partialMin) {
    // Partial-hour bracket for minutes 0–59 (fixed brackets, not rate-dependent)
    if (partialMin <=  4) return 0;   // grace
    if (partialMin <= 19) return 20;
    if (partialMin <= 34) return 40;
    if (partialMin <= 49) return 60;
    return 80;
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
        cost += cyclePay;  // inside the free window — charge the full paid block
    } else {
        cost += Math.floor(rem / 60) * rate + _bracketCost(rem % 60);
    }
    return cost;
}
function _hourlyCost(duration, planned) {
    const rate     = PRICING.hourly_rate;
    const minChg   = PRICING.session_min_charge;
    const base     = planned <= 30 ? minChg : (planned / 60 * rate);
    const overtime = duration - planned;
    if (overtime <= 0) return base;
    return base + _timedCost(overtime);
}

let _endModalTimer = null;   // holds the live-update interval

// Stores refund-modal args when the admin triggers "Refund & End" from the early-end warning
let _pendingRefundArgs = null;

/* ── Session-end audio alert (Web Audio API — no file needed) ──────────────
Plays a short 3-beep chime when the admin confirms ending a session.
Uses the browser’s built-in synthesis — works offline, no CDN required.
*/
function playSessionEndSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [0, 0.20, 0.40].forEach(function(delay) {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime + delay);
            gain.gain.setValueAtTime(0.38, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.18);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + 0.18);
        });
    } catch(e) { /* AudioContext unavailable — silently ignore */ }
}

function openEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimitedRate) {
    upfrontPaid = upfrontPaid || 0;
    document.getElementById('endSessionId').value = sessionId;

    // Fetch approved extras (controller rental etc.) FIRST, then render modal
    fetch('ajax/session_extras.php?session_id=' + sessionId)
        .then(function(r){ return r.json(); })
        .then(function(ex){
            _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, unlimitedRate,
                ex.extras || 0, ex.items || []);
        })
        .catch(function(){
            _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs,
                plannedMinutes, upfrontPaid, unlimitedRate, 0, []);
        });
}

function _renderEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimitedRate, extras, extraItems) {
    extras = extras || 0;

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
            const elapsedMin    = Math.floor(elapsed / 60);
            const elH           = Math.floor(elapsedMin / 60);
            const elM           = elapsedMin % 60;
            const elapsedLabel  = (elH ? elH + 'h ' : '') + String(elM).padStart(2,'0') + 'm';

            // Time cost alone (no extras — extras are a fixed charge, not time-based)
            const timeCost      = _timedCost(elapsedMin);
            const consumedCost  = timeCost + extras;   // total owed = time + fixed fees
            // Refund = upfront paid minus total owed
            const refundAmt     = Math.max(0, upfrontPaid - consumedCost);
            const hasRefund     = refundAmt > 0;

            // ── Populate breakdown display ───────────────────────────────
            document.getElementById('endEarlyElapsedStr').textContent  = '(' + elapsedLabel + ')';
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

            // ── No-refund note: context-aware message ────────────────────
            const noRefundNote   = document.getElementById('endEarlyNoRefundNote');
            const noRefundReason = document.getElementById('endEarlyNoRefundReason');
            if (noRefundNote) {
                noRefundNote.style.display = hasRefund ? 'none' : 'block';
                if (noRefundReason) {
                    if (upfrontPaid === 0) {
                        noRefundReason.textContent = 'Nothing was paid upfront — balance will be collected at check-out.';
                    } else if (timeCost >= upfrontPaid) {
                        noRefundReason.textContent = 'Time used already covers the upfront payment — no refund needed.';
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
            confirmBtn.disabled        = true;
            confirmBtn.style.opacity   = '0.35';
            confirmBtn.style.cursor    = 'not-allowed';

            // ── Wire up "Refund & End" button ────────────────────────────
            _pendingRefundArgs = { sessionId, customerName, unitNumber, upfrontPaid, refundAmt, consumedCost, elapsedLabel };

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

                // Pre-fill refund amount — always locked for early-end flow
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

    // Reset tendered input & change display each time modal opens
    const tenderedEl  = document.getElementById('endTendered');
    const changeDisp  = document.getElementById('endChangeDisplay');
    const costHolder  = document.getElementById('endCostAmtHolder');
    const amountDueEl = document.getElementById('endAmountDueDisplay');
    const amountDueLbl= document.getElementById('endAmountDueLabel');
    const amountDueBox= document.getElementById('endAmountDueBox');
    tenderedEl.value         = '';
    changeDisp.style.display = 'none';
    costHolder.value         = '0';
    document.getElementById('endTenderedHidden').value = '';
    const shortNotice = document.getElementById('endShortNotice');
    if (shortNotice) shortNotice.style.display = 'none';

    // Helper: update the big amount-due display + sync cost holder
    function setAmountDue(amount, sublabel) {
        costHolder.value      = amount.toFixed(2);
        amountDueEl.textContent = '₱' + amount.toFixed(2);
        if (sublabel !== undefined) amountDueLbl.textContent = sublabel;
        amountDueBox.style.display = 'block';
    }
    function hideAmountDue() {
        amountDueBox.style.display = 'none';
    }

    const modeLabel = mode === 'open_time' ? 'Open Time'
                    : mode === 'unlimited' ? 'Unlimited'
                    : 'Hourly';

    document.getElementById('endSessionSummary').textContent =
        `Ending session #${sessionId} — ${customerName} on ${unitNumber} (${modeLabel})`;

    /* ── OPEN TIME: pay at end, show live ticking cost ── */
    if (mode === 'open_time' && startTs) {
        titleEl.innerHTML     = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session & Collect Payment';
        panel.style.display   = 'block';
        payGroup.style.display = 'block';
        prepaidNote.style.display = 'none';
        payLabel.textContent  = 'Payment Method';
        confirmLbl.textContent = 'Confirm End & Record Payment';
        noteEl.innerHTML = '<i class="fas fa-info-circle"></i> Cost is calculated at end — collect from customer after confirming.';

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

    /* ── HOURLY: prepaid base, overtime may apply ── */
    } else if (mode === 'hourly' && plannedMinutes) {
        const base    = plannedMinutes <= 30 ? PRICING.session_min_charge : (plannedMinutes / 60 * PRICING.hourly_rate);
        const elapsed = Math.floor((Date.now() / 1000) - startTs);
        const minutes = Math.floor(elapsed / 60);
        const overtime = minutes - plannedMinutes;
        const cost    = _hourlyCost(minutes, plannedMinutes) + extras;
        const ph = Math.floor(plannedMinutes / 60), pm = plannedMinutes % 60;
        const bookedStr = ph ? (pm ? `${ph}h ${pm}m` : `${ph}h`) : `${pm}m`;

        panel.style.display = 'block';
        elapsedEl.textContent = String(Math.floor(minutes/60)).padStart(2,'0') + 'h ' + String(minutes%60).padStart(2,'0') + 'm';
        costEl.textContent    = '\u20b1' + cost.toFixed(2);
        updateExtrasTag(extras, extraItems);

        const remaining = Math.max(0, cost - upfrontPaid);

        if (remaining > 0) {
            setAmountDue(remaining, `Total base + overtime: ₱${cost.toFixed(2)} — Prepaid: ₱${upfrontPaid.toFixed(2)}`);
            titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session — Collect Payment';
            if (overtime > 0) {
                noteEl.innerHTML  = `<i class="fas fa-clock"></i> Booked: <strong>${bookedStr}</strong> (₱${base.toFixed(2)}).<br>`
                                  + `<span style="color:#fb566b">Overtime: +${overtime} min. Total remaining due: ₱${remaining.toFixed(2)}.</span>`;
            } else {
                noteEl.innerHTML  = `<i class="fas fa-coins"></i> Collect remaining balance of <strong>₱${remaining.toFixed(2)}</strong> now.`;
            }
            payGroup.style.display    = 'block';
            prepaidNote.style.display = 'none';
            payLabel.textContent      = 'Payment Method';
            confirmLbl.textContent    = `Confirm End & Collect ₱${remaining.toFixed(2)}`;
        } else {
            // Session fully paid
            hideAmountDue();
            costHolder.value = '0';
            titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session — Paid in Full';
            noteEl.innerHTML  = `<i class="fas fa-check-circle" style="color:#20c8a1"></i> Total cost ₱${cost.toFixed(2)} already paid. No additional charge.`;
            payGroup.style.display    = 'none';
            prepaidNote.style.display = 'block';
            confirmLbl.textContent    = 'Confirm End (No Additional Charge)';
        }

    /* ── UNLIMITED: flat rate was fully prepaid ── */
    } else if (mode === 'unlimited') {
        titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session — Paid in Full';
        panel.style.display       = 'block';
        elapsedEl.textContent     = '—';
        costEl.textContent        = 'Flat rate';
        noteEl.innerHTML          = '<i class="fas fa-infinity"></i> Unlimited session — flat rate already collected at start.';
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
        'Session #' + sessionId + ' — ' + customerName + ' on ' + unitNumber +
        ' (' + (mode === 'open_time' ? 'Open Time' : mode === 'unlimited' ? 'Unlimited' : 'Hourly') + ')';

    // Reset
    document.getElementById('payTendered').value              = '';
    document.getElementById('payChangeDisplay').style.display = 'none';
    document.getElementById('payShortNotice').style.display   = 'none';
    document.getElementById('payAmountDueDisplay').style.color = '#20c8a1';

    if (_payModalTimer) { clearInterval(_payModalTimer); _payModalTimer = null; }

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
        dueBigEl.textContent   = '₱' + due.toFixed(2);
        dueLblEl.textContent   = sublabel || '';
        amtHidden.value        = due.toFixed(2);
        if (due > 0) {
            confirmLbl.textContent = 'Collect ₱' + due.toFixed(2) + ' Balance';
            confirmBtn.disabled    = false;
            confirmBtn.style.opacity = '1';
        } else {
            confirmLbl.textContent = 'No Balance Due';
            confirmBtn.disabled    = true;
            confirmBtn.style.opacity = '0.5';
        }
        // Refresh change display if tendered already entered
        if (document.getElementById('payTendered').value)
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
            const totalCost = _timedCost(minutes);
            costEl.textContent  = '₱' + totalCost.toFixed(2);
            const due = Math.max(0, totalCost - upfrontPaid);
            const sublabel = upfrontPaid > 0
                ? 'Running cost ₱' + totalCost.toFixed(2) + ' — Already paid ₱' + upfrontPaid.toFixed(2)
                : 'Cost accumulating — pay at any time';
            setPayDue(due, sublabel);
        };
        payTick();
        _payModalTimer = setInterval(payTick, 1000);

    /* ── Hourly: snapshot at open time ── */
    } else if (mode === 'hourly' && plannedMinutes && startTs) {
        costPanel.style.display = 'block';
        const elapsed   = Math.floor((Date.now() / 1000) - startTs);
        const minutes   = Math.floor(elapsed / 60);
        const totalCost = _hourlyCost(minutes, plannedMinutes);
        const due       = Math.max(0, totalCost - upfrontPaid);
        const h = Math.floor(minutes / 60), m = minutes % 60;
        elapsedEl.textContent = (h ? h + 'h ' : '') + String(m).padStart(2,'0') + 'm';
        costEl.textContent    = '₱' + totalCost.toFixed(2);
        const baseCost  = plannedMinutes <= 30 ? 50 : (plannedMinutes / 60 * 80);
        const ph = Math.floor(plannedMinutes / 60), pm = plannedMinutes % 60;
        const bookedStr = ph ? (pm ? ph + 'h ' + pm + 'm' : ph + 'h') : pm + 'm';
        const overtime  = Math.max(0, minutes - plannedMinutes);
        let sublabel = 'Booked ' + bookedStr + ' (₱' + baseCost.toFixed(0) + ')';
        if (upfrontPaid > 0) sublabel += ' — Prepaid ₱' + upfrontPaid.toFixed(2);
        if (overtime > 0)    sublabel += ' — +' + overtime + 'min overtime';
        setPayDue(due, sublabel);

    /* ── Unlimited: already fully paid ── */
    } else if (mode === 'unlimited') {
        costPanel.style.display = 'none';
        dueBigEl.textContent = '₱0.00';
        dueBigEl.style.color = '#888';
        dueLblEl.textContent = 'Unlimited session — flat rate already collected at start';
        amtHidden.value      = '0';
        confirmLbl.textContent = 'No Balance Due';
        confirmBtn.disabled    = true;
        confirmBtn.style.opacity = '0.5';

    } else {
        costPanel.style.display = 'none';
        setPayDue(0, 'Enter amount if needed');
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
        // No tendered value — revert to full balance label
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
        ? 'Reservation #' + reservationId + ' — ' + customerName
        : 'Session #'     + sessionId     + ' — ' + customerName + ' on ' + unitNumber;
    document.getElementById('refundPaidSoFar').textContent = '₱' + paid;

    // Amount input — locked + pre-filled for reservation
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

    // Reason input — pre-filled for reservation
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
    // early_end with ₱0 is allowed — the session ends with no refund transaction.
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

        fetch('ajax/refund.php', { method: 'POST', body })
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
                _showRefundError('Network error — please try again.');
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
        'Session #' + sessionId + ' — ' + customerName + ' on ' + unitNumber;
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

// â”€â”€ Live Session Timers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const STALE_THRESHOLD = 24 * 60 * 60; // 24 hours in seconds

function pad(n) { return String(n).padStart(2, '0'); }

// Tracks which timer elements already fired the overtime beep (once per element per load)
const overtimeBeeped = new WeakSet();

/* Descending 3-tone alarm — fires when a session crosses into overtime.
   Square wave = more urgent/harsh than the sine-wave session-end chime. */
function playOvertimeBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [880, 660, 440].forEach(function(freq, i) {
            const delay = i * 0.22;
            const osc   = ctx.createOscillator();
            const gain  = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'square';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);
            gain.gain.setValueAtTime(0.25, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.20);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + 0.20);
        });
    } catch(e) {}
}

function updateTimers() {
    document.querySelectorAll('.session-timer[data-start]').forEach(el => {
        const start   = new Date(el.dataset.start.replace(' ', 'T') + '+08:00');
        const planned = el.dataset.planned ? parseInt(el.dataset.planned) : null;
        const now     = new Date();
        const elapsed = Math.floor((now - start) / 1000); // seconds

        // Stale session guard (>24h open â€” likely test/orphan data)
        if (elapsed > STALE_THRESHOLD) {
            el.classList.add('stale');
            el.textContent = `âš ï¸ ${Math.floor(elapsed / 86400)}d old â€” end session`;
            return;
        }

        if (planned) {
            // Hourly: show countdown; flip to overtime when past booked time
            const remaining = (planned * 60) - elapsed;
            if (remaining > 0) {
                const h = Math.floor(remaining / 3600);
                const m = Math.floor((remaining % 3600) / 60);
                const s = remaining % 60;
                el.style.color = '#20c8a1';
                el.textContent = (h ? h + 'h ' : '') + `${pad(m)}:${pad(s)} left`;
            } else {
                // — OVERTIME — beep once when the element first crosses the threshold
                if (!overtimeBeeped.has(el)) {
                    overtimeBeeped.add(el);
                    playOvertimeBeep();
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

// â”€â”€ Charts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
            datasets: [{ label: 'Revenue (â‚±)', data: revData,
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

// ── Admin user dropdown ──────────────────────────────────────────────
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

// ── Reservation notification poller ───────────────────────────────────
// Polls every 30 s. Baseline is set from PHP on page load so we never
// fire a toast for reservations that already existed when the admin opened the page.
(function () {
    const POLL_MS   = 30000;
    // PHP injects the current max id at page-load time — safe baseline
    let lastId = <?= $initMaxResId ?>;
    // If localStorage has a higher value (from a previous session still in memory), use that
    const stored = parseInt(localStorage.getItem('gspot_last_res_id') || '0');
    if (stored > lastId) lastId = stored;
    localStorage.setItem('gspot_last_res_id', lastId);

    function poll() {
        fetch('ajax/poll_notifications.php?last_id=' + lastId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.new_count > 0) showResNotification(data.new_count, data.items);
                if (data.max_id > lastId) {
                    lastId = data.max_id;
                    localStorage.setItem('gspot_last_res_id', lastId);
                }
            })
            .catch(function() {}); // silent on network failure
    }

    function showResNotification(count, items) {
        const existing = document.getElementById('gspotResNotif');
        if (existing) existing.remove();

        const first  = items[0] || {};
        const name   = first.customer_name || 'A customer';
        const msg    = count === 1
            ? name + ' just made a new reservation!'
            : count + ' new reservations are waiting for review.';

        const toast = document.createElement('div');
        toast.id = 'gspotResNotif';
        toast.style.cssText = [
            'position:fixed;bottom:24px;right:24px;z-index:99999;',
            'background:linear-gradient(135deg,#0d1b3e,#08101c);',
            'border:1px solid rgba(32,200,161,.45);border-radius:16px;',
            'padding:18px 20px;display:flex;align-items:flex-start;gap:14px;',
            'box-shadow:0 16px 48px rgba(0,0,0,.6),0 0 0 1px rgba(32,200,161,.1);',
            'animation:slideInRight .35s cubic-bezier(.34,1.56,.64,1);',
            'max-width:340px;font-family:inherit;'
        ].join('');

        toast.innerHTML =
            '<div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;' +
            'background:rgba(32,200,161,.15);border:1px solid rgba(32,200,161,.3);' +
            'display:flex;align-items:center;justify-content:center;font-size:18px;color:#20c8a1;">' +
            '<i class="fas fa-calendar-check"></i></div>' +
            '<div style="flex:1;min-width:0;">' +
            '<div style="font-weight:700;font-size:14px;color:#f0f0f0;margin-bottom:4px;">New Reservation' + (count > 1 ? 's' : '') + '!</div>' +
            '<div style="font-size:13px;color:#aaa;line-height:1.4;">' + msg + '</div>' +
            '<button id="gspotResNotifView" style="margin-top:10px;padding:6px 14px;border-radius:8px;' +
            'background:rgba(32,200,161,.2);border:1px solid rgba(32,200,161,.4);' +
            'color:#20c8a1;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">'
            + '<i class="fas fa-eye" style="margin-right:5px;"></i>View Reservations</button>' +
            '</div>' +
            '<button onclick="document.getElementById(\'gspotResNotif\').remove()" ' +
            'style="background:none;border:none;color:#666;cursor:pointer;font-size:18px;padding:0;flex-shrink:0;line-height:1;">&times;</button>';

        document.body.appendChild(toast);

        // Wire "View Reservations" button
        document.getElementById('gspotResNotifView').addEventListener('click', function() {
            const navEl = document.querySelector('.nav-item[onclick*="\'reservations\'"]');
            showPage('reservations', navEl);
            toast.remove();
        });

        // Play a subtle ping sound
        try {
            const ctx2 = new (window.AudioContext || window.webkitAudioContext)();
            const osc2  = ctx2.createOscillator();
            const gain2 = ctx2.createGain();
            osc2.connect(gain2); gain2.connect(ctx2.destination);
            osc2.type = 'sine'; osc2.frequency.value = 660;
            gain2.gain.setValueAtTime(0.3, ctx2.currentTime);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx2.currentTime + 0.5);
            osc2.start(); osc2.stop(ctx2.currentTime + 0.5);
        } catch(e) {}

        // Auto-dismiss after 15 s
        setTimeout(function() { if (toast.parentNode) toast.remove(); }, 15000);
    }

    // Start polling after 15 s (avoids false-positive on fresh page load)
    setTimeout(function() {
        poll();
        setInterval(poll, POLL_MS);
    }, 15000);
})();
</script>
</body>
</html>
