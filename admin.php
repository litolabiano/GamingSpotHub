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

    // START SESSION
    if ($action === 'start_session') {
        $user_id         = (int)($_POST['user_id'] ?? 0);
        $console_id      = (int)($_POST['console_id'] ?? 0);
        $rental_mode     = $_POST['rental_mode'] ?? '';
        $planned_minutes = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;
        $start_payment_method = $_POST['start_payment_method'] ?? 'cash';

        if (!$user_id || !$console_id || !in_array($rental_mode, ['hourly','open_time','unlimited'])) {
            $message = 'Please fill in all session fields correctly.';
            $messageType = 'error';
        } elseif ($rental_mode === 'hourly' && (!$planned_minutes || $planned_minutes <= 0)) {
            $message = 'Please select a duration for the hourly session.';
            $messageType = 'error';
        } else {
            $result = startSession($user_id, $console_id, $rental_mode, $user['user_id'], $planned_minutes);
      if ($result['success']) {
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
        $upfront_cost = ($planned_minutes <= 30) ? 50.0 : (float)($planned_minutes / 60 * 80);
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
}else {
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

                $remaining   = round($result['total_cost'] - $alreadyPaid, 2);

                // Shortfall: how much the customer was short (0 when they paid exact or more)
                $shortfall   = null;
                $paymentNote = null;
                if ($tendered_amount !== null && $remaining > 0) {
                    $diff = $tendered_amount - $remaining;
                    if ($diff < 0) {
                        $shortfall   = abs($diff);
                        $paymentNote = 'Short payment — customer short by ₱' . number_format($shortfall, 2);
                    }
                }

                // Fetch user_id
                $stmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ?");
                $stmt->bind_param('i', $session_id);
                $stmt->execute();
                $sess_row = $stmt->get_result()->fetch_assoc();

                if ($sess_row && $remaining > 0) {
                    // Always record the transaction even if tendered < remaining (store shortfall)
                    recordTransaction(
                        $session_id, $sess_row['user_id'], $remaining, $payment_method,
                        $user['user_id'], $tendered_amount, $shortfall, $paymentNote
                    );
                }

                $mins  = $result['duration_minutes'];
                $total = number_format($result['total_cost'], 2);
                $paid  = number_format($alreadyPaid, 2);
                $due   = number_format(max(0, $remaining), 2);

                if ($shortfall !== null && $shortfall > 0) {
                    $shortFmt = number_format($shortfall, 2);
                    $message  = "Session ended. Duration: {$mins} min. Total: ₱{$total}."
                              . " Short payment recorded — customer short by ₱{$shortFmt}.";
                    $messageType = 'warning';
                } elseif ($remaining > 0) {
                    $message     = "Session ended. Duration: {$mins} min. Total: ₱{$total} (prepaid ₱{$paid} + collected ₱{$due}).";
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
                 'business_hours_open','business_hours_close','shop_phone'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                updateSetting($key, trim($_POST[$key]));
            }
        }
        $message = 'Settings saved successfully.';
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

    // CANCEL RESERVATION
    elseif ($action === 'cancel_reservation') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        if ($res_id) {
            updateReservationStatus($res_id, 'cancelled');
            $message = 'Reservation cancelled.';
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
            $stmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ? AND status = 'active'");
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

    // ISSUE REFUND
    elseif ($action === 'issue_refund') {
        $session_id    = (int)($_POST['session_id'] ?? 0);
        $refund_amount = (float)($_POST['refund_amount'] ?? 0);
        $refund_reason = trim($_POST['refund_reason'] ?? '');

        if (!$session_id || $refund_amount <= 0) {
            $message = 'Invalid refund — amount must be greater than ₱0.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM gaming_sessions WHERE session_id = ?");
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            $sess_row = $stmt->get_result()->fetch_assoc();
            if ($sess_row) {
                $note = 'Refund issued' . ($refund_reason ? ': ' . $refund_reason : '');
                recordTransaction(
                    $session_id, $sess_row['user_id'], -abs($refund_amount), 'refund',
                    $user['user_id'], null, null, $note
                );
                $message = 'Refund of ₱' . number_format($refund_amount, 2) . ' issued.' .
                           ($refund_reason ? ' Reason: ' . htmlspecialchars($refund_reason) : '');
                $messageType = 'success';
            } else {
                $message = 'Session not found.';
                $messageType = 'error';
            }
        }
    }

    // EXTEND SESSION (adds to planned_minutes for hourly sessions)
    elseif ($action === 'extend_session') {
        $session_id    = (int)($_POST['session_id'] ?? 0);
        $extra_minutes = (int)($_POST['extra_minutes'] ?? 0);

        if (!$session_id || $extra_minutes <= 0) {
            $message = 'Invalid extend — please select additional time.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("SELECT planned_minutes FROM gaming_sessions WHERE session_id = ? AND status = 'active'");
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            $sess_row = $stmt->get_result()->fetch_assoc();
            if ($sess_row !== false && $sess_row !== null) {
                $new_planned = ((int)($sess_row['planned_minutes'] ?? 0)) + $extra_minutes;
                $stmt2 = $conn->prepare("UPDATE gaming_sessions SET planned_minutes = ? WHERE session_id = ?");
                $stmt2->bind_param('ii', $new_planned, $session_id);
                $stmt2->execute();
                $h = intdiv($new_planned, 60); $m = $new_planned % 60;
                $dStr = $h ? ($m ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
                $message = "Session #{$session_id} extended by {$extra_minutes} min. New booked duration: {$dStr}.";
                $messageType = 'success';
            } else {
                $message = 'Session not found or already ended.';
                $messageType = 'error';
            }
        }
    }
}

// â”€â”€â”€ DATA FETCHING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id), 0) AS upfront_paid
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

// Reservations (upcoming + active)
$upcomingReservations = getUpcomingReservations(14); // next 2 weeks
$pendingResCount = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'pending'));


// Financial stats
$finStmt = $conn->query(
    "SELECT
        SUM(CASE WHEN MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) AND payment_status='completed' THEN amount ELSE 0 END) AS monthly_revenue,
        SUM(CASE WHEN DATE(transaction_date)=CURDATE() AND payment_status='completed' THEN amount ELSE 0 END) AS today_revenue,
        SUM(CASE WHEN payment_status='completed' THEN amount ELSE 0 END) AS total_revenue,
        COUNT(CASE WHEN payment_status='completed' THEN 1 END) AS total_transactions
     FROM transactions"
);
$finStats = $finStmt->fetch_assoc();

