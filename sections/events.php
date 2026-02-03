<!-- Events Section -->
<section id="events" class="events-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Tournaments</span>
            <h2 class="section-title">Epic Monthly Events</h2>
            <p class="section-subtitle">Join our community tournaments and win amazing prizes</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="event-card">
                    <div class="event-badge">UPCOMING</div>
                    <div class="event-content">
                        <div class="event-date">
                            <span class="date-day">15</span>
                            <span class="date-month">FEB</span>
                        </div>
                        <div class="event-info">
                            <h3 class="event-title">Valorant Championship 2026</h3>
                            <p class="event-description">
                                Join the biggest Valorant tournament in Dasma! ₱10,000 prize pool and exclusive gaming gear for winners.
                            </p>
                            <div class="event-meta">
                                <span><i class="fas fa-users"></i> 32 Teams</span>
                                <span><i class="fas fa-trophy"></i> ₱10,000 Prize</span>
                                <span><i class="fas fa-clock"></i> 6:00 PM</span>
                            </div>
                            <a href="#register" class="btn btn-primary mt-3">Register Now</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up">
                <div class="event-card small">
                    <div class="event-content">
                        <div class="event-date small">
                            <span class="date-day">22</span>
                            <span class="date-month">FEB</span>
                        </div>
                        <div class="event-info">
                            <h4 class="event-title">Mobile Legends Cup</h4>
                            <p class="event-description">5v5 tournament</p>
                            <div class="event-meta">
                                <span><i class="fas fa-trophy"></i> ₱5,000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-left">
                <div class="event-card small">
                    <div class="event-content">
                        <div class="event-date small">
                            <span class="date-day">28</span>
                            <span class="date-month">FEB</span>
                        </div>
                        <div class="event-info">
                            <h4 class="event-title">CS2 Community Night</h4>
                            <p class="event-description">Casual games & prizes</p>
                            <div class="event-meta">
                                <span><i class="fas fa-trophy"></i> ₱3,000</span>
                            </div>
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
    background: linear-gradient(135deg, var(--color-mint), var(--color-purple));
    color: white;
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
    background: linear-gradient(135deg, var(--color-mint), var(--color-purple));
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
    color: white;
    line-height: 1;
}

.event-date.small .date-day {
    font-size: 1.8rem;
}

.date-month {
    display: block;
    font-size: 1rem;
    color: white;
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
