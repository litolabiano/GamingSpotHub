<?php $isOwner = ($user['role'] === 'owner'); ?>
<!-- ════ CONSOLES & CONTROLLERS ════════════════════════════════════════════ -->
<div class="page" id="consoles">

<!-- Tab Nav -->
<div style="display:flex;border-bottom:2px solid rgba(95,133,218,.15);margin-bottom:22px;">
  <button id="tabBtnConsoles" onclick="switchConsoleTab('consoles')" style="padding:10px 22px;background:none;border:none;border-bottom:2px solid #20c8a1;margin-bottom:-2px;color:#20c8a1;font-weight:600;font-size:13px;cursor:pointer;font-family:inherit;">
    <i class="fas fa-desktop"></i> Consoles</button>
  <button id="tabBtnControllers" onclick="switchConsoleTab('controllers')" style="padding:10px 22px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:rgba(255,255,255,.4);font-weight:600;font-size:13px;cursor:pointer;font-family:inherit;">
    <i class="fas fa-gamepad"></i> Controllers</button>
</div>

<!-- ══ CONSOLES TAB ══════════════════════════════════════════════════════ -->
<div id="consoleTab">
  <div id="activeConsolesSection">
    <div class="page-header" style="align-items:center;flex-wrap:wrap;gap:12px;">
      <div class="page-title-group" style="flex:1;">
        <h2 class="page-title"><i class="fas fa-desktop" style="color:#20c8a1;margin-right:8px;"></i>Console Management</h2>
        <p class="page-subtitle">Manage console availability and maintenance status</p>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" onclick="openModal('addConsole')"><i class="fas fa-plus"></i> Add Console</button>
        <button class="btn btn-secondary" onclick="toggleArchiveSection(true)"><i class="fas fa-archive"></i> Archived (<?= count($archivedConsoles) ?>)</button>
      </div>
      <div style="width:100%;display:flex;gap:16px;flex-wrap:wrap;">
        <span style="font-size:13px;display:flex;align-items:center;gap:6px;"><span class="status-dot available"></span><?= $availableCount ?> Available</span>
        <span style="font-size:13px;display:flex;align-items:center;gap:6px;"><span class="status-dot in_use"></span><?= $inUseCount ?> In Use</span>
        <span style="font-size:13px;display:flex;align-items:center;gap:6px;"><span class="status-dot maintenance"></span><?= $maintenanceCount ?> Maintenance</span>
      </div>
    </div>
    <div class="asb-wrap" style="margin:0 0 16px;">
      <div class="asb-search" style="max-width:260px;">
        <i class="fas fa-search"></i>
        <input type="text" class="asb-input" id="consoleSearch" placeholder="Search unit, name, type…" autocomplete="off">
        <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
      </div>
      <select class="asb-select" id="consoleStatusFilter">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="in_use">In Use</option>
        <option value="maintenance">Maintenance</option>
      </select>
      <span class="asb-count" id="consoleCount"></span>
    </div>
    <div class="console-grid" id="consoleGrid">
    <?php foreach ($allConsoles as $con):
      $bCls=$con['console_type']==='PS5'?'ps5':($con['console_type']==='PS4'?'ps4':'xbox');
      $bIco=in_array($con['console_type'],['PS5','PS4'])?'playstation':'xbox'; ?>
      <div class="console-card <?= $con['status'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
          <span class="console-type-badge <?= $bCls ?>"><i class="fab fa-<?= $bIco ?>"></i> <?= $con['console_type'] ?></span>
          <span class="badge <?= $con['status'] ?>"><?= ucfirst(str_replace('_',' ',$con['status'])) ?></span>
        </div>
        <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
        <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
        <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
        <div class="console-actions" style="margin-top:15px;display:flex;flex-wrap:wrap;gap:8px;">
          <?php if($con['status']!=='available'): ?>
          <form method="POST" style="flex:1;min-width:90px;"><input type="hidden" name="action" value="update_console_status"><?= csrfField() ?><input type="hidden" name="console_id" value="<?= $con['console_id'] ?>"><input type="hidden" name="status" value="available">
            <button type="submit" class="btn btn-success btn-sm" style="width:100%;"><i class="fas fa-check"></i> Available</button></form>
          <?php endif; ?>
          <?php if($con['status']!=='maintenance'): ?>
          <form method="POST" style="flex:1;min-width:90px;"><input type="hidden" name="action" value="update_console_status"><?= csrfField() ?><input type="hidden" name="console_id" value="<?= $con['console_id'] ?>"><input type="hidden" name="status" value="maintenance">
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(251,86,107,.12);border-color:rgba(251,86,107,.3);color:#fb566b;"><i class="fas fa-wrench"></i> Maintenance</button></form>
          <?php endif; ?>
          <form method="POST" style="flex:1;min-width:90px;" onsubmit="return confirm('Archive this console?')"><input type="hidden" name="action" value="update_console_status"><?= csrfField() ?><input type="hidden" name="console_id" value="<?= $con['console_id'] ?>"><input type="hidden" name="status" value="archived">
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#ccc;"><i class="fas fa-archive"></i> Archive</button></form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($allConsoles)): ?><div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">No active consoles.</div><?php endif; ?>
    </div>
    <div class="asb-no-results" id="consoleSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No consoles match.</div>
    <div id="consolePagination"></div>
  </div>

  <div id="archivedConsolesSection" style="display:none;">
    <div class="page-header" style="align-items:center;">
      <div class="page-title-group" style="flex:1;">
        <h2 class="page-title"><i class="fas fa-archive" style="color:#fb566b;margin-right:8px;"></i>Archived Consoles</h2>
        <p class="page-subtitle">Restore or permanently delete archived consoles.</p>
      </div>
      <button class="btn btn-secondary" onclick="toggleArchiveSection(false)"><i class="fas fa-arrow-left"></i> Back</button>
    </div>
    <div class="console-grid">
    <?php foreach ($archivedConsoles as $con):
      $bCls=$con['console_type']==='PS5'?'ps5':($con['console_type']==='PS4'?'ps4':'xbox');
      $bIco=in_array($con['console_type'],['PS5','PS4'])?'playstation':'xbox'; ?>
      <div class="console-card archived" style="opacity:.8;border-color:rgba(251,86,107,.3);background:rgba(251,86,107,.05);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
          <span class="console-type-badge <?= $bCls ?>"><i class="fab fa-<?= $bIco ?>"></i> <?= $con['console_type'] ?></span>
          <span class="badge gray">Archived</span>
        </div>
        <div class="console-unit"><?= htmlspecialchars($con['unit_number']) ?></div>
        <div class="console-name"><?= htmlspecialchars($con['console_name']) ?></div>
        <div class="console-rate"><i class="fas fa-peso-sign" style="font-size:11px;opacity:.7"></i> <?= number_format($con['hourly_rate'],2) ?>/hr</div>
        <div class="console-actions" style="margin-top:15px;display:flex;gap:8px;">
          <form method="POST" style="flex:1;"><input type="hidden" name="action" value="update_console_status"><?= csrfField() ?><input type="hidden" name="console_id" value="<?= $con['console_id'] ?>"><input type="hidden" name="status" value="available">
            <button type="submit" class="btn btn-primary btn-sm" style="width:100%;"><i class="fas fa-undo"></i> Restore</button></form>
          <form method="POST" style="flex:1;" onsubmit="return confirm('Permanently delete?')"><input type="hidden" name="action" value="delete_console"><?= csrfField() ?><input type="hidden" name="console_id" value="<?= $con['console_id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" style="width:100%;"><i class="fas fa-trash"></i> Delete</button></form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($archivedConsoles)): ?><div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">No archived consoles.</div><?php endif; ?>
    </div>
  </div>
