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
                <button class="btn btn-primary" onclick="openModal('addConsole')">
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
                <?php
                    $xboxControllerCount = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'Xbox Controller' && $c['status'] === 'available'));
                    $xboxControllerTotal = count(array_filter($allConsoles, fn($c) => $c['console_type'] === 'Xbox Controller'));
                ?>
                <span style="font-size:13px;display:flex;align-items:center;gap:6px; margin-left: auto; background:rgba(32,200,161,.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(32,200,161,.3); color: #20c8a1;">
                    <i class="fa-solid fa-gamepad"></i>
                    <?= $xboxControllerCount ?> / <?= $xboxControllerTotal ?> Xbox Controllers Available
                </span>
            </div>
        </div>

        <!-- Live search bar for consoles -->
        <div class="asb-wrap" style="margin:0 0 16px;">
            <div class="asb-search" style="max-width:260px;">
                <i class="fas fa-search"></i>
                <input type="text" class="asb-input" id="consoleSearch" placeholder="Search unit, name, type…" autocomplete="off">
                <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
            </div>
            <select class="asb-select" id="consoleStatusFilter" title="Filter by status">
                <option value="">All Statuses</option>
                <option value="available">Available</option>
                <option value="in_use">In Use</option>
                <option value="maintenance">Maintenance</option>
            </select>
            <span class="asb-count" id="consoleCount"></span>
        </div>

        <div class="console-grid" id="consoleGrid">
        <?php foreach ($allConsoles as $con): ?>
            <div class="console-card <?= $con['status'] ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <?php
                        $badgeClass = match($con['console_type']) { 'PS5' => 'ps5', 'PS4' => 'ps4', default => 'xbox' };
                        $icon = match($con['console_type']) { 'PS5', 'PS4' => 'playstation', 'Xbox Controller' => 'gamepad', default => 'xbox' };
                    ?>
                    <span class="console-type-badge <?= $badgeClass ?>">
                        <i class="fa-solid fa-<?= $icon ?>"></i> <?= $con['console_type'] ?>
                    </span>
                    <span class="badge <?= $con['status'] ?>"><?= ucfirst(str_replace('_',' ',$con['status'])) ?></span>
                </div>
                <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
                <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
                <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
                
                <div class="console-actions" style="margin-top:15px;display:flex;flex-wrap:wrap;gap:8px;">
            <!-- Edit button -->
                    <button onclick="openEditConsoleModal(<?= $con['console_id'] ?>, '<?= htmlspecialchars($con['console_name'], ENT_QUOTES) ?>', '<?= $con['console_type'] ?>', '<?= htmlspecialchars($con['unit_number'], ENT_QUOTES) ?>', <?= $con['hourly_rate'] ?>)"
                            style="width:100%;background:rgba(95,133,218,.15);color:#8aa4e8;border:1px solid rgba(95,133,218,.3);
                                   padding:7px 12px;border-radius:7px;font-size:12px;cursor:pointer;font-family:inherit;
                                   display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:4px;">
                        <i class="fas fa-edit"></i> Edit Console
                    </button>
                    
                    <?php if ($con['status'] !== 'available'): ?>
                    <form method="POST" action="admin.php#consoles" style="flex:1;min-width:90px;">
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
                    <form method="POST" action="admin.php#consoles" style="flex:1;min-width:90px;">
                        <input type="hidden" name="action" value="update_console_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <input type="hidden" name="status" value="maintenance">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.3);color:#fb566b;" title="Set to Maintenance">
                            <i class="fas fa-wrench"></i> Maintenance
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" action="admin.php#consoles" style="flex:1;min-width:90px;" onsubmit="return confirm('Are you sure you want to archive this console? It will be removed from active reservations.')">
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
        </div><!-- /#consoleGrid -->
        <div id="consolePagination"></div>
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
                        $icon = match($con['console_type']) { 'PS5', 'PS4' => 'playstation', 'Xbox Controller' => 'gamepad', default => 'xbox' };
                    ?>
                    <span class="console-type-badge <?= $badgeClass ?>">
                        <i class="fa-solid fa-<?= $icon ?>"></i> <?= $con['console_type'] ?>
                    </span>
                    <span class="badge gray">Archived</span>
                </div>
                <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
                <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
                <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
                
                <div class="console-actions" style="margin-top:15px;display:flex;gap:8px;">
                    <form method="POST" action="admin.php#consoles" style="flex:1;">
                        <input type="hidden" name="action" value="update_console_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <input type="hidden" name="status" value="available">
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;" title="Restore Console">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                    </form>
                    
                    <form method="POST" action="admin.php#consoles" style="flex:1;" onsubmit="return confirm('WARNING: Permanently delete this console? This cannot be undone.')">
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

    <!-- ── Xbox Controller Inventory (INSIDE #consoles so it only shows here) ── -->
    <?php
        $allControllers = [];
        $_res = $conn->query("SELECT * FROM controllers WHERE status != 'archived' ORDER BY unit_number");
        if ($_res) $allControllers = $_res->fetch_all(MYSQLI_ASSOC);
        unset($_res);

        $ctrl_available   = count(array_filter($allControllers, fn($c) => $c['status'] === 'available'));
        $ctrl_in_use      = count(array_filter($allControllers, fn($c) => $c['status'] === 'in_use'));
        $ctrl_maintenance = count(array_filter($allControllers, fn($c) => $c['status'] === 'maintenance'));
        $ctrl_total       = count($allControllers);
    ?>
    <div style="margin-top:36px;">
        <div class="page-header" style="align-items:center;margin-bottom:20px;">
            <div class="page-title-group" style="flex:1;">
                <h2 class="page-title">
                    <i class="fa-solid fa-gamepad" style="color:#20c8a1;margin-right:10px;"></i>
                    Xbox Controller Inventory
                </h2>
                <p class="page-subtitle">Manage physical controllers available for rental</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addController')">
                <i class="fas fa-plus"></i> Add Controller
            </button>
        </div>

        <!-- Stats row -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="background:rgba(32,200,161,.1);border:1px solid rgba(32,200,161,.25);border-radius:12px;padding:14px 22px;text-align:center;min-width:90px;">
                <div style="font-size:26px;font-weight:800;color:#20c8a1;"><?= $ctrl_available ?></div>
                <div style="font-size:12px;color:#888;margin-top:2px;">Available</div>
            </div>
            <div style="background:rgba(95,133,218,.1);border:1px solid rgba(95,133,218,.25);border-radius:12px;padding:14px 22px;text-align:center;min-width:90px;">
                <div style="font-size:26px;font-weight:800;color:#8aa4e8;"><?= $ctrl_in_use ?></div>
                <div style="font-size:12px;color:#888;margin-top:2px;">In Use</div>
            </div>
            <div style="background:rgba(251,86,107,.1);border:1px solid rgba(251,86,107,.25);border-radius:12px;padding:14px 22px;text-align:center;min-width:90px;">
                <div style="font-size:26px;font-weight:800;color:#fb566b;"><?= $ctrl_maintenance ?></div>
                <div style="font-size:12px;color:#888;margin-top:2px;">Maintenance</div>
            </div>
            <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:14px 22px;text-align:center;min-width:90px;">
                <div style="font-size:26px;font-weight:800;color:#f0f0f0;"><?= $ctrl_total ?></div>
                <div style="font-size:12px;color:#888;margin-top:2px;">Total</div>
            </div>
        </div>

        <!-- Controllers table -->
        <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-md);overflow:hidden;">
            <table class="data-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:rgba(10,33,81,.6);">
                        <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Unit #</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Name</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Type</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Status</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Notes</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($allControllers)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#555;">
                            <i class="fa-solid fa-gamepad" style="font-size:28px;display:block;margin-bottom:10px;opacity:.3;"></i>
                            No controllers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allControllers as $ctrl): ?>
                    <tr style="border-top:1px solid rgba(255,255,255,.05);">
                        <td style="padding:12px 16px;font-weight:700;color:#f1a83c;"><?= htmlspecialchars($ctrl['unit_number']) ?></td>
                        <td style="padding:12px 16px;color:#f0f0f0;"><?= htmlspecialchars($ctrl['controller_name']) ?></td>
                        <td style="padding:12px 16px;">
                            <span class="console-type-badge xbox">
                                <i class="fa-solid fa-gamepad"></i> <?= htmlspecialchars($ctrl['controller_type']) ?>
                            </span>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="status-dot <?= $ctrl['status'] ?>"></span>
                            <?= ucfirst(str_replace('_',' ',$ctrl['status'])) ?>
                        </td>
                        <td style="padding:12px 16px;color:#888;font-size:13px;"><?= htmlspecialchars($ctrl['notes'] ?? '-') ?></td>
                        <td style="padding:12px 16px;">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <form method="POST" action="admin.php#consoles" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="action" value="update_controller_status">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                    <select name="status" onchange="this.form.submit()"
                                            style="background:rgba(10,33,81,.7);border:1px solid rgba(95,133,218,.25);
                                                   color:#f0f0f0;padding:5px 10px;border-radius:7px;font-size:13px;
                                                   font-family:inherit;cursor:pointer;">
                                        <option value="available"   <?= $ctrl['status']==='available'   ? 'selected':'' ?>>Available</option>
                                        <option value="in_use"      <?= $ctrl['status']==='in_use'      ? 'selected':'' ?>>In Use</option>
                                        <option value="maintenance" <?= $ctrl['status']==='maintenance' ? 'selected':'' ?>>Maintenance</option>
                                        <?php if ($user['role'] === 'owner'): ?>
                                        <option value="archived"    <?= $ctrl['status']==='archived'    ? 'selected':'' ?>>Archive</option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                                <?php if ($user['role'] === 'owner'): ?>
                                <form method="POST" action="admin.php#consoles"
                                      onsubmit="return confirm('Permanently delete this controller?')">
                                    <input type="hidden" name="action" value="delete_controller">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                    <button type="submit"
                                            style="background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.3);
                                                   color:#fb566b;padding:5px 12px;border-radius:7px;font-size:12px;
                                                   cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px;">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- ── END Xbox Controller Inventory ── -->

