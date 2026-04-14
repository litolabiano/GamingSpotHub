<!-- ════ FINANCIAL ══════════════════════════════════════════════════════════ -->
<div class="page" id="financial">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">₱<?= number_format($finStats['today_revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">₱<?= number_format($finStats['monthly_revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">This Month's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">₱<?= number_format($finStats['total_revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">All-Time Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $finStats['total_transactions'] ?? 0 ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Transaction History</h3></div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($transSessions as $t): ?>
            <tr>
                <td>#<?= $t['transaction_id'] ?></td>
                <td><?= htmlspecialchars($t['customer_name']) ?></td>
                <td><?= htmlspecialchars($t['unit_number']) ?></td>
                <td><?= match($t['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($t['rental_mode']) } ?></td>
                <td style="color:#20c8a1;font-weight:700">₱<?= number_format($t['amount'],2) ?></td>
                <td><span class="badge pending"><?= ucfirst($t['payment_method']) ?></span></td>
                <td><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                <td><span class="badge <?= $t['payment_status'] === 'completed' ? 'completed' : 'cancelled' ?>"><?= ucfirst($t['payment_status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