</div><!-- /#consoleTab -->

<!-- ══ CONTROLLERS TAB ══════════════════════════════════════════════════ -->
<div id="controllerTab" style="display:none;">
  <div id="activeControllersSection">
    <div class="page-header" style="align-items:center;flex-wrap:wrap;gap:12px;">
      <div class="page-title-group" style="flex:1;">
        <h2 class="page-title"><i class="fas fa-gamepad" style="color:#20c8a1;margin-right:8px;"></i>Controller Management</h2>
        <p class="page-subtitle">Track and manage gaming controllers</p>
      </div>
      <div style="display:flex;gap:10px;">
        <?php if($isOwner): ?>
        <button class="btn btn-primary" onclick="openModal('addController')"><i class="fas fa-plus"></i> Add Controller</button>
        <button class="btn btn-secondary" onclick="toggleCtrlArchive(true)"><i class="fas fa-archive"></i> Archived (<?= count($archivedControllers) ?>)</button>
        <?php endif; ?>
      </div>
    </div>
    <div class="asb-wrap" style="margin:0 0 16px;">
      <div class="asb-search" style="max-width:260px;">
        <i class="fas fa-search"></i>
        <input type="text" class="asb-input" id="ctrlSearch" placeholder="Search unit, type…" autocomplete="off">
        <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
      </div>
      <select class="asb-select" id="ctrlStatusFilter">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="in_use">In Use</option>
        <option value="maintenance">Maintenance</option>
      </select>
      <span class="asb-count" id="ctrlCount"></span>
    </div>
    <div class="console-grid" id="ctrlGrid">
    <?php foreach ($allControllers as $ctrl):
      $ctIcon = match($ctrl['controller_type']) {
        'DualSense','DualShock 4' => 'playstation',
        'Xbox Controller'         => 'xbox',
        default                   => 'gamepad'
      };
      $ctBadge = match($ctrl['controller_type']) {
        'DualSense','DualShock 4' => 'ps5',
        'Xbox Controller'         => 'xbox',
        default                   => ''
      }; ?>
      <div class="console-card <?= $ctrl['status'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
          <span class="console-type-badge <?= $ctBadge ?>" style="<?= $ctBadge===''?'background:rgba(95,133,218,.15);color:#7fa3f0;':'' ?>">
            <?php if($ctIcon==='gamepad'): ?><i class="fas fa-gamepad"></i><?php else: ?><i class="fab fa-<?= $ctIcon ?>"></i><?php endif; ?>
            <?= htmlspecialchars($ctrl['controller_type']) ?>
          </span>
          <span class="badge <?= $ctrl['status'] ?>"><?= ucfirst(str_replace('_',' ',$ctrl['status'])) ?></span>
        </div>
        <div class="console-unit"><?= htmlspecialchars($ctrl['unit_number']) ?></div>
        <div class="console-name"><?= htmlspecialchars($ctrl['controller_name']) ?></div>
        <?php if($ctrl['notes']): ?><div style="font-size:11px;color:rgba(255,255,255,.35);margin-top:4px;"><?= htmlspecialchars($ctrl['notes']) ?></div><?php endif; ?>
        <div class="console-actions" style="margin-top:15px;display:flex;flex-wrap:wrap;gap:8px;">
          <?php if($ctrl['status']!=='available'): ?>
          <form method="POST" style="flex:1;min-width:90px;"><input type="hidden" name="action" value="update_controller_status"><?= csrfField() ?><input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>"><input type="hidden" name="status" value="available">
            <button type="submit" class="btn btn-success btn-sm" style="width:100%;"><i class="fas fa-check"></i> Available</button></form>
          <?php endif; ?>
          <?php if($ctrl['status']!=='maintenance'): ?>
          <form method="POST" style="flex:1;min-width:90px;"><input type="hidden" name="action" value="update_controller_status"><?= csrfField() ?><input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>"><input type="hidden" name="status" value="maintenance">
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(251,86,107,.12);border-color:rgba(251,86,107,.3);color:#fb566b;"><i class="fas fa-wrench"></i> Maintenance</button></form>
          <?php endif; ?>
          <?php if($isOwner): ?>
          <form method="POST" style="flex:1;min-width:90px;" onsubmit="return confirm('Archive this controller?')"><input type="hidden" name="action" value="update_controller_status"><?= csrfField() ?><input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>"><input type="hidden" name="status" value="archived">
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#ccc;"><i class="fas fa-archive"></i> Archive</button></form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($allControllers)): ?><div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">No active controllers. Click <strong>Add Controller</strong> to get started.</div><?php endif; ?>
    </div>
    <div class="asb-no-results" id="ctrlSearch_noResults" style="display:none;"><i class="fas fa-search" style="display:block;font-size:24px;margin-bottom:8px;opacity:.4;"></i>No controllers match.</div>
    <div id="ctrlPagination"></div>
  </div>

  <?php if($isOwner): ?>
  <div id="archivedControllersSection" style="display:none;">
    <div class="page-header" style="align-items:center;">
      <div class="page-title-group" style="flex:1;">
        <h2 class="page-title"><i class="fas fa-archive" style="color:#fb566b;margin-right:8px;"></i>Archived Controllers</h2>
        <p class="page-subtitle">Restore or permanently delete archived controllers.</p>
      </div>
      <button class="btn btn-secondary" onclick="toggleCtrlArchive(false)"><i class="fas fa-arrow-left"></i> Back</button>
    </div>
    <div class="console-grid">
    <?php foreach ($archivedControllers as $ctrl): ?>
      <div class="console-card archived" style="opacity:.8;border-color:rgba(251,86,107,.3);background:rgba(251,86,107,.05);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
          <span class="console-type-badge" style="background:rgba(95,133,218,.15);color:#7fa3f0;"><i class="fas fa-gamepad"></i> <?= htmlspecialchars($ctrl['controller_type']) ?></span>
          <span class="badge gray">Archived</span>
        </div>
        <div class="console-unit"><?= htmlspecialchars($ctrl['unit_number']) ?></div>
        <div class="console-name"><?= htmlspecialchars($ctrl['controller_name']) ?></div>
        <div class="console-actions" style="margin-top:15px;display:flex;gap:8px;">
          <form method="POST" style="flex:1;"><input type="hidden" name="action" value="update_controller_status"><?= csrfField() ?><input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>"><input type="hidden" name="status" value="available">
            <button type="submit" class="btn btn-primary btn-sm" style="width:100%;"><i class="fas fa-undo"></i> Restore</button></form>
          <form method="POST" style="flex:1;" onsubmit="return confirm('Permanently delete?')"><input type="hidden" name="action" value="delete_controller"><?= csrfField() ?><input type="hidden" name="controller_id" value="<?= $ctrl['controller_id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" style="width:100%;"><i class="fas fa-trash"></i> Delete</button></form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($archivedControllers)): ?><div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">No archived controllers.</div><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div><!-- /#controllerTab -->