// Transaction history (last 30)
$transSessions = $conn->query(
    "SELECT t.*, u.full_name AS customer_name, c.unit_number, gs.rental_mode
     FROM transactions t
     JOIN users u ON t.user_id = u.user_id
     JOIN gaming_sessions gs ON t.session_id = gs.session_id
     JOIN consoles c ON gs.console_id = c.console_id
     ORDER BY t.transaction_date DESC LIMIT 30"
)->fetch_all(MYSQLI_ASSOC);

// Unlimited rate constant (used by sessions.php pending balance display)
$unlimitedRateVal = (float)(getSetting('unlimited_rate') ?? 300);

// Pending sessions: active sessions that have already collected some upfront payment
$pendingSessions = [];
foreach ($recentSessions as $sess) {
    if ($sess['status'] === 'active' && (float)($sess['upfront_paid'] ?? 0) > 0) {
        $sess['paid_so_far'] = (float)$sess['upfront_paid'];
        $pendingSessions[] = $sess;
    }
}


// Console usage (all time)
$usageReport = getConsoleUsageReport('2020-01-01', $today);

// Settings
$settingsKeys = ['ps5_hourly_rate','xbox_hourly_rate','unlimited_rate','controller_rental_fee',
                 'business_hours_open','business_hours_close','shop_name','shop_address','shop_phone'];
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
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="assets/libs/chartjs/chart.min.js"></script>
    <style>
        /* â”€â”€ Extra admin overrides â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

        /* â”€â”€ Session timer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .session-timer { font-family: monospace; font-size: 13px; color: #f1e1aa; font-weight: 600; }
        .session-timer.stale { color: #fb566b; font-size:11px; font-weight:500; }

        /* â”€â”€ Form layout â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

        /* â”€â”€ Stat cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .stat-card-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
        .stat-change.up { color:#20c8a1; }
        .stat-icon.revenue  { background:rgba(32,200,161,.15); color:#20c8a1; }
        .stat-icon.sessions { background:rgba(95,133,218,.15); color:#5f85da; }
        .stat-icon.bookings { background:rgba(179,123,236,.15); color:#b37bec; }
        .stat-icon.consoles { background:rgba(241,225,170,.15); color:#f1e1aa; }

        /* â”€â”€ Console cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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



        /* â”€â”€ Badge â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge.active     { background:rgba(95,133,218,.2);  color:#5f85da; }
        .badge.completed  { background:rgba(32,200,161,.2);  color:#20c8a1; }
        .badge.cancelled  { background:rgba(251,86,107,.2);  color:#fb566b; }
        .badge.pending    { background:rgba(241,225,170,.2); color:#f1e1aa; }
        .badge.available  { background:rgba(32,200,161,.2);  color:#20c8a1; }
        .badge.in_use     { background:rgba(95,133,218,.2);  color:#5f85da; }
        .badge.maintenance{ background:rgba(251,86,107,.2);  color:#fb566b; }
        .badge.installed  { background:rgba(179,123,236,.2); color:#b37bec; }

        /* â”€â”€ Empty state â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .empty-state { text-align:center; padding:40px; color:#555; }
        .empty-state i { font-size:36px; margin-bottom:12px; display:block; }

        /* â”€â”€ Responsive form â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
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

<!-- â”€â”€ Sidebar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="sidebar" id="sidebar" style="display:flex;flex-direction:column;">
    <div class="sidebar-header">
        <a class="navbar-brand" href="index.php">
            <div class="logo-container">
                <span class="logo-g">G</span><span class="logo-s">s</span><span class="logo-p">p</span><span class="logo-o">o</span><span class="logo-t">t</span>
                <span class="logo-text">GAMING HUB</span>
            </div>
        </a>
    </div>
    <div class="nav-item active" onclick="showPage('dashboard', this)">
        <i class="fas fa-chart-line"></i><span>Dashboard</span>
    </div>
    <div class="nav-item" onclick="showPage('consoles', this)">
        <i class="fas fa-desktop"></i><span>Consoles</span>
    </div>
    <div class="nav-item" onclick="showPage('sessions', this)">
        <i class="fas fa-play-circle"></i><span>Sessions</span>
    </div>


    <div class="nav-item" onclick="showPage('financial', this)">
        <i class="fas fa-peso-sign"></i><span>Financial</span>
    </div>
    <div class="nav-item" onclick="showPage('reports', this)">
        <i class="fas fa-chart-bar"></i><span>Reports</span>
    </div>
    <div class="nav-item" onclick="showPage('settings', this)">
        <i class="fas fa-cog"></i><span>Settings</span>
    </div>
    <div style="flex:1"></div>
    <a href="index.php" class="nav-item" style="text-decoration:none;color:inherit;border-top:1px solid rgba(255,255,255,.1);padding-top:15px">
        <i class="fas fa-arrow-left"></i><span>Back to Site</span>
    </a>
</div>

<!-- â”€â”€ Top Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="topbar">
    <div class="topbar-left">
        <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
        <h3 id="pageTitle">Dashboard</h3>
    </div>
    <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('startSession')">
            <i class="fas fa-plus"></i> New Session
        </button>
        <div class="user-profile" style="margin-left:12px">
            <div class="user-avatar"><?= getUserInitials() ?></div>
            <div>
                <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($user['full_name']) ?></div>
                <div style="font-size:12px;color:#718096"><?= getRoleBadge() ?></div>
            </div>
        </div>
    </div>
</div>

<!-- â”€â”€ Main Content â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="main-content">

<?php include __DIR__ . '/admin_sections/dashboard.php'; ?>
<?php include __DIR__ . '/admin_sections/consoles.php'; ?>
<?php include __DIR__ . '/admin_sections/sessions.php'; ?>
<?php include __DIR__ . '/admin_sections/financial.php'; ?>
<?php include __DIR__ . '/admin_sections/reports.php'; ?>
<?php include __DIR__ . '/admin_sections/settings.php'; ?>

</div><!-- /.main-content -->
<?php include __DIR__ . '/admin_sections/modals.php'; ?>

<!-- â”€â”€ JavaScript â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<script src="assets/libs/aos/aos.js"></script>
<script>
// â”€â”€ Navigation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showPage(page, el) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById(page).classList.add('active');
    if (el) el.classList.add('active');
    const titles = {
        dashboard: 'Dashboard', consoles: 'Console Management',
        sessions: 'Session Management',
        financial: 'Financial', reports: 'Analytics & Reports',
        settings: 'Settings'
    };
    document.getElementById('pageTitle').textContent = titles[page] || page;

    // Render charts lazily on first visit to reports
    if (page === 'reports' && !window.chartsRendered) {
        renderCharts();
        window.chartsRendered = true;
    }
}

function toggleSidebar() {
    const sidebar     = document.getElementById('sidebar');
    const topbar      = document.querySelector('.topbar');
    const mainContent = document.querySelector('.main-content');
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    topbar.style.left      = isCollapsed ? '70px'  : '260px';
    mainContent.style.marginLeft = isCollapsed ? '70px'  : '260px';
}

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
    const tenderedVal = document.getElementById('endTendered').value;
    document.getElementById('endTenderedHidden').value = tenderedVal;
    // Form submits normally after this (no e.preventDefault())
}

function updateSessionPreview() {
    const minutes = parseInt(document.getElementById('durationSelect').value);
    const input   = document.getElementById('plannedMinutesInput');
    const preview = document.getElementById('sessionPreview');
    if (!minutes) { preview.style.display = 'none'; input.value = ''; return; }

    input.value = minutes;

    // Estimated end time
    const now    = new Date();
    const endAt  = new Date(now.getTime() + minutes * 60000);
    const endStr = endAt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });

    // Base cost (mirrors PHP logic)
    const cost = (minutes <= 30) ? 50 : (minutes / 60 * 80);

    // Populate hidden cost holder for the change calculator
    const costHolder = document.getElementById('startCostAmt');
    if (costHolder) costHolder.textContent = cost.toFixed(2);

    document.getElementById('previewEndTime').textContent = endStr;
    document.getElementById('previewCost').textContent    = '₱' + cost.toFixed(2);
    document.getElementById('previewOvertime').style.display = 'block';
    preview.style.display = 'block';
}

// Form validation: require duration for hourly
document.addEventListener('DOMContentLoaded', function () {
    // Show duration picker by default (hourly is default selected)
    onRentalModeChange();

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

/* ── Billing helpers — mirrors PHP computePartialPeriodCost / computeTimedCost ── *
 * Rule: FREE 30 min after every 2 paid hours.
 * Cycle = 150 min (120 paid + 30 free) = ₱160 per cycle.
 */
