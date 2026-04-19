<!-- ════ RESERVATIONS ════════════════════════════════════════════════════ -->
<div class="page" id="reservations">

    <!-- Stats summary -->
    <div class="stats-grid" style="margin-bottom:24px;">
        <?php
            $resPending   = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'pending'));
            $resConfirmed = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'confirmed'));
            $resToday     = count(array_filter($upcomingReservations, fn($r) => $r['reserved_date'] === date('Y-m-d')));
        ?>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= count($upcomingReservations) ?></div>
                    <div class="stat-label">Total Upcoming</div>
                </div>
                <div class="stat-icon" style="background:rgba(32,200,161,.15);color:#20c8a1;"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $resPending ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-icon" style="background:rgba(241,168,60,.15);color:#f1a83c;"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $resConfirmed ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-icon sessions"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $resToday ?></div>
                    <div class="stat-label">Today</div>
                </div>
                <div class="stat-icon" style="background:rgba(179,123,236,.15);color:#b37bec;"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
    </div>

    <!-- Upcoming Reservations Table -->
    <div class="card" style="border-left:3px solid #20c8a1;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <h3 class="card-title" style="margin:0;">
                    <i class="fas fa-calendar-check" style="color:#20c8a1;margin-right:8px;"></i>All Upcoming Reservations
                </h3>
                <?php if ($resPending > 0): ?>
                <span style="background:rgba(241,168,60,.2);color:#f1a83c;border:1px solid rgba(241,168,60,.35);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                    <?= $resPending ?> Pending
                </span>
                <?php endif; ?>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openModal('addReservation')">
                <i class="fas fa-plus"></i> Add Reservation
            </button>
        </div>

        <?php if (empty($upcomingReservations)): ?>
        <div style="padding:50px;text-align:center;color:#555;">
            <i class="fas fa-calendar-xmark" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
            <p style="font-size:15px;margin:0;">No upcoming reservations.</p>
            <p style="font-size:12px;color:#444;margin-top:4px;">New reservations will appear here once added.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Console</th>
                        <th>Mode</th>
                        <th>Downpayment</th>
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
                    <td style="color:#888;">#<?= $r['reservation_id'] ?></td>
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
                            <form method="POST" style="display:inline;" onsubmit="return false" id="formConfirmRes<?= $r['reservation_id'] ?>">
                                <input type="hidden" name="action" value="confirm_reservation">
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="button" class="btn btn-primary btn-sm" title="Confirm"
                                        onclick="gspotConfirm('Confirm this reservation?', function(){ document.getElementById('formConfirmRes<?= $r['reservation_id'] ?>').submit(); }, {yesLabel:'Yes, Confirm'})">
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
                            <form method="POST" style="display:inline;" onsubmit="return false" id="formNoshowRes<?= $r['reservation_id'] ?>">
                                <input type="hidden" name="action" value="noshow_reservation">
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="button" class="btn btn-secondary btn-sm" title="No-show"
                                        onclick="gspotConfirm('Mark this reservation as no-show?', function(){ document.getElementById('formNoshowRes<?= $r['reservation_id'] ?>').submit(); }, {danger:true, yesLabel:'Mark No-Show'})">
                                    <i class="fas fa-ghost"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return false" id="formCancelRes<?= $r['reservation_id'] ?>">
                                <input type="hidden" name="action" value="cancel_reservation">
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="button" class="btn btn-danger btn-sm" title="Cancel"
                                        onclick="gspotConfirm('Cancel this reservation?', function(){ document.getElementById('formCancelRes<?= $r['reservation_id'] ?>').submit(); }, {danger:true, yesLabel:'Yes, Cancel'})">
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

</div>