</div><!-- /.page#consoles -->

<!-- ══ ADD CONSOLE MODAL ════════════════════════════════════════════════ -->
<div class="modal" id="addConsoleModal">
  <div class="modal-content" style="max-width:450px;">
    <div class="modal-header">
      <h3><i class="fas fa-plus" style="color:#20c8a1;"></i> Add New Console</h3>
      <span class="modal-close" onclick="closeModal('addConsole')"><i class="fas fa-times"></i></span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_console"><?= csrfField() ?>
      <div class="modal-body">
        <div class="form-group"><label>Console Name / Description</label><input type="text" name="console_name" class="form-control" required placeholder="e.g. VIP Console"></div>
        <div class="form-group"><label>Console Type</label>
          <select name="console_type" class="form-control" required>
            <option value="" disabled selected>Select Type</option>
            <option value="PS5">PlayStation 5</option>
            <option value="Xbox Series X">Xbox Series X</option>
          </select></div>
        <div class="form-group"><label>Unit Number <span style="color:#888;font-size:11px;">(Must be unique)</span></label><input type="text" name="unit_number" class="form-control" required placeholder="e.g. PS5-01"></div>
        <div class="form-group"><label>Hourly Rate (₱)</label><input type="number" name="hourly_rate" class="form-control" required min="0" step="0.01" value="100.00"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addConsole')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Console</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ ADD CONTROLLER MODAL ══════════════════════════════════════════════ -->
