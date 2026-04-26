<?php require_once __DIR__ . '/includes/session_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions — Gspot Gaming Hub</title>
    <meta name="description" content="Terms and Conditions for Gspot Gaming Hub — rules, policies, tournament rules, and usage guidelines.">
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #080e1a; }

        .terms-hero {
            background: linear-gradient(135deg, #0d1117 0%, #0a2151 60%, #1a0a2e 100%);
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden;
        }
        .terms-hero::before {
            content:'';
            position:absolute;inset:0;
            background: radial-gradient(ellipse 70% 60% at 50% 110%, rgba(32,200,161,.15) 0%, transparent 70%);
            pointer-events:none;
        }
        .terms-hero h1 { font-family:'Outfit',sans-serif; font-weight:900; color:#fff; }

        .terms-body { background: linear-gradient(180deg,#0d1117 0%,#0a1020 100%); padding: 60px 0 100px; }

        /* Sticky TOC */
        .toc-card {
            background: rgba(10,33,81,.6);
            border: 1px solid rgba(95,133,218,.2);
            border-radius: 16px;
            padding: 22px;
            position: sticky;
            top: 80px;
            backdrop-filter: blur(12px);
        }
        .toc-card h6 { font-family:'Outfit',sans-serif; font-weight:800; color:#20c8a1; letter-spacing:1px; text-transform:uppercase; font-size:11px; margin-bottom:14px; }
        .toc-link {
            display:block; padding:7px 12px; border-radius:8px; color:rgba(255,255,255,.55);
            font-size:13px; font-weight:500; transition:.2s; border-left:2px solid transparent; margin-bottom:3px;
            text-decoration:none;
        }
        .toc-link:hover, .toc-link.active { color:#20c8a1; border-left-color:#20c8a1; background:rgba(32,200,161,.06); }

        /* Section cards */
        .terms-section {
            background: rgba(10,20,50,.55);
            border: 1px solid rgba(95,133,218,.15);
            border-radius: 18px;
            padding: 32px;
            margin-bottom: 28px;
            scroll-margin-top: 90px;
            backdrop-filter: blur(8px);
        }
        .terms-section-header {
            display:flex; align-items:center; gap:14px; margin-bottom:20px;
            padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,.07);
        }
        .terms-icon {
            width:46px; height:46px; border-radius:12px;
            display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;
        }
        .ti-mint   { background:linear-gradient(135deg,#20c8a1,#5f85da); }
        .ti-coral  { background:linear-gradient(135deg,#fb566b,#f1e1aa); }
        .ti-purple { background:linear-gradient(135deg,#b37bec,#fb566b); }
        .ti-blue   { background:linear-gradient(135deg,#5f85da,#20c8a1); }
        .ti-gold   { background:linear-gradient(135deg,#f1e1aa,#f1a83c); }
        .ti-teal   { background:linear-gradient(135deg,#20c8a1,#b37bec); }

        .terms-section-title { font-family:'Outfit',sans-serif; font-weight:800; font-size:1.2rem; color:#fff; margin:0; }
        .terms-section-title span { font-size:11px; font-weight:600; color:#888; margin-left:8px; text-transform:uppercase; letter-spacing:.5px; }

        .terms-section p, .terms-section li { color:rgba(255,255,255,.75); line-height:1.85; font-size:.97rem; }
        .terms-section ul, .terms-section ol { padding-left:1.4rem; margin:1rem 0; }
        .terms-section li { margin-bottom:.55rem; }
        .terms-section strong { color:#e8eaf0; }

        .hl {
            border-left:3px solid #20c8a1; background:rgba(32,200,161,.07);
            border-radius:0 10px 10px 0; padding:.85rem 1.2rem; margin:1rem 0;
            color:rgba(255,255,255,.85); font-size:.93rem;
        }
        .hl.warn  { border-color:#fb566b; background:rgba(251,86,107,.07); }
        .hl.info  { border-color:#5f85da; background:rgba(95,133,218,.08); }
        .hl.gold  { border-color:#f1a83c; background:rgba(241,168,60,.07); }
        .hl.purple{ border-color:#b37bec; background:rgba(179,123,236,.08); }

        .rate-table { width:100%; border-collapse:collapse; font-size:.9rem; margin:1rem 0; }
        .rate-table th { padding:.6rem 1rem; text-align:left; color:#20c8a1; font-weight:700; border-bottom:1px solid rgba(255,255,255,.1); }
        .rate-table td { padding:.6rem 1rem; border-bottom:1px solid rgba(255,255,255,.05); color:rgba(255,255,255,.8); }
        .rate-table tr:last-child td { border-bottom:none; }

        .last-updated { font-size:12px; color:#555; text-align:center; margin-top:40px; }
    </style>
</head>
<body data-navbar-fixed="true">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<script>
(function(){ var n=document.getElementById('mainNav'); if(n) n.classList.add('scrolled'); })();
</script>

<!-- Hero -->
<section class="terms-hero">
    <div class="container">
        <div class="text-center" data-aos="fade-up">
            <span class="section-tag">Legal</span>
            <h1 style="font-size:clamp(2.2rem,5vw,3.5rem);margin:16px 0 14px;">
                Terms &amp; <span style="color:#20c8a1;">Conditions</span>
            </h1>
            <p style="color:rgba(255,255,255,.65);max-width:560px;margin:0 auto;font-size:1.05rem;line-height:1.7;">
                Please read these terms carefully before using Gspot Gaming Hub's facilities, online reservation system, and tournament services.
            </p>
            <div style="margin-top:20px;display:inline-flex;align-items:center;gap:8px;background:rgba(32,200,161,.1);border:1px solid rgba(32,200,161,.3);border-radius:30px;padding:8px 18px;">
                <i class="fas fa-circle" style="font-size:7px;color:#20c8a1;"></i>
                <span style="font-size:13px;color:#20c8a1;font-weight:600;">Effective Date: April 25, 2026</span>
            </div>
        </div>
    </div>
</section>

<!-- Body -->
<section class="terms-body">
    <div class="container">
        <div class="row g-4">

            <!-- TOC -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="toc-card" data-aos="fade-right">
                    <h6><i class="fas fa-list me-2"></i>Contents</h6>
                    <a href="#sec-general"      class="toc-link">1. General</a>
                    <a href="#sec-use"          class="toc-link">2. Use of Facilities</a>
                    <a href="#sec-accounts"     class="toc-link">3. Accounts</a>
                    <a href="#sec-rates"        class="toc-link">4. Rates &amp; Payments</a>
                    <a href="#sec-reservations" class="toc-link">5. Reservations</a>
                    <a href="#sec-cancel"       class="toc-link">6. Cancellations &amp; Refunds</a>
                    <a href="#sec-tournaments"  class="toc-link">7. Tournaments</a>
                    <a href="#sec-conduct"      class="toc-link">8. Code of Conduct</a>
                    <a href="#sec-equipment"    class="toc-link">9. Equipment</a>
                    <a href="#sec-privacy"      class="toc-link">10. Privacy</a>
                    <a href="#sec-liability"    class="toc-link">11. Liability</a>
                    <a href="#sec-changes"      class="toc-link">12. Changes to Terms</a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">

                <!-- 1. General -->
                <div class="terms-section" id="sec-general" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-mint"><i class="fas fa-info-circle" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">1. General <span>Overview</span></h2>
                    </div>
                    <p>Welcome to <strong>Gspot Gaming Hub</strong> ("the Hub", "we", "us"), located in Dasmariñas, Cavite. By entering our premises, creating an account, making a reservation, or participating in any event or tournament, you ("the Customer", "Player", "User") agree to be bound by these Terms and Conditions.</p>
                    <p>These Terms apply to all services offered by Gspot Gaming Hub, including but not limited to: console gaming rentals, advance reservations, tournaments, and any future services we may introduce.</p>
                    <div class="hl"><i class="fas fa-info-circle me-2" style="color:#20c8a1;"></i>If you do not agree with any part of these Terms, please do not use our facilities or online services.</div>
                </div>

                <!-- 2. Use of Facilities -->
                <div class="terms-section" id="sec-use" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-blue"><i class="fas fa-gamepad" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">2. Use of Facilities <span>Rules</span></h2>
                    </div>
                    <ul>
                        <li>The Hub is open <strong>12:00 PM to 12:00 AM</strong> daily. Operating hours may change at management's discretion with prior notice.</li>

                        <li>All players must pay the applicable gaming rate before or at the start of their session unless a pre-approved credit arrangement is in place.</li>
                        <li>Food and beverages are <strong>allowed</strong> but must be kept away from consoles and equipment. Any damage caused by spills is the responsibility of the customer.</li>
                        <li>Smoking, vaping, and the use of illegal substances are strictly <strong>prohibited</strong> inside and within the immediate vicinity of the Hub.</li>
                        <li>Management reserves the right to deny service or remove any customer for violation of these Terms.</li>
                    </ul>
                </div>

                <!-- 3. Accounts -->
                <div class="terms-section" id="sec-accounts" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-purple"><i class="fas fa-user-shield" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">3. Accounts &amp; Membership <span>Registration</span></h2>
                    </div>
                    <p>Creating an account is <strong>free</strong> and allows access to the online reservation system, session history, and tournament registration.</p>
                    <ul>
                        <li>You must provide accurate, complete, and current information when registering.</li>
                        <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
                        <li>You must not share your account with other individuals.</li>
                        <li>We reserve the right to suspend or permanently ban accounts that violate these Terms.</li>
                        <li>Walk-in customers without accounts may use the Hub on a <strong>first-come, first-served</strong> basis but cannot make advance reservations or join online tournaments.</li>
                    </ul>
                </div>

                <!-- 4. Rates & Payments -->
                <div class="terms-section" id="sec-rates" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-gold"><i class="fas fa-peso-sign" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">4. Rates &amp; Payments <span>Pricing</span></h2>
                    </div>
                    <p>All prices are in <strong>Philippine Peso (₱)</strong>. Current standard rates are:</p>
                    <table class="rate-table">
                        <thead><tr><th>Mode</th><th>Rate</th><th>Notes</th></tr></thead>
                        <tbody>
                            <tr><td>First 30-min payment</td><td>₱50</td><td>Starter rate — only when first payment covers 30 min</td></tr>
                            <tr><td>Standard hourly (1 hr+)</td><td>₱80 / hr</td><td>When first payment covers 1 hour or more</td></tr>
                            <tr><td>30-min extension</td><td>₱40</td><td>Each additional 30-min block added after initial payment</td></tr>
                            <tr><td>Unlimited session</td><td>₱300</td><td>Flat rate for unlimited play (subject to change)</td></tr>
                        </tbody>
                    </table>
                    <div class="hl gold"><i class="fas fa-gift me-2" style="color:#f1a83c;"></i><strong>Free Time Bonus:</strong> For every <strong>2 hours</strong> paid in a single session, customers earn <strong>30 minutes of free gaming time</strong> automatically.</div>
                    <ul>
                        <li>Rates are subject to change. Updated rates will be posted on-site and on this website.</li>
                        <li>Accepted payment methods: <strong>Cash</strong> and <strong>GCash</strong>.</li>
                        <li>All payments are final once a session has begun.</li>
                    </ul>
                </div>

                <!-- 5. Reservations -->
                <div class="terms-section" id="sec-reservations" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-teal"><i class="fas fa-calendar-check" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">5. Reservations <span>Advance Booking</span></h2>
                    </div>
                    <ul>
                        <li>Reservations must be made at least <strong>1 hour in advance</strong> through the online reservation system.</li>
                        <li>Reservation slots are available from <strong>12:00 PM to 11:59 PM</strong> only.</li>
                        <li>A reservation fee (downpayment) may be collected to secure your slot. This fee is <strong>credited toward your total session cost</strong> — it is not an extra charge.</li>
                        <li>Customers are allowed a maximum of <strong>3 simultaneous active reservations</strong>, with full payment guaranteed.</li>
                        <li>The Hub reserves the right to reassign a unit if the customer has not arrived within <strong>15 minutes</strong> of their reserved time without prior notice.</li>
                        <li>Consoles under maintenance (e.g., PlayStation 4 if marked unavailable) cannot be selected for reservation until restored by management.</li>
                    </ul>
                    <div class="hl info"><i class="fas fa-clock me-2" style="color:#5f85da;"></i>Reservation slots cannot be transferred to another person. The account holder must be present.</div>
                </div>

                <!-- 6. Cancellations -->
                <div class="terms-section" id="sec-cancel" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-coral"><i class="fas fa-rotate-left" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">6. Cancellations &amp; Refunds <span>Policy</span></h2>
                    </div>
                    <ul>
                        <li><strong>Before session starts:</strong> Cancelling before your reserved start time entitles you to a <strong>full 100% refund</strong> of the reservation fee.</li>
                        <li><strong>After session starts:</strong> Cancelling after your booked time has begun will incur an <strong>inconvenience fee</strong> deducted from the reservation fee. The remaining balance will be refunded.</li>
                    </ul>
                    <div class="hl warn"><i class="fas fa-ban me-2" style="color:#fb566b;"></i><strong>3-Strike Rule:</strong> Accounts with <strong>3 consecutive cancelled reservations</strong> will be placed on a <strong>1-week reservation ban</strong>. The counter resets after a successfully completed session.</div>
                    <ul>
                        <li>Refunds are processed within <strong>1–3 business days</strong>. Cash refunds may be settled on the same day at the front desk.</li>
                        <li>Suspension appeals may be submitted to management on a case-by-case basis.</li>
                    </ul>
                </div>

                <!-- 7. Tournaments -->
                <div class="terms-section" id="sec-tournaments" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-gold"><i class="fas fa-trophy" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">7. Tournaments <span>Competition Rules</span></h2>
                    </div>
                    <p>Gspot Gaming Hub hosts regular tournaments open to registered members. By registering for a tournament, you agree to the following:</p>
                    <ul>
                        <li>All participants must <strong>register before the registration deadline</strong>. Late entries are not guaranteed unless approved by management.</li>
                        <li>Tournament registration fees are <strong>non-refundable</strong> once the tournament has officially started, unless management explicitly decides otherwise.</li>
                        <li>Players must be present at their assigned time slot. Failure to appear will result in a <strong>forfeit</strong>.</li>
                    </ul>

                    <div class="hl purple">
                        <i class="fas fa-exclamation-circle me-2" style="color:#b37bec;"></i>
                        <strong>Late Arrival &amp; Bracket Reset Policy:</strong> In exceptional cases, if a player arrives after the first elimination round has concluded but before the tournament significantly progresses, management may — at their sole discretion — allow a <strong>bracket reset</strong> for the affected round, provided that <strong>all existing winners of that round expressly agree</strong> to replay. This is a goodwill gesture only and is <strong>not guaranteed</strong>. It requires unanimous consent from the affected players and boss/management approval. No compensation is owed to winners who agree to reset.
                    </div>

                    <ul>
                        <li>All tournament decisions made by the referee or management are <strong>final</strong>.</li>
                        <li>Cheating, exploiting glitches intentionally, unsportsmanlike conduct, or any form of harassment will result in <strong>immediate disqualification</strong> and possible account suspension.</li>
                        <li>Prize distributions (cash, credits, or other rewards) will be released on the same day or within 24 hours after the tournament concludes, subject to prize availability.</li>
                        <li>Gspot Gaming Hub reserves the right to modify tournament formats, rules, and schedules with reasonable notice.</li>
                        <li>Participants may not stream or record tournament matches without prior written consent from management.</li>
                    </ul>
                </div>

                <!-- 8. Conduct -->
                <div class="terms-section" id="sec-conduct" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-purple"><i class="fas fa-handshake" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">8. Code of Conduct <span>Behavior</span></h2>
                    </div>
                    <p>All customers are expected to maintain a respectful environment for everyone. The following are strictly prohibited:</p>
                    <ul>
                        <li>Verbal or physical harassment, bullying, or discrimination of any kind toward other players or staff.</li>
                        <li>Loud or disruptive behavior that negatively impacts other customers' experience.</li>
                        <li>Unauthorized access to other customers' accounts, consoles, or personal belongings.</li>
                        <li>Any form of theft, vandalism, or property damage.</li>
                        <li>Sharing or distributing offensive, illegal, or inappropriate content on Hub equipment.</li>
                    </ul>
                    <div class="hl warn"><i class="fas fa-gavel me-2" style="color:#fb566b;"></i>Violations may result in immediate removal from the premises, suspension or permanent ban of your account, and/or referral to appropriate authorities.</div>
                </div>

                <!-- 9. Equipment -->
                <div class="terms-section" id="sec-equipment" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-blue"><i class="fas fa-tv" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">9. Equipment &amp; Maintenance <span>Hardware</span></h2>
                    </div>
                    <ul>
                        <li>Customers must handle all consoles, controllers, and peripherals <strong>with care</strong>.</li>
                        <li>Any damage to equipment caused by negligence, misuse, or intentional harm will be <strong>charged to the responsible party</strong> at the cost of repair or replacement.</li>
                        <li>Consoles marked as <strong>"Under Maintenance"</strong> are not available for use or reservation until cleared by staff. Do not attempt to use a unit that has been locked or tagged as unavailable.</li>
                        <li>Report any equipment malfunction to staff immediately. Do not attempt to self-repair any Hub equipment.</li>
                        <li>The Hub is not responsible for saved game data loss. Customers are encouraged to back up their data to personal cloud accounts where possible.</li>
                    </ul>
                </div>

                <!-- 10. Privacy -->
                <div class="terms-section" id="sec-privacy" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-teal"><i class="fas fa-shield-alt" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">10. Privacy <span>Data Policy</span></h2>
                    </div>
                    <p>By creating an account, you consent to the collection and use of your personal information (name, email, session history) for the purpose of:</p>
                    <ul>
                        <li>Managing your account and reservations.</li>
                        <li>Sending service-related notifications (e.g., reservation confirmations).</li>
                        <li>Improving Hub services and enforcing these Terms.</li>
                    </ul>
                    <div class="hl"><i class="fas fa-lock me-2" style="color:#20c8a1;"></i>We do not sell or share your personal data with third parties. Your information is stored securely and used solely for Hub operations.</div>
                </div>

                <!-- 11. Liability -->
                <div class="terms-section" id="sec-liability" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-coral"><i class="fas fa-scale-balanced" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">11. Limitation of Liability <span>Disclaimer</span></h2>
                    </div>
                    <ul>
                        <li>Gspot Gaming Hub is <strong>not liable</strong> for any loss, theft, or damage to personal belongings brought into the premises.</li>
                        <li>We are not responsible for any personal injury sustained on the premises due to the customer's own negligence.</li>
                        <li>In cases of system outage, power failure, or equipment breakdown that interrupts a paid session, affected customers will be compensated with equivalent gaming time at management's discretion.</li>
                        <li>The Hub's maximum liability in any dispute shall not exceed the amount paid by the customer for the specific session or service in question.</li>
                    </ul>
                </div>

                <!-- 12. Changes -->
                <div class="terms-section" id="sec-changes" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-mint"><i class="fas fa-file-pen" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">12. Changes to Terms <span>Updates</span></h2>
                    </div>
                    <p>Gspot Gaming Hub reserves the right to update or modify these Terms and Conditions at any time. Changes will be effective upon posting to this page with an updated effective date.</p>
                    <p>Continued use of our facilities or online services after any modification constitutes your acceptance of the new terms. We encourage you to review this page periodically.</p>
                    <div class="hl"><i class="fas fa-envelope me-2" style="color:#20c8a1;"></i>For questions or concerns about these Terms, please visit our <a href="index.php#contact" style="color:#20c8a1;">Contact</a> page or speak with our staff directly.</div>
                </div>

                <p class="last-updated">Last updated: April 25, 2026 &nbsp;·&nbsp; Gspot Gaming Hub, Dasmariñas, Cavite</p>
            </div>
        </div>
    </div>
</section>

<?php include 'sections/footer.php'; ?>
<a href="#" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    AOS.init({ duration: 700, once: true, offset: 60 });

    // TOC active link highlighting on scroll
    const sections = document.querySelectorAll('.terms-section[id]');
    const tocLinks = document.querySelectorAll('.toc-link');
    window.addEventListener('scroll', function () {
        let current = '';
        sections.forEach(s => {
            if (window.scrollY >= s.offsetTop - 120) current = s.id;
        });
        tocLinks.forEach(l => {
            l.classList.remove('active');
            if (l.getAttribute('href') === '#' + current) l.classList.add('active');
        });
    });
});
</script>
</body>
</html>
