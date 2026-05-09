<!-- ════ SESSIONS ══════════════════════════════════════════════════════════ -->
<?php
// Helper: display a styled Walk-in badge or the real customer name.
function sessionCustomerLabel(array $sess, bool $forJs = false): string {
    if ((int)($sess['user_id'] ?? 0) === WALKIN_USER_ID) {
        return $forJs ? 'Walk-in' : '<span style="background:rgba(138,164,232,.18);color:#8aa4e8;border:1px solid rgba(138,164,232,.35);border-radius:20px;padding:1px 9px;font-size:11px;font-weight:700;letter-spacing:.3px;">&#128694; Walk-in</span>';
    }
    if ($forJs) return htmlspecialchars(addslashes($sess['customer_name']));
    
    $email = !empty($sess['customer_email']) ? '<div style="color:#888; font-size:11px; font-weight:400; margin-top:1px;">' . htmlspecialchars($sess['customer_email']) . '</div>' : '';
    
    $resBadge = '';
    if (!empty($sess['source_reservation_id'])) {
        $resBadge = '<div style="margin-top:4px;">
            <span style="background:rgba(32,200,161,.12); color:#20c8a1; border:1px solid rgba(32,200,161,.3); border-radius:4px; padding:1px 6px; font-size:10px; font-weight:700; display:inline-flex; align-items:center; gap:3px;" title="Reservation #' . $sess['source_reservation_id'] . '">
                <i class="fas fa-calendar-check" style="font-size:9px;"></i>
                RESERVED #' . $sess['source_reservation_id'] . '
            </span>
        </div>';
    }
    
    return '<div style="font-weight:600; color:#f0f0f0;">' . htmlspecialchars($sess['customer_name']) . '</div>' . $email . $resBadge;
}
?>

<style>
    /* ── Controller Rental inline badge (Console column) ───────────────────── */
    .sess-ctrl-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
        padding: 3px 8px;
        background: rgba(32, 200, 161, 0.1);
        border: 1px solid rgba(32, 200, 161, 0.25);
        border-radius: 6px;
        color: #20c8a1;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
        width: fit-content;
    }

    .sess-ctrl-badge i {
        font-size: 9px;
    }

    /* ── Sortable table headers ─────────────────────────────────────────── */
    #sessionsTable thead th {

        cursor: pointer;
        user-select: none;
        white-space: nowrap;
        transition: color .15s;
    }

    #sessionsTable thead th:hover {
        color: #20c8a1;
    }

    #sessionsTable thead th .sort-icon {
        display: inline-block;
        margin-left: 5px;
        font-size: 10px;
        opacity: .35;
        transition: opacity .15s, color .15s;
    }

    #sessionsTable thead th.sort-asc .sort-icon,
    #sessionsTable thead th.sort-desc .sort-icon {
        opacity: 1;
        color: #20c8a1;
    }

    #sessionsTable thead th.no-sort {
        cursor: default;
    }

    #sessionsTable thead th.no-sort:hover {
        color: inherit;
    }

    /* ── Editable end-time cell ─────────────────────────────────────────── */
    .end-time-display {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        border-bottom: 1px dashed rgba(255, 255, 255, .25);
        padding-bottom: 1px;
        transition: border-color .15s, color .15s;
    }

    .end-time-display:hover {
        color: #20c8a1;
        border-color: #20c8a1;
    }

    .end-time-display .edit-pen {
        font-size: 10px;
        opacity: 0;
        transition: opacity .15s;
    }

    .end-time-display:hover .edit-pen {
        opacity: 1;
    }

    .end-time-edit-wrap {
        display: none;
        align-items: center;
        gap: 6px;
    }

    .end-time-edit-wrap input[type="time"] {
        background: rgba(10, 33, 81, .8);
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

    .end-time-edit-wrap .btn-confirm:hover {
        background: #17a887;
        filter: brightness(1.1);
    }


    .end-time-edit-wrap .btn-cancel-edit {
        background: transparent;
        border: 1px solid rgba(251, 86, 107, .5);
        color: #fb566b;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        cursor: pointer;
        transition: background .15s;
    }

    .end-time-edit-wrap .btn-cancel-edit:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }


    .saving-indicator {
        font-size: 11px;
        color: #f1e1aa;
    }

    @keyframes cellFlash {
        0% {
            background: rgba(32, 200, 161, .3);
        }

        100% {
            background: transparent;
        }
    }

    .cell-updated {
        animation: cellFlash .8s ease forwards;
    }
</style>

