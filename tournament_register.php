<?php
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';

// ── Run one-time schema fixes if needed ──────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `tournament_registrations` (
        `reg_id`         INT(11)      NOT NULL AUTO_INCREMENT,
        `full_name`      VARCHAR(100) NOT NULL,
        `ign`            VARCHAR(100) NOT NULL,
        `contact_number` VARCHAR(20)  NOT NULL,
        `gcash_proof`    VARCHAR(255) NOT NULL,
        `tournament`     VARCHAR(150) NOT NULL DEFAULT 'Tekken 8 Tournament',
        `status`         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `registered_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`reg_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Load active/scheduled tournament from DB ─────────────────────────────────
// Prefer the first 'scheduled' tournament; fall back to first 'upcoming'
$activeTournament = null;
$tRes = $conn->query("
    SELECT * FROM tournaments
    WHERE status IN ('scheduled','upcoming','ongoing')
    ORDER BY
        CASE status WHEN 'scheduled' THEN 0 WHEN 'ongoing' THEN 1 ELSE 2 END ASC,
        start_date ASC
    LIMIT 1
");
if ($tRes) $activeTournament = $tRes->fetch_assoc();

// Is registration currently open?
$registrationOpen = $activeTournament && $activeTournament['status'] === 'scheduled';

$success = false;
$error   = '';

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$registrationOpen) {
        $error = 'Registration is currently closed. Please check back later.';
    } else {
        $full_name      = trim($_POST['full_name']      ?? '');
        $ign            = trim($_POST['ign']            ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $tournament     = $activeTournament['tournament_name'] ?? 'Tournament';

        // Basic validation
        if (!$full_name || !$ign || !$contact_number) {
            $error = 'Please fill in all required fields.';
        } elseif (!isset($_FILES['gcash_proof']) || $_FILES['gcash_proof']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload your GCash proof of payment.';
        } else {
            $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $ftype    = mime_content_type($_FILES['gcash_proof']['tmp_name']);
            $fsize    = $_FILES['gcash_proof']['size'];

            if (!in_array($ftype, $allowed)) {
                $error = 'Invalid file type. Please upload a JPG, PNG, or WebP image.';
            } elseif ($fsize > 5 * 1024 * 1024) {
                $error = 'File is too large. Maximum size is 5 MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/gcash_proofs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $ext      = pathinfo($_FILES['gcash_proof']['name'], PATHINFO_EXTENSION);
                $filename = 'gcash_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['gcash_proof']['tmp_name'], $destPath)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO tournament_registrations (full_name, ign, contact_number, gcash_proof, tournament)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('sssss', $full_name, $ign, $contact_number, $filename, $tournament);
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $error = 'Registration failed. Please try again.';
                        @unlink($destPath);
                    }
                } else {
                    $error = 'Failed to upload file. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tekken 8 Tournament Registration – Gspot Gaming Hub</title>
    <meta name="description" content="Register for the Tekken 8 Tournament at Gspot Gaming Hub. Fill in your IGN, contact number and upload your GCash proof of payment.">

    <!-- Bootstrap CSS -->
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts (local) -->
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <!-- AOS -->
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* ── Page wrapper ──────────────────────────────── */
        .register-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d1117 0%, #0a2151 100%);
            padding-top: 100px;
            padding-bottom: 80px;
        }

        /* ── Hero banner ───────────────────────────────── */
        .reg-hero {
            text-align: center;
            margin-bottom: 3rem;
        }
        .reg-hero .section-tag { margin-bottom: 1rem; }
        .reg-hero .reg-title {
            font-size: 2.8rem;
            font-weight: 900;
            font-family: var(--font-heading);
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .reg-hero .reg-subtitle {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.7);
            max-width: 550px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* ── Info strip ────────────────────────────────── */
        .tournament-info-strip {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        .info-chip {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 0.6rem 1.4rem;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.85);
        }
        .info-chip i { color: var(--color-secondary); }

        /* ── Card wrapper ──────────────────────────────── */
        .reg-card {
            background: linear-gradient(135deg,
                rgba(10, 33, 81, 0.8),
                rgba(13, 17, 23, 0.9));
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 3rem;
            max-width: 680px;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        /* ── Form labels & inputs ──────────────────────── */
        .form-label {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-label i { color: var(--color-secondary); font-size: 0.95rem; }

        .reg-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--color-light);
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        .reg-input:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--color-secondary);
            color: var(--color-light);
            box-shadow: 0 0 0 3px rgba(241, 225, 170, 0.15);
            outline: none;
        }
        .reg-input::placeholder { color: rgba(255,255,255,0.3); }

        /* ── Upload zone ───────────────────────────────── */
        .upload-zone {
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.03);
            position: relative;
        }
        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--color-secondary);
            background: rgba(241,225,170,0.05);
        }
        .upload-zone .upload-icon {
            font-size: 2.5rem;
            color: var(--color-secondary);
            margin-bottom: 1rem;
        }
        .upload-zone p {
            color: rgba(255,255,255,0.6);
            margin: 0;
            font-size: 0.95rem;
        }
        .upload-zone span.hint {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.35);
        }
        #gcash_proof { 
            position: absolute; 
            inset: 0; 
            opacity: 0; 
            cursor: pointer; 
            width: 100%;
            height: 100%;
        }
        #preview-wrap {
            display: none;
            margin-top: 1rem;
            position: relative;
        }
        #preview-wrap img {
            max-height: 220px;
            border-radius: 12px;
            border: 2px solid var(--color-secondary);
            object-fit: contain;
            max-width: 100%;
        }
        #remove-preview {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--color-coral);
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            color: white;
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Submit button ─────────────────────────────── */
        .btn-register {
            background: linear-gradient(135deg, var(--color-secondary), #c9a227);
            color: #0a2151;
            font-weight: 700;
            font-size: 1.05rem;
            padding: 0.9rem 2.5rem;
            border-radius: 50px;
            border: none;
            width: 100%;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(241,225,170,0.35);
            color: #0a2151;
        }
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Alert boxes ───────────────────────────────── */
        .reg-alert {
            border-radius: 12px;
            padding: 1.1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .reg-alert.success {
            background: rgba(32,200,161,0.12);
            border: 1px solid var(--color-mint);
            color: var(--color-mint);
        }
        .reg-alert.error {
            background: rgba(251,86,107,0.12);
            border: 1px solid var(--color-coral);
            color: var(--color-coral);
        }

        /* ── Divider ───────────────────────────────────── */
        .form-divider {
            border-color: rgba(255,255,255,0.08);
            margin: 2rem 0;
        }

        /* ── Back link ─────────────────────────────────── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            margin-bottom: 2rem;
        }
        .back-link:hover { color: var(--color-secondary); }

        /* ── Success state ─────────────────────────────── */
        .success-card {
            text-align: center;
            padding: 1rem 0;
        }
        .success-icon-wrap {
            width: 90px;
            height: 90px;
            background: rgba(32,200,161,0.1);
            border: 2px solid var(--color-mint);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.2rem;
            color: var(--color-mint);
        }
        .success-card h3 {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
        }
        .success-card p {
            color: rgba(255,255,255,0.65);
            margin-bottom: 0;
            line-height: 1.8;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="register-page">
    <div class="container">

        <!-- Back -->
        <a href="index.php#events" class="back-link" data-aos="fade-right">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>

        <!-- Hero -->
        <div class="reg-hero" data-aos="fade-up">
            <span class="section-tag">Tournaments</span>
            <?php if ($activeTournament): ?>
            <h1 class="reg-title">
                <?= htmlspecialchars($activeTournament['game_name'] ?? $activeTournament['tournament_name']) ?>
                <span class="gradient-text">Tournament</span>
            </h1>
            <p class="reg-subtitle">
                <?php if ($activeTournament['announcement']): ?>
                    <?= htmlspecialchars($activeTournament['announcement']) ?>
                <?php else: ?>
                    Show off your skills and compete for glory!
                    Register below and submit your GCash proof of payment to confirm your slot.
                <?php endif; ?>
            </p>
            <?php else: ?>
            <h1 class="reg-title">Tournament <span class="gradient-text">Registration</span></h1>
            <p class="reg-subtitle">No upcoming tournament at the moment. Check back soon!</p>
            <?php endif; ?>
        </div>

        <!-- Info chips -->
        <?php if ($activeTournament): ?>
        <div class="tournament-info-strip" data-aos="fade-up" data-aos-delay="100">
            <div class="info-chip">
                <i class="fas fa-peso-sign"></i>
                <span>₱<?= number_format($activeTournament['entry_fee'], 0) ?> Registration Fee</span>
            </div>
            <div class="info-chip">
                <i class="fab fa-playstation"></i>
                <span>Platform: <?= htmlspecialchars($activeTournament['console_type']) ?></span>
            </div>
            <div class="info-chip">
                <i class="fas fa-users"></i>
                <span>Max <?= $activeTournament['max_participants'] ?> Players</span>
            </div>
            <?php if ($activeTournament['prize_pool'] > 0): ?>
            <div class="info-chip">
                <i class="fas fa-trophy"></i>
                <span>Prize Pool: ₱<?= number_format($activeTournament['prize_pool'], 0) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-chip">
                <i class="fas fa-calendar"></i>
                <span><?= date('M d, Y', strtotime($activeTournament['start_date'])) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form card -->
        <div class="reg-card" data-aos="fade-up" data-aos-delay="150">

            <?php if ($success): ?>
            <!-- ── SUCCESS STATE ── -->
            <div class="success-card">
                <div class="success-icon-wrap">
                    <i class="fas fa-check"></i>
                </div>
                <h3>Registration Submitted! 🎮</h3>
                <p>
                    Your registration for the <strong><?= htmlspecialchars($activeTournament['tournament_name'] ?? 'Tournament') ?></strong> has been received.<br>
                    We'll review your GCash proof and confirm your slot soon.<br><br>
                    See you on the battlefield!
                </p>
                <hr class="form-divider">
                <a href="index.php#events" class="btn btn-primary mt-2 me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Events
                </a>
                <a href="tournament_register.php" class="btn btn-secondary mt-2">
                    Register Another
                </a>
            </div>

            <?php elseif (!$activeTournament): ?>
            <!-- ── NO TOURNAMENT STATE ── -->
            <div class="success-card">
                <div class="success-icon-wrap" style="border-color:#f1a83c;color:#f1a83c;background:rgba(241,168,60,.1);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <h3>No Tournament Open</h3>
                <p style="color:rgba(255,255,255,.6);">There are no tournaments with open registration right now.<br>Check back soon or follow our Facebook page for announcements!</p>
                <hr class="form-divider">
                <a href="index.php#events" class="btn btn-primary mt-2"><i class="fas fa-arrow-left me-2"></i>Back to Events</a>
            </div>

            <?php elseif (!$registrationOpen): ?>
            <!-- ── REGISTRATION CLOSED STATE ── -->
            <div class="success-card">
                <div class="success-icon-wrap" style="border-color:#fb566b;color:#fb566b;background:rgba(251,86,107,.1);">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Registration Not Yet Open</h3>
                <p style="color:rgba(255,255,255,.6);">
                    The <strong style="color:#fff;"><?= htmlspecialchars($activeTournament['tournament_name']) ?></strong> tournament
                    is currently <strong style="color:#f1a83c;">upcoming</strong>.<br>
                    Registration will open soon. Stay tuned!
                </p>
                <?php if ($activeTournament['start_date']): ?>
                <div style="margin:16px 0;background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.2);border-radius:10px;padding:12px 18px;display:inline-block;">
                    <i class="fas fa-calendar" style="color:#f1a83c;margin-right:8px;"></i>
                    <strong style="color:#f1e1aa;">Tournament Date:</strong>
                    <span style="color:rgba(255,255,255,.8);margin-left:6px;"><?= date('F d, Y \a\t h:i A', strtotime($activeTournament['start_date'])) ?></span>
                </div>
                <?php endif; ?>
                <hr class="form-divider">
                <a href="index.php#events" class="btn btn-primary mt-2"><i class="fas fa-arrow-left me-2"></i>Back to Events</a>
            </div>

            <?php else: ?>
            <!-- ── FORM STATE ── -->
            <h4 class="mb-1" style="font-family:var(--font-heading);font-size:1.4rem;">Player Registration</h4>
            <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;margin-bottom:2rem;">
                All fields are required. Make sure your GCash payment of <strong style="color:var(--color-secondary);">₱<?= number_format($activeTournament['entry_fee'], 0) ?></strong> is sent before submitting.
            </p>

            <?php if ($error): ?>
            <div class="reg-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="regForm" novalidate>

                <!-- Full Name -->
                <div class="mb-4">
                    <label class="form-label" for="full_name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="reg-input form-control"
                        placeholder="e.g. Juan Dela Cruz"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- IGN -->
                <div class="mb-4">
                    <label class="form-label" for="ign">
                        <i class="fas fa-gamepad"></i> In-Game Name (IGN)
                    </label>
                    <input
                        type="text"
                        id="ign"
                        name="ign"
                        class="reg-input form-control"
                        placeholder="e.g. DarkFist99"
                        value="<?= htmlspecialchars($_POST['ign'] ?? '') ?>"
                        required
                    >
                    <div class="form-text" style="color:rgba(255,255,255,0.35);font-size:0.8rem;margin-top:0.4rem;">
                        Your Tekken 8 username or preferred alias
                    </div>
                </div>

                <!-- Contact Number -->
                <div class="mb-4">
                    <label class="form-label" for="contact_number">
                        <i class="fas fa-mobile-alt"></i> Contact Number
                    </label>
                    <input
                        type="tel"
                        id="contact_number"
                        name="contact_number"
                        class="reg-input form-control"
                        placeholder="e.g. 09XXXXXXXXX"
                        value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>"
                        required
                    >
                </div>

                <hr class="form-divider">

                <!-- GCash Proof Upload -->
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-receipt"></i> GCash Proof of Payment
                    </label>
                    <p style="color:rgba(255,255,255,0.4);font-size:0.82rem;margin-bottom:0.75rem;">
                        Send <strong style="color:var(--color-secondary);">₱250</strong> via GCash then upload your screenshot below.
                    </p>

                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="gcash_proof" id="gcash_proof" accept="image/*" required>
                        <div id="upload-placeholder">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p><strong>Click or drag & drop</strong> your screenshot here</p>
                            <span class="hint">JPG, PNG or WebP · Max 5 MB</span>
                        </div>
                        <div id="preview-wrap">
                            <img id="preview-img" src="" alt="GCash proof preview">
                            <button type="button" id="remove-preview" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="mb-4 p-3" style="background:rgba(241,225,170,0.06);border:1px solid rgba(241,225,170,0.15);border-radius:12px;font-size:0.85rem;color:rgba(255,255,255,0.55);line-height:1.7;">
                    <i class="fas fa-info-circle" style="color:var(--color-secondary);margin-right:0.5rem;"></i>
                    Your slot is confirmed only after our team verifies your payment. We'll contact you via the number provided.
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-register" id="submitBtn">
                    <i class="fas fa-trophy me-2"></i> Submit Registration
                </button>

            </form>
            <?php endif; ?>

        </div><!-- /.reg-card -->
    </div><!-- /.container -->
</div><!-- /.register-page -->

<?php include __DIR__ . '/sections/footer.php'; ?>

<!-- Back to Top -->
<a href="#" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

<!-- Bootstrap JS -->
<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>

<script>
AOS.init({ duration: 900, once: true, offset: 80 });

// Navbar scroll effect
const navbar = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 100);
});

// Back to top
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});

// ── Upload zone ──────────────────────────────────────────
const fileInput    = document.getElementById('gcash_proof');
const uploadZone   = document.getElementById('uploadZone');
const placeholder  = document.getElementById('upload-placeholder');
const previewWrap  = document.getElementById('preview-wrap');
const previewImg   = document.getElementById('preview-img');
const removeBtn    = document.getElementById('remove-preview');

function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        placeholder.style.display = 'none';
        previewWrap.style.display  = 'block';
    };
    reader.readAsDataURL(file);
}

function clearPreview() {
    fileInput.value = '';
    previewImg.src  = '';
    previewWrap.style.display  = 'none';
    placeholder.style.display  = '';
}

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) showPreview(fileInput.files[0]);
});

removeBtn.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();
    clearPreview();
});

// Drag & drop
uploadZone.addEventListener('dragover', e => {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
});
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showPreview(file);
    }
});

// Submit button loading state
document.getElementById('regForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
    btn.disabled = true;
});
</script>
</body>
</html>
