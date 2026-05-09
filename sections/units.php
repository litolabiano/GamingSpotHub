<?php
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/session_helper.php';
$allConsoles = getConsoles();
$isLoggedIn  = isLoggedIn();
?>
<!-- ══ UNITS ════════════════════════════════════════════════════════════════ -->
<section id="units" class="gsh-units">
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
        <div id="unitFilter" class="gsh-filter-wrap">
            <button class="uf-btn active" data-filter="all">All Units</button>
            <button class="uf-btn" data-filter="available"><span class="dot available">●</span> Available</button>
            <button class="uf-btn" data-filter="in_use"><span class="dot in-use">●</span> In Use</button>
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
            <div class="gsh-unit-header">
                <span class="gsh-type-badge">
                    <i class="<?= $icon ?>" style="color:<?= $accentColor ?>;"></i>
                    <?= htmlspecialchars($type) ?>
                </span>
                <span class="gsh-status-pill" style="color:<?= $statusColor ?>;">
                    <span class="gsh-status-indicator" style="background:<?= $statusColor ?>;
                    <?= $status==='available' ? 'box-shadow:0 0 0 3px rgba(32,200,161,.25);animation:pulse 2s infinite;' : '' ?>"></span>
                    <?= $statusLabel ?>
                </span>
            </div>

            <!-- Icon -->
            <div class="gsh-unit-icon-wrap" style="background:linear-gradient(135deg,rgba(<?= $accentColor==='#5f85da'?'95,133,218':($accentColor==='#f1a83c'?'241,168,60':'32,200,161') ?>,.12),transparent);">
                <i class="<?= $icon ?>" style="color:<?= $accentColor ?>;"></i>
            </div>

            <!-- Name + unit -->
            <div class="gsh-unit-info">
                <div class="gsh-unit-name"><?= htmlspecialchars($con['console_name']) ?></div>
                <div class="gsh-unit-sub"><?= htmlspecialchars($con['unit_number']) ?></div>
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

    <!-- Pagination Container -->
    <div id="unitsPagination" style="display:none; justify-content:center; align-items:center; gap:12px; margin-top:32px;"></div>

    <!-- Legend -->
    <div class="gsh-legend" data-aos="fade-up">
        <span class="gsh-legend-item">
            <span class="gsh-legend-dot available"></span> Available
        </span>
        <span class="gsh-legend-item">
            <span class="gsh-legend-dot in-use"></span> In Use
        </span>
        <span class="gsh-legend-item">
            <span class="gsh-legend-dot maintenance"></span> Maintenance
        </span>
    </div>

</div>
</section>