</div><!-- /#consoles — CLOSED HERE after controller inventory -->

<!-- Add Console Modal -->
<div class="modal" id="addConsoleModal">
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus" style="color:#20c8a1;"></i> Add New Console</h3>
            <span class="modal-close" onclick="closeModal('addConsole')"><i class="fas fa-times"></i></span>
        </div>
        <form method="POST" action="admin.php#consoles">
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
                        <option value="PS4">PlayStation 4</option>
                        <option value="Xbox Series X">Xbox Series X</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit Number <span style="color:#888;font-size:11px;">(Must be unique)</span></label>
                    <input type="text" name="unit_number" class="form-control" required maxlength="20" placeholder="e.g. PS5-01">
                </div>
                <div class="form-group">
                    <label>Hourly Rate (₱)</label>
                    <input type="number" name="hourly_rate" class="form-control" required min="0" step="0.01" value="100.00">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addConsole')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Console</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Console Modal -->
<div class="modal" id="editConsoleModal">
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:#8aa4e8;margin-right:8px;"></i>Edit Console</h3>
            <span class="modal-close" onclick="closeModal('editConsole')"><i class="fas fa-times"></i></span>
        </div>
        <form method="POST" action="admin.php#consoles">
            <input type="hidden" name="action" value="edit_console">
            <?= csrfField() ?>
            <input type="hidden" name="console_id" id="editConsoleId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Console Name / Description</label>
                    <input type="text" name="console_name" id="editConsoleName"
                           class="form-control" required placeholder="e.g. VIP Console">
                </div>
                <div class="form-group">
                    <label>Console Type</label>
                    <select name="console_type" id="editConsoleType" class="form-control" required>
                        <option value="PS5">PlayStation 5</option>
                        <option value="PS4">PlayStation 4</option>
                        <option value="Xbox Series X">Xbox Series X</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit Number <span style="color:#888;font-size:11px;">(Must be unique)</span></label>
                    <input type="text" name="unit_number" id="editConsoleUnit"
                           class="form-control" required maxlength="20">
                </div>
                <div class="form-group">
                    <label>Hourly Rate (₱)</label>
                    <input type="number" name="hourly_rate" id="editConsoleRate"
                           class="form-control" required min="0" step="0.01">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editConsole')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditConsoleModal(id, name, type, unit, rate) {
    document.getElementById('editConsoleId').value   = id;
    document.getElementById('editConsoleName').value = name;
    document.getElementById('editConsoleType').value = type;
    document.getElementById('editConsoleUnit').value = unit;
    document.getElementById('editConsoleRate').value = parseFloat(rate).toFixed(2);
    openModal('editConsole');
}
</script>

