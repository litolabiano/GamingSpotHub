<?php
/**
 * Admin Tournaments Section
 */
?>
<!-- ── SECTION: TOURNAMENTS ─────────────────────────────────────────────────── -->
<div class="page" id="tournaments">
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="fas fa-trophy" style="color:#f1a83c;"></i> Tournaments</h2>
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
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> All Tournaments</h3>
    </div>
    <?php if (empty($allTournaments)): ?>
    <div class="empty-state">
        <i class="fas fa-trophy"></i>
        <p>No tournaments yet. Click <strong>New Tournament</strong> to create one.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="table">
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

<!-- ── CREATE TOURNAMENT MODAL (uses existing .modal system) ──────────────────── -->
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
            <div style="max-height:65vh;overflow-y:auto;padding-right:4px;">
                <div class="form-group">
                    <label>Tournament Name *</label>
                    <input type="text" name="tournament_name" class="form-control" placeholder="e.g. Tekken 8 Grand Prix" required>
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
                            <option value="PS5">PlayStation 5</option>
                            <option value="Xbox Series X">Xbox Series X</option>
                        </select>
                    </div>
                </div>
                <?php
                    $minTournDate = (new DateTime('+7 days'))->format('Y-m-d\TH:i');
                    $minTournLabel = (new DateTime('+7 days'))->format('M d, Y');
                ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date & Time *
                            <span style="font-size:11px;color:#f1a83c;font-weight:600;margin-left:6px;">(earliest: <?= $minTournLabel ?>)</span>
                        </label>
                        <input type="datetime-local" name="start_date" id="tournStartDate"
                               min="<?= $minTournDate ?>" required>
                    </div>
                    <div class="form-group">
                        <label>End Date & Time *</label>
                        <input type="datetime-local" name="end_date" id="tournEndDate"
                               min="<?= $minTournDate ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Entry Fee (₱)</label>
                        <input type="number" name="entry_fee" value="250" min="0" step="1">
                    </div>
                    <div class="form-group">
                        <label>Prize Pool (₱)</label>
                        <input type="number" name="prize_pool" value="0" min="0" step="1">
                    </div>
                    <div class="form-group">
                        <label>Max Participants</label>
                        <input type="number" name="max_participants" value="16" min="2" max="256">
                    </div>
                </div>
                <div class="form-group">
                    <label>Announcement / Description</label>
                    <textarea name="announcement" rows="3" placeholder="Optional details…"></textarea>
                </div>
                <div style="background:rgba(241,168,60,.07);border:1px solid rgba(241,168,60,.2);border-radius:8px;padding:10px 14px;font-size:12px;color:rgba(255,255,255,.6);">
                    <i class="fas fa-info-circle" style="color:#f1a83c;margin-right:6px;"></i>
                    New tournaments start as <strong style="color:#f1a83c;">Upcoming</strong>.
                    Switch to <strong style="color:#20c8a1;">Scheduled</strong> to open public registration.
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createTournament')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Tournament</button>
            </div>
        </form>
    </div>
</div>

<!-- ── ADD PARTICIPANT MODAL ──────────────────────────────────────────────────── -->
<div class="modal" id="addParticipantModal">
    <div class="modal-content" style="max-width:420px;">
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
            <div style="margin-bottom:12px;font-size:13px;color:#888;">
                Tournament: <strong id="addParticipantTournamentName" style="color:#fff;"></strong>
            </div>
            <div class="form-group">
                <label>Select Customer *</label>
                <select name="user_id" required>
                    <option value="" disabled selected>— Pick a customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Status</label>
                <select name="payment_status">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addParticipant')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── CSRF token (PHP-rendered) available to JS-generated forms ─────────────────
const _CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>';

// ── Participants Drawer ────────────────────────────────────────────────────────
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
                '<th>#</th><th>Name</th><th>Email</th><th>Registered</th><th>Payment</th><th>Action</th>' +
                '</tr></thead><tbody>';
            data.participants.forEach((p, i) => {
                const isPaid = p.payment_status === 'paid';
                const fid    = 'rmPart_' + p.participant_id;
                html += `<tr>
                    <td style="color:#888">${i + 1}</td>
                    <td style="font-weight:600;color:#fff">${escHtml(p.full_name)}</td>
                    <td style="color:#888;font-size:12px;">${escHtml(p.email)}</td>
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

function openAddParticipant(id, name) {
    document.getElementById('addParticipantTournamentId').value = id;
    document.getElementById('addParticipantTournamentName').textContent = name;
    openModal('addParticipant');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}



// ── End date must be >= start date (min is already set via PHP on page load) ──
document.addEventListener('DOMContentLoaded', function () {
    const startInput = document.getElementById('tournStartDate');
    const endInput   = document.getElementById('tournEndDate');
    if (!startInput || !endInput) return;

    startInput.addEventListener('change', function () {
        if (this.value) {
            // End date cannot be before start date
            endInput.min = this.value;
            if (endInput.value && endInput.value < this.value) {
                endInput.value = '';
            }
        }
    });
});
</script>
