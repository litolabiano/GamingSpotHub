<?php
/**
 * GamingSpotHub – Tournament Registration Page
 * Allows users to view open tournaments and register themselves.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireLogin(); // Must be logged in to register
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/PayMongoService.php';

$user = getCurrentUser();
$message = '';
$messageType = '';

// ══════════════════════════════════════════════════════════════════════════════
// STALE PENDING SESSION CLEANUP
// ══════════════════════════════════════════════════════════════════════════════
if (!empty($_SESSION['pending_tournament_reg']) && empty($_GET['paymongo'])) {
    $stalePending = $_SESSION['pending_tournament_reg'];
    $staleAge     = time() - ($stalePending['created_at'] ?? 0);
    $staleId      = $stalePending['session_id'] ?? '';

    $shouldClean = false;
    if ($staleAge > 1800) {
        $shouldClean = true;
    } elseif ($staleAge > 60 && $staleId) {
        $staleCs = PayMongoService::getCheckoutSession($staleId);
        if ($staleCs['success'] && in_array($staleCs['payment_status'], ['unpaid', 'expired'])) {
            $shouldClean = true;
        }
    }

    if ($shouldClean) {
        unset($_SESSION['pending_tournament_reg']);
        $_SESSION['paymongo_abandoned_notice'] = true;
    }
}

if (!empty($_SESSION['paymongo_abandoned_notice'])) {
    unset($_SESSION['paymongo_abandoned_notice']);
    $message = 'Your previous payment session was not completed. No charge was made — please try again.';
    $messageType = 'error';
}

// ══════════════════════════════════════════════════════════════════════════════
// PATH B — PayMongo Checkout Session redirect-back handler
// ══════════════════════════════════════════════════════════════════════════════
if (!empty($_GET['paymongo']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $pm_result = $_GET['paymongo'];        // 'success' or 'failed'
    $pending   = $_SESSION['pending_tournament_reg'] ?? null;
    $session_id = $pending['session_id'] ?? trim($_GET['session_id'] ?? '');

    if ($pm_result === 'success' && $session_id && $pending) {
        // Verify payment
        $cs = [];
        $maxRetries = 4;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $cs = PayMongoService::getCheckoutSession($session_id);
            if (!empty($cs['success']) && $cs['payment_status'] === 'paid') {
                break;
            }
            if ($attempt < $maxRetries) sleep(1);
        }

        if ($cs['success'] && $cs['payment_status'] === 'paid') {
            $payment_id = $cs['payment_id'] ?? null;
            $stored_ref = $payment_id ?: $session_id;

            // ── Double Check duplicate registration ──
            $checkStmt = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
            $checkStmt->bind_param('ii', $pending['tournament_id'], $user['user_id']);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                unset($_SESSION['pending_tournament_reg']);
                $_SESSION['reg_success'] = 'You are already registered for this tournament.';
                header('Location: tournament_register.php');
                exit;
            }

            // Finalize registration
            $regStmt = $conn->prepare("INSERT INTO tournament_participants (tournament_id, user_id, ign, contact_number, notes, payment_status, registration_date, paymongo_source_id, paymongo_payment_id, paymongo_status) VALUES (?, ?, ?, ?, ?, 'paid', NOW(), ?, ?, 'paid')");
            $regStmt->bind_param('iisssss', $pending['tournament_id'], $user['user_id'], $pending['ign'], $pending['contact'], $pending['notes'], $session_id, $stored_ref);
            
            if ($regStmt->execute()) {
                $reg_id = $conn->insert_id;
                // Record transaction
                if ($pending['entry_fee'] > 0) {
                    recordTransaction(
                        null,
                        $user['user_id'],
                        $pending['entry_fee'],
                        'gcash',
                        $user['user_id'],
                        $pending['entry_fee'],
                        null,
                        'Tournament registration fee for ' . $pending['tournament_name'] . ' (Reg #' . $reg_id . ')'
                    );
                }

                logActivity($user['user_id'], 'Tournament Registration', 'Registered and paid for tournament: ' . $pending['tournament_name']);
                
                unset($_SESSION['pending_tournament_reg']);
                $_SESSION['reg_success'] = 'Registration successful! Your payment has been received and your spot is confirmed.';
                header('Location: tournament_register.php');
                exit;
            } else {
                unset($_SESSION['pending_tournament_reg']);
                $message = 'Payment was received but we could not save your registration. Please contact the shop with your GCash reference (' . htmlspecialchars($session_id) . ').';
                $messageType = 'error';
            }
        } elseif ($cs['success'] && $cs['payment_status'] === 'unpaid') {
            unset($_SESSION['pending_tournament_reg']);
            $message = 'Your payment was not completed. No charge was made — please try again.';
            $messageType = 'error';
        } elseif ($cs['success'] && $cs['payment_status'] === 'expired') {
            unset($_SESSION['pending_tournament_reg']);
            $message = 'Your checkout session has expired. No charge was made — please try again.';
            $messageType = 'error';
        } else {
            $message = 'We could not verify your payment status at this time. If you completed the payment, please contact the shop with your GCash reference. Otherwise, please try again.';
            $messageType = 'error';
        }
    } elseif ($pm_result === 'failed') {
        unset($_SESSION['pending_tournament_reg']);
        $message = 'Payment was cancelled or failed. No charge was made — please try again.';
        $messageType = 'error';
    } elseif ($pm_result === 'success' && !$pending) {
        header('Location: tournament_register.php');
        exit;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// PATH A — Handle Registration POST
// ══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    if (!verifyCsrf($message, $messageType)) {
        // CSRF failed
    } elseif (empty($_POST['no_refund_agreed'])) {
        $message = 'You must agree to the Terms and Conditions before registering.';
        $messageType = 'error';
    } else {
        $tournament_id = (int)($_POST['tournament_id'] ?? 0);
        $ign           = trim($_POST['ign'] ?? '');
        $contact       = trim($_POST['contact_number'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');

        if (empty($contact)) {
            $message = 'Contact number is required.';
            $messageType = 'error';
        } elseif (!preg_match('/^(09|\+639)\d{9}$/', $contact)) {
            $message = 'Please enter a valid Philippine mobile number (e.g., 09123456789 or +639123456789).';
            $messageType = 'error';
        } else {

        // 1. Validate tournament
        $tStmt = $conn->prepare("SELECT * FROM tournaments WHERE tournament_id = ? AND status = 'scheduled'");
        $tStmt->bind_param('i', $tournament_id);
        $tStmt->execute();
        $tournament = $tStmt->get_result()->fetch_assoc();

        if (!$tournament) {
            $message = 'Tournament not found or registration is closed.';
            $messageType = 'error';
        } else {
            // 2. Check if already registered
            $checkStmt = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
            $checkStmt->bind_param('ii', $tournament_id, $user['user_id']);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = 'You are already registered for this tournament.';
                $messageType = 'error';
            } else {
                $entry_fee = (float)$tournament['entry_fee'];
                
                if ($entry_fee > 0) {
                    // Create PayMongo Checkout Session
                    $base        = ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST'];
                    $script_dir  = rtrim(dirname($_SERVER['PHP_SELF']),'/');
                    $success_url = $base . $script_dir . '/tournament_register.php?paymongo=success';
                    $cancel_url  = $base . $script_dir . '/tournament_register.php?paymongo=failed';

                    $centavos = PayMongoService::pesosToCentavos($entry_fee);
                    $desc = 'Registration fee for ' . $tournament['tournament_name'];

                    $pm = PayMongoService::createCheckoutSession(
                        $centavos,
                        $desc,
                        $success_url,
                        $cancel_url,
                        $user['email'] ?? '',
                        $user['full_name'] ?? 'Customer'
                    );

                    if ($pm['success'] && !empty($pm['checkout_url'])) {
                        $_SESSION['pending_tournament_reg'] = [
                            'tournament_id'   => $tournament_id,
                            'tournament_name' => $tournament['tournament_name'],
                            'ign'             => $ign,
                            'contact'         => $contact,
                            'notes'           => $notes,
                            'entry_fee'       => $entry_fee,
                            'session_id'      => $pm['session_id'],
                            'created_at'      => time(),
                        ];
                        header('Location: ' . $pm['checkout_url']);
                        exit;
                    } else {
                        $message = 'Could not connect to GCash payment gateway: ' . htmlspecialchars($pm['message'] ?? 'Unknown error');
                        $messageType = 'error';
                    }
                } else {
                    // Free tournament - register directly
                    $regStmt = $conn->prepare("INSERT INTO tournament_participants (tournament_id, user_id, ign, contact_number, notes, payment_status, registration_date) VALUES (?, ?, ?, ?, ?, 'paid', NOW())");
                    $regStmt->bind_param('iisss', $tournament_id, $user['user_id'], $ign, $contact, $notes);
                    
                    if ($regStmt->execute()) {
                        logActivity($user['user_id'], 'Tournament Registration', 'Registered for free tournament: ' . $tournament['tournament_name']);
                        $message = 'Registration successful! Your spot is confirmed.';
                        $messageType = 'success';
                    } else {
                        $message = 'Registration failed. Please try again.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

if (!empty($_SESSION['reg_success'])) {
    $message = $_SESSION['reg_success'];
    $messageType = 'success';
    unset($_SESSION['reg_success']);
}


// Fetch open tournaments
$openTournaments = [];
if (isset($conn)) {
    $res = $conn->query("
        SELECT t.*, 
               (SELECT COUNT(*) FROM tournament_participants tp WHERE tp.tournament_id = t.tournament_id) as current_participants
        FROM tournaments t
        WHERE t.status IN ('scheduled', 'upcoming')
        ORDER BY t.start_date ASC
    ");
    if ($res) $openTournaments = $res->fetch_all(MYSQLI_ASSOC);
}

// Page title
$pageTitle = "Tournament Registration - GamingSpotHub";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap CSS (required by the shared navbar) -->
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">

    <!-- Main site CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --bg-deep: #070b14;
            --card-bg: #0d121f;
            --accent: #f1a83c;
            --mint: #20c8a1;
            --blue: #5f85da;
            --text-dim: rgba(255,255,255,0.6);
        }
        body {
            background-color: var(--bg-deep);
            color: #fff;
            margin: 0;
            padding-top: 100px; /* Space for fixed navbar */
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .header-section {
            text-align: center;
            margin-bottom: 50px;
        }
        .header-section h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff, #888);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header-section p {
            color: var(--text-dim);
            font-size: 1.1rem;
        }
        
        .tourn-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        
        .tourn-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        .tourn-card:hover {
            transform: translateY(-5px);
            border-color: rgba(241,168,60,0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .tourn-banner {
            height: 6px;
            background: linear-gradient(90deg, var(--accent), var(--mint));
        }
        
        .tourn-body {
            padding: 24px;
        }
        
        .tourn-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .status-scheduled { background: rgba(32,200,161,0.12); color: var(--mint); border: 1px solid rgba(32,200,161,0.25); }
        .status-upcoming { background: rgba(241,168,60,0.12); color: var(--accent); border: 1px solid rgba(241,168,60,0.25); }
        
        .tourn-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: #fff;
        }
        
        .tourn-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 20px 0;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text-dim);
        }
        .meta-item i {
            color: var(--blue);
            width: 14px;
        }
        
        .tourn-footer {
            padding: 20px 24px;
            background: rgba(255,255,255,0.02);
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .price-tag {
            font-weight: 800;
            color: var(--accent);
            font-size: 1.1rem;
        }
        
        .btn-reg {
            background: var(--mint);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        .btn-reg:hover {
            filter: brightness(1.1);
            transform: scale(1.02);
            color: #000;
        }
        .btn-reg.disabled {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.3);
            cursor: not-allowed;
            transform: none;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        
        .modal-content {
            background: #0d121f;
            width: 90%;
            max-width: 500px;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
            animation: modalSlide 0.3s ease-out;
        }
        @keyframes modalSlide {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 24px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 24px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dim);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            font-family: inherit;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: var(--blue); }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .alert-success { background: rgba(32,200,161,0.12); color: var(--mint); border: 1px solid rgba(32,200,161,0.3); }
        .alert-error { background: rgba(251,86,107,0.12); color: #fb566b; border: 1px solid rgba(251,86,107,0.3); }
        
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: var(--card-bg);
            border-radius: 30px;
            border: 1px dashed rgba(255,255,255,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .empty-state i {
            font-size: 4rem;
            color: rgba(255,255,255,0.1);
            margin-bottom: 24px;
        }
        .empty-state h3 { font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 12px; }
        .empty-state p { color: var(--text-dim); margin-bottom: 32px; line-height: 1.6; }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: rgba(95,133,218,0.15);
            border: 1px solid rgba(95,133,218,0.3);
            border-radius: 16px;
            color: var(--blue);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s;
        }
        .btn-back:hover {
            background: var(--blue);
            color: #fff;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <div class="header-section">
            <h1>Battle for Glory</h1>
            <p>Select a tournament below and secure your spot in the arena.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($openTournaments)): ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>No Open Tournaments</h3>
                <p>We're currently preparing our next big event. Stay tuned to our socials for announcements!</p>
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="tourn-grid">
                <?php foreach ($openTournaments as $t): 
                    $isScheduled = $t['status'] === 'scheduled';
                    $canRegister = $isScheduled;
                ?>
                    <div class="tourn-card">
                        <div class="tourn-banner"></div>
                        <div class="tourn-body">
                            <span class="tourn-status status-<?= $t['status'] ?>">
                                <?= $t['status'] === 'scheduled' ? 'Open Reg.' : 'Coming Soon' ?>
                            </span>
                            <div class="tourn-name"><?= htmlspecialchars($t['tournament_name']) ?></div>
                            <div style="font-size:13px; color:var(--blue); font-weight:700; margin-bottom:12px;">
                                <i class="fas fa-gamepad" style="margin-right:4px;"></i> <?= htmlspecialchars($t['game_name']) ?>
                            </div>
                            
                            <div class="tourn-meta">
                                <div class="meta-item"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($t['start_date'])) ?></div>
                                <div class="meta-item"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($t['start_date'])) ?></div>
                                <div class="meta-item"><i class="fas fa-users"></i> <?= $t['current_participants'] ?> Participants</div>
                                <div class="meta-item"><i class="fas fa-display"></i> <?= htmlspecialchars($t['console_type']) ?></div>
                            </div>
                            
                            <?php if ($t['prize_pool'] > 0): ?>
                            <div style="background:rgba(32,200,161,0.06); border-radius:12px; padding:12px; display:flex; align-items:center; gap:10px; margin-top:10px;">
                                <i class="fas fa-award" style="color:var(--mint); font-size:1.2rem;"></i>
                                <div>
                                    <div style="font-size:10px; color:var(--text-dim); text-transform:uppercase;">Prize Pool</div>
                                    <div style="font-size:14px; font-weight:800; color:var(--mint);">₱<?= number_format($t['prize_pool'],0) ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tourn-footer">
                            <div class="price-tag">
                                <?= (float)$t['entry_fee'] > 0 ? '₱' . number_format($t['entry_fee'], 0) : 'FREE' ?>
                            </div>
                            <?php if ($canRegister): ?>
                                <button class="btn-reg" onclick="openRegModal(<?= $t['tournament_id'] ?>, '<?= htmlspecialchars(addslashes($t['tournament_name'])) ?>', <?= (float)$t['entry_fee'] ?>)">
                                    <i class="fas fa-user-plus"></i> Register Now
                                </button>
                            <?php else: ?>
                                <button class="btn-reg disabled" disabled>WAITING</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Registration Modal -->
    <div class="modal" id="regModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="margin:0; font-size:1.2rem; font-weight:800;"><i class="fas fa-file-signature" style="color:var(--accent); margin-right:10px;"></i> Registration</h2>
                <button onclick="closeRegModal()" style="background:none; border:none; color:rgba(255,255,255,0.4); font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="tournament_id" id="modal_t_id">
                    
                    <div style="margin-bottom:20px; padding:14px; background:rgba(95,133,218,0.08); border-radius:12px; border:1px solid rgba(95,133,218,0.2);">
                        <div style="font-size:11px; color:var(--text-dim); text-transform:uppercase; margin-bottom:4px;">Tournament</div>
                        <div id="modal_t_name" style="font-weight:800; color:var(--blue);"></div>
                    </div>

                    <div class="form-group">
                        <label>In-Game Name (IGN) *</label>
                        <input type="text" name="ign" class="form-control" placeholder="e.g. GamerPro_99" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="tel" name="contact_number" class="form-control" placeholder="09171234567" required>
                        <div style="font-size:10px; color:var(--text-dim); margin-top:4px;">Format: 09XXXXXXXXX or +639XXXXXXXXX</div>
                    </div>

                    <div class="form-group" style="margin-bottom:10px;">
                        <label>Notes / Team Name</label>
                        <textarea name="notes" class="form-control" style="resize:none;" rows="2" placeholder="Optional..."></textarea>
                    </div>

                    <div style="margin-bottom: 20px; font-weight: 800; color: var(--accent); font-size: 14px; padding: 12px; background: rgba(241,168,60,0.1); border-radius: 12px;">
                        Registration Fee: <span id="modal_fee_display"></span>
                    </div>

                    <div class="form-group" style="margin-top:20px; background:rgba(255,255,255,0.03); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; margin:0; text-transform:none;">
                            <input type="checkbox" name="no_refund_agreed" value="1" required style="margin-top:4px; width:16px; height:16px; accent-color:var(--mint);">
                            <span style="font-size:12px; line-height:1.5; color:var(--text-dim); font-weight:500;">
                                I agree to the <a href="terms.php" target="_blank" style="color:var(--mint); text-decoration:none;">Terms & Conditions</a>. Entry fees are non-refundable. By registering, you agree to show up at the hub 30 minutes before the start time.
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-reg" style="width:100%; justify-content:center; padding:14px;">
                        Pay via Paymongo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            var nav = document.getElementById('mainNav');
            if (window.scrollY > 10) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
        // Initial check
        if (window.scrollY > 10) {
            document.getElementById('mainNav').classList.add('scrolled');
        }

        function openRegModal(id, name, fee) {
            document.getElementById('modal_t_id').value = id;
            document.getElementById('modal_t_name').textContent = name;
            document.getElementById('modal_fee_display').textContent = fee > 0 ? '₱' + fee.toLocaleString() : 'FREE';
            document.getElementById('regModal').classList.add('active');
        }
        function closeRegModal() {
            document.getElementById('regModal').classList.remove('active');
        }
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('regModal')) {
                closeRegModal();
            }
        }
    </script>
</body>
</html>
