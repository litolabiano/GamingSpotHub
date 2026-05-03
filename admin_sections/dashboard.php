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
                <span style="color:#8aa4e8"><?= $inUseCount ?> in use</span> &nbsp;
                <span style="color:#fb566b"><?= $maintenanceCount ?> maintenance</span>
            </div>
        </div>
    </div>

    <!-- Active Sessions Right Now -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-circle" style="color:#20c8a1;font-size:9px;margin-right:8px;animation:livePulse 2s ease infinite;"></i>
                Live Sessions
            </h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('startSession')">
                <i class="fas fa-plus"></i> Start Session
            </button>
        </div>
        <?php if (empty($activeSessions)): ?>
            <div class="empty-state">
                <i class="fas fa-couch"></i>
                <p>No active sessions right now</p>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Started</th><th>Booked Until</th><th>Elapsed / Remaining</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($activeSessions as $sess): ?>
            <tr>
                <td><span style="color:#555;font-size:12px">#</span><?= $sess['session_id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td>
                    <span class="console-type-badge <?= $sess['console_type'] === 'PS5' ? 'ps5' : ($sess['console_type'] === 'PS4' ? 'ps4' : 'xbox') ?>">
                        <?= $sess['console_type'] ?>
                    </span>
                    <span style="color:#aaa;font-size:12px;margin-left:4px"><?= htmlspecialchars($sess['unit_number']) ?></span>
                </td>
                <td><span class="badge pending"><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></span></td>
                <td><?= date('h:i A', strtotime($sess['start_time'])) ?></td>
                <td>
                    <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']):
                        $bookedEndDt = new DateTime($sess['start_time'], new DateTimeZone('Asia/Manila'));
                        $bookedEndDt->modify('+' . $sess['planned_minutes'] . ' minutes');
                    ?>
                        <span style="color:#f1e1aa;font-weight:700"><?= $bookedEndDt->format('h:i A') ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="session-timer"
                    data-start="<?= $sess['start_time'] ?>"
                    data-planned="<?= $sess['planned_minutes'] ?? '' ?>"
                    data-session-id="<?= $sess['session_id'] ?>"
                    data-mode="<?= $sess['rental_mode'] ?>"
                    data-start-ts="<?= strtotime($sess['start_time']) ?>"
                    data-upfront-paid="<?= (float)($sess['upfront_paid'] ?? 0) ?>"
                    data-unlimited-rate="<?= (float)($settings['unlimited_rate'] ?? 300) ?>"
                    data-booked-minutes="<?= (int)($sess['planned_minutes'] ?? 0) ?>"
                    data-customer="<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>"
                    data-unit="<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>">—</span></td>
                <td>
                    <button class="btn btn-danger btn-sm" title="End & Collect Payment"
                        onclick="openEndSessionModal(
                        <?= $sess['session_id'] ?>,
                        '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                        '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                        '<?= $sess['rental_mode'] ?>',
                        <?= strtotime($sess['start_time']) ?>,
                        <?= (int)($sess['planned_minutes'] ?? 0) ?>,
                        <?= (float)($sess['upfront_paid'] ?? 0) ?>,
                        <?= (float)($sess['reservation_downpayment'] ?? 0) ?>,
                        <?= (int)($sess['source_reservation_id'] ?? 0) ?>)">
                        <i class="fas fa-stop-circle"></i> End &amp; Pay
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── Today's Reservations snapshot ────────────────────────────────── -->
    <?php
    $todayRes = array_filter($upcomingReservations, fn($r) => $r['reserved_date'] === date('Y-m-d'));
    $todayRes = array_slice(array_values($todayRes), 0, 6);
    ?>
    <div class="card" style="border-left:3px solid #20c8a1;">
        <div class="card-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <h3 class="card-title" style="margin:0;">
                    <i class="fas fa-calendar-day" style="color:#20c8a1;margin-right:8px;"></i>Today's Reservations
                </h3>
                <?php if ($pendingResCount > 0): ?>
                <span style="background:rgba(241,168,60,.18);color:#f1a83c;border:1px solid rgba(241,168,60,.35);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                    <?= $pendingResCount ?> Pending
                </span>
                <?php endif; ?>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="showPage('reservations', document.querySelector('.nav-item[onclick*=\'reservations\']'))">
                <i class="fas fa-list"></i> View All
            </button>
        </div>

        <?php if (empty($todayRes)): ?>
        <div style="padding:24px;text-align:center;color:#444;font-size:13px;">
            <i class="fas fa-calendar-check" style="font-size:1.6rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            No reservations scheduled for today.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Time</th><th>Customer</th><th>Console</th><th>Mode</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($todayRes as $r):
                    $sc = ['pending' => ['#f1a83c','rgba(241,168,60,.15)'], 'confirmed' => ['#20c8a1','rgba(32,200,161,.15)']][$r['status']] ?? ['#888','rgba(100,100,100,.1)'];
                ?>
                <tr>
                    <td style="font-weight:600;color:#f1e1aa;white-space:nowrap;"><?= date('h:i A', strtotime($r['reserved_time'])) ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['customer_name']) ?></td>
                    <td><?= htmlspecialchars($r['console_type']) ?><?php if ($r['unit_number']): ?> <span style="color:#20c8a1;font-size:11px;font-weight:700"><?= htmlspecialchars($r['unit_number']) ?></span><?php endif; ?></td>
                    <td style="color:#aaa;font-size:12px;"><?= match($r['rental_mode']) { 'open_time' => 'Open Time', 'unlimited' => 'Unlimited', default => 'Hourly' . ($r['planned_minutes'] ? ' ('.($r['planned_minutes']/60).'h)' : '') } ?></td>
                    <td><span style="background:<?= $sc[1] ?>;color:<?= $sc[0] ?>;border:1px solid <?= $sc[0] ?>44;border-radius:20px;padding:2px 9px;font-size:11px;font-weight:700;text-transform:uppercase;"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return false" id="dashFormConfirm<?= $r['reservation_id'] ?>">
                                <input type="hidden" name="action" value="confirm_reservation">
                                <?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="button" class="btn btn-primary btn-sm" title="Confirm"
                                        onclick="gspotConfirm('Confirm this reservation?', function(){ document.getElementById('dashFormConfirm<?= $r['reservation_id'] ?>').submit(); }, {yesLabel:'Yes, Confirm'})">
                                    <i class="fas fa-check"></i> Confirm
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'confirmed'): ?>
                            <button class="btn btn-success btn-sm" onclick="openConvertModal(<?= htmlspecialchars(json_encode($r)) ?>)" title="Convert to Session">
                                <i class="fas fa-play"></i> Start
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($upcomingReservations) > 6): ?>
        <div style="padding:10px 20px;border-top:1px solid rgba(255,255,255,.05);font-size:12px;color:#555;text-align:right;">
            +<?= count($upcomingReservations) - 6 ?> more —
            <button onclick="showPage('reservations', document.querySelector('.nav-item[onclick*=\'reservations\']'))" style="background:none;border:none;color:#20c8a1;font-size:12px;cursor:pointer;padding:0;">View all reservations →</button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Session Recap (compact) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Sessions</h3>
            <button class="btn btn-secondary btn-sm" onclick="showPage('sessions', document.querySelector('.nav-item[onclick*=\'sessions\']'))">
                <i class="fas fa-list"></i> All Sessions
            </button>
        </div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Duration</th><th>Cost</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($recentSessions, 0, 6) as $sess): ?>
            <tr>
                <td style="color:#555;font-size:12px">#<?= $sess['session_id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td style="color:#aaa"><?= htmlspecialchars($sess['unit_number']) ?></td>
                <td style="color:#aaa"><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></td>
                <td style="color:#aaa"><?= $sess['duration_minutes'] !== null ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'].' min' : '< 1 min') : '—' ?></td>
                <td style="color:#20c8a1;font-weight:700"><?= $sess['total_cost'] ? '₱'.number_format($sess['total_cost'],2) : '—' ?></td>
                <td><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
