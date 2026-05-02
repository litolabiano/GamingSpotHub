<!-- ════ CONSOLES ══════════════════════════════════════════════════════════ -->
<div class="page" id="consoles">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-desktop" style="color:#20c8a1;margin-right:10px;"></i>Console Management</h2>
            <p class="page-subtitle">Manage console availability and maintenance status</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
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
            <div class="console-actions">
                <?php if ($con['status'] !== 'available'): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="update_console_status">
                    <?= csrfField() ?>
                    <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                    <input type="hidden" name="status" value="available">
                    <button type="submit" class="btn btn-success btn-sm" title="Set as Available">
                        <i class="fas fa-check"></i> Available
                    </button>
                </form>
                <?php endif; ?>
                <?php if ($con['status'] !== 'maintenance'): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="update_console_status">
                    <?= csrfField() ?>
                    <input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
                    <input type="hidden" name="status" value="maintenance">
                    <button type="submit" class="btn btn-secondary btn-sm" title="Set to Maintenance"
                            style="background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.3);color:#fb566b;">
                        <i class="fas fa-wrench"></i> Maintenance
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