function _bracketCost(partialMin) {
    // Partial-hour bracket for minutes 0–59
    if (partialMin <= 4)  return 0;   // grace
    if (partialMin <= 19) return 20;
    if (partialMin <= 34) return 40;
    if (partialMin <= 49) return 60;
    return 80;
}
function _timedCost(totalMin) {
    if (totalMin <= 0) return 0;
    const CYCLE = 150;   // 120 min paid + 30 min free
    const CYCLE_COST = 160;
    const fullCycles = Math.floor(totalMin / CYCLE);
    const remainder  = totalMin % CYCLE;
    let cost = fullCycles * CYCLE_COST;
    if (remainder > 120) {
        // Inside the free 30-min window — charge the full 2-hour block
        cost += CYCLE_COST;
    } else {
        // Inside the paid window — hourly bracket billing
        cost += Math.floor(remainder / 60) * 80 + _bracketCost(remainder % 60);
    }
    return cost;
}
function _hourlyCost(duration, planned) {
    const base     = planned <= 30 ? 50 : (planned / 60 * 80);
    const overtime = duration - planned;
    if (overtime <= 0) return base;
    return base + _timedCost(overtime);
}

let _endModalTimer = null;   // holds the live-update interval

function openEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimitedRate) {
    upfrontPaid = upfrontPaid || 0;
    document.getElementById('endSessionId').value = sessionId;

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
            const dueCost = _timedCost(minutes);
            costEl.textContent = '₱' + dueCost.toFixed(2);
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
        const base    = plannedMinutes <= 30 ? 50 : (plannedMinutes / 60 * 80);
        const elapsed = Math.floor((Date.now() / 1000) - startTs);
        const minutes = Math.floor(elapsed / 60);
        const overtime = minutes - plannedMinutes;
        const cost    = _hourlyCost(minutes, plannedMinutes);
        const ph = Math.floor(plannedMinutes / 60), pm = plannedMinutes % 60;
        const bookedStr = ph ? (pm ? `${ph}h ${pm}m` : `${ph}h`) : `${pm}m`;

        panel.style.display = 'block';
        elapsedEl.textContent = String(Math.floor(minutes/60)).padStart(2,'0') + 'h ' + String(minutes%60).padStart(2,'0') + 'm';
        costEl.textContent    = '₱' + cost.toFixed(2);

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
function openRefundModal(sessionId, customerName, unitNumber, upfrontPaid) {
    document.getElementById('refundSessionId').value = sessionId;
    document.getElementById('refundSessionSummary').textContent =
        'Session #' + sessionId + ' — ' + customerName + ' on ' + unitNumber;
    const paid = parseFloat(upfrontPaid || 0).toFixed(2);
    document.getElementById('refundPaidSoFar').textContent = '₱' + paid;
    document.getElementById('refundMaxNote').textContent   = 'Max refundable: ₱' + paid;
    document.getElementById('refundAmount').value = '';
    document.getElementById('refundReason').value = '';
    openModal('refundSession');
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
</script>
</body>
</html>
