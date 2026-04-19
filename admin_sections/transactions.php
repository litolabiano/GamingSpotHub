<!-- ════ TRANSACTIONS ══════════════════════════════════════════════════════════ -->
<div class="page" id="transactions">
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

    <!-- Pending Payments -->
    <?php if (!empty($pendingSessions)): ?>
    <div class="card" style="border-left:3px solid #fb566b;margin-bottom:20px;">
        <div class="card-header" style="border-bottom:1px solid rgba(251,86,107,.2);">
            <h3 class="card-title" style="color:#fb566b;">
                <i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>
                Outstanding Balances
                <span style="background:rgba(251,86,107,.2);color:#fb566b;font-size:12px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:8px;">
                    <?= count($pendingSessions) ?>
                </span>
            </h3>
            <span style="font-size:12px;color:#888;">Sessions with unpaid balances — collect before end of day</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Console</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th>Paid</th>
                    <th>Balance Owed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingSessions as $ps):
                $psStart   = strtotime($ps['start_time']);
                $psPaid    = (float)$ps['paid_so_far'];
                $isCompleted = ($ps['status'] === 'completed');

                if ($isCompleted) {
                    $psExpected  = (float)$ps['total_cost'];
                    $psModeLabel = match($ps['rental_mode']) {
                        'open_time' => 'Open Time',
                        'unlimited' => 'Unlimited',
                        default => 'Hourly'
                    };
                } else {
                    if ($ps['rental_mode'] === 'hourly' && $ps['planned_minutes']) {
                        $psExpected = $ps['planned_minutes'] <= 30 ? 50.0 : (float)($ps['planned_minutes'] / 60 * 80);
                        $psModeLabel = 'Hourly';
                    } else {
                        $psExpected  = $unlimitedRateVal;
                        $psModeLabel = 'Unlimited';
                    }
                }
                $psOwed = max(0, $psExpected - $psPaid);
                $bookedMinutes = ($ps['rental_mode'] === 'hourly' && $ps['planned_minutes']) ? (int)$ps['planned_minutes'] : 0;
            ?>
            <tr style="<?= $isCompleted ? 'background:rgba(251,86,107,.03);' : '' ?>">
                <td>#<?= $ps['session_id'] ?></td>
                <td><?= htmlspecialchars($ps['customer_name']) ?></td>
                <td><?= htmlspecialchars($ps['unit_number']) ?></td>
                <td><?= $psModeLabel ?></td>
                <td>
                    <?php if ($isCompleted): ?>
                    <span style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                        Ended
                    </span>
                    <?php else: ?>
                    <span style="background:rgba(32,200,161,.15);color:#20c8a1;border:1px solid rgba(32,200,161,.3);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                        Active
                    </span>
                    <?php endif; ?>
                </td>
                <td style="color:#20c8a1;font-weight:700;">₱<?= number_format($psPaid, 2) ?></td>
                <td>
                    <span style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);
                                 padding:3px 10px;border-radius:6px;font-weight:700;font-size:13px;">
                        ₱<?= number_format($psOwed, 2) ?> due
                    </span>
                </td>
                <td>
                    <?php if ($psOwed > 0): ?>
                    <button class="btn btn-sm" title="Collect Payment"
                        style="background:rgba(32,200,161,.18);border:1px solid rgba(32,200,161,.5);color:#20c8a1;font-weight:700;"
                        onclick="openPayModal(
                            <?= $ps['session_id'] ?>,
                            '<?= htmlspecialchars(addslashes($ps['customer_name'])) ?>',
                            '<?= htmlspecialchars(addslashes($ps['unit_number'])) ?>',
                            '<?= $ps['rental_mode'] ?>',
                            <?= $psStart ?>,
                            <?= $bookedMinutes ?>,
                            <?= $psPaid ?>,
                            <?= (float)($settings['unlimited_rate'] ?? 300) ?>
                        )">
                        <i class="fas fa-peso-sign"></i> Collect
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

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
