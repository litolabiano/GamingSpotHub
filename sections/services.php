<?php
require_once __DIR__ . '/../includes/db_functions.php';
$hourlyRate    = (float)(getSetting('ps5_hourly_rate') ?? 80);
$unlimitedRate = (float)(getSetting('unlimited_rate') ?? 400);
?>
<!-- ══ SERVICES / PRICING ═══════════════════════════════════════════════════ -->
<section id="gaming" style="background:linear-gradient(180deg,#0d1b2a 0%,#07101f 100%);padding:90px 0 100px;position:relative;overflow:hidden;">

    <!-- Decorative background blobs -->
    <div style="position:absolute;top:-100px;left:-100px;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(95,133,218,.07),transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-80px;right:-80px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(179,123,236,.07),transparent 70%);pointer-events:none;"></div>

    <div class="container" style="position:relative;z-index:2;">

        <!-- Header -->
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Pricing</span>
            <h2 class="section-title">Gaming Rates</h2>
            <p class="section-subtitle">Flexible plans for every kind of gamer — from quick 30-min sessions to all-day marathons</p>
        </div>

        <!-- Pricing cards -->
        <div class="row g-4 justify-content-center">

            <!-- Open Time -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="50">
                <div class="gsh-price-card">
                    <div class="gsh-price-icon" style="background:linear-gradient(135deg,rgba(32,200,161,.2),rgba(32,200,161,.05));">
                        <i class="fas fa-hourglass-half" style="color:#20c8a1;font-size:1.8rem;"></i>
                    </div>
                    <div class="gsh-price-name">Open Time</div>
                    <div class="gsh-price-amount">
                        <span class="amt-currency">₱</span>20<span class="amt-per">starts at</span>
                    </div>
                    <p class="gsh-price-desc">Pay only for the time you use — perfect for quick sessions and drop-in play.</p>
                    <ul class="gsh-price-perks">
                        <li><i class="fas fa-check" style="color:#20c8a1;"></i> No pre-booking needed</li>
                        <li><i class="fas fa-check" style="color:#20c8a1;"></i> Per-minute bracket billing</li>
                        <li><i class="fas fa-check" style="color:#20c8a1;"></i> Free 30 min after every 2 hrs</li>
                        <li><i class="fas fa-check" style="color:#20c8a1;"></i> Pay when you're done</li>
                    </ul>
                    <a href="#units" class="gsh-price-btn" style="--pc:#20c8a1;">Walk In</a>
                </div>
            </div>

            <!-- Hourly — center / featured -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
                <div class="gsh-price-card featured" style="--accent:#5f85da;">
                    <div class="gsh-price-badge">MOST POPULAR</div>
                    <div class="gsh-price-icon" style="background:linear-gradient(135deg,rgba(95,133,218,.25),rgba(95,133,218,.05));">
                        <i class="fas fa-clock" style="color:#5f85da;font-size:1.8rem;"></i>
                    </div>
                    <div class="gsh-price-name">Hourly</div>
                    <div class="gsh-price-amount" style="background:linear-gradient(135deg,#5f85da,#b37bec);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                        <span class="amt-currency" style="-webkit-text-fill-color:unset;color:#5f85da;">₱</span><?= number_format($hourlyRate,0) ?><span class="amt-per" style="-webkit-text-fill-color:unset;">/hour</span>
                    </div>
                    <p class="gsh-price-desc">Pre-book your exact duration. Earn free time bonuses for longer sessions.</p>
                    <ul class="gsh-price-perks">
                        <li><i class="fas fa-check" style="color:#5f85da;"></i> PS5, PS4 &amp; Xbox Series X</li>
                        <li><i class="fas fa-check" style="color:#5f85da;"></i> Choose your exact duration</li>
                        <li><i class="fas fa-check" style="color:#5f85da;"></i> Free 30 min after every 2 hrs</li>
                        <li><i class="fas fa-check" style="color:#5f85da;"></i> Bracket billing on overtime</li>
                    </ul>
                    <a href="reserve.php" class="gsh-price-btn" style="--pc:#5f85da;background:linear-gradient(135deg,#5f85da,#b37bec);color:#fff;">Reserve Now</a>
                </div>
            </div>

            <!-- Unlimited -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="250">
                <div class="gsh-price-card" style="--accent:#b37bec;">
                    <div class="gsh-price-icon" style="background:linear-gradient(135deg,rgba(179,123,236,.2),rgba(179,123,236,.05));">
                        <i class="fas fa-infinity" style="color:#b37bec;font-size:1.8rem;"></i>
                    </div>
                    <div class="gsh-price-name">Unlimited</div>
                    <div class="gsh-price-amount">
                        <span class="amt-currency">₱</span><?= number_format($unlimitedRate,0) ?><span class="amt-per">/session</span>
                    </div>
                    <p class="gsh-price-desc">Play all day for a flat rate — no time limits, no surprise charges at the end.</p>
                    <ul class="gsh-price-perks">
                        <li><i class="fas fa-check" style="color:#b37bec;"></i> All-day access</li>
                        <li><i class="fas fa-check" style="color:#b37bec;"></i> No overtime charges ever</li>
                        <li><i class="fas fa-check" style="color:#b37bec;"></i> Any available console unit</li>
                        <li><i class="fas fa-check" style="color:#b37bec;"></i> Best for long sessions</li>
                    </ul>
                    <a href="reserve.php" class="gsh-price-btn" style="--pc:#b37bec;">Go Unlimited</a>
                </div>
            </div>

        </div>

        <!-- Bonus info strip -->
        <div class="text-center mt-5" data-aos="fade-up">
            <div style="display:inline-flex;align-items:center;gap:12px;background:rgba(241,225,170,.06);border:1px solid rgba(241,225,170,.2);border-radius:14px;padding:14px 24px;">
                <i class="fas fa-gift" style="color:#f1e1aa;font-size:1.2rem;"></i>
                <span style="color:rgba(255,255,255,.7);font-size:14px;">
                    <strong style="color:#f1e1aa;">Free 30 min</strong> bonus every 2 hours on Hourly &amp; Open Time — no sign-up required.
                </span>
            </div>
        </div>

    </div>
