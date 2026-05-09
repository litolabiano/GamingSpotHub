<!-- ════ RESERVATIONS ════════════════════════════════════════════════════ -->
<div class="page" id="reservations">
    <div id="reservationsContent">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-calendar-check" style="color:#20c8a1;margin-right:10px;"></i>Reservations</h2>
            <p class="page-subtitle">Manage upcoming reservations — paid via PayMongo</p>
        </div>
        <button class="btn-prim" onclick="openModal('addReservation')">
            <i class="fas fa-plus"></i> Add Reservation
        </button>

    </div>

    <!-- Stats summary -->
    <div class="stats-grid" style="margin-bottom:24px;">
        <?php
        $resPending   = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'pending'));
        $resConfirmed = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'reserved'));
        $resRescheduled = 0;
        try {
            $rrStat = $conn->query("SELECT COUNT(*) AS cnt FROM reservation_reschedules");
            if ($rrStat) $resRescheduled = (int)$rrStat->fetch_assoc()['cnt'];
        } catch (Exception $e) {}
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
                    <div class="stat-value"><?= $resRescheduled ?></div>
                    <div class="stat-label">Rescheduled</div>
                </div>
                <div class="stat-icon" style="background:rgba(32,200,161,.15);color:#20c8a1;"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
    </div>

    <!-- Pending User Reschedules -->
    <?php if (!empty($pendingUserReschedules)): ?>
    <div class="card" style="border-left:3px solid #f1a83c;margin-bottom:24px;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h3 class="card-title" style="margin:0;">
                <i class="fas fa-clock" style="color:#f1a83c;margin-right:8px;"></i>Customer Reschedule Requests
                <span style="background:rgba(241,168,60,.2);color:#f1a83c;border:1px solid rgba(241,168,60,.35);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;margin-left:8px;">
                    <?= count($pendingUserReschedules) ?> Pending
                </span>
            </h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Console</th>
                        <th>Requested From</th>
                        <th>Requested To</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingUserReschedules as $pr): ?>
                        <tr>
                            <td style="color:#888;">#<?= $pr['reservation_id'] ?></td>
                            <td>
                                <strong style="color:#f0f0f0;"><?= htmlspecialchars($pr['customer_name']) ?></strong>
                                <div style="color:#888; font-size: 11px; margin-top: 1px;">
                                    <?= htmlspecialchars((!empty($pr['customer_phone']) && $pr['customer_phone'] !== $pr['customer_email']) ? $pr['customer_phone'] : '—') ?>
                                </div>
                                <div style="color:#888; font-size: 11px;"><?= htmlspecialchars($pr['customer_email'] ?? '—') ?></div>
                            </td>
                            <td>
                                <?= htmlspecialchars($pr['console_type']) ?>
                                <?php if (!empty($pr['unit_number'])): ?>
                                    <br><span style="color:#20c8a1;font-size:11px;font-weight:700;"><?= htmlspecialchars($pr['unit_number']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#aaa;">
                                <?= date('M d, Y', strtotime($pr['old_date'])) ?><br>
                                <span style="font-size:11px;"><?= date('h:i A', strtotime($pr['old_time'])) ?></span>
                            </td>
                            <td>
                                <strong style="color:#20c8a1;"><?= date('M d, Y', strtotime($pr['new_date'])) ?></strong><br>
                                <span style="font-size:11px;color:#20c8a1;"><?= date('h:i A', strtotime($pr['new_time'])) ?></span>
                            </td>
                            <td>
                                <span style="font-size:12px;color:#f1a83c;font-weight:600;">User Request</span>
                                <?php if ($pr['reason_detail']): ?>
                                    <br><span style="font-size:11px;color:#888;"><?= htmlspecialchars($pr['reason_detail']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button class="btn-prim btn-sm" title="Approve"
                                        onclick="adminRespondReschedule(<?= $pr['reschedule_id'] ?>, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>

                                    <button class="btn-dang btn-sm" title="Reject"
                                        onclick="adminRespondReschedule(<?= $pr['reschedule_id'] ?>, 'reject')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Reservations Table -->
    <div class="card" style="border-left:3px solid #20c8a1;margin-bottom:24px;">
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
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <div class="asb-search" style="max-width:240px;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="asb-input" id="resSearch" placeholder="Search customer, console…" autocomplete="off">
                    <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
                </div>
                <select class="asb-select" id="resStatusFilter" title="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="reserved">Confirmed</option>
                </select>
                <span class="asb-count" id="resCount"></span>
            </div>
        </div>

        <?php if (empty($upcomingReservations)): ?>
            <div style="padding:50px;text-align:center;color:#555;">
                <i class="fas fa-calendar-xmark" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                <p style="font-size:15px;margin:0;">No upcoming reservations.</p>
                <p style="font-size:12px;color:#444;margin-top:4px;">New reservations will appear here once added.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table" id="resTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date &amp; Time</th>
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
                                'pending'   => ['bg' => 'rgba(241,168,60,.12)',  'text' => '#f1a83c',  'border' => 'rgba(241,168,60,.3)'],
                                'reserved'  => ['bg' => 'rgba(32,200,161,.12)',  'text' => '#20c8a1',  'border' => 'rgba(32,200,161,.3)'],
                                'no_show'   => ['bg' => 'rgba(251,86,107,.12)',  'text' => '#fb566b',  'border' => 'rgba(251,86,107,.3)'],
                            ];
                            $sc = $statusColors[$r['status']] ?? ['bg' => 'rgba(100,100,100,.1)', 'text' => '#888', 'border' => 'rgba(100,100,100,.2)'];
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
                                    <div style="color:#888; font-size: 11px; margin-top: 1px;">
                                        <?= htmlspecialchars((!empty($r['customer_phone']) && $r['customer_phone'] !== $r['customer_email']) ? $r['customer_phone'] : '—') ?>
                                    </div>
                                    <div style="color:#888; font-size: 11px;"><?= htmlspecialchars($r['customer_email'] ?? '—') ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($r['console_type']) ?>
                                    <?php if ($r['unit_number']): ?>
                                        <br><span style="color:#20c8a1;font-size:11px;font-weight:700;"><?= htmlspecialchars($r['unit_number']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#aaa;">
                                    <?= match ($r['rental_mode']) {
                                        'open_time' => 'Open Time',
                                        'unlimited' => 'Unlimited',
                                        default => 'Hourly' . ($r['planned_minutes'] ? ' (' . ($r['planned_minutes'] / 60) . 'h)' : '')
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
                                        <button class="btn-prim btn-sm" title="Reschedule"
                                            onclick="openRescheduleModal(<?= $r['reservation_id'] ?>, '<?= htmlspecialchars($r['customer_name']) ?>', '<?= $r['reserved_date'] ?>', '<?= $r['reserved_time'] ?>', '<?= addslashes($r['console_type']) ?>')">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </button>


                                         <button class="btn-sec btn-sm no-show-btn" title="No Show" 
                                            data-start="<?= $r['reserved_date'] . ' ' . $r['reserved_time'] ?>"
                                            onclick="markNoShow(<?= $r['reservation_id'] ?>, '<?= htmlspecialchars($r['customer_name']) ?>')"
                                            style="background:rgba(251,86,107,.15);border:1.5px solid rgba(251,86,107,.45);color:#fb566b;transition:all 0.3s;">
                                            <i class="fas fa-user-slash"></i> No Show
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="asb-no-results" id="resSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No reservations match your search.</div>
                <div id="resPagination"></div>
            </div>
        <?php endif; ?>
    </div>
    <!-- ── Cancelled Reservations table ── -->
    <?php
    $cancelledReservations = $cancelledReservations ?? [];
    $cancelReasonLabels = [
        'schedule_change'   => 'Schedule changed',
        'found_alternative' => 'Found alternative',
        'budget_issue'      => 'Budget / financial',
        'technical_issue'   => 'Technical issue',
        'emergency'         => 'Personal emergency',
        'other'             => 'Other',
        'admin_decision'    => 'Admin decision',
    ];
    ?>
    <?php if (!empty($cancelledReservations)): ?>
        <div class="card" style="border-left:3px solid #fb566b;">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h3 class="card-title" style="margin:0;">
                        <i class="fas fa-ban" style="color:#fb566b;margin-right:8px;"></i>Cancelled Reservations
                    </h3>
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="asb-search" style="max-width:220px;">
                        <i class="fas fa-search"></i>
                        <input type="text" class="asb-input" id="cancelResSearch" placeholder="Search…" autocomplete="off">
                        <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
                    </div>
                    <span class="asb-count" id="cancelResCount"></span>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table" id="cancelResTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date &amp; Time</th>
                            <th>Customer</th>
                            <th>Console</th>
                            <th>Mode</th>
                            <th>Downpayment</th>
                            <th>Cancelled By</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cancelledReservations as $r): ?>
                            <tr>
                                <td style="color:#888;">#<?= $r['reservation_id'] ?></td>
                                <td style="white-space:nowrap;">
                                    <?= date('M d, Y', strtotime($r['reserved_date'])) ?><br>
                                    <span style="color:#888;font-size:11px;"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                                </td>
                                <td>
                                    <div style="font-weight:600;color:#f0f0f0;"><?= htmlspecialchars($r['customer_name']) ?></div>
                                    <div style="color:#888; font-size: 11px; margin-top: 1px;">
                                        <?= htmlspecialchars((!empty($r['customer_phone']) && $r['customer_phone'] !== $r['customer_email']) ? $r['customer_phone'] : '—') ?>
                                    </div>
                                    <div style="color:#888; font-size: 11px;"><?= htmlspecialchars($r['customer_email'] ?? '—') ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($r['console_type']) ?>
                                    <?php if (!empty($r['unit_number'])): ?>
                                        <br><span style="color:#20c8a1;font-size:11px;font-weight:700;"><?= htmlspecialchars($r['unit_number']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#aaa;">
                                    <?= match ($r['rental_mode']) {
                                        'open_time' => 'Open Time',
                                        'unlimited' => 'Unlimited',
                                        default     => 'Hourly' . ($r['planned_minutes'] ? ' (' . ($r['planned_minutes'] / 60) . 'h)' : '')
                                    } ?>
                                </td>
                                <td>
                                    <?php if ((float)$r['downpayment_amount'] > 0): ?>
                                        <span style="color:#20c8a1;font-weight:700;">&#8369;<?= number_format((float)$r['downpayment_amount'], 2) ?></span>
                                        <span style="color:#888;font-size:11px;display:block;"><?= ucfirst($r['downpayment_method'] ?? '') ?></span>
                                        <span style="color:#fb566b;font-size:10px;font-weight:700;display:block;margin-top:2px;"
                                              title="Reservation downpayments are non-refundable per store policy.">
                                            <i class="fas fa-lock" style="margin-right:3px;"></i>Non-refundable
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#555;">&#8212;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'no_show'): ?>
                                        <span style="background:rgba(251,86,107,.1);color:#fb566b;border:1px solid rgba(251,86,107,.25);border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;">
                                            <i class="fas fa-user-slash" style="margin-right:3px;"></i>No-Show
                                        </span>
                                    <?php elseif ($r['cancelled_by'] === 'user'): ?>
                                        <span style="background:rgba(251,86,107,.1);color:#fb566b;border:1px solid rgba(251,86,107,.25);border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;">
                                            <i class="fas fa-user" style="margin-right:3px;"></i>Customer
                                        </span>
                                    <?php elseif ($r['cancelled_by'] === 'admin'): ?>
                                        <span style="background:rgba(150,150,150,.1);color:#888;border:1px solid rgba(150,150,150,.2);border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;">
                                            <i class="fas fa-user-shield" style="margin-right:3px;"></i>Staff
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#888;font-size:11px;font-style:italic;">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'no_show'): ?>
                                        <span style="font-size:12px;color:#fb566b;font-weight:600;">Customer did not arrive</span>
                                    <?php else: 
                                        $rt = $r['cancel_reason_type'] ?? null;
                                        $rd = $r['cancel_reason_detail'] ?? null;
                                        if ($rt): ?>
                                            <span style="font-size:12px;color:#d0d8f0;font-weight:600;">
                                                <?= htmlspecialchars($cancelReasonLabels[$rt] ?? ucfirst(str_replace('_', ' ', $rt))) ?>
                                            </span>
                                            <?php if ($rd): ?>
                                                <br><span style="font-size:11px;color:#888;"><?= htmlspecialchars($rd) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#555;font-size:12px;">&#8212;</span>
                                        <?php endif; 
                                    endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="asb-no-results" id="cancelResSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No cancelled reservations match your search.</div>
                <div id="cancelResPagination"></div>
            </div>
        </div>
    <?php endif; ?>

    </div> <!-- end #reservationsContent -->
</div><!-- /.page#reservations -->

<!-- ── Reschedule Reservation Modal (outside .page to avoid transform stacking context) ── -->
<div id="rescheduleResModal" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(0,0,0,.75);backdrop-filter:blur(6px);
     align-items:center;justify-content:center;">
    <div style="background:#0e1d36;border:1px solid rgba(32,200,161,.4);border-radius:18px;
                padding:28px 28px 24px;max-width:480px;width:94%;box-shadow:0 20px 60px rgba(0,0,0,.6);">

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div style="width:40px;height:40px;border-radius:12px;background:rgba(32,200,161,.15);
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-calendar-alt" style="color:#20c8a1;"></i>
            </div>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Reschedule Reservation</div>
                <div style="font-size:12px;color:#888;" id="rescheduleResSubtitle">Reservation #... &mdash; Customer</div>
            </div>
        </div>

        <input type="hidden" id="rescheduleResId">

        <!-- Reason -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">Reason *</label>
            <select id="rescheduleReason" required style="
                width:100%;background:rgba(10,33,81,.7);
                border:1px solid rgba(95,133,218,.3);
                color:#f0f0f0;padding:11px 14px;border-radius:10px;
                font-size:14px;font-family:inherit;outline:none;">
                <option value="" disabled selected>-- Select a reason --</option>
                <option value="typhoon">🌀 Typhoon / Bad Weather</option>
                <option value="power_outage">⚡ Power Outage</option>
                <option value="emergency">🚨 Emergency</option>
                <option value="maintenance">🔧 Equipment Maintenance</option>
                <option value="other">📋 Other&hellip;</option>
            </select>
        </div>

        <!-- Detail -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;"
                   id="rescheduleDetailLabel">Additional Notes (Optional)</label>
            <textarea id="rescheduleDetail" rows="2"
                placeholder="Explain the situation to the customer..." style="
                width:100%;background:rgba(10,33,81,.7);
                border:1px solid rgba(95,133,218,.3);
                color:#f0f0f0;padding:11px 14px;border-radius:10px;
                font-size:13px;font-family:inherit;outline:none;
                resize:vertical;box-sizing:border-box;"></textarea>
        </div>

        <!-- New Date -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">New Date *</label>
            <input type="date" id="rescheduleDate" required
                min="<?= date('Y-m-d') ?>"
                style="width:100%;background:rgba(10,33,81,.7);
                border:1px solid rgba(95,133,218,.3);
                color:#f0f0f0;padding:11px 14px;border-radius:10px;
                font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;">
        </div>

        <!-- New Time -->
        <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">New Time *</label>
            <select id="rescheduleTime" required style="
                width:100%;background:rgba(10,33,81,.7);
                border:1px solid rgba(95,133,218,.3);
                color:#f0f0f0;padding:11px 14px;border-radius:10px;
                font-size:14px;font-family:inherit;outline:none;">
                <option value="" disabled selected>-- Select a time --</option>
                <?php
                for ($h = 12; $h <= 23; $h++) {
                    foreach (['00', '30'] as $m) {
                        $val  = sprintf('%02d:%s', $h, $m);
                        $disp = date('g:i A', strtotime("2000-01-01 $val"));
                        echo "<option value=\"$val\">$disp</option>\n";
                    }
                }
                ?>
            </select>
        </div>

        <!-- Console Type -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">Console Type *</label>
            <select id="rescheduleConsoleType" required style="
                width:100%;background:rgba(10,33,81,.7);
                border:1px solid rgba(95,133,218,.3);
                color:#f0f0f0;padding:11px 14px;border-radius:10px;
                font-size:14px;font-family:inherit;outline:none;" onchange="refreshRescheduleUnits()">
                <?php foreach ($consoleTypes as $ct): ?>
                    <option value="<?= htmlspecialchars($ct['type_name']) ?>"><?= htmlspecialchars($ct['type_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- New Unit (Dynamic) -->
        <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">New Unit (Optional)</label>
            <select id="rescheduleUnit" style="
                width:100%;background:rgba(10,33,81,.7);
                border:1px solid rgba(95,133,218,.3);
                color:#f0f0f0;padding:11px 14px;border-radius:10px;
                font-size:14px;font-family:inherit;outline:none;">
                <option value="">-- Assign any available --</option>
            </select>
            <div id="rescheduleUnitStatus" style="font-size:11px;color:#888;margin-top:4px;">Select date & time to check unit availability</div>
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:10px;">
            <button type="button" class="btn-prim" id="rescheduleSubmitBtn" onclick="submitReschedule()" style="flex:1;">
                <i class="fas fa-calendar-check"></i> Confirm Reschedule
            </button>
            <button type="button" class="btn-sec" onclick="closeRescheduleModal()" style="flex:1;">
                Cancel
            </button>
        </div>

    </div>
</div>

<script>
function openRescheduleModal(resId, customerName, oldDate, oldTime, consoleType) {
    document.getElementById('rescheduleResId').value = resId;
    document.getElementById('rescheduleResSubtitle').textContent =
        'Reservation #' + resId + ' — ' + customerName;
    document.getElementById('rescheduleReason').value  = '';
    document.getElementById('rescheduleDetail').value  = '';
    document.getElementById('rescheduleDate').dataset.oldDate = oldDate;
    document.getElementById('rescheduleTime').dataset.oldTime = oldTime.substring(0,5);
    document.getElementById('rescheduleDate').dataset.consoleType = consoleType;
    document.getElementById('rescheduleConsoleType').value = consoleType;
    document.getElementById('rescheduleDate').value    = oldDate;
    document.getElementById('rescheduleTime').value    = oldTime.substring(0,5);
    document.getElementById('rescheduleResModal').style.display = 'flex';
    refreshRescheduleUnits();
}

function closeRescheduleModal() {
    document.getElementById('rescheduleResModal').style.display = 'none';
}
document.getElementById('rescheduleResModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRescheduleModal();
});
document.getElementById('rescheduleReason')?.addEventListener('change', function() {
    const isOther = this.value === 'other';
    document.getElementById('rescheduleDetailLabel').textContent =
        isOther ? 'Please describe the reason *' : 'Additional Notes (Optional)';
});
document.getElementById('rescheduleDate')?.addEventListener('change', refreshRescheduleUnits);
document.getElementById('rescheduleTime')?.addEventListener('change', refreshRescheduleUnits);

function refreshRescheduleUnits() {
    const date = document.getElementById('rescheduleDate').value;
    const time = document.getElementById('rescheduleTime').value;
    const type = document.getElementById('rescheduleConsoleType').value;
    const sel  = document.getElementById('rescheduleUnit');
    const stat = document.getElementById('rescheduleUnitStatus');
    if (!date || !time || !type) return;
    stat.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking units...';
    sel.disabled = true;
    fetch(`ajax/check_unit_availability.php?date=${date}&time=${time}&console_type=${encodeURIComponent(type)}`)
    .then(r => r.json())
    .then(data => {
        sel.disabled = false;
        if (!data.success) { stat.textContent = 'Error checking units'; return; }
        let html = '<option value="">-- Assign any available --</option>';
        data.units.forEach(u => {
            const ok = u.status === 'available';
            html += `<option value="${u.id}" ${ok ? '' : 'disabled'}>#${u.unit} - ${u.name} ${ok ? '(Available)' : '(' + u.status + ')'}</option>`;
        });
        sel.innerHTML = html;
        stat.textContent = data.units.filter(u => u.status === 'available').length + ' units available for this slot';
    })
    .catch(() => { sel.disabled = false; stat.textContent = 'Network error'; });
}

function submitReschedule() {
    const resId  = document.getElementById('rescheduleResId').value;
    const reason = document.getElementById('rescheduleReason').value;
    const detail = document.getElementById('rescheduleDetail').value.trim();
    const date   = document.getElementById('rescheduleDate').value;
    const time   = document.getElementById('rescheduleTime').value;
    const type   = document.getElementById('rescheduleConsoleType').value;
    const unitId = document.getElementById('rescheduleUnit').value;
    if (!reason) { alert('Please select a reason.'); return; }
    if (reason === 'other' && !detail) { alert('Please describe the reason.'); return; }
    if (!date)   { alert('Please select a new date.'); return; }
    if (!time)   { alert('Please select a new time.'); return; }
    if (!type)   { alert('Please select a console type.'); return; }
    const oldDate = document.getElementById('rescheduleDate').dataset.oldDate;
    const oldTime = document.getElementById('rescheduleTime').dataset.oldTime;
    if (date === oldDate && time === oldTime && !unitId && type === document.getElementById('rescheduleDate').dataset.consoleType) {
        alert('Please change the date, time, console type, or assign a specific unit.');
        return;
    }
    const btn = document.getElementById('rescheduleSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Rescheduling...';
    fetch('ajax/reschedule_reservation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ reservation_id: resId, reason, reason_detail: detail, new_date: date, new_time: time, console_type: type, console_id: unitId })
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid server response. The request might have succeeded, but we couldn\'t confirm it.');
        }
    })
    .then(data => {
        if (data.success) {
            closeRescheduleModal();
            if (typeof showAdminToast === 'function') { showAdminToast(data.message, 'success'); } else { alert('✓ ' + data.message); }
            refreshReservationsUI();
        } else {
            alert('✕ ' + (data.message || 'Failed to reschedule.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Reschedule';
        }
    })
    .catch(err => {
        alert('✕ ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Reschedule';
    });
}
</script>

<script>
let resPag, cancelPag;

function initReservationsHandlers() {
    /* ── Upcoming Reservations ── */
    const resSearch = document.getElementById('resSearch');
    const resStatus = document.getElementById('resStatusFilter');
    const resTable  = document.getElementById('resTable');

    resPag = new AdminPaginator('resTable', {
        pageSize:      10,
        pageSizes:     [10, 25, 50],
        paginationSel: '#resPagination',
        noResultsSel:  '#resSearch_noResults',
        countSel:      '#resCount',
    });

    function filterRes() {
        if (!resTable) return;
        const q  = (resSearch?.value || '').trim().toLowerCase();
        const st = (resStatus?.value || '').toLowerCase();
        resTable.querySelectorAll('tbody tr').forEach(row => {
            const hay = row.innerText.toLowerCase();
            const match = (!q || hay.includes(q)) && (!st || hay.includes(st));
            row.classList.toggle('asb-hidden', !match);
        });
        const cb = resSearch?.parentElement?.querySelector('.asb-clear');
        if (cb) cb.style.display = q ? 'block' : 'none';
        resPag.reset();
    }

    if (resSearch) resSearch.addEventListener('input', filterRes);
    if (resStatus) resStatus.addEventListener('change', filterRes);
    const resClear = resSearch?.parentElement?.querySelector('.asb-clear');
    if (resClear) resClear.addEventListener('click', () => { resSearch.value = ''; filterRes(); resSearch.focus(); });

    resPag.apply();

    /* ── Cancelled Reservations ── */
    const cancelSearch = document.getElementById('cancelResSearch');
    const cancelTable  = document.getElementById('cancelResTable');

    cancelPag = new AdminPaginator('cancelResTable', {
        pageSize:      10,
        pageSizes:     [10, 25, 50],
        paginationSel: '#cancelResPagination',
        noResultsSel:  '#cancelResSearch_noResults',
        countSel:      '#cancelResCount',
    });

    function filterCancel() {
        if (!cancelTable) return;
        const q = (cancelSearch?.value || '').trim().toLowerCase();
        cancelTable.querySelectorAll('tbody tr').forEach(row => {
            row.classList.toggle('asb-hidden', q && !row.innerText.toLowerCase().includes(q));
        });
        const cb = cancelSearch?.parentElement?.querySelector('.asb-clear');
        if (cb) cb.style.display = q ? 'block' : 'none';
        cancelPag.reset();
    }

    if (cancelSearch) cancelSearch.addEventListener('input', filterCancel);
    const cancelClear = cancelSearch?.parentElement?.querySelector('.asb-clear');
    if (cancelClear) cancelClear.addEventListener('click', () => { cancelSearch.value = ''; filterCancel(); cancelSearch.focus(); });

    cancelPag.apply();
}

function refreshReservationsUI() {
    fetch(location.href)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newContent = doc.getElementById('reservationsContent');
            const oldContent = document.getElementById('reservationsContent');
            if (newContent && oldContent) {
                oldContent.innerHTML = newContent.innerHTML;
                initReservationsHandlers();
            }
        });
}

document.addEventListener('DOMContentLoaded', initReservationsHandlers);

function adminRespondReschedule(rescheduleId, action) {
    if (!confirm('Are you sure you want to ' + action + ' this request?')) return;
    fetch('ajax/admin_respond_reschedule.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'reschedule_id=' + rescheduleId + '&action=' + action
    }).then(r => r.json()).then(d => {
        if (typeof showAdminToast === 'function') {
            showAdminToast(d.message, d.success ? 'success' : 'error');
        } else {
            alert((d.success ? '✓ ' : '✕ ') + d.message);
        }
        if (d.success) refreshReservationsUI();
    }).catch(e => {
        console.error(e);
        alert('Network error');
    });
}

function markNoShow(resId, name) {
    if (!confirm('Mark reservation for "' + name + '" as NO SHOW?\n\nThis will forfeit the reservation fee and release the console unit. No refund will be issued.')) return;

    fetch('ajax/mark_no_show.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'reservation_id=' + resId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof showAdminToast === 'function') {
                showAdminToast(data.message, 'success');
            } else {
                alert('✓ ' + data.message);
            }
            refreshReservationsUI();
        } else {
            alert('✕ ' + (data.message || 'Failed to mark as no show'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error');
    });
}
</script>