<?php
/**
 * Good Spot Gaming Hub — Reserve a Console
 * Customer-facing reservation page. Login required.
 *
 * Payment flow:
 *  A) Customer fills form → POST → validation → PayMongo GCash redirect
 *  B) PayMongo redirects back → ?paymongo=success&source_id=src_xxx
 *     → verify payment → createReservation() → success flash
 *  B-alt) ?paymongo=failed → show error, let customer retry
 *  C) Fallback: customer uploads screenshot instead (legacy path, kept as option)
 */
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/PayMongoService.php';

requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$user    = getCurrentUser();
$success = '';
$error   = '';

// ── Fetch consoles ────────────────────────────────────────────────────────────
$allConsoles = getConsoles();
$ps5Count    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS5'));
$ps4Count    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS4'));
$xboxCount   = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'Xbox Series X'));
$ps5Maint    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS5'           && $c['status'] === 'maintenance'));
$ps4Maint    = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'PS4'           && $c['status'] === 'maintenance'));
$xboxMaint   = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'Xbox Series X' && $c['status'] === 'maintenance'));
$ps5AllMaint  = $ps5Count  > 0 && $ps5Maint  === $ps5Count;
$ps4AllMaint  = $ps4Count  > 0 && $ps4Maint  === $ps4Count;
$xboxAllMaint = $xboxCount > 0 && $xboxMaint === $xboxCount;

$consolesByType = [];
foreach ($allConsoles as $c) {
    $consolesByType[$c['console_type']][] = [
        'id'          => (int)$c['console_id'],
        'unit'        => $c['unit_number'],
        'name'        => $c['console_name'],
        'status'      => $c['status'],
        'maintenance' => ($c['status'] === 'maintenance'),
    ];
}

$unlimitedRate = getSetting('unlimited_rate') ?? 300;
$gcashNumber   = getSetting('gcash_number')   ?? '09XX-XXX-XXXX';

