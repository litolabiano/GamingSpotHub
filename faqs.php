<?php require_once __DIR__ . '/includes/session_helper.php'; 
require_once __DIR__ . '/includes/db_functions.php';
$pr = getPricingRules();
$unlimitedRate = (float)(getSetting('unlimited_rate') ?? 300);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - G SPOT Gaming Hub</title>
    <meta name="description" content="Frequently Asked Questions about G SPOT Gaming Hub — reservations, policies, gaming rates, and more.">

    <!-- Bootstrap CSS -->
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts (local) -->
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">

    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">

    <!-- AOS Animation Library (local) -->
    <link href="assets/libs/aos/aos.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --faq-bg: #060d1a;
            --faq-surface: #0d1626;
            --faq-border: rgba(255,255,255,.08);
            --faq-accent: #20c8a1;
        }

        body {
            background-color: var(--faq-bg);
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        /* ── Page Hero ── */
        .faqs-hero {
            position: relative;
            padding: 120px 0 60px;
            background: radial-gradient(circle at 50% -20%, rgba(32,200,161,.15), transparent 70%);
            text-align: center;
        }

        .faqs-hero h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff 30%, rgba(255,255,255,.5));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .faqs-hero p {
            color: rgba(255,255,255,.6);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ── Search Bar ── */
        .faq-search-container {
            max-width: 600px;
            margin: 40px auto 0;
            position: relative;
        }

        .faq-search-input {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--faq-border);
            border-radius: 50px;
            padding: 18px 25px 18px 55px;
            color: #fff;
            font-size: 1rem;
            transition: all .3s;
        }

        .faq-search-input:focus {
            outline: none;
            background: rgba(255,255,255,.08);
            border-color: var(--faq-accent);
            box-shadow: 0 0 20px rgba(32,200,161,.1);
        }

        .faq-search-icon {
            position: absolute;
            left: 22px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.3);
            font-size: 1.2rem;
        }

        /* ── Category Navigation ── */
        .faq-nav {
            display: flex;
            overflow-x: auto;
            gap: 12px;
            padding: 20px 0;
            margin-bottom: 40px;
            scrollbar-width: none;
        }

        .faq-nav::-webkit-scrollbar { display: none; }

        .faq-nav-btn {
            white-space: nowrap;
            background: rgba(255,255,255,.03);
            border: 1px solid var(--faq-border);
            padding: 10px 22px;
            border-radius: 50px;
            color: rgba(255,255,255,.7);
            font-size: 0.9rem;
            font-weight: 600;
            transition: all .3s;
            cursor: pointer;
        }

        .faq-nav-btn:hover, .faq-nav-btn.active {
            background: rgba(32,200,161,.1);
            border-color: var(--faq-accent);
            color: var(--faq-accent);
        }

        /* ── Section Titles ── */
        .faq-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            margin: 60px 0 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #fff;
        }

        .faq-section-title i {
            width: 45px;
            height: 45px;
            background: rgba(32,200,161,.1);
            color: var(--faq-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.2rem;
        }

        /* ── Accordion Styling ── */
        .accordion-item {
            background: var(--faq-surface);
            border: 1px solid var(--faq-border);
            border-radius: 16px !important;
            margin-bottom: 12px;
            overflow: hidden;
            transition: transform .3s, border-color .3s;
        }

        .accordion-item:hover {
            border-color: rgba(255,255,255,.15);
        }

        .accordion-button {
            background: transparent !important;
            color: #fff !important;
            font-weight: 700;
            padding: 22px 25px;
            font-size: 1.05rem;
            box-shadow: none !important;
        }

        .accordion-button:not(.collapsed) {
            color: var(--faq-accent) !important;
        }

        .accordion-button::after {
            filter: invert(1);
        }

        .accordion-body {
            padding: 0 25px 25px;
            color: rgba(255,255,255,.7);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .accordion-body strong { color: #fff; }

        /* ── Highlight Box ── */
        .faq-highlight {
            background: rgba(255,255,255,.03);
            border-left: 3px solid var(--faq-accent);
            padding: 15px 20px;
            border-radius: 0 12px 12px 0;
            margin: 15px 0;
            font-size: 0.9rem;
        }

        /* ── Footer Link ── */
        .faq-footer {
            text-align: center;
            padding: 80px 0;
            border-top: 1px solid var(--faq-border);
            margin-top: 60px;
        }

        .faq-footer h3 { font-family: 'Outfit', sans-serif; font-weight: 800; }
        .faq-footer p { color: rgba(255,255,255,.5); margin-bottom: 25px; }

        .btn-contact {
            background: var(--faq-accent);
            color: #060d1a;
            font-weight: 700;
            padding: 12px 35px;
            border-radius: 50px;
            text-decoration: none;
            transition: all .3s;
        }

        .btn-contact:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(32,200,161,.2);
            color: #060d1a;
        }

        @media (max-width: 768px) {
            .faqs-hero { padding: 100px 0 40px; }
            .accordion-button { padding: 18px 20px; font-size: 1rem; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero ── -->
<section class="faqs-hero">
    <div class="container">
        <h1 data-aos="fade-up">Have <span>Questions?</span></h1>
        <p data-aos="fade-up" data-aos-delay="100">Find everything you need to know about G SPOT Gaming Hub reservations, sessions, and policies.</p>
        
        <div class="faq-search-container" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-search faq-search-icon"></i>
            <input type="text" class="faq-search-input" id="faqSearch" placeholder="Search for a question (e.g. 'refund', 'GCash', 'late')">
        </div>
    </div>
</section>

<!-- ── FAQs Content ── -->
<section class="pb-5">
    <div class="container">
        
        <!-- Category Nav -->
        <div class="faq-nav" data-aos="fade-up">
            <button class="faq-nav-btn active" onclick="filterFaqs('all')">All Categories</button>
            <button class="faq-nav-btn" onclick="filterFaqs('general')">General</button>
            <button class="faq-nav-btn" onclick="filterFaqs('account')">Account</button>
            <button class="faq-nav-btn" onclick="filterFaqs('reservations')">Reservations</button>
            <button class="faq-nav-btn" onclick="filterFaqs('rescheduling')">Rescheduling</button>
            <button class="faq-nav-btn" onclick="filterFaqs('sessions')">Sessions</button>
            <button class="faq-nav-btn" onclick="filterFaqs('tournaments')">Tournaments</button>
            <button class="faq-nav-btn" onclick="filterFaqs('billing')">Payments</button>
            <button class="faq-nav-btn" onclick="filterFaqs('rules')">Rules & Terms</button>
        </div>

        <div id="faqAccordionContainer">

            <!-- GENERAL INFORMATION -->
            <div class="faq-category-section" data-category="general">
                <h2 class="faq-section-title"><i class="fas fa-info-circle"></i> General Information</h2>
                <div class="accordion" id="accGeneral">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qG1">What is this Console Rental system and how does it work?</button></h2>
                        <div id="qG1" class="accordion-collapse collapse" data-bs-parent="#accGeneral"><div class="accordion-body">G SPOT Gaming Hub is a premium console rental facility. Our online system allows you to browse available console units (PS5, PS4, Xbox), reserve a specific time slot in advance, and manage your bookings. You can pay your reservation fee via GCash to secure your slot instantly.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qG2">What consoles are available for rent?</button></h2>
                        <div id="qG2" class="accordion-collapse collapse" data-bs-parent="#accGeneral"><div class="accordion-body">We offer high-performance gaming units including <strong>PlayStation 5 (PS5)</strong>, <strong>PlayStation 4 (PS4)</strong>, and <strong>Xbox Series X</strong>. Each unit is equipped with premium controllers and a wide selection of top-tier games.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qG3">What are the operating hours of the shop?</button></h2>
                        <div id="qG3" class="accordion-collapse collapse" data-bs-parent="#accGeneral"><div class="accordion-body">The hub is open daily from <strong>12:00 PM to 11:00 PM</strong>. Reservations can only be made within these operating hours.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qG4">How much does it cost to rent a console per hour?</button></h2>
                        <div id="qG4" class="accordion-collapse collapse" data-bs-parent="#accGeneral"><div class="accordion-body">Our standard hourly rate is <strong>₱80</strong>. We also offer a starter rate of <strong>₱50 for the first 30 minutes</strong>. Additional time can be added in 30-minute increments for <strong>₱40</strong> each.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qG5">Is there a minimum rental duration?</button></h2>
                        <div id="qG5" class="accordion-collapse collapse" data-bs-parent="#accGeneral"><div class="accordion-body">Yes, the minimum rental duration is <strong>30 minutes</strong>, which costs ₱50.</div></div>
                    </div>
                </div>
            </div>

            <!-- ACCOUNT AND REGISTRATION -->
            <div class="faq-category-section" data-category="account">
                <h2 class="faq-section-title"><i class="fas fa-user-circle"></i> Account & Registration</h2>
                <div class="accordion" id="accAccount">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qA1">How do I create an account?</button></h2>
                        <div id="qA1" class="accordion-collapse collapse" data-bs-parent="#accAccount"><div class="accordion-body">Click the "Register" button on the navigation bar, fill in your full name, email address, phone number, and password. After submitting, you will need to verify your email.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qA2">Why do I need to verify my email address?</button></h2>
                        <div id="qA2" class="accordion-collapse collapse" data-bs-parent="#accAccount"><div class="accordion-body">Email verification ensures your account is secure and that you can receive important notifications about your reservations, payments, and reschedule requests.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qA3">How do I verify my email address using the OTP sent to my Gmail?</button></h2>
                        <div id="qA3" class="accordion-collapse collapse" data-bs-parent="#accAccount"><div class="accordion-body">Check your Gmail inbox for a message from G SPOT Gaming Hub. Copy the 6-digit One-Time Password (OTP) and enter it on the verification page that appears after registration.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qA4">What should I do if I did not receive my verification OTP?</button></h2>
                        <div id="qA4" class="accordion-collapse collapse" data-bs-parent="#accAccount"><div class="accordion-body">First, check your <strong>Spam</strong> or Junk folder. If it's not there, wait for 60 seconds and use the "Resend OTP" link on the verification page. Ensure you entered your email correctly.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qA5">How do I log in to my account?</button></h2>
                        <div id="qA5" class="accordion-collapse collapse" data-bs-parent="#accAccount"><div class="accordion-body">Go to the Login page, enter your registered email and password, and click "Sign In".</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qA6">How do I update my account information?</button></h2>
                        <div id="qA6" class="accordion-collapse collapse" data-bs-parent="#accAccount"><div class="accordion-body">Log in and navigate to your <strong>Dashboard</strong>. Go to the "Profile" or "Account Settings" section to update your contact number or other personal details.</div></div>
                    </div>
                </div>
            </div>

            <!-- RESERVATIONS -->
            <div class="faq-category-section" data-category="reservations">
                <h2 class="faq-section-title"><i class="fas fa-calendar-check"></i> Reservations</h2>
                <div class="accordion" id="accReservations">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qR1">How do I make a reservation?</button></h2>
                        <div id="qR1" class="accordion-collapse collapse" data-bs-parent="#accReservations"><div class="accordion-body">Go to the <strong>Reserve</strong> page, select your preferred console type, pick an available date and time, choose your duration, and proceed to payment.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qR2">How is the availability determined when making a reservation?</button></h2>
                        <div id="qR2" class="accordion-collapse collapse" data-bs-parent="#accReservations"><div class="accordion-body">The system performs real-time conflict checking. It only shows units that are not already in use by an active session and do not have an existing confirmed reservation during your requested time window.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qR3">How much is the reservation fee and how do I pay it?</button></h2>
                        <div id="qR3" class="accordion-collapse collapse" data-bs-parent="#accReservations"><div class="accordion-body">The reservation fee is typically <strong>₱50</strong>. This serves as a downpayment to secure your slot and is deducted from your total bill. Payment is made via <strong>GCash</strong> through the integrated PayMongo gateway.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qR4">Is the reservation fee refundable?</button></h2>
                        <div id="qR4" class="accordion-collapse collapse" data-bs-parent="#accReservations"><div class="accordion-body">Yes, the fee is fully refundable if you cancel your reservation <strong>BEFORE</strong> the scheduled start time. Cancellations made after the start time may incur an "inconvenience fee".</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qR5">Does my reservation need to be approved by the admin?</button></h2>
                        <div id="qR5" class="accordion-collapse collapse" data-bs-parent="#accReservations"><div class="accordion-body">No. Once payment is successful, your reservation is <strong>instantly confirmed</strong> and set to "Reserved" status. No manual approval is required.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qR6">What is the difference between 'Reserved' and 'Pending' status?</button></h2>
                        <div id="qR6" class="accordion-collapse collapse" data-bs-parent="#accReservations"><div class="accordion-body"><strong>'Reserved'</strong> means your booking is confirmed and secured. <strong>'Pending'</strong> typically appears only during a reschedule negotiation between you and the admin.</div></div>
                    </div>
                </div>
            </div>

            <!-- NO SHOW POLICY -->
            <div class="faq-category-section" data-category="rules">
                <h2 class="faq-section-title"><i class="fas fa-user-slash"></i> No Show Policy</h2>
                <div class="accordion" id="accNoShow">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qNS1">What happens if I do not show up for my reservation?</button></h2>
                        <div id="qNS1" class="accordion-collapse collapse" data-bs-parent="#accNoShow"><div class="accordion-body">If you fail to arrive, your reservation will be marked as a "No Show". In this case, your <strong>reservation fee will be forfeited</strong> (non-refundable), and the console unit will be released back to availability.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qNS2">Who marks a reservation as a no show and when?</button></h2>
                        <div id="qNS2" class="accordion-collapse collapse" data-bs-parent="#accNoShow"><div class="accordion-body">The admin or shopkeeper manually marks no shows. The option to do so usually becomes available 30 minutes after your scheduled start time if the session has not been started.</div></div>
                    </div>
                </div>
            </div>

            <!-- RESCHEDULING -->
            <div class="faq-category-section" data-category="rescheduling">
                <h2 class="faq-section-title"><i class="fas fa-clock"></i> Rescheduling</h2>
                <div class="accordion" id="accResched">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qRS1">Can I reschedule my reservation?</button></h2>
                        <div id="qRS1" class="accordion-collapse collapse" data-bs-parent="#accResched"><div class="accordion-body">Yes. You can request a reschedule through your dashboard. Rescheduling is subject to console availability and admin approval.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qRS2">What happens when the admin sends me a reschedule request?</button></h2>
                        <div id="qRS2" class="accordion-collapse collapse" data-bs-parent="#accResched"><div class="accordion-body">You will receive a notification. You can view the proposed date and time in your dashboard and choose to <strong>Accept</strong>, <strong>Counter-propose</strong> (suggest a different time), or <strong>Decline</strong>.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qRS3">What if I disagree with the admin's proposed reschedule?</button></h2>
                        <div id="qRS3" class="accordion-collapse collapse" data-bs-parent="#accResched"><div class="accordion-body">You can submit a counter-proposal with your own preferred date and time. The status will remain "Pending" until both you and the admin agree on the new schedule.</div></div>
                    </div>
                </div>
            </div>

            <!-- SESSIONS -->
            <div class="faq-category-section" data-category="sessions">
                <h2 class="faq-section-title"><i class="fas fa-play-circle"></i> Sessions</h2>
                <div class="accordion" id="accSessions">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qS1">Does my reserved session start automatically?</button></h2>
                        <div id="qS1" class="accordion-collapse collapse" data-bs-parent="#accSessions"><div class="accordion-body">Yes, the session is scheduled to start at the exact time you reserved. It is important to arrive at least 10 minutes early to ensure you get your full play time.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qS2">Can I extend my session while I am playing?</button></h2>
                        <div id="qS2" class="accordion-collapse collapse" data-bs-parent="#accSessions"><div class="accordion-body">Yes, provided the unit is not reserved by another user for the following slot. You can request an extension from the staff, and the additional time will be added to your current session.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qS3">What happens when my session ends?</button></h2>
                        <div id="qS3" class="accordion-collapse collapse" data-bs-parent="#accSessions"><div class="accordion-body">The system will lock the unit, and a final billing summary will be generated. You then settle any remaining balance at the front desk.</div></div>
                    </div>
                </div>
            </div>

            <!-- TOURNAMENTS -->
            <div class="faq-category-section" data-category="tournaments">
                <h2 class="faq-section-title"><i class="fas fa-trophy"></i> Tournaments</h2>
                <div class="accordion" id="accTourneys">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qT1">How do I join a tournament?</button></h2>
                        <div id="qT1" class="accordion-collapse collapse" data-bs-parent="#accTourneys"><div class="accordion-body">Go to the <strong>Tournament Dashboard</strong>, browse active events, and click "Register". Some tournaments may require an entry fee.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qT2">Is there a maximum number of participants?</button></h2>
                        <div id="qT2" class="accordion-collapse collapse" data-bs-parent="#accTourneys"><div class="accordion-body">No. We have removed participant limits to allow as many gamers as possible to compete!</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qT3">What happens if my entry is removed by the admin?</button></h2>
                        <div id="qT3" class="accordion-collapse collapse" data-bs-parent="#accTourneys"><div class="accordion-body">Your registration will remain visible in your dashboard but will be clearly marked as <strong>"Removed by Admin"</strong> or "Disqualified". You will not be able to participate in that event.</div></div>
                    </div>
                </div>
            </div>

            <!-- PAYMENTS AND BILLING -->
            <div class="faq-category-section" data-category="billing">
                <h2 class="faq-section-title"><i class="fas fa-money-bill-wave"></i> Payments & Billing</h2>
                <div class="accordion" id="accBilling">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qB1">What payment methods are accepted?</button></h2>
                        <div id="qB1" class="accordion-collapse collapse" data-bs-parent="#accBilling"><div class="accordion-body">For online reservations, we accept <strong>GCash</strong> via PayMongo. For in-shop payments (walk-ins, extensions, snacks), we accept <strong>Cash</strong> and <strong>GCash</strong>.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qB2">When do I pay for my session?</button></h2>
                        <div id="qB2" class="accordion-collapse collapse" data-bs-parent="#accBilling"><div class="accordion-body">You pay a reservation fee (downpayment) at the time of booking. The remaining balance (based on total time played and any extra services) is paid at the end of your session.</div></div>
                    </div>
                </div>
            </div>

            <!-- RULES AND TERMS -->
            <div class="faq-category-section" data-category="rules">
                <h2 class="faq-section-title"><i class="fas fa-gavel"></i> Rules & Terms</h2>
                <div class="accordion" id="accRules">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qRT1">What are the rules inside the shop?</button></h2>
                        <div id="qRT1" class="accordion-collapse collapse" data-bs-parent="#accRules"><div class="accordion-body">Respect fellow gamers, maintain a reasonable noise level, and follow the instructions of the staff. <strong>Food and drinks are strictly prohibited</strong> near the console units to prevent damage.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qRT2">Am I responsible for any hardware damage?</button></h2>
                        <div id="qRT2" class="accordion-collapse collapse" data-bs-parent="#accRules"><div class="accordion-body">Yes. Users are held liable for any physical damage caused to consoles, controllers, or screens during their session. Please handle our equipment with care.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qRT3">What happens if I violate the terms or rules?</button></h2>
                        <div id="qRT3" class="accordion-collapse collapse" data-bs-parent="#accRules"><div class="accordion-body">Minor violations may result in a warning. Serious or repeated violations can lead to immediate termination of your session, forfeiture of fees, and a permanent ban from the hub.</div></div>
                    </div>
                </div>
            </div>

        </div> <!-- end faqAccordionContainer -->

        <!-- CTA -->
        <div class="faq-footer" data-aos="fade-up">
            <h3>Still have questions?</h3>
            <p>If you couldn't find the answer you were looking for, our team is here to help.</p>
            <a href="index.php#contact" class="btn-contact">Contact Support</a>
        </div>

    </div>
</section>

<!-- ── Scripts ── -->
<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });

    // Search functionality
    const searchInput = document.getElementById('faqSearch');
    const faqItems = document.querySelectorAll('.accordion-item');
    const sections = document.querySelectorAll('.faq-category-section');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        
        faqItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        // Hide sections with no visible items
        sections.forEach(section => {
            const visibleItems = section.querySelectorAll('.accordion-item[style="display: block;"]').length;
            const allItems = section.querySelectorAll('.accordion-item').length;
            
            // If query is empty, show all sections
            if (query === '') {
                section.style.display = 'block';
                faqItems.forEach(i => i.style.display = 'block');
                return;
            }

            if (visibleItems > 0) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    });

    // Category filter
    function filterFaqs(category) {
        // Update buttons
        document.querySelectorAll('.faq-nav-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.toLowerCase().includes(category) || (category === 'all' && btn.textContent.toLowerCase().includes('all'))) {
                btn.classList.add('active');
            }
        });

        // Show/hide sections
        sections.forEach(section => {
            if (category === 'all' || section.getAttribute('data-category') === category) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });

        // Reset search
        searchInput.value = '';
        faqItems.forEach(i => i.style.display = 'block');
    }
</script>

</body>
</html>
