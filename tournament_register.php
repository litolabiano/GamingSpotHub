<?php
/**
 * GamingSpotHub – Tournament Registration Page
 * Allows users to view open tournaments and register themselves.
 */
require_once __DIR__ . '/includes/session_helper.php';
requireLogin(); // Must be logged in to register
require_once __DIR__ . '/includes/db_functions.php';

$user = getCurrentUser();
$message = '';
$messageType = '';

// Handle Registration POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    if (!verifyCsrf($message, $messageType)) {
        // CSRF failed
    } else {
        $tournament_id = (int)($_POST['tournament_id'] ?? 0);
        $ign           = trim($_POST['ign'] ?? '');
        $contact       = trim($_POST['contact_number'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');

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
                    // 4. Register
                    $regStmt = $conn->prepare("INSERT INTO tournament_participants (tournament_id, user_id, ign, contact_number, notes, payment_status, registration_date) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                    $regStmt->bind_param('iisss', $tournament_id, $user['user_id'], $ign, $contact, $notes);
                    
                    if ($regStmt->execute()) {
                        $message = 'Registration successful! Please proceed to the shop to pay the entry fee.';
                        $messageType = 'success';
                    } else {
                        $message = 'Registration failed. Please try again.';
                        $messageType = 'error';
                    }
                }
            }
        }
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
                                <button class="btn-reg" onclick="openRegModal(<?= $t['tournament_id'] ?>, '<?= htmlspecialchars(addslashes($t['tournament_name'])) ?>')">
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
                        <input type="tel" name="contact_number" class="form-control" placeholder="09171234567" required pattern="[0-9]{10,11}">
                    </div>

                    <div class="form-group">
                        <label>Notes / Team Name</label>
                        <textarea name="notes" class="form-control" style="resize:none;" rows="2" placeholder="Optional..."></textarea>
                    </div>

                    <div style="background:rgba(241,168,60,0.06); border-radius:12px; padding:12px; font-size:11px; color:var(--text-dim); line-height:1.5; margin-bottom:20px;">
                        <i class="fas fa-info-circle" style="color:var(--accent); margin-right:6px;"></i>
                        By registering, you agree to show up at the hub 30 minutes before the start time. Entry fees are non-refundable.
                    </div>

                    <button type="submit" class="btn-reg" style="width:100%; justify-content:center; padding:14px;">
                        Confirm Registration
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

        function openRegModal(id, name) {
            document.getElementById('modal_t_id').value = id;
            document.getElementById('modal_t_name').textContent = name;
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
