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
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Sessions</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('startSession')"><i class="fas fa-plus"></i> New Session</button>
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
                    <button class="btn btn-danger btn-sm" onclick="openEndSessionModal(
                        <?= $sess['session_id'] ?>,
                        '<?= htmlspecialchars(addslashes($sess['customer_name'])) ?>',
                        '<?= htmlspecialchars(addslashes($sess['unit_number'])) ?>',
                        '<?= $sess['rental_mode'] ?>',
                        <?= $startTs ?>,
                        <?= $bookedMinutes ?>,
                        <?= (float)($sess['upfront_paid'] ?? 0) ?>
                    )">
                        <i class="fas fa-stop"></i> End
                    </button>
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
                        alert('Error: ' + data.message);
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
                    alert('Network error — please try again.');
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

    const table   = document.getElementById('sessionsTable');
    const tbody   = table.querySelector('tbody');
    const headers = table.querySelectorAll('thead th[data-col]');

    let currentCol = null;
    let currentAsc = true;

    headers.forEach(function (th) {
        th.addEventListener('click', function () {
            const col  = parseInt(th.dataset.col);
            const isAsc = (currentCol === col) ? !currentAsc : true;
            sortTable(col, isAsc);
            updateIcons(th, isAsc);
            currentCol = col;
            currentAsc = isAsc;
        });
    });

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
})();
</script>
