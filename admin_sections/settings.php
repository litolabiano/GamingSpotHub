<!-- ════ SETTINGS ═══════════════════════════════════════════════════════════ -->
<div class="page" id="settings">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-cog" style="color:#f1a83c;margin-right:10px;"></i>Settings</h2>
            <p class="page-subtitle">Pricing rules, bonus time, and shop configuration</p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save_settings">
        <?= csrfField() ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

            <!-- ── Pricing ── -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-peso-sign" style="color:#20c8a1;margin-right:6px;"></i>Pricing</h3></div>

                <div class="form-group">
                    <label>PS5 / PS4 Hourly Rate (₱/hr)</label>
                    <input type="number" step="0.01" min="0" name="ps5_hourly_rate"
                           value="<?= htmlspecialchars($settings['ps5_hourly_rate'] ?? '80') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> Applies to all PS5 &amp; PS4 units. Saves to each console row automatically.
                    </div>
                </div>

                <div class="form-group">
                    <label>Xbox Series X Hourly Rate (₱/hr)</label>
                    <input type="number" step="0.01" min="0" name="xbox_hourly_rate"
                           value="<?= htmlspecialchars($settings['xbox_hourly_rate'] ?? '80') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> Applies to all Xbox units.
                    </div>
                </div>

                <div class="form-group">
                    <label>Unlimited (whole day) Rate (₱)</label>
                    <input type="number" step="0.01" min="0" name="unlimited_rate"
                           value="<?= htmlspecialchars($settings['unlimited_rate'] ?? '400') ?>">
                </div>

                <div class="form-group">
                    <label>First 30-min Minimum Charge (₱)</label>
                    <input type="number" step="0.01" min="0" name="session_min_charge"
                           value="<?= htmlspecialchars($settings['session_min_charge'] ?? '50') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> Flat charge for sessions of 30 minutes or less.
                        This is the <strong>“first pay”</strong> amount shown in the duration dropdown.
                    </div>
                </div>

                <div class="form-group">
                    <label>Controller Rental Fee (₱)</label>
                    <input type="number" step="0.01" min="0" name="controller_rental_fee"
                           value="<?= htmlspecialchars($settings['controller_rental_fee'] ?? '20') ?>">
                </div>
            </div>

            <!-- ── Bonus Time Rule ── -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-gift" style="color:#f1a83c;margin-right:6px;"></i>Bonus Time Rule
                    </h3>
                </div>

                <div style="background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.25);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;color:#f1e1aa;">
                    <i class="fas fa-star" style="color:#f1a83c;margin-right:6px;"></i>
                    Every <strong><?= (int)($settings['bonus_paid_minutes'] ?? 120) ?> paid minutes</strong>
                    the customer earns <strong><?= (int)($settings['bonus_free_minutes'] ?? 30) ?> free minutes</strong>.
                    All dropdowns and billing update automatically when you save.
                </div>

                <div class="form-group">
                    <label>Paid Minutes Per Bonus Cycle</label>
                    <input type="number" step="30" min="30" max="360" name="bonus_paid_minutes"
                           value="<?= htmlspecialchars($settings['bonus_paid_minutes'] ?? '120') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        Default: <strong>120</strong> (every 2 hrs). Must be a multiple of 30.
                    </div>
                </div>

                <div class="form-group">
                    <label>Free Minutes Earned Per Cycle</label>
                    <input type="number" step="5" min="5" max="120" name="bonus_free_minutes"
                           value="<?= htmlspecialchars($settings['bonus_free_minutes'] ?? '30') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        Default: <strong>30</strong> (30 min free).
                    </div>
                </div>

                <div class="form-group">
                    <label>Max Hourly Session (paid minutes)</label>
                    <input type="number" step="30" min="60" max="480" name="max_hourly_minutes"
                           value="<?= htmlspecialchars($settings['max_hourly_minutes'] ?? '240') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        Default: <strong>240</strong> (4 hrs paid). Above this, suggest Unlimited mode.
                    </div>
                </div>
            </div>

            <!-- ── Shop Information ── -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-store" style="color:#5f85da;margin-right:6px;"></i>Shop Information</h3></div>
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" name="shop_name" value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>" readonly style="opacity:.6">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="shop_address" value="<?= htmlspecialchars($settings['shop_address'] ?? '') ?>" readonly style="opacity:.6">
                </div>
                <div class="form-group">
                    <label>Opening Time</label>
                    <input type="time" name="business_hours_open" value="<?= htmlspecialchars($settings['business_hours_open'] ?? '09:00') ?>">
                </div>
                <div class="form-group">
                    <label>Closing Time</label>
                    <input type="time" name="business_hours_close" value="<?= htmlspecialchars($settings['business_hours_close'] ?? '23:00') ?>">
                </div>
                <div class="form-group">
                    <label>Shop Phone</label>
                    <input type="text" name="shop_phone" value="<?= htmlspecialchars($settings['shop_phone'] ?? '') ?>">
                </div>
            </div>

        </div>

        <div style="margin-top:16px;display:flex;align-items:center;gap:12px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save All Settings
            </button>
            <span style="font-size:12px;color:#888;">
                <i class="fas fa-info-circle"></i>
                Saving rates will immediately update all console dropdowns and duration options across the system.
            </span>
        </div>
    </form>
