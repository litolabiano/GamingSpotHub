<?php
/**
 * Good Spot Gaming Hub — Reserve a Console
 * Customer-facing reservation page. Login required.
 */
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';

requireLogin();

// Start session for flash messages if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

$user    = getCurrentUser();
$success = '';
$error   = '';

// Fetch all non-maintenance consoles for display
$allConsoles = getConsoles();
$ps5Count    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS5'));
$ps4Count    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS4'));
$xboxCount   = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'Xbox Series X'));

// Group non-maintenance consoles by type for the unit picker (passed to JS)
$consolesByType = [];
foreach ($allConsoles as $c) {
    if ($c['status'] !== 'maintenance') {
        $consolesByType[$c['console_type']][] = [
            'id'     => (int)$c['console_id'],
            'unit'   => $c['unit_number'],
            'name'   => $c['console_name'],
            'status' => $c['status'],
        ];
    }
}

$unlimitedRate = getSetting('unlimited_rate') ?? 300;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $console_type       = $_POST['console_type']  ?? '';
    $rental_mode        = $_POST['rental_mode']   ?? '';
    $planned_minutes    = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;
    $reserved_date      = $_POST['reserved_date'] ?? '';
    $reserved_time      = $_POST['reserved_time'] ?? '';
    $notes              = trim($_POST['notes']     ?? '');
    $preferred_unit_id  = (int)($_POST['preferred_console_id'] ?? 0) ?: null;
    $dp_amount          = (float)($_POST['downpayment_amount']  ?? 0);
    $dp_method          = !empty($_POST['downpayment_method']) && $dp_amount > 0
                          ? $_POST['downpayment_method'] : null;

    // ── Reservation ban check (3-strike rule) ──────────────────────────────
    $banStmt = $conn->prepare(
        "SELECT reservation_banned_until, consecutive_cancellations FROM users WHERE user_id = ?"
    );
    $banStmt->bind_param('i', $user['user_id']);
    $banStmt->execute();
    $banRow = $banStmt->get_result()->fetch_assoc();
    if (!empty($banRow['reservation_banned_until']) && strtotime($banRow['reservation_banned_until']) > time()) {
        $banExpiry = date('F j, Y \a\t g:i A', strtotime($banRow['reservation_banned_until']));
        $error = 'Your account is temporarily suspended from making online reservations due to 3 consecutive cancellations. '
               . 'This restriction is automatically lifted on <strong>' . $banExpiry . '</strong>. '
               . 'You are still welcome to walk in and use any available unit.';
    }
    // ── Max 3 active reservations check ────────────────────────────────────
    elseif (!$error) {
        $cntStmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM reservations WHERE user_id = ? AND status IN ('pending','confirmed')"
        );
        $cntStmt->bind_param('i', $user['user_id']);
        $cntStmt->execute();
        $activeCount = (int)$cntStmt->get_result()->fetch_assoc()['cnt'];
        if ($activeCount >= 3) {
            $error = 'You already have ' . $activeCount . ' active reservation(s). The maximum allowed is 3 simultaneous reservations. '
                   . 'Please cancel or wait for an existing reservation to be processed before booking another.';
        }
    }

    // Validation
    if (!$error && !in_array($console_type, ['PS5', 'PS4', 'Xbox Series X'])) {
        $error = 'Please select a valid console type.';
    } elseif (!$error && !in_array($rental_mode, ['hourly', 'open_time', 'unlimited'])) {
        $error = 'Please select a valid rental mode.';
    } elseif (!$error && $rental_mode === 'hourly' && $planned_minutes < 30) {
        $error = 'Please select a duration for hourly mode.';
    } elseif (!$error && $rental_mode === 'hourly' && $planned_minutes > getPricingRules()['max_hourly_minutes']) {
        $rules = getPricingRules();
        $error = 'Hourly reservations are limited to a maximum of ' . ($rules['max_hourly_minutes'] / 60) . ' hours.';
    } elseif (!$error && (!$reserved_date || !$reserved_time)) {
        $error = 'Please provide both date and time.';
    } elseif (!$error && $reserved_date < date('Y-m-d')) {
        $error = 'Reservation date cannot be in the past.';
    } elseif (!$error && strtotime($reserved_date . ' ' . $reserved_time) < (time() + 3600)) {
        $error = 'Reservation must be at least 1 hour from now.';
    } elseif (!$error && (int)date('H', strtotime($reserved_time)) < 12) {
        $error = 'Reservations can only be made from 12:00 PM (noon) onwards.';
    } elseif (!$error && ((int)date('H', strtotime($reserved_time)) >= 24 || $reserved_time > '23:59')) {
        $error = 'Reservations must be before 12:00 AM (midnight) — operating hours end at midnight.';
    } elseif (!$error && $dp_amount > 0 && !$dp_method) {
        $error = 'Please select a payment method for your downpayment.';
    } elseif (!$error && $dp_amount > 0 && ($_POST['no_refund_agreed'] ?? '') !== '1') {
        $error = 'You must read and agree to the No-Refund Policy before submitting a payment.';
    }

    if (!$error) {
        $result = createReservation(
            $user['user_id'], $console_type, $rental_mode, $planned_minutes,
            $reserved_date, $reserved_time,
            $notes ?: null,
            $dp_amount, $dp_method,
            $preferred_unit_id
        );

        if ($result['success']) {
            // PRG pattern: store success flash in session, then redirect to GET
            $_SESSION['reserve_success'] = 'Your reservation #' . $result['reservation_id'] . ' has been submitted! ' .
                                           'A staff member will confirm it shortly.';
            header('Location: reserve.php');
            exit;
        } else {
            $error = 'Could not save reservation: ' . htmlspecialchars($result['message']);
        }
    }
}


// Read and clear the flash success message (set after PRG redirect)
$success = '';
if (!empty($_SESSION['reserve_success'])) {
    $success = $_SESSION['reserve_success'];
    unset($_SESSION['reserve_success']);
}

// My reservations
$myReservations = getMyReservations($user['user_id']);
$todayStr = date('Y-m-d');
// Earliest bookable datetime = now + 1 hour (rounded down to the minute)
$minDateTime = date('Y-m-d\TH:i', strtotime('+1 hour'));

