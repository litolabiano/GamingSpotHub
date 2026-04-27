<?php
require_once __DIR__ . '/includes/session_helper.php';
if (isLoggedIn() && in_array($_SESSION['role'] ?? '', ['owner', 'shopkeeper'])) {
    header('Location: ' . getBaseUrl() . '/admin.php'); exit;
}
require_once __DIR__ . '/includes/db_config.php';
$r = $conn->query("SELECT COUNT(*) AS cnt FROM consoles"); $stat_consoles = (int)$r->fetch_assoc()['cnt'];
$r = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='customer' AND status='active'"); $stat_members = (int)$r->fetch_assoc()['cnt'];
$r = $conn->query("SELECT COUNT(*) AS cnt FROM gaming_sessions WHERE status='completed'"); $stat_sessions = (int)$r->fetch_assoc()['cnt'];
$r = $conn->query("SELECT DISTINCT console_type FROM consoles ORDER BY console_type");
$consoleTypes = []; while ($row = $r->fetch_assoc()) $consoleTypes[] = $row['console_type'];
if (count($consoleTypes) > 1) { $last = array_pop($consoleTypes); $consoleList = implode(', ', $consoleTypes).' & '.$last; }
else $consoleList = $consoleTypes[0] ?? 'console';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Good Spot Gaming Hub — Console Gaming in Dasma</title>
    <meta name="description" content="Premium PS5, PS4 & Xbox console gaming in Dasmariñas. Book hourly, open time, or unlimited sessions. Walk in or reserve online.">
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    /* ══ HERO ══════════════════════════════════════════════════════════════ */
    .gsh-hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        background: #060d1a;
        overflow: hidden;
        padding-top: 80px;
    }
    /* Animated mesh background */
    .gsh-hero-canvas {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 80% 60% at 70% 20%, rgba(95,133,218,.18) 0%, transparent 60%),
            radial-gradient(ellipse 50% 70% at 10% 80%, rgba(179,123,236,.12) 0%, transparent 55%),
            radial-gradient(ellipse 60% 50% at 50% 110%, rgba(32,200,161,.08) 0%, transparent 55%);
        pointer-events: none;
    }
    /* Grid lines */
    .gsh-hero-canvas::before {
        content:'';
        position:absolute;
        inset:0;
        background-image:
            linear-gradient(rgba(95,133,218,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(95,133,218,.05) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    /* Glowing orb */
    .gsh-hero-canvas::after {
        content:'';
        position:absolute;
        top:-20%;
        right:-10%;
        width:600px;
        height:600px;
        border-radius:50%;
        background:radial-gradient(circle, rgba(95,133,218,.15) 0%, transparent 70%);
        animation: orbFloat 8s ease-in-out infinite;
    }
    @keyframes orbFloat {
        0%,100%{transform:translateY(0) scale(1);}
        50%{transform:translateY(-40px) scale(1.05);}
    }

    /* Badge */
    .gsh-badge {
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
        margin-bottom: 24px;
    }
    .gsh-badge-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #20c8a1;
        animation: pulse 1.5s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:.4;transform:scale(1.6);} }

    /* Hero title */
    .gsh-title {
        font-family: 'Outfit', sans-serif;
        font-size: clamp(2.6rem, 7vw, 5.2rem);
        font-weight: 900;
        line-height: 1.06;
        color: #fff;
        letter-spacing: -1px;
        margin-bottom: 22px;
    }
    .gsh-title .line2 {
        background: linear-gradient(135deg, #20c8a1 0%, #5f85da 50%, #b37bec 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        background-size: 200% auto;
        animation: shimmer 4s linear infinite;
    }
    @keyframes shimmer { 0%{background-position:0% center;} 100%{background-position:200% center;} }

    .gsh-subtitle {
        font-size: clamp(1rem, 2vw, 1.18rem);
        color: rgba(255,255,255,.6);
        line-height: 1.75;
        max-width: 520px;
        margin-bottom: 36px;
    }

    /* CTA buttons */
    .gsh-cta {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 52px;
    }
    .gsh-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, #20c8a1, #17a887);
        color: #06110a;
        font-weight: 800;
        font-size: 15px;
        padding: 14px 28px;
        border-radius: 12px;
        text-decoration: none;
        transition: all .25s;
        box-shadow: 0 4px 24px rgba(32,200,161,.3);
    }
    .gsh-btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 36px rgba(32,200,161,.45);
        color: #06110a;
    }
    .gsh-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.85);
        font-weight: 700;
        font-size: 15px;
        padding: 14px 28px;
        border-radius: 12px;
        text-decoration: none;
        transition: all .25s;
        backdrop-filter: blur(8px);
    }
    .gsh-btn-outline:hover {
        border-color: rgba(95,133,218,.6);
        background: rgba(95,133,218,.1);
        color: #fff;
        transform: translateY(-3px);
    }

    /* Stats row */
    .gsh-stats {
        display: flex;
        gap: 32px;
        flex-wrap: wrap;
    }
    .gsh-stat {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .gsh-stat-val {
        font-family: 'Outfit', sans-serif;
        font-size: 2rem;
        font-weight: 900;
        color: #fff;
        line-height: 1;
    }
    .gsh-stat-lbl {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: rgba(255,255,255,.4);
    }
    .gsh-stat-div {
        width: 1px;
        background: rgba(255,255,255,.1);
        align-self: stretch;
        margin: 4px 0;
    }

    /* Right side console display */
    .gsh-console-showcase {
        position: relative;
        height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* Central glow */
    .gsh-console-showcase::before {
        content:'';
        position:absolute;
        width:300px;height:300px;
        border-radius:50%;
        background:radial-gradient(circle, rgba(95,133,218,.2), transparent 70%);
        animation: orbFloat 6s ease-in-out infinite;
    }
    /* Floating console cards */
    .gsh-con-card {
        position: absolute;
        background: rgba(10,20,50,.7);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 20px;
        padding: 22px 24px;
        backdrop-filter: blur(16px);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        transition: transform .3s, border-color .3s;
        cursor: default;
        min-width: 130px;
        text-align: center;
    }
    .gsh-con-card:hover { transform: translateY(-8px) scale(1.05) !important; }
    .gsh-con-card i { font-size: 2.4rem; }
    .gsh-con-card .cc-name { font-weight: 800; font-size: 14px; color: #fff; }
    .gsh-con-card .cc-price { font-size: 11px; color: rgba(255,255,255,.45); }

    .gsh-con-card.ps5 {
        border-color: rgba(95,133,218,.4);
        box-shadow: 0 0 30px rgba(95,133,218,.12);
        top: 10%; left: 5%;
        animation: float1 7s ease-in-out infinite;
    }
    .gsh-con-card.ps4 {
        border-color: rgba(241,168,60,.35);
        box-shadow: 0 0 30px rgba(241,168,60,.1);
        bottom: 20%; left: 0%;
        animation: float2 6s ease-in-out infinite 1s;
    }
    .gsh-con-card.xbox {
        border-color: rgba(32,200,161,.4);
        box-shadow: 0 0 30px rgba(32,200,161,.12);
        top: 12%; right: 0%;
        animation: float3 8s ease-in-out infinite .5s;
    }
    .gsh-con-card.main {
        border-color: rgba(179,123,236,.4);
        box-shadow: 0 0 50px rgba(179,123,236,.15), inset 0 0 30px rgba(179,123,236,.05);
        width: 160px;
        padding: 30px;
        position: relative;
        top: 0; left: 0;
        z-index: 2;
        animation: float2 7s ease-in-out infinite 2s;
    }
    .gsh-con-card.main i { font-size: 3.5rem; }
    /* Live badge */
    .gsh-live-badge {
        position: absolute;
        top: -14px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #20c8a1, #17a887);
        color: #06110a;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 1.5px;
        padding: 3px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }
    @keyframes float1 { 0%,100%{transform:translateY(0) rotate(-2deg);} 50%{transform:translateY(-18px) rotate(2deg);} }
    @keyframes float2 { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-14px);} }
    @keyframes float3 { 0%,100%{transform:translateY(0) rotate(3deg);} 50%{transform:translateY(-20px) rotate(-2deg);} }

    /* Scroll cue */
    .gsh-scroll {
        position: absolute;
        bottom: 32px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        color: rgba(255,255,255,.3);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: color .2s;
    }
    .gsh-scroll:hover { color: rgba(255,255,255,.7); }
    .gsh-scroll-icon {
        width: 30px; height: 48px;
        border: 2px solid rgba(255,255,255,.15);
        border-radius: 15px;
        position: relative;
    }
    .gsh-scroll-icon::before {
        content:'';
        position:absolute;
        top:6px; left:50%; transform:translateX(-50%);
        width:4px; height:8px;
        background:rgba(255,255,255,.4);
        border-radius:2px;
        animation: scrollBob 1.8s ease-in-out infinite;
    }
    @keyframes scrollBob { 0%,100%{top:6px;opacity:1;} 100%{top:22px;opacity:0;} }

    /* Responsive */
    @media(max-width:991px) {
        .gsh-console-showcase { height: 320px; margin-top: 40px; }
        .gsh-con-card.ps4 { display: none; }
    }
    @media(max-width:576px) {
        .gsh-console-showcase { height: 260px; }
        .gsh-stats { gap: 18px; }
        .gsh-stat-val { font-size: 1.5rem; }
    }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ══ HERO ══════════════════════════════════════════════════════════════════ -->
<section id="home" class="gsh-hero">
    <div class="gsh-hero-canvas"></div>

    <div class="container" style="position:relative;z-index:2">
        <div class="row align-items-center">
            <!-- Left: copy -->
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="800">
                <div class="gsh-badge">
                    <span class="gsh-badge-dot"></span>
                    Now Open · 12PM – 12AM
                </div>
                <h1 class="gsh-title">
                    YOUR ULTIMATE<br>
                    <span class="line2">GAMING DESTINATION</span><br>
                    <span style="font-size:.55em;font-weight:700;color:rgba(255,255,255,.5);-webkit-text-fill-color:unset;background:none;">IN DASMARIÑAS</span>
                </h1>
                <p class="gsh-subtitle">
                    Premium <?= htmlspecialchars($consoleList) ?> console stations in a cozy, competitive atmosphere.
                    Walk in, reserve online, or go unlimited — your call.
                </p>
                <div class="gsh-cta">
                    <a href="#units" class="gsh-btn-primary">
                        <i class="fas fa-gamepad"></i> View Units
                    </a>
                    <a href="reserve.php" class="gsh-btn-outline">
                        <i class="fas fa-calendar-check"></i> Reserve Now
                    </a>
                </div>
                <div class="gsh-stats" data-aos="fade-up" data-aos-delay="200">
                    <div class="gsh-stat">
                        <div class="gsh-stat-val"><?= $stat_consoles ?>+</div>
                        <div class="gsh-stat-lbl">Gaming Units</div>
                    </div>
                    <div class="gsh-stat-div"></div>
                    <div class="gsh-stat">
                        <div class="gsh-stat-val"><?= $stat_members ?>+</div>
                        <div class="gsh-stat-lbl">Members</div>
                    </div>
                    <div class="gsh-stat-div"></div>
                    <div class="gsh-stat">
                        <div class="gsh-stat-val"><?= $stat_sessions ?>+</div>
                        <div class="gsh-stat-lbl">Sessions Played</div>
                    </div>
                </div>
            </div>

            <!-- Right: floating console cards -->
            <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-duration="900">
                <div class="gsh-console-showcase">
                    <!-- Central main card -->
                    <div class="gsh-con-card main">
                        <div class="gsh-live-badge">● LIVE</div>
                        <i class="fas fa-gamepad" style="color:#b37bec"></i>
                        <div class="cc-name">Good Spot</div>
                        <div class="cc-price">Gaming Hub</div>
                    </div>
                    <!-- PS5 -->
                    <div class="gsh-con-card ps5">
                        <i class="fab fa-playstation" style="color:#5f85da"></i>
                        <div class="cc-name">PlayStation 5</div>
                        <div class="cc-price">₱80/hr</div>
                    </div>
                    <!-- PS4 -->
                    <div class="gsh-con-card ps4">
                        <i class="fab fa-playstation" style="color:#f1a83c"></i>
                        <div class="cc-name">PlayStation 4</div>
                        <div class="cc-price">₱80/hr</div>
                    </div>
                    <!-- Xbox -->
                    <div class="gsh-con-card xbox">
                        <i class="fab fa-xbox" style="color:#20c8a1"></i>
                        <div class="cc-name">Xbox Series X</div>
                        <div class="cc-price">₱80/hr</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll cue -->
    <a href="#about" class="gsh-scroll">
        <div class="gsh-scroll-icon"></div>
        Scroll
    </a>
</section>

<?php include 'sections/about.php'; ?>
<?php include 'sections/services.php'; ?>
<?php include 'sections/units.php'; ?>
<?php include 'sections/events.php'; ?>
<?php include 'sections/contact.php'; ?>
<?php include 'sections/footer.php'; ?>

<a href="#home" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script>
AOS.init({ duration: 700, easing: 'ease-out-cubic', once: true, offset: 60 });
// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
});
</script>
</body>
</html>