<style>
.gsh-units {
    background: linear-gradient(180deg, #07101f 0%, #0d1b2a 100%);
    padding: 100px 0;
}
.gsh-filter-wrap {
    display: inline-flex;
    gap: 8px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 6px;
}
.uf-btn .dot.available { color: #20c8a1; }
.uf-btn .dot.in-use { color: #fb566b; }

.gsh-unit-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}
.gsh-status-pill {
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}
.gsh-status-indicator {
    display: inline-block;
    width: 7px; height: 7px;
    border-radius: 50%;
}
.gsh-unit-icon-wrap {
    width: 90px; height: 90px;
    border-radius: 22px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    border: 1px solid rgba(255, 255, 255, 0.06);
}
.gsh-unit-icon-wrap i {
    font-size: 3rem;
}
.gsh-unit-info {
    text-align: center;
    margin-bottom: 16px;
}
.gsh-unit-name {
    font-size: 1.2rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}
.gsh-unit-sub {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.35);
    font-weight: 600;
    letter-spacing: 0.5px;
}
.gsh-legend {
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-top: 40px;
    flex-wrap: wrap;
}
.gsh-legend-item {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    gap: 7px;
}
.gsh-legend-dot {
    width: 9px; height: 9px;
    border-radius: 50%;
}
.gsh-legend-dot.available { background: #20c8a1; box-shadow: 0 0 0 3px rgba(32, 200, 161, 0.2); }
.gsh-legend-dot.in-use { background: #fb566b; }
.gsh-legend-dot.maintenance { background: #f1a83c; }

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
let currentUnitPage = 0;
const UNITS_PER_PAGE = 6;
let currentFilter = 'all';

function renderUnitsPagination() {
    const allCols = document.querySelectorAll('#unitsGrid > [data-status]');
    const visibleCols = [];
    
    allCols.forEach(col => {
        if (currentFilter === 'all' || col.dataset.status === currentFilter) {
            visibleCols.push(col);
        } else {
            col.style.display = 'none';
        }
    });
    
    const totalPages = Math.ceil(visibleCols.length / UNITS_PER_PAGE);
    
    if (currentUnitPage >= totalPages && totalPages > 0) {
        currentUnitPage = totalPages - 1;
    } else if (totalPages === 0) {
        currentUnitPage = 0;
    }
    
    visibleCols.forEach((col, index) => {
        const pageIdx = Math.floor(index / UNITS_PER_PAGE);
        col.style.display = (pageIdx === currentUnitPage) ? '' : 'none';
    });
    
    const pagContainer = document.getElementById('unitsPagination');
    if (!pagContainer) return;
    
    if (totalPages > 1) {
        pagContainer.style.display = 'flex';
        let pagHtml = `<i class="fas fa-chevron-left" onclick="goToIdxPage('prev', ${totalPages})" style="cursor:pointer;color:#888;transition:color .3s;padding:8px;" onmouseover="this.style.color='#20c8a1'" onmouseout="this.style.color='#888'"></i>`;
        pagHtml += `<div style="display:flex; gap:8px;">`;
        for (let p = 0; p < totalPages; p++) {
            const bg = p === currentUnitPage ? '#20c8a1' : 'rgba(255,255,255,.15)';
            pagHtml += `<div class="idx-page-dot" onclick="goToIdxPage(${p}, ${totalPages})" style="width:24px;height:6px;border-radius:10px;background:${bg};cursor:pointer;transition:background .3s;"></div>`;
        }
        pagHtml += `</div>`;
        pagHtml += `<i class="fas fa-chevron-right" onclick="goToIdxPage('next', ${totalPages})" style="cursor:pointer;color:#888;transition:color .3s;padding:8px;" onmouseover="this.style.color='#20c8a1'" onmouseout="this.style.color='#888'"></i>`;
        pagContainer.innerHTML = pagHtml;
    } else {
        pagContainer.style.display = 'none';
        pagContainer.innerHTML = '';
    }
}

function goToIdxPage(pageNum, totalPages) {
    if (pageNum === 'prev') {
        currentUnitPage = Math.max(0, currentUnitPage - 1);
    } else if (pageNum === 'next') {
        currentUnitPage = Math.min(totalPages - 1, currentUnitPage + 1);
    } else {
        currentUnitPage = pageNum;
    }
    renderUnitsPagination();
}

// Filter tabs
document.querySelectorAll('.uf-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.uf-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        currentUnitPage = 0; // Reset to first page
        renderUnitsPagination();
    });
});

// Initialize pagination on load
renderUnitsPagination();

// ── Real-time console status polling ──────────────────────────────────────────
(function() {
    function updateUnits() {
        fetch('/GamingSpotHub/api/console_status.php')
            .then(r => r.json())
            .then(data => {
                let statusChanged = false;
                data.consoles.forEach(c => {
                    const card = document.querySelector(`[data-console-id="${c.id}"]`);
                    if (!card) return;
                    
                    const col = card.parentElement;
                    if (col.dataset.status !== c.status) {
                        col.dataset.status = c.status;
                        statusChanged = true;
                    }
                    
                    const colors = { available:'#20c8a1', in_use:'#fb566b', maintenance:'#f1a83c' };
                    const labels = { available:'Available', in_use:'In Use', maintenance:'Under Maintenance' };
                    
                    const pill = card.querySelector('.gsh-status-pill');
                    if (pill) {
                        const clr = colors[c.status] || '#888';
                        pill.style.color = clr;
                        pill.innerHTML = `<span class="gsh-status-indicator" style="background:${clr};${c.status==='available'?'box-shadow:0 0 0 3px rgba(32,200,161,.25);animation:pulse 2s infinite;':''}"></span> ${labels[c.status]||c.status}`;
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
                
                // Re-run pagination if any status changed (so filters update accurately)
                if (statusChanged) {
                    renderUnitsPagination();
                }
            }).catch(() => {});
    }
    setInterval(updateUnits, 10000);
})();
</script>
