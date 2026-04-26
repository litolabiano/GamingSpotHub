<?php require_once __DIR__ . '/includes/session_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Progress — Good Spot Gaming Hub</title>
    <link href="assets/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="assets/libs/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── Hero ── */
        .prog-hero {
            position: relative;
            min-height: 36vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0d1117 0%, #0a2151 60%, #1a0a2e 100%);
            overflow: hidden;
            padding-top: 80px;
        }
        .prog-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 110%, rgba(95,133,218,.2) 0%, transparent 70%);
            pointer-events: none;
        }
        .prog-hero-content { position: relative; z-index: 2; text-align: center; width: 100%; }
        .prog-hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900; line-height: 1.15; margin-bottom: 1rem;
        }
        .prog-hero-title span {
            background: linear-gradient(135deg, var(--color-mint), var(--color-blue), var(--color-purple));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }

        /* ── Main section ── */
        .prog-section {
            background: linear-gradient(180deg, #0d1117 0%, #1a2332 100%);
            padding: 70px 0 100px;
        }

        /* ── Overall Stats Bar ── */
        .stats-bar {
            display: flex; flex-wrap: wrap; gap: 16px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 20px; padding: 24px 28px;
            margin-bottom: 50px;
        }
        .stat-chip {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,.04); border-radius: 12px;
            padding: 12px 18px; flex: 1 1 150px;
        }
        .stat-chip .num { font-size: 1.7rem; font-weight: 900; line-height: 1; }
        .stat-chip .lbl { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: 2px; }
        .stat-chip.done .num { color: #20c8a1; }
        .stat-chip.warn .num { color: #f1a83c; }
        .stat-chip.err  .num { color: #fb566b; }
        .stat-chip.info .num { color: #5f85da; }

        /* ── Feature Category ── */
        .feat-category { margin-bottom: 48px; }
        .feat-cat-header {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 20px;
        }
        .feat-cat-icon {
            width: 46px; height: 46px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .feat-cat-icon.teal   { background: linear-gradient(135deg, #20c8a1, #5f85da); }
        .feat-cat-icon.purple { background: linear-gradient(135deg, #b37bec, #fb566b); }
        .feat-cat-icon.coral  { background: linear-gradient(135deg, #fb566b, #f1e1aa); }
        .feat-cat-icon.blue   { background: linear-gradient(135deg, #5f85da, #20c8a1); }
        .feat-cat-icon.gold   { background: linear-gradient(135deg, #f1e1aa, #b37bec); }
        .feat-cat-title { font-size: 1.35rem; font-weight: 800; margin: 0; }

        /* ── Category progress bar ── */
        .cat-progress-wrap { margin-bottom: 18px; }
        .cat-progress-meta { display: flex; justify-content: space-between; font-size: .8rem; color: rgba(255,255,255,.5); margin-bottom: 6px; }
        .cat-progress-bar {
            height: 6px; border-radius: 6px;
            background: rgba(255,255,255,.08); overflow: hidden;
        }
        .cat-progress-fill { height: 100%; border-radius: 6px; transition: width 1.2s cubic-bezier(.25,.46,.45,.94); }

        /* ── Feature Items ── */
        .feat-list { display: flex; flex-direction: column; gap: 10px; }
        .feat-item {
            display: flex; align-items: flex-start; gap: 14px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px; padding: 14px 16px;
            transition: border-color .3s, background .3s;
        }
        .feat-item:hover { border-color: rgba(255,255,255,.13); background: rgba(255,255,255,.05); }
        .feat-item.done  { border-left: 3px solid #20c8a1; }
        .feat-item.warn  { border-left: 3px solid #f1a83c; }
        .feat-item.missing { border-left: 3px solid #fb566b; }
        .feat-item.partial { border-left: 3px solid #5f85da; }

        .feat-badge {
            min-width: 28px; height: 28px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 800; flex-shrink: 0; margin-top: 1px;
        }
        .feat-badge.done    { background: rgba(32,200,161,.15); color: #20c8a1; }
        .feat-badge.warn    { background: rgba(241,168,60,.15);  color: #f1a83c; }
        .feat-badge.missing { background: rgba(251,86,107,.15);  color: #fb566b; }
        .feat-badge.partial { background: rgba(95,133,218,.15);  color: #5f85da; }

        .feat-info { flex: 1; min-width: 0; }
        .feat-title { font-size: .95rem; font-weight: 700; color: #f0f0f0; margin-bottom: 3px; }
        .feat-desc  { font-size: .82rem; color: rgba(255,255,255,.55); line-height: 1.6; }
        .feat-tag   {
            display: inline-block; font-size: .68rem; font-weight: 700;
            padding: 2px 8px; border-radius: 20px; margin-left: 8px; vertical-align: middle;
            letter-spacing: .5px; text-transform: uppercase;
        }
        .tag-done    { background: rgba(32,200,161,.15); color: #20c8a1; }
        .tag-warn    { background: rgba(241,168,60,.15);  color: #f1a83c; }
        .tag-missing { background: rgba(251,86,107,.15);  color: #fb566b; }
        .tag-partial { background: rgba(95,133,218,.15);  color: #5f85da; }

        /* ── Bug & Issue Log ── */
        .issue-card {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px; padding: 20px 22px;
            margin-bottom: 12px;
            transition: border-color .3s;
        }
        .issue-card:hover { border-color: rgba(255,255,255,.13); }
        .issue-card.fixed   { border-left: 3px solid #20c8a1; }
        .issue-card.open    { border-left: 3px solid #fb566b; }
        .issue-card.partial { border-left: 3px solid #f1a83c; }
        .issue-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 6px; }
        .issue-icon { font-size: 1rem; margin-top: 2px; }
        .issue-icon.fixed   { color: #20c8a1; }
        .issue-icon.open    { color: #fb566b; }
        .issue-icon.partial { color: #f1a83c; }
        .issue-title { font-weight: 700; font-size: .95rem; flex: 1; }
        .issue-file {
            font-size: .72rem; color: rgba(255,255,255,.4);
            font-family: monospace; padding: 2px 7px;
            background: rgba(255,255,255,.06); border-radius: 5px; white-space: nowrap;
        }
        .issue-desc { font-size: .83rem; color: rgba(255,255,255,.6); line-height: 1.65; margin-left: 28px; }
        .issue-fix  { font-size: .8rem; color: #20c8a1; margin-top: 5px; margin-left: 28px; font-style: italic; }

        /* ── Legend ── */
        .legend { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 32px; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: .8rem; color: rgba(255,255,255,.6); }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero ── -->
<section class="prog-hero">
    <div class="container">
        <div class="prog-hero-content" data-aos="fade-up">
            <span class="section-tag">Dev Build</span>
            <h1 class="prog-hero-title">Implementation <span>Progress</span></h1>
            <p style="color:rgba(255,255,255,.65); max-width:520px; margin:0 auto; font-size:1.05rem; line-height:1.7;">
                Live feature tracker — based on project docs, FAQs, and full codebase scan.
            </p>
        </div>
    </div>
</section>

<!-- ── Body ── -->
<section class="prog-section">
<div class="container">

<?php
/* ════════════════════════════════════════════════
   DATA — Feature definitions
   status: done | partial | warn | missing
   ════════════════════════════════════════════════ */
$categories = [

    [
        'title' => 'Authentication & Users',
        'icon'  => 'fa-user-shield', 'color' => 'teal',
        'items' => [
            ['status'=>'done',    'title'=>'User Registration (bcrypt + email verification)',      'desc'=>'register.php sends verification email via PHPMailer. Token stored with 24h expiry.'],
            ['status'=>'done',    'title'=>'Login / Logout',                                       'desc'=>'Login validates with password_verify(). Session stores user_id, role, email. Logout destroys session.'],
            ['status'=>'done',    'title'=>'Forgot Password (anti-enumeration)',                    'desc'=>'Always shows same success message. Reset token stored with 1hr expiry using MySQL NOW().'],
            ['status'=>'done',    'title'=>'Role-based Access Control',                            'desc'=>'requireRole() enforces owner/shopkeeper on admin.php. requireLogin() on all customer pages.'],
            ['status'=>'done',    'title'=>'Universal Navbar',                                     'desc'=>'navbar.php reused across all pages. Absolute $base_url links work from any directory depth.'],
            ['status'=>'missing', 'title'=>'3-Strike Cancellation Ban Enforcement',                'desc'=>'FAQs describe a 1-week reservation ban after 3 consecutive cancels. No column, no check in reserve.php or createReservation().', 'tag'=>'FAQ Mismatch'],
            ['status'=>'missing', 'title'=>'Email Notification on Reservation Confirmed',          'desc'=>'Admin confirms a reservation, but no email is sent to the customer. FAQs imply a confirmation notification.', 'tag'=>'Missing'],
        ],
    ],

    [
        'title' => 'Reservation System',
        'icon'  => 'fa-calendar-check', 'color' => 'blue',
        'items' => [
            ['status'=>'done',    'title'=>'Customer Reservation Creation',                        'desc'=>'reserve.php — full form with console type, rental mode, date/time, preferred unit, downpayment.'],
            ['status'=>'done',    'title'=>'Downpayment Recorded as Transaction',                  'desc'=>'createReservation() now calls recordTransaction() after INSERT so the ledger reflects the payment. (Fixed this session.)'],
            ['status'=>'done',    'title'=>'PRG Pattern (no double-submit on refresh)',            'desc'=>'POST → session flash → redirect → GET. Prevents duplicate reservations on page reload.'],
            ['status'=>'done',    'title'=>'Admin Approval (Confirm / Cancel / No-Show)',          'desc'=>'admin_sections/reservations.php — full action buttons. cancel_reservation sets cancelled_by=admin.'],
            ['status'=>'done',    'title'=>'Convert Reservation → Active Session',                 'desc'=>'convertReservationToSession() assigns a console, starts the session, marks reservation as converted.'],
            ['status'=>'done',    'title'=>'Customer Cancellation (Dashboard)',                    'desc'=>'dashboard.php AJAX cancel flow. Sets status=cancelled, cancelled_by=user. Shows confirmation modal.'],
            ['status'=>'done',    'title'=>'Availability Check on Reserve Form',                   'desc'=>'ajax/check_unit_availability.php checks reservations table for overlapping slots.'],
            ['status'=>'done',    'title'=>'Preferred Unit Pre-selection',                         'desc'=>'createReservation() now accepts $preferred_unit_id as 10th param and stores it in reservations.console_id. The preferred unit selected by the customer is correctly persisted. (Fixed this session.)'],
            ['status'=>'done',    'title'=>'Inconvenience Fee on Late Cancellation',              'desc'=>'cancel_reservation.php deducts configurable inconvenience_fee (\u20b150, from system_settings) when customer cancels after reserved start time. Net refund shown in message and returned as amount field. (Fixed this session.)'],
        ],
    ],

    [
        'title' => 'Refund & Cancellation Flow',
        'icon'  => 'fa-rotate-left', 'color' => 'coral',
        'items' => [
            ['status'=>'done',    'title'=>'Admin: Issue Refund for Session (openRefundModal)',    'desc'=>'Sessions section — unified refundSessionModal. recordTransaction() stores negative amount.'],
            ['status'=>'done',    'title'=>'Admin: Issue Refund for Reservation (process_refund)', 'desc'=>'Cancelled Reservations table — Refund button calls openRefundModal(null, ..., reservationId). Unified modal routes to process_refund action.'],
            ['status'=>'done',    'title'=>'Refund Modal Unified (single modal, dual mode)',       'desc'=>'openRefundModal() 5th param = reservationId switches mode: pre-fills amount, locks fields, sets action=process_refund.'],
            ['status'=>'done',    'title'=>'transactions.session_id Nullable',                     'desc'=>'ALTER TABLE transactions MODIFY session_id INT NULL. Allows reservation-only refunds without a session. (Fixed this session.)'],
            ['status'=>'done',    'title'=>'NULL cancelled_by Handled',                            'desc'=>'Old cancelled rows have cancelled_by=NULL. Both UI and PHP now accept NULL to show/process refund button.'],
            ['status'=>'done',    'title'=>'Cancelled Reservations Table in Admin',                'desc'=>'admin_sections/reservations.php — second table showing all cancelled reservations with refund status.'],
            ['status'=>'missing', 'title'=>'Refund Status on Customer Dashboard',                  'desc'=>'Customer can see cancelled reservations but has no visibility into whether admin has issued the refund (refund_issued column not surfaced on dashboard).', 'tag'=>'Missing'],
        ],
    ],

    [
        'title' => 'Gaming Sessions',
        'icon'  => 'fa-gamepad', 'color' => 'purple',
        'items' => [
            ['status'=>'done',    'title'=>'Start Session (admin + upfront payment)',              'desc'=>'startSession() creates session, marks console in_use, records initial transaction.'],
            ['status'=>'done',    'title'=>'End Session (auto cost calculation)',                  'desc'=>'endSession() computes duration + totalCost via computeRentalFee(). Marks console available.'],
            ['status'=>'done',    'title'=>'Extend Session (planned_minutes)',                     'desc'=>'extend_session action adds extra minutes to planned_minutes for hourly sessions.'],
            ['status'=>'done',    'title'=>'Collect Mid-Session Payment',                          'desc'=>'collect_payment action records partial payment without ending session. Shows balance due.'],
            ['status'=>'done',    'title'=>'Early End + Refund (including ₱0 case)',               'desc'=>'early_end action calls endSession() then records refund. ₱0-paid sessions end cleanly with no transaction — the guard now allows zero-refund early_end. (Fixed this session.)'],
            ['status'=>'done',    'title'=>'Walk-in / No Account Sessions',                        'desc'=>'System user (user_id=0, role=walkin) satisfies NOT NULL FK constraints. Admin selects Walk-in from dropdown → session starts with user_id=0. Styled badge shown in session list. (Added this session.)'],
            ['status'=>'done',    'title'=>'Correct Billing for Early-Ended Sessions',             'desc'=>'computeRentalFee() now charges ACTUAL elapsed time for early-ended hourly sessions (not planned duration). A 1h45m booking ended after 2min costs ₱20, not ₱140. (Fixed this session.)'],
            ['status'=>'done',    'title'=>'Pending Payments — No False Positives',                'desc'=>'Walk-in / early-end sessions with ₱0 paid no longer appear as outstanding balances. Filter requires paidSoFar > 0 for completed sessions. (Fixed this session.)'],
            ['status'=>'done',    'title'=>'Session Refund Button (all 4 buttons visible)',       'desc'=>'Flex-wrap layout fix — End, Pay, Refund, Extend now all visible and clickable. (Fixed previous session.)'],
            ['status'=>'done',    'title'=>'Edit End Time (inline editor)',                        'desc'=>'Completed session rows allow click-to-edit end time via AJAX, recalculates cost server-side.'],
            ['status'=>'partial', 'title'=>'Live Session Timer',                                  'desc'=>'JS timer counts up in Sessions admin. Timer resets if page is hard-refreshed — server state is authoritative.'],
            ['status'=>'done',    'title'=>'Rental Fee Calculation (₱50 first 30min / ₱80/hr)',  'desc'=>'computeRentalFee() handles open_time, hourly, unlimited modes with correct tier logic.'],
            ['status'=>'done',    'title'=>'CSRF Protection on All Admin Modal Forms',             'desc'=>'All 6 POST forms in admin_sections/modals.php now include <?= csrfField() ?>. Resolves "Security check failed" error. (Fixed this session.)'],
        ],
    ],

    [
        'title' => 'Financial & Reporting',
        'icon'  => 'fa-chart-line', 'color' => 'gold',
        'items' => [
            ['status'=>'done',    'title'=>'Transactions Table (ledger)',                          'desc'=>'All payments + refunds recorded. Negative amounts = refunds. payment_note field for context.'],
            ['status'=>'done',    'title'=>'Admin Financial Dashboard',                            'desc'=>'admin_sections/financial.php — daily revenue, total transactions, outstanding balances.'],
            ['status'=>'done',    'title'=>'Daily Sales Report (admin)',                           'desc'=>'getDailySalesReport() returns total revenue + sessions for any given date.'],
            ['status'=>'missing', 'title'=>'PDF/CSV Report Export',                               'desc'=>'reports table exists in schema. No report generation code found (no PDF library, no export handler).', 'tag'=>'Missing'],
            ['status'=>'missing', 'title'=>'Tournament Revenue Tracking',                         'desc'=>'tournament_participants has entry_fee but no transaction recorded when someone registers for a tournament.', 'tag'=>'Missing'],
            ['status'=>'partial', 'title'=>'Reservation Downpayment in Financial Reports',        'desc'=>'Now recorded via recordTransaction() but session_id=NULL may cause some report queries to miss these rows if they JOIN on session_id.', 'tag'=>'Check Required'],
        ],
    ],

    [
        'title' => 'Customer Dashboard',
        'icon'  => 'fa-gauge-high', 'color' => 'teal',
        'items' => [
            ['status'=>'done',    'title'=>'Session History & Stats',                              'desc'=>'Total time played, total spend, session count shown with Chart.js visualizations.'],
            ['status'=>'done',    'title'=>'Active Session Live Tracker',                         'desc'=>'Shows current session unit, elapsed time, estimated cost in real-time.'],
            ['status'=>'done',    'title'=>'Upcoming & Past Reservations',                        'desc'=>'getMyReservations() lists all reservations with status badges.'],
            ['status'=>'done',    'title'=>'Reservation Cancellation (AJAX)',                     'desc'=>'Cancel button triggers confirmation modal → AJAX call → toast notification → row removal.'],
            ['status'=>'missing', 'title'=>'Refund Status Visibility',                            'desc'=>'Customer cannot see whether their cancelled reservation has been refunded yet (refund_issued not shown).'],
            ['status'=>'missing', 'title'=>'Game Request UI',                                     'desc'=>'game_requests table exists. No customer-facing UI to submit or track game requests.', 'tag'=>'Missing'],
        ],
    ],

    [
        'title' => 'Tournaments & Games Library',
        'icon'  => 'fa-trophy', 'color' => 'gold',
        'items' => [
            ['status'=>'done',    'title'=>'Admin Tournament Management',                         'desc'=>'admin_sections/tournaments.php — full CRUD: create tournament, update status (upcoming/scheduled/ongoing/completed/cancelled), register/remove participants. Wired into admin sidebar with badge counter. Schema fixed: game_name, created_by, expanded status + console_type enums. (Confirmed working this session.)'],
            ['status'=>'partial', 'title'=>'Customer Tournament Registration',                    'desc'=>'tournament_register.php exists with a styled registration form. However, it uses a separate tournament_registrations table instead of the proper tournament_participants table and does not dynamically list admin-created tournaments.', 'tag'=>'Needs Integration'],
            ['status'=>'done',    'title'=>'Games Library Admin UI',                              'desc'=>'admin_sections/games.php built this session — CRUD table grouped by platform with search filter, Add/Edit/Hide/Delete actions (POST handlers in admin.php). games table created with 12 seeded titles. Wired into admin sidebar as "Games" nav item.'],
            ['status'=>'missing', 'title'=>'Customer Game Library View',                          'desc'=>'No public page listing available games per console type. games table now exists with data — a public games.php browse page still needs to be built.', 'tag'=>'Not Started'],
        ],
    ],


];



$issues = [
    ['status'=>'fixed',   'title'=>'Downpayment not recorded in transactions',                      'file'=>'db_functions.php',   'desc'=>'createReservation() only saved to reservations table. Financial ledger had no record of payment.', 'fix'=>'Added recordTransaction() call inside createReservation() after successful INSERT.'],
    ['status'=>'fixed',   'title'=>'transactions.session_id NOT NULL blocked reservation refunds',  'file'=>'MySQL schema',        'desc'=>'Refund for reservation (no session) passed NULL → DB rejected with column cannot be null fatal error.', 'fix'=>'ALTER TABLE transactions MODIFY session_id INT NULL.'],
    ['status'=>'fixed',   'title'=>'Session Refund button unclickable (grid overflow)',              'file'=>'sessions.php',        'desc'=>'4 buttons in grid-template-columns:1fr 1fr caused Refund and Extend to be cut off and unreachable.', 'fix'=>'Changed to display:flex; flex-wrap:wrap with flex:1 1 70px per button.'],
    ['status'=>'fixed',   'title'=>'Two disconnected refund systems',                               'file'=>'admin.php + modals',  'desc'=>'Session refunds used openRefundModal(). Reservation refunds used a completely separate form+gspotConfirm.', 'fix'=>'Extended openRefundModal() with 5th param reservationId. One modal now handles both modes.'],
    ['status'=>'fixed',   'title'=>'process_refund used raw INSERT with wrong column names',        'file'=>'admin.php',           'desc'=>'Raw INSERT used notes and transaction_date columns that don\'t exist. Should use payment_note.', 'fix'=>'Replaced raw INSERT with recordTransaction() which uses the correct schema.'],
    ['status'=>'fixed',   'title'=>'cancelled_by NULL causes refund button to be hidden',           'file'=>'reservations.php',    'desc'=>'Old cancelled rows have cancelled_by=NULL. Strict === \'user\' check excluded all pre-migration rows.', 'fix'=>'Changed to in_array($r[\'cancelled_by\'], [\'user\', null], true) in UI and SQL uses OR IS NULL.'],
    ['status'=>'fixed',   'title'=>'getCancelledReservations() undefined',                          'file'=>'db_functions.php',    'desc'=>'Function was not present in db_functions.php. Earlier edit silently failed.', 'fix'=>'Added function correctly with full JOIN and ORDER BY.'],
    ['status'=>'fixed',   'title'=>'"Security check failed" on all admin modal form submissions',   'file'=>'admin_sections/modals.php', 'desc'=>'All 6 POST forms (Start Session, End Session, Collect Payment, Issue Refund, Add Reservation, Convert Reservation) were missing <?= csrfField() ?> tokens.', 'fix'=>'Injected csrfField() into each POST form. AJAX endpoints already use role-based auth instead.'],
    ['status'=>'fixed',   'title'=>'Early-end ₱0 refund blocked by dual guard',                    'file'=>'ajax/refund.php + admin.php', 'desc'=>'Walk-in sessions with ₱0 paid triggered both JS and PHP refund_amount <= 0 guards, preventing early-end.', 'fix'=>'Both guards now allow refund_amount=0 when action_type=early_end. Amount field locked read-only at 0 in modal.'],
    ['status'=>'fixed',   'title'=>'Pending Payments shows false ₱140 due on ended walk-in session','file'=>'admin.php + db_functions.php', 'desc'=>'Hourly sessions ended early still had planned total_cost in DB. Sessions with ₱0 paid + no refund showed as outstanding.', 'fix'=>'computeRentalFee() now charges actual elapsed time for early-end. Pending Payments filter adds paidSoFar > 0 guard.'],
    ['status'=>'fixed',   'title'=>'Walk-in session FK constraint failure',                         'file'=>'db_functions.php',    'desc'=>'Walk-in user existed with user_id=0 but constant was set to 25 (non-existent). FK on gaming_sessions rejected INSERT.', 'fix'=>'WALKIN_USER_ID updated to 0 to match actual DB row. admin.php handler no longer requires user_id in validation.'],
    ['status'=>'fixed',   'title'=>'createReservation() ignores $preferred_unit_id',               'file'=>'db_functions.php',    'desc'=>'reserve.php passed preferred_unit_id as 10th argument but function signature only had 9 params. The preferred unit was silently dropped.', 'fix'=>'Added $preferred_unit_id = null as 10th param, included console_id in INSERT, fixed bind_param type string to iississsdsii (12 params).'],
    ['status'=>'fixed',   'title'=>'3-Strike cancellation ban not enforced on reserve form',      'file'=>'reserve.php + cancel_reservation.php', 'desc'=>'Ban columns existed but ban check and streak increment were both already implemented across both files.', 'fix'=>'Confirmed as already implemented: cancel_reservation.php increments streak + sets 7-day ban; reserve.php blocks new reservations during ban window.'],
    ['status'=>'fixed',   'title'=>'Inconvenience fee on late cancellations not calculated',       'file'=>'ajax/cancel_reservation.php', 'desc'=>'$isLateCancel was detected but amount was not reduced — full downpayment reported, with vague "staff will deduct" message.', 'fix'=>'Added inconvenience_fee setting (₱50 default) to system_settings. cancel_reservation.php now auto-deducts the fee and returns precise gross_amount, inconvenience_fee, and net amount fields.'],
    ['status'=>'fixed',   'title'=>'Refund status not visible to customer',                       'file'=>'dashboard.php',       'desc'=>'Confirmed as already implemented. Cancelled reservations show either a green "Refunded" or amber "Refund pending" badge based on refund_issued column.', 'fix'=>'No change needed — feature was already built.'],
    ['status'=>'fixed',   'title'=>'Financial report queries may miss session_id=NULL transactions','file'=>'admin.php',           'desc'=>'Main $finStats query goes FROM transactions directly (no gaming_sessions JOIN). Transaction history already uses LEFT JOIN gaming_sessions. No INNER JOINs on transactions table found.', 'fix'=>'Confirmed as already correct — all transaction-based queries use LEFT JOIN or no join at all.'],
];

/* ══ Compute stats ══ */
$totDone = $totPartial = $totWarn = $totMissing = 0;
foreach ($categories as $cat) {
    foreach ($cat['items'] as $it) {
        match($it['status']) {
            'done'    => $totDone++,
            'partial' => $totPartial++,
            'warn'    => $totWarn++,
            'missing' => $totMissing++,
            default   => null,
        };
    }
}
$totAll  = $totDone + $totPartial + $totWarn + $totMissing;
$pctDone = $totAll ? round(($totDone / $totAll) * 100) : 0;
$pctPart = $totAll ? round((($totDone + $totPartial) / $totAll) * 100) : 0;
?>

    <!-- ── Stats bar ── -->
    <div class="stats-bar" data-aos="fade-up">
        <div class="stat-chip info">
            <div>
                <div class="num"><?= $totAll ?></div>
                <div class="lbl">Total Features</div>
            </div>
        </div>
        <div class="stat-chip done">
            <div>
                <div class="num"><?= $totDone ?></div>
                <div class="lbl">Implemented</div>
            </div>
        </div>
        <div class="stat-chip warn">
            <div>
                <div class="num"><?= $totPartial ?></div>
                <div class="lbl">Partial / Needs Check</div>
            </div>
        </div>
        <div class="stat-chip err">
            <div>
                <div class="num"><?= $totMissing ?></div>
                <div class="lbl">Missing / Not Started</div>
            </div>
        </div>
        <div class="stat-chip done" style="flex:2 1 220px;">
            <div style="width:100%">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:.85rem;color:rgba(255,255,255,.6);">Overall Completion</span>
                    <span style="font-weight:800;color:#20c8a1;font-size:1.1rem;"><?= $pctDone ?>%</span>
                </div>
                <div style="height:10px;border-radius:10px;background:rgba(255,255,255,.08);overflow:hidden;">
                    <div style="height:100%;width:<?= $pctDone ?>%;background:linear-gradient(90deg,#20c8a1,#5f85da);border-radius:10px;transition:width 1.4s cubic-bezier(.25,.46,.45,.94);"></div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:5px;font-size:.72rem;color:rgba(255,255,255,.35);">
                    <span><?= $totDone ?> done · <?= $totPartial ?> partial</span>
                    <span><?= $totMissing ?> missing</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Legend ── -->
    <div class="legend" data-aos="fade-up">
        <div class="legend-item"><div class="legend-dot" style="background:#20c8a1;"></div> Implemented</div>
        <div class="legend-item"><div class="legend-dot" style="background:#5f85da;"></div> Partial / Needs investigation</div>
        <div class="legend-item"><div class="legend-dot" style="background:#f1a83c;"></div> Warning / FAQ mismatch</div>
        <div class="legend-item"><div class="legend-dot" style="background:#fb566b;"></div> Missing / Not started</div>
    </div>

    <!-- ── Feature Categories ── -->
    <?php foreach ($categories as $ci => $cat):
        $catDone = count(array_filter($cat['items'], fn($i) => $i['status'] === 'done'));
        $catAll  = count($cat['items']);
        $catPct  = $catAll ? round(($catDone / $catAll) * 100) : 0;
        $fillColor = $catPct >= 80 ? '#20c8a1' : ($catPct >= 50 ? '#5f85da' : '#fb566b');
    ?>
    <div class="feat-category" data-aos="fade-up" data-aos-delay="<?= $ci * 60 ?>">
        <div class="feat-cat-header">
            <div class="feat-cat-icon <?= $cat['color'] ?>">
                <i class="fas <?= $cat['icon'] ?>" style="color:#fff;"></i>
            </div>
            <div>
                <h2 class="feat-cat-title"><?= $cat['title'] ?></h2>
            </div>
            <div style="margin-left:auto;text-align:right;">
                <span style="font-size:1.1rem;font-weight:800;color:<?= $fillColor ?>;"><?= $catDone ?>/<?= $catAll ?></span>
                <span style="font-size:.75rem;color:rgba(255,255,255,.4);display:block;"><?= $catPct ?>% done</span>
            </div>
        </div>

        <div class="cat-progress-wrap">
            <div class="cat-progress-bar">
                <div class="cat-progress-fill" style="width:<?= $catPct ?>%;background:linear-gradient(90deg,<?= $fillColor ?>,<?= $fillColor ?>99);"></div>
            </div>
        </div>

        <div class="feat-list">
        <?php foreach ($cat['items'] as $item):
            $badge = match($item['status']) {
                'done'    => ['icon'=>'✓', 'cls'=>'done'],
                'partial' => ['icon'=>'~', 'cls'=>'partial'],
                'warn'    => ['icon'=>'!', 'cls'=>'warn'],
                'missing' => ['icon'=>'✕', 'cls'=>'missing'],
                default   => ['icon'=>'?', 'cls'=>'partial'],
            };
        ?>
        <div class="feat-item <?= $item['status'] === 'warn' ? 'warn' : $item['status'] ?>">
            <div class="feat-badge <?= $badge['cls'] ?>"><?= $badge['icon'] ?></div>
            <div class="feat-info">
                <div class="feat-title">
                    <?= htmlspecialchars($item['title']) ?>
                    <?php if (!empty($item['tag'])): ?>
                    <span class="feat-tag tag-<?= $item['status'] === 'missing' ? 'missing' : ($item['status'] === 'partial' ? 'partial' : 'warn') ?>"><?= $item['tag'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="feat-desc"><?= htmlspecialchars($item['desc']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ── Issue / Bug Log ── -->
    <div class="feat-category" data-aos="fade-up">
        <div class="feat-cat-header">
            <div class="feat-cat-icon coral">
                <i class="fas fa-bug" style="color:#fff;"></i>
            </div>
            <h2 class="feat-cat-title">Bug &amp; Logic Issue Log</h2>
            <div style="margin-left:auto;">
                <span style="color:#20c8a1;font-weight:800;font-size:1.1rem;"><?= count(array_filter($issues, fn($i)=>$i['status']==='fixed')) ?> fixed</span>
                <span style="color:rgba(255,255,255,.3);margin:0 6px;">/</span>
                <span style="color:#fb566b;font-weight:800;font-size:1.1rem;"><?= count(array_filter($issues, fn($i)=>$i['status']==='open')) ?> open</span>
            </div>
        </div>

        <?php foreach ($issues as $iss): ?>
        <div class="issue-card <?= $iss['status'] ?>">
            <div class="issue-header">
                <i class="fas <?= $iss['status']==='fixed' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> issue-icon <?= $iss['status'] ?>"></i>
                <span class="issue-title"><?= htmlspecialchars($iss['title']) ?></span>
                <span class="issue-file"><?= htmlspecialchars($iss['file']) ?></span>
            </div>
            <div class="issue-desc"><?= htmlspecialchars($iss['desc']) ?></div>
            <?php if ($iss['status'] === 'fixed'): ?>
            <div class="issue-fix"><i class="fas fa-wrench" style="margin-right:5px;"></i><?= htmlspecialchars($iss['fix']) ?></div>
            <?php else: ?>
            <div class="issue-fix" style="color:#f1a83c;"><i class="fas fa-lightbulb" style="margin-right:5px;"></i><?= htmlspecialchars($iss['fix']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div><!-- /container -->
</section>

<?php include __DIR__ . '/sections/footer.php'; ?>

<script src="assets/libs/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/libs/aos/aos.js"></script>
<script>
AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
</script>
</body>
</html>
