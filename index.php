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
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <div class="logo-container">
                    <span class="logo-g">G</span><span class="logo-s">s</span><span class="logo-p">p</span><span class="logo-o">o</span><span class="logo-t">t</span>
                    <span class="logo-text">GAMING HUB</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#gaming">Gaming</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cafe">Cafe</a></li>
                    <li class="nav-item"><a class="nav-link" href="#events">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                <div class="nav-cta ms-lg-3">
                    <a href="#booking" class="btn btn-primary">Book a PC</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-particles" id="particles-js"></div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="hero-content">
                        <h1 class="hero-title">YOUR ULTIMATE<br><span class="gradient-text">GAMING DESTINATION</span></h1>
                        <p class="hero-subtitle">PLAY. EAT. COMPETE.</p>
                        <p class="hero-description">Your go spot for gaming fun! Dive into Xbox, PlayStation, and Nintendo Switch action in our cozy playful paradise.</p>
                        <div class="hero-buttons">
                            <a href="#booking" class="btn btn-primary btn-lg me-3"><i class="fas fa-gamepad me-2"></i> Book a PC</a>
                            <a href="#cafe" class="btn btn-secondary btn-lg"><i class="fas fa-coffee me-2"></i> View Menu</a>
                        </div>
                        <div class="hero-stats mt-5">
                            <div class="stat-item">
                                <h3 class="stat-number">1.5K</h3>
                                <p class="stat-label">Followers</p>
                            </div>
                            <div class="stat-item">
                                <h3 class="stat-number">20+</h3>
                                <p class="stat-label">Gaming PCs</p>
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