<div class="modal" id="addControllerModal">
  <div class="modal-content" style="max-width:450px;">
    <div class="modal-header">
      <h3><i class="fas fa-gamepad" style="color:#20c8a1;"></i> Add New Controller</h3>
      <span class="modal-close" onclick="closeModal('addController')"><i class="fas fa-times"></i></span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_controller"><?= csrfField() ?>
      <div class="modal-body">
        <div class="form-group"><label>Controller Name / Description</label><input type="text" name="controller_name" class="form-control" required placeholder="e.g. Player 1 DualSense"></div>
        <div class="form-group"><label>Controller Type</label>
          <select name="controller_type" class="form-control" required>
            <option value="" disabled selected>Select Type</option>
            <option value="DualSense">DualSense (PS5)</option>
            <option value="DualShock 4">DualShock 4 (PS4)</option>
            <option value="Xbox Controller">Xbox Controller</option>
            <option value="Other">Other</option>
          </select></div>
        <div class="form-group"><label>Unit Number <span style="color:#888;font-size:11px;">(Must be unique)</span></label><input type="text" name="ctrl_unit_number" class="form-control" required placeholder="e.g. DS5-01"></div>
        <div class="form-group"><label>Notes <span style="color:#888;font-size:11px;">(Optional)</span></label><input type="text" name="controller_notes" class="form-control" placeholder="e.g. Slight drift on left stick"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addController')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Controller</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Tab switching ── */
