<!-- ════ REPORTS ════════════════════════════════════════════════════════════ -->
<div class="page" id="reports">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-chart-bar" style="color:#b37bec;margin-right:10px;"></i>Analytics &amp; Reports</h2>
            <p class="page-subtitle">Revenue trends, console usage, and cancellation analytics</p>
        </div>
    </div>

    <!-- ══ REPORT GENERATION ════════════════════════════════════════════════════ -->
    <div class="card" style="margin-bottom:20px; border-left: 3px solid #20c8a1;">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-invoice-dollar" style="color:#20c8a1;margin-right:8px;"></i> Generate Financial & Operations Report</h3></div>
        <div style="padding: 20px;">
            <form action="report_receipt.php" method="GET" target="_blank" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
                <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                    <label style="font-size:12px;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;display:block;">Report Type</label>
                    <select name="type" id="reportTypeSelect" style="width:100%;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#fff;outline:none;" onchange="updateReportDateInput()">
                        <option value="daily" style="background:#0d1117;">Daily Report</option>
                        <option value="monthly" style="background:#0d1117;">Monthly Report</option>
                        <option value="yearly" style="background:#0d1117;">Yearly Report</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                    <label id="reportDateLabel" style="font-size:12px;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;display:block;">Select Date</label>
                    <input type="date" name="date" id="reportDateInput" style="width:100%;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#fff;outline:none;" required value="<?= getOperatingDay() ?>">
                </div>
                <button type="submit" style="height:41px;background:linear-gradient(135deg,#20c8a1,#5f85da);color:#fff;border:none;border-radius:8px;padding:0 24px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(32,200,161,.2);">
                    <i class="fas fa-print"></i> Generate & Print
                </button>
            </form>
        </div>
    </div>
    <script>
    function updateReportDateInput() {
        const t = document.getElementById('reportTypeSelect').value;
        const i = document.getElementById('reportDateInput');
        const l = document.getElementById('reportDateLabel');
        if (t === 'daily') {
            i.type = 'date';
            l.textContent = 'Select Date';
            i.value = '<?= getOperatingDay() ?>';
        } else if (t === 'monthly') {
            i.type = 'month';
            l.textContent = 'Select Month';
            i.value = '<?= date('Y-m') ?>';
        } else if (t === 'yearly') {
            i.type = 'number';
            i.min = 2020; i.max = 2050; i.placeholder = 'YYYY';
            l.textContent = 'Select Year';
            i.value = '<?= date('Y') ?>';
        }
    }
    </script>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Revenue — Last 7 Days</h3></div>
            <canvas id="revChart" height="200"></canvas>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Sessions by Console Type</h3></div>
            <canvas id="typeChart" height="200"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:10px;">
            <h3 class="card-title">Console Usage Report (All Time)</h3>
            <div style="display:flex;gap:8px;align-items:center;">
                <div class="asb-search" style="max-width:220px;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="asb-input" id="usageSearch" placeholder="Search unit or type…" autocomplete="off">
                    <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
                </div>
                <span class="asb-count" id="usageCount"></span>
            </div>
        </div>
        <table class="data-table" id="usageTable">
            <thead><tr><th>Unit</th><th>Type</th><th>Total Sessions</th><th>Total Hours</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($usageReport as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['unit_number']) ?></td>
                <td><?= htmlspecialchars($u['console_type']) ?></td>
                <td><?= $u['total_sessions'] ?></td>
                <td><?= number_format($u['total_minutes']/60, 1) ?> hrs</td>
                <td style="color:#20c8a1">₱<?= number_format($u['total_revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="asb-no-results" id="usageSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No consoles match your search.</div>
        <div id="usagePagination"></div>
    </div>



    <!-- ══ CANCELLATION ANALYTICS ═══════════════════════════════════════════ -->
    <?php
        $totalCancels   = (int)($cancelStatsRow['total_cancels']   ?? 0);
        $userCancels    = (int)($cancelStatsRow['user_cancels']    ?? 0);
        $adminCancels   = (int)($cancelStatsRow['admin_cancels']   ?? 0);
        $userPct        = $totalCancels > 0 ? round($userCancels  / $totalCancels * 100) : 0;
        $adminPct       = $totalCancels > 0 ? round($adminCancels / $totalCancels * 100) : 0;
        // Rescheduled count
        $totalRescheduled = 0;
        try {
            $rrr = $conn->query("SELECT COUNT(*) AS cnt FROM reservation_reschedules");
            if ($rrr) $totalRescheduled = (int)$rrr->fetch_assoc()['cnt'];
        } catch (Exception $e) {}

        // Reason label map
        $reasonLabels = [
            'schedule_change'   => 'Schedule Change',
            'found_alternative' => 'Found Alternative',
            'budget_issue'      => 'Budget Issue',
            'technical_issue'   => 'Technical Issue',
            'emergency'         => 'Emergency',
            'other'             => 'Other',
            'admin_decision'    => 'Admin Decision',
        ];
        $cancelReasonLabels = array_map(fn($r) => $reasonLabels[$r['reason']] ?? ucfirst($r['reason']), $cancelReasons);
        $cancelReasonCounts = array_column($cancelReasons, 'cnt');
        $cancelConsoleLabels = array_column($cancelByConsole, 'console_type');
        $cancelConsoleCounts = array_column($cancelByConsole, 'cnt');
    ?>

    <h3 style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:700;color:#fff;
               margin:32px 0 16px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-ban" style="color:#fb566b"></i> Reservation Cancellation Analytics
    </h3>

    <!-- Stat cards row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px">
        <?php
        $cancelStatCards = [
            ['label'=>'Total Cancellations', 'value'=>$totalCancels,                          'icon'=>'ban',          'color'=>'#fb566b', 'bg'=>'rgba(251,86,107,.12)'],
            ['label'=>'User-Initiated',       'value'=>$userCancels.' ('.$userPct.'%)',        'icon'=>'user-times',   'color'=>'#f1a83c', 'bg'=>'rgba(241,168,60,.12)'],
            ['label'=>'Admin-Initiated',      'value'=>$adminCancels.' ('.$adminPct.'%)',      'icon'=>'shield-alt',   'color'=>'#b37bec', 'bg'=>'rgba(179,123,236,.12)'],
            ['label'=>'Rescheduled',          'value'=>$totalRescheduled,                     'icon'=>'calendar-alt', 'color'=>'#20c8a1', 'bg'=>'rgba(32,200,161,.12)'],
        ];
        foreach ($cancelStatCards as $sc): ?>
        <div class="card" style="padding:18px;border-color:<?= $sc['color'] ?>22">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                <div>
                    <div style="font-size:22px;font-weight:800;font-family:'Outfit',sans-serif;color:#fff"><?= $sc['value'] ?></div>
                    <div style="font-size:11px;color:rgba(255,255,255,.45);margin-top:3px"><?= $sc['label'] ?></div>
                </div>
                <div style="width:40px;height:40px;border-radius:10px;background:<?= $sc['bg'] ?>;
                            display:flex;align-items:center;justify-content:center;color:<?= $sc['color'] ?>;font-size:18px">
                    <i class="fas fa-<?= $sc['icon'] ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts row: reason doughnut + trend line -->
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie" style="color:#fb566b"></i> Reasons Breakdown</h3></div>
            <?php if ($totalCancels > 0): ?>
            <div style="position:relative;height:220px;display:flex;align-items:center;justify-content:center">
                <canvas id="cancelReasonChart"></canvas>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:40px 20px;color:rgba(255,255,255,.4)">
                <i class="fas fa-ban" style="font-size:30px;display:block;margin-bottom:8px"></i>No cancellations yet
            </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line" style="color:#fb566b"></i> Cancellations — Last 30 Days</h3></div>
            <div style="position:relative;height:220px">
                <canvas id="cancelTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Console breakdown + who-cancelled doughnut -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-gamepad" style="color:#5f85da"></i> Cancellations by Console Type</h3></div>
            <div style="position:relative;height:180px">
                <canvas id="cancelConsoleChart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users" style="color:#b37bec"></i> Cancelled By</h3></div>
            <?php if ($totalCancels > 0): ?>
            <div style="position:relative;height:180px;display:flex;align-items:center;justify-content:center">
                <canvas id="cancelByWhoChart"></canvas>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:30px 20px;color:rgba(255,255,255,.4)">No data yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed cancellations table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list" style="color:#fb566b"></i> Top Cancellation Reasons</h3>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Reason</th>
                <th>Count</th>
                <th>% of Total</th>
                <th>User-Initiated</th>
                <th>Admin-Initiated</th>
            </tr></thead>
            <tbody>
            <?php
            $reasonDetail = $conn->query(
                "SELECT
                    cancel_reason_type AS reason,
                    COUNT(*)                                        AS total,
                    SUM(cancelled_by = 'user')                      AS by_user,
                    SUM(cancelled_by = 'admin')                     AS by_admin
                 FROM reservation_cancellations
                 GROUP BY cancel_reason_type
                 ORDER BY total DESC"
            )->fetch_all(MYSQLI_ASSOC);
            foreach ($reasonDetail as $rd):
                $pct = $totalCancels > 0 ? round($rd['total'] / $totalCancels * 100, 1) : 0;
                $label = $reasonLabels[$rd['reason']] ?? ucfirst(str_replace('_',' ',$rd['reason']));
            ?>
            <tr>
                <td>
                    <span style="display:inline-block;padding:2px 9px;border-radius:12px;font-size:11px;font-weight:700;
                                 background:rgba(251,86,107,.12);color:#fb566b;border:1px solid rgba(251,86,107,.2)">
                        <?= htmlspecialchars($label) ?>
                    </span>
                </td>
                <td><strong><?= (int)$rd['total'] ?></strong></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:3px">
                            <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,#fb566b,#f1a83c);border-radius:3px"></div>
                        </div>
                        <span style="font-size:12px;color:rgba(255,255,255,.55);min-width:34px"><?= $pct ?>%</span>
                    </div>
                </td>
                <td style="color:#f1a83c"><?= (int)$rd['by_user'] ?></td>
                <td style="color:#b37bec"><?= (int)$rd['by_admin'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reasonDetail)): ?>
            <tr><td colspan="5" style="text-align:center;color:rgba(255,255,255,.35);padding:30px">No cancellations recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ CANCELLATION CHARTS JAVASCRIPT ════════════════════════════════════════ -->
<script>
(function () {
    /* Palette */
    const CORAL   = '#fb566b';
    const GOLD    = '#f1a83c';
    const BLUE    = '#5f85da';
    const MINT    = '#20c8a1';
    const PURPLE  = '#b37bec';
    const CYAN    = '#38bdf8';
    const ORANGE  = '#fb923c';

    const reasonColors  = [CORAL, GOLD, BLUE, MINT, PURPLE, CYAN, ORANGE];
    const consoleColors = [BLUE, GOLD, MINT];

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: 'rgba(255,255,255,.55)', font: { size: 11 } } } }
    };

    /* ── Reasons Doughnut ─────────────────────────────────────────────────── */
    <?php if ($totalCancels > 0 && !empty($cancelReasonLabels)): ?>
    new Chart(document.getElementById('cancelReasonChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cancelReasonLabels) ?>,
            datasets: [{
                data: <?= json_encode($cancelReasonCounts) ?>,
                backgroundColor: reasonColors.slice(0, <?= count($cancelReasonCounts) ?>),
                borderWidth: 2,
                borderColor: '#0d1117',
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '65%',
            plugins: {
                ...chartDefaults.plugins,
                legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,.55)', font: { size: 11 }, padding: 10 } }
            }
        }
    });
    <?php endif; ?>

    /* ── Trend Line ───────────────────────────────────────────────────────── */
    new Chart(document.getElementById('cancelTrendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($cancelTrendLabels) ?>,
            datasets: [{
                label: 'Cancellations',
                data: <?= json_encode($cancelTrend) ?>,
                borderColor: CORAL,
                backgroundColor: 'rgba(251,86,107,.08)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: CORAL,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            ...chartDefaults,
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,.4)', maxTicksLimit: 10, font: { size: 10 } }, grid: { color: 'rgba(255,255,255,.04)' } },
                y: { ticks: { color: 'rgba(255,255,255,.4)', stepSize: 1 }, grid: { color: 'rgba(255,255,255,.04)' }, beginAtZero: true }
            }
        }
    });

    /* ── Console Breakdown Bar ────────────────────────────────────────────── */
    new Chart(document.getElementById('cancelConsoleChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($cancelConsoleLabels) ?>,
            datasets: [{
                label: 'Cancellations',
                data: <?= json_encode($cancelConsoleCounts) ?>,
                backgroundColor: consoleColors.slice(0, <?= count($cancelConsoleCounts) ?>),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            ...chartDefaults,
            indexAxis: 'y',
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,.4)', stepSize: 1 }, grid: { color: 'rgba(255,255,255,.04)' }, beginAtZero: true },
                y: { ticks: { color: 'rgba(255,255,255,.55)' }, grid: { display: false } }
            }
        }
    });

    /* ── Cancelled-By Doughnut ────────────────────────────────────────────── */
    <?php
    $whoLabels = array_map(fn($w) => ucfirst($w['cancelled_by']), $cancelByWho);
    $whoCounts = array_column($cancelByWho, 'cnt');
    ?>
    <?php if ($totalCancels > 0 && !empty($whoLabels)): ?>
    new Chart(document.getElementById('cancelByWhoChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($whoLabels) ?>,
            datasets: [{
                data: <?= json_encode($whoCounts) ?>,
                backgroundColor: [GOLD, PURPLE],
                borderWidth: 2,
                borderColor: '#0d1117',
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '65%',
            plugins: {
                ...chartDefaults.plugins,
                legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,.55)', font: { size: 11 }, padding: 10 } }
            }
        }
    });
    <?php endif; ?>
})();
</script>

<script>
/* ── Reports: Usage Table search + pagination ───────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    const usageSearch = document.getElementById('usageSearch');
    const usageTable  = document.getElementById('usageTable');

    const usagePag = new AdminPaginator('usageTable', {
        pageSize:      10,
        pageSizes:     [10, 25, 50],
        paginationSel: '#usagePagination',
        noResultsSel:  '#usageSearch_noResults',
        countSel:      '#usageCount',
    });

    function filterUsage() {
        if (!usageTable) return;
        const q = (usageSearch?.value || '').trim().toLowerCase();
        usageTable.querySelectorAll('tbody tr').forEach(row => {
            row.classList.toggle('asb-hidden', q && !row.innerText.toLowerCase().includes(q));
        });
        const cb = usageSearch?.parentElement?.querySelector('.asb-clear');
        if (cb) cb.style.display = q ? 'block' : 'none';
        usagePag.reset();
    }

    if (usageSearch) usageSearch.addEventListener('input', filterUsage);
    const usageClear = usageSearch?.parentElement?.querySelector('.asb-clear');
    if (usageClear) usageClear.addEventListener('click', () => {
        usageSearch.value = '';
        filterUsage();
        usageSearch.focus();
    });

    usagePag.apply();
});
</script>
