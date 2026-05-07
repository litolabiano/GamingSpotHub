<?php
/**
 * Admin Tournaments Section
 */
?>
<!-- ── SECTION: TOURNAMENTS ─────────────────────────────────────────────────── -->
<div class="page" id="tournaments">
<div class="page-header">
    <div class="page-title-group">
        <h2 class="page-title"><i class="fas fa-trophy" style="color:#f1a83c;margin-right:10px;"></i>Tournaments</h2>
        <p class="page-subtitle">Create tournaments and manage participant registrations</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('createTournament')">
        <i class="fas fa-plus"></i> New Tournament
    </button>
</div>

<?php
// Fetch all tournaments with participant counts
$tourStmt = $conn->query("
    SELECT t.*,
           COUNT(tp.participant_id) AS registered_count
    FROM tournaments t
    LEFT JOIN tournament_participants tp ON t.tournament_id = tp.tournament_id
    GROUP BY t.tournament_id
    ORDER BY t.start_date DESC
");
$allTournaments = $tourStmt ? $tourStmt->fetch_all(MYSQLI_ASSOC) : [];

$totalTournaments   = count($allTournaments);
$openTournaments    = count(array_filter($allTournaments, fn($t) => $t['status'] === 'scheduled'));
$ongoingTournaments = count(array_filter($allTournaments, fn($t) => $t['status'] === 'ongoing'));
$totalParticipants  = array_sum(array_column($allTournaments, 'registered_count'));
?>

<!-- Stats Row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= $totalTournaments ?></div>
                <div class="stat-label">Total Tournaments</div>
            </div>
            <div class="stat-icon" style="background:rgba(241,168,60,.15);color:#f1a83c;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                <i class="fas fa-trophy"></i>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= $openTournaments ?></div>
                <div class="stat-label">Open for Registration</div>
            </div>
            <div class="stat-icon" style="background:rgba(32,200,161,.15);color:#20c8a1;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                <i class="fas fa-door-open"></i>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= $ongoingTournaments ?></div>
                <div class="stat-label">Ongoing</div>
            </div>
            <div class="stat-icon" style="background:rgba(95,133,218,.15);color:#5f85da;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                <i class="fas fa-play-circle"></i>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= $totalParticipants ?></div>
                <div class="stat-label">Total Registered</div>
            </div>
            <div class="stat-icon" style="background:rgba(179,123,236,.15);color:#b37bec;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tournament List -->
<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:10px;">
        <h3 class="card-title"><i class="fas fa-list"></i> All Tournaments</h3>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <div class="asb-search" style="max-width:220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="asb-input" id="tournSearch" placeholder="Search tournament, game…" autocomplete="off">
                <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
            </div>
            <select class="asb-select" id="tournStatusFilter" title="Filter by status">
                <option value="">All Statuses</option>
                <option value="upcoming">Upcoming</option>
                <option value="scheduled">Open Reg.</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <span class="asb-count" id="tournCount"></span>
        </div>
    </div>
    <?php if (empty($allTournaments)): ?>
    <div class="empty-state">
        <i class="fas fa-trophy"></i>
        <p>No tournaments yet. Click <strong>New Tournament</strong> to create one.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="data-table" id="tournTable">
        <thead>
            <tr>
                <th>Tournament</th>
                <th>Console</th>
                <th>Date</th>
                <th>Fee</th>
                <th>Prize Pool</th>
                <th>Players</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allTournaments as $t):
            $statusColors = [
                'upcoming'  => ['#f1a83c', 'rgba(241,168,60,.15)'],
                'scheduled' => ['#20c8a1', 'rgba(32,200,161,.15)'],
                'ongoing'   => ['#5f85da', 'rgba(95,133,218,.15)'],
                'completed' => ['#888',    'rgba(150,150,150,.15)'],
                'cancelled' => ['#fb566b', 'rgba(251,86,107,.15)'],
            ];
            [$sc, $sbg] = $statusColors[$t['status']] ?? ['#888','rgba(150,150,150,.15)'];
            $isFull = (int)$t['registered_count'] >= (int)$t['max_participants'];
        ?>
        <tr>
            <td>
                <div style="font-weight:700;color:#fff;"><?= htmlspecialchars($t['tournament_name']) ?></div>
                <?php if (!empty($t['game_name'])): ?>
                <div style="font-size:11px;color:#5f85da;margin-top:2px;"><i class="fas fa-gamepad" style="margin-right:3px;"></i><?= htmlspecialchars($t['game_name']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <span style="background:rgba(95,133,218,.15);color:#5f85da;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                    <?= htmlspecialchars($t['console_type']) ?>
                </span>
            </td>
            <td style="font-size:13px;">
                <div><?= date('M d, Y', strtotime($t['start_date'])) ?></div>
                <div style="color:#888;font-size:11px;"><?= date('h:i A', strtotime($t['start_date'])) ?></div>
            </td>
            <td style="color:#f1e1aa;font-weight:600;">₱<?= number_format($t['entry_fee'], 0) ?></td>
            <td style="color:#20c8a1;font-weight:600;">₱<?= number_format($t['prize_pool'], 0) ?></td>
            <td>
                <div style="font-size:13px;">
                    <span style="color:#fff;font-weight:700;"><?= $t['registered_count'] ?></span>
                    <span style="color:#888;"> / <?= $t['max_participants'] ?></span>
                </div>
                <?php if ($isFull): ?>
                <div style="font-size:10px;color:#fb566b;font-weight:700;margin-top:2px;">FULL</div>
                <?php endif; ?>
            </td>
            <td>
                <span style="background:<?= $sbg ?>;color:<?= $sc ?>;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;">
                    <?= ucfirst($t['status']) ?>
                </span>
            </td>
            <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
                    <!-- View Registrants -->
                    <button class="btn btn-sm btn-secondary"
                        onclick="viewParticipants(<?= $t['tournament_id'] ?>, '<?= htmlspecialchars(addslashes($t['tournament_name'])) ?>')"
                        title="View Registrants">
                        <i class="fas fa-users"></i>
                    </button>

                    <!-- Status switch buttons -->
                    <?php if ($t['status'] === 'upcoming'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_tournament_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="tournament_id" value="<?= $t['tournament_id'] ?>">
                        <input type="hidden" name="new_status" value="scheduled">
                        <button type="submit" class="btn btn-sm"
                            style="background:rgba(32,200,161,.15);color:#20c8a1;border:1px solid rgba(32,200,161,.3);"
                            title="Open Registration">
                            <i class="fas fa-door-open"></i> Open Reg.
                        </button>
                    </form>

                    <?php elseif ($t['status'] === 'scheduled'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_tournament_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="tournament_id" value="<?= $t['tournament_id'] ?>">
                        <input type="hidden" name="new_status" value="upcoming">
                        <button type="submit" class="btn btn-sm"
                            style="background:rgba(241,168,60,.12);color:#f1a83c;border:1px solid rgba(241,168,60,.3);"
                            title="Close Registration">
                            <i class="fas fa-lock"></i> Close Reg.
                        </button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_tournament_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="tournament_id" value="<?= $t['tournament_id'] ?>">
                        <input type="hidden" name="new_status" value="ongoing">
                        <button type="submit" class="btn btn-sm"
                            style="background:rgba(95,133,218,.15);color:#5f85da;border:1px solid rgba(95,133,218,.3);"
                            title="Start Tournament">
                            <i class="fas fa-play"></i> Start
                        </button>
                    </form>

                    <?php elseif ($t['status'] === 'ongoing'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_tournament_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="tournament_id" value="<?= $t['tournament_id'] ?>">
                        <input type="hidden" name="new_status" value="completed">
                        <button type="submit" class="btn btn-sm"
                            style="background:rgba(32,200,161,.12);color:#20c8a1;border:1px solid rgba(32,200,161,.3);">
                            <i class="fas fa-flag-checkered"></i> Complete
                        </button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_tournament_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="tournament_id" value="<?= $t['tournament_id'] ?>">
                        <input type="hidden" name="new_status" value="cancelled">
                        <button type="submit" class="btn btn-sm"
                            style="background:rgba(251,86,107,.12);color:#fb566b;border:1px solid rgba(251,86,107,.3);">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Add Participant -->
                    <?php if (in_array($t['status'], ['scheduled','ongoing']) && !$isFull): ?>
                    <button class="btn btn-sm"
                        style="background:rgba(179,123,236,.12);color:#b37bec;border:1px solid rgba(179,123,236,.3);"
                        onclick="openAddParticipant(<?= $t['tournament_id'] ?>, '<?= htmlspecialchars(addslashes($t['tournament_name'])) ?>')"
                        title="Add Participant">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="asb-no-results" id="tournSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No tournaments match your search.</div>
    <div id="tournPagination"></div>
    </div>
    <?php endif; ?>
</div>

<!-- Participants Panel (inline, toggleable) -->
<div id="participantsDrawer" style="display:none;margin-top:20px;">
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h3 class="card-title">
                <i class="fas fa-users"></i> Registrants:
                <span id="drawerTournamentName" style="color:#f1a83c;">—</span>
            </h3>
            <button class="btn btn-sm btn-secondary" onclick="closeDrawer()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <div id="drawerContent">
            <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>
        </div>
    </div>
</div>

</div><!-- /#tournaments -->

<!-- ── CREATE TOURNAMENT MODAL ──────────────────────────────────────────────── -->
<div class="modal" id="createTournamentModal">
    <div class="modal-content" style="max-width:580px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-trophy" style="color:#f1a83c;margin-right:8px;"></i> Create Tournament
            </h3>
            <button class="modal-close" onclick="closeModal('createTournament')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_tournament">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Tournament Name *</label>
                <input type="text" name="tournament_name" placeholder="e.g. Tekken 8 Grand Prix" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Game Name *</label>
                    <input type="text" name="game_name" placeholder="e.g. Tekken 8" required>
                </div>
                <div class="form-group">
                    <label>Console *</label>
                    <select name="console_type" required>
                        <option value="" disabled selected>Select Console</option>
                        <?php foreach ($consoleTypes as $ct): ?>
                            <option value="<?= htmlspecialchars($ct['type_name']) ?>"><?= htmlspecialchars($ct['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php
                $minTournDate  = (new DateTime('+7 days'))->format('Y-m-d');
                $minTournLabel = (new DateTime('+7 days'))->format('M d, Y');
            ?>
            <!-- Start Date & Time — split selects, no browser datetime-local picker -->
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date *
                        <span style="font-size:11px;color:#f1a83c;font-weight:600;margin-left:6px;">(earliest: <?= $minTournLabel ?>)</span>
                    </label>
                    <input type="date" id="tournStartDatePart" min="<?= $minTournDate ?>" required
                           onchange="syncTournDateTime('start')"
                           style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#f0f0f0;font-size:14px;box-sizing:border-box;">
                </div>
                <div class="form-group">
                    <label>Start Time *</label>
                    <select id="tournStartTimePart" onchange="syncTournDateTime('start')" required>
                        <option value="" disabled selected>— Select time —</option>
                        <?php for ($h = 8; $h <= 22; $h++): foreach (['00','30'] as $m): ?>
                            <?php $val = sprintf('%02d:%s',$h,$m); $disp = date('g:i A', strtotime("2000-01-01 $val")); ?>
                            <option value="<?= $val ?>"><?= $disp ?></option>
                        <?php endforeach; endfor; ?>
                    </select>
                </div>
            </div>
            <input type="hidden" name="start_date" id="tournStartDateHidden">

            <!-- End Date & Time -->
            <div class="form-row">
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" id="tournEndDatePart" min="<?= $minTournDate ?>" required
                           onchange="syncTournDateTime('end')"
                           style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#f0f0f0;font-size:14px;box-sizing:border-box;">
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <select id="tournEndTimePart" onchange="syncTournDateTime('end')" required>
                        <option value="" disabled selected>— Select time —</option>
                        <?php for ($h = 8; $h <= 23; $h++): foreach (['00','30'] as $m): ?>
                            <?php $val = sprintf('%02d:%s',$h,$m); $disp = date('g:i A', strtotime("2000-01-01 $val")); ?>
                            <option value="<?= $val ?>"><?= $disp ?></option>
                        <?php endforeach; endfor; ?>
                    </select>
                </div>
            </div>
            <input type="hidden" name="end_date" id="tournEndDateHidden">

            <div class="form-row">
                <div class="form-group">
                    <label>Entry Fee (₱)</label>
                    <select name="entry_fee">
                        <option value="0">Free</option>
                        <option value="50">₱50</option>
                        <option value="100">₱100</option>
                        <option value="150">₱150</option>
                        <option value="200">₱200</option>
                        <option value="250" selected>₱250</option>
                        <option value="300">₱300</option>
                        <option value="500">₱500</option>
                        <option value="1000">₱1,000</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prize Pool (₱)</label>
                    <select name="prize_pool">
                        <option value="0" selected>None</option>
                        <option value="500">₱500</option>
                        <option value="1000">₱1,000</option>
                        <option value="2000">₱2,000</option>
                        <option value="3000">₱3,000</option>
                        <option value="5000">₱5,000</option>
                        <option value="10000">₱10,000</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Max Participants</label>
                    <select name="max_participants">
                        <option value="4">4 players</option>
                        <option value="8">8 players</option>
                        <option value="16" selected>16 players</option>
                        <option value="32">32 players</option>
                        <option value="64">64 players</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Announcement / Description</label>
                <textarea name="announcement" rows="3" placeholder="Optional details…"></textarea>
            </div>
            <div style="background:rgba(241,168,60,.07);border:1px solid rgba(241,168,60,.2);border-radius:8px;padding:10px 14px;font-size:12px;color:rgba(255,255,255,.6);margin-bottom:16px;">
                <i class="fas fa-info-circle" style="color:#f1a83c;margin-right:6px;"></i>
                New tournaments start as <strong style="color:#f1a83c;">Upcoming</strong>.
                Switch to <strong style="color:#20c8a1;">Scheduled</strong> to open public registration.
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createTournament')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Tournament</button>
            </div>
        </form>
    </div>
</div>

<!-- ── ADD PARTICIPANT MODAL ──────────────────────────────────────────────────── -->
<div class="modal" id="addParticipantModal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-user-plus" style="color:#b37bec;margin-right:8px;"></i> Add Participant
            </h3>
            <button class="modal-close" onclick="closeModal('addParticipant')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="admin_register_participant">
            <?= csrfField() ?>
            <input type="hidden" name="tournament_id" id="addParticipantTournamentId">
            <div style="margin-bottom:14px;font-size:13px;color:#888;">
                Tournament: <strong id="addParticipantTournamentName" style="color:#fff;"></strong>
            </div>

            <!-- Mode toggle -->
            <div style="display:flex;gap:8px;margin-bottom:16px;">
                <button type="button" id="modeRegistered"
                    onclick="setParticipantMode('registered')"
                    style="flex:1;padding:8px;border-radius:8px;border:1px solid rgba(95,133,218,.5);background:rgba(95,133,218,.15);color:#5f85da;font-weight:700;font-size:12px;cursor:pointer;">
                    <i class="fas fa-user"></i> Registered Customer
                </button>
                <button type="button" id="modeWalkin"
                    onclick="setParticipantMode('walkin')"
                    style="flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:transparent;color:#888;font-weight:700;font-size:12px;cursor:pointer;">
                    <i class="fas fa-walking"></i> Walk-in
                </button>
            </div>
            <input type="hidden" name="participant_mode" id="participantModeInput" value="registered">

            <!-- Registered customer fields -->
            <div id="registeredFields">
                <div class="form-group">
                    <label>Select Customer *</label>
                    <select name="user_id" id="customerSelect">
                        <option value="" disabled selected>— Pick a customer —</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Walk-in fields -->
            <div id="walkinFields" style="display:none;">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="walkin_name" id="walkinNameInput" placeholder="e.g. Juan Dela Cruz">
                </div>
            </div>

            <!-- Shared fields -->
            <div class="form-group">
                <label>IGN (In-Game Name)</label>
                <input type="text" name="ign" placeholder="e.g. DarkFist99">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" placeholder="09XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Optional remark…">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addParticipant')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
            </div>
        </form>
    </div>
</div>

<script>
const _CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>';

function syncTournDateTime(which) {
    const prefix   = which === 'start' ? 'Start' : 'End';
    const datePart = document.getElementById('tourn' + prefix + 'DatePart');
    const timePart = document.getElementById('tourn' + prefix + 'TimePart');
    const hidden   = document.getElementById('tourn' + prefix + 'DateHidden');
    if (!datePart || !timePart || !hidden) return;
    if (datePart.value && timePart.value) {
        hidden.value = datePart.value + 'T' + timePart.value;
    }
    if (which === 'start' && datePart.value) {
        const endDate = document.getElementById('tournEndDatePart');
        if (endDate) {
            endDate.min = datePart.value;
            if (endDate.value && endDate.value < datePart.value) endDate.value = '';
        }
    }
}

function viewParticipants(tournamentId, tournamentName) {
    document.getElementById('drawerTournamentName').textContent = tournamentName;
    document.getElementById('drawerContent').innerHTML =
        '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>';
    const drawer = document.getElementById('participantsDrawer');
    drawer.style.display = 'block';
    drawer.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch('ajax/get_tournament_participants.php?tournament_id=' + tournamentId)
        .then(r => r.json())
        .then(data => {
            if (!data.participants || data.participants.length === 0) {
                document.getElementById('drawerContent').innerHTML =
                    '<div class="empty-state"><i class="fas fa-users"></i><p>No participants registered yet.</p></div>';
                return;
            }
            let html = '<div style="overflow-x:auto;"><table class="table"><thead><tr>' +
                '<th>#</th><th>Name</th><th>IGN</th><th>Contact</th><th>Registered</th><th>Payment</th><th>Proof</th><th>Action</th>' +
                '</tr></thead><tbody>';
            data.participants.forEach((p, i) => {
                const isPaid    = p.payment_status === 'paid';
                const fid       = 'rmPart_' + p.participant_id;
                const dispName  = p.walkin_name ? `${escHtml(p.full_name)} <span style="font-size:10px;color:#f1a83c;background:rgba(241,168,60,.12);padding:2px 6px;border-radius:10px;margin-left:4px;">Walk-in: ${escHtml(p.walkin_name)}</span>` : escHtml(p.full_name);
                const proofHtml = p.paymongo_source_id
                    ? `<span style="color:#20c8a1;font-size:11px;font-weight:700;"><i class="fas fa-check-circle"></i> GCash Paid</span><br><span style="font-size:10px;color:#444;">${escHtml(p.paymongo_source_id)}</span>`
                    : '<span style="color:#555;font-size:11px;">—</span>';
                html += `<tr>
                    <td style="color:#888">${i + 1}</td>
                    <td style="font-weight:600;color:#fff">${dispName}<br><span style="font-size:11px;color:#555;">${escHtml(p.email)}</span></td>
                    <td style="color:#b37bec;font-size:12px;">${p.ign ? escHtml(p.ign) : '<span style="color:#555;">—</span>'}</td>
                    <td style="color:#888;font-size:12px;">${p.contact_number ? escHtml(p.contact_number) : '<span style="color:#555;">—</span>'}</td>
                    <td style="color:#888;font-size:12px;">${p.registration_date}</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="${escHtml(_CSRF)}">
                            <input type="hidden" name="action" value="update_participant_payment">
                            <input type="hidden" name="participant_id" value="${p.participant_id}">
                            <input type="hidden" name="payment_status" value="${isPaid ? 'pending' : 'paid'}">
                            <button type="submit"
                                style="background:${isPaid ? 'rgba(32,200,161,.15)' : 'rgba(241,168,60,.15)'};
                                       color:${isPaid ? '#20c8a1' : '#f1a83c'};
                                       border:1px solid ${isPaid ? 'rgba(32,200,161,.3)' : 'rgba(241,168,60,.3)'};
                                       padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;">
                                ${isPaid ? '✓ Paid' : '⏳ Pending'}
                            </button>
                        </form>
                    </td>
                    <td>${proofHtml}</td>
                    <td>
                        <form method="POST" style="display:inline;" id="${fid}">
                            <input type="hidden" name="csrf_token" value="${escHtml(_CSRF)}">
                            <input type="hidden" name="action" value="remove_participant">
                            <input type="hidden" name="participant_id" value="${p.participant_id}">
                            <button type="button"
                                onclick="gspotConfirm('Remove this participant?', function(){ document.getElementById('${fid}').submit(); }, {danger:true, yesLabel:'Remove'})"
                                style="background:rgba(251,86,107,.12);color:#fb566b;
                                       border:1px solid rgba(251,86,107,.3);
                                       padding:3px 10px;border-radius:8px;font-size:11px;cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            document.getElementById('drawerContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('drawerContent').innerHTML =
                '<div class="empty-state"><i class="fas fa-exclamation-triangle" style="color:#fb566b;"></i><p>Failed to load participants.</p></div>';
        });
}

function closeDrawer() {
    document.getElementById('participantsDrawer').style.display = 'none';
}

function setParticipantMode(mode) {
    document.getElementById('participantModeInput').value = mode;
    const isWalkin = mode === 'walkin';
    document.getElementById('registeredFields').style.display = isWalkin ? 'none' : '';
    document.getElementById('walkinFields').style.display     = isWalkin ? '' : 'none';
    // Required attributes
    document.getElementById('customerSelect').required   = !isWalkin;
    document.getElementById('walkinNameInput').required  = isWalkin;
    // Button styles
    const btnReg    = document.getElementById('modeRegistered');
    const btnWalkin = document.getElementById('modeWalkin');
    btnReg.style.background    = isWalkin ? 'transparent' : 'rgba(95,133,218,.15)';
    btnReg.style.borderColor   = isWalkin ? 'rgba(255,255,255,.12)' : 'rgba(95,133,218,.5)';
    btnReg.style.color         = isWalkin ? '#888' : '#5f85da';
    btnWalkin.style.background = isWalkin ? 'rgba(241,168,60,.15)' : 'transparent';
    btnWalkin.style.borderColor= isWalkin ? 'rgba(241,168,60,.5)' : 'rgba(255,255,255,.12)';
    btnWalkin.style.color      = isWalkin ? '#f1a83c' : '#888';
}

function openAddParticipant(id, name) {
    document.getElementById('addParticipantTournamentId').value = id;
    document.getElementById('addParticipantTournamentName').textContent = name;
    setParticipantMode('registered'); // reset to default mode
    openModal('addParticipant');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

/* ── Tournaments live search + status filter + pagination ─────────────────── */
(function() {
    const tournSearch  = document.getElementById('tournSearch');
    const tournStatus  = document.getElementById('tournStatusFilter');
    const tournTable   = document.getElementById('tournTable');

    const pag = new AdminPaginator('tournTable', {
        pageSize:      10,
        pageSizes:     [10, 25, 50],
        paginationSel: '#tournPagination',
        noResultsSel:  '#tournSearch_noResults',
        countSel:      '#tournCount',
    });

    function filterTourn() {
        if (!tournTable) return;
        const q  = (tournSearch?.value || '').trim().toLowerCase();
        const st = (tournStatus?.value || '').toLowerCase();
        tournTable.querySelectorAll('tbody tr').forEach(row => {
            const hay = row.innerText.toLowerCase();
            const match = (!q || hay.includes(q)) && (!st || hay.includes(st));
            row.classList.toggle('asb-hidden', !match);
        });
        const cb = tournSearch?.parentElement?.querySelector('.asb-clear');
        if (cb) cb.style.display = q ? 'block' : 'none';
        pag.reset();
    }

    if (tournSearch) tournSearch.addEventListener('input', filterTourn);
    if (tournStatus) tournStatus.addEventListener('change', filterTourn);
    const tournClear = tournSearch?.parentElement?.querySelector('.asb-clear');
    if (tournClear) tournClear.addEventListener('click', () => { tournSearch.value = ''; filterTourn(); tournSearch.focus(); });

    pag.apply();
})();
</script>

