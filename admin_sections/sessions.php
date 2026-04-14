<!-- ════ SESSIONS ══════════════════════════════════════════════════════════ -->
<div class="page" id="sessions">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Sessions</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('startSession')"><i class="fas fa-plus"></i> New Session</button>
        </div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Customer</th><th>Console</th><th>Mode</th><th>Booked</th><th>Start</th><th>End</th><th>Duration</th><th>Cost</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recentSessions as $sess): ?>
            <tr>
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
                <td><?= date('M d h:i A', strtotime($sess['start_time'])) ?></td>
                <td><?= $sess['end_time'] ? date('h:i A', strtotime($sess['end_time'])) : '<span style="color:#20c8a1">Live</span>' ?></td>
                <td><?= $sess['duration_minutes'] !== null ? ($sess['duration_minutes'] > 0 ? $sess['duration_minutes'].' min' : '< 1 min') : '—' ?></td>
                <td><?= $sess['total_cost'] ? '₱'.number_format($sess['total_cost'],2) : '—' ?></td>
                <td><span class="badge <?= $sess['status'] ?>"><?= ucfirst($sess['status']) ?></span></td>
                <td>
                <?php if ($sess['status'] === 'active'): ?>
                    <button class="btn btn-danger btn-sm" onclick="openEndSessionModal(<?= $sess['session_id'] ?>, '<?= htmlspecialchars($sess['customer_name']) ?>', '<?= htmlspecialchars($sess['unit_number']) ?>')">
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
