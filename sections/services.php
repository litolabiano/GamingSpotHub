<?php
// Pull rates from the database
require_once __DIR__ . '/../includes/db_functions.php';
$hourlyRate    = (float)(getSetting('ps5_hourly_rate') ?? 80);
$unlimitedRate = (float)(getSetting('unlimited_rate') ?? 400);
?>
<!-- Services / Pricing Section -->
<section id="gaming" class="services-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Pricing</span>
            <h2 class="section-title">Gaming Rates</h2>
            <p class="section-subtitle">Affordable rates for the ultimate console gaming experience</p>
        </div>
        <div class="row g-4 justify-content-center">

            <!-- Hourly Rate -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="service-title">Hourly</h3>
                    <div class="price-tag">₱<?= number_format($hourlyRate, 0) ?><span>/hour</span></div>
                    <p class="service-description">
                        Pre-book your session time and enjoy uninterrupted gaming on any console.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> PS5, PS4 & Xbox Series X</li>
                        <li><i class="fas fa-check"></i> Choose your duration</li>
                        <li><i class="fas fa-check"></i> Free 30 min after every 2 hrs</li>
                        <li><i class="fas fa-check"></i> Overtime bracket billing</li>
                    </ul>
                    <a href="#booking" class="service-link">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Unlimited Rate -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="service-card featured">
                    <div class="featured-badge">BEST VALUE</div>
                    <div class="service-icon">
                        <i class="fas fa-infinity"></i>
                    </div>
                    <h3 class="service-title">Unlimited</h3>
                    <div class="price-tag">₱<?= number_format($unlimitedRate, 0) ?><span>/session</span></div>
                    <p class="service-description">
                        Play all day for a flat rate — no time limits, no extra charges.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> All-day access</li>
                        <li><i class="fas fa-check"></i> No overtime charges</li>
                        <li><i class="fas fa-check"></i> Any console unit</li>
                        <li><i class="fas fa-check"></i> Best for long sessions</li>
                    </ul>
                    <a href="#booking" class="service-link">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Open Time -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h3 class="service-title">Open Time</h3>
                    <div class="price-tag">₱20<span>starts at</span></div>
                    <p class="service-description">
                        Pay only for the time you use — perfect for quick sessions and casual play.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> No pre-booking needed</li>
                        <li><i class="fas fa-check"></i> Bracket billing (per minute)</li>
                        <li><i class="fas fa-check"></i> Free 30 min every 2 hrs</li>
                        <li><i class="fas fa-check"></i> Pay when you're done</li>
                    </ul>
                    <a href="#booking" class="service-link">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.services-section {
    background: linear-gradient(180deg, #1A2332 0%, #0D1117 100%);
}

.price-tag {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    margin: 1rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.price-tag span {
    font-size: 1.2rem;
    font-weight: 400;
    opacity: 0.8;
}
</style>