// ══════════════════════════════════════════════════════════════════════════════
// PATH B — PayMongo Checkout Session redirect-back handler
// ══════════════════════════════════════════════════════════════════════════════
if (!empty($_GET['paymongo'])) {
    $pm_result = $_GET['paymongo'];        // 'success' or 'failed'
    $pending   = $_SESSION['pending_reservation'] ?? null;
    // session_id is stored in the session when we created the checkout session
    $session_id = $pending['session_id'] ?? trim($_GET['session_id'] ?? '');

    if ($pm_result === 'success' && $session_id && $pending) {

        // ── Verify payment via Checkout Session API ───────────────────────────
        // PayMongo may redirect to success_url a split-second before their
        // servers update payment_status to 'paid'. Retry up to 4 times (1s apart).
        $cs         = [];
        $maxRetries = 4;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $cs = PayMongoService::getCheckoutSession($session_id);
            if (!empty($cs['success']) && $cs['payment_status'] === 'paid') {
                break; // confirmed paid — stop polling
            }
            if ($attempt < $maxRetries) {
                sleep(1); // wait 1 second before next check
            }
        }

        if ($cs['success'] && $cs['payment_status'] === 'paid') {

            $payment_id = $cs['payment_id'] ?? null;

            // In test mode PayMongo often doesn't include pay_xxx in the
            // checkout session response. Fall back to the checkout session ID
            // (cs_xxx) so the reference field is never blank.
            $stored_ref = $payment_id ?: $session_id;

            error_log('[Reserve PATH B] payment_id=' . ($payment_id ?? 'null')
                . ' session_id=' . $session_id
                . ' stored_ref=' . $stored_ref);

            // ── Create the reservation in DB ──────────────────────────────────
            $result = createReservation(
                $user['user_id'],
                $pending['console_type'],
                $pending['rental_mode'],
                $pending['planned_minutes'] ?? null,
                $pending['reserved_date'],
                $pending['reserved_time'],
                $pending['notes'] ?: null,
                (float)$pending['dp_amount'],
                'gcash',
                $pending['preferred_unit_id'] ?? null,
                null   // no screenshot — PayMongo handled it
            );

            if ($result['success']) {
                $res_id = $result['reservation_id'];

                // ── Store PayMongo IDs on the reservation row ─────────────────
                // paymongo_source_id  = the Checkout Session ID (cs_xxx) — always available
                // paymongo_payment_id = pay_xxx if returned; else cs_xxx as fallback
                $upd = $conn->prepare(
                    "UPDATE reservations
                        SET paymongo_source_id  = ?,
                            paymongo_payment_id = ?,
                            paymongo_status     = 'paid',
                            downpayment_paid    = 1
                      WHERE reservation_id = ?"
                );
                $upd->bind_param('ssi', $session_id, $stored_ref, $res_id);
                $upd->execute();

                unset($_SESSION['pending_reservation']);
                $_SESSION['reserve_success'] =
                    'Payment confirmed via GCash! Your reservation #' . $res_id .
                    ' has been submitted. A staff member will confirm your slot shortly.';
                header('Location: reserve.php');
                exit;
            } else {
                $error = 'Payment was received but we could not save your reservation: ' .
                         htmlspecialchars($result['message']) .
                         ' — Please contact the shop with your GCash reference.';
            }

        } elseif ($cs['success'] && $cs['payment_status'] === 'unpaid') {
            // Session exists but customer hasn't paid yet
            $error = 'Your payment has not been completed yet. Please try again or contact the shop.';
        } elseif ($cs['success'] && $cs['payment_status'] === 'expired') {
            $error = 'Your checkout session expired. Please go back and try again.';
        } else {
            $error = 'We could not verify your payment. Please try again or use the manual screenshot option below.';
        }

    } elseif ($pm_result === 'failed') {
        $error = 'Payment was cancelled or failed. Please try again below.';
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// PATH A — Initial form POST → validate → PayMongo redirect OR screenshot fallback
// ══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $console_type      = $_POST['console_type']  ?? '';
    $rental_mode       = $_POST['rental_mode']   ?? '';
    $planned_minutes   = ($rental_mode === 'hourly') ? (int)($_POST['planned_minutes'] ?? 0) : null;
    $reserved_date     = $_POST['reserved_date'] ?? '';
    $reserved_time     = $_POST['reserved_time'] ?? '';
    $notes             = trim($_POST['notes']    ?? '');
    $preferred_unit_id = (int)($_POST['preferred_console_id'] ?? 0) ?: null;
    $dp_amount         = (float)($_POST['downpayment_amount']  ?? 0);
    $pay_via           = $_POST['pay_via'] ?? 'paymongo';   // 'paymongo' or 'screenshot'

    // ── Reservation ban check ─────────────────────────────────────────────────
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

    // ── One active reservation limit ──────────────────────────────────────────
    if (!$error) {
        $cntStmt = $conn->prepare(
            "SELECT reservation_id, reserved_date, reserved_time
               FROM reservations
              WHERE user_id = ? AND status IN ('pending','confirmed')
              LIMIT 1"
        );
        $cntStmt->bind_param('i', $user['user_id']);
        $cntStmt->execute();
        $existingRes = $cntStmt->get_result()->fetch_assoc();
        if ($existingRes) {
            $error = 'You already have an active reservation (Reservation #' . $existingRes['reservation_id']
                   . ' on ' . date('M d, Y', strtotime($existingRes['reserved_date']))
                   . ' at ' . date('g:i A', strtotime($existingRes['reserved_time']))
                   . '). Please cancel it first before making a new booking.';
        }
    }

    // ── Field validation ──────────────────────────────────────────────────────
    if (!$error && !in_array($console_type, ['PS5', 'PS4', 'Xbox Series X'])) {
        $error = 'Please select a valid console type.';
    } elseif (!$error && !in_array($rental_mode, ['hourly', 'unlimited'])) {
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
    } elseif (!$error && $reserved_date > date('Y-m-d', strtotime('+1 month'))) {
        $error = 'Reservations can only be made up to 1 month in advance.';
    } elseif (!$error && strtotime($reserved_date . ' ' . $reserved_time) < (time() + 3600)) {
        $error = 'Reservation must be at least 1 hour from now.';
    } elseif (!$error && (int)date('H', strtotime($reserved_time)) < 12) {
        $error = 'Reservations can only be made from 12:00 PM (noon) onwards.';
    } elseif (!$error && $reserved_time > '23:00') {
        $error = 'Reservations must be no later than 11:00 PM.';
    } elseif (!$error && ($_POST['no_refund_agreed'] ?? '') !== '1') {
        $error = 'You must read and agree to the No-Refund Policy before submitting.';
    }

    if (!$error) {

        // ── PATH A1: Pay via PayMongo Checkout Session ────────────────────
        if ($pay_via === 'paymongo') {

            // Build redirect URLs that PayMongo will use after checkout
            $base        = ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST'];
            $script_dir  = rtrim(dirname($_SERVER['PHP_SELF']),'/');
            $success_url = $base . $script_dir . '/reserve.php?paymongo=success';
            $cancel_url  = $base . $script_dir . '/reserve.php?paymongo=failed';

            // Amount must be at least ₱1 (100 centavos)
            $centavos = PayMongoService::pesosToCentavos($dp_amount > 0 ? $dp_amount : 20.0);

            $modeLabel = ($rental_mode === 'unlimited') ? 'Unlimited session' :
                         ($planned_minutes ? round($planned_minutes / 60, 1) . 'hr session' : 'session');
            $desc = 'Reservation fee for ' . $modeLabel . ' on ' . $reserved_date . ' at ' . $reserved_time;

            $pm = PayMongoService::createCheckoutSession(
                $centavos,
                $desc,
                $success_url,
                $cancel_url,
                $user['email']     ?? '',
                $user['full_name'] ?? 'Customer'
            );

            if ($pm['success'] && !empty($pm['checkout_url'])) {
                // Stash reservation data in session so PATH B can pick it up
                $_SESSION['pending_reservation'] = [
                    'console_type'      => $console_type,
                    'rental_mode'       => $rental_mode,
                    'planned_minutes'   => $planned_minutes,
                    'reserved_date'     => $reserved_date,
                    'reserved_time'     => $reserved_time,
                    'notes'             => $notes,
                    'dp_amount'         => $dp_amount,
                    'preferred_unit_id' => $preferred_unit_id,
                    'session_id'        => $pm['session_id'],   // Checkout Session ID
                    'created_at'        => time(),
                ];

                // Redirect to PayMongo hosted checkout page
                header('Location: ' . $pm['checkout_url']);
                exit;

            } else {
                // PayMongo API error — show the error, do not fall into screenshot check
                $error = 'Could not connect to GCash payment gateway: ' .
                         htmlspecialchars($pm['message'] ?? 'Unknown error') .
                         '. Please try again, or use the "pay manually" link to upload a screenshot instead.';
            }
        }

        // ── PATH A2: Screenshot fallback (only when customer explicitly chose it) ──
        if (!$error && $pay_via === 'screenshot') {
            $payment_proof_file = null;
            $upload_error       = '';
            if (!empty($_FILES['payment_proof']['name'])) {
                $file        = $_FILES['payment_proof'];
                $allowed     = ['image/jpeg','image/png','image/gif','image/webp'];
                $allowed_ext = ['jpg','jpeg','png','gif','webp'];
                $maxSize     = 5 * 1024 * 1024;
                $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $upload_error = 'Upload failed. Please try again.';
                } elseif (!in_array($file['type'], $allowed) || !in_array($ext, $allowed_ext)) {
                    $upload_error = 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.';
                } elseif ($file['size'] > $maxSize) {
                    $upload_error = 'File too large. Maximum 5 MB.';
                } else {
                    $destDir  = __DIR__ . '/uploads/payment_proofs/';
                    $filename = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                        $payment_proof_file = $filename;
                    } else {
                        $upload_error = 'Could not save file. Please contact support.';
                    }
                }
            }

            if ($upload_error) {
                $error = $upload_error;
            } elseif (empty($payment_proof_file)) {
                $error = 'Please upload your GCash proof of payment screenshot, or use the GCash button above.';
            } else {
                $result = createReservation(
                    $user['user_id'], $console_type, $rental_mode, $planned_minutes,
                    $reserved_date, $reserved_time,
                    $notes ?: null,
                    $dp_amount, 'gcash',
                    $preferred_unit_id,
                    $payment_proof_file
                );

                if ($result['success']) {
                    $_SESSION['reserve_success'] = 'Your reservation #' . $result['reservation_id'] .
                        ' has been submitted! A staff member will verify your GCash screenshot and confirm it shortly.';
                    header('Location: reserve.php');
                    exit;
                } else {
                    $error = 'Could not save reservation: ' . htmlspecialchars($result['message']);
                }
            }
        }
    }
}

// ── Flash message ─────────────────────────────────────────────────────────────
if (!empty($_SESSION['reserve_success'])) {
    $success = $_SESSION['reserve_success'];
    unset($_SESSION['reserve_success']);
}

// ── My reservations + active check ───────────────────────────────────────────
$myReservations = getMyReservations($user['user_id']);

$activeResCheck = $conn->prepare(
    "SELECT reservation_id, reserved_date, reserved_time, status
       FROM reservations
      WHERE user_id = ? AND status IN ('pending','confirmed')
      LIMIT 1"
);
$activeResCheck->bind_param('i', $user['user_id']);
$activeResCheck->execute();
$activeReservation = $activeResCheck->get_result()->fetch_assoc();

$todayStr    = date('Y-m-d');
$minDateTime = date('Y-m-d\TH:i', strtotime('+1 hour'));

