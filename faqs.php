<?php require_once __DIR__ . '/includes/session_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Gspot Gaming Hub</title>
    <meta name="description" content="Frequently Asked Questions about Gspot Gaming Hub — reservations, cancellation policies, gaming rates, and more.">

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
        /* ── Page Hero ── */
        .faqs-hero {
            position: relative;
            min-height: 38vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0d1117 0%, #0a2151 60%, #1a0a2e 100%);
            overflow: hidden;
            padding-top: 80px;
        }

        .faqs-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 110%, rgba(32,200,161,.18) 0%, transparent 70%);
            pointer-events: none;
        }

        .faqs-hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 100%;
        }

        .faqs-hero-content .section-tag { margin-bottom: 1rem; }

        .faqs-hero-title {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 1.1rem;
        }

        .faqs-hero-title span {
            background: linear-gradient(135deg, var(--color-mint), var(--color-purple), var(--color-coral));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .faqs-hero-subtitle {
            font-size: 1.1rem;
            color: rgba(255,255,255,.7);
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* ── Search Bar ── */
        .faq-search-wrap {
            max-width: 560px;
            margin: 2.2rem auto 0;
        }

        .faq-search-wrap .input-group {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50px;
            overflow: hidden;
        }

        .faq-search-wrap .form-control {
            border: none;
            background: transparent;
            padding: .75rem 1.4rem;
            font-size: 1rem;
            color: #fff;
        }

        .faq-search-wrap .form-control::placeholder { color: rgba(255,255,255,.4); }
        .faq-search-wrap .form-control:focus { box-shadow: none; }

        .faq-search-wrap .btn-search {
            border-radius: 0;
            padding: .75rem 1.4rem;
            background: linear-gradient(135deg, var(--color-mint), var(--color-blue));
            border: none;
            color: #fff;
            font-size: 1rem;
        }

        /* ── Category Pills ── */
        .faq-categories {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            justify-content: center;
            margin-bottom: 2.5rem;
        }

        .faq-cat-btn {
            padding: .45rem 1.3rem;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.05);
            color: rgba(255,255,255,.7);
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s ease;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .faq-cat-btn:hover,
        .faq-cat-btn.active {
            background: linear-gradient(135deg, rgba(32,200,161,.25), rgba(95,133,218,.25));
            border-color: var(--color-mint);
            color: var(--color-mint);
        }

        /* ── FAQ Main Body ── */
        .faqs-section {
            background: linear-gradient(180deg, #0d1117 0%, #1a2332 100%);
            padding: 80px 0 100px;
        }

        /* ── Category Group ── */
        .faq-group {
            margin-bottom: 3.5rem;
        }

        .faq-group-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .faq-group-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .faq-group-icon.teal   { background: linear-gradient(135deg, #20c8a1, #5f85da); }
        .faq-group-icon.purple { background: linear-gradient(135deg, #b37bec, #fb566b); }
        .faq-group-icon.coral  { background: linear-gradient(135deg, #fb566b, #f1e1aa); }
        .faq-group-icon.blue   { background: linear-gradient(135deg, #5f85da, #20c8a1); }
        .faq-group-icon.gold   { background: linear-gradient(135deg, #f1e1aa, #fb566b); }

        .faq-group-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            color: var(--color-light);
        }

        /* ── Accordion Cards ── */
        .faq-accordion .accordion-item {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px !important;
            margin-bottom: .85rem;
            overflow: hidden;
            transition: border-color .3s ease, box-shadow .3s ease;
        }

        .faq-accordion .accordion-item:hover {
            border-color: rgba(32,200,161,.25);
        }

        .faq-accordion .accordion-item.open-item {
            border-color: rgba(32,200,161,.4);
            box-shadow: 0 0 22px rgba(32,200,161,.1);
        }

        .faq-accordion .accordion-button {
            background: transparent;
            color: var(--color-light);
            font-size: 1rem;
            font-weight: 600;
            padding: 1.2rem 1.5rem;
            border: none;
            box-shadow: none !important;
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .faq-accordion .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, rgba(32,200,161,.08), rgba(95,133,218,.06));
            color: var(--color-mint);
        }

        .faq-accordion .accordion-button::after {
            filter: invert(1) sepia(1) saturate(3) hue-rotate(120deg);
            margin-left: auto;
            flex-shrink: 0;
        }

        .faq-q-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(32,200,161,.2), rgba(95,133,218,.2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            color: var(--color-mint);
            font-weight: 800;
            flex-shrink: 0;
            letter-spacing: .5px;
        }

        .faq-accordion .accordion-body {
            padding: 0 1.5rem 1.4rem calc(1.5rem + 30px + .85rem);
            color: rgba(255,255,255,.75);
            font-size: .97rem;
            line-height: 1.8;
        }

        /* ── Highlight Boxes inside answers ── */
        .faq-highlight {
            background: rgba(32,200,161,.07);
            border-left: 3px solid var(--color-mint);
            border-radius: 0 12px 12px 0;
            padding: .9rem 1.2rem;
            margin: .9rem 0;
            color: rgba(255,255,255,.85);
            font-size: .93rem;
        }

        .faq-highlight.warning {
            background: rgba(251,86,107,.07);
            border-color: var(--color-coral);
        }

        .faq-highlight.info {
            background: rgba(95,133,218,.1);
            border-color: var(--color-blue);
        }

        .faq-highlight.gold {
            background: rgba(241,225,170,.07);
            border-color: var(--color-secondary);
        }

        /* ── Timeline inside answer ── */
        .faq-timeline {
            position: relative;
            padding-left: 1.6rem;
            margin: 1rem 0;
        }

        .faq-timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: linear-gradient(180deg, var(--color-mint), var(--color-purple));
            border-radius: 2px;
        }

        .faq-timeline-item {
            position: relative;
            margin-bottom: 1rem;
            padding-left: .5rem;
        }

        .faq-timeline-item::before {
            content: '';
            position: absolute;
            left: -1.15rem;
            top: .55rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--color-mint);
            border: 2px solid #0d1117;
        }

        .faq-timeline-item strong {
            color: var(--color-mint);
            display: block;
            margin-bottom: .22rem;
        }

        .faq-timeline-item.danger::before { background: var(--color-coral); }
        .faq-timeline-item.danger strong  { color: var(--color-coral); }
        .faq-timeline-item.gold::before   { background: var(--color-secondary); }
        .faq-timeline-item.gold strong    { color: var(--color-secondary); }

        /* ── Steps list ── */
        .faq-steps {
            counter-reset: faq-step;
            padding: 0;
            list-style: none;
            margin: 1rem 0;
        }

        .faq-steps li {
            counter-increment: faq-step;
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            margin-bottom: .75rem;
            color: rgba(255,255,255,.8);
        }

        .faq-steps li::before {
            content: counter(faq-step);
            min-width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-mint), var(--color-blue));
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: .1rem;
        }

        /* ── CTA Banner ── */
        .faq-cta {
            background: linear-gradient(135deg, rgba(32,200,161,.12), rgba(95,133,218,.12));
            border: 1px solid rgba(32,200,161,.25);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            margin-top: 3.5rem;
        }

        .faq-cta h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: .75rem;
        }

        .faq-cta p {
            color: rgba(255,255,255,.7);
            margin-bottom: 1.6rem;
            font-size: 1rem;
        }

        /* ── Search hide/show ── */
        .faq-group[data-hidden="true"] { display: none; }
        .faq-accordion .accordion-item[data-hidden="true"] { display: none; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .faq-accordion .accordion-body {
                padding-left: 1.5rem;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero ── -->
<section class="faqs-hero">
    <div class="container">
        <div class="faqs-hero-content" data-aos="fade-up">
            <span class="section-tag">FAQs</span>
            <h1 class="faqs-hero-title">Frequently Asked <span>Questions</span></h1>
            <p class="faqs-hero-subtitle">
                Everything you need to know about Gspot Gaming Hub — reservations, cancellations, policies, and more.
            </p>
            <!-- Search -->
            <div class="faq-search-wrap">
                <div class="input-group">
                    <input type="text" class="form-control" id="faqSearchInput" placeholder="Search a question…" autocomplete="off">
                    <button class="btn-search" type="button" id="faqSearchBtn"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── FAQs Body ── -->
<section class="faqs-section">
    <div class="container">

        <!-- Category Filter Pills -->
        <div class="faq-categories" data-aos="fade-up">
            <button class="faq-cat-btn active" data-cat="all">All</button>
            <button class="faq-cat-btn" data-cat="reservations">Reservations</button>
            <button class="faq-cat-btn" data-cat="cancellations">Cancellations &amp; Refunds</button>
            <button class="faq-cat-btn" data-cat="rates">Rates &amp; Pricing</button>
            <button class="faq-cat-btn" data-cat="account">Account &amp; Membership</button>
            <button class="faq-cat-btn" data-cat="general">General</button>
        </div>

        <!-- ════════════════════════════════════════════
             GROUP 1 — RESERVATIONS
             ════════════════════════════════════════════ -->
        <div class="faq-group" data-group="reservations">
            <div class="faq-group-header">
                <div class="faq-group-icon teal"><i class="fas fa-calendar-check" style="color:#fff;"></i></div>
                <h2 class="faq-group-title">Reservations</h2>
            </div>
            <div class="accordion faq-accordion" id="accordionReservations">

                <!-- Q1 -->
                <div class="accordion-item" data-cat="reservations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqR1">
                            <span class="faq-q-icon">Q</span>
                            How do I make a reservation at Gspot Gaming Hub?
                        </button>
                    </h2>
                    <div id="faqR1" class="accordion-collapse collapse" data-bs-parent="#accordionReservations">
                        <div class="accordion-body">
                            Making a reservation is quick and easy! Just follow these steps:
                            <ol class="faq-steps">
                                <li>Go to our <a href="reserve.php" style="color:var(--color-mint);">Reserve page</a> or click <em>"Reserve"</em> in the navigation bar.</li>
                                <li>Log in or register if you haven't already.</li>
                                <li>Browse available gaming units and pick the console type you want.</li>
                                <li>Select your preferred date and time slot.</li>
                                <li>Confirm your booking and complete the reservation fee payment.</li>
                            </ol>
                            You will receive a confirmation notification once your reservation is successfully placed.
                        </div>
                    </div>
                </div>

                <!-- Q2 -->
                <div class="accordion-item" data-cat="reservations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqR2">
                            <span class="faq-q-icon">Q</span>
                            How far in advance can I reserve a unit?
                        </button>
                    </h2>
                    <div id="faqR2" class="accordion-collapse collapse" data-bs-parent="#accordionReservations">
                        <div class="accordion-body">
                            You can reserve a unit up to <strong>1hr in advance</strong>. This ensures fair access for all members and helps us manage unit availability efficiently. 
                        </div>
                    </div>
                </div>

                <!-- Q3 -->
                <div class="accordion-item" data-cat="reservations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqR3">
                            <span class="faq-q-icon">Q</span>
                            Is a reservation fee required? Is it part of the gaming rate?
                        </button>
                    </h2>
                    <div id="faqR3" class="accordion-collapse collapse" data-bs-parent="#accordionReservations">
                        <div class="accordion-body">
                            Yes, a <strong>reservation fee</strong> is collected upfront to secure your slot. This fee is <strong>credited toward your total gaming session cost</strong> — it is <em>not</em> an extra charge on top of the gaming rate. Think of it as a deposit that gets applied when you arrive and start playing.
                            <div class="faq-highlight">
                                <i class="fas fa-info-circle me-2" style="color:var(--color-mint);"></i>
                                If you cancel on time (before your session starts), the full reservation fee is refunded. See the <strong>Cancellations &amp; Refunds</strong> section for complete details.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Q4 -->
                <div class="accordion-item" data-cat="reservations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqR4">
                            <span class="faq-q-icon">Q</span>
                            Can I reserve more than one unit at a time?
                        </button>
                    </h2>
                    <div id="faqR4" class="accordion-collapse collapse" data-bs-parent="#accordionReservations">
                        <div class="accordion-body">
                        To maintain fairness, accounts are typically limited to one active reservation. However, you may reserve a maximum of <strong>three (3) units simultaneously</strong>, provided that full payment is guaranteed at the time of booking. For bulk arrangements, please visit the front desk.
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- end reservations group -->


        <!-- ════════════════════════════════════════════
             GROUP 2 — CANCELLATIONS & REFUNDS
             ════════════════════════════════════════════ -->
        <div class="faq-group" data-group="cancellations">
            <div class="faq-group-header">
                <div class="faq-group-icon coral"><i class="fas fa-rotate-left" style="color:#fff;"></i></div>
                <h2 class="faq-group-title">Cancellations &amp; Refunds</h2>
            </div>
            <div class="accordion faq-accordion" id="accordionCancel">

                <!-- Q — Core Policy -->
                <div class="accordion-item" data-cat="cancellations">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqC1">
                            <span class="faq-q-icon">Q</span>
                            What is the cancellation policy for reservations?
                        </button>
                    </h2>
                    <div id="faqC1" class="accordion-collapse collapse show" data-bs-parent="#accordionCancel">
                        <div class="accordion-body">
                            We have a straightforward two-scenario policy based on <strong>when</strong> you cancel relative to your reserved session start time:

                            <div class="faq-timeline">
                                <div class="faq-timeline-item">
                                    <strong>✅ Cancelled BEFORE your session starts — Full Refund</strong>
                                    If you cancel your reservation before the booked start time (e.g., you reserved 3:00 PM and you cancel at or before 2:59 PM), you will receive a <strong>100% full refund</strong> of the reservation fee. No deductions, no questions asked.
                                </div>
                                <div class="faq-timeline-item danger">
                                    <strong>⚠️ Cancelled AFTER your session has started — Inconvenience Fee Applied</strong>
                                    If you cancel once your reserved time has already begun (e.g., you reserved 3:00 PM and cancel at 3:01 PM or later), an <strong>inconvenience fee will be deducted</strong> from your reservation fee before the remainder is refunded. This fee compensates for the unit being held and potentially unavailable to other players during that window.
                                </div>
                            </div>

                            <div class="faq-highlight warning">
                                <i class="fas fa-exclamation-triangle me-2" style="color:var(--color-coral);"></i>
                                <strong>Example:</strong> You book Unit 3 at <em>3:00 PM</em>. If you decide to cancel at <em>3:01 PM</em>, a deduction (inconvenience fee) will be taken from your paid reservation fee before any refund is issued. The exact inconvenience fee amount is determined by staff at the time of cancellation.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Q — Inconvenience Fee -->
                <div class="accordion-item" data-cat="cancellations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC2">
                            <span class="faq-q-icon">Q</span>
                            What exactly is the "Inconvenience Fee"?
                        </button>
                    </h2>
                    <div id="faqC2" class="accordion-collapse collapse" data-bs-parent="#accordionCancel">
                        <div class="accordion-body">
                            The <strong>Inconvenience Fee</strong> is a partial deduction applied when a customer cancels a reservation <em>after</em> their booked session has already started. It exists because:
                            <ul style="color:rgba(255,255,255,.8); margin-top:.75rem; line-height:2;">
                                <li>The gaming unit was exclusively reserved for you and was unavailable to other players.</li>
                                <li>Staff had already prepared the unit and system for your session.</li>
                                <li>Other potential customers may have been turned away due to the reserved slot.</li>
                            </ul>
                            <div class="faq-highlight info">
                                <i class="fas fa-circle-info me-2" style="color:var(--color-blue);"></i>
                                The fee is deducted directly from your reservation fee. Any remaining balance after the deduction will be refunded to you. Please talk to our staff for the specific deduction amount.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Q — 3 Consecutive Cancels -->
                <div class="accordion-item" data-cat="cancellations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC3">
                            <span class="faq-q-icon">Q</span>
                            What happens if I cancel reservations multiple times?
                        </button>
                    </h2>
                    <div id="faqC3" class="accordion-collapse collapse" data-bs-parent="#accordionCancel">
                        <div class="accordion-body">
                            We understand that plans change, but repeated cancellations affect other members and the hub's operations. Here is our <strong>3-Strike Cancellation Rule</strong>:

                            <div class="faq-highlight warning" style="margin-top:1rem;">
                                <i class="fas fa-ban me-2" style="color:var(--color-coral);"></i>
                                <strong>3 Consecutive Cancellations = 1-Week Reservation Timeout</strong><br>
                                If your account accumulates <strong>3 consecutive cancelled reservations</strong>, your account will be placed on a <em>temporary reservation ban</em> for <strong>7 days (1 week)</strong>. During this period you will <strong>not be able to make new reservations</strong>. You can still walk in and use available units — only the online reservation feature is restricted.
                            </div>

                            <div class="faq-timeline" style="margin-top:1.2rem;">
                                <div class="faq-timeline-item">
                                    <strong>1st Cancellation</strong>
                                    Normal cancellation. Full or partial refund depending on timing (see policy above).
                                </div>
                                <div class="faq-timeline-item">
                                    <strong>2nd Consecutive Cancellation</strong>
                                    Normal cancellation processed. A warning notice is added to your account.
                                </div>
                                <div class="faq-timeline-item danger">
                                    <strong>3rd Consecutive Cancellation — Timeout Activated</strong>
                                    Your reservation privileges are suspended for <strong>1 week</strong> from the date of the third cancellation. The restriction is automatically lifted after 7 days.
                                </div>
                                <div class="faq-timeline-item gold">
                                    <strong>Counter Reset</strong>
                                    Successfully completing a reservation (showing up and gaming) resets your consecutive cancellation counter back to zero.
                                </div>
                            </div>

                            <div class="faq-highlight gold">
                                <i class="fas fa-lightbulb me-2" style="color:var(--color-secondary);"></i>
                                <strong>Tip:</strong> If you know you might not make it, please cancel as early as possible — ideally before your session start time. Early cancellations ensure full refunds and are still counted as cancellations toward the streak, but at least they free up the slot for other gamers!
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Q — How to cancel -->
                <div class="accordion-item" data-cat="cancellations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC4">
                            <span class="faq-q-icon">Q</span>
                            How do I cancel my reservation?
                        </button>
                    </h2>
                    <div id="faqC4" class="accordion-collapse collapse" data-bs-parent="#accordionCancel">
                        <div class="accordion-body">
                            You can cancel your reservation easily through your dashboard:
                            <ol class="faq-steps">
                                <li>Log in to your account and go to <strong>My Dashboard</strong>.</li>
                                <li>Navigate to the <em>My Reservations</em> section.</li>
                                <li>Find your upcoming reservation and click <strong>"Cancel Reservation"</strong>.</li>
                                <li>Confirm the cancellation when prompted.</li>
                            </ol>
                            Your refund (full or partial depending on timing) will be processed by our staff and returned through the original payment method.
                        </div>
                    </div>
                </div>

                <!-- Q — Refund timeline -->
                <div class="accordion-item" data-cat="cancellations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC5">
                            <span class="faq-q-icon">Q</span>
                            How long does a refund take to process?
                        </button>
                    </h2>
                    <div id="faqC5" class="accordion-collapse collapse" data-bs-parent="#accordionCancel">
                        <div class="accordion-body">
                            Refunds are typically processed within <strong>1–3 business days</strong> after the cancellation is confirmed by our staff. The exact timeline may vary depending on the payment method used. Cash refunds for walk-in payments can usually be settled the same day at the front desk.
                        </div>
                    </div>
                </div>

                <!-- Q — Timeout lifted -->
                <div class="accordion-item" data-cat="cancellations">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC6">
                            <span class="faq-q-icon">Q</span>
                            My reservation was suspended. Can I appeal or have it lifted early?
                        </button>
                    </h2>
                    <div id="faqC6" class="accordion-collapse collapse" data-bs-parent="#accordionCancel">
                        <div class="accordion-body">
                            Timeouts are automatically enforced by the system and are <strong>generally non-negotiable</strong>. However, if you believe your cancellations were due to an emergency or a system error, you may speak with our staff or contact us through our <a href="index.php#contact" style="color:var(--color-mint);">Contact</a> page. Appeals are handled on a case-by-case basis by management.
                            <div class="faq-highlight">
                                <i class="fas fa-clock me-2" style="color:var(--color-mint);"></i>
                                The 1-week ban is automatically removed once the suspension period ends. No manual action is needed on your part after the 7 days are up.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- end cancellations group -->


        <!-- ════════════════════════════════════════════
             GROUP 3 — RATES & PRICING
             ════════════════════════════════════════════ -->
        <div class="faq-group" data-group="rates">
            <div class="faq-group-header">
                <div class="faq-group-icon blue"><i class="fas fa-peso-sign" style="color:#fff;"></i></div>
                <h2 class="faq-group-title">Rates &amp; Pricing</h2>
            </div>
            <div class="accordion faq-accordion" id="accordionRates">

                <!-- Q — Main Pricing -->
                <div class="accordion-item" data-cat="rates">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqP1">
                            <span class="faq-q-icon">Q</span>
                            How much does it cost to play?
                        </button>
                    </h2>
                    <div id="faqP1" class="accordion-collapse collapse" data-bs-parent="#accordionRates">
                        <div class="accordion-body">
                            We have two billing modes depending on what your <strong>first payment</strong> covers:

                            <!-- Mode A: First payment = 30 min only -->
                            <div style="background:rgba(179,123,236,.08); border:1px solid rgba(179,123,236,.3); border-radius:16px; padding:1.3rem 1.4rem; margin:1.2rem 0;">
                                <div style="font-size:.7rem; font-weight:800; letter-spacing:1px; color:var(--color-purple); text-transform:uppercase; margin-bottom:.6rem;">MODE A &mdash; First payment is 30 minutes only</div>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.85rem;">
                                    <div style="background:rgba(179,123,236,.12); border:1px solid rgba(179,123,236,.25); border-radius:12px; padding:1rem; text-align:center;">
                                        <div style="font-size:1.5rem; font-weight:900; color:var(--color-purple);">&#8369;50</div>
                                        <div style="font-size:.8rem; color:rgba(255,255,255,.6); margin-top:.25rem;">first 30 min</div>
                                        <div style="font-size:.78rem; color:rgba(255,255,255,.7); margin-top:.5rem; line-height:1.4;">Starter rate &mdash; <em>initial 30-min payment only</em></div>
                                    </div>
                                    <div style="background:rgba(95,133,218,.1); border:1px solid rgba(95,133,218,.3); border-radius:12px; padding:1rem; text-align:center;">
                                        <div style="font-size:1.5rem; font-weight:900; color:var(--color-blue);">&#8369;40</div>
                                        <div style="font-size:.8rem; color:rgba(255,255,255,.6); margin-top:.25rem;">per 30-min extension</div>
                                        <div style="font-size:.78rem; color:rgba(255,255,255,.7); margin-top:.5rem; line-height:1.4;">Each additional 30 min after that</div>
                                    </div>
                                </div>
                                <div class="faq-highlight info" style="margin-top:.9rem; margin-bottom:0;">
                                    <i class="fas fa-circle-info me-2" style="color:var(--color-blue);"></i>
                                    <strong>Example:</strong> Pay for 30 min first (₱50), then extend twice &rarr;
                                    ₱50 + ₱40 + ₱40 = <strong>₱130 for 1 hr 30 min</strong>
                                </div>
                            </div>

                            <!-- Mode B: First payment = 1 hr or more -->
                            <div style="background:rgba(32,200,161,.05); border:1px solid rgba(32,200,161,.25); border-radius:16px; padding:1.3rem 1.4rem; margin:1.2rem 0;">
                                <div style="font-size:.7rem; font-weight:800; letter-spacing:1px; color:var(--color-mint); text-transform:uppercase; margin-bottom:.6rem;">MODE B &mdash; First payment is 1 hour or more</div>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.85rem;">
                                    <div style="background:rgba(32,200,161,.1); border:1px solid rgba(32,200,161,.3); border-radius:12px; padding:1rem; text-align:center;">
                                        <div style="font-size:1.5rem; font-weight:900; color:var(--color-mint);">&#8369;80</div>
                                        <div style="font-size:.8rem; color:rgba(255,255,255,.6); margin-top:.25rem;">per hour</div>
                                        <div style="font-size:.78rem; color:rgba(255,255,255,.7); margin-top:.5rem; line-height:1.4;">Standard rate &mdash; applies per full hour paid</div>
                                    </div>
                                    <div style="background:rgba(95,133,218,.1); border:1px solid rgba(95,133,218,.3); border-radius:12px; padding:1rem; text-align:center;">
                                        <div style="font-size:1.5rem; font-weight:900; color:var(--color-blue);">&#8369;40</div>
                                        <div style="font-size:.8rem; color:rgba(255,255,255,.6); margin-top:.25rem;">per 30-min extension</div>
                                        <div style="font-size:.78rem; color:rgba(255,255,255,.7); margin-top:.5rem; line-height:1.4;">Each additional 30 min added later</div>
                                    </div>
                                </div>
                                <div class="faq-highlight" style="margin-top:.9rem; margin-bottom:0;">
                                    <i class="fas fa-circle-info me-2" style="color:var(--color-mint);"></i>
                                    <strong>Example:</strong> Pay for 2 hours upfront &rarr; ₱80 &times; 2 = <strong>₱160</strong>.
                                    Plus the <strong>free 30-minute bonus</strong> (1 free 30 min per 2 hrs paid), your actual play time is <strong>2 hrs 30 min</strong>.
                                </div>
                            </div>

                            <div class="faq-highlight gold">
                                <i class="fas fa-gift me-2" style="color:var(--color-secondary);"></i>
                                <strong>Remember:</strong> The <strong>free 30-min bonus per 2 hrs</strong> applies in both modes &mdash; see the dedicated question below for full details.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Q — ₱50 clarification -->
                <div class="accordion-item" data-cat="rates">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqP1b">
                            <span class="faq-q-icon">Q</span>
                            When exactly does the &#8369;50 / 30-minute rate apply?
                        </button>
                    </h2>
                    <div id="faqP1b" class="accordion-collapse collapse" data-bs-parent="#accordionRates">
                        <div class="accordion-body">
                            The <strong>&#8369;50 starter rate</strong> is a special discounted rate that applies <strong>only when your very first payment is for 30 minutes</strong>. It does <strong>not</strong> apply if your first payment already covers 1 hour or more.

                            <div class="faq-timeline" style="margin-top:1rem;">
                                <div class="faq-timeline-item">
                                    <strong>&#10003; &#8369;50 rate APPLIES &mdash; first payment = 30 min only</strong>
                                    You pay for 30 minutes to start. The rate for that first block is &#8369;50. Any time you add after that (extensions) is charged at &#8369;40 per 30 minutes.
                                </div>
                                <div class="faq-timeline-item danger">
                                    <strong>&#10007; &#8369;50 rate does NOT apply &mdash; first payment = 1 hr or more</strong>
                                    If your first payment covers 1 hour, 2 hours, etc., the standard <strong>&#8369;80/hr</strong> rate applies instead. The &#8369;50 starter rate is <em>not</em> available in this case.
                                </div>
                            </div>

                            <div class="faq-highlight warning">
                                <i class="fas fa-triangle-exclamation me-2" style="color:var(--color-coral);"></i>
                                <strong>Example &mdash; paying 2 hours upfront:</strong><br>
                                &#8369;80 &times; 2 = <strong>&#8369;160 total</strong>. The &#8369;50 first-30-min rate does <em>not</em> apply here. You also receive <strong>30 free minutes</strong> (1 free 30-min block per 2 hrs paid), so your actual play time is <strong>2 hrs 30 min</strong>.
                            </div>

                            <div class="faq-highlight">
                                <i class="fas fa-lightbulb me-2" style="color:var(--color-mint);"></i>
                                <strong>Summary:</strong><br>
                                &bull; First payment = 30 min &rarr; <strong>&#8369;50</strong>, then &#8369;40/30-min extension<br>
                                &bull; First payment = 1 hr+ &rarr; <strong>&#8369;80/hr</strong>, then &#8369;40/30-min extension
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Q — Free time bonus -->
                <div class="accordion-item" data-cat="rates">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqP2">
                            <span class="faq-q-icon">Q</span>
                            Do you offer any free gaming time or loyalty bonuses?
                        </button>
                    </h2>
                    <div id="faqP2" class="accordion-collapse collapse" data-bs-parent="#accordionRates">
                        <div class="accordion-body">
                            Yes! We reward longer sessions with <strong>free bonus time</strong>:

                            <div class="faq-highlight gold" style="margin-top:1rem;">
                                <i class="fas fa-gift me-2" style="color:var(--color-secondary);"></i>
                                <strong>Free 30 Minutes for Every 2 Hours Played</strong><br>
                                For every <strong>2 hours</strong> you play in a single session, you earn <strong>30 minutes of free gaming time</strong> — automatically!
                            </div>

                            <strong style="color:rgba(255,255,255,.9);">Bonus time examples:</strong>
                            <div style="overflow-x:auto; margin-top:.75rem;">
                                <table style="width:100%; border-collapse:collapse; font-size:.9rem;">
                                    <thead>
                                        <tr style="border-bottom:1px solid rgba(255,255,255,.1);">
                                            <th style="padding:.6rem 1rem; text-align:left; color:var(--color-mint); font-weight:700;">Hours Paid</th>
                                            <th style="padding:.6rem 1rem; text-align:left; color:var(--color-mint); font-weight:700;">Free Time Earned</th>
                                            <th style="padding:.6rem 1rem; text-align:left; color:var(--color-mint); font-weight:700;">Total Play Time</th>
                                        </tr>
                                    </thead>
                                    <tbody style="color:rgba(255,255,255,.8);">
                                        <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
                                            <td style="padding:.6rem 1rem;">2 hours</td>
                                            <td style="padding:.6rem 1rem; color:var(--color-secondary);">+30 min free</td>
                                            <td style="padding:.6rem 1rem; font-weight:600;">2 hrs 30 min</td>
                                        </tr>
                                        <tr style="border-bottom:1px solid rgba(255,255,255,.05); background:rgba(255,255,255,.02);">
                                            <td style="padding:.6rem 1rem;">4 hours</td>
                                            <td style="padding:.6rem 1rem; color:var(--color-secondary);">+1 hr free</td>
                                            <td style="padding:.6rem 1rem; font-weight:600;">5 hours</td>
                                        </tr>
                                        <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
                                            <td style="padding:.6rem 1rem;">6 hours</td>
                                            <td style="padding:.6rem 1rem; color:var(--color-secondary);">+1 hr 30 min free</td>
                                            <td style="padding:.6rem 1rem; font-weight:600;">7 hrs 30 min</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="faq-highlight">
                                <i class="fas fa-circle-info me-2" style="color:var(--color-mint);"></i>
                                Free time is applied automatically at the end of every completed 2-hour block. You do not need to ask for it — just keep playing!
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" data-cat="rates">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqP3">
                            <span class="faq-q-icon">Q</span>
                            What payment methods are accepted?
                        </button>
                    </h2>
                    <div id="faqP3" class="accordion-collapse collapse" data-bs-parent="#accordionRates">
                        <div class="accordion-body">
                            We accept the following payment methods:
                            <ul style="color:rgba(255,255,255,.8); margin-top:.75rem; line-height:2;">
                                <li><i class="fas fa-money-bill-wave me-2" style="color:var(--color-mint);"></i> Cash (at the front desk)</li>
                                <li><i class="fas fa-mobile-alt me-2" style="color:var(--color-mint);"></i> GCash</li>
                            </ul>
                            Please ask our staff for the latest available payment options.
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- end rates group -->


        <!-- ════════════════════════════════════════════
             GROUP 4 — ACCOUNT & MEMBERSHIP
             ════════════════════════════════════════════ -->
        <div class="faq-group" data-group="account">
            <div class="faq-group-header">
                <div class="faq-group-icon purple"><i class="fas fa-user-shield" style="color:#fff;"></i></div>
                <h2 class="faq-group-title">Account &amp; Membership</h2>
            </div>
            <div class="accordion faq-accordion" id="accordionAccount">

                <div class="accordion-item" data-cat="account">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqA1">
                            <span class="faq-q-icon">Q</span>
                            Do I need an account to play?
                        </button>
                    </h2>
                    <div id="faqA1" class="accordion-collapse collapse" data-bs-parent="#accordionAccount">
                        <div class="accordion-body">
                            You can walk in and play without an account on a <strong>first-come, first-served</strong> basis. However, creating a free account lets you <strong>reserve units in advance</strong>, track your session history, and access exclusive member perks during events and tournaments.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" data-cat="account">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqA2">
                            <span class="faq-q-icon">Q</span>
                            How do I register for an account?
                        </button>
                    </h2>
                    <div id="faqA2" class="accordion-collapse collapse" data-bs-parent="#accordionAccount">
                        <div class="accordion-body">
                            Click the <strong>"Register"</strong> button in the top-right navigation bar of any page. Fill in your name, email, and a password. Once registered, you can log in and start reserving units immediately.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" data-cat="account">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqA3">
                            <span class="faq-q-icon">Q</span>
                            I forgot my password. How do I recover it?
                        </button>
                    </h2>
                    <div id="faqA3" class="accordion-collapse collapse" data-bs-parent="#accordionAccount">
                        <div class="accordion-body">
                            On the Login page, click <strong>"Forgot Password?"</strong> and enter the email address linked to your account. A password reset link will be sent to that email. Check your inbox (and spam folder just in case) and follow the instructions to set a new password.
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- end account group -->


        <!-- ════════════════════════════════════════════
             GROUP 5 — GENERAL
             ════════════════════════════════════════════ -->
        <div class="faq-group" data-group="general">
            <div class="faq-group-header">
                <div class="faq-group-icon gold"><i class="fas fa-gamepad" style="color:#fff;"></i></div>
                <h2 class="faq-group-title">General</h2>
            </div>
            <div class="accordion faq-accordion" id="accordionGeneral">

                <div class="accordion-item" data-cat="general">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqG1">
                            <span class="faq-q-icon">Q</span>
                            What consoles are available?
                        </button>
                    </h2>
                    <div id="faqG1" class="accordion-collapse collapse" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            We currently offer <strong>PlayStation 4, PlayStation 5, and Xbox Series X</strong> units with premium displays. Our lineup may expand — check the <a href="index.php#units" style="color:var(--color-mint);">Units</a> section for current availability.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" data-cat="general">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqG2">
                            <span class="faq-q-icon">Q</span>
                            Where are you located and what are your operating hours?
                        </button>
                    </h2>
                    <div id="faqG2" class="accordion-collapse collapse" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            We are located in <strong>Dasmariñas, Cavite</strong>. For exact address and the latest operating hours, please visit our <a href="index.php#contact" style="color:var(--color-mint);">Contact</a> section or reach us via our social media pages.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" data-cat="general">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqG3">
                            <span class="faq-q-icon">Q</span>
                            Are tournaments open to all members?
                        </button>
                    </h2>
                    <div id="faqG3" class="accordion-collapse collapse" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            Yes! Our monthly tournaments are open to all registered members. Watch our <a href="index.php#events" style="color:var(--color-mint);">Events</a> section for upcoming tournament schedules, game titles, and registration details.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" data-cat="general">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqG4">
                            <span class="faq-q-icon">Q</span>
                            I have a question not listed here. How do I contact you?
                        </button>
                    </h2>
                    <div id="faqG4" class="accordion-collapse collapse" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            We'd love to hear from you! You can reach us through:
                            <ul style="color:rgba(255,255,255,.8); margin-top:.75rem; line-height:2;">
                                <li><i class="fas fa-envelope me-2" style="color:var(--color-mint);"></i> Our <a href="index.php#contact" style="color:var(--color-mint);">Contact Form</a> on the website.</li>
                                <li><i class="fab fa-facebook-f me-2" style="color:var(--color-mint);"></i> Facebook page (link in the footer).</li>
                                <li><i class="fas fa-store me-2" style="color:var(--color-mint);"></i> Walk-in — chat with our friendly staff directly at the hub.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- end general group -->


        <!-- ── CTA Banner ── -->
        <div class="faq-cta" data-aos="fade-up">
            <h3>Still have questions?</h3>
            <p>Our team is happy to help you with anything else you need to know before your visit.</p>
            <a href="index.php#contact" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-envelope me-2"></i> Contact Us
            </a>
            <a href="reserve.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-gamepad me-2"></i> Reserve a Unit
            </a>
        </div>

    </div>
</section>

<?php include 'sections/footer.php'; ?>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

<!-- Bootstrap JS (local) -->
<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script src="assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── AOS
    AOS.init({ duration: 700, once: true, offset: 60 });

    // ── Highlight open accordion items with a class
    document.querySelectorAll('.faq-accordion .accordion-collapse').forEach(function (el) {
        el.addEventListener('show.bs.collapse', function () {
            this.closest('.accordion-item').classList.add('open-item');
        });
        el.addEventListener('hide.bs.collapse', function () {
            this.closest('.accordion-item').classList.remove('open-item');
        });
        if (el.classList.contains('show')) {
            el.closest('.accordion-item').classList.add('open-item');
        }
    });

    // ── Category filter
    const catBtns  = document.querySelectorAll('.faq-cat-btn');
    const groups   = document.querySelectorAll('.faq-group');
    const allItems = document.querySelectorAll('.faq-accordion .accordion-item');

    catBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            catBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');

            const cat = this.dataset.cat;

            groups.forEach(function (g) { g.removeAttribute('data-hidden'); });
            allItems.forEach(function (item) { item.removeAttribute('data-hidden'); });

            if (cat !== 'all') {
                groups.forEach(function (g) {
                    if (g.dataset.group !== cat) {
                        g.setAttribute('data-hidden', 'true');
                    }
                });
            }
        });
    });

    // ── Search
    function doSearch(query) {
        query = query.trim().toLowerCase();

        groups.forEach(function (g) { g.removeAttribute('data-hidden'); });
        allItems.forEach(function (item) { item.removeAttribute('data-hidden'); });

        // reset category pills
        catBtns.forEach(function (b) { b.classList.remove('active'); });
        document.querySelector('.faq-cat-btn[data-cat="all"]').classList.add('active');

        if (!query) return;

        allItems.forEach(function (item) {
            const text = item.innerText.toLowerCase();
            if (!text.includes(query)) {
                item.setAttribute('data-hidden', 'true');
            }
        });

        // hide groups with no visible items
        groups.forEach(function (g) {
            const visible = g.querySelectorAll('.accordion-item:not([data-hidden="true"])');
            if (visible.length === 0) {
                g.setAttribute('data-hidden', 'true');
            }
        });
    }

    document.getElementById('faqSearchInput').addEventListener('input', function () {
        doSearch(this.value);
    });
    document.getElementById('faqSearchBtn').addEventListener('click', function () {
        doSearch(document.getElementById('faqSearchInput').value);
    });
    document.getElementById('faqSearchInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') doSearch(this.value);
    });
});
</script>
</body>
</html>
