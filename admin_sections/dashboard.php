<!-- ════ DASHBOARD ════════════════════════════════════════════════════════ -->
<div class="page active" id="dashboard">

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value">₱<?= number_format($todayRevenue, 2) ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
                <div class="stat-icon revenue"><i class="fas fa-peso-sign"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-calendar-day"></i> <?= date('F d, Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $activeCount ?></div>
                    <div class="stat-label">Active Sessions</div>
                </div>
                <div class="stat-icon sessions"><i class="fas fa-play-circle"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-circle" style="color:#20c8a1;font-size:8px"></i> Live right now</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $todayBookings ?></div>
                    <div class="stat-label">Sessions Today</div>
                </div>
                <div class="stat-icon bookings"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-check"></i> Completed today</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $availableCount ?>/<?= count($allConsoles) ?></div>
                    <div class="stat-label">Consoles Available</div>
                </div>
                <div class="stat-icon consoles"><i class="fas fa-desktop"></i></div>
            </div>
            <div class="stat-change up">
                <span style="color:#5f85da"><?= $inUseCount ?> in use</span> &nbsp;
                <span style="color:#fb566b"><?= $maintenanceCount ?> maintenance</span>
            </div>
        </div>
    </div>

    <!-- Active Sessions Right Now -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-circle" style="color:#20c8a1;font-size:10px;margin-right:8px"></i>Live Sessions</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('startSession')"><i class="fas fa-plus"></i> Start Session</button>
        </div>
        <?php if (empty($activeSessions)): ?>
            <div class="empty-state"><i class="fas fa-couch"></i>No active sessions right now</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Session #</th><th>Customer</th><th>Console</th><th>Mode</th><th>Started</th><th>Booked Until</th><th>Elapsed / Remaining</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($activeSessions as $sess): ?>
            <tr>
                <td>#<?= $sess['session_id'] ?></td>
                <td><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td>
                    <span class="console-type-badge <?= $sess['console_type'] === 'PS5' ? 'ps5' : ($sess['console_type'] === 'PS4' ? 'ps4' : 'xbox') ?>">
                        <?= $sess['console_type'] ?>
                    </span>
                    <?= htmlspecialchars($sess['unit_number']) ?>
                </td>
                <td><span class="badge pending"><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></span></td>
                <td><?= date('h:i A', strtotime($sess['start_time'])) ?></td>
                <td>
                    <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']):
                        $bookedEndDt = new DateTime($sess['start_time'], new DateTimeZone('Asia/Manila'));
                        $bookedEndDt->modify('+' . $sess['planned_minutes'] . ' minutes');
                    ?>
                        <span style="color:#f1e1aa;font-weight:600"><?= $bookedEndDt->format('h:i A') ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="session-timer" data-start="<?= $sess['start_time'] ?>" data-planned="<?= $sess['planned_minutes'] ?? '' ?>">—</span></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="openEndSessionModal(
                        <?= $sess['session_id'] ?>,
                        '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                        '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                        '<?= $sess['rental_mode'] ?>',
                        <?= strtotime($sess['start_time']) ?>,
                        <?= (int)($sess['planned_minutes'] ?? 0) ?>,
                        <?= (float)($sess['upfront_paid'] ?? 0) ?>)">
                        <i class="fas fa-stop-circle"></i> End &amp; Pay
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Recent Sessions -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">Recent Sessions</h3></div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Duration</th><th>Cost</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($recentSessions, 0, 8) as $sess): ?>
            <tr>
                <td>#<?= $sess['session_id'] ?></td>
                <td><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td><?= htmlspecialchars($sess['unit_number']) ?></td>
                <td><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></td>
                <td><?= $sess['duration_minutes'] !== null ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'].' min' : '< 1 min') : '—' ?></td>
                <td><?= $sess['total_cost'] ? '₱'.number_format($sess['total_cost'],2) : '—' ?></td>
                <td><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