// Pre-selected console type from URL (e.g. reserve.php?console=PS5)
$presetConsole     = '';
$validConsoleTypes = ['PS5', 'PS4', 'Xbox Series X'];
if (!empty($_GET['console'])) {
    $candidate = urldecode(trim($_GET['console']));
    if (in_array($candidate, $validConsoleTypes)) $presetConsole = $candidate;
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
        background: #060d1a;
        padding: 120px 0 70px;
        position: relative;
        overflow: hidden;
    }
    /* Animated mesh canvas — matches homepage */
    .reserve-hero-canvas {
        position: absolute; inset: 0;
        background:
            radial-gradient(ellipse 70% 60% at 75% 30%, rgba(32,200,161,.12) 0%, transparent 60%),
            radial-gradient(ellipse 50% 70% at 15% 75%, rgba(95,133,218,.1) 0%, transparent 55%),
            radial-gradient(ellipse 55% 40% at 50% 110%, rgba(179,123,236,.07) 0%, transparent 55%);
        pointer-events: none;
    }
    /* Grid lines */
    .reserve-hero-canvas::before {
        content:'';
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(95,133,218,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(95,133,218,.05) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    /* Glowing orb */
    .reserve-hero-canvas::after {
        content:'';
        position: absolute;
        top: -15%; right: -8%;
        width: 500px; height: 500px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(32,200,161,.12) 0%, transparent 70%);
        animation: reserveOrb 8s ease-in-out infinite;
    }
    @keyframes reserveOrb {
        0%,100%{transform:translateY(0) scale(1);}
        50%{transform:translateY(-30px) scale(1.04);}
    }
    .reserve-hero h1 { font-family: 'Outfit', sans-serif; font-weight: 900; color: #fff; }
    .reserve-hero p  { color: rgba(255,255,255,.65); }
    /* Hero badge */
    .reserve-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(32,200,161,.1);
        border: 1px solid rgba(32,200,161,.3);
        color: #20c8a1;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 6px 14px;
        border-radius: 50px;
        margin-bottom: 20px;
    }
    .reserve-hero-badge-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #20c8a1;
        animation: reservePulse 1.5s ease-in-out infinite;
    }
    @keyframes reservePulse { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:.35;transform:scale(1.6);} }
    /* Hero stat pills */
    .reserve-hero-stats {
        display: flex;
        gap: 0;
        margin-top: 32px;
        flex-wrap: wrap;
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 14px;
        padding: 16px 20px;
        max-width: 420px;
    }
    .reserve-hero-stat {
        flex: 1;
        text-align: center;
        padding: 0 16px;
        border-right: 1px solid rgba(255,255,255,.07);
    }
    .reserve-hero-stat:first-child { padding-left: 0; }
    .reserve-hero-stat:last-child  { padding-right: 0; border-right: none; }
    .rhs-val {
        font-family: 'Outfit', sans-serif;
        font-size: 1.8rem;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 3px;
    }
    .rhs-lbl {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .7px;
        text-transform: uppercase;
        color: rgba(255,255,255,.35);
    }
    /* Hero right: console cards with float animations */
    .rhero-console-wrap {
        display: flex;
        gap: 14px;
        align-items: stretch;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .rhero-con-card {
        background: rgba(10,18,42,.75);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 18px;
        padding: 22px 18px;
        text-align: center;
        min-width: 110px;
        flex: 1;
        max-width: 140px;
        backdrop-filter: blur(10px);
        transition: transform .3s, border-color .3s;
    }
    .rhero-con-card:hover { transform: translateY(-6px); }
    .rhero-con-card.ps5  { border-color: rgba(95,133,218,.3);  animation: rcFloat1 7s ease-in-out infinite; }
    .rhero-con-card.ps4  { border-color: rgba(241,168,60,.3);  animation: rcFloat2 6s ease-in-out infinite 1s; }
    .rhero-con-card.xbox { border-color: rgba(32,200,161,.35); animation: rcFloat3 8s ease-in-out infinite .5s; }
    @keyframes rcFloat1 { 0%,100%{transform:translateY(0) rotate(-1deg);} 50%{transform:translateY(-14px) rotate(1deg);} }
    @keyframes rcFloat2 { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-10px);} }
    @keyframes rcFloat3 { 0%,100%{transform:translateY(0) rotate(2deg);} 50%{transform:translateY(-16px) rotate(-1deg);} }

    /* ── Form card ──────────────────────────────────────── */
    .reserve-card {
        background: rgba(8,16,38,.75);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 22px;
        padding: 28px;
        backdrop-filter: blur(14px);
        position: relative;
        overflow: hidden;
        transition: border-color .3s, box-shadow .3s;
    }
    .reserve-card:hover {
        border-color: rgba(95,133,218,.25);
        box-shadow: 0 12px 40px rgba(0,0,0,.25);
    }
    /* Step number accent line */
    .reserve-card::before {
        content:'';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, #20c8a1, #5f85da, #b37bec);
        opacity: 0;
        transition: opacity .3s;
    }
    .reserve-card:hover::before { opacity: 1; }
    .reserve-card h2 {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 1.2rem;
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

    /* ── Maintenance state ───────────────────────────── */
    .console-type-card.ct-maintenance {
        border-color: rgba(255,255,255,.06) !important;
        background: rgba(255,255,255,.02) !important;
        cursor: not-allowed !important;
        opacity: .55;
        pointer-events: none;   /* block all mouse events */
    }
    .ct-maint-badge {
        display: inline-block;
        margin-top: 6px;
        font-size: 10px;
        font-weight: 700;
        color: #f1a83c;
        background: rgba(241,168,60,.12);
        border: 1px solid rgba(241,168,60,.3);
        border-radius: 20px;
        padding: 2px 8px;
        letter-spacing: .3px;
    }
    /* Unit card inside unit picker — individual maintenance unit */
    .unit-card-maintenance {
        opacity: .45 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
        border-color: rgba(255,255,255,.06) !important;
        background: rgba(255,255,255,.02) !important;
    }

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

    /* ── Time select ──────────────────────────────── */
    /* Style the select to match dt-native-input */
    #timeSelect {
        width: 100%;
        background: transparent;
        border: none;
        outline: none;
        color: #e0e0e0;
        font-size: 1rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        padding: 0;
        appearance: none;
        -webkit-appearance: none;
    }
    #timeSelect option {
        background: #0d1f3c;
        color: #e0e0e0;
        font-weight: 600;
    }
    #timeSelect option:disabled {
        color: rgba(255,255,255,.22);
        background: #0a1628;
    }
    #timeSelect option[value=""] {
        color: #555;
    }
    </style>

