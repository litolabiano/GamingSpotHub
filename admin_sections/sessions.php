<!-- ════ SESSIONS ══════════════════════════════════════════════════════════ -->


<style>
/* ── Sortable table headers ─────────────────────────────────────────── */
#sessionsTable thead th {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
    transition: color .15s;
}
#sessionsTable thead th:hover { color: #20c8a1; }
#sessionsTable thead th .sort-icon {
    display: inline-block;
    margin-left: 5px;
    font-size: 10px;
    opacity: .35;
    transition: opacity .15s, color .15s;
}
#sessionsTable thead th.sort-asc  .sort-icon,
#sessionsTable thead th.sort-desc .sort-icon { opacity: 1; color: #20c8a1; }
#sessionsTable thead th.no-sort { cursor: default; }
#sessionsTable thead th.no-sort:hover { color: inherit; }

/* ── Editable end-time cell ─────────────────────────────────────────── */
.end-time-display {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    border-bottom: 1px dashed rgba(255,255,255,.25);
    padding-bottom: 1px;
    transition: border-color .15s, color .15s;
}
.end-time-display:hover { color: #20c8a1; border-color: #20c8a1; }
.end-time-display .edit-pen {
    font-size: 10px;
    opacity: 0;
    transition: opacity .15s;
}
.end-time-display:hover .edit-pen { opacity: 1; }

.end-time-edit-wrap {
    display: none;
    align-items: center;
    gap: 6px;
}
.end-time-edit-wrap input[type="time"] {
    background: rgba(10,33,81,.8);
    border: 1px solid #20c8a1;
    color: #f0f0f0;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    width: 100px;
}
.end-time-edit-wrap .btn-confirm {
    background: #20c8a1;
    color: #0a0f1c;
    border: none;
    border-radius: 6px;
    padding: 4px 9px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.end-time-edit-wrap .btn-confirm:hover { background: #17a887; }
.end-time-edit-wrap .btn-cancel-edit {
    background: transparent;
    border: 1px solid rgba(251,86,107,.5);
    color: #fb566b;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    transition: background .15s;
}
.end-time-edit-wrap .btn-cancel-edit:hover { background: rgba(251,86,107,.1); }

.saving-indicator { font-size: 11px; color: #f1e1aa; }

@keyframes cellFlash {
    0%   { background: rgba(32,200,161,.3); }
    100% { background: transparent; }
}
.cell-updated { animation: cellFlash .8s ease forwards; }
</style>

<div class="page" id="sessions">

<!-- ── RESERVATIONS PANEL ──────────────────────────────────────────────── -->
<div id="reservations-panel" class="card" style="margin-bottom:28px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <h2 class="card-title" style="margin:0;">
                <i class="fas fa-calendar-check" style="color:#20c8a1;"></i> Reservations
            </h2>
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
    <div style="padding:40px;text-align:center;color:#555;">
        <i class="fas fa-calendar-xmark" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
        No upcoming reservations for the next 14 days.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Date & Time</th>
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Customer</th>
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Console</th>
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Mode</th>
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Downpayment</th>
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Status</th>
                    <th style="padding:10px 14px;color:#888;font-weight:600;text-align:left;">Actions</th>
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
            <tr style="border-bottom:1px solid rgba(255,255,255,.04);<?= $isToday ? 'background:rgba(32,200,161,.03);' : '' ?>">
                <td style="padding:12px 14px;white-space:nowrap;">
                    <?php if ($isToday): ?>
                    <span style="color:#20c8a1;font-size:10px;font-weight:700;display:block;">TODAY</span>
                    <?php endif; ?>
                    <?= date('M d, Y', strtotime($r['reserved_date'])) ?><br>
                    <span style="color:#888;font-size:11px;"><?= date('h:i A', strtotime($r['reserved_time'])) ?></span>
                </td>
                <td style="padding:12px 14px;">
                    <div style="font-weight:600;color:#f0f0f0;"><?= htmlspecialchars($r['customer_name']) ?></div>
                    <?php if ($r['customer_phone']): ?>
                    <div style="color:#888;font-size:11px;"><?= htmlspecialchars($r['customer_phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 14px;">
                    <?= htmlspecialchars($r['console_type']) ?>
                    <?php if ($r['unit_number']): ?>
                    <br><span style="color:#20c8a1;font-size:11px;font-weight:700;"><?= htmlspecialchars($r['unit_number']) ?></span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 14px;color:#aaa;">
                    <?= match($r['rental_mode']) {
                        'open_time' => 'Open Time',
                        'unlimited' => 'Unlimited',
                        default => 'Hourly' . ($r['planned_minutes'] ? ' (' . ($r['planned_minutes']/60) . 'h)' : '')
                    } ?>
                </td>
                <td style="padding:12px 14px;">
                    <?php if ($r['downpayment_amount'] > 0): ?>
                    <span style="color:#20c8a1;font-weight:700;">₱<?= number_format($r['downpayment_amount'], 2) ?></span>
                    <span style="color:#888;font-size:11px;display:block;"><?= ucfirst($r['downpayment_method'] ?? '') ?></span>
                    <?php else: ?>
                    <span style="color:#555;">—</span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 14px;">
                    <span style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;border:1px solid <?= $sc['border'] ?>;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;text-transform:uppercase;">
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
                <td style="padding:12px 14px;">
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm this reservation?')">
                            <input type="hidden" name="action" value="confirm_reservation">
                            <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm" title="Confirm">
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
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as no-show?')">
                            <input type="hidden" name="action" value="noshow_reservation">
                            <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" title="No-show">
                                <i class="fas fa-ghost"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this reservation?')">
                            <input type="hidden" name="action" value="cancel_reservation">
                            <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Cancel">
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


<?php if (!empty($pendingSessions)): ?>
    <div class="card" style="border-left:3px solid #f1a83c;margin-bottom:20px;">
        <div class="card-header" style="border-bottom:1px solid rgba(241,168,60,.2);">
            <h3 class="card-title" style="color:#f1a83c;">
                <i class="fas fa-clock" style="margin-right:8px;"></i>
                Pending Payments
                <span style="background:rgba(241,168,60,.2);color:#f1a83c;font-size:12px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:8px;">
                    <?= count($pendingSessions) ?>
                </span>
            </h3>
            <span style="font-size:12px;color:#888;">Sessions with outstanding balances</span>
        </div>
        <table class="data-table" id="pendingPaymentsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Console</th>
                    <th>Mode</th>
                    <th>Started</th>
                    <th>Status</th>
                    <th>Paid So Far</th>
                    <th>Balance Owed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingSessions as $ps):
                $psStart   = strtotime($ps['start_time']);
                $psElapsed = time() - $psStart;
                $psH = floor($psElapsed / 3600);
                $psM = floor(($psElapsed % 3600) / 60);
                $psPaid = (float)$ps['paid_so_far'];
                $isCompleted = ($ps['status'] === 'completed');

                if ($isCompleted) {
                    // Completed: use actual total_cost
                    $psExpected  = (float)$ps['total_cost'];
                    $psModeLabel = match($ps['rental_mode']) {
                        'open_time' => 'Open Time',
                        'unlimited' => 'Unlimited',
                        default => 'Hourly'
                    };
                } else {
                    // Active: estimate expected cost
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
                <td><?= date('h:i A', $psStart) ?></td>
                <td>
                    <?php if ($isCompleted): ?>
                    <span style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                        Ended
                    </span>
                    <?php else: ?>
                    <span class="session-timer" data-start="<?= $ps['start_time'] ?>" style="color:#f1e1aa;font-family:monospace;">
                        <?= ($psH ? $psH.'h ' : '') . str_pad($psM, 2, '0', STR_PAD_LEFT) . 'm' ?>
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
                        <i class="fas fa-peso-sign"></i> Pay
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
        <div class="card-header">
            <h3 class="card-title">All Sessions</h3>
            <div style="display:flex;gap:8px;align-items:center;">
                <button class="btn btn-secondary btn-sm" id="resetSortBtn" title="Reset to default sort: active sessions first"
                    style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);color:#ccc;font-size:12px;">
                    <i class="fas fa-sort-amount-down"></i> Default Sort
                </button>
                <button class="btn btn-primary btn-sm" onclick="openModal('startSession')"><i class="fas fa-plus"></i> New Session</button>
            </div>
        </div>
        <table class="data-table" id="sessionsTable">
            <thead>
                <tr>
                    <th data-col="0">#<span class="sort-icon">&#8597;</span></th>
                    <th data-col="1">Customer<span class="sort-icon">&#8597;</span></th>
                    <th data-col="2">Console<span class="sort-icon">&#8597;</span></th>
                    <th data-col="3">Mode<span class="sort-icon">&#8597;</span></th>
                    <th data-col="4">Booked<span class="sort-icon">&#8597;</span></th>
                    <th data-col="5">Start<span class="sort-icon">&#8597;</span></th>
                    <th data-col="6">End<span class="sort-icon">&#8597;</span></th>
                    <th data-col="7">Duration<span class="sort-icon">&#8597;</span></th>
                    <th data-col="8">Cost<span class="sort-icon">&#8597;</span></th>
                    <th data-col="9">Status<span class="sort-icon">&#8597;</span></th>
                    <th class="no-sort">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentSessions as $sess): ?>
            <?php
                $bookedMinutes = ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes'])
                    ? (int)$sess['planned_minutes'] : 0;
                $startTs   = strtotime($sess['start_time']);
                $endTs     = $sess['end_time'] ? strtotime($sess['end_time']) : 0;
                $isLive    = $sess['status'] === 'active' ? 1 : 0;
                $costVal   = $sess['total_cost'] ? (float)$sess['total_cost'] : 0;
                $durationV = $sess['duration_minutes'] !== null ? (int)$sess['duration_minutes'] : -1;
                // Current end time in 24-hour HH:MM for the time input default value
                $endHHMM   = $sess['end_time'] ? date('H:i', $endTs) : '';
            ?>
            <tr
                data-id="<?= $sess['session_id'] ?>"
                data-customer="<?= htmlspecialchars(strtolower($sess['customer_name'])) ?>"
                data-console="<?= htmlspecialchars(strtolower($sess['unit_number'])) ?>"
                data-mode="<?= htmlspecialchars($sess['rental_mode']) ?>"
                data-booked="<?= $bookedMinutes ?>"
                data-start="<?= $startTs ?>"
                data-end="<?= $endTs ?>"
                data-duration="<?= $durationV ?>"
                data-cost="<?= $costVal ?>"
                data-status="<?= $isLive ?>"
            >
                <td>#<?= $sess['session_id'] ?></td>
                <td><?= htmlspecialchars($sess['customer_name']) ?></td>
                <td><?= htmlspecialchars($sess['unit_number']) ?></td>
                <td><?= match($sess['rental_mode']) { 'open_time' => 'Open Time', default => ucfirst($sess['rental_mode']) } ?></td>
                <td>
                    <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']):
                        $ph = intdiv($sess['planned_minutes'], 60);
                        $pm = $sess['planned_minutes'] % 60;
                        echo $ph ? ($pm ? "{$ph}h {$pm}m" : "{$ph}h") : "{$pm}m";
                    else: ?>—<?php endif; ?>
                </td>
                <td><?= date('M d h:i A', $startTs) ?></td>

                <!-- ── End Time cell (editable for completed sessions only) ── -->
                <td class="end-time-cell" data-session-id="<?= $sess['session_id'] ?>">
                <?php if ($sess['status'] === 'completed' && $sess['end_time']): ?>
                    <span class="end-time-display" title="Click to edit end time">
                        <?= date('h:i A', $endTs) ?>
                        <i class="fas fa-pen edit-pen"></i>
                    </span>
                    <span class="end-time-edit-wrap">
                        <input type="time" class="end-time-input" value="<?= $endHHMM ?>">
                        <button class="btn-confirm" type="button">✓</button>
                        <button class="btn-cancel-edit" type="button">✕</button>
                    </span>
                <?php elseif ($sess['status'] === 'active'): ?>
                    <span style="color:#20c8a1">Live</span>
                <?php else: ?>—<?php endif; ?>
                </td>

                <td class="duration-cell">
                    <?= $sess['duration_minutes'] !== null
                        ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'].' min' : '< 1 min')
                        : '—' ?>
                </td>

                <td class="cost-cell">
                    <?= $sess['total_cost'] ? '₱'.number_format($sess['total_cost'],2) : '—' ?>
                </td>

                <td><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
                <td>
                <?php if ($sess['status'] === 'active'): ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;min-width:160px;">
                        <button class="btn btn-danger btn-sm" title="End Session"
                            style="justify-content:center;"
                            onclick="openEndSessionModal(
                            <?= $sess['session_id'] ?>,
                            '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                            '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                            '<?= $sess['rental_mode'] ?>',
                            <?= $startTs ?>,
                            <?= $bookedMinutes ?>,
                            <?= (float)($sess['upfront_paid'] ?? 0) ?>,
                            <?= (float)($settings['unlimited_rate'] ?? 300) ?>
                        )">
                            <i class="fas fa-stop"></i> End
                        </button>
                        <button class="btn btn-sm" title="Collect Payment"
                            style="background:rgba(32,200,161,.18);border:1px solid rgba(32,200,161,.5);color:#20c8a1;font-weight:700;justify-content:center;"
                            onclick="openPayModal(
                                <?= $sess['session_id'] ?>,
                                '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                                '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                                '<?= $sess['rental_mode'] ?>',
                                <?= $startTs ?>,
                                <?= $bookedMinutes ?>,
                                <?= (float)($sess['upfront_paid'] ?? 0) ?>,
                                <?= (float)($settings['unlimited_rate'] ?? 300) ?>
                            )">
                            <i class="fas fa-peso-sign"></i> Pay
                        </button>
                        <button class="btn btn-sm" title="Issue Refund"
                            style="background:rgba(241,168,60,.15);border:1px solid rgba(241,168,60,.4);color:#f1a83c;justify-content:center;"
                            onclick="openRefundModal(
                                <?= $sess['session_id'] ?>,
                                '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                                '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                                <?= (float)($sess['upfront_paid'] ?? 0) ?>
                            )">
                            <i class="fas fa-undo-alt"></i> Refund
                        </button>
                        <button class="btn btn-sm" title="Extend Session"
                            style="background:rgba(95,133,218,.15);border:1px solid rgba(95,133,218,.4);color:#8aa4e8;justify-content:center;"
                            onclick="openExtendModal(
                                <?= $sess['session_id'] ?>,
                                '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                                '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                                <?= $bookedMinutes ?>
                            )">
                            <i class="fas fa-clock"></i> Extend
                        </button>
                    </div>

                <?php else: ?>—<?php endif; ?>
                </td>

            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