<div class="page" id="sessions">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-play-circle" style="color:#5f85da;margin-right:10px;"></i>Session Management</h2>
            <p class="page-subtitle">View, manage, and control all gaming sessions</p>
        </div>
        <button class="btn-prim" onclick="openModal('startSession')">
            <i class="fas fa-plus"></i> New Session
        </button>

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
            <table class="data-table table-cards" id="pendingPaymentsTable">
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
                        $psPaid   = (float)$ps['paid_so_far'];
                        $psExtras = (float)($ps['approved_extras'] ?? 0); // approved additional_requests sum
                        $isCompleted = ($ps['status'] === 'completed');

                        if ($isCompleted) {
                            // Completed: use actual total_cost (already includes extras via endSession())
                            $psExpected  = (float)$ps['total_cost'];
                            $psModeLabel = match ($ps['rental_mode']) {
                                'open_time' => 'Open Time',
                                'unlimited' => 'Unlimited',
                                default => 'Hourly'
                            };
                        } else {
                            // Active: estimate expected cost from DB-driven pricing rules + approved extras
                            if ($ps['rental_mode'] === 'hourly' && $ps['planned_minutes']) {
                                // computeHourlySessionBaseCost() reverses the free-bonus to get
                                // the true PAID portion — avoids ₱400 for a 4hr+1hr-free session.
                                $psExpected = computeHourlySessionBaseCost((int)$ps['planned_minutes']);
                                $psModeLabel = 'Hourly';
                            } else {
                                $psExpected  = $unlimitedRateVal;
                                $psModeLabel = 'Unlimited';
                            }
                            // Add any approved additional_requests (e.g. controller rental)
                            // to the expected total so the balance owed is correct.
                            $psExpected += $psExtras;
                        }
                        $psOwed = max(0, $psExpected - $psPaid);
                        $bookedMinutes = ($ps['rental_mode'] === 'hourly' && $ps['planned_minutes']) ? (int)$ps['planned_minutes'] : 0;
                    ?>
                        <tr style="<?= $isCompleted ? 'background:rgba(251,86,107,.03);' : '' ?>">
                            <td data-label="#">#<?= $ps['session_id'] ?></td>
                            <td data-label="Customer"><?= sessionCustomerLabel($ps) ?></td>
                            <td data-label="Console"><?= htmlspecialchars($ps['unit_number']) ?></td>
                            <td data-label="Mode"><?= $psModeLabel ?></td>
                            <td data-label="Started"><?= date('h:i A', $psStart) ?></td>
                            <td data-label="Status">
                                <?php if ($isCompleted): ?>
                                    <span style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                                        Ended
                                    </span>
                                <?php else: ?>
                                    <span class="session-timer" data-start="<?= $ps['start_time'] ?>" style="color:#f1e1aa;font-family:monospace;">
                                        <?= ($psH ? $psH . 'h ' : '') . str_pad($psM, 2, '0', STR_PAD_LEFT) . 'm' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Paid So Far" style="color:#20c8a1;font-weight:700;">₱<?= number_format($psPaid, 2) ?></td>
                            <td data-label="Balance Owed">
                                <span style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);
                                 padding:3px 10px;border-radius:6px;font-weight:700;font-size:13px;">
                                    ₱<?= number_format($psOwed, 2) ?> due
                                </span>
                            </td>
                            <td data-label="Action">
                                <?php if ($psOwed > 0): ?>
                                    <button class="btn-prim btn-sm" title="Collect Payment"
                                        onclick="openPayModal(
                            <?= $ps['session_id'] ?>,
                            '<?= sessionCustomerLabel($ps, true) ?>',
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

                                <?php 
                                $psEndTs = strtotime($ps['end_time'] ?? '');
                                if ($isCompleted && $psEndTs && (time() - $psEndTs < 3600)): ?>
                                    <button class="btn-restore btn-sm" 
                                        onclick="restoreSession(<?= $ps['session_id'] ?>, '<?= sessionCustomerLabel($ps, true) ?>', '<?= htmlspecialchars(addslashes($ps['unit_number'])) ?>', <?= $psEndTs ?>, this)"
                                        data-end-ts="<?= $psEndTs ?>"
                                        style="background:rgba(32,200,161,.12);color:#20c8a1;border:1.5px solid rgba(32,200,161,.3);justify-content:center;font-size:11px;padding:4px 8px;margin-top:4px;">
                                        <i class="fas fa-undo"></i> Undo <span class="restore-timer"></span>
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
            <h3 class="card-title">All Sessions</h3>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex:1;justify-content:flex-end;">
                <div class="asb-search" style="max-width:260px;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="asb-input" id="sessionsSearch" placeholder="Search customer, console, mode…" autocomplete="off">
                    <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
                </div>
                <select class="asb-select" id="sessionsStatusFilter" title="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="ending_soon">Ending Soon</option>
                    <option value="completed">Completed</option>
                </select>
                <select class="asb-select" id="sessionsOriginFilter" title="Filter by origin">
                    <option value="">All Origins</option>
                    <option value="reservation">From Reservation</option>
                    <option value="walkin">Walk-in Sessions</option>
                </select>
                <button class="btn-sec btn-sm" id="resetSortBtn" title="Reset to default sort: active sessions first">
                    <i class="fas fa-sort-amount-down"></i>
                </button>

                <span class="asb-count" id="sessionsCount"></span>
            </div>
        </div>
        <table class="data-table table-cards" id="sessionsTable">
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
                        data-customer="<?= htmlspecialchars(strtolower((int)($sess['user_id'] ?? 0) === WALKIN_USER_ID ? 'walk-in' : $sess['customer_name'])) ?>"
                        data-email="<?= htmlspecialchars(strtolower($sess['customer_email'] ?? '')) ?>"
                        data-console="<?= htmlspecialchars(strtolower($sess['unit_number'])) ?>"
                        data-mode="<?= htmlspecialchars($sess['rental_mode']) ?>"
                        data-booked="<?= $bookedMinutes ?>"
                        data-planned="<?= $sess['planned_minutes'] ?? 0 ?>"
                        data-remaining="9999999"
                        data-start="<?= $startTs ?>"
                        data-end="<?= $endTs ?>"
                        data-duration="<?= $durationV ?>"
                        data-cost="<?= $costVal ?>"
                        data-status="<?= $isLive ?>"
                        data-source-res-id="<?= $sess['source_reservation_id'] ?? 0 ?>">
                        <td data-label="#">#<?= $sess['session_id'] ?></td>
                        <td data-label="Customer"><?= sessionCustomerLabel($sess) ?></td>
                        <td data-label="Console" class="console-cell" data-session-id="<?= $sess['session_id'] ?>">
                            <span class="console-display end-time-display" title="Click to reassign console" style="border-bottom:1px dashed rgba(255, 255, 255, .25);">
                                <span class="unit-text"><?= htmlspecialchars($sess['unit_number']) ?></span>
                                <i class="fas fa-pen edit-pen" style="font-size:10px;margin-left:4px;"></i>
                            </span>
                            <span class="console-edit-wrap end-time-edit-wrap" style="display:none;align-items:center;gap:6px;">
                                <select class="console-select" style="background:rgba(10,33,81,.8);border:1px solid #20c8a1;color:#f0f0f0;padding:3px 6px;border-radius:6px;font-size:12px;outline:none;width:80px;">
                                    <option value="" disabled selected>— Select —</option>
                                    <?php foreach ($allConsoles as $c): ?>
                                        <?php if ($sess['status'] === 'completed' || $c['status'] === 'available'): ?>
                                            <option value="<?= $c['console_id'] ?>"><?= htmlspecialchars($c['unit_number']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-confirm-console btn-confirm" type="button" style="padding:4px 8px;font-size:11px;">✓</button>
                                <button class="btn-cancel-console btn-cancel-edit" type="button" style="padding:4px 8px;font-size:11px;">✕</button>
                            </span>
                        </td>
                        <td data-label="Mode"><?= match ($sess['rental_mode']) {
                                'open_time' => 'Open Time',
                                default => ucfirst($sess['rental_mode'])
                            } ?></td>
                        <td data-label="Booked">
                            <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']):
                                $ph = intdiv($sess['planned_minutes'], 60);
                                $pm = $sess['planned_minutes'] % 60;
                                echo $ph ? ($pm ? "{$ph}h {$pm}m" : "{$ph}h") : "{$pm}m";
                            else: ?>—<?php endif; ?>
                        </td>
                        <td data-label="Start"><?= date('M d h:i A', $startTs) ?></td>

                        <!-- ── End Time cell (editable for completed sessions only) ── -->
                        <td data-label="End" class="end-time-cell" data-session-id="<?= $sess['session_id'] ?>">
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
                                <?php if ($sess['rental_mode'] === 'hourly' && $sess['planned_minutes']): ?>
                                    <?php $projectedEndTs = $startTs + ($sess['planned_minutes'] * 60); ?>
                                    <div class="projected-end-wrap">
                                        <span style="color:#20c8a1;font-weight:600;" title="Projected end: start + booked time">
                                            <?= date('h:i A', $projectedEndTs) ?>
                                        </span>
                                        <div class="remaining-countdown" style="margin-top:2px;"></div>
                                        <span style="font-size:10px;color:#5f85da;display:block;margin-top:2px;" class="projected-label">Projected</span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#20c8a1">Live</span>
                                <?php endif; ?>
                                <?php else: ?>—<?php endif; ?>
                        </td>

                        <td data-label="Duration" class="duration-cell">
                            <?= $sess['duration_minutes'] !== null
                                ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'] . ' min' : '< 1 min')
                                : '—' ?>
                        </td>
                <td data-label="Cost" class="cost-cell">
                            <?= $sess['total_cost'] ? '₱' . number_format($sess['total_cost'], 2) : '—' ?>
                        </td>

                        <td data-label="Status"><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
                        <td data-label="Action">
                            <?php if ($sess['status'] === 'active'): ?>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;min-width:170px;">
                                    <button class="btn-dang btn-sm" title="End Session"
                                        style="justify-content:center;flex:1 1 70px;"
                                        onclick="openEndSessionModal(
                            <?= $sess['session_id'] ?>,
                            '<?= sessionCustomerLabel($sess, true) ?>',
                            '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                            '<?= $sess['rental_mode'] ?>',
                            <?= $startTs ?>,
                            <?= $bookedMinutes ?>,
                            <?= (float)($sess['upfront_paid'] ?? 0) ?>,
                            <?= (float)($sess['reservation_downpayment'] ?? 0) ?>,
                            <?= (float)($settings['unlimited_rate'] ?? 300) ?>,
                            <?= (int)($sess['source_reservation_id'] ?? 0) ?>)">
                                        <i class="fas fa-stop"></i> End
                                    </button>


                                    <?php if ($sess['rental_mode'] !== 'unlimited'): ?>
                                    <button class="btn-sec btn-sm" title="Extend Session"
                                        style="justify-content:center;flex:1 1 70px;"
                                        onclick="openExtendModal(
                                <?= $sess['session_id'] ?>,
                                '<?= sessionCustomerLabel($sess, true) ?>',
                                '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                                <?= $bookedMinutes ?>,
                                '<?= $sess['rental_mode'] ?>'
                            )">
                                        <i class="fas fa-clock"></i> Extend
                                    </button>

                                    <?php endif; ?>
                                </div>

                                <?php else: ?>
                                    <div style="display:flex;flex-direction:column;gap:4px;">
                                        <?php if ($sess['status'] === 'completed'): ?>
                                            <?php 
                                            $endTs = strtotime($sess['end_time']);
                                            $now = time();
                                            $diff = $now - $endTs;
                                            $canRestore = ($diff < 3600); // 1 hour window
                                            ?>
                                            <?php if ($canRestore): ?>
                                                <button class="btn-restore btn-sm" 
                                                        onclick="restoreSession(<?= $sess['session_id'] ?>, '<?= sessionCustomerLabel($sess, true) ?>', '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>', <?= $endTs ?>, this)"
                                                        data-end-ts="<?= $endTs ?>"
                                                        style="background:rgba(32,200,161,.12);color:#20c8a1;border:1.5px solid rgba(32,200,161,.3);justify-content:center;font-size:11px;padding:4px 8px;">
                                                    <i class="fas fa-undo"></i> Undo <span class="restore-timer"></span>
                                                </button>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="asb-no-results" id="sessionsSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:28px;margin-bottom:8px;opacity:.4;"></i>No sessions match your search.</div>
        <div id="sessionsPagination"></div>
    </div>
    
    <?php
    // ── Build active controller rentals list (active sessions only, no duplicates) ──
    $activeCtrlRentals = [];
    $_seenCtrlConsoles = [];
    foreach ($recentSessions as $sess) {
        // Treat paused sessions as still "live" for controller rentals visibility.
        if (!in_array($sess['status'], ['active', 'paused'], true)) continue;
        $cid = (int)($sess['console_id'] ?? 0);
        if (!isset($ctrlRentalByConsole[$cid])) continue;    // must have a rental
        if (isset($_seenCtrlConsoles[$cid])) continue;       // deduplicate by console
        $_seenCtrlConsoles[$cid] = true;
        $cr = $ctrlRentalByConsole[$cid];
        $activeCtrlRentals[] = [
            'session_id'    => $sess['session_id'],
            'customer_name' => $sess['customer_name'],
            'user_id'       => $sess['user_id'],
            'unit_number'   => $sess['unit_number'],
            'qty'           => $cr['qty'],
            'max_mins'      => $cr['max_mins'] ?? 0,
            'total_cost'    => $cr['total_cost'],
            'rented_since'  => $cr['rented_since'],
        ];
    }
    ?>

        <div class="card" style="margin-top:24px; border-left:3px solid #20c8a1;">
            <div class="card-header" style="border-bottom:1px solid rgba(32,200,161,.15);flex-wrap:wrap;gap:10px;">
                <h3 class="card-title" style="color:#20c8a1;">
                    <i class="fa-solid fa-gamepad" style="margin-right:8px;"></i>
                    Active Controller Rentals
                    <span style="background:rgba(32,200,161,.15);color:#20c8a1;font-size:12px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:8px;">
                        <?= count($activeCtrlRentals) ?>
                    </span>
                </h3>
                <div style="display:flex;align-items:center;gap:10px;margin-left:auto;">
                    <span style="font-size:12px;color:#888;">Controllers currently rented out in active sessions</span>
                    <span class="asb-count" id="ctrlRentalsCount"></span>
                </div>
            </div>
            <?php if (!empty($activeCtrlRentals)): ?>
                <table class="data-table table-cards" id="ctrlRentalsTable">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Customer</th>
                            <th>Console</th>
                            <th>Controllers</th>
                            <th>Duration</th>
                            <th>Rented At</th>
                            <th>Ends At</th>
                            <th>Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeCtrlRentals as $cr): ?>
                            <?php
                                $rentedTs = strtotime($cr['rented_since']);
                                $ph = $cr['max_mins'] > 0 ? intdiv($cr['max_mins'], 60) : 0;
                                $pm = $cr['max_mins'] > 0 ? $cr['max_mins'] % 60 : 0;
                                $durStr = $cr['max_mins'] > 0
                                    ? ($ph ? ($pm ? "{$ph}h {$pm}m" : "{$ph}h") : "{$pm}m")
                                    : '—';
                                $endsTs  = $cr['max_mins'] > 0 ? ($rentedTs + $cr['max_mins'] * 60) : null;
                                $isExpired = $endsTs && time() > $endsTs;
                            ?>
                            <tr>
                            <td data-label="Session">#<?= $cr['session_id'] ?></td>
                            <td data-label="Customer">
                                    <?php if ((int)$cr['user_id'] === WALKIN_USER_ID): ?>
                                        <span style="background:rgba(241,168,60,.15);color:#f1a83c;border:1px solid rgba(241,168,60,.3);border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                            <i class="fas fa-walking" style="font-size:9px;"></i> Walk-in
                                        </span>
                                    <?php else: ?>
                                        <span style="font-weight:600;color:#f0f0f0;"><?= htmlspecialchars($cr['customer_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                            <td data-label="Console" style="font-weight:700;color:#f1a83c;"><?= htmlspecialchars($cr['unit_number']) ?></td>
                            <td data-label="Controllers">
                                    <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(32,200,161,.1);border:1px solid rgba(32,200,161,.2);border-radius:6px;padding:3px 9px;color:#20c8a1;font-weight:700;font-size:12px;">
                                        <i class="fa-solid fa-gamepad" style="font-size:10px;"></i>
                                        <?= $cr['qty'] ?>× Controller<?= $cr['qty'] > 1 ? 's' : '' ?>
                                    </span>
                                </td>
                            <td data-label="Duration"><?= $durStr ?></td>
                            <td data-label="Rented At"><?= date('h:i A', $rentedTs) ?></td>
                            <td data-label="Ends At">
                                    <?php if ($endsTs): ?>
                                        <span style="color:<?= $isExpired ? '#fb566b' : '#20c8a1' ?>;font-weight:700;">
                                            <?= date('h:i A', $endsTs) ?>
                                            <?php if ($isExpired): ?>
                                                <span style="font-size:10px;background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);border-radius:4px;padding:1px 5px;margin-left:4px;">OVERDUE</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#888;">—</span>
                                    <?php endif; ?>
                                </td>
                            <td data-label="Fee" style="color:#f1e1aa;font-weight:700;">₱<?= number_format($cr['total_cost'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="ctrlRentalsPagination"></div>
            <?php else: ?>
                <div style="padding:16px 18px;color:#888;">
                    No active controller rentals right now.
                </div>
            <?php endif; ?>
        </div>
</div>

<script>
    /* ══════════════════════════════════════════════════════════════════════
   Inline End-Time Editor
   All datetime reconstruction is done SERVER-SIDE (PHP/Manila tz).
   JS only sends HH:MM — never computes timestamps.
   ══════════════════════════════════════════════════════════════════════ */

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.end-time-cell').forEach(function(cell) {
            const display = cell.querySelector('.end-time-display');
            const editWrap = cell.querySelector('.end-time-edit-wrap');
            if (!display || !editWrap) return; // live / no end time rows

            const input = editWrap.querySelector('.end-time-input');
            const confirmBtn = editWrap.querySelector('.btn-confirm');
            const cancelBtn = editWrap.querySelector('.btn-cancel-edit');
            const row = cell.closest('tr');
            const sessionId = cell.dataset.sessionId;

            /* ── Open editor ── */
            display.addEventListener('click', function() {
                display.style.display = 'none';
                editWrap.style.display = 'inline-flex';
                input.focus();
            });

            /* ── Cancel ── */
            cancelBtn.addEventListener('click', function() {
                editWrap.style.display = 'none';
                display.style.display = 'inline-flex';
            });

            /* ── Keyboard shortcuts ── */
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') confirmBtn.click();
                if (e.key === 'Escape') cancelBtn.click();
            });

            /* ── Confirm & save ── */
            confirmBtn.addEventListener('click', function() {
                const timeVal = input.value; // "HH:MM" — 24-hour
                if (!timeVal) return;

                // Show saving state
                editWrap.style.display = 'none';
                display.style.display = 'inline-flex';
                const origHtml = display.innerHTML;
                display.innerHTML = '<span class="saving-indicator"><i class="fas fa-spinner fa-spin"></i> Saving…</span>';

                /* POST only session_id + HH:MM — PHP handles full datetime math */
                const fd = new FormData();
                fd.append('session_id', sessionId);
                fd.append('new_end_hhmm', timeVal);

                fetch('ajax/recalculate_session.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(function(data) {
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
                        row.dataset.cost = data.total_cost;
                        // data-end: convert the PHP 24h time back to a rough ts
                        // (only used for client-side sort; precision is fine)
                        const [h, m] = data.end_time_24.split(':').map(Number);
                        const startTs = parseInt(row.dataset.start); // seconds
                        const startDate = new Date(startTs * 1000);
                        const endDate = new Date(startDate);
                        endDate.setHours(h, m, 0, 0);
                        if (endDate.getTime() / 1000 <= startTs) endDate.setDate(endDate.getDate() + 1);
                        row.dataset.end = Math.floor(endDate.getTime() / 1000);
                    })
                    .catch(function() {
                        showInlineToast('Network error — please check your connection and try again.', 'error');
                        display.innerHTML = origHtml;
                    });
            });
        });

        function flashCell(el) {
            el.classList.remove('cell-updated');
            void el.offsetWidth; // reflow to restart animation
            el.classList.add('cell-updated');
        }

        /* ── Inline Console Reassignment (Active Sessions) ── */
        document.querySelectorAll('.console-cell').forEach(function(cell) {
            const display = cell.querySelector('.console-display');
            const editWrap = cell.querySelector('.console-edit-wrap');
            if (!display || !editWrap) return;

            const select = editWrap.querySelector('.console-select');
            const confirmBtn = editWrap.querySelector('.btn-confirm-console');
            const cancelBtn = editWrap.querySelector('.btn-cancel-console');
            const row = cell.closest('tr');
            const sessionId = cell.dataset.sessionId;

            display.addEventListener('click', function() {
                display.style.display = 'none';
                editWrap.style.display = 'inline-flex';
                select.focus();
            });

            cancelBtn.addEventListener('click', function() {
                editWrap.style.display = 'none';
                display.style.display = 'inline-flex';
            });

            confirmBtn.addEventListener('click', function() {
                const newConsoleId = select.value;
                if (!newConsoleId) return;

                editWrap.style.display = 'none';
                display.style.display = 'inline-flex';
                const origHtml = display.innerHTML;
                display.innerHTML = '<span class="saving-indicator"><i class="fas fa-spinner fa-spin"></i> Saving…</span>';

                const fd = new FormData();
                fd.append('session_id', sessionId);
                fd.append('console_id', newConsoleId);

                fetch('ajax/reassign_console.php', { method: 'POST', credentials: 'same-origin', body: fd })
                    .then(r => r.json())
                    .then(function(data) {
                        if (!data.success) {
                            showInlineToast('Could not reassign: ' + data.message, 'error');
                            display.innerHTML = origHtml;
                            return;
                        }

                        // Success update
                        display.innerHTML = '<span class="unit-text">' + data.unit_number + '</span> <i class="fas fa-pen edit-pen" style="font-size:10px;margin-left:4px;"></i>';
                        row.dataset.console = data.unit_number.toLowerCase();
                        showInlineToast(data.message, 'success');
                        flashCell(cell);
                        
                        setTimeout(function() { 
                            if (typeof updateLiveSection === 'function') updateLiveSection();
                            else location.reload();
                        }, 1000);
                    })
                    .catch(function() {
                        showInlineToast('Network error — please check your connection and try again.', 'error');
                        display.innerHTML = origHtml;
                    });
            });
        });

    }); // end DOMContentLoaded

    /* ══════════════════════════════════════════════════════════════════════
       Sortable Column Headers
       ══════════════════════════════════════════════════════════════════════ */
    (function() {
        const DATA_KEYS = ['id', 'customer', 'console', 'mode', 'booked', 'start', 'end', 'duration', 'cost', 'status'];
        const NUMERIC_COLS = new Set([0, 4, 5, 6, 7, 8, 9]);

        const table = document.getElementById('sessionsTable');
        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('thead th[data-col]');
        const resetBtn = document.getElementById('resetSortBtn');

        let currentCol = null;
        let currentAsc = true;

        headers.forEach(function(th) {
            th.addEventListener('click', function() {
                const col = parseInt(th.dataset.col);
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
            rows.sort(function(a, b) {
                const aStatus = parseInt(a.dataset.status) || 0; // 1 = active
                const bStatus = parseInt(b.dataset.status) || 0;
                if (bStatus !== aStatus) return bStatus - aStatus; // active (1) on top
                // Secondary: newest start time first (descending)
                const aStart = parseFloat(a.dataset.start) || 0;
                const bStart = parseFloat(b.dataset.start) || 0;
                return bStart - aStart;
            });
            rows.forEach(function(r) {
                tbody.appendChild(r);
            });

            // Clear all column sort indicators
            headers.forEach(function(th) {
                th.classList.remove('sort-asc', 'sort-desc');
                th.querySelector('.sort-icon').innerHTML = '&#8597;';
            });
            currentCol = null;
            currentAsc = true;
        }

        function sortTable(col, asc) {
            const key = DATA_KEYS[col];
            const isNum = NUMERIC_COLS.has(col);
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort(function(a, b) {
                let av = a.dataset[key];
                let bv = b.dataset[key];
                if (isNum) {
                    av = parseFloat(av) || 0;
                    bv = parseFloat(bv) || 0;
                } else {
                    av = av.toLowerCase();
                    bv = bv.toLowerCase();
                }
                if (av < bv) return asc ? -1 : 1;
                if (av > bv) return asc ? 1 : -1;
                return 0;
            });

            rows.forEach(function(r) {
                tbody.appendChild(r);
            });
        }

        function updateIcons(activeTh, asc) {
            headers.forEach(function(th) {
                th.classList.remove('sort-asc', 'sort-desc');
                th.querySelector('.sort-icon').innerHTML = '&#8597;';
            });
            activeTh.classList.add(asc ? 'sort-asc' : 'sort-desc');
            activeTh.querySelector('.sort-icon').innerHTML = asc ? '&#8593;' : '&#8595;';
        }

        // Apply default sort immediately on load
        applyDefaultSort();

        // Reset Sort button
        resetBtn.addEventListener('click', function() {
            applyDefaultSort();
            if (window._sessionsPag) window._sessionsPag.reset();
        });
    })();

    /* ── Sessions live search + status filter + pagination ─────────────────── */
    (function() {
        const searchInput  = document.getElementById('sessionsSearch');
        const statusFilter = document.getElementById('sessionsStatusFilter');
        const table        = document.getElementById('sessionsTable');
        if (!table) return;
        const tbody = table.querySelector('tbody');

        /* Paginator — 10 rows per page */
        const pag = new AdminPaginator('sessionsTable', {
            pageSize:      10,
            pageSizes:     [10, 25, 50],
            paginationSel: '#sessionsPagination',
            noResultsSel:  '#sessionsSearch_noResults',
            countSel:      '#sessionsCount',
        });
        window._sessionsPag = pag; // expose for sort reset

        function filterRows() {
            const q  = (searchInput?.value || '').trim().toLowerCase();
            const st = (statusFilter?.value || '').toLowerCase();
            const og = (document.getElementById('sessionsOriginFilter')?.value || '').toLowerCase();
            const endingSoon = st === 'ending_soon';
            
            tbody.querySelectorAll('tr').forEach(row => {
                const rowStatus = row.dataset.status === '1' ? 'active' : 'completed';
                const rowOrigin = parseInt(row.dataset.sourceResId) > 0 ? 'reservation' : 'walkin';
                
                const hay = [
                    row.dataset.customer || '',
                    row.dataset.console  || '',
                    row.dataset.mode     || '',
                    row.dataset.id       || '',
                    rowStatus,
                    rowOrigin
                ].join(' ').toLowerCase();
                
                let statusMatch = !st || rowStatus === st;
                if (endingSoon) {
                    statusMatch = (rowStatus === 'active' && parseInt(row.dataset.planned) > 0);
                }

                let originMatch = !og || rowOrigin === og;

                const match = (!q || hay.includes(q)) && statusMatch && originMatch;
                row.classList.toggle('asb-hidden', !match);
            });

            if (endingSoon) {
                sortEndingSoon();
            }

            /* Update clear-btn visibility */
            const cb = searchInput?.parentElement?.querySelector('.asb-clear');
            if (cb) cb.style.display = q ? 'block' : 'none';
            pag.reset();
        }

        if (searchInput)  searchInput.addEventListener('input', filterRows);
        if (statusFilter) statusFilter.addEventListener('change', filterRows);
        const originFilter = document.getElementById('sessionsOriginFilter');
        if (originFilter) originFilter.addEventListener('change', filterRows);
        const clearBtn = searchInput?.parentElement?.querySelector('.asb-clear');
        if (clearBtn) clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterRows();
            searchInput.focus();
        });

        /* Initial render */
        pag.apply();
    })();

    /* ── Controller Rentals table pagination ───────────────────────────────── */
    (function() {
        if (!document.getElementById('ctrlRentalsTable')) return;
        const pagCtrl = new AdminPaginator('ctrlRentalsTable', {
            pageSize:      10,
            pageSizes:     [10, 25, 50],
            paginationSel: '#ctrlRentalsPagination',
            countSel:      '#ctrlRentalsCount',
        });
        pagCtrl.apply();
    })();

    /* ── Inline toast helper (replaces browser alert) ── */
    function showInlineToast(msg, type) {
        const existing = document.getElementById('sessionsInlineToast');
        if (existing) existing.remove();

        const colors = type === 'error' ? {
            bg: 'rgba(251,86,107,.15)',
            border: 'rgba(251,86,107,.4)',
            color: '#fb566b',
            icon: 'fa-exclamation-circle'
        } : {
            bg: 'rgba(32,200,161,.15)',
            border: 'rgba(32,200,161,.4)',
            color: '#20c8a1',
            icon: 'fa-check-circle'
        };

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

    function restoreSession(sessionId, customer, console, endTs, btn) {
        const now = Math.floor(Date.now() / 1000);
        const elapsedSec = now - endTs;
        const m = Math.floor(elapsedSec / 60);
        const s = elapsedSec % 60;
        const timeStr = (m > 0 ? `${m}m ` : "") + `${s}s`;

        if (!confirm(`Restore session for ${customer} on ${console}?\n\nIt has been ${timeStr} since this session was ended. This time will be deducted from the remaining session time.`)) {
            return;
        }

        fetch('ajax/restore_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `session_id=${sessionId}`
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                // Immediate UI feedback: hide button and update status badge
                if (btn) {
                    btn.style.display = 'none';
                    const row = btn.closest('tr');
                    if (row) {
                        // Find any "Ended" or "Completed" badge and swap it to "Active"
                        const badges = row.querySelectorAll('.badge, span');
                        badges.forEach(b => {
                            const txt = b.textContent.trim().toLowerCase();
                            if (txt === 'ended' || txt === 'completed') {
                                b.className = 'badge active';
                                b.textContent = 'Active';
                                b.style.background = 'rgba(32,200,161,.15)';
                                b.style.color = '#20c8a1';
                                b.style.border = '1px solid rgba(32,200,161,.3)';
                            }
                        });
                        // If it's the pending payments table, the status cell might contain the timer instead
                        const timer = row.querySelector('.session-timer');
                        if (timer) {
                            timer.style.display = 'inline-block';
                        }
                    }
                }

                if (typeof showAdminToast === 'function') {
                    showAdminToast(d.message, 'success');
                } else if (typeof showInlineToast === 'function') {
                    showInlineToast(d.message, 'success');
                } else {
                    alert('✓ ' + d.message);
                }
                if (typeof refreshSection === 'function') refreshSection();
            } else {
                alert('✕ ' + d.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Network error');
        });
    }

    /* ── Remaining Time Countdown ── */
    function initRemainingTimers() {
        const rows = document.querySelectorAll('#sessionsTable tbody tr');
        
        function updateTimers() {
            const now = Math.floor(Date.now() / 1000);
            let needsSort = false;
            const statusFilter = document.getElementById('sessionsStatusFilter');
            const isEndingSoonFilter = statusFilter && statusFilter.value === 'ending_soon';

            rows.forEach(row => {
                if (row.dataset.status !== '1') return; // Only active sessions
                const mode = row.dataset.mode;
                const planned = parseInt(row.dataset.planned);
                const start = parseInt(row.dataset.start);
                
                if (mode === 'hourly' && planned > 0) {
                    const totalSec = planned * 60;
                    const elapsed = now - start;
                    const remaining = totalSec - elapsed;
                    
                    row.dataset.remaining = remaining;
                    
                    const countdownEl = row.querySelector('.remaining-countdown');
                    const labelEl = row.querySelector('.projected-label');
                    
                    if (countdownEl) {
                        if (remaining <= 300) { // 5 minutes or less
                            const m = Math.floor(Math.abs(remaining) / 60);
                            const s = Math.abs(remaining) % 60;
                            const timeStr = (remaining < 0 ? '-' : '') + `${m}:${s.toString().padStart(2, '0')}`;
                            
                            countdownEl.innerHTML = `<span class="ending-soon-badge"><i class="fas fa-clock"></i> ${timeStr}</span>`;
                            countdownEl.style.display = 'block';
                            if (labelEl) labelEl.style.display = 'none';
                            row.classList.add('ending-soon-highlight');
                        } else {
                            countdownEl.style.display = 'none';
                            if (labelEl) labelEl.style.display = 'block';
                            row.classList.remove('ending-soon-highlight');
                        }
                    }
                } else {
                    row.dataset.remaining = "9999999";
                }
            });

            if (isEndingSoonFilter) {
                sortEndingSoon();
            }
        }

        function sortEndingSoon() {
            const table = document.getElementById('sessionsTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr:not(.asb-hidden)'));
            
            rows.sort((a, b) => {
                const remA = parseInt(a.dataset.remaining) || 9999999;
                const remB = parseInt(b.dataset.remaining) || 9999999;
                return remA - remB;
            });
            
            rows.forEach(r => tbody.appendChild(r));
        }

        // Run once immediately
        updateTimers();
        // Update every second
        setInterval(updateTimers, 1000);
        
        // Expose sort function for the filter
        window.sortEndingSoon = sortEndingSoon;
    }

    // Initial call
    initRemainingTimers();

    // Use MutationObserver to re-init timers when the section content is updated by live_section.php
    const sessionsContainer = document.getElementById('sessions');
    if (sessionsContainer) {
        const observer = new MutationObserver((mutations) => {
            initRemainingTimers();
        });
        observer.observe(sessionsContainer, { childList: true, subtree: true });
    }
</script>