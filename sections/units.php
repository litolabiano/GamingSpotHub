<?php
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/session_helper.php';
$allConsoles = getConsoles();
$isLoggedIn  = isLoggedIn();
?>
<!-- ══ UNITS ════════════════════════════════════════════════════════════════ -->
<section id="units" style="background:linear-gradient(180deg,#07101f 0%,#0d1b2a 100%);padding:90px 0 100px;">
<div class="container">

    <!-- Header -->
    <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-tag">Available Now</span>
        <h2 class="section-title">Our Gaming Stations</h2>
        <p class="section-subtitle">
            <?= count($allConsoles) ?> premium console stations — real-time availability updates every 10 seconds
        </p>
    </div>

    <!-- Filter tabs -->
    <div class="text-center mb-4" data-aos="fade-up">
        <div id="unitFilter" style="display:inline-flex;gap:8px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:6px;">
            <button class="uf-btn active" data-filter="all">All Units</button>
            <button class="uf-btn" data-filter="available"><span style="color:#20c8a1">●</span> Available</button>
            <button class="uf-btn" data-filter="in_use"><span style="color:#fb566b">●</span> In Use</button>
        </div>
    </div>

    <!-- Grid -->
    <div class="row g-4" id="unitsGrid">
    <?php $delay = 0; foreach ($allConsoles as $con):
        $type   = $con['console_type'];
        $status = $con['status'];
        $icon   = str_starts_with($type,'PS') ? 'fab fa-playstation' : (stripos($type,'Xbox')!==false ? 'fab fa-xbox' : 'fas fa-gamepad');
        $statusColor = match($status){ 'available'=>'#20c8a1','in_use'=>'#fb566b','maintenance'=>'#f1a83c',default=>'#888' };
        $statusLabel = match($status){ 'available'=>'Available','in_use'=>'In Use','maintenance'=>'Under Maintenance',default=>ucfirst($status) };
        $accentColor = str_starts_with($type,'PS5') ? '#5f85da' : (str_starts_with($type,'PS4') ? '#f1a83c' : '#20c8a1');
        $consoleParam = urlencode($type);
        $reserveUrl   = 'reserve.php?console='.$consoleParam;
        $bookHref     = $isLoggedIn ? $reserveUrl : 'auth/login.php?redirect='.urlencode($reserveUrl);
    ?>
    <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>" data-status="<?= $status ?>">
        <div class="gsh-unit-card" data-console-id="<?= $con['console_id'] ?>"
             style="--accent:<?= $accentColor ?>;">

            <!-- Top row: type badge + status -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                <span class="gsh-type-badge">
                    <i class="<?= $icon ?>" style="color:<?= $accentColor ?>;margin-right:6px;"></i>
                    <?= htmlspecialchars($type) ?>
                </span>
                <span class="gsh-status-dot" style="color:<?= $statusColor ?>;">
                    <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= $statusColor ?>;margin-right:5px;
                    <?= $status==='available' ? 'box-shadow:0 0 0 3px rgba(32,200,161,.25);animation:pulse 2s infinite;' : '' ?>"></span>
                    <?= $statusLabel ?>
                </span>
            </div>

            <!-- Icon -->
            <div class="gsh-unit-icon" style="background:linear-gradient(135deg,rgba(<?= $accentColor==='#5f85da'?'95,133,218':($accentColor==='#f1a83c'?'241,168,60':'32,200,161') ?>,.12),transparent);">
                <i class="<?= $icon ?>" style="font-size:3rem;color:<?= $accentColor ?>;"></i>
            </div>

            <!-- Name + unit -->
            <div style="text-align:center;margin-bottom:16px;">
                <div style="font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:4px;"><?= htmlspecialchars($con['console_name']) ?></div>
                <div style="font-size:12px;color:rgba(255,255,255,.35);font-weight:600;letter-spacing:.5px;"><?= htmlspecialchars($con['unit_number']) ?></div>
            </div>

            <!-- Rate -->
            <div class="gsh-rate">
                ₱<?= number_format((float)$con['hourly_rate'],0) ?>
                <span>/hour</span>
            </div>

            <!-- Perks -->
            <div class="gsh-perks">
                <span>🎮 Console included</span>
                <span>📺 HD/4K Display</span>
                <span>🎧 Gaming headset</span>
                <span>🛋️ Comfy seating</span>
            </div>

            <!-- CTA -->
            <?php if ($status === 'available'): ?>
            <a href="<?= $bookHref ?>" class="gsh-unit-btn" id="unit-btn-<?= $con['console_id'] ?>">
                <i class="fas fa-calendar-check" style="margin-right:7px;"></i> Book Now
            </a>
            <?php else: ?>
            <div class="gsh-unit-btn disabled" id="unit-btn-<?= $con['console_id'] ?>">
                <?= $statusLabel ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php $delay += 60; endforeach; ?>
    </div>

    <!-- Legend -->
    <div style="display:flex;justify-content:center;gap:24px;margin-top:40px;flex-wrap:wrap;" data-aos="fade-up">
        <span style="font-size:13px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:7px;">
            <span style="width:9px;height:9px;border-radius:50%;background:#20c8a1;box-shadow:0 0 0 3px rgba(32,200,161,.2);"></span> Available
        </span>
        <span style="font-size:13px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:7px;">
            <span style="width:9px;height:9px;border-radius:50%;background:#fb566b;"></span> In Use
        </span>
        <span style="font-size:13px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:7px;">
            <span style="width:9px;height:9px;border-radius:50%;background:#f1a83c;"></span> Maintenance
        </span>
    </div>