/* ══════════════════════════════════════════════════════════════════════
   Inline End-Time Editor
   All datetime reconstruction is done SERVER-SIDE (PHP/Manila tz).
   JS only sends HH:MM — never computes timestamps.
   ══════════════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.end-time-cell').forEach(function (cell) {
        const display    = cell.querySelector('.end-time-display');
        const editWrap   = cell.querySelector('.end-time-edit-wrap');
        if (!display || !editWrap) return;   // live / no end time rows

        const input      = editWrap.querySelector('.end-time-input');
        const confirmBtn = editWrap.querySelector('.btn-confirm');
        const cancelBtn  = editWrap.querySelector('.btn-cancel-edit');
        const row        = cell.closest('tr');
        const sessionId  = cell.dataset.sessionId;

        /* ── Open editor ── */
        display.addEventListener('click', function () {
            display.style.display  = 'none';
            editWrap.style.display = 'inline-flex';
            input.focus();
        });

        /* ── Cancel ── */
        cancelBtn.addEventListener('click', function () {
            editWrap.style.display = 'none';
            display.style.display  = 'inline-flex';
        });

        /* ── Keyboard shortcuts ── */
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter')  confirmBtn.click();
            if (e.key === 'Escape') cancelBtn.click();
        });

        /* ── Confirm & save ── */
        confirmBtn.addEventListener('click', function () {
            const timeVal = input.value;   // "HH:MM" — 24-hour
            if (!timeVal) return;

            // Show saving state
            editWrap.style.display  = 'none';
            display.style.display   = 'inline-flex';
            const origHtml = display.innerHTML;
            display.innerHTML = '<span class="saving-indicator"><i class="fas fa-spinner fa-spin"></i> Saving…</span>';

            /* POST only session_id + HH:MM — PHP handles full datetime math */
            const fd = new FormData();
            fd.append('session_id',   sessionId);
            fd.append('new_end_hhmm', timeVal);

            fetch('ajax/recalculate_session.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(function (data) {
                    if (!data.success) {
                        showInlineToast('Could not update: ' + data.message, 'error');
                        display.innerHTML = origHtml;
                        return;
                    }

                    /* ── Update End display (12-hour, no date) ── */
                    display.innerHTML =
                        data.end_time_display +
                        ' <i class="fas fa-pen edit-pen"></i>';

                    /* Update the input default so re-opening shows the new time */
                    input.value = data.end_time_24;

                    /* ── Flash-update Duration ── */
                    const durCell = row.querySelector('.duration-cell');
                    durCell.textContent = data.duration_display;
                    flashCell(durCell);

                    /* ── Flash-update Cost ── */
                    const costCell = row.querySelector('.cost-cell');
                    costCell.textContent = data.cost_display;
                    flashCell(costCell);

                    /* ── Keep sort data-* in sync ── */
                    row.dataset.duration = data.duration_minutes;
                    row.dataset.cost     = data.total_cost;
                    // data-end: convert the PHP 24h time back to a rough ts
                    // (only used for client-side sort; precision is fine)
                    const [h, m] = data.end_time_24.split(':').map(Number);
                    const startTs = parseInt(row.dataset.start);      // seconds
                    const startDate = new Date(startTs * 1000);
                    const endDate   = new Date(startDate);
                    endDate.setHours(h, m, 0, 0);
                    if (endDate.getTime() / 1000 <= startTs) endDate.setDate(endDate.getDate() + 1);
                    row.dataset.end = Math.floor(endDate.getTime() / 1000);
                })
                .catch(function () {
                    showInlineToast('Network error — please check your connection and try again.', 'error');
                    display.innerHTML = origHtml;
                });
        });
    });

    function flashCell(el) {
        el.classList.remove('cell-updated');
        void el.offsetWidth;   // reflow to restart animation
        el.classList.add('cell-updated');
    }

}); // end DOMContentLoaded

