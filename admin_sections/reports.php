<!-- ════ REPORTS ════════════════════════════════════════════════════════════ -->
<div class="page" id="reports">
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
        <div class="card-header"><h3 class="card-title">Console Usage Report (All Time)</h3></div>
        <table class="data-table">
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
    </div>

    <!-- ══ CANCELLATION ANALYTICS ═══════════════════════════════════════════ -->
    <?php
        $totalCancels   = (int)($cancelStatsRow['total_cancels']   ?? 0);
        $userCancels    = (int)($cancelStatsRow['user_cancels']    ?? 0);
        $adminCancels   = (int)($cancelStatsRow['admin_cancels']   ?? 0);
        $refundsIssued  = (int)($cancelStatsRow['refunds_issued']  ?? 0);
        $totalRefunded  = (float)($cancelStatsRow['total_refunded'] ?? 0);
        $userPct        = $totalCancels > 0 ? round($userCancels  / $totalCancels * 100) : 0;
        $adminPct       = $totalCancels > 0 ? round($adminCancels / $totalCancels * 100) : 0;
        $refundRate     = $totalCancels > 0 ? round($refundsIssued / $totalCancels * 100) : 0;

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
            ['label'=>'Total Cancellations', 'value'=>$totalCancels,         'icon'=>'ban',           'color'=>'#fb566b', 'bg'=>'rgba(251,86,107,.12)'],
            ['label'=>'User-Initiated',       'value'=>$userCancels.' ('.$userPct.'%)',  'icon'=>'user-times',   'color'=>'#f1a83c', 'bg'=>'rgba(241,168,60,.12)'],
            ['label'=>'Admin-Initiated',      'value'=>$adminCancels.' ('.$adminPct.'%)','icon'=>'shield-alt',   'color'=>'#b37bec', 'bg'=>'rgba(179,123,236,.12)'],
            ['label'=>'Refunds Issued',       'value'=>$refundsIssued.' ('.$refundRate.'%)','icon'=>'undo-alt', 'color'=>'#20c8a1', 'bg'=>'rgba(32,200,161,.12)'],
            ['label'=>'Total Refunded',       'value'=>'₱'.number_format($totalRefunded,2),'icon'=>'peso-sign','color'=>'#5f85da', 'bg'=>'rgba(95,133,218,.12)'],
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
                <th>Refunds Given</th>
            </tr></thead>
            <tbody>
            <?php
            $reasonDetail = $conn->query(
                "SELECT
                    cancel_reason_type AS reason,
                    COUNT(*)                                        AS total,
                    SUM(cancelled_by = 'user')                      AS by_user,
                    SUM(cancelled_by = 'admin')                     AS by_admin,
                    SUM(refund_issued = 1)                          AS refunded
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
                <td style="color:#20c8a1"><?= (int)$rd['refunded'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reasonDetail)): ?>
            <tr><td colspan="6" style="text-align:center;color:rgba(255,255,255,.35);padding:30px">No cancellations recorded yet.</td></tr>
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