// Pre-selected console type from URL (e.g. reserve.php?console=PS5)
$presetConsole = '';
$validConsoleTypes = ['PS5', 'PS4', 'Xbox Series X'];
if (!empty($_GET['console'])) {
    $candidate = urldecode(trim($_GET['console']));
    if (in_array($candidate, $validConsoleTypes)) {
        $presetConsole = $candidate;
    }
}
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
    /* ══════════════════════════════════════════════════════
       RESERVE PAGE — RESPONSIVE DESIGN SYSTEM
       Breakpoints: xs <480  sm <576  md <768  lg <992
    ══════════════════════════════════════════════════════ */

    /* ── Hero ───────────────────────────────────────────── */
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

    /* ── Form card ──────────────────────────────────────── */
    .reserve-card {
        background: rgba(10,20,50,.7);
        border: 1px solid rgba(95,133,218,.2);
        border-radius: 20px;
        padding: 28px;
        backdrop-filter: blur(12px);
    }
    .reserve-card h2 {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 1.25rem;
        color: #fff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .reserve-card h2 i { color: #20c8a1; flex-shrink: 0; }

    /* ── Console type grid ──────────────────────────────── */
    .console-type-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .console-type-card {
        border: 2px solid rgba(255,255,255,.1);
        border-radius: 14px;
        padding: 18px 12px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: rgba(255,255,255,.03);
        user-select: none;
    }
    .console-type-card:hover  { border-color: rgba(32,200,161,.5); background: rgba(32,200,161,.05); }
    .console-type-card.selected { border-color: #20c8a1; background: rgba(32,200,161,.1); }
    .console-type-card .ct-icon  { font-size: 2rem; margin-bottom: 6px; }
    .console-type-card .ct-name  { font-weight: 700; font-size: .9rem; color: #fff; }
    .console-type-card .ct-count { font-size: 11px; color: #888; margin-top: 3px; }
    .console-type-card .ct-avail { font-size: 10px; margin-top: 3px; }

    /* ── Mode grid ──────────────────────────────────────── */
    .mode-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .mode-card {
        border: 2px solid rgba(255,255,255,.1);
        border-radius: 12px;
        padding: 14px 8px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: rgba(255,255,255,.03);
    }
    .mode-card:hover   { border-color: rgba(179,123,236,.5); }
    .mode-card.selected { border-color: #b37bec; background: rgba(179,123,236,.1); }
    .mode-card .mc-icon { font-size: 1.4rem; margin-bottom: 6px; }
    .mode-card .mc-name { font-weight: 700; font-size: 12px; color: #fff; }
    .mode-card .mc-desc { font-size: 10px; color: #888; margin-top: 4px; line-height: 1.4; }

    /* ── Duration grid ──────────────────────────────────── */
    .duration-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 20px;
    }
    .dur-btn {
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 10px;
        padding: 10px 4px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: rgba(255,255,255,.04);
        color: #ccc;
        font-size: 11px;
        font-weight: 600;
    }
    .dur-btn:hover   { border-color: #f1a83c; color: #f1a83c; }
    .dur-btn.selected { border-color: #f1a83c; background: rgba(241,168,60,.12); color: #f1a83c; }
    .dur-btn .dur-price { display: block; font-size: 12px; font-weight: 800; color: #f1e1aa; margin-top: 3px; }

    /* ── Section label ──────────────────────────────────── */
    .sec-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: #888;
        margin-bottom: 10px;
    }

    /* ── Payment box ────────────────────────────────────── */
    .dp-box {
        background: rgba(32,200,161,.06);
        border: 1px solid rgba(32,200,161,.2);
        border-radius: 14px;
        padding: 18px;
        margin-bottom: 20px;
    }
    .dp-box .dp-title {
        font-weight: 700;
        color: #20c8a1;
        font-size: 13px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .dp-method-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 14px; }
    .pm-card {
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 10px;
        padding: 10px 6px;
        text-align: center;
        cursor: pointer;
        font-size: 11px;
        font-weight: 600;
        color: #aaa;
        transition: all .2s;
    }
    .pm-card:hover   { border-color: #20c8a1; }
    .pm-card.selected { border-color: #20c8a1; background: rgba(32,200,161,.1); color: #20c8a1; }
    .pm-card .pm-icon { display: block; font-size: 1.2rem; margin-bottom: 4px; }

    /* ── Form inputs ────────────────────────────────────── */
    .res-input {
        width: 100%;
        background: rgba(10,33,81,.6);
        border: 1px solid rgba(95,133,218,.25);
        color: #f0f0f0;
        padding: 12px 14px;
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

    /* ── Summary preview ────────────────────────────────── */
    .reserve-summary {
        background: linear-gradient(135deg, rgba(32,200,161,.08), rgba(95,133,218,.06));
        border: 1px solid rgba(32,200,161,.25);
        border-radius: 14px;
        padding: 18px;
        margin-bottom: 24px;
    }
    .rs-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,.06); font-size: 13px; gap: 8px; }
    .rs-row:last-child { border-bottom: none; }
    .rs-label { color: #888; white-space: nowrap; }
    .rs-value { font-weight: 700; color: #f0f0f0; text-align: right; word-break: break-word; }

    /* ── My reservations table ──────────────────────────── */
    .my-res-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .my-res-table th { color: #888; font-weight: 600; text-align: left; padding: 8px 10px; border-bottom: 1px solid rgba(255,255,255,.08); }
    .my-res-table td { padding: 10px 10px; border-bottom: 1px solid rgba(255,255,255,.05); color: #d0d0d0; vertical-align: middle; }
    .my-res-table tr:last-child td { border-bottom: none; }
    .res-badge {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        white-space: nowrap;
    }
    .res-badge.pending   { background:rgba(241,168,60,.15);  color:#f1a83c;  border:1px solid rgba(241,168,60,.3); }
    .res-badge.confirmed { background:rgba(32,200,161,.15);  color:#20c8a1;  border:1px solid rgba(32,200,161,.3); }
    .res-badge.converted { background:rgba(95,133,218,.15);  color:#8aa4e8;  border:1px solid rgba(95,133,218,.3); }
    .res-badge.cancelled { background:rgba(251,86,107,.12);  color:#fb566b;  border:1px solid rgba(251,86,107,.3); }
    .res-badge.no_show   { background:rgba(100,100,100,.15); color:#888;     border:1px solid rgba(100,100,100,.3); }

    /* ── Submit button ──────────────────────────────────── */
    .res-submit-btn {
        width: 100%; padding: 16px;
        background: linear-gradient(135deg, #20c8a1, #17a887);
        border: none; border-radius: 12px;
        color: #0a0f1c; font-weight: 800; font-size: 16px;
        cursor: pointer; transition: all .2s;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .res-submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(32,200,161,.35); }
    .res-submit-btn:active { transform: translateY(0); }

    /* ── Availability badges ────────────────────────────── */
    .avail-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    }
    .avail-badge.ok   { background: rgba(32,200,161,.15); color: #20c8a1; }
    .avail-badge.none { background: rgba(251,86,107,.15); color: #fb566b; }

    /* ── Hero stat boxes ────────────────────────────────── */
    .hero-stats { display: flex; gap: 20px; margin-top: 28px; flex-wrap: wrap; }
    .hero-stat  { text-align: center; }

    /* ══════════════════════════════════════════════════════
       RESPONSIVE BREAKPOINTS
    ══════════════════════════════════════════════════════ */

    /* ── ≤992px : tablet landscape ──────────────────────── */
    @media (max-width: 992px) {
        .reserve-hero { padding: 100px 0 50px; }
        .reserve-card { padding: 24px; }
        .duration-grid { grid-template-columns: repeat(4, 1fr); }
    }

    /* ── ≤768px : tablet portrait ───────────────────────── */
    @media (max-width: 768px) {
        .reserve-hero { padding: 90px 0 40px; }
        .reserve-hero h1 { font-size: clamp(1.6rem, 5vw, 2.4rem) !important; }
        .reserve-card { padding: 20px; border-radius: 16px; }
        .reserve-card h2 { font-size: 1.1rem; margin-bottom: 16px; }

        /* Grids collapse to 2 cols */
        .console-type-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .duration-grid     { grid-template-columns: repeat(3, 1fr); }

        /* Right sidebar drops below on mobile (Bootstrap handles col stacking) */
        .hero-stats { gap: 14px; }
        .hero-stats .hero-stat div:first-child { font-size: 1.5rem !important; }
    }

    /* ── ≤576px : large phone ───────────────────────────── */
    @media (max-width: 576px) {
        .reserve-hero { padding: 80px 0 32px; }
        .reserve-card { padding: 16px; border-radius: 14px; }
        .reserve-card h2 { font-size: 1rem; gap: 8px; }

        /* All selector grids → 2 cols */
        .console-type-grid,
        .mode-grid,
        .dp-method-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }

        /* Duration → 3 cols to keep them readable */
        .duration-grid { grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .dur-btn { padding: 8px 2px; font-size: 10px; }
        .dur-btn .dur-price { font-size: 11px; }

        /* Date + time stacked on very small screens */
        .date-time-row .col-6 { flex: 0 0 100%; max-width: 100%; }

        /* Full-width inputs feel better on mobile */
        .res-input { padding: 11px 12px; font-size: 13px; }

        /* Summary rows wrap gracefully */
        .rs-row { font-size: 12px; }

        /* Table horizontal scroll */
        .my-res-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .my-res-table       { min-width: 380px; }

        /* Submit button */
        .res-submit-btn { font-size: 15px; padding: 14px; }

        .dp-box { padding: 14px; }
        .reserve-summary { padding: 14px; }
    }

    /* ── ≤480px : small phone ───────────────────────────── */
    @media (max-width: 480px) {
        .console-type-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .console-type-card { padding: 12px 8px; }
        .console-type-card .ct-icon { font-size: 1.6rem; }

        .mode-card .mc-desc { display: none; } /* hide descriptions to save space */

        .duration-grid { grid-template-columns: repeat(2, 1fr); }

        /* Hero stat numbers smaller */
        .hero-stats { gap: 10px; }
    }

    /* ── Date & Time modern picker ──────────────────────── */
    .dt-field-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        background: rgba(10,33,81,.55);
        border: 1.5px solid rgba(95,133,218,.2);
        border-radius: 16px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all .25s;
        position: relative;
        overflow: hidden;
        margin-bottom: 14px;
    }
    .dt-field-wrap:hover {
        border-color: rgba(32,200,161,.5);
        background: rgba(10,33,81,.75);
        transform: translateY(-1px);
        box-shadow: 0 6px 24px rgba(0,0,0,.25);
    }
    .dt-field-wrap.dt-filled {
        border-color: rgba(32,200,161,.45);
        box-shadow: 0 0 0 3px rgba(32,200,161,.07);
    }
    .dt-field-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(32,200,161,.25), rgba(32,200,161,.1));
        color: #20c8a1;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        transition: all .25s;
    }
    .dt-field-wrap:hover .dt-field-icon { transform: scale(1.08); }
    .dt-field-body {
        flex: 1;
        min-width: 0;
    }
    .dt-field-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #888;
        margin-bottom: 2px;
    }
    .dt-native-input {
        width: 100%;
        background: transparent;
        border: none;
        outline: none;
        color: #f0f0f0;
        font-size: 1rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        padding: 0;
        /* make the native picker wide enough */
        min-width: 0;
    }
    .dt-native-input::-webkit-calendar-picker-indicator {
        /* stretch it to cover the whole wrapper */
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .dt-field-sublabel {
        font-size: 11px;
        color: #555;
        margin-top: 2px;
        transition: color .2s;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dt-field-wrap.dt-filled .dt-field-sublabel { color: #20c8a1; }
    #timeWrap.dt-filled .dt-field-sublabel { color: #b37bec; }
    .dt-field-arrow {
        font-size: 11px;
        color: rgba(255,255,255,.2);
        flex-shrink: 0;
        transition: all .2s;
    }
    .dt-field-wrap:hover .dt-field-arrow { color: rgba(32,200,161,.7); transform: translateX(2px); }

    /* Pulse animation for banner dot */
    @keyframes dtPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .5; transform: scale(1.4); }
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
<section style="padding: clamp(32px, 6vw, 60px) 0 clamp(48px, 8vw, 80px); background: #07101f;">
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
                    <input type="hidden" name="console_type"         id="hiddenConsoleType">
                    <input type="hidden" name="rental_mode"          id="hiddenRentalMode">
                    <input type="hidden" name="planned_minutes"      id="hiddenPlannedMinutes">
                    <input type="hidden" name="downpayment_method"   id="hiddenDpMethod">
                    <input type="hidden" name="preferred_console_id" id="hiddenPreferredUnit" value="">

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



                    <!-- ── Step 2: Date & Time ──────────────────────────── -->
                    <div class="reserve-card" style="margin-bottom:24px;position:relative;overflow:hidden;" id="step2Card">

                        <!-- Decorative glow -->
                        <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;
                                    background:radial-gradient(circle, rgba(32,200,161,.12) 0%, transparent 70%);
                                    pointer-events:none;border-radius:50%;"></div>

                        <h2><i class="fas fa-calendar-alt"></i> Step 2 &mdash; Pick Date &amp; Time</h2>

                        <!-- Live selection preview banner -->
                        <div id="dtPreviewBanner" style="
                            display:none;
                            background:linear-gradient(135deg, rgba(32,200,161,.12), rgba(95,133,218,.1));
                            border:1px solid rgba(32,200,161,.3);
                            border-radius:14px;
                            padding:14px 18px;
                            margin-bottom:20px;
                            align-items:center;gap:14px;">
                            <div style="background:linear-gradient(135deg,#20c8a1,#17a887);
                                        border-radius:12px;padding:10px 14px;text-align:center;flex-shrink:0;">
                                <div id="dtBannerDay"   style="font-size:11px;font-weight:800;color:#0a1a10;text-transform:uppercase;letter-spacing:1px;">—</div>
                                <div id="dtBannerDate"  style="font-size:1.6rem;font-weight:900;color:#0a1a10;line-height:1;">—</div>
                                <div id="dtBannerMonth" style="font-size:11px;font-weight:700;color:#0a1a10;">—</div>
                            </div>
                            <div>
                                <div id="dtBannerTime"  style="font-size:1.4rem;font-weight:800;color:#fff;line-height:1.2;">—:— —</div>
                                <div id="dtBannerLabel" style="font-size:12px;color:#20c8a1;margin-top:4px;font-weight:600;">
                                    <i class="fas fa-circle" style="font-size:7px;vertical-align:middle;margin-right:5px;animation:dtPulse 1.5s ease-in-out infinite;"></i>
                                    Session scheduled
                                </div>
                            </div>
                        </div>

                        <!-- Pickers row -->
                        <div class="row gx-3 date-time-row">

                            <!-- Date picker -->
                            <div class="col-sm-6 col-12" style="margin-bottom:4px;">
                                <div class="dt-field-wrap" id="dateWrap">
                                    <div class="dt-field-icon"><i class="fas fa-calendar"></i></div>
                                    <div class="dt-field-body">
                                        <div class="dt-field-label">Date</div>
                                        <input type="date" id="reservedDate" name="reserved_date"
                                               class="dt-native-input"
                                               min="<?= date('Y-m-d') ?>"
                                               value="<?= htmlspecialchars($_POST['reserved_date'] ?? '') ?>"
                                               onchange="onDateTimeChange(); updateDtBanner();" required>
                                        <div class="dt-field-sublabel" id="dateSublabel">Pick your visit date</div>
                                    </div>
                                    <div class="dt-field-arrow"><i class="fas fa-chevron-right"></i></div>
                                </div>
                            </div>

                            <!-- Time picker -->
                            <div class="col-sm-6 col-12" style="margin-bottom:4px;">
                                <div class="dt-field-wrap" id="timeWrap">
                                    <div class="dt-field-icon" style="background:linear-gradient(135deg,rgba(179,123,236,.25),rgba(95,133,218,.2));color:#b37bec;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="dt-field-body">
                                        <div class="dt-field-label">Time</div>
                                        <input type="time" id="reservedTime" name="reserved_time"
                                               class="dt-native-input"
                                               min="12:00" max="23:59"
                                               value="<?= htmlspecialchars($_POST['reserved_time'] ?? '') ?>"
                                               onchange="onDateTimeChange(); updateDtBanner();" required>
                                        <div class="dt-field-sublabel" id="timeSublabel">12:00 PM – 12:00 AM</div>
                                    </div>
                                    <div class="dt-field-arrow" style="color:#b37bec;"><i class="fas fa-chevron-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <!-- Operating hours info strip -->
                        <div style="display:flex;align-items:center;gap:10px;margin-top:4px;
                                    padding:10px 14px;border-radius:10px;
                                    background:rgba(32,200,161,.05);border:1px solid rgba(32,200,161,.12);">
                            <span style="width:8px;height:8px;border-radius:50%;background:#20c8a1;flex-shrink:0;
                                         box-shadow:0 0 0 3px rgba(32,200,161,.2);"></span>
                            <span style="font-size:12px;color:#888;">
                                Operating hours &nbsp;<strong style="color:#20c8a1;">12:00 PM</strong> to
                                <strong style="color:#20c8a1;">12:00 AM</strong>
                                &nbsp;&middot;&nbsp; Reservation requires <strong style="color:#ccc;">1 hr lead time</strong>
                            </span>
                        </div>
                        <div id="availabilityResult" style="display:none;margin-top:14px;"></div>
                    </div>

                    <!-- ── Step 2b: Unit Picker ── -->
                    <div class="reserve-card" id="unitPickerCard" style="margin-bottom:24px;display:none;">
                        <h2>
                            <i class="fas fa-tv"></i> Step 2b &mdash; Choose Preferred Unit
                            <span style="font-size:12px;font-weight:500;color:#888;margin-left:6px;">(Optional)</span>
                        </h2>
                        <p style="color:#888;font-size:13px;margin-bottom:14px;">
                            Availability shown is based on your chosen date &amp; time.
                        </p>
                        <div id="unitPickerGrid"
                             style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:12px;"></div>
                        <div id="unitPickerAny" onclick="selectUnit(null)"
                             style="padding:10px 16px;border-radius:10px;border:2px solid rgba(32,200,161,.35);
                                    background:rgba(32,200,161,.06);color:#20c8a1;font-size:13px;font-weight:700;
                                    cursor:pointer;text-align:center;transition:.2s;"
                             onmouseover="this.style.borderColor='rgba(32,200,161,.8)'"
                             onmouseout="this.style.borderColor='rgba(32,200,161,.35)'">
                            <i class="fas fa-shuffle" style="margin-right:6px;"></i> No preference &mdash; let staff assign
                        </div>
                    </div>

                    <!-- ── Step 3: Rental Mode ── -->
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

                        <!-- Duration picker (hourly only) — max 4 hrs -->
                        <div id="durationSection" style="display:none;margin-top:20px;">
                            <div class="sec-label" style="display:flex;align-items:center;gap:8px;">Duration *
                                <span style="font-size:10px;background:rgba(32,200,161,.15);color:#20c8a1;border:1px solid rgba(32,200,161,.3);border-radius:20px;padding:2px 8px;font-weight:700;">
                                    Max 4 hrs
                                </span>
                            </div>
                            <div class="duration-grid">
                                <?php
                                // DB-driven: reads bonus_paid_minutes, bonus_free_minutes, max_hourly_minutes
                                // from system_settings. Change the DB to change this list.
                                $durationOptions = getHourlyDurationOptions();
                                foreach ($durationOptions as $opt):
                                    $bonus = $opt['bonus_free'] ?? $opt['bonus'];
                                    $total = $opt['total'];
                                ?>
                                <div class="dur-btn" data-mins="<?= $opt['paid'] ?>" data-cost="<?= $opt['cost'] ?>" data-bonus="<?= $opt['bonus'] ?>" data-total="<?= $opt['total'] ?>" onclick="selectDuration(<?= $opt['paid'] ?>)">
                                    <?= $opt['label_paid'] ?>
                                    <span class="dur-price">&#8369;<?= number_format($opt['cost'], 0) ?></span>
                                    <?php if ($opt['bonus'] > 0): ?>
                                    <span style="display:block;font-size:9px;color:#20c8a1;font-weight:700;margin-top:3px;letter-spacing:.3px;"><?= $opt['label_bonus'] ?></span>
                                    <span style="display:block;font-size:9px;color:#888;font-weight:600;margin-top:1px;">&rarr; <?= $opt['label_total'] ?> total</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top:10px;font-size:11px;color:#555;background:rgba(32,200,161,.05);border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:8px;">
                                <i class="fas fa-gift" style="color:#20c8a1;"></i>
                                <?php $pr = getPricingRules(); ?>
                                <span>Bonus: every <strong style="color:#20c8a1;"><?= $pr['bonus_paid_minutes'] / 60 ?> paid hours</strong> = <strong style="color:#20c8a1;"><?= $pr['bonus_free_minutes'] ?> min free</strong> added to your session.</span>
                            </div>
                        </div>
                    </div>

                    <!-- ── Step 4: Payment ── -->
                    <div class="reserve-card" style="margin-bottom:24px;" id="dpCard">
                        <h2><i class="fas fa-peso-sign"></i> Step 4 — Payment <span id="dpCardMode" style="font-size:13px;font-weight:500;color:#888;"></span></h2>
                        <p id="dpDesc" style="color:#888;font-size:13px;margin-bottom:18px;">
                            Full payment is required to confirm your hourly reservation.
                        </p>

                        <!-- Open Time: no upfront payment notice (shown/hidden by JS) -->
                        <div id="dpOpenTimeNotice" style="display:none;
                            background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.25);
                            border-radius:14px;padding:18px;text-align:center;">
                            <i class="fas fa-clock" style="font-size:2rem;color:#5f85da;margin-bottom:10px;display:block;"></i>
                            <div style="font-weight:700;color:#fff;font-size:14px;margin-bottom:6px;">No Upfront Payment Needed</div>
                            <div style="font-size:12px;color:#888;line-height:1.6;">
                                Pay bracket pricing at the end of your session.<br>
                                <strong style="color:#5f85da;">Free 30 mins</strong> every 2 paid hours!
                            </div>
                        </div>

                        <div id="dpPaymentBox" class="dp-box">
                            <div class="dp-title" style="justify-content:space-between;">
                                <span><i class="fas fa-coins"></i> Payment Amount</span>
                                <span id="dpHint" style="font-size:12px;font-weight:600;color:#20c8a1;"></span>
                            </div>
                            <input type="number" name="downpayment_amount" id="dpAmount" class="res-input"
                                   min="0" step="1" placeholder="Select a duration above first"
                                   readonly
                                   style="cursor:not-allowed;background:rgba(32,200,161,.06);border-color:rgba(32,200,161,.3);color:#20c8a1;font-size:16px;font-weight:700;"
                                   value="<?= htmlspecialchars($_POST['downpayment_amount'] ?? '') ?>">
                            <p id="dpNote" style="font-size:11px;color:#888;margin:-8px 0 14px;"><i class="fas fa-lock" style="margin-right:4px;"></i>Fixed at 100% of your session cost.</p>

                            <div id="dpMethodSection" style="display:none;">
                                <div class="sec-label">Payment Method *</div>
                                <div class="dp-method-grid">
                                    <div class="pm-card" id="pm-cash" onclick="selectDpMethod('cash')">
                                        <span class="pm-icon">&#x1F4B5;</span>Cash
                                    </div>
                                    <div class="pm-card" id="pm-gcash" onclick="selectDpMethod('gcash')">
                                        <span class="pm-icon">&#x1F4F1;</span>GCash
                                    </div>
                                    <div class="pm-card" id="pm-credit_card" onclick="selectDpMethod('credit_card')">
                                        <span class="pm-icon">&#x1F4B3;</span>Credit Card
                                    </div>
                                </div>
                                <p style="font-size:11px;color:#888;margin-bottom:0;">
                                    <i class="fas fa-info-circle"></i>
                                    Payment will be recorded as your session upfront payment.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-sticky-note"></i> Step 5 — Notes (Optional)</h2>
                        <textarea name="notes" class="res-input" placeholder="Any special requests? (e.g. preferred controller, specific game ready, group size...)"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- ── No-Refund Policy Acknowledgment ───────────────── -->
                    <div id="noRefundPolicyBox" style="
                        background:linear-gradient(135deg,rgba(251,86,107,.08),rgba(241,168,60,.06));
                        border:1px solid rgba(251,86,107,.35);
                        border-radius:16px;
                        padding:20px 22px;
                        margin-bottom:20px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                            <div style="width:38px;height:38px;border-radius:10px;
                                background:rgba(251,86,107,.15);
                                display:flex;align-items:center;justify-content:center;
                                flex-shrink:0;color:#fb566b;font-size:1rem;">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div style="font-weight:800;color:#fb566b;font-size:14px;letter-spacing:.3px;">No-Refund Policy</div>
                        </div>
                        <ul style="font-size:12px;color:#ccc;line-height:1.9;margin:0 0 16px 16px;padding:0;">
                            <li>All payments made for reservations are <strong style="color:#fff;">non-refundable</strong> under any circumstances.</li>
                            <li>No refunds will be issued regardless of the cancellation reason.</li>
                            <li>No store credit or GC will be given in place of a refund.</li>
                            <li>No partial refunds will be processed.</li>
                            <li>This policy applies to all cancellations — whether initiated by you or by staff on your behalf.</li>
                        </ul>
                        <label id="noRefundLabel" style="
                            display:flex;align-items:flex-start;gap:12px;
                            cursor:pointer;
                            background:rgba(255,255,255,.04);
                            border:1px solid rgba(255,255,255,.1);
                            border-radius:10px;
                            padding:12px 14px;
                            transition:border-color .2s,background .2s;">
                            <input type="checkbox" id="noRefundCheck" name="no_refund_agreed" value="1"
                                   onchange="handleNoRefundCheck(this)"
                                   style="width:18px;height:18px;margin-top:1px;flex-shrink:0;accent-color:#fb566b;cursor:pointer;">
                            <span style="font-size:13px;color:#e0e0e0;line-height:1.5;">
                                I have read, understood, and agreed to the <strong style="color:#fb566b;">No-Refund Policy</strong> above.
                                I acknowledge that any payment I make for this reservation will not be refunded for any reason.
                            </span>
                        </label>
                    </div>

                    <!-- Summary preview -->
                    <div class="reserve-summary" id="summaryBox" style="display:none;">
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#20c8a1;margin-bottom:14px;">
                            <i class="fas fa-receipt"></i> Reservation Summary
                        </div>
                        <div class="rs-row"><span class="rs-label">Console</span><span class="rs-value" id="s-console">—</span></div>
                        <div class="rs-row"><span class="rs-label">Date &amp; Time</span><span class="rs-value" id="s-datetime">—</span></div>
                        <div class="rs-row"><span class="rs-label">Mode</span><span class="rs-value" id="s-mode">—</span></div>
                        <div class="rs-row"><span class="rs-label">Duration</span><span class="rs-value" id="s-duration">—</span></div>
                        <div class="rs-row"><span class="rs-label">Payment</span><span class="rs-value" id="s-dp">None</span></div>
                    </div>

                    <button type="submit" class="res-submit-btn" id="submitBtn" disabled>
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
                            <span>30 minutes (Hourly)</span><span style="color:#f1e1aa;font-weight:700;">₱<?= number_format($pr['session_min_charge'], 0) ?></span>
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

/* ── Auto-select console type from URL param (?console=PS5 etc.) ── */
(function () {
    const preset = <?= json_encode($presetConsole) ?>;
    if (preset) {
        // setTimeout so all functions below are fully defined before this runs
        setTimeout(function () {
            selectConsoleType(preset);
            // Scroll the chosen console card into view
            const cardId = CONSOLE_TYPE_IDS[preset];
            if (cardId) {
                const card = document.getElementById(cardId);
                if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 300);
    }
})();

/* ── State ──────────────────────────────────────────── */
let selectedConsoleType = '';
let selectedMode        = '';
let selectedDuration    = 0;
let selectedDpMethod    = '';
let selectedUnitId      = null;
let selectedUnitLabel   = '';

const unlimitedRate   = <?= (int)$unlimitedRate ?>;
const PRICING         = <?= json_encode(getPricingRules()) ?>;
const CONSOLES_BY_TYPE = <?= json_encode($consolesByType, JSON_UNESCAPED_UNICODE) ?>;

/* ── Console type ───────────────────────────────────── */
const CONSOLE_TYPE_IDS = { 'PS5': 'ct-ps5', 'PS4': 'ct-ps4', 'Xbox Series X': 'ct-xbox' };
function selectConsoleType(type) {
    selectedConsoleType = type;
    document.getElementById('hiddenConsoleType').value = type;
    document.querySelectorAll('.console-type-card').forEach(c => c.classList.remove('selected'));
    const el = document.getElementById(CONSOLE_TYPE_IDS[type]);
    if (el) el.classList.add('selected');

    // Reset unit selection; show picker only if date+time already chosen
    selectUnit(null, true);
    refreshUnitPicker();

    updateSummary();
    checkAvailability();
}

/* ── Unit picker ─────────────────────────────────────────
   Logic:
   • Hidden until console type AND date AND time are all set
   • When all three are set, queries the reservation DB via AJAX
   • Shows real availability at the chosen date/time (not live status)
   ───────────────────────────────────────────────────────── */
let unitPickerTimer = null;

function refreshUnitPicker() {
    const card  = document.getElementById('unitPickerCard');
    const dateV = document.getElementById('reservedDate').value;
    const timeV = document.getElementById('reservedTime').value;

    // Hide entirely until a console type is selected
    if (!selectedConsoleType) {
        card.style.display = 'none';
        return;
    }

    // Console type chosen but date/time not yet set → show locked placeholder
    if (!dateV || !timeV) {
        card.style.display = 'block';
        renderUnitPickerLocked();
        return;
    }

    // All three set — fetch real availability (debounced)
    card.style.display = 'block';
    clearTimeout(unitPickerTimer);
    unitPickerTimer = setTimeout(() => fetchUnitAvailability(dateV, timeV), 350);
}

function renderUnitPickerLocked() {
    const grid = document.getElementById('unitPickerGrid');
    // Show static cards from PHP data but greyed out with a lock overlay
    const units = CONSOLES_BY_TYPE[selectedConsoleType] || [];
    if (!units.length) {
        document.getElementById('unitPickerCard').style.display = 'none';
        return;
    }
    grid.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:22px 16px;
                    background:rgba(255,255,255,.03);border-radius:12px;
                    border:1.5px dashed rgba(255,255,255,.1);color:#555;font-size:13px;">
            <i class="fas fa-calendar-clock" style="font-size:1.6rem;display:block;margin-bottom:10px;color:#444;"></i>
            <strong style="color:#888;">Pick a date &amp; time first</strong><br>
            <span style="font-size:11px;">Available units will appear here once you've chosen your slot in Step 2.</span>
        </div>`;
    const anyBtn = document.getElementById('unitPickerAny');
    if (anyBtn) { anyBtn.style.opacity = '.35'; anyBtn.style.pointerEvents = 'none'; }
}

function fetchUnitAvailability(dateV, timeV) {
    const grid = document.getElementById('unitPickerGrid');
    const anyBtn = document.getElementById('unitPickerAny');

    // Loading state
    grid.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:20px;color:#888;font-size:12px;">
            <i class="fas fa-spinner fa-spin" style="margin-right:6px;color:#20c8a1;"></i>
            Checking availability for ${dateV} at ${formatTime12(timeV)}…
        </div>`;

    fetch(`ajax/check_unit_availability.php?date=${encodeURIComponent(dateV)}&time=${encodeURIComponent(timeV)}&console_type=${encodeURIComponent(selectedConsoleType)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                grid.innerHTML = `<div style="grid-column:1/-1;color:#fb566b;font-size:12px;padding:12px;">
                    <i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                return;
            }

            // ── Unassigned reservation warning ──────────────────────────
            let extraHtml = '';
            if (data.unassigned_count > 0) {
                const names = data.unassigned_reservations
                    .map(r => `<strong>${r.reserved_by}</strong> @ ${formatTime12(r.reserved_time)}`)
                    .join(', ');
                extraHtml = `
                <div style="grid-column:1/-1;display:flex;align-items:flex-start;gap:10px;
                            padding:10px 14px;border-radius:10px;margin-bottom:6px;
                            background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.3);">
                    <i class="fas fa-triangle-exclamation" style="color:#f1a83c;margin-top:2px;flex-shrink:0;"></i>
                    <span style="font-size:12px;color:#ccc;">
                        <strong style="color:#f1a83c;">${data.unassigned_count} reservation${data.unassigned_count > 1 ? 's' : ''}</strong>
                        exist for this date but no specific unit was assigned yet
                        (${names}). Staff will allocate a unit at check-in.
                    </span>
                </div>`;
            }

            renderUnitCards(data.units, extraHtml);
            if (anyBtn) { anyBtn.style.opacity = '1'; anyBtn.style.pointerEvents = ''; }
        })
        .catch(() => {
            grid.innerHTML = `<div style="grid-column:1/-1;color:#fb566b;font-size:12px;padding:12px;">
                <i class="fas fa-exclamation-circle"></i> Could not check availability.</div>`;
        });
}

function renderUnitCards(units, extraHtml = '') {
    const grid = document.getElementById('unitPickerGrid');

    grid.innerHTML = extraHtml + units.map(u => {
        const isAvail    = u.status === 'available';
        const isReserved = u.status === 'reserved';
        const isInUse    = u.status === 'in_use';

        const colour  = isAvail ? '#20c8a1' : isInUse ? '#f1a83c' : '#fb566b';
        const bgAlpha = isAvail ? '.03'     : '.06';
        const border  = isAvail
            ? 'rgba(255,255,255,.12)'
            : isInUse ? 'rgba(241,168,60,.3)' : 'rgba(251,86,107,.3)';

        const statusIcon  = isAvail ? '✅' : isReserved ? '🔒' : '🎮';
        const statusLabel = isAvail ? 'Available' : isReserved ? 'Reserved' : 'In Use';
        const clickable   = isAvail;

        const tooltip = isReserved && u.conflict
            ? `title="Reserved at ${u.conflict.reserved_time} by ${u.conflict.reserved_by}"`
            : '';

        return `<div id="unit-${u.id}" data-unit-id="${u.id}" ${tooltip}
                     onclick="${clickable ? `selectUnit(${u.id}, false, '${u.unit}')` : ''}"
                     style="border:2px solid ${border};border-radius:12px;padding:14px 8px;
                            cursor:${clickable ? 'pointer' : 'not-allowed'};transition:all .2s;
                            text-align:center;background:rgba(255,255,255,${bgAlpha});
                            user-select:none;opacity:${clickable ? '1' : '.65'};">
                    <div style="font-size:1.2rem;margin-bottom:6px;">${statusIcon}</div>
                    <div style="font-weight:700;font-size:13px;color:#fff;margin-bottom:4px;">${u.unit}</div>
                    <div style="font-size:10px;color:${colour};font-weight:700;">${statusLabel}</div>
                </div>`;
    }).join('');

    // Restore "any" button
    const anyBtn = document.getElementById('unitPickerAny');
    if (anyBtn) {
        anyBtn.style.opacity = '1';
        anyBtn.style.pointerEvents = '';
        // Re-highlight if "any" is currently selected
        if (!selectedUnitId) {
            anyBtn.style.borderColor = '#20c8a1';
            anyBtn.style.background  = 'rgba(32,200,161,.15)';
        } else {
            anyBtn.style.borderColor = 'rgba(32,200,161,.35)';
            anyBtn.style.background  = 'rgba(32,200,161,.06)';
        }
    }
}

function formatTime12(timeStr) {
    if (!timeStr) return '';
    const [h, m] = timeStr.split(':');
    const hh = parseInt(h), ampm = hh >= 12 ? 'PM' : 'AM';
    return `${hh % 12 || 12}:${m} ${ampm}`;
}

function selectUnit(id, silent = false, label = '') {
    selectedUnitId    = id;
    selectedUnitLabel = label;
    document.getElementById('hiddenPreferredUnit').value = id || '';

    // Reset all unit cards
    document.querySelectorAll('[data-unit-id]').forEach(el => {
        const isReserved = el.querySelector('div:last-child')?.textContent.trim() === 'Reserved';
        const isInUse    = el.querySelector('div:last-child')?.textContent.trim() === 'In Use';
        el.style.borderColor = isReserved ? 'rgba(251,86,107,.3)'
                             : isInUse    ? 'rgba(241,168,60,.3)'
                             :              'rgba(255,255,255,.12)';
        el.style.background  = 'rgba(255,255,255,.03)';
    });

    const anyBtn = document.getElementById('unitPickerAny');
    if (anyBtn) { anyBtn.style.borderColor = 'rgba(32,200,161,.35)'; anyBtn.style.background = 'rgba(32,200,161,.06)'; }

    if (id) {
        const card = document.getElementById(`unit-${id}`);
        if (card) { card.style.borderColor = '#f1a83c'; card.style.background = 'rgba(241,168,60,.13)'; }
    } else if (!silent && anyBtn) {
        anyBtn.style.borderColor = '#20c8a1';
        anyBtn.style.background  = 'rgba(32,200,161,.15)';
    }

    if (!silent) updateSummary();
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

    // ── Update the Step 4 payment card to reflect the selected mode ──
    const dpDesc        = document.getElementById('dpDesc');
    const dpNote        = document.getElementById('dpNote');
    const dpHint        = document.getElementById('dpHint');
    const dpAmount      = document.getElementById('dpAmount');
    const dpPaymentBox  = document.getElementById('dpPaymentBox');
    const dpOpenNotice  = document.getElementById('dpOpenTimeNotice');
    const dpMethodSec   = document.getElementById('dpMethodSection');

    if (mode === 'open_time') {
        dpDesc.textContent = 'No upfront payment needed — pay bracket pricing at the end of your session.';
        dpOpenNotice.style.display  = 'block';
        dpPaymentBox.style.display  = 'none';
        // Zero out hidden amount so backend ignores it
        dpAmount.value = '0';
        selectedDpMethod = '';
        document.getElementById('hiddenDpMethod').value = '';
        document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('selected'));

    } else if (mode === 'unlimited') {
        dpDesc.textContent = `A flat rate of \u20b1${unlimitedRate} is required upfront for unlimited play.`;
        dpOpenNotice.style.display  = 'none';
        dpPaymentBox.style.display  = 'block';
        dpAmount.value              = unlimitedRate;
        dpHint.textContent          = `Flat rate: \u20b1${unlimitedRate}`;
        dpNote.innerHTML            = '<i class="fas fa-lock" style="margin-right:4px;"></i>Fixed flat rate — no duration to select.';
        dpMethodSec.style.display   = 'block';

    } else {
        // hourly — reset to waiting-for-duration state
        dpDesc.textContent          = 'Full payment is required to confirm your hourly reservation.';
        dpOpenNotice.style.display  = 'none';
        dpPaymentBox.style.display  = 'block';
        dpAmount.value              = '';
        dpHint.textContent          = '';
        dpNote.innerHTML            = '<i class="fas fa-lock" style="margin-right:4px;"></i>Fixed at 100% of your session cost.';
        dpMethodSec.style.display   = 'none';
        selectedDpMethod = '';
        document.getElementById('hiddenDpMethod').value = '';
        document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('selected'));
    }

    updateSummary();
}

/* ── Duration ───────────────────────────────────────── */
function selectDuration(mins) {
    // Guard: never accept more than 4 hours (240 min)
    if (mins > 240) { alert('Hourly reservations are limited to 4 hours maximum.'); return; }

    selectedDuration = mins;
    document.getElementById('hiddenPlannedMinutes').value = mins;
    document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector(`.dur-btn[data-mins="${mins}"]`)?.classList.add('selected');

    // Read cost and bonus from data attributes on the button (set by PHP / getHourlyDurationOptions)
    const btn      = document.querySelector(`.dur-btn[data-mins="${mins}"]`);
    const fullCost = btn ? parseFloat(btn.dataset.cost  || 0) : (mins <= 30 ? PRICING.session_min_charge : mins / 60 * PRICING.hourly_rate);
    const bonusMins= btn ? parseInt(btn.dataset.bonus   || 0) : 0;
    const totalMins= btn ? parseInt(btn.dataset.total   || mins) : mins;

    document.getElementById('dpAmount').value = fullCost;

    const totalH     = Math.floor(totalMins / 60);
    const totalM     = totalMins % 60;
    const totalLabel = (totalH ? totalH + 'h ' : '') + (totalM ? totalM + 'm' : '');

    let hint = `Full cost: \u20b1${fullCost.toFixed(0)}`;
    if (bonusMins > 0) {
        hint += ` · Total session: ${totalLabel} (+${bonusMins} min free 🎁)`;
    }
    document.getElementById('dpHint').textContent = hint;
    document.getElementById('dpMethodSection').style.display = 'block';

    updateSummary();
}


/* ── Downpayment ────────────────────────────────────── */
/* Read-only field — driven entirely by selectDuration(). No manual input needed. */
function onDpAmountChange() {}

function selectDpMethod(method) {
    selectedDpMethod = method;
    document.getElementById('hiddenDpMethod').value = method;
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('pm-' + method).classList.add('selected');
    updateSummary();
}

/* ── No-Refund Policy checkbox ──────────────────────── */
function handleNoRefundCheck(checkbox) {
    const btn   = document.getElementById('submitBtn');
    const label = document.getElementById('noRefundLabel');
    if (checkbox.checked) {
        btn.disabled = false;
        btn.style.opacity = '1';
        label.style.borderColor = 'rgba(251,86,107,.6)';
        label.style.background  = 'rgba(251,86,107,.08)';
    } else {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        label.style.borderColor = 'rgba(255,255,255,.1)';
        label.style.background  = 'rgba(255,255,255,.04)';
    }
}

/* ── Operating hours + 1-hr lead-time enforcement ──── */
const MIN_LEAD_SECONDS = 3600; // 1 hour
const OPEN_TIME        = '12:00'; // noon
const CLOSE_TIME       = '23:59'; // last slot (midnight closing)

function getMinTimeForDate(dateStr) {
    const today    = new Date();
    const todayStr = today.toISOString().slice(0, 10);

    // Earliest bookable = opening OR now+1hr, whichever is later
    if (dateStr === todayStr) {
        const earliest = new Date(today.getTime() + MIN_LEAD_SECONDS * 1000);
        const lead = String(earliest.getHours()).padStart(2,'0') + ':' + String(earliest.getMinutes()).padStart(2,'0');
        // If lead time falls before opening, snap to opening
        return lead > OPEN_TIME ? lead : OPEN_TIME;
    }
    // Future date: just use opening time
    return OPEN_TIME;
}

function enforceMinTime() {
    const dateEl = document.getElementById('reservedDate');
    const timeEl = document.getElementById('reservedTime');
    const minT   = getMinTimeForDate(dateEl.value);

    // Always enforce operating window
    timeEl.min = minT;
    timeEl.max = CLOSE_TIME;

    let invalid = false;
    if (timeEl.value && timeEl.value < minT) {
        invalid = true;
        timeEl.title = timeEl.value < OPEN_TIME
            ? 'We\'re open from 12:00 PM (noon)'
            : 'Must be at least 1 hour from now';
    } else if (timeEl.value && timeEl.value > CLOSE_TIME) {
        invalid = true;
        timeEl.title = 'We close at 12:00 AM (midnight)';
    }

    if (invalid) {
        timeEl.value       = '';
        timeEl.style.borderColor = '#fb566b';
        timeEl.style.boxShadow   = '0 0 0 3px rgba(251,86,107,.2)';
    } else {
        timeEl.style.borderColor = '';
        timeEl.style.boxShadow   = '';
        timeEl.title = '';
    }
}

/* ── Availability check ─────────────────────────────── */
let availTimer = null;
function onDateTimeChange() {
    enforceMinTime();
    clearTimeout(availTimer);
    availTimer = setTimeout(checkAvailability, 500);
    // Refresh unit picker with reservation-aware availability for the new slot
    refreshUnitPicker();
    updateSummary();
}

// Run on page load — pre-fill and clamp to operating hours
document.addEventListener('DOMContentLoaded', function () {
    const dateEl = document.getElementById('reservedDate');
    const timeEl = document.getElementById('reservedTime');

    if (!dateEl.value && !timeEl.value) {
        const earliest = new Date(Date.now() + MIN_LEAD_SECONDS * 1000);
        const yyyy = earliest.getFullYear();
        const mm   = String(earliest.getMonth() + 1).padStart(2, '0');
        const dd   = String(earliest.getDate()).padStart(2, '0');
        const hh   = String(earliest.getHours()).padStart(2, '0');
        const min  = String(earliest.getMinutes()).padStart(2, '0');
        const leadt = `${hh}:${min}`;

        dateEl.value = `${yyyy}-${mm}-${dd}`;

        // Snap to noon if lead-time falls before opening
        if (leadt < OPEN_TIME) {
            timeEl.value = OPEN_TIME;
        } else if (leadt > CLOSE_TIME) {
            // Past closing — move to next day at noon
            const tomorrow = new Date(earliest);
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateEl.value = tomorrow.toISOString().slice(0, 10);
            timeEl.value = OPEN_TIME;
        } else {
            timeEl.value = leadt;
        }
    }

    enforceMinTime();
    updateDtBanner();
    updateSummary();
    checkAvailability();
    // If a console was pre-selected (e.g. via URL ?console=PS5), refresh the unit picker
    refreshUnitPicker();
});


function checkAvailability() {
    const date = document.getElementById('reservedDate').value;
    const time = document.getElementById('reservedTime').value;
    const el   = document.getElementById('availabilityResult');
    const miniEls = {
        'PS5':         document.getElementById('avail-ps5'),
        'PS4':         document.getElementById('avail-ps4'),
        'Xbox Series X': document.getElementById('avail-xbox')
    };
    // Map console type → ct-avail badge inside the type card
    const ctAvailEls = {
        'PS5':         document.querySelector('#ct-ps5 .ct-avail'),
        'PS4':         document.querySelector('#ct-ps4 .ct-avail'),
        'Xbox Series X': document.querySelector('#ct-xbox .ct-avail'),
    };

    if (!date || !time) {
        el.style.display = 'none';
        // Reset badges to live status (PHP-rendered text stays)
        Object.values(ctAvailEls).forEach(e => { if (e) e.removeAttribute('data-dt-checked'); });
        return;
    }

    el.style.display = 'block';
    el.innerHTML = '<span style="color:#888;font-size:12px;"><i class="fas fa-spinner fa-spin"></i> Checking availability…</span>';
    Object.values(miniEls).forEach(e => { if (e) e.innerHTML = ''; });

    fetch(`ajax/check_availability.php?date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                el.innerHTML = `<span style="color:#fb566b;font-size:12px;"><i class="fas fa-exclamation-circle"></i> ${data.message}</span>`;
                return;
            }

            const av = data.availability;
            let html = '<div style="display:flex;gap:10px;flex-wrap:wrap;">';

            ['PS5','PS4','Xbox Series X'].forEach(type => {
                const info = av[type];
                if (!info) return;
                const ok  = info.available > 0;
                const cls = ok ? 'ok' : 'none';
                html += `<span class="avail-badge ${cls}"><i class="fas fa-${ok ? 'check' : 'xmark'}"></i> ${type}: ${info.available}/${info.total} free at this slot</span>`;

                // Update mini badge in the availability result area
                const miniEl = miniEls[type];
                if (miniEl) miniEl.innerHTML = `<span class="avail-badge ${cls}" style="font-size:10px;">${ok ? info.available + ' free' : 'Full'}</span>`;

                // ── Update the "X free" badge INSIDE the console type card ──
                const ctEl = ctAvailEls[type];
                if (ctEl) {
                    const color = ok ? '#20c8a1' : '#fb566b';
                    const label = ok ? info.available + (info.available === 1 ? ' free' : ' free') : 'None free';
                    ctEl.innerHTML = `<span style="display:inline-block;padding:2px 8px;border-radius:20px;
                                             background:${ok ? 'rgba(32,200,161,.15)' : 'rgba(251,86,107,.15)'};
                                             color:${color};font-size:10px;font-weight:700;
                                             border:1px solid ${ok ? 'rgba(32,200,161,.3)' : 'rgba(251,86,107,.3)'};">
                                        ${label} at this slot
                                     </span>`;
                    ctEl.setAttribute('data-dt-checked', '1');
                }
            });

            html += '</div>';
            el.innerHTML = html;
        })
        .catch(() => { el.innerHTML = '<span style="color:#888;font-size:12px;">Could not check availability</span>'; });
}

/* ── Date & Time banner ─────────────────────────────── */
function updateDtBanner() {
    const dateVal = document.getElementById('reservedDate').value;
    const timeVal = document.getElementById('reservedTime').value;
    const banner  = document.getElementById('dtPreviewBanner');
    const dateWrap = document.getElementById('dateWrap');
    const timeWrap = document.getElementById('timeWrap');

    // Update filled state on wrappers
    if (dateVal) {
        dateWrap.classList.add('dt-filled');
        const d = new Date(dateVal + 'T12:00:00');
        document.getElementById('dateSublabel').textContent =
            d.toLocaleDateString('en-PH', { weekday:'long', month:'long', day:'numeric', year:'numeric' });
    } else {
        dateWrap.classList.remove('dt-filled');
        document.getElementById('dateSublabel').textContent = 'Pick your visit date';
    }

    if (timeVal) {
        timeWrap.classList.add('dt-filled');
        // Format time as 12-hr
        const [hStr, mStr] = timeVal.split(':');
        const h = parseInt(hStr), m = mStr;
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12  = h % 12 || 12;
        document.getElementById('timeSublabel').textContent = `${h12}:${m} ${ampm} selected`;
    } else {
        timeWrap.classList.remove('dt-filled');
        document.getElementById('timeSublabel').textContent = '12:00 PM – 12:00 AM';
    }

    // Show / update banner if both are set
    if (dateVal && timeVal) {
        const d = new Date(dateVal + 'T' + timeVal);
        const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        document.getElementById('dtBannerDay').textContent   = DAYS[d.getDay()];
        document.getElementById('dtBannerDate').textContent  = d.getDate();
        document.getElementById('dtBannerMonth').textContent = MONTHS[d.getMonth()] + ' ' + d.getFullYear();
        const h = d.getHours(), m = String(d.getMinutes()).padStart(2,'0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12  = h % 12 || 12;
        document.getElementById('dtBannerTime').textContent = `${h12}:${m} ${ampm}`;
        banner.style.display = 'flex';
    } else {
        banner.style.display = 'none';
    }
}


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
    const unitEl = document.getElementById('s-unit');
    if (unitEl) {
        unitEl.textContent  = selectedUnitId ? selectedUnitLabel : 'Any available';
        unitEl.style.color  = selectedUnitId ? '#f1a83c' : '#888';
    }
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

    let dpText = 'None';
    if (selectedMode === 'open_time') {
        dpText = 'Pay at session end (bracket pricing)';
    } else if (dp > 0) {
        dpText = '\u20b1' + dp.toFixed(2) + (selectedDpMethod ? ' via ' + selectedDpMethod : ' — method required');
    } else if (selectedMode === 'hourly') {
        dpText = 'None — select a duration above';
    } else if (selectedMode === 'unlimited') {
        dpText = '\u20b1' + unlimitedRate + ' — select payment method';
    }
    document.getElementById('s-dp').textContent = dpText;
}

/* ── Form validation ────────────────────────────────── */
document.getElementById('reserveForm').addEventListener('submit', function(e) {
    if (!selectedConsoleType) { e.preventDefault(); alert('Please select a console type.'); return; }
    if (!selectedMode)        { e.preventDefault(); alert('Please select a rental mode.'); return; }
    if (selectedMode === 'hourly' && !selectedDuration) { e.preventDefault(); alert('Please select a duration.'); return; }

    const dateVal = document.getElementById('reservedDate').value;
    const timeVal = document.getElementById('reservedTime').value;

    if (dateVal && timeVal) {
        // Operating hours check
        if (timeVal < OPEN_TIME) {
            e.preventDefault();
            alert('\u26a0\ufe0f We\u2019re only open from 12:00 PM (noon). Please pick a time between noon and midnight.');
            document.getElementById('reservedTime').focus();
            return;
        }
        if (timeVal > CLOSE_TIME) {
            e.preventDefault();
            alert('\u26a0\ufe0f We close at 12:00 AM (midnight). Please pick a time before midnight.');
            document.getElementById('reservedTime').focus();
            return;
        }

        // 1-hour lead time check
        const chosen   = new Date(dateVal + 'T' + timeVal);
        const earliest = new Date(Date.now() + MIN_LEAD_SECONDS * 1000);
        if (chosen < earliest) {
            e.preventDefault();
            alert('\u26a0\ufe0f Reservations must be at least 1 hour from now. Please pick a later time.');
            document.getElementById('reservedTime').focus();
            return;
        }
    }

    const dp = parseFloat(document.getElementById('dpAmount').value) || 0;
    if (dp > 0 && !selectedDpMethod) { e.preventDefault(); alert('Please select a payment method for your downpayment.'); return; }
});
</script>
</body>
</html>
