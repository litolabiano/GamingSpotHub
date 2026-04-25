<?php require_once __DIR__ . '/includes/session_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gspot Gaming Hub - Your Ultimate Gaming Destination in Dasma</title>
    <meta name="description" content="Your go spot for gaming fun! Dive into console gaming action in our cozy playful paradise.">
    
    <!-- Bootstrap CSS -->
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts (local) -->
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">

    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">

    <!-- AOS Animation Library (local) -->
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <?php include __DIR__ . '/includes/navbar.php'; ?>


    <?php
    // --- Hero Stats: pull real counts from the database ---
    require_once __DIR__ . '/includes/db_config.php';

    $r = $conn->query("SELECT COUNT(*) AS cnt FROM consoles");
    if ($r) $stat_consoles = (int) $r->fetch_assoc()['cnt'];

    $r = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer' AND status = 'active'");
    if ($r) $stat_members = (int) $r->fetch_assoc()['cnt'];

    // Build dynamic console-type list from the database
    $r = $conn->query("SELECT DISTINCT console_type FROM consoles ORDER BY console_type");
    $consoleTypes = [];
    while ($row = $r->fetch_assoc()) { $consoleTypes[] = $row['console_type']; }
    // Format: "PS4, PS5 & Xbox Series X"
    if (count($consoleTypes) > 1) {
        $last = array_pop($consoleTypes);
        $consoleList = implode(', ', $consoleTypes) . ' & ' . $last;
    } else {
        $consoleList = $consoleTypes[0] ?? 'console';
    }
    $heroDesc = "Your go spot for gaming fun! Dive into {$consoleList} action in our cozy playful paradise.";
    ?>

    <!-- Dynamic meta injected from DB -->
    <script>document.querySelector('meta[name="description"]').setAttribute('content', <?= json_encode($heroDesc) ?>);</script>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-particles" id="particles-js"></div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="hero-content">
                        <h1 class="hero-title">YOUR ULTIMATE<br><span class="gradient-text">GAMING DESTINATION</span></h1>
                        <p class="hero-subtitle">PLAY. ENJOY. COMPETE.</p>
                        <p class="hero-description"><?= htmlspecialchars($heroDesc) ?></p>
                        <div class="hero-buttons">
                            <a href="#units" class="btn btn-primary btn-lg me-3"><i class="fas fa-gamepad me-2"></i> View Available Units</a>
                            <a href="#gaming" class="btn btn-secondary btn-lg"><i class="fas fa-peso-sign me-2"></i> See Pricing Plans</a>
                        </div>
                        <div class="hero-stats mt-5">
                            <div class="stat-item">
                                <h3 class="stat-number"><?= $stat_consoles ?>+</h3>
                                <p class="stat-label">Gaming Units</p>
                            </div>
                            <div class="stat-item">
                                <h3 class="stat-number"><?= $stat_members ?>+</h3>
                                <p class="stat-label">Members</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5" data-aos="fade-left" data-aos-delay="200">
                    <div class="hero-image">
                        <div class="floating-card card-1"><i class="fab fa-playstation"></i></div>
                        <div class="floating-card card-2"><i class="fab fa-xbox"></i></div>
                        <div class="floating-card card-3"><i class="fas fa-gamepad"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator"><a href="#about"><i class="fas fa-chevron-down"></i></a></div>
    </section>

    <?php include 'sections/about.php'; ?>
    <?php include 'sections/services.php'; ?>
    <?php include 'sections/units.php'; ?>
    <?php include 'sections/events.php'; ?>
    <?php include 'sections/contact.php'; ?>
    <?php include 'sections/footer.php'; ?>

    <!-- Back to Top Button -->
    <a href="#home" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

    <!-- Bootstrap JS (local) -->
    <script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/aos/aos.js"></script>
    <script src="assets/libs/particles/particles.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
