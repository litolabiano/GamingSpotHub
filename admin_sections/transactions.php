<!-- ════ TRANSACTIONS ══════════════════════════════════════════════════════════ -->
<div class="page" id="transactions">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-exchange-alt" style="color:#20c8a1;margin-right:10px;"></i>Transactions</h2>
            <p class="page-subtitle">Financial overview and payment history</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value">₱<?= number_format($finStats['today_revenue'] ?? 0, 2) ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
                <div class="stat-icon revenue"><i class="fas fa-peso-sign"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-calendar-day"></i> <?= date('F d, Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value">₱<?= number_format($finStats['monthly_revenue'] ?? 0, 2) ?></div>
                    <div class="stat-label">This Month's Revenue</div>
                </div>
                <div class="stat-icon sessions"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-chart-line"></i> <?= date('F Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value">₱<?= number_format($finStats['total_revenue'] ?? 0, 2) ?></div>
                    <div class="stat-label">All-Time Revenue</div>
                </div>
                <div class="stat-icon bookings"><i class="fas fa-chart-bar"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-infinity"></i> All time</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $finStats['total_transactions'] ?? 0 ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-icon consoles"><i class="fas fa-receipt"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-check-circle"></i> Completed</div>
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
                        $pr = getPricingRules();
                        // computeTimedCost walks the bonus cycle so free minutes
                        // are not billed. e.g. 300 min (4hr paid+1hr free) → ₱320.
                        $psExpected = $ps['planned_minutes'] <= 30
                            ? $pr['session_min_charge']
                            : (float)computeTimedCost((int)$ps['planned_minutes']);
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
        <div class="card-header" style="flex-wrap:wrap;gap:10px;">
            <h3 class="card-title">Transaction History</h3>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex:1;justify-content:flex-end;">
                <div class="asb-search" style="max-width:240px;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="asb-input" id="txSearch" placeholder="Search customer, console…" autocomplete="off">
                    <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
                </div>
                <select class="asb-select" id="txMethodFilter" title="Filter by method">
                    <option value="">All Methods</option>
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="paymongo">PayMongo</option>
                </select>
                <select class="asb-select" id="txStatusFilter" title="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
                <span class="asb-count" id="txCount"></span>
            </div>
        </div>
        <table class="data-table" id="txTable">
            <thead><tr>
                <th>#</th>
                <th>Customer</th>
                <th>Console</th>
                <th>Mode</th>
                <th>Amount</th>
                <th>Method</th>
                <th>PayMongo ID</th>
                <th>Date</th>
                <th>Status</th>
            </tr></thead>
            <tbody>
            <?php foreach ($transSessions as $t):
                $pmPayId  = $t['paymongo_payment_id'] ?? null;
                $pmSrcId  = $t['paymongo_source_id']  ?? null;
                $pmId     = $pmPayId ?: $pmSrcId;  // prefer payment_id, fallback to session_id
            ?>
            <tr>
                <td>#<?= $t['transaction_id'] ?></td>
                <td><?= htmlspecialchars($t['customer_name']) ?></td>
                <td><?= htmlspecialchars($t['unit_number']) ?></td>
                <td><?= match($t['rental_mode']) {
                    'open_time'   => 'Open Time',
                    'reservation' => '<span style="color:#20c8a1;font-weight:700;">Reservation</span>',
                    'refund'      => '<span style="color:#fb566b;">Refund</span>',
                    default       => ucfirst($t['rental_mode'])
                } ?></td>
                <td style="color:<?= (float)$t['amount'] < 0 ? '#fb566b' : '#20c8a1' ?>;font-weight:700">
                    <?= (float)$t['amount'] < 0 ? '-' : '' ?>₱<?= number_format(abs((float)$t['amount']), 2) ?>
                </td>
                <td><span class="badge pending"><?= ucfirst($t['payment_method']) ?></span></td>
                <td style="font-size:11px;">
                    <?php if ($pmId): ?>
                        <span style="font-family:monospace;color:#20c8a1;font-weight:600;letter-spacing:.3px;"
                              title="<?= htmlspecialchars($pmId) ?>">
                            <?= htmlspecialchars($pmId) ?>
                        </span>
                        <?php if ($pmSrcId && $pmSrcId !== $pmPayId): ?>
                        <br><span style="font-family:monospace;font-size:10px;color:#555;">
                            <?= htmlspecialchars($pmSrcId) ?>
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#444;">—</span>
                    <?php endif; ?>
                </td>
                <td><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                <td><span class="badge <?= $t['payment_status'] === 'completed' ? 'completed' : 'cancelled' ?>"><?= ucfirst($t['payment_status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="asb-no-results" id="txSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No transactions match your search.</div>
        <div id="txPagination"></div>
    </div>
</div>

<script>
/* ── Transactions search + pagination ────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    const txSearch = document.getElementById('txSearch');
    const txMethod = document.getElementById('txMethodFilter');
    const txStatus = document.getElementById('txStatusFilter');
    const txTable  = document.getElementById('txTable');

    const txPag = new AdminPaginator('txTable', {
        pageSize:      10,
        pageSizes:     [10, 25, 50],
        paginationSel: '#txPagination',
        noResultsSel:  '#txSearch_noResults',
        countSel:      '#txCount',
    });

    function filterTx() {
        if (!txTable) return;
        const q  = (txSearch?.value || '').trim().toLowerCase();
        const m  = (txMethod?.value || '').toLowerCase();
        const st = (txStatus?.value || '').toLowerCase();
        txTable.querySelectorAll('tbody tr').forEach(row => {
            const hay = row.innerText.toLowerCase();
            const match = (!q || hay.includes(q)) && (!m || hay.includes(m)) && (!st || hay.includes(st));
            row.classList.toggle('asb-hidden', !match);
        });
        const cb = txSearch?.parentElement?.querySelector('.asb-clear');
        if (cb) cb.style.display = q ? 'block' : 'none';
        txPag.reset();
    }

    if (txSearch) txSearch.addEventListener('input', filterTx);
    if (txMethod) txMethod.addEventListener('change', filterTx);
    if (txStatus) txStatus.addEventListener('change', filterTx);
    const txClear = txSearch?.parentElement?.querySelector('.asb-clear');
    if (txClear) txClear.addEventListener('click', () => { txSearch.value = ''; filterTx(); txSearch.focus(); });

    txPag.apply();
});
</script>
