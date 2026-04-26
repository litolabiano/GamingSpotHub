<?php
/**
 * Admin Games Library Section
 * Included by admin.php when page=games.
 */
?>
<!-- ════ GAMES LIBRARY ═══════════════════════════════════════════════════════ -->
<div class="page" id="games">
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="fas fa-gamepad" style="color:#5f85da;"></i> Games Library</h2>
        <p class="page-subtitle">Manage the games available across all consoles</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addGame')">
        <i class="fas fa-plus"></i> Add Game
    </button>
</div>

<?php
// Fetch all games
$gStmt = $conn->query("SELECT * FROM games ORDER BY console_type, game_name");
$allGames = $gStmt ? $gStmt->fetch_all(MYSQLI_ASSOC) : [];

$byConsole = [];
foreach ($allGames as $g) {
    $byConsole[$g['console_type']][] = $g;
}
$activeCount   = count(array_filter($allGames, fn($g) => $g['is_active']));
$inactiveCount = count($allGames) - $activeCount;
?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= count($allGames) ?></div><div class="stat-label">Total Games</div></div>
            <div class="stat-icon" style="background:rgba(95,133,218,.15);color:#5f85da;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;"><i class="fas fa-gamepad"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= $activeCount ?></div><div class="stat-label">Active</div></div>
            <div class="stat-icon" style="background:rgba(32,200,161,.15);color:#20c8a1;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= count($byConsole) ?></div><div class="stat-label">Platforms</div></div>
            <div class="stat-icon" style="background:rgba(179,123,236,.15);color:#b37bec;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;"><i class="fas fa-tv"></i></div>
        </div>
    </div>
</div>

<?php if (empty($allGames)): ?>
<div class="empty-state"><i class="fas fa-gamepad"></i><p>No games yet. Click <strong>Add Game</strong> to get started.</p></div>
<?php else: ?>

<!-- Search -->
<div style="margin-bottom:16px;">
    <input type="text" id="gameSearchInput" placeholder="🔍 Search games..." class="form-control"
           oninput="filterGames(this.value)"
           style="max-width:340px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:9px 14px;">
</div>

<!-- Platform-grouped cards -->
<?php foreach ($byConsole as $platform => $games): ?>
<div class="card" style="margin-bottom:20px;" data-platform-group="<?= htmlspecialchars($platform) ?>">
    <div class="card-header">
        <h3 class="card-title">
            <span style="background:rgba(95,133,218,.15);color:#5f85da;padding:2px 10px;border-radius:20px;font-size:13px;margin-right:8px;"><?= htmlspecialchars($platform) ?></span>
            <span style="font-size:13px;color:#888;font-weight:400;"><?= count($games) ?> game<?= count($games) !== 1 ? 's' : '' ?></span>
        </h3>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" id="gameTable_<?= preg_replace('/\W+/', '_', $platform) ?>">
        <thead>
            <tr>
                <th>#</th>
                <th>Game Name</th>
                <th>Genre</th>
                <th>Status</th>
                <th>Added</th>
                <th class="no-sort">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($games as $g): ?>
        <tr class="game-row" data-name="<?= htmlspecialchars(strtolower($g['game_name'])) ?>">
            <td>#<?= $g['game_id'] ?></td>
            <td style="font-weight:700;color:#fff;"><?= htmlspecialchars($g['game_name']) ?></td>
            <td>
                <span style="background:rgba(179,123,236,.12);color:#b37bec;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;">
                    <?= htmlspecialchars($g['genre']) ?>
                </span>
            </td>
            <td>
                <?php if ($g['is_active']): ?>
                <span style="background:rgba(32,200,161,.12);color:#20c8a1;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;">Active</span>
                <?php else: ?>
                <span style="background:rgba(251,86,107,.12);color:#fb566b;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;">Hidden</span>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#888;"><?= date('M d, Y', strtotime($g['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button class="btn btn-sm" title="Edit"
                        style="background:rgba(95,133,218,.15);border:1px solid rgba(95,133,218,.4);color:#8aa4e8;"
                        onclick="openEditGameModal(<?= $g['game_id'] ?>,'<?= htmlspecialchars(addslashes($g['game_name'])) ?>','<?= htmlspecialchars($g['genre']) ?>','<?= htmlspecialchars($g['console_type']) ?>',<?= $g['is_active'] ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="margin:0;" onsubmit="return confirm('Toggle visibility for <?= htmlspecialchars(addslashes($g['game_name'])) ?>?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_game">
                        <input type="hidden" name="game_id" value="<?= $g['game_id'] ?>">
                        <button type="submit" class="btn btn-sm"
                            style="background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.35);color:#f1a83c;">
                            <i class="fas fa-eye<?= $g['is_active'] ? '-slash' : '' ?>"></i> <?= $g['is_active'] ? 'Hide' : 'Show' ?>
                        </button>
                    </form>
                    <form method="POST" style="margin:0;" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($g['game_name'])) ?>? This cannot be undone.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_game">
                        <input type="hidden" name="game_id" value="<?= $g['game_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- ── Add Game Modal ── -->