</div>

<!-- ── Xbox Controller Inventory ─────────────────────────────────────── -->
<div class="card" style="margin-top:28px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:800;">
                <i class="fas fa-gamepad" style="color:var(--clr-mint);margin-right:8px;"></i>
                Xbox Controller Inventory
            </h3>
            <p style="margin:4px 0 0;font-size:12px;color:#888;">
                Manage physical controllers available for rental
            </p>
        </div>
        <button onclick="openModal('addController')"
                class="btn btn-primary" style="font-size:13px;padding:9px 18px;">
            <i class="fas fa-plus"></i> Add Controller
        </button>
    </div>

    <!-- Summary bar -->
    <?php
    $ctrlTotal     = $conn->query("SELECT COUNT(*) AS n FROM controllers WHERE status != 'archived'")->fetch_assoc()['n'] ?? 0;
    $ctrlAvailable = $conn->query("SELECT COUNT(*) AS n FROM controllers WHERE status = 'available'")->fetch_assoc()['n'] ?? 0;
    $ctrlInUse     = $conn->query("SELECT COUNT(*) AS n FROM controllers WHERE status = 'in_use'")->fetch_assoc()['n'] ?? 0;
    $ctrlMaint     = $conn->query("SELECT COUNT(*) AS n FROM controllers WHERE status = 'maintenance'")->fetch_assoc()['n'] ?? 0;
    ?>
    <div style="display:flex;gap:12px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--clr-border);">
        <div style="background:rgba(32,200,161,.12);border:1px solid rgba(32,200,161,.3);border-radius:10px;padding:10px 18px;text-align:center;min-width:90px;">
            <div style="font-size:22px;font-weight:900;color:#20c8a1;"><?= $ctrlAvailable ?></div>
            <div style="font-size:11px;color:#888;margin-top:2px;">Available</div>
        </div>
        <div style="background:rgba(95,133,218,.12);border:1px solid rgba(95,133,218,.3);border-radius:10px;padding:10px 18px;text-align:center;min-width:90px;">
            <div style="font-size:22px;font-weight:900;color:#8aa4e8;"><?= $ctrlInUse ?></div>
            <div style="font-size:11px;color:#888;margin-top:2px;">In Use</div>
        </div>
        <div style="background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.3);border-radius:10px;padding:10px 18px;text-align:center;min-width:90px;">
            <div style="font-size:22px;font-weight:900;color:#fb566b;"><?= $ctrlMaint ?></div>
            <div style="font-size:11px;color:#888;margin-top:2px;">Maintenance</div>
        </div>
        <div style="background:rgba(241,225,170,.08);border:1px solid rgba(241,225,170,.2);border-radius:10px;padding:10px 18px;text-align:center;min-width:90px;">
            <div style="font-size:22px;font-weight:900;color:#f1e1aa;"><?= $ctrlTotal ?></div>
            <div style="font-size:11px;color:#888;margin-top:2px;">Total</div>
        </div>
    </div>

    <!-- Controller list -->
    <div style="padding:16px 20px;">
        <?php if (empty($allControllers) && empty($archivedControllers)): ?>
        <div class="empty-state">
            <i class="fas fa-gamepad"></i>
            <p style="font-weight:600;color:#666;">No controllers added yet</p>
            <p style="font-size:12px;">Add Xbox controllers available for rental in your shop.</p>
        </div>
        <?php else: ?>
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th>Unit #</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allControllers as $ctrl): ?>
                <tr>
                    <td><strong style="color:#f1e1aa;"><?= htmlspecialchars($ctrl['unit_number']) ?></strong></td>
                    <td><?= htmlspecialchars($ctrl['controller_name']) ?></td>
                    <td>
                        <span class="badge" style="background:rgba(32,200,161,.15);color:#20c8a1;border:1px solid rgba(32,200,161,.3);">
                            <i class="fas fa-gamepad" style="margin-right:4px;font-size:10px;"></i>
                            <?= htmlspecialchars($ctrl['controller_type']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-dot <?= $ctrl['status'] ?>"></span>
                        <?= ucfirst(str_replace('_',' ',$ctrl['status'])) ?>
                    </td>
                    <td style="color:#888;font-size:12px;"><?= htmlspecialchars($ctrl['notes'] ?? '—') ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <!-- Status dropdown (no archive option here) -->
                            <form method="POST" style="display:inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_controller_status">
                                <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                <select name="status" onchange="this.form.submit()"
                                        style="background:rgba(10,33,81,.7);border:1px solid rgba(95,133,218,.25);
                                               color:#ccc;padding:5px 8px;border-radius:7px;font-size:12px;cursor:pointer;">
                                    <option value="available"   <?= $ctrl['status']==='available'   ? 'selected':'' ?>>Available</option>
                                    <option value="in_use"      <?= $ctrl['status']==='in_use'      ? 'selected':'' ?>>In Use</option>
                                    <option value="maintenance" <?= $ctrl['status']==='maintenance' ? 'selected':'' ?>>Maintenance</option>
                                </select>
                            </form>
                            <!-- Archive button (replaces trash) -->
                            <form method="POST" onsubmit="return confirm('Archive this controller? It will be hidden from active inventory.')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_controller_status">
                                <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                <input type="hidden" name="status" value="archived">
                                <button type="submit"
                                        style="background:rgba(255,255,255,.06);color:#aaa;border:1px solid rgba(255,255,255,.12);
                                               padding:5px 10px;border-radius:7px;font-size:12px;cursor:pointer;">
                                    <i class="fas fa-archive"></i> Archive
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Archived controllers (collapsible) -->
        <?php if (!empty($archivedControllers)): ?>
        <div style="margin-top:20px;">
            <button onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'block':'none'"
                    style="background:none;border:none;color:#888;font-size:13px;cursor:pointer;padding:0;">
                <i class="fas fa-archive" style="margin-right:5px;"></i>
                Show <?= count($archivedControllers) ?> archived controller(s)
            </button>
            <div style="display:none;margin-top:12px;">
                <table class="data-table" style="width:100%;opacity:.6;">
                    <thead><tr><th>Unit #</th><th>Name</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($archivedControllers as $ctrl): ?>
                        <tr>
                            <td><strong style="color:#f1e1aa;"><?= htmlspecialchars($ctrl['unit_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ctrl['controller_name']) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <!-- Restore -->
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_controller_status">
                                        <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                        <input type="hidden" name="status" value="available">
                                        <button type="submit"
                                                style="background:rgba(32,200,161,.15);color:#20c8a1;border:1px solid rgba(32,200,161,.3);
                                                       padding:5px 12px;border-radius:7px;font-size:12px;cursor:pointer;">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                    <!-- Permanent Delete (owner only) -->
                                    <?php if ($user['role'] === 'owner'): ?>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Permanently delete this controller? This cannot be undone.')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_controller">
                                        <input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
                                        <button type="submit"
                                                style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);
                                                       padding:5px 10px;border-radius:7px;font-size:12px;cursor:pointer;">
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
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
