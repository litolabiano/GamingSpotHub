<?php require_once __DIR__ . '/includes/session_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gspot Gaming Hub - Your Ultimate Gaming Destination in Dasma</title>
    <meta name="description" content="Your go spot for gaming fun! Dive into Xbox, PlayStation, and Nintendo Switch action in our cozy playful paradise.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!--AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <?php include __DIR__ . '/includes/navbar.php'; ?>


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
                        <p class="hero-description">Your go spot for gaming fun! Dive into Xbox, PlayStation, and Nintendo Switch action in our cozy playful paradise.</p>
                        <div class="hero-buttons">
                            <a href="#booking" class="btn btn-primary btn-lg me-3"><i class="fas fa-gamepad me-2"></i> View Available Units</a>
                            <a href="#pricing" class="btn btn-secondary btn-lg"><i class="fas fa-peso-sign me-2"></i> See Pricing Plans</a>
                        </div>
                        <div class="hero-stats mt-5">
                            <div class="stat-item">
                                <h3 class="stat-number">100+</h3>
                                <p class="stat-label">Games</p>
                            </div>
                            <div class="stat-item">
                                <h3 class="stat-number">20+</h3>
                                <p class="stat-label">Gaming Units</p>
                            </div>
                            <div class="stat-item">
                                <h3 class="stat-number">24/7</h3>
                                <p class="stat-label">Open Hours</p>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
