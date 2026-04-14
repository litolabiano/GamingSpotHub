<?php
/**
 * Good Spot Gaming Hub - Admin Dashboard
 * Live database-connected management panel for Owner & Shopkeeper roles.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/includes/db_functions.php';

$user = getCurrentUser();

// ── Flash message: read once from session and clear ──
$message     = $_SESSION['flash_message']      ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// ——————————————————————————————————————————————————————————————————————————————

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // START SESSION
    if ($action === 'start_session') {
        $user_id              = (int)($_POST['user_id'] ?? 0);
        $console_id           = (int)($_POST['console_id'] ?? 0);
        $rental_mode          = $_POST['rental_mode'] ?? '';
        $planned_minutes      = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;
        $start_payment_method = $_POST['start_payment_method'] ?? 'cash';
        $collect_upfront      = isset($_POST['collect_upfront']);
        // Each mode has its own distinct field name to avoid last-value collision
        $amt_tendered = ($rental_mode === 'unlimited')
            ? (float)($_POST['unlim_amount_tendered'] ?? 0)
            : (float)($_POST['start_amount_tendered'] ?? 0);

        if (!$user_id || !$console_id || !in_array($rental_mode, ['hourly','open_time','unlimited'])) {
            $message = 'Please fill in all session fields correctly.';
            $messageType = 'error';
        } elseif ($rental_mode === 'hourly' && (!$planned_minutes || $planned_minutes <= 0)) {
            $message = 'Please select a duration for the hourly session.';
            $messageType = 'error';
        } else {
            // Compute expected upfront cost (used for messaging & partial-payment record)
            $upfront_cost = 0.0;
            if ($rental_mode === 'unlimited') {
                $upfront_cost = (float)(getSetting('unlimited_rate') ?? 300);
            } elseif ($rental_mode === 'hourly' && $collect_upfront && $planned_minutes) {
                $upfront_cost = ($planned_minutes <= 30) ? 50.0 : (float)($planned_minutes / 60 * 80);
            }

            $result = startSession($user_id, $console_id, $rental_mode, $user['user_id'], $planned_minutes);
            if ($result['success']) {
                if ($rental_mode === 'unlimited') {
                    $unlimited_payment = $_POST['unlimited_payment_method'] ?? 'cash';
                    // Record whatever was tendered (partial or full)
                    $amt_to_record = ($amt_tendered > 0) ? min($amt_tendered, $upfront_cost) : $upfront_cost;
                    recordTransaction($result['session_id'], $user_id, $amt_to_record, $unlimited_payment, $user['user_id']);
                    $recorded = number_format($amt_to_record, 2);
                    if ($amt_to_record < $upfront_cost) {
                        $remaining = number_format($upfront_cost - $amt_to_record, 2);
                        $message = "Session #" . $result['session_id'] . " started. ₱{$recorded} collected via " . ucfirst($unlimited_payment) . " (partial). ₱{$remaining} remaining — collect at session end.";
                    } else {
                        $change  = $amt_tendered > $upfront_cost ? $amt_tendered - $upfront_cost : 0;
                        $message = "Session #" . $result['session_id'] . " started. ₱{$recorded} flat rate collected via " . ucfirst($unlimited_payment) . ".";
                        if ($change > 0) $message .= ' Change: ₱' . number_format($change, 2) . '.';
                    }
                    $messageType = 'success';

                } elseif ($rental_mode === 'hourly' && $collect_upfront && $planned_minutes) {
                    // Record whatever was tendered (partial or full)
                    $amt_to_record = ($amt_tendered > 0) ? min($amt_tendered, $upfront_cost) : $upfront_cost;
                    recordTransaction($result['session_id'], $user_id, $amt_to_record, $start_payment_method, $user['user_id']);
                    $recorded = number_format($amt_to_record, 2);
                    if ($amt_to_record < $upfront_cost) {
                        $remaining = number_format($upfront_cost - $amt_to_record, 2);
                        $message = "Session #" . $result['session_id'] . " started. ₱{$recorded} collected via " . ucfirst($start_payment_method) . " (partial). ₱{$remaining} remaining — collect at session end.";
                    } else {
                        $change  = $amt_tendered > $upfront_cost ? $amt_tendered - $upfront_cost : 0;
                        $message = "Session #" . $result['session_id'] . " started. ₱{$recorded} collected upfront via " . ucfirst($start_payment_method) . ".";
                        if ($change > 0) $message .= ' Change: ₱' . number_format($change, 2) . '.';
                    }
                    $messageType = 'success';

                } else {
                    $message     = 'Session #' . $result['session_id'] . ' started. Payment will be collected at the end.';
                    $messageType = 'success';
                }
            } else {
                // Generic fallback
                $errMsg = $result['message'];

                // If the console is not available, find out why and show actionable info
                if (stripos($result['message'], 'not available') !== false) {
                    $blockStmt = $conn->prepare(
                        "SELECT gs.session_id, u.full_name, c.status AS con_status
                         FROM consoles c
                         LEFT JOIN gaming_sessions gs ON gs.console_id = c.console_id AND gs.status = 'active'
                         LEFT JOIN users u ON u.user_id = gs.user_id
                         WHERE c.console_id = ?"
                    );
                    $blockStmt->bind_param('i', $console_id);
                    $blockStmt->execute();
                    $blockRow = $blockStmt->get_result()->fetch_assoc();

                    if ($blockRow && $blockRow['con_status'] === 'maintenance') {
                        $errMsg = 'That console is currently under maintenance.';
                    } elseif ($blockRow && $blockRow['session_id']) {
                        $errMsg = 'Console is in use by session #' . $blockRow['session_id']
                                . ' (' . htmlspecialchars($blockRow['full_name']) . ').'
                                . ' End that session first.';
                    } else {
                        $errMsg = 'Console is not available. The page has been refreshed — please select another console.';
                    }
                }

                $message     = $errMsg;
                $messageType = 'error';
            }
        }
    }


    // END SESSION + RECORD OUTSTANDING BALANCE
    elseif ($action === 'end_session') {
        $session_id     = (int)($_POST['session_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $end_tendered   = (float)($_POST['end_amount_tendered'] ?? 0);

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

                if ($sess_row && $remaining > 0) {
                    // Record only the outstanding balance (overtime for hourly, full for open_time)
                    recordTransaction($session_id, $sess_row['user_id'], $remaining, $payment_method, $user['user_id']);
                }

                $mins = $result['duration_minutes'];
                $total = number_format($result['total_cost'], 2);
                $paid  = number_format($alreadyPaid, 2);
                $due   = number_format(max(0, $remaining), 2);

                if ($remaining > 0) {
                    $message = "Session ended. Duration: {$mins} min. Total: ₱{$total} (prepaid ₱{$paid} + collected ₱{$due}).";
                    if ($end_tendered > 0) {
                        $change = $end_tendered - $remaining;
                        if ($change > 0) $message .= ' Change: ₱' . number_format($change, 2) . '.';
                    }
                } else {
                    $message = "Session ended. Duration: {$mins} min. Total: ₱{$total}. Fully paid upfront — no extra charge.";
                }
                $messageType = 'success';
            } else {
                // Check the actual session state so we can give a helpful message
                $chkStmt = $conn->prepare("SELECT status, total_cost FROM gaming_sessions WHERE session_id = ?");
                $chkStmt->bind_param('i', $session_id);
                $chkStmt->execute();
                $chkRow = $chkStmt->get_result()->fetch_assoc();

                if (!$chkRow) {
                    $message = 'Session #' . $session_id . ' does not exist.';
                } elseif ($chkRow['status'] === 'completed') {
                    $message = 'Session #' . $session_id . ' was already ended';
                    if ($chkRow['total_cost']) {
                        $message .= ' (Total: ₱' . number_format($chkRow['total_cost'], 2) . ')';
                    }
                    $message .= '. Refresh the page to see updated session list.';
                } else {
                    $message = 'Could not end session: ' . $result['message'];
                }
                $messageType = 'error';
            }
        }
    }

    // COLLECT PAYMENT (active session — does NOT end the session)
    elseif ($action === 'collect_payment') {
        $session_id     = (int)($_POST['session_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $pay_amount     = (float)($_POST['pay_amount'] ?? 0);

        if (!$session_id || $pay_amount <= 0) {
            $message = 'Invalid payment details.';
            $messageType = 'error';
        } else {
            // Fetch session + total already paid
            $chk = $conn->prepare(
                "SELECT gs.user_id, gs.status, gs.rental_mode, gs.planned_minutes,
                        gs.start_time, gs.hourly_rate,
                        s.setting_value AS unlimited_rate,
                        COALESCE(SUM(t.amount), 0) AS total_paid
                 FROM gaming_sessions gs
                 LEFT JOIN system_settings s ON s.setting_key = 'unlimited_rate'
                 LEFT JOIN transactions t ON t.session_id = gs.session_id AND t.payment_status = 'completed'
                 WHERE gs.session_id = ? AND gs.status = 'active'
                 GROUP BY gs.session_id"
            );
            $chk->bind_param('i', $session_id);
            $chk->execute();
            $sessRow = $chk->get_result()->fetch_assoc();

            if (!$sessRow) {
                $message = 'Active session #' . $session_id . ' not found.';
                $messageType = 'error';
            } else {
                $alreadyPaid = (float)$sessRow['total_paid'];

                // Calculate current estimated cost so we can warn about overpayment
                $startTs  = strtotime($sessRow['start_time']);
                $elapsed  = (int)round((time() - $startTs) / 60);
                $estCost  = computeRentalFee(
                    $sessRow['rental_mode'], $elapsed,
                    $sessRow['hourly_rate'],
                    (float)($sessRow['unlimited_rate'] ?? 300),
                    $sessRow['planned_minutes'] ?? null
                );

                // Warn but don't block — staff may intentionally collect partial
                recordTransaction($session_id, $sessRow['user_id'], $pay_amount, $payment_method, $user['user_id']);
                $newTotal = $alreadyPaid + $pay_amount;
                $message  = '₱' . number_format($pay_amount, 2) . ' collected for session #' . $session_id
                          . '. Total paid so far: ₱' . number_format($newTotal, 2) . '.';
                $messageType = 'success';
            }
        }
    }


    // REFUND SESSION
    elseif ($action === 'refund_session') {
        $session_id     = (int)($_POST['session_id'] ?? 0);
        $refund_amount  = (float)($_POST['refund_amount'] ?? 0);
        $refund_reason  = trim($_POST['refund_reason'] ?? '');
        $payment_method = $_POST['payment_method'] ?? 'cash';

        if (!$session_id || $refund_amount <= 0) {
            $message = 'Invalid refund details.';
            $messageType = 'error';
        } else {
            // Fetch session + total paid so far
            $chk = $conn->prepare("SELECT gs.user_id, gs.status, COALESCE(SUM(t.amount),0) AS total_paid
                FROM gaming_sessions gs
                LEFT JOIN transactions t ON t.session_id = gs.session_id AND t.payment_status = 'completed'
                WHERE gs.session_id = ? GROUP BY gs.session_id");
            $chk->bind_param('i', $session_id);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();

            if (!$row) {
                $message = 'Session not found.';
                $messageType = 'error';
            } elseif ($refund_amount > (float)$row['total_paid']) {
                $message = 'Refund amount exceeds total collected (₱' . number_format($row['total_paid'], 2) . ').';
                $messageType = 'error';
            } else {
                // Insert a negative transaction as refund
                $neg = -abs($refund_amount);
                $stmt = $conn->prepare("INSERT INTO transactions (session_id, user_id, amount, payment_method, payment_status, processed_by)
                    VALUES (?, ?, ?, ?, 'refunded', ?)");
                $stmt->bind_param('iidsi', $session_id, $row['user_id'], $neg, $payment_method, $user['user_id']);
                $stmt->execute();
                $message = 'Refund of ₱' . number_format($refund_amount, 2) . ' issued for session #' . $session_id . '.';
                $messageType = 'success';
            }
        }
    }

    // EXTEND SESSION
    elseif ($action === 'extend_session') {
        $session_id   = (int)($_POST['session_id'] ?? 0);
        $add_minutes  = (int)($_POST['add_minutes'] ?? 0);

        if (!$session_id || $add_minutes <= 0) {
            $message = 'Please specify a valid number of minutes to extend.';
            $messageType = 'error';
        } else {
            $chk = $conn->prepare("SELECT session_id, rental_mode, planned_minutes, status FROM gaming_sessions WHERE session_id = ? AND status = 'active'");
            $chk->bind_param('i', $session_id);
            $chk->execute();
            $sess = $chk->get_result()->fetch_assoc();

            if (!$sess) {
                $message = 'Active session #' . $session_id . ' not found.';
                $messageType = 'error';
            } else {
                $new_planned = (int)($sess['planned_minutes'] ?? 0) + $add_minutes;
                $upd = $conn->prepare("UPDATE gaming_sessions SET planned_minutes = ? WHERE session_id = ?");
                $upd->bind_param('ii', $new_planned, $session_id);
                $upd->execute();
                $h = intdiv($add_minutes, 60);
                $m = $add_minutes % 60;
                $label = $h ? ($m ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
                $message = 'Session #' . $session_id . ' extended by ' . $label . '. New booked duration: ' . intdiv($new_planned, 60) . 'h ' . ($new_planned % 60) . 'm.';
                $messageType = 'success';
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

    // ── PRG: store flash and redirect so page refresh never re-submits ──
    if ($message !== '') {
        $_SESSION['flash_message']      = $message;
        $_SESSION['flash_message_type'] = $messageType;
    }
    $section = $_POST['_section'] ?? 'dashboard';
    header('Location: admin.php#' . $section);
    exit;
}

// â”€â”€â”€ DATA FETCHING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// Dashboard stats — revenue from transactions (cash actually collected), bookings from gaming_sessions
$today           = date('Y-m-d');
$activeSessions  = getActiveSessions();
$activeCount     = count($activeSessions);

$todayRevStmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS today_revenue
     FROM transactions
     WHERE DATE(transaction_date) = ? AND payment_status = 'completed'"
);
$todayRevStmt->bind_param('s', $today);
$todayRevStmt->execute();
$todayRevenue = (float)$todayRevStmt->get_result()->fetch_assoc()['today_revenue'];

$todayBookingsStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM gaming_sessions
     WHERE DATE(start_time) = ? AND status IN ('active','completed')"
);
$todayBookingsStmt->bind_param('s', $today);
$todayBookingsStmt->execute();
$todayBookings = (int)$todayBookingsStmt->get_result()->fetch_assoc()['cnt'];

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

// ── Pending Payments: active sessions with partial upfront (paid > 0 but < expected) ─────
$unlimitedRateVal = (float)(getSetting('unlimited_rate') ?? 300);
$pendingStmt = $conn->prepare(
    "SELECT gs.session_id, gs.rental_mode, gs.planned_minutes, gs.start_time,
            u.full_name AS customer_name, u.user_id,
            c.unit_number, c.console_type,
            COALESCE(SUM(t.amount), 0) AS paid_so_far
     FROM gaming_sessions gs
     JOIN users u ON gs.user_id = u.user_id
     JOIN consoles c ON gs.console_id = c.console_id
     LEFT JOIN transactions t ON t.session_id = gs.session_id AND t.payment_status = 'completed'
     WHERE gs.status = 'active'
     GROUP BY gs.session_id
     HAVING paid_so_far > 0
     ORDER BY gs.start_time DESC"
);
$pendingStmt->execute();
$pendingAllPaid = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Keep only sessions where paid < expected base cost
$pendingSessions = array_filter($pendingAllPaid, function($sess) use ($unlimitedRateVal) {
    $paid = (float)$sess['paid_so_far'];
    if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']) {
        $expected = $sess['planned_minutes'] <= 30 ? 50.0 : (float)($sess['planned_minutes'] / 60 * 80);
        return $paid < $expected;
    }
    if ($sess['rental_mode'] === 'unlimited') {
        return $paid < $unlimitedRateVal;
    }
    return false;
});

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
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
function calcChange(tenderedId, displayId, costHolderId, isEnd) {
    const el   = document.getElementById(costHolderId);
    const due  = parseFloat(el.value !== undefined ? el.value : el.textContent) || 0;
    const paid = parseFloat(document.getElementById(tenderedId).value) || 0;
    const disp = document.getElementById(displayId);

    if (!paid) { disp.style.display = 'none'; return; }

    const diff = paid - due;
    disp.style.display = 'block';
    if (diff >= 0) {
        // Exact or over — show change
        disp.style.background = 'rgba(32,200,161,.15)';
        disp.style.border     = '1px solid rgba(32,200,161,.3)';
        disp.style.color      = '#20c8a1';
        disp.innerHTML        = `<i class="fas fa-coins"></i> Change: <strong>₱${diff.toFixed(2)}</strong>`;
    } else if (isEnd) {
        // End session — must collect full balance, no deferral allowed
        disp.style.background = 'rgba(251,86,107,.15)';
        disp.style.border     = '1px solid rgba(251,86,107,.3)';
        disp.style.color      = '#fb566b';
        disp.innerHTML        = `<i class="fas fa-exclamation-circle"></i> Insufficient — short by <strong>₱${Math.abs(diff).toFixed(2)}</strong>`;
    } else {
        // Start session partial — balance deferred to session end
        disp.style.background = 'rgba(241,168,60,.12)';
        disp.style.border     = '1px solid rgba(241,168,60,.35)';
        disp.style.color      = '#f1a83c';
        disp.innerHTML        = `<i class="fas fa-clock"></i> Partial — <strong>₱${Math.abs(diff).toFixed(2)}</strong> remaining (collect at session end)`;
    }
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

// Store Pay modal args while End modal is open so _endPayFirst() can open it
let _endPayArgs = null;

function openEndSessionModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimRate) {
    upfrontPaid    = upfrontPaid    || 0;
    unlimRate      = unlimRate      || 0;
    plannedMinutes = plannedMinutes || 0;

    // Parse Manila-time datetime string to Unix epoch
    if (typeof startTs === 'string') {
        startTs = Math.floor(new Date(startTs.replace(' ', 'T') + '+08:00').getTime() / 1000);
    }

    // Store args for "Collect Payment First" shortcut
    _endPayArgs = [sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimRate];

    if (_endModalTimer) { clearInterval(_endModalTimer); _endModalTimer = null; }

    document.getElementById('endSessionId').value = sessionId;

    const panel       = document.getElementById('endCostPanel');
    const elapsedEl   = document.getElementById('endElapsed');
    const costEl      = document.getElementById('endEstCost');
    const noteEl      = document.getElementById('endCostNote');
    const prepaidNote = document.getElementById('endPrepaidNote');
    const warningDiv  = document.getElementById('endPayWarning');
    const warningAmt  = document.getElementById('endPayWarningAmt');
    const payFirstBtn = document.getElementById('endPayFirstBtn');
    const confirmLbl  = document.getElementById('endSessionConfirmLabel');
    const titleEl     = document.getElementById('endSessionModalTitle');

    // Reset state
    warningDiv.style.display  = 'none';
    prepaidNote.style.display = 'none';
    payFirstBtn.style.display = 'none';

    const modeLabel = mode === 'open_time' ? 'Open Time'
                    : mode === 'unlimited'  ? 'Unlimited'
                    : 'Hourly';

    document.getElementById('endSessionSummary').textContent =
        `Ending session #${sessionId} — ${customerName} on ${unitNumber} (${modeLabel})`;

    titleEl.innerHTML = '<i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session';
    confirmLbl.textContent = 'Confirm End Session';

    var _endOutstanding = 0;  // tracks current outstanding amount for submit guard

    function showOutstanding(remaining) {
        _endOutstanding = remaining;
        warningAmt.textContent = '₱' + remaining.toFixed(2);
        warningDiv.style.display  = 'block';
        prepaidNote.style.display = 'none';
        payFirstBtn.style.display = 'block';
    }
    function showFullyPaid() {
        _endOutstanding = 0;
        warningDiv.style.display  = 'none';
        prepaidNote.style.display = 'block';
        payFirstBtn.style.display = 'none';
    }

    // Intercept form submit — warn and block if payment is still due
    var endForm = document.getElementById('endSessionForm');
    if (endForm) {
        endForm.onsubmit = function(e) {
            if (_endOutstanding > 0) {
                e.preventDefault();
                // Flash the warning banner with a shake effect
                warningDiv.style.outline = '2px solid #f1a83c';
                warningDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                warningDiv.animate
                    ? warningDiv.animate([
                        { transform: 'translateX(-6px)' }, { transform: 'translateX(6px)' },
                        { transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' },
                        { transform: 'translateX(0)' }
                      ], { duration: 350 })
                    : null;
                setTimeout(function() { warningDiv.style.outline = ''; }, 800);
            }
        };
    }

    /* ── OPEN TIME: live ticking elapsed + cost (read-only) ── */
    if (mode === 'open_time' && startTs) {
        panel.style.display = 'block';
        noteEl.innerHTML = '<i class="fas fa-info-circle"></i> Open time — session cost shown for reference.';

        function tick() {
            const elapsed  = Math.floor(Date.now() / 1000 - startTs);
            const minutes  = Math.floor(elapsed / 60);
            const secs     = elapsed % 60;
            const h = Math.floor(minutes / 60), m = minutes % 60;
            elapsedEl.textContent = (h ? h + 'h ' : '') + String(m).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
            const dueCost  = _timedCost(minutes);
            costEl.textContent = '₱' + dueCost.toFixed(2);
            const remaining = Math.max(0, dueCost - upfrontPaid);
            if (remaining > 0) { showOutstanding(remaining); }
            else                { showFullyPaid(); }
        }
        tick();
        _endModalTimer = setInterval(tick, 1000);

    /* ── HOURLY ── */
    } else if (mode === 'hourly' && plannedMinutes && startTs) {
        panel.style.display = 'block';
        const elapsed   = Math.floor(Date.now() / 1000 - startTs);
        const minutes   = Math.floor(elapsed / 60);
        const cost      = _hourlyCost(minutes, plannedMinutes);
        const base      = plannedMinutes <= 30 ? 50 : (plannedMinutes / 60 * 80);
        const overtime  = minutes - plannedMinutes;
        const ph = Math.floor(plannedMinutes / 60), pm = plannedMinutes % 60;
        const bookedStr = (ph ? ph + 'h ' : '') + (pm ? pm + 'm' : '');

        elapsedEl.textContent = String(Math.floor(minutes/60)).padStart(2,'0') + 'h ' + String(minutes%60).padStart(2,'0') + 'm';
        costEl.textContent    = '₱' + cost.toFixed(2);

        if (overtime > 0) {
            noteEl.innerHTML = `<i class="fas fa-clock"></i> Booked: <strong>${bookedStr}</strong> (₱${base.toFixed(2)}) — <span style="color:#fb566b">Overtime: +${overtime} min</span>.`;
        } else {
            noteEl.innerHTML = `<i class="fas fa-clock"></i> Booked: <strong>${bookedStr}</strong>. Session within booked time.`;
        }

        const remaining = Math.max(0, cost - upfrontPaid);
        if (remaining > 0) { showOutstanding(remaining); }
        else                { showFullyPaid(); }

    /* ── UNLIMITED ── */
    } else if (mode === 'unlimited') {
        panel.style.display   = 'block';
        elapsedEl.textContent = '—';
        costEl.textContent    = unlimRate ? '₱' + unlimRate.toFixed(2) : 'Flat rate';
        noteEl.innerHTML      = '<i class="fas fa-infinity"></i> Unlimited flat rate session.';

        const remaining = unlimRate > 0 ? Math.max(0, unlimRate - upfrontPaid) : 0;
        if (remaining > 0) { showOutstanding(remaining); }
        else                { showFullyPaid(); }

    } else {
        panel.style.display = 'none';
    }

    openModal('endSession');
}