/* ══════════════════════════════════════════════════════════════════════
   Sortable Column Headers
   ══════════════════════════════════════════════════════════════════════ */
(function () {
    const DATA_KEYS    = ['id','customer','console','mode','booked','start','end','duration','cost','status'];
    const NUMERIC_COLS = new Set([0, 4, 5, 6, 7, 8, 9]);

    const table      = document.getElementById('sessionsTable');
    const tbody      = table.querySelector('tbody');
    const headers    = table.querySelectorAll('thead th[data-col]');
    const resetBtn   = document.getElementById('resetSortBtn');

    let currentCol = null;
    let currentAsc = true;

    headers.forEach(function (th) {
        th.addEventListener('click', function () {
            const col   = parseInt(th.dataset.col);
            const isAsc = (currentCol === col) ? !currentAsc : true;
            sortTable(col, isAsc);
            updateIcons(th, isAsc);
            currentCol = col;
            currentAsc = isAsc;
        });
    });

    /* ── Default sort: active sessions first, then newest start time first ── */
    function applyDefaultSort() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function (a, b) {
            const aStatus = parseInt(a.dataset.status) || 0;  // 1 = active
            const bStatus = parseInt(b.dataset.status) || 0;
            if (bStatus !== aStatus) return bStatus - aStatus; // active (1) on top
            // Secondary: newest start time first (descending)
            const aStart = parseFloat(a.dataset.start) || 0;
            const bStart = parseFloat(b.dataset.start) || 0;
            return bStart - aStart;
        });
        rows.forEach(function (r) { tbody.appendChild(r); });

        // Clear all column sort indicators
        headers.forEach(function (th) {
            th.classList.remove('sort-asc', 'sort-desc');
            th.querySelector('.sort-icon').innerHTML = '&#8597;';
        });
        currentCol = null;
        currentAsc = true;
    }

    function sortTable(col, asc) {
        const key   = DATA_KEYS[col];
        const isNum = NUMERIC_COLS.has(col);
        const rows  = Array.from(tbody.querySelectorAll('tr'));

        rows.sort(function (a, b) {
            let av = a.dataset[key];
            let bv = b.dataset[key];
            if (isNum) { av = parseFloat(av) || 0; bv = parseFloat(bv) || 0; }
            else        { av = av.toLowerCase();    bv = bv.toLowerCase();    }
            if (av < bv) return asc ? -1 : 1;
            if (av > bv) return asc ?  1 : -1;
            return 0;
        });

        rows.forEach(function (r) { tbody.appendChild(r); });
    }

    function updateIcons(activeTh, asc) {
        headers.forEach(function (th) {
            th.classList.remove('sort-asc', 'sort-desc');
            th.querySelector('.sort-icon').innerHTML = '&#8597;';
        });
        activeTh.classList.add(asc ? 'sort-asc' : 'sort-desc');
        activeTh.querySelector('.sort-icon').innerHTML = asc ? '&#8593;' : '&#8595;';
    }

    // Apply default sort immediately on load
    applyDefaultSort();

    // Reset Sort button
    resetBtn.addEventListener('click', function () {
        applyDefaultSort();
    });
})();

/* ── Inline toast helper (replaces browser alert) ── */
function showInlineToast(msg, type) {
    const existing = document.getElementById('sessionsInlineToast');
    if (existing) existing.remove();

    const colors = type === 'error'
        ? { bg: 'rgba(251,86,107,.15)', border: 'rgba(251,86,107,.4)', color: '#fb566b', icon: 'fa-exclamation-circle' }
        : { bg: 'rgba(32,200,161,.15)',  border: 'rgba(32,200,161,.4)',  color: '#20c8a1', icon: 'fa-check-circle' };

    const el = document.createElement('div');
    el.id = 'sessionsInlineToast';
    el.style.cssText = `position:fixed;top:80px;right:20px;z-index:9999;
        padding:14px 20px;border-radius:10px;font-size:14px;font-weight:500;
        display:flex;align-items:center;gap:10px;max-width:400px;
        background:${colors.bg};border:1px solid ${colors.border};color:${colors.color};
        box-shadow:0 8px 32px rgba(0,0,0,.4);animation:slideInRight .3s ease;`;
    el.innerHTML = `<i class="fas ${colors.icon}"></i> ${msg}`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4500);
}
</script>
