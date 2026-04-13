<!-- About Section -->
<section id="about" class="about-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
                <div class="section-header mb-4">
                    <span class="section-tag">About Us</span>
                    <h2 class="section-title">Welcome to Gspot Gaming Hub</h2>
                </div>
                <p class="about-text">
                    Located in the heart of Dasma, we're more than just a gaming cafe - we're a community hub where gamers come together to play, compete, and connect.
                </p>
                <p class="about-text">
                    Whether you're into competitive esports, casual gaming with friends, or just looking for a cozy spot to enjoy great food and games, we've got you covered.
                </p>
                <div class="about-features mt-4">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <div class="feature-content">
                            <h4>High-End Gaming PCs</h4>
                            <p>Latest hardware for smooth gaming experience</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="feature-content">
                            <h4> Tournaments</h4>
                            <p>Compete and win amazing prizes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="about-image">
                    <div id="aboutCarousel" class="carousel slide" data-bs-ride="carousel">
                        <!-- Indicators -->
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#aboutCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#aboutCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#aboutCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            <button type="button" data-bs-target="#aboutCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
                        </div>
                        
                        <!-- Slides -->
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="assets/images/1.png" alt="Gspot Gaming Hub Interior 1" class="d-block w-100 img-fluid rounded shadow-lg">
                            </div>
                            <div class="carousel-item">
                                <img src="assets/images/2.png" alt="Gspot Gaming Hub Interior 2" class="d-block w-100 img-fluid rounded shadow-lg">
                            </div>
                            <div class="carousel-item">
                                <img src="assets/images/3.png" alt="Gspot Gaming Hub Interior 3" class="d-block w-100 img-fluid rounded shadow-lg">
                            </div>
                            <div class="carousel-item">
                                <img src="assets/images/4.png" alt="Gspot Gaming Hub Interior 4" class="d-block w-100 img-fluid rounded shadow-lg">
                            </div>
                        </div>
                        
                        <!-- Controls -->
                        <button class="carousel-control-prev" type="button" data-bs-target="#aboutCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#aboutCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.about-section {
    background: linear-gradient(180deg, #0D1117 0%, #1A2332 100%);
}

.about-text {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.8;
    margin-bottom: 1.5rem;
}

.about-features {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    transition: all 0.3s ease;
}

.feature-item:hover {
    background: rgba(78, 205, 196, 0.05);
    border-color: rgba(78, 205, 196, 0.2);
    transform: translateX(10px);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--color-mint), var(--color-purple));
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feature-icon i {
    font-size: 1.5rem;
    color: white;
}

.feature-content h4 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: var(--color-light);
}

.feature-content p {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
}

.about-image img {
    border: 2px solid rgba(78, 205, 196, 0.2);
}
</style>