// "Collect Payment First" — close End modal and open Pay modal
function _endPayFirst() {
    closeModal('endSession');
    if (_endPayArgs) {
        openPayModal.apply(null, _endPayArgs);
    }
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

    // ── Custom validation for Start Session modal (replaces browser tooltips) ──
    const startForm = document.getElementById('startSessionForm');
    if (startForm) {
        startForm.addEventListener('submit', function (e) {
            const userId    = startForm.querySelector('[name="user_id"]').value;
            const consoleId = startForm.querySelector('[name="console_id"]').value;
            const mode      = document.getElementById('rentalModeSelect').value;
            const planned   = document.getElementById('plannedMinutesInput').value;

            if (!userId) {
                e.preventDefault();
                showStartError('Please select a customer before starting the session.');
                return;
            }
            if (!consoleId) {
                e.preventDefault();
                showStartError('Please select a console for this session.');
                return;
            }
            if (mode === 'hourly' && !planned) {
                e.preventDefault();
                showStartError('Please select a duration for the hourly session.');
                return;
            }
            clearStartError();
        });
    }
    function showStartError(msg) {
        const box = document.getElementById('startFormError');
        document.getElementById('startFormErrorMsg').textContent = msg;
        if (box) { box.style.display = 'block'; box.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
    }
    function clearStartError() {
        const box = document.getElementById('startFormError');
        if (box) box.style.display = 'none';
    }
}); // end DOMContentLoaded

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