</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero ─────────────────────────────────────────────────────────── -->
<section class="reserve-hero">
    <div class="reserve-hero-canvas"></div>
    <div class="container" style="position:relative;z-index:2;">
        <div class="row align-items-center">

            <!-- Left: copy -->
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="750">
                <div class="reserve-hero-badge">
                    <span class="reserve-hero-badge-dot"></span>
                    Advance Booking
                </div>
                <h1 style="font-size:clamp(2.2rem,5.5vw,3.6rem);line-height:1.1;margin-bottom:16px;letter-spacing:-.5px;">
                    Reserve Your<br>
                    <span style="background:linear-gradient(135deg,#20c8a1,#5f85da);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Gaming Session</span>
                </h1>
                <p style="font-size:1.05rem;max-width:480px;line-height:1.8;">
                    Secure your <?= htmlspecialchars($consoleList ?? 'PS5, PS4 &amp; Xbox Series X') ?> slot in advance.
                    Pick your date, time, and rental mode — and optionally pay a downpayment to lock it in.
                </p>

                <!-- Stat pill strip -->
                <div class="reserve-hero-stats">
                    <div class="reserve-hero-stat">
                        <div class="rhs-val" style="color:#5f85da;"><?= $ps5Count ?></div>
                        <div class="rhs-lbl">PS5 Units</div>
                    </div>
                    <?php if ($ps4Count > 0): ?>
                    <div class="reserve-hero-stat">
                        <div class="rhs-val" style="color:#f1a83c;"><?= $ps4Count ?></div>
                        <div class="rhs-lbl">PS4 Units</div>
                    </div>
                    <?php endif; ?>
                    <div class="reserve-hero-stat">
                        <div class="rhs-val" style="color:#20c8a1;"><?= $xboxCount ?></div>
                        <div class="rhs-lbl">Xbox Units</div>
                    </div>
                    <div class="reserve-hero-stat">
                        <div class="rhs-val" style="color:#b37bec;">∞</div>
                        <div class="rhs-lbl">Future Dates</div>
                    </div>
                </div>
            </div>

            <!-- Right: floating console cards -->
            <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-delay="150" data-aos-duration="800">
                <div class="rhero-console-wrap">
                    <div class="rhero-con-card ps5">
                        <i class="fab fa-playstation" style="font-size:2.8rem;color:#5f85da;"></i>
                        <div style="font-weight:800;color:#fff;margin-top:10px;font-size:15px;">PS5</div>
                        <div style="color:rgba(255,255,255,.35);font-size:11px;margin-top:3px;">₱80/hr</div>
                        <div style="margin-top:10px;font-size:10px;font-weight:700;color:#5f85da;background:rgba(95,133,218,.12);border-radius:8px;padding:3px 8px;"><?= $ps5Count ?> units</div>
                    </div>
                    <?php if ($ps4Count > 0): ?>
                    <div class="rhero-con-card ps4">
                        <i class="fab fa-playstation" style="font-size:2.8rem;color:#f1a83c;"></i>
                        <div style="font-weight:800;color:#fff;margin-top:10px;font-size:15px;">PS4</div>
                        <div style="color:rgba(255,255,255,.35);font-size:11px;margin-top:3px;">₱80/hr</div>
                        <div style="margin-top:10px;font-size:10px;font-weight:700;color:#f1a83c;background:rgba(241,168,60,.12);border-radius:8px;padding:3px 8px;"><?= $ps4Count ?> unit<?= $ps4Count>1?'s':'' ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="rhero-con-card xbox">
                        <i class="fab fa-xbox" style="font-size:2.8rem;color:#20c8a1;"></i>
                        <div style="font-weight:800;color:#fff;margin-top:10px;font-size:15px;">Xbox</div>
                        <div style="color:rgba(255,255,255,.35);font-size:11px;margin-top:3px;">₱80/hr</div>
                        <div style="margin-top:10px;font-size:10px;font-weight:700;color:#20c8a1;background:rgba(32,200,161,.12);border-radius:8px;padding:3px 8px;"><?= $xboxCount ?> units</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── Main Content ──────────────────────────────────────────────────── -->
