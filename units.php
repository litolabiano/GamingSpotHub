<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Units - Gspot Gaming Hub</title>
    <meta name="description" content="Browse our available gaming units with hourly rates. Choose from PC gaming, consoles, VIP rooms, and streaming setups.">
    
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
            <a class="navbar-brand" href="index.php#home">
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
                    <li class="nav-item"><a class="nav-link" href="index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#gaming">Gaming Rates</a></li>
                    <li class="nav-item"><a class="nav-link active" href="units.php">Units</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#events">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
                </ul>
                <div class="nav-cta ms-lg-3">
                    <a href="#Login" class="btn btn-primary">Login</a>
                    <a href="#Register" class="btn btn-primary">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Units Section -->
    <section id="units" class="units-section py-5" style="margin-top: 80px;">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <span class="section-tag">Available Units</span>
                <h2 class="section-title">Gaming Units</h2>
                <p class="section-subtitle">Choose from our premium gaming stations</p>
            </div>
            
            <div class="row g-4">
                <!-- PC Unit 1 -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="unit-card">
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">PC-01</div>
                        <div class="unit-type">
                            <i class="fas fa-desktop"></i>
                            <span>Gaming PC</span>
                        </div>
                        <div class="unit-price">₱50<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-microchip"></i> Intel i7-13700K</li>
                                <li><i class="fas fa-memory"></i> 32GB DDR5 RAM</li>
                                <li><i class="fas fa-hdd"></i> RTX 4070 Ti</li>
                                <li><i class="fas fa-tv"></i> 27" 165Hz Monitor</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- PC Unit 2 -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="unit-card">
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">PC-02</div>
                        <div class="unit-type">
                            <i class="fas fa-desktop"></i>
                            <span>Gaming PC</span>
                        </div>
                        <div class="unit-price">₱50<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-microchip"></i> Intel i7-13700K</li>
                                <li><i class="fas fa-memory"></i> 32GB DDR5 RAM</li>
                                <li><i class="fas fa-hdd"></i> RTX 4070 Ti</li>
                                <li><i class="fas fa-tv"></i> 27" 165Hz Monitor</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- PC Unit 3 -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="unit-card">
                        <div class="unit-status occupied">Occupied</div>
                        <div class="unit-number">PC-03</div>
                        <div class="unit-type">
                            <i class="fas fa-desktop"></i>
                            <span>Gaming PC</span>
                        </div>
                        <div class="unit-price">₱50<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-microchip"></i> AMD Ryzen 9 7900X</li>
                                <li><i class="fas fa-memory"></i> 32GB DDR5 RAM</li>
                                <li><i class="fas fa-hdd"></i> RTX 4070</li>
                                <li><i class="fas fa-tv"></i> 27" 144Hz Monitor</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn disabled">Occupied</a>
                    </div>
                </div>

                <!-- PS5 Unit 1 -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="250">
                    <div class="unit-card featured">
                        <div class="featured-badge">POPULAR</div>
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">PS5-01</div>
                        <div class="unit-type">
                            <i class="fab fa-playstation"></i>
                            <span>PlayStation 5</span>
                        </div>
                        <div class="unit-price">₱60<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-gamepad"></i> PS5 Console</li>
                                <li><i class="fas fa-tv"></i> 55" 4K TV</li>
                                <li><i class="fas fa-headphones"></i> Premium Headset</li>
                                <li><i class="fas fa-couch"></i> Gaming Chair</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- Xbox Unit 1 -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="unit-card">
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">XBOX-01</div>
                        <div class="unit-type">
                            <i class="fab fa-xbox"></i>
                            <span>Xbox Series X</span>
                        </div>
                        <div class="unit-price">₱60<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-gamepad"></i> Xbox Series X</li>
                                <li><i class="fas fa-tv"></i> 55" 4K TV</li>
                                <li><i class="fas fa-headphones"></i> Premium Headset</li>
                                <li><i class="fas fa-couch"></i> Gaming Chair</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- VIP Room -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="350">
                    <div class="unit-card vip">
                        <div class="featured-badge">VIP</div>
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">VIP-01</div>
                        <div class="unit-type">
                            <i class="fas fa-crown"></i>
                            <span>VIP Gaming Room</span>
                        </div>
                        <div class="unit-price">₱100<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-users"></i> Capacity: 4-6 pax</li>
                                <li><i class="fas fa-desktop"></i> 2x Gaming PCs</li>
                                <li><i class="fas fa-gamepad"></i> PS5 & Xbox Series X</li>
                                <li><i class="fas fa-utensils"></i> Free Snacks & Drinks</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- Nintendo Switch Unit -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="unit-card">
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">NSW-01</div>
                        <div class="unit-type">
                            <i class="fas fa-gamepad"></i>
                            <span>Nintendo Switch</span>
                        </div>
                        <div class="unit-price">₱60<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-gamepad"></i> Nintendo Switch OLED</li>
                                <li><i class="fas fa-tv"></i> 43" Full HD TV</li>
                                <li><i class="fas fa-users"></i> 4 Controllers</li>
                                <li><i class="fas fa-couch"></i> Comfortable Seating</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- PC Unit 4 (Premium) -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="450">
                    <div class="unit-card premium">
                        <div class="featured-badge">PREMIUM</div>
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">PC-04</div>
                        <div class="unit-type">
                            <i class="fas fa-desktop"></i>
                            <span>Premium Gaming PC</span>
                        </div>
                        <div class="unit-price">₱75<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-microchip"></i> Intel i9-14900K</li>
                                <li><i class="fas fa-memory"></i> 64GB DDR5 RAM</li>
                                <li><i class="fas fa-hdd"></i> RTX 4090</li>
                                <li><i class="fas fa-tv"></i> 32" 240Hz Monitor</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>

                <!-- Streaming Setup -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="unit-card premium">
                        <div class="featured-badge">STREAMING</div>
                        <div class="unit-status available">Available</div>
                        <div class="unit-number">STR-01</div>
                        <div class="unit-type">
                            <i class="fas fa-video"></i>
                            <span>Streaming Station</span>
                        </div>
                        <div class="unit-price">₱80<span>/hour</span></div>
                        <div class="unit-specs">
                            <h5>Specifications</h5>
                            <ul>
                                <li><i class="fas fa-microchip"></i> Intel i9-14900K</li>
                                <li><i class="fas fa-camera"></i> 4K Webcam & Mic</li>
                                <li><i class="fas fa-lightbulb"></i> RGB Lighting Setup</li>
                                <li><i class="fas fa-tv"></i> Dual 27" Monitors</li>
                            </ul>
                        </div>
                        <a href="#booking" class="unit-book-btn">Book Now</a>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="units-legend mt-5" data-aos="fade-up">
                <div class="legend-item">
                    <span class="legend-indicator available"></span>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <span class="legend-indicator occupied"></span>
                    <span>Occupied</span>
                </div>
            </div>
        </div>
    </section>

    <?php include 'sections/footer.php'; ?>

    <!-- Back to Top Button -->
    <a href="#units" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
