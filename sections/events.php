<!-- ══ EVENTS / TOURNAMENTS ══════════════════════════════════════════════ -->
<?php
// Pull the next actionable tournament from the DB
$evTournament = null;
if (isset($conn)) {
    $evRes = $conn->query("
        SELECT * FROM tournaments
        WHERE status IN ('scheduled','upcoming','ongoing')
        ORDER BY
            CASE status WHEN 'scheduled' THEN 0 WHEN 'ongoing' THEN 1 ELSE 2 END ASC,
            start_date ASC
        LIMIT 1
    ");
    if ($evRes) $evTournament = $evRes->fetch_assoc();
}

// Slot count for this tournament
$evSlotsTaken = 0;
$evSlotsMax   = 16;
if ($evTournament) {
    $evMax = (int)$evTournament['max_participants'];
    $evSlotsMax = $evMax;
    $evSlotRes  = $conn->prepare("SELECT COUNT(*) AS cnt FROM tournament_participants WHERE tournament_id = ?");
    $evSlotRes->bind_param('i', $evTournament['tournament_id']);
    $evSlotRes->execute();
    $evSlotsTaken = (int)$evSlotRes->get_result()->fetch_assoc()['cnt'];
}
$evSlotsLeft = $evSlotsMax - $evSlotsTaken;
$evIsFull    = $evTournament && $evSlotsLeft <= 0;
$evIsOpen    = $evTournament && $evTournament['status'] === 'scheduled' && !$evIsFull;

$evStatusLabel = [
    'upcoming'  => 'Upcoming',
    'scheduled' => 'Open for Registration',
    'ongoing'   => 'Ongoing',
];
$evStatusColor = [
    'upcoming'  => '#f1a83c',
    'scheduled' => '#20c8a1',
    'ongoing'   => '#5f85da',
];
$evSLabel = $evTournament ? ($evStatusLabel[$evTournament['status']] ?? 'Upcoming') : '';
$evSColor = $evTournament ? ($evStatusColor[$evTournament['status']] ?? '#f1a83c') : '#f1a83c';
?>
<section id="events" style="background:linear-gradient(180deg,#07101f 0%,#0d1b2a 100%);padding:90px 0 100px;position:relative;overflow:hidden;">

    <!-- BG glow -->
    <div style="position:absolute;top:-100px;left:50%;transform:translateX(-50%);width:700px;height:400px;border-radius:50%;background:radial-gradient(ellipse,rgba(241,168,60,.06),transparent 70%);pointer-events:none;"></div>

    <div class="container" style="position:relative;z-index:2;">

        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Compete</span>
            <h2 class="section-title">Tournaments &amp; Events</h2>
            <p class="section-subtitle">Join our community battles and fight for glory and prizes</p>
        </div>

        <div class="row g-4 justify-content-center">

            <!-- Main event card -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="80">
                <div class="gsh-event-card">
                    <div class="gsh-event-bar"></div>

                    <?php if ($evTournament): ?>
                    <div style="display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap;">
                        <!-- Date block -->
                        <div class="gsh-event-date">
                            <div style="font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:4px;">Date</div>
                            <?php if ($evTournament['start_date']): ?>
                            <div style="font-size:2rem;font-weight:900;color:#f1a83c;line-height:1;"><?= date('d', strtotime($evTournament['start_date'])) ?></div>
                            <div style="font-size:13px;color:rgba(255,255,255,.6);font-weight:700;margin-top:2px;"><?= date('M Y', strtotime($evTournament['start_date'])) ?></div>
                            <?php else: ?>
                            <div style="font-size:2rem;font-weight:900;color:#f1a83c;line-height:1;">TBA</div>
                            <div style="font-size:13px;color:rgba(255,255,255,.5);font-weight:700;margin-top:4px;">2026</div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div style="flex:1;min-width:220px;">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                                <span style="background:rgba(<?= $evTournament['status']==='scheduled'?'32,200,161':'241,168,60' ?>,.15);border:1px solid rgba(<?= $evTournament['status']==='scheduled'?'32,200,161':'241,168,60' ?>,.3);color:<?= $evSColor ?>;font-size:9px;font-weight:900;letter-spacing:1.5px;padding:4px 12px;border-radius:20px;text-transform:uppercase;">
                                    <?= $evIsFull ? 'Full' : $evSLabel ?>
                                </span>
                                <span style="background:rgba(95,133,218,.1);border:1px solid rgba(95,133,218,.2);color:#8aa4e8;font-size:9px;font-weight:700;letter-spacing:.8px;padding:4px 10px;border-radius:20px;">
                                    <i class="fab fa-playstation" style="margin-right:4px;"></i><?= htmlspecialchars($evTournament['console_type']) ?>
                                </span>
                            </div>
                            <h3 style="font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:900;color:#fff;margin-bottom:6px;">
                                <?= htmlspecialchars($evTournament['tournament_name']) ?>
                            </h3>
                            <?php if (!empty($evTournament['game_name'])): ?>
                            <div style="font-size:13px;color:#5f85da;margin-bottom:8px;"><i class="fas fa-gamepad" style="margin-right:4px;"></i><?= htmlspecialchars($evTournament['game_name']) ?></div>
                            <?php endif; ?>
                            <p style="color:rgba(255,255,255,.6);font-size:14px;line-height:1.75;margin-bottom:18px;">
                                <?= $evTournament['announcement']
                                    ? htmlspecialchars($evTournament['announcement'])
                                    : 'Come and compete! Show off your skills and battle it out against the best players in Dasma for bragging rights and prizes.' ?>
                            </p>

                            <!-- Meta chips -->
                            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                                <span class="gsh-ev-chip">
                                    <i class="fas fa-peso-sign" style="color:#20c8a1;"></i>
                                    <?= (float)$evTournament['entry_fee'] > 0 ? '₱' . number_format($evTournament['entry_fee'], 0) . ' Entry' : 'Free Entry' ?>
                                </span>
                                <?php if ((float)$evTournament['prize_pool'] > 0): ?>
                                <span class="gsh-ev-chip">
                                    <i class="fas fa-trophy" style="color:#f1a83c;"></i>
                                    Prize: ₱<?= number_format($evTournament['prize_pool'], 0) ?>
                                </span>
                                <?php endif; ?>
                                <span class="gsh-ev-chip">
                                    <i class="fas fa-users" style="color:#b37bec;"></i>
                                    <?= $evSlotsTaken ?> / <?= $evSlotsMax ?> Slots
                                </span>
                                <span class="gsh-ev-chip">
                                    <i class="fas fa-map-marker-alt" style="color:#fb566b;"></i>
                                    Good Spot, Dasma
                                </span>
                            </div>

                            <?php if ($evIsOpen): ?>
                            <a href="tournament_register.php" style="display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,#f1a83c,#e08a1a);color:#1a0a00;font-weight:800;font-size:14px;padding:13px 26px;border-radius:12px;text-decoration:none;transition:all .25s;box-shadow:0 4px 20px rgba(241,168,60,.3);">
                                <i class="fas fa-trophy"></i> Register Now
                            </a>
                            <?php elseif ($evIsFull): ?>
                            <span style="display:inline-flex;align-items:center;gap:8px;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.3);color:#fb566b;font-weight:700;font-size:13px;padding:10px 20px;border-radius:12px;">
                                <i class="fas fa-users-slash"></i> Tournament Full
                            </span>
                            <?php else: ?>
                            <a href="tournament_register.php" style="display:inline-flex;align-items:center;gap:10px;background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.3);color:#f1a83c;font-weight:700;font-size:14px;padding:12px 24px;border-radius:12px;text-decoration:none;">
                                <i class="fas fa-clock"></i> Registration Opening Soon
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- No tournament state -->
                    <div style="text-align:center;padding:2rem 1rem;">
                        <div style="font-size:3rem;margin-bottom:1rem;">🏆</div>
                        <h3 style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:.75rem;">No Upcoming Tournament</h3>
                        <p style="color:rgba(255,255,255,.55);font-size:14px;line-height:1.75;margin-bottom:1.5rem;">
                            We're planning our next event. Follow our Facebook page for announcements and stay tuned!
                        </p>
                        <a href="https://www.facebook.com/gspotgaminghub" target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:8px;background:rgba(24,119,242,.1);border:1px solid rgba(24,119,242,.3);color:#6fa8f7;font-weight:700;font-size:13px;padding:10px 20px;border-radius:10px;text-decoration:none;">
                            <i class="fab fa-facebook-f"></i> Follow for Updates
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CTA side card -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="160">
                <div class="gsh-event-cta">
                    <div style="font-size:2.5rem;margin-bottom:14px;">🏆</div>
                    <h4 style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:10px;">Want to Host an Event?</h4>
                    <p style="font-size:13px;color:rgba(255,255,255,.55);line-height:1.7;margin-bottom:20px;">
                        We offer private tournament and event packages for groups. Get in touch to arrange a custom event at Good Spot.
                    </p>
                    <a href="#contact" style="display:block;text-align:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.8);font-weight:700;font-size:13px;padding:12px;border-radius:12px;text-decoration:none;transition:all .25s;">
                        Contact Us →
                    </a>
                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,.06);">
                        <div style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:12px;">Follow for Updates</div>
                        <a href="https://www.facebook.com/gspotgaminghub" target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:8px;background:rgba(24,119,242,.1);border:1px solid rgba(24,119,242,.3);color:#6fa8f7;font-weight:700;font-size:13px;padding:9px 16px;border-radius:10px;text-decoration:none;transition:all .2s;">
                            <i class="fab fa-facebook-f"></i> Facebook Page
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.gsh-event-card { background:rgba(10,18,40,.8); border:1px solid rgba(241,168,60,.2); border-radius:22px; padding:32px; position:relative; overflow:hidden; transition:transform .3s, box-shadow .3s; box-shadow:0 0 40px rgba(241,168,60,.05); backdrop-filter:blur(10px); }
.gsh-event-card:hover { transform:translateY(-5px); box-shadow:0 20px 60px rgba(241,168,60,.12); }
.gsh-event-bar { position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#f1a83c,#b37bec,#5f85da); }
.gsh-event-date { background:rgba(241,168,60,.08); border:1px solid rgba(241,168,60,.2); border-radius:16px; padding:20px 24px; text-align:center; flex-shrink:0; min-width:90px; }
.gsh-ev-chip { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08); border-radius:20px; padding:5px 12px; font-size:12px; color:rgba(255,255,255,.6); font-weight:600; }
.gsh-event-cta { background:rgba(10,18,40,.7); border:1px solid rgba(255,255,255,.08); border-radius:22px; padding:28px 24px; height:100%; backdrop-filter:blur(10px); transition:border-color .3s; }
.gsh-event-cta:hover { border-color:rgba(32,200,161,.25); }
</style>
