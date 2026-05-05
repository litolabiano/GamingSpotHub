<!-- ════ CONSOLES ══════════════════════════════════════════════════════════ -->
<div class="page" id="consoles">

    <!-- Active Consoles Section -->
    <div id="activeConsolesSection">
        <!-- Page Header -->
        <div class="page-header" style="align-items:center;flex-wrap:wrap;gap:15px;">
            <div class="page-title-group" style="flex:1;min-width:300px;">
                <h2 class="page-title"><i class="fas fa-desktop" style="color:#20c8a1;margin-right:10px;"></i>Console Management</h2>
                <p class="page-subtitle">Manage console availability and maintenance status</p>
            </div>
            
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button class="btn btn-primary" onclick="openModal('addConsoleModal')">
                    <i class="fas fa-plus"></i> Add Console
                </button>
                <button class="btn btn-secondary" onclick="toggleArchiveSection(true)">
                    <i class="fas fa-archive"></i> Archived Consoles (<?= count($archivedConsoles) ?>)
                </button>
            </div>
            
            <div style="width:100%;display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:5px;">
                <span style="font-size:13px;display:flex;align-items:center;gap:6px;">
                    <span class="status-dot available"></span><?= $availableCount ?> Available
                </span>
                <span style="font-size:13px;display:flex;align-items:center;gap:6px;">
                    <span class="status-dot in_use"></span><?= $inUseCount ?> In Use
                </span>
                <span style="font-size:13px;display:flex;align-items:center;gap:6px;">
                    <span class="status-dot maintenance"></span><?= $maintenanceCount ?> Maintenance
                </span>
            </div>
        </div>

        <div class="console-grid">
        <?php foreach ($allConsoles as $con): ?>
            <div class="console-card <?= $con['status'] ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <?php
                        $badgeClass = match($con['console_type']) { 'PS5' => 'ps5', 'PS4' => 'ps4', default => 'xbox' };
                        $icon = match($con['console_type']) { 'PS5', 'PS4' => 'playstation', default => 'xbox' };
                    ?>
                    <span class="console-type-badge <?= $badgeClass ?>">
                        <i class="fab fa-<?= $icon ?>"></i> <?= $con['console_type'] ?>
                    </span>
                    <span class="badge <?= $con['status'] ?>"><?= ucfirst(str_replace('_',' ',$con['status'])) ?></span>
                </div>
                <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
                <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
                <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
                
                <div class="console-actions" style="margin-top:15px;display:flex;flex-wrap:wrap;gap:8px;">
                    <?php if ($con['status'] !== 'available'): ?>
                    <form method="POST" style="flex:1;min-width:90px;">
                        <input type="hidden" name="action" value="update_console_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <input type="hidden" name="status" value="available">
                        <button type="submit" class="btn btn-success btn-sm" style="width:100%;" title="Set as Available">
                            <i class="fas fa-check"></i> Available
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($con['status'] !== 'maintenance'): ?>
                    <form method="POST" style="flex:1;min-width:90px;">
                        <input type="hidden" name="action" value="update_console_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <input type="hidden" name="status" value="maintenance">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.3);color:#fb566b;" title="Set to Maintenance">
                            <i class="fas fa-wrench"></i> Maintenance
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="flex:1;min-width:90px;" onsubmit="return confirm('Are you sure you want to archive this console? It will be removed from active reservations.')">
                        <input type="hidden" name="action" value="update_console_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <input type="hidden" name="status" value="archived">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#ccc;" title="Archive Console">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($allConsoles)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;background:rgba(255,255,255,.02);border-radius:12px;">No active consoles found.</div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Archived Consoles Section -->
    <div id="archivedConsolesSection" style="display:none;">
        <div class="page-header" style="align-items:center;">
            <div class="page-title-group" style="flex:1;">
                <h2 class="page-title"><i class="fas fa-archive" style="color:#fb566b;margin-right:10px;"></i>Archived Consoles</h2>
                <p class="page-subtitle">These consoles are hidden from normal operations.</p>
            </div>
            
            <button class="btn btn-secondary" onclick="toggleArchiveSection(false)">
                <i class="fas fa-arrow-left"></i> Back to Active
            </button>
        </div>
        
        <div class="console-grid">
        <?php foreach ($archivedConsoles as $con): ?>
            <div class="console-card archived" style="opacity:0.8;border-color:rgba(251,86,107,.3);background:rgba(251,86,107,.05);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <?php
                        $badgeClass = match($con['console_type']) { 'PS5' => 'ps5', 'PS4' => 'ps4', default => 'xbox' };
                        $icon = match($con['console_type']) { 'PS5', 'PS4' => 'playstation', default => 'xbox' };
                    ?>
                    <span class="console-type-badge <?= $badgeClass ?>">
                        <i class="fab fa-<?= $icon ?>"></i> <?= $con['console_type'] ?>
                    </span>
                    <span class="badge gray">Archived</span>
                </div>
                <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
                <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
                <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
                
                <div class="console-actions" style="margin-top:15px;display:flex;gap:8px;">
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="action" value="update_console_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <input type="hidden" name="status" value="available">
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;" title="Restore Console">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                    </form>
                    
                    <form method="POST" style="flex:1;" onsubmit="return confirm('WARNING: Permanently delete this console? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete_console">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" style="width:100%;" title="Permanently Delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($archivedConsoles)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;background:rgba(255,255,255,.02);border-radius:12px;">No archived consoles found.</div>
        <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- Add Console Modal -->
<div class="modal" id="addConsoleModal">
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus" style="color:#20c8a1;"></i> Add New Console</h3>
            <span class="modal-close" onclick="closeModal('addConsoleModal')"><i class="fas fa-times"></i></span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_console">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Console Name / Description</label>
                    <input type="text" name="console_name" class="form-control" required placeholder="e.g. VIP Console">
                </div>
                <div class="form-group">
                    <label>Console Type</label>
                    <select name="console_type" class="form-control" required>
                        <option value="" disabled selected>Select Type</option>
                        <option value="PS5">PlayStation 5</option>
                        <option value="Xbox Series X">Xbox Series X</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit Number <span style="color:#888;font-size:11px;">(Must be unique)</span></label>
                    <input type="text" name="unit_number" class="form-control" required placeholder="e.g. PS5-01">
                </div>
                <div class="form-group">
                    <label>Hourly Rate (₱)</label>
                    <input type="number" name="hourly_rate" class="form-control" required min="0" step="0.01" value="100.00">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addConsoleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Console</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleArchiveSection(showArchive) {
    document.getElementById('activeConsolesSection').style.display = showArchive ? 'none' : 'block';
    document.getElementById('archivedConsolesSection').style.display = showArchive ? 'block' : 'none';
}
</script>