<div id="addGameModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('addGameModal')">
<div class="modal-container" style="max-width:480px;">
    <div class="modal-header">
        <h3 class="modal-title"><i class="fas fa-plus-circle" style="color:#5f85da;margin-right:8px;"></i>Add Game</h3>
        <button class="modal-close" onclick="closeModal('addGameModal')">×</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_game">
        <div class="modal-body" style="display:flex;flex-direction:column;gap:16px;">
            <div class="form-group">
                <label class="form-label">Game Name *</label>
                <input type="text" name="game_name" class="form-control" placeholder="e.g. Tekken 8" required maxlength="150">
            </div>
            <div class="form-group">
                <label class="form-label">Console / Platform *</label>
                <select name="console_type" class="form-control" required>
                    <option value="PS5">PS5</option>
                    <option value="Xbox Series X">Xbox Series X</option>
                    <option value="PS4">PS4</option>
                    <option value="PC">PC</option>
                    <option value="Multi" selected>Multi-platform</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Genre *</label>
                <select name="genre" class="form-control" required>
                    <option>Action</option>
                    <option>Fighting</option>
                    <option>Shooter</option>
                    <option>Sports</option>
                    <option>Racing</option>
                    <option>RPG</option>
                    <option>Strategy</option>
                    <option>Open World</option>
                    <option>Sandbox</option>
                    <option>Horror</option>
                    <option>Simulation</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Short description of the game..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addGameModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Game</button>
        </div>
    </form>
</div>
</div>

<!-- ── Edit Game Modal ── -->
<div id="editGameModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal('editGameModal')">
<div class="modal-container" style="max-width:480px;">
    <div class="modal-header">
        <h3 class="modal-title"><i class="fas fa-edit" style="color:#5f85da;margin-right:8px;"></i>Edit Game</h3>
        <button class="modal-close" onclick="closeModal('editGameModal')">×</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit_game">
        <input type="hidden" name="game_id" id="editGameId">
        <div class="modal-body" style="display:flex;flex-direction:column;gap:16px;">
            <div class="form-group">
                <label class="form-label">Game Name *</label>
                <input type="text" name="game_name" id="editGameName" class="form-control" required maxlength="150">
            </div>
            <div class="form-group">
                <label class="form-label">Console / Platform *</label>
                <select name="console_type" id="editGameConsole" class="form-control" required>
                    <option value="PS5">PS5</option>
                    <option value="Xbox Series X">Xbox Series X</option>
                    <option value="PS4">PS4</option>
                    <option value="PC">PC</option>
                    <option value="Multi">Multi-platform</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Genre *</label>
                <select name="genre" id="editGameGenre" class="form-control" required>
                    <option>Action</option><option>Fighting</option><option>Shooter</option>
                    <option>Sports</option><option>Racing</option><option>RPG</option>
                    <option>Strategy</option><option>Open World</option><option>Sandbox</option>
                    <option>Horror</option><option>Simulation</option><option>Other</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editGameModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
</div>
</div>

<script>
function openEditGameModal(id, name, genre, console_type, isActive) {
    document.getElementById('editGameId').value      = id;
    document.getElementById('editGameName').value    = name;
    document.getElementById('editGameGenre').value   = genre;
    document.getElementById('editGameConsole').value = console_type;
    openModal('editGameModal');
}
function filterGames(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.game-row').forEach(row => {
        row.style.display = (!q || row.dataset.name.includes(q)) ? '' : 'none';
    });
    // Hide platform groups that have no visible rows
    document.querySelectorAll('[data-platform-group]').forEach(card => {
        const vis = card.querySelectorAll('.game-row:not([style*="display: none"])').length;
        card.style.display = vis ? '' : 'none';
    });
}
</script>
</div><!-- /page#games -->
