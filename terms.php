<?php require_once __DIR__ . '/includes/session_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions — Gspot Gaming Hub</title>
    <meta name="description" content="Terms and Conditions for Gspot Gaming Hub — rules, policies, reservation guidelines, and usage terms.">
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html { scroll-behavior: smooth; }
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
            <span class="section-tag">System Policies</span>
            <h1 style="font-size:clamp(2.2rem,5vw,3.5rem);margin:16px 0 14px;">
                Terms &amp; <span style="color:#20c8a1;">Conditions</span>
            </h1>
            <p style="color:rgba(255,255,255,.65);max-width:560px;margin:0 auto;font-size:1.05rem;line-height:1.7;">
                These terms govern your use of the Gspot Gaming Hub reservation system, facilities, and services. Please read them carefully.
            </p>
            <div style="margin-top:20px;display:inline-flex;align-items:center;gap:8px;background:rgba(32,200,161,.1);border:1px solid rgba(32,200,161,.3);border-radius:30px;padding:8px 18px;">
                <i class="fas fa-circle" style="font-size:7px;color:#20c8a1;"></i>
                <span style="font-size:13px;color:#20c8a1;font-weight:600;">Effective Date: May 10, 2026</span>
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
                    <a href="#sec-general"      class="toc-link">1. General Terms</a>
                    <a href="#sec-reservations" class="toc-link">2. Reservations</a>
                    <a href="#sec-sessions"     class="toc-link">3. Session Terms</a>
                    <a href="#sec-noshow"       class="toc-link">4. No Show Policy</a>
                    <a href="#sec-reschedule"   class="toc-link">5. Reschedule Policy</a>
                    <a href="#sec-cancel"       class="toc-link">6. Cancellation Policy</a>
                    <a href="#sec-tournaments"  class="toc-link">7. Tournament Terms</a>
                    <a href="#sec-payments"     class="toc-link">8. Payment Terms</a>
                    <a href="#sec-controllers"  class="toc-link">9. Controller Rental</a>
                    <a href="#sec-conduct"      class="toc-link">10. User Conduct</a>
                    <a href="#sec-accounts"     class="toc-link">11. Account Terms</a>
                    <a href="#sec-privacy"      class="toc-link">12. Privacy Policy</a>
                    <a href="#section-13-staff-code-of-conduct" class="toc-link">13. Staff Code of Conduct</a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">

                <!-- 1. General Terms -->
                <div class="terms-section" id="sec-general" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-mint"><i class="fas fa-info-circle" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">1. General Terms <span>Overview</span></h2>
                    </div>
                    <ul>
                        <li>Users must create and verify an account before making any reservation or joining a tournament.</li>
                        <li>Users must provide accurate and truthful information during account registration.</li>
                        <li>The system is operated by Gspot Gaming Hub, and all decisions made by the Admin or Shopkeeper are final.</li>
                        <li>The shop reserves the right to update the Terms and Conditions at any time without prior notice.</li>
                        <li>Continued use of the system after any updates constitutes acceptance of the new terms.</li>
                    </ul>
                </div>

                <!-- 2. Reservation Terms -->
                <div class="terms-section" id="sec-reservations" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-blue"><i class="fas fa-calendar-check" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">2. Reservation Terms <span>Advance Booking</span></h2>
                    </div>
                    <ul>
                        <li>Reservations can be made through the User reservation page (<code>reserve.php</code>) by selecting a console type, preferred unit, date, time, session mode, and duration.</li>
                        <li>The availability of console units is dynamic based on the selected date and time — only units currently available for that specific slot will be shown.</li>
                        <li>A reservation fee must be paid via GCash through PayMongo before a reservation is confirmed.</li>
                        <li>The reservation fee is calculated as a <strong>₱20.00 base fee plus 5% of the total session cost</strong>.</li>
                        <li>Once payment is completed, the reservation status is immediately set to <strong>'Reserved'</strong>. No further admin approval is required.</li>
                        <li>The reservation fee is <strong style="color:#c21c02;">strictly non-refundable</strong> under any circumstances, including cancellations and no-shows.</li>
                        <li>Reservations are only permitted within the shop's operating hours: 12:00 PM to 12:00 AM.</li>
                        <li>Reservations can be made up to 1 month in advance from the current date.</li>
                        <li>Users must arrive at the shop on their reserved date and time, as the session will auto-start at the scheduled time.</li>
                        <li>Users are responsible for arriving on time; late arrivals will not result in any extension of the reserved session time.</li>
                        <li>Users must agree to these Terms and Conditions by checking the required checkbox before completing the reservation payment.</li>
                    </ul>
                </div>

                <!-- 3. Session Terms -->
                <div class="terms-section" id="sec-sessions" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-purple"><i class="fas fa-play-circle" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">3. Session Terms <span>Gameplay Rules</span></h2>
                    </div>
                    <ul>
                        <li>Reserved sessions **start automatically** at the scheduled time without manual intervention.</li>
                        <li>Walk-in sessions can be created manually by the Admin or Shopkeeper at the counter.</li>
                        <li>Available session modes are **Hourly** and **Unlimited**, subject to time-based restrictions.</li>
                        <li>**Unlimited session mode** is NOT available for bookings made at 7:00 PM or later.</li>
                        <li>Maximum session duration is restricted based on the time of booking to ensure all play ends before the 12:00 AM closing time:</li>
                    </ul>
                    <table class="rate-table">
                        <thead><tr><th>Booking Time</th><th>Maximum Duration</th></tr></thead>
                        <tbody>
                            <tr><td>7:00 PM</td><td>5 hours</td></tr>
                            <tr><td>7:30 PM</td><td>4 hours 30 minutes</td></tr>
                            <tr><td>8:30 PM</td><td>3 hours 30 minutes</td></tr>
                            <tr><td>9:00 PM</td><td>3 hours</td></tr>
                            <tr><td>9:30 PM</td><td>2 hours 30 minutes</td></tr>
                            <tr><td>10:30 PM</td><td>1 hour 30 minutes</td></tr>
                            <tr><td>11:00 PM</td><td>1 hour</td></tr>
                            <tr><td>11:30 PM</td><td>30 minutes</td></tr>
                        </tbody>
                    </table>
                    <ul>
                        <li>The session fee for hourly sessions is calculated based on the actual time consumed multiplied by the console unit's hourly rate, minus any reservation fee already paid.</li>
                        <li>Users may request additional time during an active session through the **Request More Time** feature, subject to Admin or Shopkeeper approval.</li>
                        <li>Controller add-ons are billed at **₱20.00 per hour** (₱10 per 30-minute block) based on actual consumed time.</li>
                        <li>Controller rental fees are only charged if the controller has been used for **5 minutes or more**.</li>
                    </ul>
                </div>

                <!-- 4. No Show Policy -->
                <div class="terms-section" id="sec-noshow" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-coral"><i class="fas fa-user-slash" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">4. No Show Policy <span>Forfeiture</span></h2>
                    </div>
                    <ul>
                        <li>If a user does not arrive for their reservation after the session has auto-started, the staff may mark the reservation as a **No Show**.</li>
                        <li>When marked as a No Show, the reservation fee is **automatically forfeited** and transferred to the shop's earnings.</li>
                        <li>No penalty fee will be charged beyond the forfeiture of the reservation fee.</li>
                        <li>The No Show button only becomes available to staff after the reservation session has auto-started.</li>
                    </ul>
                </div>

                <!-- 5. Reschedule Policy -->
                <div class="terms-section" id="sec-reschedule" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-gold"><i class="fas fa-calendar-alt" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">5. Reschedule Policy <span>Negotiation</span></h2>
                    </div>
                    <ul>
                        <li>Both the Admin and the User may initiate a reschedule request for an existing reservation.</li>
                        <li>If the Admin proposes a new schedule, the user may confirm it or propose a **counter-proposal** with a different date/time.</li>
                        <li>All counter-proposals from the user require a valid reason and must be for a date/time **later** than the Admin's original proposal.</li>
                        <li>Negotiation continues until both parties agree on the final schedule.</li>
                        <li>If the user confirms the Admin's proposal without changes, no reason is required and it is immediately finalized.</li>
                        <li>The **'Pending'** status only applies during an active reschedule negotiation.</li>
                        <li>Once agreed, the status returns to **'Reserved'** with the updated schedule.</li>
                    </ul>
                </div>

                <!-- 6. Cancellation Policy -->
                <div class="terms-section" id="sec-cancel" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-coral"><i class="fas fa-ban" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">6. Cancellation Policy <span>Refunds</span></h2>
                    </div>
                    <div class="hl warn">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        **Reservation fees are strictly non-refundable** regardless of the reason for cancellation.
                    </div>
                    <p>The shop reserves the right to cancel or reschedule any reservation at any time due to unforeseen circumstances (e.g., equipment failure, power outage).</p>
                </div>

                <!-- 7. Tournament Terms -->
                <div class="terms-section" id="sec-tournaments" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-teal"><i class="fas fa-trophy" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">7. Tournament Terms <span>Events</span></h2>
                    </div>
                    <ul>
                        <li>Users may register for tournaments via the User Dashboard.</li>
                        <li>A registration fee must be paid via PayMongo before the entry is confirmed.</li>
                        <li>Tournament prize pools are dynamic and depend on the final number of registered participants.</li>
                        <li>There is no maximum participant limit for tournaments.</li>
                        <li>The Admin reserves the right to remove any participant from a tournament at any time for conduct or rule violations.</li>
                        <li>Removed participants will still see their entry in the dashboard with a status indicating removal by Admin.</li>
                        <li>Tournament registration fees are **non-refundable**.</li>
                    </ul>
                </div>

                <!-- 8. Payment Terms -->
                <div class="terms-section" id="sec-payments" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-mint"><i class="fas fa-peso-sign" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">8. Payment Terms <span>Gateways</span></h2>
                    </div>
                    <ul>
                        <li>All online reservation and tournament payments are processed through **PayMongo (via GCash)**.</li>
                        <li>Walk-in session payments are collected in **Cash** at the counter.</li>
                        <li>All payments are final and non-refundable, except in the specific case where a session was accidentally ended by staff and restored within the **1-hour undo window**.</li>
                        <li>The shop is not responsible for failed or delayed payments caused by payment gateway issues.</li>
                    </ul>
                </div>

                <!-- 9. Controller Rental Terms -->
                <div class="terms-section" id="sec-controllers" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-blue"><i class="fas fa-gamepad" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">9. Controller Rental <span>Add-ons</span></h2>
                    </div>
                    <ul>
                        <li>Controller add-ons are billed at **₱20.00 per hour** (₱10 per 30-minute block).</li>
                        <li>Fees are only applied if the controller is in use for **5 minutes or more**.</li>
                        <li>Controller rentals cannot extend beyond the shop's closing time of 12:00 AM.</li>
                        <li>Users are financially responsible for any damage caused to controllers during their rental period.</li>
                    </ul>
                </div>

                <!-- 10. User Conduct -->
                <div class="terms-section" id="sec-conduct" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-purple"><i class="fas fa-handshake" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">10. User Conduct <span>Responsibilities</span></h2>
                    </div>
                    <ul>
                        <li>Users are responsible for the proper use and care of all console units and shop equipment.</li>
                        <li>Any damage caused to consoles, controllers, or furniture will be the user's financial responsibility.</li>
                        <li>Food and drinks near console units must follow the shop's specific safety regulations.</li>
                        <li>Disruptive, abusive, or inappropriate behavior will result in immediate termination of the session without a refund.</li>
                        <li>The shop reserves the right to ban any user who repeatedly violates shop rules.</li>
                    </ul>
                </div>

                <!-- 11. Account Terms -->
                <div class="terms-section" id="sec-accounts" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-gold"><i class="fas fa-user-shield" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">11. Account Terms <span>Security</span></h2>
                    </div>
                    <ul>
                        <li>Users are responsible for the security and confidentiality of their account credentials.</li>
                        <li>Accounts must not be shared with any other person.</li>
                        <li>The shop reserves the right to suspend or ban any account in violation of these terms.</li>
                        <li>Shopkeeper accounts are created exclusively by the Admin and do not require email verification.</li>
                    </ul>
                </div>

                <!-- 12. Privacy Policy -->
                <div class="terms-section" id="sec-privacy" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-teal"><i class="fas fa-shield-alt" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">12. Privacy Policy <span>Data Collection</span></h2>
                    </div>
                    <p>The system collects personal information (Name, Email, Contact Number) for the sole purpose of managing reservations, sessions, and tournaments.</p>
                    <ul>
                        <li>User information will NOT be shared with third parties outside of shop operations.</li>
                        <li>Transaction and session data may be used for internal reporting and analytics purposes.</li>
                    </ul>
                </div>

                <!-- 13. Staff Code of Conduct -->
                <div class="terms-section" id="section-13-staff-code-of-conduct" data-aos="fade-up">
                    <div class="terms-section-header">
                        <div class="terms-icon ti-blue"><i class="fas fa-user-tie" style="color:#fff;"></i></div>
                        <h2 class="terms-section-title">Section 13 — Staff Code of Conduct <span>Professionalism</span></h2>
                    </div>
                    <p>All Admins and Shopkeepers are bound by the Staff Code of Conduct to ensure the highest standard of service and operational integrity.</p>
                    <ul>
                        <li>Staff must maintain professionalism, honesty, and integrity in all interactions with customers and other staff members.</li>
                        <li>All transactions, session starts, extensions, and payments must be recorded accurately through the official system interface.</li>
                        <li>Staff are strictly prohibited from bypassing the billing engine or manually modifying database values for unauthorized purposes.</li>
                        <li>Equipment maintenance and damage reports must be submitted promptly and truthfully.</li>
                        <li>User privacy must be respected at all times; personal data must never be used for non-official purposes.</li>
                        <li>Staff must follow all instructions provided by the Admin/Owner and stay updated with the latest Hub policies.</li>
                        <li>Violations of the Staff Code of Conduct will result in immediate suspension of privileges and possible permanent account termination.</li>
                    </ul>
                </div>

                <p class="last-updated">Last updated: May 10, 2026 &nbsp;·&nbsp; Gspot Gaming Hub, Dasmariñas, Cavite</p>
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