<script>
function toggleArchiveSection(showArchive) {
    document.getElementById('activeConsolesSection').style.display = showArchive ? 'none' : 'block';
    document.getElementById('archivedConsolesSection').style.display = showArchive ? 'block' : 'none';
}

/* ── Consoles live search + status filter + pagination ─────────────────────── */
(function() {
    const searchInput  = document.getElementById('consoleSearch');
    const statusFilter = document.getElementById('consoleStatusFilter');
    const grid         = document.getElementById('consoleGrid');

    const pag = new AdminCardPaginator('#consoleGrid', '.console-card', {
        pageSize:      12,
        pageSizes:     [12, 24, 48],
        paginationSel: '#consolePagination',
        countSel:      '#consoleCount',
    });

    function filterCards() {
        if (!grid) return;
        const q  = (searchInput?.value || '').trim().toLowerCase();
        const st = (statusFilter?.value || '').toLowerCase();
        grid.querySelectorAll('.console-card').forEach(card => {
            const hay        = card.innerText.toLowerCase();
            const cardStatus = ['available','in_use','maintenance'].find(s => card.classList.contains(s)) || '';
            const match = (!q || hay.includes(q)) && (!st || cardStatus === st);
            card.classList.toggle('asb-hidden', !match);
        });
        const cb = searchInput?.parentElement?.querySelector('.asb-clear');
        if (cb) cb.style.display = q ? 'block' : 'none';
        pag.reset();
    }

    if (searchInput)  searchInput.addEventListener('input', filterCards);
    if (statusFilter) statusFilter.addEventListener('change', filterCards);
    const clearBtn = searchInput?.parentElement?.querySelector('.asb-clear');
    if (clearBtn) clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterCards();
        searchInput.focus();
    });

    pag.apply();
})();
</script>