</section>

<style>
.gsh-price-card {
    background: rgba(10,18,40,.75);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 22px;
    padding: 32px 26px 28px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 0;
    transition: transform .3s, border-color .3s, box-shadow .3s;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}
.gsh-price-card:hover {
    transform: translateY(-7px);
    border-color: rgba(255,255,255,.18);
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
}
.gsh-price-card.featured {
    border-color: rgba(95,133,218,.4);
    box-shadow: 0 0 0 1px rgba(95,133,218,.2), 0 20px 60px rgba(95,133,218,.1);
    background: rgba(12,22,52,.85);
}
.gsh-price-badge {
    position: absolute;
    top: 18px; right: 18px;
    background: linear-gradient(135deg,#5f85da,#b37bec);
    color: #fff;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1.2px;
    padding: 4px 10px;
    border-radius: 20px;
}
.gsh-price-icon {
    width: 64px; height: 64px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 18px;
    border: 1px solid rgba(255,255,255,.06);
}
.gsh-price-name {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 6px;
}
.gsh-price-amount {
    font-family: 'Outfit', sans-serif;
    font-size: 2.8rem;
    font-weight: 900;
    color: #20c8a1;
    line-height: 1;
    margin-bottom: 14px;
}
.amt-currency { font-size: 1.4rem; vertical-align: top; margin-top: 8px; display: inline-block; }
.amt-per {
    font-size: .9rem;
    font-weight: 500;
    color: rgba(255,255,255,.35);
    margin-left: 3px;
    -webkit-text-fill-color: rgba(255,255,255,.35);
}
.gsh-price-desc {
    font-size: 13.5px;
    color: rgba(255,255,255,.5);
    line-height: 1.7;
    margin-bottom: 18px;
}
.gsh-price-perks {
    list-style: none;
    padding: 0; margin: 0 0 22px;
    display: flex;
    flex-direction: column;
    gap: 9px;
    flex: 1;
}
.gsh-price-perks li {
    font-size: 13.5px;
    color: rgba(255,255,255,.7);
    display: flex;
    align-items: center;
    gap: 9px;
}
.gsh-price-perks li i { font-size: 11px; flex-shrink: 0; }
.gsh-price-btn {
    display: block;
    text-align: center;
    padding: 13px;
    background: linear-gradient(135deg, var(--pc,#20c8a1), color-mix(in srgb, var(--pc,#20c8a1) 70%, #000 30%));
    color: #fff;
    font-weight: 800;
    font-size: 14px;
    border-radius: 12px;
    text-decoration: none;
    transition: all .25s;
}
.gsh-price-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(0,0,0,.3);
    color: #fff;
}
</style>
