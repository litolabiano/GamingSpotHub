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

    <!-- ── RESERVATIONS (Dashboard view) ─────────────────────────────────── -->
    <div class="card" style="border-left:3px solid #20c8a1;margin-bottom:20px;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <h3 class="card-title" style="margin:0;">
                    <i class="fas fa-calendar-check" style="color:#20c8a1;margin-right:8px;"></i>Upcoming Reservations
                </h3>
                <?php if ($pendingResCount > 0): ?>
                <span style="background:rgba(241,168,60,.2);color:#f1a83c;border:1px solid rgba(241,168,60,.35);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                    <?= $pendingResCount ?> Pending
                </span>
                <?php endif; ?>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openModal('addReservation')">
                <i class="fas fa-plus"></i> Add Reservation
            </button>
        </div>

        <?php if (empty($upcomingReservations)): ?>
        <div style="padding:30px;text-align:center;color:#555;">
            <i class="fas fa-calendar-xmark" style="font-size:1.8rem;display:block;margin-bottom:8px;"></i>
            No upcoming reservations.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Console</th>
                        <th>Mode</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($upcomingReservations as $r):
                    $isToday = ($r['reserved_date'] === date('Y-m-d'));
                    $statusColors = [
                        'pending'   => ['bg'=>'rgba(241,168,60,.12)',  'text'=>'#f1a83c',  'border'=>'rgba(241,168,60,.3)'],
                        'confirmed' => ['bg'=>'rgba(32,200,161,.12)',  'text'=>'#20c8a1',  'border'=>'rgba(32,200,161,.3)'],
                    ];
                    $sc = $statusColors[$r['status']] ?? ['bg'=>'rgba(100,100,100,.1)','text'=>'#888','border'=>'rgba(100,100,100,.2)'];
                ?>
                <tr style="<?= $isToday ? 'background:rgba(32,200,161,.03);' : '' ?>">
                    <td style="white-space:nowrap;">
                        <?php if ($isToday): ?>
                        <span style="color:#20c8a1;font-size:10px;font-weight:700;display:block;">TODAY</span>
                        <?php endif; ?>
                        <?= date('M d, Y', strtotime($r['reserved_date'])) ?><br>
                        <span style="color:#888;font-size:11px;"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                    </td>
                    <td>
                        <div style="font-weight:600;color:#f0f0f0;"><?= htmlspecialchars($r['customer_name']) ?></div>
                        <?php if ($r['customer_phone']): ?>
                        <div style="color:#888;font-size:11px;"><?= htmlspecialchars($r['customer_phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($r['console_type']) ?>
                        <?php if ($r['unit_number']): ?>
                        <br><span style="color:#20c8a1;font-size:11px;font-weight:700;"><?= htmlspecialchars($r['unit_number']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#aaa;">
                        <?= match($r['rental_mode']) {
                            'open_time' => 'Open Time',
                            'unlimited' => 'Unlimited',
                            default => 'Hourly' . ($r['planned_minutes'] ? ' (' . ($r['planned_minutes']/60) . 'h)' : '')
                        } ?>
                    </td>
                    <td>
                        <?php if ($r['downpayment_amount'] > 0): ?>
                        <span style="color:#20c8a1;font-weight:700;">₱<?= number_format($r['downpayment_amount'], 2) ?></span>
                        <span style="color:#888;font-size:11px;display:block;"><?= ucfirst($r['downpayment_method'] ?? '') ?></span>
                        <?php else: ?>
                        <span style="color:#555;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;border:1px solid <?= $sc['border'] ?>;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;text-transform:uppercase;">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
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

                            <?php if (in_array($r['status'], ['pending','confirmed'])): ?>
                            <button class="btn btn-success btn-sm"
                                    onclick="openConvertModal(<?= htmlspecialchars(json_encode($r)) ?>)"
                                    title="Convert to Session">
                                <i class="fas fa-play"></i> Start
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return false" id="dashFormNoshow<?= $r['reservation_id'] ?>">
                                <input type="hidden" name="action" value="noshow_reservation">
                                <?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="button" class="btn btn-secondary btn-sm" title="No-show"
                                        onclick="gspotConfirm('Mark this reservation as no-show?', function(){ document.getElementById('dashFormNoshow<?= $r['reservation_id'] ?>').submit(); }, {danger:true, yesLabel:'Mark No-Show'})">
                                    <i class="fas fa-ghost"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return false" id="dashFormCancel<?= $r['reservation_id'] ?>">
                                <input type="hidden" name="action" value="cancel_reservation">
                                <?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="button" class="btn btn-danger btn-sm" title="Cancel"
                                        onclick="gspotConfirm('Cancel this reservation?', function(){ document.getElementById('dashFormCancel<?= $r['reservation_id'] ?>').submit(); }, {danger:true, yesLabel:'Yes, Cancel'})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
