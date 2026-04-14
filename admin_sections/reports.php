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
</div>
