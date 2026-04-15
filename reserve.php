<?php
/**
 * Good Spot Gaming Hub — Reserve a Console
 * Customer-facing reservation page. Login required.
 */
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';

requireLogin();

$user    = getCurrentUser();
$success = '';
$error   = '';

// Fetch all non-maintenance consoles for display
$allConsoles = getConsoles();
$ps5Count    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS5'));
$ps4Count    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS4'));
$xboxCount   = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'Xbox Series X'));

$unlimitedRate = getSetting('unlimited_rate') ?? 300;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $console_type       = $_POST['console_type']  ?? '';
    $rental_mode        = $_POST['rental_mode']   ?? '';
    $planned_minutes    = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;
    $reserved_date      = $_POST['reserved_date'] ?? '';
    $reserved_time      = $_POST['reserved_time'] ?? '';
    $notes              = trim($_POST['notes']     ?? '');
    $dp_amount          = (float)($_POST['downpayment_amount']  ?? 0);
    $dp_method          = !empty($_POST['downpayment_method']) && $dp_amount > 0
                          ? $_POST['downpayment_method'] : null;

    // Validation
    if (!in_array($console_type, ['PS5', 'PS4', 'Xbox Series X'])) {
        $error = 'Please select a valid console type.';
    } elseif (!in_array($rental_mode, ['hourly', 'open_time', 'unlimited'])) {
        $error = 'Please select a valid rental mode.';
    } elseif ($rental_mode === 'hourly' && $planned_minutes < 30) {
        $error = 'Please select a duration for hourly mode.';
    } elseif (!$reserved_date || !$reserved_time) {
        $error = 'Please provide both date and time.';
    } elseif ($reserved_date < date('Y-m-d')) {
        $error = 'Reservation date cannot be in the past.';
    } elseif ($dp_amount > 0 && !$dp_method) {
        $error = 'Please select a payment method for your downpayment.';
    } else {
        $result = createReservation(
            $user['user_id'], $console_type, $rental_mode, $planned_minutes,
            $reserved_date, $reserved_time,
            $notes ?: null,
            $dp_amount, $dp_method
        );

        if ($result['success']) {
            $success = 'Your reservation #' . $result['reservation_id'] . ' has been submitted! ' .
                       'A staff member will confirm it shortly.';
        } else {
            $error = 'Could not save reservation: ' . htmlspecialchars($result['message']);
        }
    }
}

