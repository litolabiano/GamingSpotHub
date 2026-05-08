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
                <button class="btn-prim" onclick="openModal('addConsole')">
                    <i class="fas fa-plus"></i> Add Console
                </button>
                <button class="btn-sec" onclick="openModal('manageConsoleTypes')">
                    <i class="fas fa-tags"></i> Manage Types
                </button>
                <button class="btn-sec" onclick="toggleArchiveSection(true)">
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
                        $typeLower = strtolower($con['console_type']);
                        $badgeClass = (str_contains($typeLower, 'ps5')) ? 'ps5' : ((str_contains($typeLower, 'ps4')) ? 'ps4' : 'xbox');
                        $icon = (str_contains($typeLower, 'playstation') || str_contains($typeLower, 'ps')) ? 'playstation' : (str_contains($typeLower, 'controller') ? 'gamepad' : 'xbox');
                    ?>
                    <span class="console-type-badge <?= $badgeClass ?>">
                        <i class="fa-solid fa-<?= $icon ?>"></i> <?= $con['console_type'] ?>
                    </span>
                    <span class="badge <?= $con['status'] ?>"><?= ucfirst(str_replace('_',' ',$con['status'])) ?></span>
                </div>
                <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
                <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
                <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
                <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;">
                    <i class="fa-solid fa-gamepad" style="font-size:10px;margin-right:4px;"></i> <?= (int)($con['controller_count'] ?? 0) ?> Controller<?= (int)($con['controller_count'] ?? 0) != 1 ? 's' : '' ?>
                </div>

                <?php
                    $rental = $ctrlRentalByConsole[$con['console_id']] ?? null;
                ?>
                <?php if ($con['status'] === 'in_use'): ?>
                    <?php if ($rental): ?>
                        <?php
                            $rentedAgo = '';
                            if (!empty($rental['rented_since'])) {
                                $diff = (new DateTime())->diff(new DateTime($rental['rented_since']));
                                $h = $diff->h + ($diff->days * 24);
                                $m = $diff->i;
                                $rentedAgo = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                            }
                        ?>
                        <div class="ctrl-rental-badge ctrl-rental-active" title="Controller Rental Active">
                            <span class="ctrl-rental-icon"><i class="fa-solid fa-gamepad"></i></span>
                            <div class="ctrl-rental-info">
                                <div class="ctrl-rental-label">With Controller Rental</div>
                                <div class="ctrl-rental-details">
                                    <?= $rental['qty'] ?> controller<?= $rental['qty'] > 1 ? 's' : '' ?> &middot;
                                    ₱<?= number_format($rental['total_cost'], 2) ?>
                                    <?php if ($rentedAgo): ?>&middot; <?= $rentedAgo ?><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ctrl-rental-badge ctrl-rental-none" title="No Controller Rental">
                            <i class="fa-solid fa-gamepad" style="opacity:.4"></i>
                            <span>No Controller Rental</span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="console-actions">
                    <!-- Row 1: Edit (always full width) -->
                    <div class="console-edit-row">
                        <button onclick="openEditConsoleModal(<?= $con['console_id'] ?>, '<?= htmlspecialchars($con['console_name'], ENT_QUOTES) ?>', '<?= $con['console_type'] ?>', '<?= htmlspecialchars($con['unit_number'], ENT_QUOTES) ?>', <?= $con['hourly_rate'] ?>, <?= (int)$con['controller_count'] ?>)"

                                class="btn-sec btn-sm">
                            <i class="fas fa-edit"></i> Edit Console
                        </button>

                    </div>

                    <!-- Row 2: Status toggle buttons (2-col grid, only shows if NOT that status) -->
                    <div class="console-status-row">
                        <?php if ($con['status'] !== 'available'): ?>
                        <form method="POST" action="admin.php#consoles">
                            <input type="hidden" name="action" value="update_console_status">
                            <?= csrfField() ?>
                            <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                            <input type="hidden" name="status" value="available">
                            <button type="submit" class="btn-prim btn-sm" title="Set as Available">
                                <i class="fas fa-check"></i> Available
                            </button>

                        </form>
                        <?php endif; ?>

                        <?php if ($con['status'] !== 'maintenance'): ?>
                        <form method="POST" action="admin.php#consoles">
                            <input type="hidden" name="action" value="update_console_status">
                            <?= csrfField() ?>
                            <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                            <input type="hidden" name="status" value="maintenance">
                            <button type="submit" class="btn-dang btn-sm" title="Set to Maintenance">
                                <i class="fas fa-wrench"></i> Maintenance
                            </button>

                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- Row 3: Archive (always full width) -->
                    <div class="console-archive-row">
                        <form method="POST" action="admin.php#consoles"
                              onsubmit="return confirm('Archive this console? It will be removed from active reservations.')">
                            <input type="hidden" name="action" value="update_console_status">
                            <?= csrfField() ?>
                            <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                            <input type="hidden" name="status" value="archived">
                            <button type="submit" class="btn-sec btn-sm" title="Archive Console" style="opacity:0.7;">
                                <i class="fas fa-archive"></i> Archive
                            </button>

                        </form>
                    </div>

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
            
            <button class="btn-sec" onclick="toggleArchiveSection(false)">
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
                        <button type="submit" class="btn-prim btn-sm" style="width:100%;" title="Restore Console">
                            <i class="fas fa-undo"></i> Restore
                        </button>

                    </form>
                    
                    <form method="POST" action="admin.php#consoles" style="flex:1;" onsubmit="return confirm('WARNING: Permanently delete this console? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete_console">
                        <?= csrfField() ?>
                        <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                        <button type="submit" class="btn-dang btn-sm" style="width:100%;" title="Permanently Delete">
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

    <!-- ── Controller Inventory (INSIDE #consoles so it only shows here) ── -->
    <div id="activeControllersSection">
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
                    Controller Inventory
                </h2>
                <p class="page-subtitle">Manage physical controllers available for rental</p>
            </div>
            <button class="btn-prim" onclick="openModal('addController')">
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
                                      onsubmit="return confirm('Archive this controller? It will be moved to the Archived section.')">
                                    <input type="hidden" name="action" value="update_controller_status">
                                    <input type="hidden" name="status" value="archived">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                    <button type="submit" class="btn-dang btn-sm" style="padding:5px 12px; font-size:12px;">
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

        <!-- Archived controllers (collapsible) -->
        <?php
            $archivedControllers = [];
            $_res = $conn->query("SELECT * FROM controllers WHERE status = 'archived' ORDER BY unit_number");
            if ($_res) $archivedControllers = $_res->fetch_all(MYSQLI_ASSOC);
            unset($_res);
        ?>
        <div style="margin-top:20px;">
            <button onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'block':'none'"
                    style="background:none;border:none;color:#888;font-size:13px;cursor:pointer;padding:0;">
                <i class="fas fa-archive" style="margin-right:5px;"></i>
                Show <?= count($archivedControllers) ?> archived controller(s)
            </button>
            <div style="display:none;margin-top:12px;">
                <?php if (empty($archivedControllers)): ?>
                    <div style="padding:20px;text-align:center;color:#666;background:rgba(255,255,255,.02);border-radius:8px;border:1px dashed rgba(255,255,255,.1);">
                        No controllers have been archived yet.
                    </div>
                <?php else: ?>
                <table class="data-table" style="width:100%;opacity:.6;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(10,33,81,.6);">
                            <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;">Unit #</th>
                            <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;">Name</th>
                            <th style="padding:12px 16px;text-align:left;font-size:12px;color:#888;font-weight:700;text-transform:uppercase;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($archivedControllers as $ctrl): ?>
                        <tr style="border-top:1px solid rgba(255,255,255,.05);">
                            <td style="padding:12px 16px;font-weight:700;color:#f1a83c;"><?= htmlspecialchars($ctrl['unit_number']) ?></td>
                            <td style="padding:12px 16px;color:#f0f0f0;"><?= htmlspecialchars($ctrl['controller_name']) ?></td>
                            <td style="padding:12px 16px;">
                                <div style="display:flex;gap:6px;">
                                    <form method="POST" action="admin.php#consoles" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_controller_status">
                                        <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                        <input type="hidden" name="status" value="available">
                                        <button type="submit" class="btn-prim btn-sm" style="padding:5px 12px; font-size:12px;">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>

                                    </form>
                                    <?php if ($user['role'] === 'owner'): ?>
                                    <form method="POST" action="admin.php#consoles" style="display:inline;"
                                          onsubmit="return confirm('Permanently delete this controller? This cannot be undone.')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_controller">
                                        <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                        <button type="submit" class="btn-dang btn-sm" style="padding:5px 10px; font-size:12px;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>

                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- ── END Controller Inventory ── -->
    </div>

</div><!-- /#consoles — CLOSED HERE after controller inventory -->

<!-- Add Console Modal -->
<div class="modal" id="addConsoleModal">
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus" style="color:#20c8a1;"></i> Add New Console</h3>
            <span class="modal-close" onclick="closeModal('addConsole')"><i class="fas fa-times"></i></span>
        </div>
        <form method="POST" action="admin.php#consoles" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Adding...';">
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
                        <?php foreach ($consoleTypes as $ct): ?>
                            <option value="<?= htmlspecialchars($ct['type_name']) ?>"><?= htmlspecialchars($ct['type_name']) ?></option>
                        <?php endforeach; ?>
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
                <div class="form-group">
                    <label>Available Controllers</label>
                    <input type="number" name="controller_count" class="form-control" required min="0" value="2">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sec" onclick="closeModal('addConsole')">Cancel</button>
                <button type="submit" class="btn-prim"><i class="fas fa-save"></i> Add Console</button>
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
        <form method="POST" action="admin.php#consoles" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Saving...';">
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
                        <?php foreach ($consoleTypes as $ct): ?>
                            <option value="<?= htmlspecialchars($ct['type_name']) ?>"><?= htmlspecialchars($ct['type_name']) ?></option>
                        <?php endforeach; ?>
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
                <div class="form-group">
                    <label>Available Controllers</label>
                    <input type="number" name="controller_count" id="editConsoleControllerCount"
                           class="form-control" required min="0">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sec" onclick="closeModal('editConsole')">Cancel</button>
                <button type="submit" class="btn-prim">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function openEditConsoleModal(id, name, type, unit, rate, ctrlCount) {
    document.getElementById('editConsoleId').value   = id;
    document.getElementById('editConsoleName').value = name;
    document.getElementById('editConsoleType').value = type;
    document.getElementById('editConsoleUnit').value = unit;
    document.getElementById('editConsoleRate').value = parseFloat(rate).toFixed(2);
    document.getElementById('editConsoleControllerCount').value = ctrlCount;
    openModal('editConsole');
}

</script>

<script>
function toggleArchiveSection(showArchive) {
    document.getElementById('activeConsolesSection').style.display = showArchive ? 'none' : 'block';
    const ctrlSection = document.getElementById('activeControllersSection');
    if(ctrlSection) ctrlSection.style.display = showArchive ? 'none' : 'block';
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

<!-- Manage Console Types Modal -->
<div class="modal" id="manageConsoleTypesModal">
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3><i class="fas fa-tags" style="color:#20c8a1;"></i> Manage Types</h3>
            <span class="modal-close" onclick="closeModal('manageConsoleTypes')"><i class="fas fa-times"></i></span>
        </div>
        <div class="modal-body">

            <!-- ── Tab switcher ── -->
            <div style="display:flex;gap:0;border:1px solid rgba(95,133,218,.25);border-radius:10px;overflow:hidden;margin-bottom:20px;" id="typeCategoryTabs">
                <button onclick="switchTypeTab('console')" id="tabConsole"
                        style="flex:1;padding:10px 16px;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:.15s;background:rgba(32,200,161,.15);color:#20c8a1;">
                    <i class="fas fa-desktop" style="margin-right:5px;"></i> Console Types
                </button>
                <button onclick="switchTypeTab('controller')" id="tabController"
                        style="flex:1;padding:10px 16px;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:.15s;background:rgba(255,255,255,.04);color:#888;border-left:1px solid rgba(95,133,218,.2);">
                    <i class="fas fa-gamepad" style="margin-right:5px;"></i> Controller Types
                </button>
            </div>

            <!-- ══ CONSOLE TYPES PANEL ══ -->
            <div id="panelConsole">
                <!-- Add New Console Type -->
                <form method="POST" action="admin.php#consoles" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid rgba(255,255,255,.1);">
                    <input type="hidden" name="action" value="add_console_type">
                    <input type="hidden" name="category" value="console">
                    <?= csrfField() ?>
                    <label style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;display:block;margin-bottom:8px;">Add New Console Type</label>
                    <div style="display:flex;gap:10px;">
                        <input type="text" name="type_name" class="form-control" required placeholder="e.g. Nintendo Switch" style="flex:1;">
                        <button type="submit" class="btn-prim"><i class="fas fa-plus"></i> Add</button>
                    </div>
                </form>

                <!-- Active Console Types -->
                <label style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;display:block;margin-bottom:12px;">Active Console Types</label>
                <div style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;padding-right:5px;margin-bottom:20px;">
                    <?php if (empty($consoleTypes)): ?>
                        <div style="text-align:center;padding:20px;color:#555;font-style:italic;">No active console types.</div>
                    <?php else: ?>
                        <?php foreach ($consoleTypes as $ct): ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(32,200,161,.05);padding:10px 14px;border-radius:8px;border:1px solid rgba(32,200,161,.1);">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="fas fa-desktop" style="color:#20c8a1;font-size:12px;"></i>
                                    <span style="font-weight:600;font-size:14px;color:#f0f0f0;"><?= htmlspecialchars($ct['type_name']) ?></span>
                                </div>
                                <form method="POST" action="admin.php#consoles" onsubmit="return confirm('Archive this console type? All associated consoles will be moved to ARCHIVE.');">
                                    <input type="hidden" name="action" value="archive_console_type">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type_id" value="<?= $ct['type_id'] ?>">
                                    <button type="submit" title="Archive Type" style="background:none;border:none;color:#f1a83c;cursor:pointer;font-size:14px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Archived Console Types -->
                <?php if (!empty($archivedConsoleTypes)): ?>
                <label style="font-size:12px;font-weight:700;color:#fb566b;text-transform:uppercase;display:block;margin-bottom:12px;border-top:1px solid rgba(255,255,255,.05);padding-top:15px;">Archived Console Types</label>
                <div style="display:flex;flex-direction:column;gap:8px;max-height:150px;overflow-y:auto;padding-right:5px;margin-bottom:15px;">
                    <?php foreach ($archivedConsoleTypes as $ct): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.03);padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.03);opacity:0.8;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fas fa-desktop" style="color:#888;font-size:12px;"></i>
                                <span style="font-weight:500;font-size:13px;color:#aaa;"><?= htmlspecialchars($ct['type_name']) ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <form method="POST" action="admin.php#consoles">
                                    <input type="hidden" name="action" value="restore_console_type">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type_id" value="<?= $ct['type_id'] ?>">
                                    <button type="submit" title="Restore" style="background:none;border:none;color:#20c8a1;cursor:pointer;font-size:14px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"><i class="fas fa-undo"></i></button>
                                </form>
                                <form method="POST" action="admin.php#consoles" onsubmit="return confirm('PERMANENTLY DELETE this type? This is irreversible.');">
                                    <input type="hidden" name="action" value="delete_console_type">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type_id" value="<?= $ct['type_id'] ?>">
                                    <button type="submit" title="Delete Permanently" style="background:none;border:none;color:#fb566b;cursor:pointer;font-size:14px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div><!-- /#panelConsole -->

            <!-- ══ CONTROLLER TYPES PANEL ══ -->
            <div id="panelController" style="display:none;">
                <!-- Add New Controller Type -->
                <form method="POST" action="admin.php#consoles" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid rgba(255,255,255,.1);">
                    <input type="hidden" name="action" value="add_controller_type">
                    <?= csrfField() ?>
                    <label style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;display:block;margin-bottom:8px;">Add New Controller Type</label>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <input type="text" name="type_name" class="form-control" required placeholder="e.g. Joy-Con">
                        <div class="form-group" style="margin:0;">
                            <label style="font-size:11px;color:#888;margin-bottom:4px;display:block;">Compatible Console <span style="color:#5f85da;">(FK link)</span></label>
                            <select name="console_type_id" class="form-control">
                                <option value="">— Not linked / Generic —</option>
                                <?php foreach ($consoleTypes as $cType): ?>
                                    <option value="<?= $cType['type_id'] ?>"><?= htmlspecialchars($cType['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-prim" style="align-self:flex-start;"><i class="fas fa-plus"></i> Add Controller Type</button>
                    </div>
                </form>

                <!-- Active Controller Types -->
                <label style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;display:block;margin-bottom:12px;">Active Controller Types</label>
                <div style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;padding-right:5px;margin-bottom:20px;">
                    <?php if (empty($controllerTypes)): ?>
                        <div style="text-align:center;padding:20px;color:#555;font-style:italic;">No active controller types.</div>
                    <?php else: ?>
                        <?php foreach ($controllerTypes as $ct): ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(95,133,218,.07);padding:10px 14px;border-radius:8px;border:1px solid rgba(95,133,218,.15);">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="fas fa-gamepad" style="color:#8aa4e8;font-size:12px;"></i>
                                    <div>
                                        <span style="font-weight:600;font-size:14px;color:#f0f0f0;"><?= htmlspecialchars($ct['type_name']) ?></span>
                                        <?php if (!empty($ct['parent_console_name'])): ?>
                                            <div style="font-size:11px;color:#5f85da;margin-top:2px;">
                                                <i class="fas fa-link" style="font-size:9px;margin-right:3px;"></i><?= htmlspecialchars($ct['parent_console_name']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size:11px;color:#555;margin-top:2px;">Generic / not linked</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <form method="POST" action="admin.php#consoles" onsubmit="return confirm('Archive this controller type?')">
                                    <input type="hidden" name="action" value="archive_controller_type">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type_id" value="<?= $ct['type_id'] ?>">
                                    <button type="submit" title="Archive Type" style="background:none;border:none;color:#f1a83c;cursor:pointer;font-size:14px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Archived Controller Types -->
                <?php if (!empty($archivedCtrlTypes)): ?>
                <label style="font-size:12px;font-weight:700;color:#fb566b;text-transform:uppercase;display:block;margin-bottom:12px;border-top:1px solid rgba(255,255,255,.05);padding-top:15px;">Archived Controller Types</label>
                <div style="display:flex;flex-direction:column;gap:8px;max-height:150px;overflow-y:auto;padding-right:5px;margin-bottom:15px;">
                    <?php foreach ($archivedCtrlTypes as $ct): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.03);padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.03);opacity:0.8;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fas fa-gamepad" style="color:#888;font-size:12px;"></i>
                                <span style="font-weight:500;font-size:13px;color:#aaa;"><?= htmlspecialchars($ct['type_name']) ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <form method="POST" action="admin.php#consoles">
                                    <input type="hidden" name="action" value="restore_controller_type">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type_id" value="<?= $ct['type_id'] ?>">
                                    <button type="submit" title="Restore" style="background:none;border:none;color:#20c8a1;cursor:pointer;font-size:14px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"><i class="fas fa-undo"></i></button>
                                </form>
                                <form method="POST" action="admin.php#consoles" onsubmit="return confirm('PERMANENTLY DELETE this type? This is irreversible.');">
                                    <input type="hidden" name="action" value="delete_controller_type">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type_id" value="<?= $ct['type_id'] ?>">
                                    <button type="submit" title="Delete Permanently" style="background:none;border:none;color:#fb566b;cursor:pointer;font-size:14px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div><!-- /#panelController -->

            <div style="margin-top:10px;padding:12px;background:rgba(95,133,218,.1);border:1px solid rgba(95,133,218,.2);border-radius:8px;display:flex;gap:10px;">
                <i class="fas fa-info-circle" style="color:#5f85da;margin-top:2px;"></i>
                <div style="font-size:12px;color:rgba(140,160,210,.9);line-height:1.4;">
                    <strong>Console types</strong> appear in the Add Console form. <strong>Controller types</strong> appear in the Add Controller form. Archiving a console type also archives all consoles of that type.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-sec btn-full" onclick="closeModal('manageConsoleTypes')">Close</button>
        </div>
    </div>
</div>

<script>
function switchTypeTab(tab) {
    document.getElementById('panelConsole').style.display    = tab === 'console'    ? 'block' : 'none';
    document.getElementById('panelController').style.display = tab === 'controller' ? 'block' : 'none';
    document.getElementById('tabConsole').style.background    = tab === 'console'    ? 'rgba(32,200,161,.15)' : 'rgba(255,255,255,.04)';
    document.getElementById('tabConsole').style.color         = tab === 'console'    ? '#20c8a1' : '#888';
    document.getElementById('tabController').style.background = tab === 'controller' ? 'rgba(95,133,218,.2)'   : 'rgba(255,255,255,.04)';
    document.getElementById('tabController').style.color      = tab === 'controller' ? '#8aa4e8' : '#888';
}
</script>