<section style="padding: clamp(32px, 6vw, 60px) 0 clamp(48px, 8vw, 80px); background: linear-gradient(180deg, #07101f 0%, #060d1a 100%);
    position:relative;overflow:hidden;"
    data-aos-anchor-placement="top-bottom">
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

        <?php if ($activeReservation && !$success): ?>
        <div style="background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.45);border-radius:16px;padding:22px 26px;margin-bottom:30px;display:flex;gap:16px;align-items:flex-start;" data-aos="fade-down">
            <i class="fas fa-exclamation-triangle" style="color:#f1a83c;font-size:1.6rem;margin-top:2px;flex-shrink:0;"></i>
            <div style="flex:1;">
                <div style="font-weight:800;color:#f1a83c;font-size:15px;margin-bottom:6px;">You already have an active reservation</div>
                <div style="color:#ccc;font-size:13px;line-height:1.6;">
                    <strong style="color:#fff;">Reservation #<?= $activeReservation['reservation_id'] ?></strong>
                    is currently <span style="color:#f1a83c;font-weight:700;text-transform:uppercase;font-size:11px;"><?= $activeReservation['status'] ?></span>
                    for <strong style="color:#fff;"><?= date('F j, Y', strtotime($activeReservation['reserved_date'])) ?></strong>
                    at <strong style="color:#fff;"><?= date('g:i A', strtotime($activeReservation['reserved_time'])) ?></strong>.<br>
                    You can only hold <strong style="color:#f1a83c;">one active reservation</strong> at a time.
                    You must cancel your existing reservation first before making a new one.
                </div>
                <a href="dashboard.php#reservations" style="display:inline-flex;align-items:center;gap:8px;margin-top:14px;
                    background:rgba(241,168,60,.15);border:1px solid rgba(241,168,60,.4);color:#f1a83c;
                    padding:9px 18px;border-radius:10px;font-weight:700;font-size:13px;text-decoration:none;
                    transition:all .2s;" onmouseover="this.style.background='rgba(241,168,60,.25)'"
                    onmouseout="this.style.background='rgba(241,168,60,.15)'">
                    <i class="fas fa-calendar-times"></i> Go to My Reservations to Cancel
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-5">

            <!-- ── LEFT: Reservation Form ─────────────────────────── -->
            <div class="col-lg-7" data-aos="fade-up">
                <?php if ($activeReservation && !$success): ?>
                <div style="position:relative;">
                    <div style="position:absolute;inset:0;background:rgba(6,13,26,.65);z-index:10;border-radius:22px;
                        display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;
                        backdrop-filter:blur(2px);">
                        <i class="fas fa-lock" style="font-size:2.5rem;color:rgba(241,168,60,.6);"></i>
                        <div style="color:rgba(255,255,255,.5);font-size:13px;font-weight:600;text-align:center;padding:0 20px;">
                            Cancel your existing reservation to unlock the booking form
                        </div>
                    </div>
                <?php endif; ?>
                <form method="POST" id="reserveForm" enctype="multipart/form-data"
                    <?= ($activeReservation && !$success) ? 'style="pointer-events:none;user-select:none;"' : '' ?>>
                    <input type="hidden" name="console_type"         id="hiddenConsoleType">
                    <input type="hidden" name="rental_mode"          id="hiddenRentalMode">
                    <input type="hidden" name="planned_minutes"      id="hiddenPlannedMinutes">
                    <input type="hidden" name="preferred_console_id" id="hiddenPreferredUnit" value="">

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-desktop"></i> Step 1 — Choose Console Type</h2>
                        <div class="console-type-grid">
                            <?php
                            $ctCards = [
                                ['type'=>'PS5',         'id'=>'ct-ps5',  'icon'=>'fab fa-playstation', 'color'=>'#5f85da', 'label'=>'PlayStation 5', 'count'=>$ps5Count,  'allMaint'=>$ps5AllMaint,  'maintCount'=>$ps5Maint],
                                ['type'=>'PS4',         'id'=>'ct-ps4',  'icon'=>'fab fa-playstation', 'color'=>'#f1a83c', 'label'=>'PlayStation 4', 'count'=>$ps4Count,  'allMaint'=>$ps4AllMaint,  'maintCount'=>$ps4Maint],
                                ['type'=>'Xbox Series X','id'=>'ct-xbox','icon'=>'fab fa-xbox',         'color'=>'#20c8a1', 'label'=>'Xbox Series X', 'count'=>$xboxCount, 'allMaint'=>$xboxAllMaint, 'maintCount'=>$xboxMaint],
                            ];
                            foreach ($ctCards as $ct):
                                if ($ct['count'] === 0) continue; // hide types with no units at all
                                $isMaint = $ct['allMaint'];
                            ?>
                            <div class="console-type-card<?= $isMaint ? ' ct-maintenance' : '' ?>"
                                 id="<?= $ct['id'] ?>"
                                 <?= !$isMaint ? "onclick=\"selectConsoleType('{$ct['type']}')\"" : '' ?>
                                 <?= $isMaint ? 'title="All units of this type are currently under maintenance."' : '' ?>>
                                <div class="ct-icon">
                                    <i class="<?= $ct['icon'] ?>" style="color:<?= $isMaint ? '#444' : $ct['color'] ?>;"></i>
                                </div>
                                <div class="ct-name" style="<?= $isMaint ? 'color:#555;' : '' ?>"><?= $ct['label'] ?></div>
                                <?php if ($isMaint): ?>
                                    <div class="ct-count" style="color:#444;"><?= $ct['count'] ?> units</div>
                                    <div class="ct-maint-badge"><i class="fas fa-tools"></i> Under Maintenance</div>
                                <?php else: ?>
                                    <div class="ct-count"><?= $ct['count'] ?> units</div>
                                    <div class="ct-avail" id="avail-<?= strtolower(str_replace(' ','-',$ct['type'])) ?>"></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
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
                                               max="<?= date('Y-m-d', strtotime('+1 month')) ?>"
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
                                        <!-- Hidden input keeps id=reservedTime so all existing JS works -->
                                        <input type="hidden" id="reservedTime" name="reserved_time"
                                               value="<?= htmlspecialchars($_POST['reserved_time'] ?? '') ?>">
                                        <select id="timeSelect" onchange="onTimeSelect(this)" required>
                                            <option value="" disabled selected>Select time&hellip;</option>
                                        </select>
                                        <div class="dt-field-sublabel" id="timeSublabel">12:00 PM &ndash; 11:00 PM</div>
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
                                Reservation hours &nbsp;<strong style="color:#20c8a1;">12:00 PM</strong> to
                                <strong style="color:#20c8a1;">11:00 PM</strong>
                                &nbsp;&middot;&nbsp; Requires <strong style="color:#ccc;">1 hr lead time</strong>
                            </span>
                        </div>
                        <div id="availabilityResult" style="display:none;margin-top:14px;"></div>
                        <!-- Slot-locked warning: shown when a confirmed reservation blocks the selected slot -->
                        <div id="slotLockedAlert" style="display:none;margin-top:12px;
                            background:rgba(251,86,107,.1);border:1px solid rgba(251,86,107,.4);
                            border-radius:12px;padding:14px 16px;
                            display:none;align-items:flex-start;gap:12px;">
                            <i class="fas fa-lock" style="color:#fb566b;font-size:1.2rem;margin-top:1px;flex-shrink:0;"></i>
                            <div>
                                <div style="font-weight:700;color:#fb566b;font-size:13px;margin-bottom:3px;">Time Slot Unavailable</div>
                                <div id="slotLockedMsg" style="font-size:12px;color:#bbb;line-height:1.5;"></div>
                            </div>
                        </div>
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
                                <div class="mc-desc">Pre-book a fixed duration. Pay reservation fee now.</div>
                            </div>
                            <div class="mode-card" id="mode-unlimited" onclick="selectMode('unlimited')">
                                <div class="mc-icon">♾️</div>
                                <div class="mc-name">Unlimited</div>
                                <div class="mc-desc">Flat ₱<?= $unlimitedRate ?> for unlimited play. Pay reservation fee now.</div>
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

                    <!-- ── Step 4: Reservation Fee / GCash Payment ── -->
                    <div class="reserve-card" style="margin-bottom:24px;" id="dpCard">
                        <h2><i class="fas fa-peso-sign"></i> Step 4 &mdash; Reservation Fee
                            <span style="font-size:12px;font-weight:600;background:rgba(0,174,90,.18);color:#00c96b;border-radius:20px;padding:2px 10px;margin-left:6px;">GCash Only</span>
                        </h2>

                        <!-- Waiting state -->
                        <div id="gcashWaiting" style="text-align:center;padding:28px;color:#555;">
                            <i class="fas fa-mobile-alt" style="font-size:2.2rem;display:block;margin-bottom:10px;color:#333;"></i>
                            <div style="font-size:13px;">Select a rental mode and duration above to see your payment details.</div>
                        </div>

                        <!-- Payment panel (shown once fee is calculated) -->
                        <div id="gcashPanel" style="display:none;">

                            <!-- ── Primary: PayMongo GCash Button ──────────────────── -->
                            <div id="pmGcashBlock" style="
                                background:linear-gradient(135deg,rgba(0,174,90,.14),rgba(32,200,161,.08));
                                border:1.5px solid rgba(0,174,90,.45);
                                border-radius:16px;padding:22px;margin-bottom:16px;">

                                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                                    <div style="background:#00ae5a;border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-mobile-alt" style="color:#fff;font-size:1rem;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:800;color:#00c96b;font-size:14px;">Pay via GCash</div>
                                        <div style="font-size:11px;color:#888;margin-top:2px;">Automatic — you'll be redirected to GCash, then back here.</div>
                                    </div>
                                </div>

                                <!-- Fee summary -->
                                <div style="background:rgba(0,0,0,.25);border-radius:10px;padding:12px 14px;margin-bottom:16px;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#bbb;margin-bottom:4px;">
                                        <span>Base fee</span><span>₱20.00</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#bbb;margin-bottom:6px;">
                                        <span>5% of <span id="feeCostLabel">₱0</span></span>
                                        <span id="feePctAmount">₱0.00</span>
                                    </div>
                                    <div style="border-top:1px solid rgba(255,255,255,.1);padding-top:8px;display:flex;justify-content:space-between;font-size:15px;font-weight:800;color:#20c8a1;">
                                        <span>Total to Pay</span>
                                        <span id="feeTotalLabel">₱0</span>
                                    </div>
                                </div>

                                <!-- Fee locked-in notice — actual submit happens via the main button below -->
                                <div id="feeLockedNotice" style="
                                    background:rgba(0,174,90,.08);border:1px dashed rgba(0,174,90,.35);
                                    border-radius:10px;padding:12px 16px;
                                    display:flex;align-items:center;gap:10px;font-size:12px;color:#888;">
                                    <i class="fas fa-check-circle" style="color:#00c96b;font-size:1.1rem;flex-shrink:0;"></i>
                                    <span>Fee calculated. Click <strong style="color:#20c8a1;">Confirm &amp; Pay via GCash</strong> below to complete your booking.</span>
                                </div>
                                <div style="text-align:center;margin-top:10px;">
                                    <button type="button" onclick="toggleScreenshotFallback()"
                                        style="background:none;border:none;color:#555;font-size:11px;cursor:pointer;text-decoration:underline;">
                                        Pay manually instead (upload screenshot)
                                    </button>
                                </div>
                            </div>

                            <!-- ── Fallback: Screenshot Upload ─────────────────────── -->
                            <div id="screenshotFallback" style="display:none;">
                                <div class="dp-box">
                                    <div class="dp-title">
                                        <span><i class="fas fa-image"></i> Upload GCash Receipt *</span>
                                        <span style="font-size:11px;color:#888;font-weight:400;margin-left:8px;">— manual verification by staff</span>
                                    </div>

                                    <!-- Shop number reference -->
                                    <div style="background:rgba(0,0,0,.2);border-radius:10px;padding:10px 14px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                                        <div>
                                            <div style="font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Send to GCash Number</div>
                                            <div style="font-size:1.1rem;font-weight:900;color:#fff;letter-spacing:1.5px;"><?= htmlspecialchars($gcashNumber) ?></div>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Amount</div>
                                            <div id="gcashAmountDisplay" style="font-size:1.3rem;font-weight:900;color:#20c8a1;">₱0</div>
                                        </div>
                                    </div>

                                    <label for="payment_proof" id="proofUploadLabel" style="
                                        display:block;border:2px dashed rgba(32,200,161,.35);
                                        border-radius:12px;padding:22px;text-align:center;
                                        cursor:pointer;background:rgba(32,200,161,.03);
                                        transition:border-color .2s,background .2s;"
                                        onmouseover="this.style.borderColor='rgba(32,200,161,.7)';this.style.background='rgba(32,200,161,.07)'"
                                        onmouseout="this.style.borderColor='rgba(32,200,161,.35)';this.style.background='rgba(32,200,161,.03)'">
                                        <i class="fas fa-cloud-upload-alt" id="proofUploadIcon" style="font-size:2rem;color:#20c8a1;display:block;margin-bottom:8px;"></i>
                                        <div id="proofFileName" style="font-weight:700;color:#fff;font-size:14px;margin-bottom:4px;">Click to select screenshot</div>
                                        <div style="font-size:11px;color:#666;">JPG, PNG, GIF or WebP &mdash; max 5 MB</div>
                                    </label>
                                    <input type="file" id="payment_proof" name="payment_proof"
                                           accept="image/jpeg,image/png,image/gif,image/webp"
                                           style="display:none;"
                                           onchange="onProofSelected(this)">
                                    <div id="proofPreview" style="display:none;margin-top:14px;text-align:center;">
                                        <img id="proofImg" src="" alt="Receipt Preview"
                                             style="max-width:100%;max-height:220px;border-radius:10px;border:2px solid rgba(32,200,161,.4);">
                                        <div style="font-size:12px;color:#20c8a1;margin-top:8px;font-weight:700;">
                                            <i class="fas fa-check-circle"></i> Screenshot ready to submit
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align:center;margin-top:-6px;margin-bottom:10px;">
                                    <button type="button" onclick="toggleScreenshotFallback()"
                                        style="background:none;border:none;color:#555;font-size:11px;cursor:pointer;text-decoration:underline;">
                                        ← Back to GCash auto-pay
                                    </button>
                                </div>
                            </div>

                            <!-- Hidden inputs -->
                            <input type="hidden" name="downpayment_amount" id="dpAmount" value="0">
                            <input type="hidden" name="downpayment_method" value="gcash">
                            <input type="hidden" name="pay_via" id="payViaInput" value="paymongo">
                        </div>
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
                            <li>A <strong style="color:#fff;">Reservation Fee</strong> of <strong style="color:#fff;">&#8369;20 + 5% of your session cost</strong> is required to confirm every booking.</li>
                            <li>The reservation fee is <strong style="color:#fb566b;">non-refundable</strong> under <em>all</em> circumstances &mdash; including customer-initiated cancellations, and no-shows.</li>
                            <li><strong style="color:#f1a83c;">15-Minute Grace Period:</strong> If you do not arrive within 15 minutes of your reserved start time, your reservation is automatically cancelled and the fee is forfeited.</li>
                            <li>No store credit, GC, or partial refund will be issued in place of the fee.</li>
                            <li>By paying the reservation fee you confirm you have read and accepted these terms.</li>
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

                    <div class="reserve-card" style="margin-bottom:24px;">
                        <h2><i class="fas fa-sticky-note"></i> Step 5 — Notes (Optional)</h2>
                        <textarea name="notes" class="res-input" placeholder="Any special requests? (e.g. preferred controller, specific game ready, group size...)"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>


                    <div class="reserve-summary" id="summaryBox" style="display:none;">
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#20c8a1;margin-bottom:14px;">
                            <i class="fas fa-receipt"></i> Reservation Summary
                        </div>
                        <div class="rs-row"><span class="rs-label">Console</span><span class="rs-value" id="s-console">—</span></div>
                        <div class="rs-row"><span class="rs-label">Date &amp; Time</span><span class="rs-value" id="s-datetime">—</span></div>
                        <div class="rs-row"><span class="rs-label">Mode</span><span class="rs-value" id="s-mode">—</span></div>
                        <div class="rs-row"><span class="rs-label">Duration</span><span class="rs-value" id="s-duration">—</span></div>
                        <div class="rs-row"><span class="rs-label">Reservation Fee</span><span class="rs-value" id="s-dp">—</span></div>
                    </div>

                    <!-- This single button is the last action for BOTH paths (PayMongo & screenshot) -->
                    <button type="submit" class="res-submit-btn" id="submitBtn" disabled>
                        <i class="fas fa-mobile-alt" id="submitBtnIcon"></i>
                        <span id="submitBtnLabel">Confirm &amp; Pay via GCash</span>
                        <i class="fas fa-arrow-right" style="font-size:13px;opacity:.7;"></i>
                    </button>
                    <div style="text-align:center;margin-top:10px;font-size:11px;color:#555;">
                        <i class="fas fa-lock" style="margin-right:4px;"></i>
                        You will be redirected to GCash to complete payment. Your reservation is saved only after payment succeeds.
                    </div>
                </form>
                <?php if ($activeReservation && !$success): ?></div><?php endif; ?>
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
                                    <th>Date &amp; Time</th>
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
        setTimeout(function () {
            selectConsoleType(preset);
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

const unlimitedRate    = <?= (int)$unlimitedRate ?>;
const PRICING          = <?= json_encode(getPricingRules()) ?>;
const CONSOLES_BY_TYPE = <?= json_encode($consolesByType, JSON_UNESCAPED_UNICODE) ?>;

/* ── Time select helpers ────────────────────────── */
// Valid slots 12:00–23:00 in 30-min steps
const TIME_SLOTS = (function () {
    const s = [];
    for (let h = 12; h <= 23; h++) {
        s.push(String(h).padStart(2,'0') + ':00');
        if (h < 23) s.push(String(h).padStart(2,'00') + ':30');
    }
    return s;
})();

function fmtSlot(t) {
    const [h, m] = t.split(':');
    const hh = parseInt(h);
    return `${hh % 12 || 12}:${m} ${hh >= 12 ? 'PM' : 'AM'}`;
}

// Rebuild <select> options; grey (disabled) anything before minT
function buildTimeSelect() {
    const sel    = document.getElementById('timeSelect');
    const hidden = document.getElementById('reservedTime');
    const dateEl = document.getElementById('reservedDate');
    const minT   = getMinTimeForDate(dateEl.value || new Date().toISOString().slice(0,10));
    const curVal = hidden.value;

    // Rebuild all options
    sel.innerHTML = '<option value="" disabled>Select time…</option>';
    TIME_SLOTS.forEach(slot => {
        const opt = document.createElement('option');
        opt.value = slot;
        opt.textContent = fmtSlot(slot);
        if (slot < minT) {
            opt.disabled = true;       // greyed out in browser
            opt.title = slot < OPEN_TIME ? 'Outside operating hours' : 'Within 1-hr lead time';
        }
        opt.selected = (slot === curVal);
        sel.appendChild(opt);
    });

    // If previously selected slot is now disabled, clear it
    if (curVal && curVal < minT) {
        hidden.value = '';
        sel.value = '';
        const wrap = document.getElementById('timeWrap');
        if (wrap) wrap.classList.remove('dt-filled');
    }
}

function onTimeSelect(sel) {
    const hidden = document.getElementById('reservedTime');
    hidden.value = sel.value;
    const wrap = document.getElementById('timeWrap');
    if (wrap) {
        if (sel.value) wrap.classList.add('dt-filled');
        else wrap.classList.remove('dt-filled');
    }
    onDateTimeChange();
    updateDtBanner();
}


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

    if (mode === 'unlimited') {
        // Fee is deterministic for unlimited — show panel immediately
        const pct = Math.round(unlimitedRate * 0.05);
        const fee = 20 + pct;
        setGcashFee(unlimitedRate, pct, fee);
        showGcashPanel();
    } else {
        // Hourly — wait for duration pick
        document.getElementById('dpAmount').value = '0';
        hideGcashPanel();
    }

    updateSummary();
}

/* ── GCash panel helpers ─────────────────────────────── */
function setGcashFee(sessionCost, pct, fee) {
    document.getElementById('dpAmount').value = fee;
    document.getElementById('feeCostLabel').textContent     = '\u20b1' + sessionCost;
    document.getElementById('feePctAmount').textContent     = '\u20b1' + pct.toFixed(2);
    document.getElementById('feeTotalLabel').textContent    = '\u20b1' + fee;
    const gcashDisp = document.getElementById('gcashAmountDisplay');
    if (gcashDisp) gcashDisp.textContent = '\u20b1' + fee;
    const pmBtn = document.getElementById('pmBtnAmount');
    if (pmBtn) pmBtn.textContent = '\u20b1' + fee;
}
function showGcashPanel() {
    document.getElementById('gcashWaiting').style.display = 'none';
    document.getElementById('gcashPanel').style.display   = 'block';
}
function hideGcashPanel() {
    document.getElementById('gcashPanel').style.display   = 'none';
    document.getElementById('gcashWaiting').style.display = 'block';
}

/* ── Proof of payment file preview ──────────────────── */
function onProofSelected(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById('proofFileName').textContent = file.name;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('proofImg').src = e.target.result;
        document.getElementById('proofPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

/* ── Update submit button label when switching pay method ── */
function updateSubmitBtn() {
    const payVia = document.getElementById('payViaInput')?.value || 'paymongo';
    const icon   = document.getElementById('submitBtnIcon');
    const label  = document.getElementById('submitBtnLabel');
    if (!icon || !label) return;
    if (payVia === 'screenshot') {
        icon.className  = 'fas fa-cloud-upload-alt';
        label.innerHTML = 'Submit Reservation';
    } else {
        icon.className  = 'fas fa-mobile-alt';
        label.innerHTML = 'Confirm &amp; Pay via GCash';
    }
}

/* ── Screenshot fallback toggle ──────────────────────── */
function toggleScreenshotFallback() {
    const pmBlock  = document.getElementById('pmGcashBlock');
    const fallback = document.getElementById('screenshotFallback');
    const payVia   = document.getElementById('payViaInput');
    const isShowing = fallback.style.display !== 'none';

    if (isShowing) {
        // Switch back to PayMongo
        fallback.style.display = 'none';
        pmBlock.style.display  = 'block';
        payVia.value           = 'paymongo';
    } else {
        // Show screenshot upload
        pmBlock.style.display  = 'none';
        fallback.style.display = 'block';
        payVia.value           = 'screenshot';
    }
    updateSubmitBtn();
}

/* ── Duration ───────────────────────────────────────── */
function selectDuration(mins) {
    if (mins > 240) { alert('Hourly reservations are limited to 4 hours maximum.'); return; }

    selectedDuration = mins;
    document.getElementById('hiddenPlannedMinutes').value = mins;
    document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector(`.dur-btn[data-mins="${mins}"]`)?.classList.add('selected');

    const btn      = document.querySelector(`.dur-btn[data-mins="${mins}"]`);
    const fullCost = btn ? parseFloat(btn.dataset.cost || 0) : (mins <= 30 ? PRICING.session_min_charge : mins / 60 * PRICING.hourly_rate);

    // Reservation fee = \u20b120 + 5% of session cost
    const pct = Math.round(fullCost * 0.05);
    const fee = 20 + pct;

    setGcashFee(fullCost, pct, fee);
    showGcashPanel();

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

/* ── Policy checkbox ─ no-refund must be ticked to enable Submit ───── */
function handlePolicyChecks() {
    const btn           = document.getElementById('submitBtn');
    const noRefund      = document.getElementById('noRefundCheck');
    const noRefundLabel = document.getElementById('noRefundLabel');

    if (noRefundLabel) {
        noRefundLabel.style.borderColor = noRefund?.checked ? 'rgba(251,86,107,.6)' : 'rgba(255,255,255,.1)';
        noRefundLabel.style.background  = noRefund?.checked ? 'rgba(251,86,107,.08)' : 'rgba(255,255,255,.04)';
    }

    const allAgreed   = noRefund ? noRefund.checked : false;
    btn.disabled      = !allAgreed;
    btn.style.opacity = allAgreed ? '1' : '0.5';
}

/* Alias — the no-refund checkbox calls this by name */
function handleNoRefundCheck() { handlePolicyChecks(); }

/* ── Operating hours + 1-hr lead-time enforcement ──── */
const MIN_LEAD_SECONDS = 3600; // 1 hour
const OPEN_TIME        = '12:00'; // noon
const CLOSE_TIME       = '23:00'; // last bookable slot (11 PM)

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
    // Rebuild the <select> options to reflect the current date's lead time.
    // Invalid slots are automatically greyed out (disabled) in the dropdown.
    buildTimeSelect();
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
    const hidden = document.getElementById('reservedTime');

    // Build the time select first
    buildTimeSelect();

    if (!dateEl.value) {
        // Auto-fill today (or tomorrow if past close)
        const earliest = new Date(Date.now() + MIN_LEAD_SECONDS * 1000);
        const yyyy = earliest.getFullYear();
        const mm   = String(earliest.getMonth() + 1).padStart(2, '0');
        const dd   = String(earliest.getDate()).padStart(2, '0');
        const hh   = String(earliest.getHours()).padStart(2, '0');
        const min  = String(earliest.getMinutes()).padStart(2, '0');
        const leadt = `${hh}:${min}`;

        if (leadt > CLOSE_TIME) {
            const tomorrow = new Date(earliest);
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateEl.value = tomorrow.toISOString().slice(0, 10);
        } else {
            dateEl.value = `${yyyy}-${mm}-${dd}`;
        }
        buildTimeSelect(); // rebuild with correct date
    }

    // Restore POST value display on validation error reload
    if (hidden.value) {
        const sel = document.getElementById('timeSelect');
        if (sel) sel.value = hidden.value;
        const wrap = document.getElementById('timeWrap');
        if (wrap) wrap.classList.add('dt-filled');
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
        document.getElementById('timeSublabel').textContent = '12:00 PM – 11:00 PM';
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
    document.getElementById('s-mode').textContent =
        selectedMode === 'unlimited' ? 'Unlimited' : 'Hourly';

    let durText = '—';
    if (selectedMode === 'hourly' && selectedDuration) {
        const h = Math.floor(selectedDuration/60), m = selectedDuration%60;
        const cost = selectedDuration <= 30 ? PRICING.session_min_charge : selectedDuration/60*PRICING.hourly_rate;
        durText = (h ? h+'h ' : '') + (m ? m+'m ' : '') + '— ₱' + cost.toFixed(0) + ' (session cost)';
    } else if (selectedMode === 'unlimited') {
        durText = 'Unlimited — ₱' + unlimitedRate;
    }
    document.getElementById('s-duration').textContent = durText;

    let dpText = 'None';
    if (dp > 0) {
        dpText = '₱' + dp.toFixed(2) + (selectedDpMethod ? ' via ' + selectedDpMethod : ' — payment method required');
    } else if (selectedMode === 'hourly') {
        dpText = 'Select a duration above';
    } else if (selectedMode === 'unlimited') {
        dpText = 'Select payment method';
    }
    document.getElementById('s-dp').textContent = dpText;
}

/* ── Form validation ────────────────────────────────── */
document.getElementById('reserveForm').addEventListener('submit', function(e) {
    const payVia = document.getElementById('payViaInput')?.value || 'paymongo';

    if (!selectedConsoleType) { e.preventDefault(); alert('Please select a console type.'); return; }
    if (!selectedMode)        { e.preventDefault(); alert('Please select a rental mode.'); return; }
    if (selectedMode === 'hourly' && !selectedDuration) { e.preventDefault(); alert('Please select a duration.'); return; }

    const dateVal = document.getElementById('reservedDate').value;
    const timeVal = document.getElementById('reservedTime').value;

    if (!dateVal || !timeVal) {
        e.preventDefault();
        alert('\u26a0\ufe0f Please select a date and time.');
        return;
    }

    // Operating hours check
    if (timeVal < OPEN_TIME) {
        e.preventDefault();
        alert('\u26a0\ufe0f Reservations are only accepted from 12:00 PM (noon). Please pick a time between 12:00 PM and 11:00 PM.');
        return;
    }
    if (timeVal > CLOSE_TIME) {
        e.preventDefault();
        alert('\u26a0\ufe0f The last bookable time slot is 11:00 PM. Please pick a time at or before 11:00 PM.');
        return;
    }

    // Check if slot is confirmed-locked
    const lockAlert = document.getElementById('slotLockedAlert');
    if (lockAlert && lockAlert.style.display === 'flex' && selectedConsoleType) {
        e.preventDefault();
        alert('\u26a0\ufe0f This time slot is already fully reserved (confirmed) for ' + selectedConsoleType + '. Please choose a different time.');
        return;
    }

    // 1-hour lead time check
    const chosen   = new Date(dateVal + 'T' + timeVal);
    const earliest = new Date(Date.now() + MIN_LEAD_SECONDS * 1000);
    if (chosen < earliest) {
        e.preventDefault();
        alert('\u26a0\ufe0f Reservations must be at least 1 hour from now. Please pick a later time.');
        return;
    }

    const dp = parseFloat(document.getElementById('dpAmount').value) || 0;
    if (!dp) {
        e.preventDefault();
        alert('\u26a0\ufe0f Please select a rental mode and duration to calculate your reservation fee.');
        return;
    }

    // Screenshot path only: require proof file
    if (payVia === 'screenshot') {
        const proofFile = document.getElementById('payment_proof');
        if (!proofFile || !proofFile.files || proofFile.files.length === 0) {
            e.preventDefault();
            alert('\u26a0\ufe0f Please upload your GCash payment screenshot before submitting.');
            return;
        }
    }

    // PayMongo path: show loading state on button
    if (payVia === 'paymongo') {
        const btn   = document.getElementById('submitBtn');
        const label = document.getElementById('submitBtnLabel');
        const icon  = document.getElementById('submitBtnIcon');
        if (btn)   { btn.disabled = true; btn.style.opacity = '.75'; }
        if (icon)  icon.className = 'fas fa-spinner fa-spin';
        if (label) label.textContent = 'Redirecting to GCash…';
    }
});
</script>
</body>
</html>