// My reservations
$myReservations = getMyReservations($user['user_id']);
$todayStr = date('Y-m-d');
$minDateTime = date('Y-m-d\TH:i', strtotime('+30 minutes'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve a Console — Good Spot Gaming Hub</title>
    <meta name="description" content="Book your PS5, PS4 or Xbox Series X session in advance at Good Spot Gaming Hub, Dasmariñas.">

    <!-- Local libs (per project rules — no CDN) -->
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
    /* ── Reserve page overrides ─────────────────────────────────── */
    .reserve-hero {
        background: linear-gradient(135deg, #0a0f1c 0%, #0d1b3e 50%, #0a1225 100%);
        padding: 120px 0 60px;
        position: relative;
        overflow: hidden;
    }
    .reserve-hero::before {
        content:'';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse 60% 50% at 70% 40%, rgba(32,200,161,.1), transparent),
                    radial-gradient(ellipse 40% 60% at 20% 70%, rgba(95,133,218,.08), transparent);
        pointer-events: none;
    }
    .reserve-hero h1 { font-family: 'Outfit', sans-serif; font-weight: 900; color: #fff; }
    .reserve-hero p  { color: rgba(255,255,255,.65); }

    /* Form card */
    .reserve-card {
        background: rgba(10,20,50,.7);
        border: 1px solid rgba(95,133,218,.2);
        border-radius: 20px;
        padding: 36px;
        backdrop-filter: blur(12px);
    }
    .reserve-card h2 {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 1.4rem;
        color: #fff;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .reserve-card h2 i { color: #20c8a1; }

    /* Console type selector */
    .console-type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
    .console-type-card {
        border: 2px solid rgba(255,255,255,.1);
        border-radius: 14px;
        padding: 20px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: rgba(255,255,255,.03);
        user-select: none;
    }
    .console-type-card:hover { border-color: rgba(32,200,161,.5); background: rgba(32,200,161,.05); }
    .console-type-card.selected { border-color: #20c8a1; background: rgba(32,200,161,.1); }
    .console-type-card .ct-icon { font-size: 2.4rem; margin-bottom: 8px; }
    .console-type-card .ct-name { font-weight: 700; font-size: 1rem; color: #fff; }
    .console-type-card .ct-count { font-size: 12px; color: #888; margin-top: 4px; }
    .console-type-card .ct-avail { font-size: 11px; margin-top: 4px; }

    /* Mode selector */
    .mode-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .mode-card {
        border: 2px solid rgba(255,255,255,.1);
        border-radius: 12px;
        padding: 14px 10px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: rgba(255,255,255,.03);
    }
    .mode-card:hover { border-color: rgba(179,123,236,.5); }
    .mode-card.selected { border-color: #b37bec; background: rgba(179,123,236,.1); }
    .mode-card .mc-icon { font-size: 1.4rem; margin-bottom: 6px; }
    .mode-card .mc-name { font-weight: 700; font-size: 13px; color: #fff; }
    .mode-card .mc-desc { font-size: 11px; color: #888; margin-top: 4px; line-height: 1.4; }

    /* Duration grid */
    .duration-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 20px;
    }
    .dur-btn {
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 10px;
        padding: 10px 6px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: rgba(255,255,255,.04);
        color: #ccc;
        font-size: 12px;
        font-weight: 600;
    }
    .dur-btn:hover { border-color: #f1a83c; color: #f1a83c; }
    .dur-btn.selected { border-color: #f1a83c; background: rgba(241,168,60,.12); color: #f1a83c; }
    .dur-btn .dur-price { display: block; font-size: 13px; font-weight: 800; color: #f1e1aa; margin-top: 3px; }

    /* Section label */
    .sec-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: #888;
        margin-bottom: 10px;
    }

    /* Downpayment box */
    .dp-box {
        background: rgba(32,200,161,.06);
        border: 1px solid rgba(32,200,161,.2);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .dp-box .dp-title {
        font-weight: 700;
        color: #20c8a1;
        font-size: 14px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dp-method-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 14px; }
    .pm-card {
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 10px;
        padding: 10px 8px;
        text-align: center;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: #aaa;
        transition: all .2s;
    }
    .pm-card:hover { border-color: #20c8a1; }
    .pm-card.selected { border-color: #20c8a1; background: rgba(32,200,161,.1); color: #20c8a1; }
    .pm-card .pm-icon { display: block; font-size: 1.2rem; margin-bottom: 4px; }

    /* Reserve form inputs */
    .res-input {
        width: 100%;
        background: rgba(10,33,81,.6);
        border: 1px solid rgba(95,133,218,.25);
        color: #f0f0f0;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        box-sizing: border-box;
        transition: border-color .2s, box-shadow .2s;
        margin-bottom: 16px;
    }
    .res-input:focus { border-color: #20c8a1; box-shadow: 0 0 0 3px rgba(32,200,161,.1); }
    textarea.res-input { min-height: 80px; resize: vertical; }

    /* Summary preview */
    .reserve-summary {
        background: linear-gradient(135deg, rgba(32,200,161,.08), rgba(95,133,218,.06));
        border: 1px solid rgba(32,200,161,.25);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .rs-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,.06); font-size: 13px; }
    .rs-row:last-child { border-bottom: none; }
    .rs-label { color: #888; }
    .rs-value { font-weight: 700; color: #f0f0f0; }

    /* My Reservations table */
    .my-res-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .my-res-table th { color: #888; font-weight: 600; text-align: left; padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,.08); }
    .my-res-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,.05); color: #d0d0d0; vertical-align: middle; }
    .my-res-table tr:last-child td { border-bottom: none; }
    .res-badge {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    }
    .res-badge.pending   { background:rgba(241,168,60,.15);  color:#f1a83c;  border:1px solid rgba(241,168,60,.3); }
    .res-badge.confirmed { background:rgba(32,200,161,.15);  color:#20c8a1;  border:1px solid rgba(32,200,161,.3); }
    .res-badge.converted { background:rgba(95,133,218,.15);  color:#8aa4e8;  border:1px solid rgba(95,133,218,.3); }
    .res-badge.cancelled { background:rgba(251,86,107,.12);  color:#fb566b;  border:1px solid rgba(251,86,107,.3); }
    .res-badge.no_show   { background:rgba(100,100,100,.15); color:#888;     border:1px solid rgba(100,100,100,.3); }

    .res-submit-btn {
        width: 100%; padding: 16px;
        background: linear-gradient(135deg, #20c8a1, #17a887);
        border: none; border-radius: 12px;
        color: #0a0f1c; font-weight: 800; font-size: 16px;
        cursor: pointer; transition: all .2s;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .res-submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(32,200,161,.35); }

    .avail-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    }
    .avail-badge.ok   { background: rgba(32,200,161,.15); color: #20c8a1; }
    .avail-badge.none { background: rgba(251,86,107,.15); color: #fb566b; }

    @media (max-width: 768px) {
        .console-type-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .console-type-grid, .mode-grid, .duration-grid, .dp-method-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero ─────────────────────────────────────────────────────────── -->
<section class="reserve-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <p style="color:#20c8a1;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">
                    <i class="fas fa-calendar-check"></i> Advance Booking
                </p>
                <h1 style="font-size: clamp(2rem,5vw,3.2rem);margin-bottom:16px;">
                    Reserve Your<br><span style="color:#20c8a1;">Gaming Session</span>
                </h1>
                <p style="font-size:1.05rem;max-width:480px;">
                    Secure your PS5, PS4 or Xbox Series X slot in advance. Pick your date, time, and rental mode — and optionally pay a downpayment to lock it in.
                </p>
                <div style="display:flex;gap:20px;margin-top:28px;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:900;color:#20c8a1;"><?= $ps5Count ?></div>
                        <div style="font-size:12px;color:#888;">PS5 Units</div>
                    </div>
                    <?php if ($ps4Count > 0): ?>
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:900;color:#f1a83c;"><?= $ps4Count ?></div>
                        <div style="font-size:12px;color:#888;">PS4 Units</div>
                    </div>
                    <?php endif; ?>
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:900;color:#5f85da;"><?= $xboxCount ?></div>
                        <div style="font-size:12px;color:#888;">Xbox Units</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:900;color:#b37bec;">∞</div>
                        <div style="font-size:12px;color:#888;">Future Dates</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex justify-content-end" data-aos="fade-left">
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                    <div style="background:rgba(10,33,81,.6);border:1px solid rgba(95,133,218,.25);border-radius:16px;padding:20px;text-align:center;width:120px;">
                        <i class="fab fa-playstation" style="font-size:2.5rem;color:#5f85da;"></i>
                        <div style="color:#fff;font-weight:700;margin-top:8px;">PS5</div>
                        <div style="color:#888;font-size:11px;">₱80/hr</div>
                    </div>
                    <?php if ($ps4Count > 0): ?>
                    <div style="background:rgba(10,33,81,.6);border:1px solid rgba(241,168,60,.25);border-radius:16px;padding:20px;text-align:center;width:120px;">
                        <i class="fab fa-playstation" style="font-size:2.5rem;color:#f1a83c;"></i>
                        <div style="color:#fff;font-weight:700;margin-top:8px;">PS4</div>
                        <div style="color:#888;font-size:11px;">₱80/hr</div>
                    </div>
                    <?php endif; ?>
                    <div style="background:rgba(10,33,81,.6);border:1px solid rgba(32,200,161,.25);border-radius:16px;padding:20px;text-align:center;width:120px;">
                        <i class="fab fa-xbox" style="font-size:2.5rem;color:#20c8a1;"></i>
                        <div style="color:#fff;font-weight:700;margin-top:8px;">Xbox</div>
                        <div style="color:#888;font-size:11px;">₱80/hr</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Main Content ──────────────────────────────────────────────────── -->
<section style="padding: 60px 0 80px; background: #07101f;">
    <div class="container">

        <?php if ($success): ?>
        <div style="background:rgba(32,200,161,.12);border:1px solid rgba(32,200,161,.4);border-radius:14px;padding:20px 24px;margin-bottom:30px;display:flex;gap:14px;align-items:flex-start;" data-aos="fade-down">
            <i class="fas fa-check-circle" style="color:#20c8a1;font-size:1.5rem;margin-top:2px;flex-shrink:0;"></i>
            <div>
                <div style="font-weight:700;color:#20c8a1;font-size:15px;margin-bottom:4px;">Reservation Submitted!</div>
                <div style="color:#bbb;font-size:14px;"><?= htmlspecialchars($success) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div style="background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.4);border-radius:14px;padding:20px 24px;margin-bottom:30px;display:flex;gap:14px;align-items:flex-start;" data-aos="fade-down">
            <i class="fas fa-exclamation-circle" style="color:#fb566b;font-size:1.5rem;margin-top:2px;flex-shrink:0;"></i>
            <div>
                <div style="font-weight:700;color:#fb566b;font-size:15px;margin-bottom:4px;">Oops!</div>
                <div style="color:#bbb;font-size:14px;"><?= htmlspecialchars($error) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-5">

            <!-- ── LEFT: Reservation Form ─────────────────────────── -->
            <div class="col-lg-7" data-aos="fade-up">
                <form method="POST" id="reserveForm">
                    <input type="hidden" name="console_type"    id="hiddenConsoleType">
                    <input type="hidden" name="rental_mode"     id="hiddenRentalMode">
                    <input type="hidden" name="planned_minutes" id="hiddenPlannedMinutes">
                    <input type="hidden" name="downpayment_method" id="hiddenDpMethod">

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-desktop"></i> Step 1 — Choose Console Type</h2>
                        <div class="console-type-grid">
                            <div class="console-type-card" id="ct-ps5" onclick="selectConsoleType('PS5')">
                                <div class="ct-icon"><i class="fab fa-playstation" style="color:#5f85da;"></i></div>
                                <div class="ct-name">PlayStation 5</div>
                                <div class="ct-count"><?= $ps5Count ?> units</div>
                                <div class="ct-avail" id="avail-ps5"></div>
                            </div>
                            <?php if ($ps4Count > 0): ?>
                            <div class="console-type-card" id="ct-ps4" onclick="selectConsoleType('PS4')">
                                <div class="ct-icon"><i class="fab fa-playstation" style="color:#f1a83c;"></i></div>
                                <div class="ct-name">PlayStation 4</div>
                                <div class="ct-count"><?= $ps4Count ?> units</div>
                                <div class="ct-avail" id="avail-ps4"></div>
                            </div>
                            <?php endif; ?>
                            <div class="console-type-card" id="ct-xbox" onclick="selectConsoleType('Xbox Series X')">
                                <div class="ct-icon"><i class="fab fa-xbox" style="color:#20c8a1;"></i></div>
                                <div class="ct-name">Xbox Series X</div>
                                <div class="ct-count"><?= $xboxCount ?> units</div>
                                <div class="ct-avail" id="avail-xbox"></div>
                            </div>
                        </div>
                    </div>

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-calendar-alt"></i> Step 2 — Pick Date & Time</h2>
                        <div class="row gx-3">
                            <div class="col-6">
                                <div class="sec-label">Date *</div>
                                <input type="date" id="reservedDate" name="reserved_date"
                                       class="res-input" min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($_POST['reserved_date'] ?? '') ?>"
                                       onchange="onDateTimeChange()" required>
                            </div>
                            <div class="col-6">
                                <div class="sec-label">Time *</div>
                                <input type="time" id="reservedTime" name="reserved_time"
                                       class="res-input"
                                       value="<?= htmlspecialchars($_POST['reserved_time'] ?? '') ?>"
                                       onchange="onDateTimeChange()" required>
                            </div>
                        </div>
                        <div id="availabilityResult" style="display:none;margin-top:-8px;margin-bottom:12px;"></div>
                    </div>

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-gamepad"></i> Step 3 — Rental Mode</h2>
                        <div class="mode-grid">
                            <div class="mode-card" id="mode-hourly" onclick="selectMode('hourly')">
                                <div class="mc-icon">⏱️</div>
                                <div class="mc-name">Hourly</div>
                                <div class="mc-desc">Pre-book a fixed duration. Pay at session start.</div>
                            </div>
                            <div class="mode-card" id="mode-open_time" onclick="selectMode('open_time')">
                                <div class="mc-icon">🔓</div>
                                <div class="mc-name">Open Time</div>
                                <div class="mc-desc">Play freely. Pay bracket pricing at end.</div>
                            </div>
                            <div class="mode-card" id="mode-unlimited" onclick="selectMode('unlimited')">
                                <div class="mc-icon">♾️</div>
                                <div class="mc-name">Unlimited</div>
                                <div class="mc-desc">Flat ₱<?= $unlimitedRate ?> for unlimited play.</div>
                            </div>
                        </div>

                        <!-- Duration picker (hourly only) -->
                        <div id="durationSection" style="display:none;">
                            <div class="sec-label">Duration *</div>
                            <div class="duration-grid">
                                <?php
                                $durations = [
                                    [30,'30 min','₱50'],
                                    [60,'1 hr','₱80'],
                                    [90,'1h 30m','₱120'],
                                    [120,'2 hrs','₱160'],
                                    [150,'2h 30m','₱200'],
                                    [180,'3 hrs','₱240'],
                                    [240,'4 hrs','₱320'],
                                    [300,'5 hrs','₱400'],
                                    [360,'6 hrs','₱480'],
                                    [420,'7 hrs','₱560'],
                                    [480,'8 hrs','₱640'],
                                ];
                                foreach ($durations as [$mins, $label, $price]): ?>
                                <div class="dur-btn" data-mins="<?= $mins ?>" onclick="selectDuration(<?= $mins ?>)">
                                    <?= $label ?>
                                    <span class="dur-price"><?= $price ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-peso-sign"></i> Step 4 — Downpayment (Optional)</h2>
                        <p style="color:#888;font-size:13px;margin-bottom:18px;">
                            Paying a downpayment helps confirm your slot. The remaining balance is collected in-store.
                        </p>
                        <div class="dp-box">
                            <div class="dp-title"><i class="fas fa-coins"></i> Downpayment Amount</div>
                            <input type="number" name="downpayment_amount" id="dpAmount" class="res-input"
                                   min="0" step="10" placeholder="₱0 — leave blank to skip"
                                   value="<?= htmlspecialchars($_POST['downpayment_amount'] ?? '') ?>"
                                   oninput="onDpAmountChange()">

                            <div id="dpMethodSection" style="display:none;">
                                <div class="sec-label">Payment Method</div>
                                <div class="dp-method-grid">
                                    <div class="pm-card" id="pm-cash" onclick="selectDpMethod('cash')">
                                        <span class="pm-icon">💵</span>Cash
                                    </div>
                                    <div class="pm-card" id="pm-gcash" onclick="selectDpMethod('gcash')">
                                        <span class="pm-icon">📱</span>GCash
                                    </div>
                                    <div class="pm-card" id="pm-credit_card" onclick="selectDpMethod('credit_card')">
                                        <span class="pm-icon">💳</span>Credit Card
                                    </div>
                                </div>
                                <p style="font-size:11px;color:#888;margin-bottom:0;">
                                    <i class="fas fa-info-circle"></i>
                                    Downpayment will be recorded and deducted from your total at session end.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-sticky-note"></i> Step 5 — Notes (Optional)</h2>
                        <textarea name="notes" class="res-input" placeholder="Any special requests? (e.g. preferred controller, specific game ready, group size...)"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Summary preview -->
                    <div class="reserve-summary" id="summaryBox" style="display:none;">
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#20c8a1;margin-bottom:14px;">
                            <i class="fas fa-receipt"></i> Reservation Summary
                        </div>
                        <div class="rs-row"><span class="rs-label">Console</span><span class="rs-value" id="s-console">—</span></div>
                        <div class="rs-row"><span class="rs-label">Date & Time</span><span class="rs-value" id="s-datetime">—</span></div>
                        <div class="rs-row"><span class="rs-label">Mode</span><span class="rs-value" id="s-mode">—</span></div>
                        <div class="rs-row"><span class="rs-label">Duration</span><span class="rs-value" id="s-duration">—</span></div>
                        <div class="rs-row"><span class="rs-label">Downpayment</span><span class="rs-value" id="s-dp">None</span></div>
                    </div>

                    <button type="submit" class="res-submit-btn" id="submitBtn">
                        <i class="fas fa-calendar-check"></i> Submit Reservation
                    </button>
                </form>
            </div>

            <!-- ── RIGHT: Info + My Reservations ─────────────────── -->
            <div class="col-lg-5" data-aos="fade-up" data-aos-delay="150">

                <!-- How it works -->
                <div class="reserve-card" style="margin-bottom:24px;">
                    <h2><i class="fas fa-info-circle"></i> How It Works</h2>
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <?php
                        $steps = [
                            ['icon'=>'fas fa-calendar-plus','color'=>'#20c8a1','title'=>'1. Submit Request','desc'=>'Fill out the form and pick your preferred console, date, time, and mode.'],
                            ['icon'=>'fas fa-bell','color'=>'#f1a83c','title'=>'2. Staff Confirms','desc'=>'Our team reviews your booking and assigns a specific unit to you.'],
                            ['icon'=>'fas fa-play-circle','color'=>'#5f85da','title'=>'3. Show Up & Play','desc'=>'Arrive at your reserved time and the session starts right away.'],
                        ];
                        foreach ($steps as $s): ?>
                        <div style="display:flex;gap:14px;align-items:flex-start;">
                            <div style="width:38px;height:38px;border-radius:10px;background:<?= $s['color'] ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="<?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;color:#fff;font-size:14px;margin-bottom:4px;"><?= $s['title'] ?></div>
                                <div style="font-size:12px;color:#888;line-height:1.5;"><?= $s['desc'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pricing reminder -->
                <div class="reserve-card" style="margin-bottom:24px;">
                    <h2><i class="fas fa-tags"></i> Pricing</h2>
                    <div style="font-size:13px;color:#aaa;line-height:1.8;">
                        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.06);">
                            <span>30 minutes (Hourly)</span><span style="color:#f1e1aa;font-weight:700;">₱50</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.06);">
                            <span>Per hour (Hourly)</span><span style="color:#f1e1aa;font-weight:700;">₱80</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.06);">
                            <span>Open Time (bracket)</span><span style="color:#f1e1aa;font-weight:700;">₱80/hr</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:6px 0;">
                            <span>Unlimited (flat)</span><span style="color:#f1e1aa;font-weight:700;">₱<?= $unlimitedRate ?></span>
                        </div>
                    </div>
                    <div style="margin-top:12px;font-size:11px;color:#555;background:rgba(32,200,161,.05);border-radius:8px;padding:10px;">
                        <i class="fas fa-gift" style="color:#20c8a1;"></i>
                        Free 30 mins every 2 paid hours on Open Time sessions!
                    </div>
                </div>

                <!-- My Reservations -->
                <div class="reserve-card">
                    <h2><i class="fas fa-list-check"></i> My Reservations</h2>
                    <?php if (empty($myReservations)): ?>
                    <div style="text-align:center;padding:30px;color:#555;">
                        <i class="fas fa-calendar-xmark" style="font-size:2rem;margin-bottom:12px;display:block;"></i>
                        No reservations yet
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="my-res-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Console</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($myReservations as $r): ?>
                            <tr>
                                <td style="white-space:nowrap;">
                                    <?= date('M d, Y', strtotime($r['reserved_date'])) ?><br>
                                    <span style="color:#888;font-size:11px;"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($r['console_type']) ?>
                                    <?php if ($r['unit_number']): ?>
                                    <br><span style="color:#888;font-size:11px;"><?= htmlspecialchars($r['unit_number']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= match($r['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($r['rental_mode']) } ?></td>
                                <td><span class="res-badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/sections/footer.php'; ?>

<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script>
AOS.init({ duration: 600, once: true });

/* ── State ──────────────────────────────────────────── */
let selectedConsoleType = '';
let selectedMode        = '';
let selectedDuration    = 0;
let selectedDpMethod    = '';

const unlimitedRate = <?= (int)$unlimitedRate ?>;

/* ── Console type ───────────────────────────────────── */
const CONSOLE_TYPE_IDS = { 'PS5': 'ct-ps5', 'PS4': 'ct-ps4', 'Xbox Series X': 'ct-xbox' };
function selectConsoleType(type) {
    selectedConsoleType = type;
    document.getElementById('hiddenConsoleType').value = type;
    document.querySelectorAll('.console-type-card').forEach(c => c.classList.remove('selected'));
    const el = document.getElementById(CONSOLE_TYPE_IDS[type]);
    if (el) el.classList.add('selected');
    updateSummary();
    checkAvailability();
}

/* ── Rental mode ────────────────────────────────────── */
function selectMode(mode) {
    selectedMode = mode;
    document.getElementById('hiddenRentalMode').value = mode;
    document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('mode-' + mode).classList.add('selected');
    document.getElementById('durationSection').style.display = (mode === 'hourly') ? 'block' : 'none';

    if (mode !== 'hourly') {
        selectedDuration = 0;
        document.getElementById('hiddenPlannedMinutes').value = '';
        document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('selected'));
    }
    updateSummary();
}

/* ── Duration ───────────────────────────────────────── */
function selectDuration(mins) {
    selectedDuration = mins;
    document.getElementById('hiddenPlannedMinutes').value = mins;
    document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector(`.dur-btn[data-mins="${mins}"]`)?.classList.add('selected');
    updateSummary();
}

/* ── Downpayment ────────────────────────────────────── */
function onDpAmountChange() {
    const amt = parseFloat(document.getElementById('dpAmount').value) || 0;
    document.getElementById('dpMethodSection').style.display = amt > 0 ? 'block' : 'none';
    if (amt <= 0) {
        selectedDpMethod = '';
        document.getElementById('hiddenDpMethod').value = '';
        document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('selected'));
    }
    updateSummary();
}

function selectDpMethod(method) {
    selectedDpMethod = method;
    document.getElementById('hiddenDpMethod').value = method;
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('pm-' + method).classList.add('selected');
    updateSummary();
}

/* ── Availability check ─────────────────────────────── */
let availTimer = null;
function onDateTimeChange() { clearTimeout(availTimer); availTimer = setTimeout(checkAvailability, 500); updateSummary(); }

function checkAvailability() {
    const date = document.getElementById('reservedDate').value;
    const time = document.getElementById('reservedTime').value;
    const el = document.getElementById('availabilityResult');
    const miniEls = { 'PS5': document.getElementById('avail-ps5'), 'PS4': document.getElementById('avail-ps4'), 'Xbox Series X': document.getElementById('avail-xbox') };

    if (!date || !time) { el.style.display='none'; return; }

    el.style.display = 'block';
    el.innerHTML = '<span style="color:#888;font-size:12px;"><i class="fas fa-spinner fa-spin"></i> Checking availability…</span>';
    Object.values(miniEls).forEach(e => { if (e) e.innerHTML = ''; });

    fetch(`ajax/check_availability.php?date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { el.innerHTML = `<span style="color:#fb566b;font-size:12px;"><i class="fas fa-exclamation-circle"></i> ${data.message}</span>`; return; }

            const av = data.availability;
            let html = '<div style="display:flex;gap:10px;flex-wrap:wrap;">';

            ['PS5','PS4','Xbox Series X'].forEach(type => {
                const info = av[type];
                if (!info) return; // type not in DB, skip
                const ok = info.available > 0;
                const cls = ok ? 'ok' : 'none';
                html += `<span class="avail-badge ${cls}"><i class="fas fa-${ok ? 'check' : 'xmark'}"></i> ${type}: ${info.available}/${info.total} free</span>`;

                const miniEl = miniEls[type];
                if (miniEl) miniEl.innerHTML = `<span class="avail-badge ${cls}" style="font-size:10px;">${ok ? info.available + ' free' : 'Full'}</span>`;
            });

            html += '</div>';
            el.innerHTML = html;
        })
        .catch(() => { el.innerHTML = '<span style="color:#888;font-size:12px;">Could not check availability</span>'; });
}

/* ── Summary ────────────────────────────────────────── */
function updateSummary() {
    const date = document.getElementById('reservedDate').value;
    const time = document.getElementById('reservedTime').value;
    const dp   = parseFloat(document.getElementById('dpAmount')?.value) || 0;

    const ready = selectedConsoleType && selectedMode && date && time &&
                  (selectedMode !== 'hourly' || selectedDuration > 0);

    const box = document.getElementById('summaryBox');
    box.style.display = ready ? 'block' : 'none';

    if (!ready) return;

    document.getElementById('s-console').textContent  = selectedConsoleType;
    document.getElementById('s-datetime').textContent =
        new Date(date + 'T' + time).toLocaleDateString('en-PH', {weekday:'short',month:'short',day:'numeric',year:'numeric'}) +
        ' at ' + new Date(date + 'T' + time).toLocaleTimeString('en-PH', {hour:'2-digit',minute:'2-digit'});
    document.getElementById('s-mode').textContent     =
        selectedMode === 'open_time' ? 'Open Time' : selectedMode.charAt(0).toUpperCase() + selectedMode.slice(1);

    let durText = '—';
    if (selectedMode === 'hourly' && selectedDuration) {
        const h = Math.floor(selectedDuration/60), m = selectedDuration%60;
        const cost = selectedDuration <= 30 ? 50 : selectedDuration/60*80;
        durText = (h ? h+'h ' : '') + (m ? m+'m ' : '') + '— ₱' + cost.toFixed(0);
    } else if (selectedMode === 'unlimited') {
        durText = 'Unlimited — ₱' + unlimitedRate;
    } else if (selectedMode === 'open_time') {
        durText = 'Pay at end (bracket pricing)';
    }
    document.getElementById('s-duration').textContent = durText;
    document.getElementById('s-dp').textContent = dp > 0
        ? '₱' + dp.toFixed(2) + (selectedDpMethod ? ' via ' + selectedDpMethod : ' (method required)')
        : 'None — pay in full on arrival';
}

/* ── Form validation ────────────────────────────────── */
document.getElementById('reserveForm').addEventListener('submit', function(e) {
    if (!selectedConsoleType) { e.preventDefault(); alert('Please select a console type.'); return; }
    if (!selectedMode)        { e.preventDefault(); alert('Please select a rental mode.'); return; }
    if (selectedMode === 'hourly' && !selectedDuration) { e.preventDefault(); alert('Please select a duration.'); return; }

    const dp = parseFloat(document.getElementById('dpAmount').value) || 0;
    if (dp > 0 && !selectedDpMethod) { e.preventDefault(); alert('Please select a payment method for your downpayment.'); return; }
});
</script>
</body>
</html>