</div>
</section>

<style>
.gsh-unit-card {
    background: rgba(10,20,40,.7);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 20px;
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: transform .3s, border-color .3s, box-shadow .3s;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(8px);
}
.gsh-unit-card::before {
    content:'';
    position:absolute;
    top:-60px;right:-60px;
    width:180px;height:180px;
    border-radius:50%;
    background:radial-gradient(circle, color-mix(in srgb, var(--accent) 20%, transparent) 0%, transparent 70%);
    pointer-events:none;
}
.gsh-unit-card:hover {
    transform: translateY(-6px);
    border-color: var(--accent);
    box-shadow: 0 16px 48px rgba(0,0,0,.3), 0 0 0 1px var(--accent);
}
.gsh-type-badge {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
}
.gsh-status-dot { font-size: 12px; font-weight: 700; }
.gsh-unit-icon {
    width: 90px; height: 90px;
    border-radius: 22px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    border: 1px solid rgba(255,255,255,.06);
}
.gsh-rate {
    text-align: center;
    font-family: 'Outfit', sans-serif;
    font-size: 2rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 16px;
}
.gsh-rate span { font-size: .9rem; font-weight: 500; color: rgba(255,255,255,.4); margin-left: 2px; }
.gsh-perks {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: center;
    margin-bottom: 20px;
    margin-top: auto;
    padding-top: 10px;
}
.gsh-perks span {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 11px;
    color: rgba(255,255,255,.5);
}
.gsh-unit-btn {
    display: block;
    width: 100%;
    text-align: center;
    padding: 13px;
    background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 70%, #000));
    color: #fff;
    font-weight: 800;
    font-size: 14px;
    border-radius: 12px;
    text-decoration: none;
    transition: all .25s;
    border: none;
    cursor: pointer;
}
.gsh-unit-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.3);
    color: #fff;
}
.gsh-unit-btn.disabled {
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.3);
    cursor: not-allowed;
    pointer-events: none;
}
/* Filter buttons */
.uf-btn {
    background: none;
    border: none;
    color: rgba(255,255,255,.5);
    font-size: 13px;
    font-weight: 700;
    padding: 8px 18px;
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s;
}
.uf-btn:hover, .uf-btn.active {
    background: rgba(255,255,255,.1);
    color: #fff;
}
</style>

<script>
// Filter tabs
document.querySelectorAll('.uf-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.uf-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const f = this.dataset.filter;
        document.querySelectorAll('#unitsGrid > [data-status]').forEach(col => {
            col.style.display = (f === 'all' || col.dataset.status === f) ? '' : 'none';
        });
    });
});

// ── Real-time console status polling ──────────────────────────────────────────
(function() {
    function updateUnits() {
        fetch('/GamingSpotHub/api/console_status.php')
            .then(r => r.json())
            .then(data => {
                data.consoles.forEach(c => {
                    const card = document.querySelector(`[data-console-id="${c.id}"]`);
                    if (!card) return;
                    const col = card.parentElement;
                    col.dataset.status = c.status;
                    const colors = { available:'#20c8a1', in_use:'#fb566b', maintenance:'#f1a83c' };
                    const labels = { available:'Available', in_use:'In Use', maintenance:'Under Maintenance' };
                    const dot = card.querySelector('.gsh-status-dot');
                    if (dot) {
                        const clr = colors[c.status] || '#888';
                        dot.style.color = clr;
                        dot.innerHTML = `<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${clr};margin-right:5px;${c.status==='available'?'box-shadow:0 0 0 3px rgba(32,200,161,.25);animation:pulse 2s infinite;':''}"></span>${labels[c.status]||c.status}`;
                    }
                    const btn = card.querySelector('.gsh-unit-btn');
                    if (btn) {
                        if (c.status === 'available') {
                            btn.classList.remove('disabled');
                            btn.innerHTML = '<i class="fas fa-calendar-check" style="margin-right:7px;"></i> Book Now';
                            if (btn.getAttribute('href') === '#') {
                                const type = card.querySelector('.gsh-type-badge')?.textContent.trim();
                                if (type) {
                                    const isLoggedIn = document.querySelector('.user-dropdown') !== null;
                                    const url = 'reserve.php?console=' + encodeURIComponent(type.trim());
                                    btn.href = isLoggedIn ? url : 'auth/login.php?redirect=' + encodeURIComponent(url);
                                }
                            }
                        } else {
                            btn.classList.add('disabled');
                            btn.innerHTML = labels[c.status] || c.status;
                            btn.setAttribute('href', '#');
                        }
                    }
                });
            }).catch(() => {});
    }
    setInterval(updateUnits, 10000);
})();
</script>