function switchConsoleTab(tab) {
    const isConsole = tab === 'consoles';
    document.getElementById('consoleTab').style.display    = isConsole ? '' : 'none';
    document.getElementById('controllerTab').style.display = isConsole ? 'none' : '';
    const bc = 'border-bottom:2px solid #20c8a1;margin-bottom:-2px;color:#20c8a1;font-weight:600;font-size:13px;cursor:pointer;font-family:inherit;';
    const bi = 'border-bottom:2px solid transparent;margin-bottom:-2px;color:rgba(255,255,255,.4);font-weight:600;font-size:13px;cursor:pointer;font-family:inherit;';
    document.getElementById('tabBtnConsoles').style.cssText    = 'padding:10px 22px;background:none;border:none;' + (isConsole ? bc : bi);
    document.getElementById('tabBtnControllers').style.cssText = 'padding:10px 22px;background:none;border:none;' + (isConsole ? bi : bc);
}
function toggleArchiveSection(show) {
    document.getElementById('activeConsolesSection').style.display   = show ? 'none' : '';
    document.getElementById('archivedConsolesSection').style.display = show ? '' : 'none';
}
function toggleCtrlArchive(show) {
    document.getElementById('activeControllersSection').style.display   = show ? 'none' : '';
    const arc = document.getElementById('archivedControllersSection');
    if (arc) arc.style.display = show ? '' : 'none';
}

/* ── Console search + pagination ── */
(function(){
    const si = document.getElementById('consoleSearch');
    const sf = document.getElementById('consoleStatusFilter');
    const g  = document.getElementById('consoleGrid');
    const pag = new AdminCardPaginator('#consoleGrid','.console-card',{pageSize:12,pageSizes:[12,24,48],paginationSel:'#consolePagination',noResultsSel:'#consoleSearch_noResults',countSel:'#consoleCount'});
    function filter(){
        if(!g)return;
        const q=(si?.value||'').trim().toLowerCase(), st=(sf?.value||'').toLowerCase();
        g.querySelectorAll('.console-card').forEach(c=>{
            const cs=['available','in_use','maintenance'].find(s=>c.classList.contains(s))||'';
            c.classList.toggle('asb-hidden',!((!q||c.innerText.toLowerCase().includes(q))&&(!st||cs===st)));
        });
        const cb=si?.parentElement?.querySelector('.asb-clear');
        if(cb)cb.style.display=q?'block':'none';
        pag.reset();
    }
    if(si)si.addEventListener('input',filter);
    if(sf)sf.addEventListener('change',filter);
    const cb=si?.parentElement?.querySelector('.asb-clear');
    if(cb)cb.addEventListener('click',()=>{si.value='';filter();si.focus();});
    pag.apply();
})();

/* ── Controller search + pagination ── */
(function(){
    const si = document.getElementById('ctrlSearch');
    const sf = document.getElementById('ctrlStatusFilter');
    const g  = document.getElementById('ctrlGrid');
    if(!g)return;
    const pag = new AdminCardPaginator('#ctrlGrid','.console-card',{pageSize:12,pageSizes:[12,24,48],paginationSel:'#ctrlPagination',noResultsSel:'#ctrlSearch_noResults',countSel:'#ctrlCount'});
    function filter(){
        const q=(si?.value||'').trim().toLowerCase(), st=(sf?.value||'').toLowerCase();
        g.querySelectorAll('.console-card').forEach(c=>{
            const cs=['available','in_use','maintenance'].find(s=>c.classList.contains(s))||'';
            c.classList.toggle('asb-hidden',!((!q||c.innerText.toLowerCase().includes(q))&&(!st||cs===st)));
        });
        const cb=si?.parentElement?.querySelector('.asb-clear');
        if(cb)cb.style.display=q?'block':'none';
        pag.reset();
    }
    if(si)si.addEventListener('input',filter);
    if(sf)sf.addEventListener('change',filter);
    const cb=si?.parentElement?.querySelector('.asb-clear');
    if(cb)cb.addEventListener('click',()=>{si.value='';filter();si.focus();});
    pag.apply();
})();
</script>
