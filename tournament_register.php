<?php
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/PayMongoService.php';

$loggedIn = isLoggedIn();
$user     = $loggedIn ? getCurrentUser() : null;
$error    = '';
$success  = '';

// ── Load active tournament ────────────────────────────────────────────────────
$activeTournament = null;
$tRes = $conn->query("
    SELECT * FROM tournaments
    WHERE status IN ('scheduled','upcoming','ongoing')
    ORDER BY CASE status WHEN 'scheduled' THEN 0 WHEN 'ongoing' THEN 1 ELSE 2 END, start_date ASC
    LIMIT 1
");
if ($tRes) $activeTournament = $tRes->fetch_assoc();
$registrationOpen = $activeTournament && $activeTournament['status'] === 'scheduled';

$slotsTaken = 0;
if ($activeTournament) {
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM tournament_participants WHERE tournament_id = ?");
    $s->bind_param('i', $activeTournament['tournament_id']);
    $s->execute();
    $slotsTaken = (int)$s->get_result()->fetch_assoc()['c'];
}
$slotsLeft  = $activeTournament ? ((int)$activeTournament['max_participants'] - $slotsTaken) : 0;
$isFull     = $activeTournament && $slotsLeft <= 0;

$alreadyRegistered = false;
if ($loggedIn && $activeTournament) {
    $ck = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
    $ck->bind_param('ii', $activeTournament['tournament_id'], $user['user_id']);
    $ck->execute();
    $alreadyRegistered = $ck->get_result()->num_rows > 0;
}

// ── PATH B: PayMongo redirect-back ────────────────────────────────────────────
if (!empty($_GET['paymongo']) && $loggedIn) {
    $pm_result = $_GET['paymongo'];
    $pending   = $_SESSION['pending_tournament_reg'] ?? null;
    $source_id = $pending['source_id'] ?? '';

    if ($pm_result === 'success' && $source_id && $pending) {
        $src = PayMongoService::getSource($source_id);

        if ($src['success'] && in_array($src['status'], ['chargeable','consumed','paid'])) {
            $charge = PayMongoService::createPayment(
                $source_id,
                PayMongoService::pesosToCentavos((float)$pending['entry_fee']),
                'Tournament entry — ' . ($pending['tournament_name'] ?? 'Good Spot Gaming Hub')
            );
            $payment_id = $charge['payment_id'] ?? null;

            // Re-check duplicate + cap before inserting
            $tid = (int)$pending['tournament_id'];
            $uid = (int)$user['user_id'];

            $dupChk = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
            $dupChk->bind_param('ii', $tid, $uid);
            $dupChk->execute();

            if ($dupChk->get_result()->num_rows > 0) {
                $error = 'You are already registered for this tournament.';
            } else {
                $capChk = $conn->prepare("SELECT COUNT(*) AS c FROM tournament_participants WHERE tournament_id = ?");
                $capChk->bind_param('i', $tid);
                $capChk->execute();
                $cap = (int)$capChk->get_result()->fetch_assoc()['c'];

                if ($cap >= (int)$pending['max_participants']) {
                    $error = 'Sorry, the tournament just filled up. Your GCash payment will need to be refunded — please contact the shop.';
                } else {
                    $ins = $conn->prepare("
                        INSERT INTO tournament_participants
                            (tournament_id, user_id, payment_status, ign, contact_number, notes,
                             registered_by, paymongo_source_id, paymongo_payment_id, paymongo_status, registration_date)
                        VALUES (?, ?, 'paid', ?, ?, ?, NULL, ?, ?, 'paid', NOW())
                    ");
                    $ins->bind_param('iisssss', $tid, $uid,
                        $pending['ign'], $pending['contact_number'], $pending['notes'],
                        $source_id, $payment_id);

                    if ($ins->execute()) {
                        unset($_SESSION['pending_tournament_reg']);
                        $success = 'Payment confirmed! Your slot for <strong>' . htmlspecialchars($pending['tournament_name']) . '</strong> is secured. See you on the battlefield!';
                        $alreadyRegistered = true;
                    } else {
                        $error = 'Payment was received but we could not save your registration. Please contact the shop with your GCash reference.';
                    }
                }
            }
        } elseif ($src['success'] && $src['status'] === 'pending') {
            $error = 'Your GCash payment is still processing. Please wait a moment and refresh this page.';
        } else {
            $error = 'Could not verify your GCash payment. Please try again or contact the shop.';
        }

    } elseif ($pm_result === 'failed') {
        $error = 'GCash payment was cancelled or failed. Please try again.';
        unset($_SESSION['pending_tournament_reg']);
    }
}

// ── PATH A: Form POST → validate → PayMongo redirect ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn && !$success) {
    $tid            = (int)($_POST['tournament_id']   ?? 0);
    $ign            = trim($_POST['ign']              ?? '');
    $contact_number = trim($_POST['contact_number']   ?? '');
    $notes          = trim($_POST['notes']            ?? '');

    if (!$tid || !$ign || !$contact_number) {
        $error = 'Please fill in all required fields.';
    } elseif (!$activeTournament || $activeTournament['tournament_id'] != $tid) {
        $error = 'Invalid tournament.';
    } elseif ($activeTournament['status'] !== 'scheduled') {
        $error = 'Registration is not open.';
    } elseif ($isFull) {
        $error = 'This tournament is full.';
    } elseif ($alreadyRegistered) {
        $error = 'You are already registered.';
    } else {
        $base        = ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST'];
        $dir         = rtrim(dirname($_SERVER['PHP_SELF']),'/');
        $success_url = $base . $dir . '/tournament_register.php?paymongo=success';
        $failed_url  = $base . $dir . '/tournament_register.php?paymongo=failed';

        $centavos = PayMongoService::pesosToCentavos((float)$activeTournament['entry_fee']);
        $pm = PayMongoService::createGCashSource(
            $centavos,
            'Tournament entry — ' . $activeTournament['tournament_name'],
            $success_url,
            $failed_url,
            $user['email']     ?? '',
            $user['full_name'] ?? 'Customer',
            $contact_number
        );

        if ($pm['success'] && !empty($pm['checkout_url'])) {
            $_SESSION['pending_tournament_reg'] = [
                'tournament_id'   => $tid,
                'tournament_name' => $activeTournament['tournament_name'],
                'entry_fee'       => $activeTournament['entry_fee'],
                'max_participants'=> $activeTournament['max_participants'],
                'ign'             => $ign,
                'contact_number'  => $contact_number,
                'notes'           => $notes,
                'source_id'       => $pm['source_id'],
                'created_at'      => time(),
            ];
            header('Location: ' . $pm['checkout_url']);
            exit;
        } else {
            $error = 'Could not connect to GCash payment gateway: ' . htmlspecialchars($pm['message'] ?? 'Unknown error') . '. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $activeTournament ? htmlspecialchars($activeTournament['tournament_name']) . ' — Register' : 'Tournament Registration' ?> – Good Spot Gaming Hub</title>
    <meta name="description" content="Register for tournaments at Good Spot Gaming Hub. Pay securely via GCash.">
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-page{min-height:100vh;background:linear-gradient(135deg,#0d1117 0%,#0a2151 100%);padding-top:100px;padding-bottom:80px;}
        .reg-hero{text-align:center;margin-bottom:3rem;}
        .reg-title{font-size:2.6rem;font-weight:900;font-family:var(--font-heading);line-height:1.2;margin-bottom:1rem;}
        .reg-subtitle{font-size:1.05rem;color:rgba(255,255,255,.7);max-width:550px;margin:0 auto;line-height:1.8;}
        .info-strip{display:flex;justify-content:center;gap:1.2rem;flex-wrap:wrap;margin-bottom:3rem;}
        .info-chip{display:flex;align-items:center;gap:.6rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:50px;padding:.55rem 1.3rem;font-size:.88rem;color:rgba(255,255,255,.85);}
        .info-chip i{color:var(--color-secondary);}
        .reg-card{background:linear-gradient(135deg,rgba(10,33,81,.85),rgba(13,17,23,.9));backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:3rem;max-width:620px;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,.4);}
        .form-lbl{color:rgba(255,255,255,.9);font-weight:600;margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem;font-size:.9rem;}
        .form-lbl i{color:var(--color-secondary);font-size:.9rem;}
        .reg-input{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);color:var(--color-light);border-radius:12px;padding:.85rem 1.2rem;width:100%;transition:all .3s;}
        .reg-input:focus{background:rgba(255,255,255,.08);border-color:var(--color-secondary);color:var(--color-light);box-shadow:0 0 0 3px rgba(241,225,170,.15);outline:none;}
        .reg-input::placeholder{color:rgba(255,255,255,.3);}
        .gcash-pay-btn{width:100%;padding:1rem 2rem;background:linear-gradient(135deg,#007aff,#00b4ff);border:none;border-radius:14px;color:#fff;font-weight:800;font-size:1.05rem;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:.75rem;letter-spacing:.3px;}
        .gcash-pay-btn:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(0,122,255,.4);}
        .gcash-pay-btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}
        .gcash-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(0,122,255,.1);border:1px solid rgba(0,122,255,.25);border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;color:#5aa8ff;margin-bottom:12px;}
        .amount-display{background:rgba(0,122,255,.08);border:1px solid rgba(0,122,255,.2);border-radius:14px;padding:1.2rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
        .amount-label{color:rgba(255,255,255,.5);font-size:.85rem;}
        .amount-value{font-size:2rem;font-weight:900;font-family:var(--font-heading);color:#5aa8ff;}
        .secure-note{font-size:.78rem;color:rgba(255,255,255,.35);text-align:center;margin-top:.75rem;display:flex;align-items:center;justify-content:center;gap:.4rem;}
        .divider{border-color:rgba(255,255,255,.08);margin:1.75rem 0;}
        .back-link{display:inline-flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.5);font-size:.9rem;text-decoration:none;margin-bottom:2rem;transition:color .3s;}
        .back-link:hover{color:var(--color-secondary);}
        .state-card{text-align:center;padding:1rem 0;}
        .state-icon{width:86px;height:86px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2.2rem;}
        .slots-bar-bg{background:rgba(255,255,255,.08);border-radius:20px;height:7px;overflow:hidden;margin-top:6px;}
        .slots-bar-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,#20c8a1,#5f85da);transition:width .5s;}
        .reg-alert{border-radius:12px;padding:1rem 1.4rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.7rem;font-weight:500;font-size:.9rem;}
        .reg-alert.success{background:rgba(32,200,161,.1);border:1px solid var(--color-mint);color:var(--color-mint);}
        .reg-alert.error{background:rgba(251,86,107,.1);border:1px solid var(--color-coral);color:var(--color-coral);}
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="register-page">
    <div class="container">
        <a href="index.php#events" class="back-link" data-aos="fade-right">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>

        <!-- Hero -->
        <div class="reg-hero" data-aos="fade-up">
            <span class="section-tag">Tournaments</span>
            <?php if ($activeTournament): ?>
            <h1 class="reg-title"><?= htmlspecialchars($activeTournament['tournament_name']) ?> <span class="gradient-text">Registration</span></h1>
            <p class="reg-subtitle"><?= $activeTournament['announcement'] ? htmlspecialchars($activeTournament['announcement']) : 'Compete for glory and prizes. Pay your entry fee securely via GCash.' ?></p>
            <?php else: ?>
            <h1 class="reg-title">Tournament <span class="gradient-text">Registration</span></h1>
            <?php endif; ?>
        </div>

        <!-- Info chips -->
        <?php if ($activeTournament): ?>
        <div class="info-strip" data-aos="fade-up" data-aos-delay="100">
            <div class="info-chip"><i class="fas fa-peso-sign"></i> ₱<?= number_format($activeTournament['entry_fee'], 0) ?> Entry</div>
            <div class="info-chip"><i class="fab fa-playstation"></i> <?= htmlspecialchars($activeTournament['console_type']) ?></div>
            <div class="info-chip"><i class="fas fa-users"></i> <?= $slotsTaken ?> / <?= $activeTournament['max_participants'] ?> Slots</div>
            <?php if ($activeTournament['prize_pool'] > 0): ?>
            <div class="info-chip"><i class="fas fa-trophy"></i> Prize: ₱<?= number_format($activeTournament['prize_pool'], 0) ?></div>
            <?php endif; ?>
            <?php if ($activeTournament['start_date']): ?>
            <div class="info-chip"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($activeTournament['start_date'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Card -->
        <div class="reg-card" data-aos="fade-up" data-aos-delay="150">

            <?php if ($success): ?>
            <!-- SUCCESS -->
            <div class="state-card">
                <div class="state-icon" style="background:rgba(32,200,161,.12);border:2px solid var(--color-mint);color:var(--color-mint);">
                    <i class="fas fa-check"></i>
                </div>
                <h3 style="font-family:var(--font-heading);font-size:1.7rem;margin-bottom:.75rem;">You're In! 🎮</h3>
                <div class="reg-alert success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                <p style="color:rgba(255,255,255,.55);line-height:1.8;font-size:.9rem;">Your GCash payment was confirmed and your slot is secured. We'll contact you at the number you provided with further details.</p>
                <hr class="divider">
                <a href="index.php#events" class="btn btn-primary mt-2"><i class="fas fa-arrow-left me-2"></i>Back to Events</a>
            </div>

            <?php elseif (!$activeTournament): ?>
            <div class="state-card">
                <div class="state-icon" style="background:rgba(241,168,60,.08);border:2px solid #f1a83c;color:#f1a83c;"><i class="fas fa-hourglass-half"></i></div>
                <h3 style="font-family:var(--font-heading);">No Open Tournament</h3>
                <p style="color:rgba(255,255,255,.55);">No tournaments are accepting registrations right now. Check back soon!</p>
                <a href="index.php#events" class="btn btn-primary mt-3"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>

            <?php elseif (!$registrationOpen): ?>
            <div class="state-card">
                <div class="state-icon" style="background:rgba(251,86,107,.08);border:2px solid #fb566b;color:#fb566b;"><i class="fas fa-lock"></i></div>
                <h3 style="font-family:var(--font-heading);">Registration Not Open</h3>
                <p style="color:rgba(255,255,255,.55);"><?= htmlspecialchars($activeTournament['tournament_name']) ?> is <strong style="color:#f1a83c;"><?= ucfirst($activeTournament['status']) ?></strong>. Registration will open soon.</p>
                <a href="index.php#events" class="btn btn-primary mt-3"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>

            <?php elseif (!$loggedIn): ?>
            <div class="state-card">
                <div class="state-icon" style="background:rgba(95,133,218,.1);border:2px solid #5f85da;color:#5f85da;"><i class="fas fa-user-lock"></i></div>
                <h3 style="font-family:var(--font-heading);">Login to Register</h3>
                <p style="color:rgba(255,255,255,.55);">You need an account to register for <strong style="color:#fff;"><?= htmlspecialchars($activeTournament['tournament_name']) ?></strong>.<br>Walk-in? Ask the shopkeeper to register you at the counter.</p>
                <hr class="divider">
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="auth/login.php?redirect=tournament_register.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Log In</a>
                    <a href="auth/register.php" class="btn btn-secondary"><i class="fas fa-user-plus me-2"></i>Create Account</a>
                </div>
            </div>

            <?php elseif ($isFull): ?>
            <div class="state-card">
                <div class="state-icon" style="background:rgba(251,86,107,.08);border:2px solid #fb566b;color:#fb566b;"><i class="fas fa-users-slash"></i></div>
                <h3 style="font-family:var(--font-heading);">Tournament is Full</h3>
                <p style="color:rgba(255,255,255,.55);">All <?= $activeTournament['max_participants'] ?> slots are taken. Follow our page for the next tournament!</p>
                <a href="index.php#events" class="btn btn-primary mt-3"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>

            <?php elseif ($alreadyRegistered && !$error): ?>
            <div class="state-card">
                <div class="state-icon" style="background:rgba(32,200,161,.1);border:2px solid var(--color-mint);color:var(--color-mint);"><i class="fas fa-check"></i></div>
                <h3 style="font-family:var(--font-heading);">You're Already Registered! 🎮</h3>
                <p style="color:rgba(255,255,255,.55);">You have already signed up for <strong style="color:#fff;"><?= htmlspecialchars($activeTournament['tournament_name']) ?></strong>. Your slot is confirmed!</p>
                <a href="index.php#events" class="btn btn-primary mt-3"><i class="fas fa-arrow-left me-2"></i>Back to Events</a>
            </div>

            <?php else: ?>
            <!-- REGISTRATION FORM -->
            <?php if ($error): ?>
            <div class="reg-alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <h4 style="font-family:var(--font-heading);font-size:1.35rem;margin-bottom:.4rem;">Player Details</h4>
            <p style="color:rgba(255,255,255,.45);font-size:.85rem;margin-bottom:1.5rem;">Fill in your info, then pay your entry fee via GCash.</p>

            <!-- Slots bar -->
            <?php $pct = min(100, round(($slotsTaken/$activeTournament['max_participants'])*100)); ?>
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:rgba(255,255,255,.4);margin-bottom:5px;">
                    <span><?= $slotsTaken ?>/<?= $activeTournament['max_participants'] ?> slots taken</span>
                    <span style="color:<?= $slotsLeft<=3?'#fb566b':'#20c8a1'; ?>;"><?= $slotsLeft ?> remaining</span>
                </div>
                <div class="slots-bar-bg"><div class="slots-bar-fill" style="width:<?= $pct ?>%;<?= $pct>=80?'background:linear-gradient(90deg,#f1a83c,#fb566b);':'' ?>"></div></div>
            </div>

            <form method="POST" id="regForm">
                <input type="hidden" name="tournament_id" value="<?= $activeTournament['tournament_id'] ?>">

                <div class="mb-4">
                    <label class="form-lbl" for="ign"><i class="fas fa-gamepad"></i> In-Game Name (IGN) *</label>
                    <input type="text" id="ign" name="ign" class="reg-input" placeholder="e.g. DarkFist99" required value="<?= htmlspecialchars($_POST['ign'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-lbl" for="contact_number"><i class="fas fa-mobile-alt"></i> Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number" class="reg-input" placeholder="e.g. 09XXXXXXXXX" required value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-lbl" for="notes"><i class="fas fa-comment"></i> Notes <span style="font-weight:400;color:rgba(255,255,255,.3);">(optional)</span></label>
                    <input type="text" id="notes" name="notes" class="reg-input" placeholder="e.g. Team name, special request…" value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>">
                </div>

                <hr class="divider">

                <!-- Payment -->
                <div class="gcash-badge"><i class="fas fa-shield-alt"></i> Secure Payment via PayMongo</div>
                <div class="amount-display">
                    <div>
                        <div class="amount-label">Entry Fee</div>
                        <div class="amount-value">₱<?= number_format($activeTournament['entry_fee'], 0) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:rgba(255,255,255,.3);margin-bottom:4px;">Payment via</div>
                        <div style="font-size:1.1rem;font-weight:800;color:#5aa8ff;"><i class="fas fa-mobile-alt me-1"></i> GCash</div>
                    </div>
                </div>

                <button type="submit" class="gcash-pay-btn" id="submitBtn">
                    <i class="fas fa-mobile-alt" style="font-size:1.2rem;"></i>
                    Pay ₱<?= number_format($activeTournament['entry_fee'], 0) ?> via GCash
                    <i class="fas fa-arrow-right" style="font-size:.85rem;opacity:.7;"></i>
                </button>
                <div class="secure-note"><i class="fas fa-lock" style="font-size:.7rem;"></i> Redirected to GCash — no card info stored here</div>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/sections/footer.php'; ?>
<a href="#" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script>
AOS.init({duration:900,once:true,offset:80});
const navbar=document.getElementById('mainNav');
window.addEventListener('scroll',()=>navbar?.classList.toggle('scrolled',window.scrollY>100));
const btt=document.getElementById('backToTop');
window.addEventListener('scroll',()=>btt?.classList.toggle('show',window.scrollY>300));

document.getElementById('regForm')?.addEventListener('submit',function(){
    const btn=document.getElementById('submitBtn');
    if(btn){btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Connecting to GCash…';btn.disabled=true;}
});
</script>
</body>
</html>
