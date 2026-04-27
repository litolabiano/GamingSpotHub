<!-- ════ RESERVATIONS ════════════════════════════════════════════════════ -->
<div class="page" id="reservations">

    <!-- Stats summary -->
    <div class="stats-grid" style="margin-bottom:24px;">
        <?php
        $resPending   = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'pending'));
        $resConfirmed = count(array_filter($upcomingReservations, fn($r) => $r['status'] === 'confirmed'));
        $resToday     = count(array_filter($upcomingReservations, fn($r) => $r['reserved_date'] === date('Y-m-d')));
        $resNeedRefund = count(array_filter($cancelledReservations ?? [], fn($r) =>
        $r['cancelled_by'] === 'user' && (float)$r['downpayment_amount'] > 0 && !$r['refund_issued']));
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
                    <div class="stat-value"><?= $resNeedRefund ?></div>
                    <div class="stat-label">Refunds Pending</div>
                </div>
                <div class="stat-icon" style="background:rgba(251,86,107,.15);color:#fb566b;"><i class="fas fa-coins"></i></div>
            </div>
        </div>
    </div>

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
                                'confirmed' => ['bg' => 'rgba(32,200,161,.12)',  'text' => '#20c8a1',  'border' => 'rgba(32,200,161,.3)'],
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
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return false" id="formConfirmRes<?= $r['reservation_id'] ?>">
                                                <input type="hidden" name="action" value="confirm_reservation">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                                <button type="button" class="btn btn-primary btn-sm" title="Confirm"
                                                    onclick="gspotConfirm('Confirm this reservation?', function(){ document.getElementById('formConfirmRes<?= $r['reservation_id'] ?>').submit(); }, {yesLabel:'Yes, Confirm'})">
                                                    <i class="fas fa-check"></i> Confirm
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                                            <button class="btn btn-success btn-sm"
                                                onclick="openConvertModal(<?= htmlspecialchars(json_encode($r)) ?>)"
                                                title="Convert to Session">
                                                <i class="fas fa-play"></i> Start
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return false" id="formNoshowRes<?= $r['reservation_id'] ?>">
                                                <input type="hidden" name="action" value="noshow_reservation">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                                <button type="button" class="btn btn-secondary btn-sm" title="No-show"
                                                    onclick="gspotConfirm('Mark this reservation as no-show?', function(){ document.getElementById('formNoshowRes<?= $r['reservation_id'] ?>').submit(); }, {danger:true, yesLabel:'Mark No-Show'})">
                                                    <i class="fas fa-ghost"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm" title="Cancel"
                                                onclick="openAdminCancelModal(<?= $r['reservation_id'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
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
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date &amp; Time</th>
                            <th>Customer</th>
                            <th>Console</th>
                            <th>Mode</th>
                            <th>Payment</th>
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
                                    <?php if (!empty($r['customer_phone'])): ?>
                                        <div style="color:#888;font-size:11px;"><?= htmlspecialchars($r['customer_phone']) ?></div>
                                    <?php endif; ?>
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
                                        <span style="color:#fb566b;font-size:10px;font-weight:700;display:block;margin-top:2px;">Non-refundable</span>
                                    <?php else: ?>
                                        <span style="color:#555;">&#8212;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['cancelled_by'] === 'user'): ?>
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
                                    <?php
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
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ── Admin Cancel Reservation Reason Modal ── -->
<div id="adminCancelResModal" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(0,0,0,.7);backdrop-filter:blur(6px);
     align-items:center;justify-content:center;">
    <div style="background:#0e1d36;border:1px solid rgba(251,86,107,.4);border-radius:18px;
                padding:28px 28px 24px;max-width:460px;width:94%;box-shadow:0 20px 60px rgba(0,0,0,.6);">

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div style="width:40px;height:40px;border-radius:12px;background:rgba(251,86,107,.15);
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-ban" style="color:#fb566b;"></i>
            </div>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Cancel Reservation (Admin)</div>
                <div style="font-size:12px;color:#888;" id="adminCancelResSubtitle">Reservation #...</div>
            </div>
        </div>

        <form method="POST" id="adminCancelResForm" onsubmit="return adminCancelSubmit(event)">
            <input type="hidden" name="action" value="cancel_reservation">
            <?= csrfField() ?>
            <input type="hidden" name="reservation_id" id="adminCancelResId" value="">

            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;">Reason for Cancellation *</label>
                <select name="cancel_reason_type" id="adminCancelReasonType" required style="
                    width:100%;background:rgba(10,33,81,.7);
                    border:1px solid rgba(95,133,218,.3);
                    color:#f0f0f0;padding:11px 14px;border-radius:10px;
                    font-size:14px;font-family:inherit;outline:none;">
                    <option value="" disabled selected>-- Select a reason --</option>
                    <option value="schedule_change">Customer's schedule changed</option>
                    <option value="found_alternative">Customer found alternative</option>
                    <option value="budget_issue">Budget / financial reason</option>
                    <option value="technical_issue">Technical or system issue</option>
                    <option value="emergency">Customer emergency</option>
                    <option value="admin_decision">Admin / operational decision</option>
                    <option value="other">Other reason&hellip;</option>
                </select>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:12px;font-weight:700;color:#888;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;"
                       id="adminCancelDetailLabel">Additional Details (Optional)</label>
                <textarea name="cancel_reason_detail" id="adminCancelReasonDetail" rows="3"
                    placeholder="Add any relevant notes..." style="
                    width:100%;background:rgba(10,33,81,.7);
                    border:1px solid rgba(95,133,218,.3);
                    color:#f0f0f0;padding:11px 14px;border-radius:10px;
                    font-size:13px;font-family:inherit;outline:none;
                    resize:vertical;box-sizing:border-box;"></textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" style="flex:1;padding:11px;border-radius:10px;border:none;
                        background:linear-gradient(135deg,#fb566b,#e03050);color:#fff;
                        font-weight:700;font-size:13px;cursor:pointer;">
                    <i class="fas fa-ban"></i> Confirm Cancellation
                </button>
                <button type="button" onclick="closeAdminCancelModal()"
                    style="flex:1;padding:11px;border-radius:10px;
                           border:1px solid rgba(255,255,255,.15);background:transparent;
                           color:#aaa;font-weight:700;font-size:13px;cursor:pointer;">
                    Keep Reservation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdminCancelModal(resId) {
    document.getElementById('adminCancelResId').value       = resId;
    document.getElementById('adminCancelResSubtitle').textContent = 'Reservation #' + resId;
    document.getElementById('adminCancelReasonType').value  = '';
    document.getElementById('adminCancelReasonDetail').value = '';
    document.getElementById('adminCancelDetailLabel').textContent = 'Additional Details (Optional)';
    document.getElementById('adminCancelResModal').style.display = 'flex';
}
function closeAdminCancelModal() {
    document.getElementById('adminCancelResModal').style.display = 'none';
}
document.getElementById('adminCancelReasonType')?.addEventListener('change', function() {
    const isOther = this.value === 'other';
    document.getElementById('adminCancelDetailLabel').textContent =
        isOther ? 'Please describe the reason *' : 'Additional Details (Optional)';
});
function adminCancelSubmit(e) {
    const type   = document.getElementById('adminCancelReasonType').value;
    const detail = document.getElementById('adminCancelReasonDetail').value.trim();
    if (!type) {
        e.preventDefault();
        alert('Please select a reason for cancellation.');
        return false;
    }
    if (type === 'other' && !detail) {
        e.preventDefault();
        alert('Please describe the reason for cancellation.');
        document.getElementById('adminCancelReasonDetail').focus();
        return false;
    }
    return true;
}
document.getElementById('adminCancelResModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAdminCancelModal();
});
</script>
