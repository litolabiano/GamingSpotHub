<!-- Events Section -->
<section id="events" class="events-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Tournaments</span>
            <h2 class="section-title">Epic Monthly Events</h2>
            <p class="section-subtitle">Join our community tournaments and win amazing prizes</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <div class="event-card">
                    <div class="event-badge">UPCOMING</div>
                    <div class="event-content">
                        <div class="event-date">
                            <span class="date-day">TBA</span>
                            <span class="date-month">2026</span>
                        </div>
                        <div class="event-info">
                            <h3 class="event-title">Tekken 8 Tournament</h3>
                            <p class="event-description">
                                Come and join our Tekken 8 Tournament! Show off your fighting skills and compete for glory. Registration fee is only ₱250. Platform: PS5.
                            </p>
                            <div class="event-meta">
                                <span><i class="fas fa-peso-sign"></i> ₱250 Registration</span>
                                <span><i class="fab fa-playstation"></i> Platform: PS5</span>
                                <span><i class="fas fa-map-marker-alt"></i> Good Spot Gaming Hub 29 Don Placido Ave. Zone 2 Dasmariñas, Cavite</span>
                            </div>
                            <a href="tournament_register.php" class="btn btn-primary mt-3">
                                <i class="fas fa-trophy me-2"></i>Register Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.events-section {
    background: linear-gradient(180deg, #0D1117 0%, #1A2332 100%);
}

.event-card {
    background: linear-gradient(135deg, rgba(26, 35, 50, 0.8), rgba(13, 17, 23, 0.8));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    height: 100%;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    border-color: var(--color-mint);
    box-shadow: 0 10px 30px rgba(78, 205, 196, 0.3);
}

.event-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #0a2151;
    border: 1px solid var(--color-mint);
    color: var(--color-secondary);
    padding: 0.4rem 1rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
}

.event-content {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

.event-card.small .event-content {
    flex-direction: column;
    gap: 1rem;
}

.event-date {
    background: #0a2151;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 15px;
    text-align: center;
    flex-shrink: 0;
}

.event-date.small {
    padding: 1rem;
}

.date-day {
    display: block;
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--color-secondary);
    line-height: 1;
}

.event-date.small .date-day {
    font-size: 1.8rem;
}

.date-month {
    display: block;
    font-size: 1rem;
    color: var(--color-secondary);
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

.event-info {
    flex: 1;
}

.event-title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--color-light);
}

.event-card.small .event-title {
    font-size: 1.1rem;
}

.event-description {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.event-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.event-meta span {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

.event-meta i {
    color: var(--color-mint);
    margin-right: 0.5rem;
}
</style>
