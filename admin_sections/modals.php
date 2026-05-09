<!-- ════ MODALS ══════════════════════════════════════════════════════════════ -->

<!-- ════ CUSTOM CONFIRM DIALOG ══════════════════════════════════════════════ -->
<div id="gspotConfirmModal" style="
    display:none;position:fixed;inset:0;z-index:99999;
    background:rgba(0,0,0,0.72);backdrop-filter:blur(6px);
    align-items:center;justify-content:center;
">
    <div style="
        background:linear-gradient(145deg,#0d1b3e,#08101c);
        border:1px solid rgba(95,133,218,0.3);
        border-radius:18px;
        padding:32px 30px 28px;
        max-width:400px;width:90%;
        box-shadow:0 24px 64px rgba(0,0,0,0.6),0 0 0 1px rgba(32,200,161,0.08);
        animation:gspotConfirmIn .22s cubic-bezier(.34,1.56,.64,1);
        position:relative;
    ">
        <!-- Icon -->
        <div style="
            width:52px;height:52px;border-radius:14px;
            background:rgba(241,168,60,0.15);border:1px solid rgba(241,168,60,0.3);
            display:flex;align-items:center;justify-content:center;
            font-size:22px;color:#f1a83c;
            margin:0 auto 18px;
        ">
            <i class="fas fa-circle-question"></i>
        </div>
        <!-- Message -->
        <p id="gspotConfirmMsg" style="
            text-align:center;font-size:15px;font-weight:600;
            color:#e8eaf0;margin:0 0 24px;line-height:1.55;
        "></p>
        <!-- Buttons -->
        <div style="display:flex;gap:10px;">
            <button id="gspotConfirmNo" class="btn-sec" style="flex:1;">
                <i class="fas fa-times" style="margin-right:6px"></i>Cancel
            </button>
            <button id="gspotConfirmYes" class="btn-prim" style="flex:1;">
                <i class="fas fa-check" style="margin-right:6px"></i>Confirm
            </button>
        </div>

    </div>
</div>
<style>
@keyframes gspotConfirmIn {
    from { opacity:0; transform:scale(.88) translateY(12px); }
    to   { opacity:1; transform:scale(1)  translateY(0); }
}
/* ── Modal design system ─────────────────────────────────────────── */
.modal { display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:16px; }
.modal.active { display:flex; }
.modal-content {
    background:linear-gradient(160deg,#0c1a38 0%,#080f1d 100%);
    border:1px solid rgba(95,133,218,.22);
    border-radius:20px;
    width:100%;max-width:580px;
    max-height:90vh;overflow-y:auto;overflow-x:hidden;
    box-shadow:0 32px 80px rgba(0,0,0,.7),0 0 0 1px rgba(32,200,161,.06);
    animation:modalSlideIn .25s cubic-bezier(.34,1.36,.64,1);
    scrollbar-width:thin;scrollbar-color:rgba(95,133,218,.25) transparent;
}
/* Wide variant — for multi-column content like controller rental */
.modal-content--wide { max-width:700px; }
.modal-content::-webkit-scrollbar{width:4px}
.modal-content::-webkit-scrollbar-thumb{background:rgba(95,133,218,.3);border-radius:4px}
@keyframes modalSlideIn {
    from { opacity:0; transform:translateY(20px) scale(.97); }
    to   { opacity:1; transform:translateY(0)    scale(1); }
}
.modal-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:20px 24px 16px;
    border-bottom:1px solid rgba(255,255,255,.06);
    position:sticky;top:0;
    background:linear-gradient(160deg,#0c1a38,#080f1d);
    z-index:2;
}
.modal-title { font-size:16px;font-weight:700;color:#e8eaf6;margin:0;display:flex;align-items:center;gap:8px; }
.modal-close {
    width:32px;height:32px;border-radius:8px;
    background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
    color:#aaa;font-size:18px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:.15s;line-height:1;
}
.modal-close:hover { background:rgba(251,86,107,.15);border-color:rgba(251,86,107,.4);color:#fb566b; }
.modal-body { padding:20px 24px 24px; }
/* Inputs & Selects */
.modal-body .form-group { margin-bottom:16px; }
.modal-body .form-group:last-child { margin-bottom:0; }
.modal-body label {
    display:block;font-size:11px;font-weight:700;
    text-transform:uppercase;letter-spacing:.6px;
    color:#6b7fa8;margin-bottom:7px;
}
.modal-body input[type="text"],
.modal-body input[type="number"],
.modal-body input[type="date"],
.modal-body input[type="time"],
.modal-body select,
.modal-body textarea {
    width:100%;box-sizing:border-box;
    padding:11px 14px;border-radius:10px;
    border:1px solid rgba(95,133,218,.2);
    background:rgba(255,255,255,.04);
    color:#e8eaf6;font-size:14px;
    font-family:inherit;
    transition:border-color .2s,box-shadow .2s,background .2s;
    outline:none;
}
.modal-body input:focus,
.modal-body select:focus,
.modal-body textarea:focus {
    border-color:rgba(95,133,218,.6);
    background:rgba(95,133,218,.07);
    box-shadow:0 0 0 3px rgba(95,133,218,.12);
}
.modal-body input:invalid:not(:placeholder-shown) { border-color:rgba(251,86,107,.5); }
.modal-body input[readonly] { cursor:not-allowed;opacity:.85; }
.modal-body select option { background:#0d1a35;color:#e8eaf6; }
/* Form rows */
.modal-body .form-row { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
@media(max-width:480px){ .modal-body .form-row { grid-template-columns:1fr; } }
/* Info banners */
.modal-banner {
    border-radius:12px;padding:14px 16px;margin-bottom:16px;
    display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:1.5;
}
.modal-banner.info  { background:rgba(95,133,218,.09);border:1px solid rgba(95,133,218,.2);color:#8aa4e8; }
.modal-banner.warn  { background:rgba(241,168,60,.09);border:1px solid rgba(241,168,60,.3);color:#f1a83c; }
.modal-banner.danger{ background:rgba(251,86,107,.09);border:1px solid rgba(251,86,107,.3);color:#fb566b; }
.modal-banner.success{background:rgba(32,200,161,.09);border:1px solid rgba(32,200,161,.25);color:#20c8a1; }
.modal-banner i { margin-top:2px;flex-shrink:0; }
/* Section divider */
.modal-divider { border:none;border-top:1px solid rgba(255,255,255,.07);margin:18px 0; }
/* Prominent cost display */
.cost-display {
    border-radius:14px;padding:18px 20px;margin-bottom:16px;text-align:center;
    background:linear-gradient(135deg,rgba(32,200,161,.1),rgba(32,200,161,.04));
    border:1px solid rgba(32,200,161,.3);
}
.cost-display .cost-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin-bottom:6px; }
.cost-display .cost-amount { font-size:42px;font-weight:900;color:#20c8a1;line-height:1;letter-spacing:-1px; }
.cost-display.danger { background:linear-gradient(135deg,rgba(251,86,107,.12),rgba(241,168,60,.06));border-color:rgba(251,86,107,.3); }
.cost-display.danger .cost-amount { color:#fb566b; }
/* Locked-field style (pre-filled, read-only) */
.field-locked {
    border-color:rgba(95,133,218,.35)!important;
    background:rgba(95,133,218,.07)!important;
    color:#8aa4e8!important;
    cursor:not-allowed!important;
}
.field-unlocked {
    border-color:rgba(255,255,255,.2)!important;
    background:rgba(255,255,255,.06)!important;
    color:#fff!important;
    cursor:text!important;
}
/* Change display pill */
.change-pill {
    border-radius:10px;padding:10px 14px;font-size:15px;font-weight:800;
    margin-top:8px;display:none;
}
.change-pill.positive { background:rgba(32,200,161,.12);border:1px solid rgba(32,200,161,.3);color:#20c8a1; }
.change-pill.negative { background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.3);color:#f1a83c; }
/* Submit buttons */
.modal-body .btn-full { width:100%;justify-content:center;padding:13px 20px;font-size:15px;font-weight:700;border-radius:12px; }
/* Required star */
.req { color:#fb566b;margin-left:2px; }
/* Constraint hint */
.field-hint { font-size:11px;color:#556;margin-top:5px;line-height:1.4; }
.field-hint.warn { color:#8a6630; }
/* Hide number spinner arrows */
.modal-body input[type=number]::-webkit-inner-spin-button,
.modal-body input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none;margin:0; }
.modal-body input[type=number] { -moz-appearance:textfield; }
/* Tendered wrapper states */
.tendered-wrapper-locked  { border-color:rgba(95,133,218,.4)!important; background:rgba(95,133,218,.07)!important; }
.tendered-wrapper-locked  .tendered-prefix { color:#5f85da!important; }
.tendered-wrapper-locked  input { color:#8aa4e8!important; }
.tendered-wrapper-locked  .tendered-lock  { color:#5f85da!important; }
.tendered-wrapper-unlocked{ border-color:rgba(32,200,161,.5)!important; background:rgba(32,200,161,.05)!important; }
.tendered-wrapper-unlocked .tendered-prefix { color:#20c8a1!important; }
.tendered-wrapper-unlocked input { color:#fff!important; }
.tendered-wrapper-unlocked .tendered-lock  { color:#20c8a1!important; }
/* Shake animation for insufficient payment */
@keyframes shakeX {
    0%,100% { transform:translateX(0); }
    20%     { transform:translateX(-8px); }
    40%     { transform:translateX(8px); }
    60%     { transform:translateX(-5px); }
    80%     { transform:translateX(5px); }
}
/* Start Session button disabled state */
#startSessionForm .btn-prim:disabled {
    background:rgba(100,100,120,.3)!important;
    box-shadow:none!important;
    cursor:not-allowed!important;
    opacity:.55!important;
}
/* Controller rental duration dropdown (Start Session) */
.admin-ctrl-dur-select {
    width:100%;
    background:rgba(10,33,81,.6);
    border:1px solid rgba(95,133,218,.25);
    color:#e8eaf6;
    padding:8px 10px;
    border-radius:8px;
    font-size:13px;
    outline:none;
    cursor:pointer;
}
.admin-ctrl-dur-select:disabled { opacity:0.5; cursor:not-allowed; }
/* Two-column controller grid responsive collapse */
@media(max-width:520px){
    #controllerSelectContainer > div[style*="grid-template-columns"] {
        grid-template-columns:1fr !important;
    }
    #adminCtrl2Block { border-right:none !important; border-top:1px solid rgba(95,133,218,.1); }
}

</style>

<!-- ════ CUSTOMER SEARCH WIDGET — shared CSS ═══════════════════════════ -->
<style>
/* ── Customer search widget ────────────────────────────────────────── */
.cs-wrap        { position:relative; }
.cs-input-row   { position:relative; display:flex; align-items:center; }
.cs-icon        { position:absolute;left:13px;top:50%;transform:translateY(-50%);
                  color:#5f85da;font-size:13px;pointer-events:none;z-index:1; }
.cs-input       { width:100%;box-sizing:border-box;
                  padding:11px 38px 11px 36px;border-radius:10px;
                  border:1px solid rgba(95,133,218,.2);
                  background:rgba(255,255,255,.04);
                  color:#e8eaf6;font-size:14px;font-family:inherit;
                  transition:border-color .2s,box-shadow .2s,background .2s;
                  outline:none; }
.cs-input:focus { border-color:rgba(95,133,218,.6);
                  background:rgba(95,133,218,.07);
                  box-shadow:0 0 0 3px rgba(95,133,218,.12); }
.cs-clear       { position:absolute;right:10px;top:50%;transform:translateY(-50%);
                  background:none;border:none;color:#5f85da;cursor:pointer;
                  font-size:15px;padding:4px;opacity:0;pointer-events:none;
                  transition:opacity .15s; }
.cs-clear.visible { opacity:1;pointer-events:auto; }
.cs-dropdown    { position:absolute;top:calc(100% + 5px);left:0;right:0;
                  background:linear-gradient(160deg,#0c1a38,#080f1d);
                  border:1px solid rgba(95,133,218,.3);border-radius:12px;
                  max-height:220px;overflow-y:auto;z-index:99999;
                  box-shadow:0 12px 36px rgba(0,0,0,.65);
                  scrollbar-width:thin;scrollbar-color:rgba(95,133,218,.25) transparent;
                  display:none; }
.cs-dropdown.open { display:block; }
.cs-item        { padding:11px 14px;cursor:pointer;
                  display:flex;align-items:center;gap:10px;
                  border-bottom:1px solid rgba(255,255,255,.04);
                  transition:background .12s; }
.cs-item:last-child { border-bottom:none; }
.cs-item:hover, .cs-item.active { background:rgba(95,133,218,.14); }
.cs-item-avatar { width:30px;height:30px;border-radius:50%;
                  background:linear-gradient(135deg,#5f85da,#20c8a1);
                  display:flex;align-items:center;justify-content:center;
                  font-size:12px;font-weight:700;color:#fff;flex-shrink:0; }
.cs-item-name   { font-size:13px;font-weight:600;color:#e8eaf6; }
.cs-item-email  { font-size:11px;color:#667;margin-top:1px; }
.cs-selected    { display:none;align-items:center;gap:10px;
                  background:rgba(32,200,161,.08);
                  border:1px solid rgba(32,200,161,.25);border-radius:10px;
                  padding:10px 14px;margin-top:0; }
.cs-selected.show { display:flex; }
.cs-selected-avatar { width:34px;height:34px;border-radius:50%;
                  background:linear-gradient(135deg,#20c8a1,#5f85da);
                  display:flex;align-items:center;justify-content:center;
                  font-size:13px;font-weight:700;color:#fff;flex-shrink:0; }
.cs-selected-info { flex:1;min-width:0; }
.cs-selected-name  { font-size:13px;font-weight:700;color:#20c8a1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.cs-selected-email { font-size:11px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.cs-deselect    { background:none;border:none;color:#fb566b;cursor:pointer;
                  font-size:16px;padding:4px;flex-shrink:0;transition:opacity .15s; }
.cs-deselect:hover { opacity:.7; }
.cs-status      { padding:12px 14px;font-size:12px;color:#667;text-align:center; }
.cs-highlight   { color:#8aa4e8;font-weight:700; }
/* Validation failure state */
.cs-error       { border-color:rgba(251,86,107,.6)!important;box-shadow:0 0 0 3px rgba(251,86,107,.12)!important; }
</style>

<script>
(function(){
    const modal   = document.getElementById('gspotConfirmModal');
    const msgEl   = document.getElementById('gspotConfirmMsg');
    const yesBtn  = document.getElementById('gspotConfirmYes');
    const noBtn   = document.getElementById('gspotConfirmNo');
    let _cb       = null;

    window.gspotConfirm = function(message, callback, opts) {
        opts = opts || {};
        msgEl.textContent = message;

        // Customise Yes button label/colour for destructive actions
        if (opts.danger) {
            yesBtn.style.background = 'linear-gradient(135deg,#fb566b,#c0392b)';
            yesBtn.style.boxShadow  = '0 4px 16px rgba(251,86,107,0.35)';
        } else {
            yesBtn.style.background = 'linear-gradient(135deg,#20c8a1,#5f85da)';
            yesBtn.style.boxShadow  = '0 4px 16px rgba(32,200,161,0.3)';
        }
        yesBtn.innerHTML = '<i class="fas fa-check" style="margin-right:6px"></i>' + (opts.yesLabel || 'Confirm');

        _cb = callback;
        modal.style.display = 'flex';
        /* micro-delay so the CSS animation fires on each open */
        modal.firstElementChild.style.animation = 'none';
        requestAnimationFrame(() => { modal.firstElementChild.style.animation = ''; });
    };

    yesBtn.addEventListener('click', function() {
        modal.style.display = 'none';
        if (typeof _cb === 'function') _cb();
        _cb = null;
    });
    noBtn.addEventListener('click', function() {
        modal.style.display = 'none';
        _cb = null;
    });
    modal.addEventListener('click', function(e) {
        if (e.target === modal) { modal.style.display = 'none'; _cb = null; }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            modal.style.display = 'none'; _cb = null;
        }
    });
})();
</script>

<!-- Start Session Modal -->
<div class="modal" id="startSessionModal">
    <div class="modal-content modal-content--wide">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-play-circle" style="color:#20c8a1;margin-right:8px"></i>Start New Session</h3>
            <button class="modal-close" onclick="closeModal('startSession')">&times;</button>
        </div>
        <div class="modal-body">
        <form method="POST" id="startSessionForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="start_session">
            <input type="hidden" name="planned_minutes" id="plannedMinutesInput" value="">
            <input type="hidden" name="user_id" id="ssUserId" value="">
            <input type="hidden" name="unit_number" id="ssUnitNumber" value="">
            <div class="form-group">
                <label>Customer <span style="color:#888;font-size:11px;font-weight:400;">(optional — leave blank for walk-in)</span></label>
                <div class="cs-wrap" id="ssWrap">
                    <div class="cs-input-row">
                        <input type="text" id="ssQuery" class="cs-input"
                               placeholder="Search customer by name or email…"
                               autocomplete="off"
                               oninput="csSearch('ss')"
                               onkeydown="csKeyNav(event,'ss')">
                        <button type="button" class="cs-clear" id="ssClear"
                                onclick="csDeselect('ss','walk-in')">&times;</button>
                    </div>
                    <div class="cs-dropdown" id="ssDropdown" role="listbox"></div>
                    <div class="cs-selected" id="ssSelected">
                        <div class="cs-selected-avatar" id="ssSelAvatar"></div>
                        <div class="cs-selected-info">
                            <div class="cs-selected-name" id="ssSelName"></div>
                            <div class="cs-selected-email" id="ssSelEmail"></div>
                        </div>
                        <button type="button" class="cs-deselect" title="Change customer"
                                onclick="csDeselect('ss','walk-in')">&times;</button>
                    </div>
                    <p class="field-hint" id="ssHint">Leave empty to record as a walk-in.</p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Console *</label>
                    <select name="console_id" id="consoleSelect" required onchange="onConsoleChange()">
                        <option value="" disabled selected>— Select console —</option>
                        <?php foreach ($availableConsoles as $con): ?>
                        <option value="<?= $con['console_id'] ?>"
                                data-type="<?= htmlspecialchars($con['console_type']) ?>"
                                data-rate="<?= (float)$con['hourly_rate'] ?>"
                                data-compat="<?= htmlspecialchars($con['compatible_controller_type'] ?? '') ?>">
                            <?= htmlspecialchars($con['unit_number']) ?> — <?= $con['console_type'] ?> (₱<?= $con['hourly_rate'] ?>/hr)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rental Mode *</label>
                    <select name="rental_mode" id="rentalModeSelect" required onchange="onRentalModeChange()">
                        <option value="hourly">Hourly (pre-booked)</option>
                        <option value="open_time">Open Time (bracket pricing)</option>
                        <option value="unlimited" id="ssUnlimOption">Unlimited (flat ₱<?= htmlspecialchars($settings['unlimited_rate'] ?? '300') ?>)</option>
                    </select>
                    <div id="ssUnlimRestrictedMsg" style="display:none; font-size:10px; color:#fb566b; margin-top:4px; font-weight:600;">
                        <i class="fas fa-exclamation-triangle"></i> Unlimited mode unavailable after 7:00 PM
                    </div>
                </div>
            </div>

            <!-- Duration picker — shown only for Hourly mode -->
            <div class="form-group" id="durationPickerGroup">
                <label>Duration *</label>
                <select id="durationSelect" onchange="updateSessionPreview()">
                    <option value="" disabled selected>— Select duration —</option>
                    <?php foreach (getHourlyDurationOptions() as $opt): ?>
                    <option value="<?= $opt['paid'] ?>"
                            data-cost="<?= $opt['cost'] ?>"
                            data-total="<?= $opt['total'] ?>">
                        <?= $opt['label_paid'] ?> — ₱<?= number_format($opt['cost'], 0) ?>
                        <?= $opt['bonus'] > 0 ? '(+' . $opt['label_bonus'] . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php $pr = getPricingRules(); ?>
                <div style="margin-top:7px;font-size:12px;color:rgba(241,168,60,.85);display:flex;align-items:flex-start;gap:6px;">
                    <i class="fas fa-info-circle" style="margin-top:2px;"></i>
                    <span>Max <?= $pr['max_hourly_minutes'] / 60 ?> hrs for hourly; for longer play, use <strong style="color:#f1a83c;">Unlimited</strong> mode (flat &#8369;<?= number_format(getSetting('unlimited_rate'), 0) ?>).</span>
                </div>
            </div>

            <!-- Preview card -->
            <div id="sessionPreview" style="display:none;background:rgba(32,200,161,.08);border:1px solid rgba(32,200,161,.25);border-radius:10px;padding:16px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Scheduled End</div>
                        <div id="previewEndTime" style="font-size:20px;font-weight:700;color:#20c8a1;">—</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Session Cost</div>
                        <div id="previewCost" style="font-size:20px;font-weight:700;color:#f1e1aa;">—</div>
                    </div>
                </div>
                <div id="previewOvertime" style="margin-top:10px;font-size:12px;color:#fb566b;display:none;">
                    <i class="fas fa-exclamation-triangle"></i> Overtime charges apply after scheduled end time
                </div>
            </div>

<?php
// Load specific available controllers grouped by console type
$ctrlAvailListByType = [];
$ctrlRes = $conn->query(
    "SELECT cs.type_name AS console_type,
            c.controller_id, c.unit_number, c.hourly_rate
       FROM controllers c
       JOIN controller_types ct ON ct.type_id = c.controller_type_id
       JOIN console_types cs ON cs.type_id = ct.console_type_id
       WHERE c.status = 'available'
       ORDER BY c.unit_number ASC"
);
if ($ctrlRes) {
    while ($row = $ctrlRes->fetch_assoc()) {
        $ctrlAvailListByType[$row['console_type']][] = [
            'id' => (int)$row['controller_id'],
            'unit' => $row['unit_number'],
            'rate' => (float)$row['hourly_rate']
        ];
    }
}
$ctrlAvailListJson = json_encode($ctrlAvailListByType, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script>
/* Specific controllers by console type — used by onConsoleChange() */
const CTRL_LIST_BY_TYPE = <?= $ctrlAvailListJson ?>;
</script>

<div id="controllerRentalGroup" style="display:none; margin-bottom:16px;">
    <div style="background:rgba(95,133,218,.06);border:1px solid rgba(95,133,218,.18);border-radius:14px;overflow:hidden;">

        <!-- Header toggle row -->
        <div style="padding:14px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(95,133,218,.12);">
            <input type="checkbox" id="controllerRentalToggle" name="controller_rental" value="1"
                   onchange="onControllerToggle()" style="width:16px;height:16px;accent-color:#20c8a1;cursor:pointer;flex-shrink:0;">
            <label for="controllerRentalToggle" id="controllerRentalLabel"
                   style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1;margin:0;">
                <i class="fas fa-gamepad" id="ctrlRentalIcon" style="color:#20c8a1;font-size:15px;"></i>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:13px;color:#20c8a1;letter-spacing:.3px;">
                        Add Controller Rental
                        <span id="ctrlRateDisplay" style="font-size:11px;font-weight:600;color:#f1a83c;margin-left:6px;"></span>
                    </div>
                    <div id="ctrlAvailText" style="font-size:11px;margin-top:2px;color:#888;"></div>
                </div>
            </label>
            <!-- Quantity selector — only visible when checked -->
            <div id="ctrlQtyWrap" style="display:none;">
                <select name="controller_count" id="adminControllerCount" onchange="onAdminControllerToggle()"
                        style="background:rgba(10,33,81,.8);color:#f0f0f0;border:1px solid rgba(95,133,218,.3);border-radius:8px;padding:6px 10px;font-size:12px;font-weight:700;outline:none;cursor:pointer;">
                    <option value="1">1 Controller</option>
                    <option value="2">2 Controllers</option>
                </select>
            </div>
        </div>

        <!-- Controller selection body (hidden until checked) -->
        <div id="controllerSelectContainer" style="display:none;">

            <!-- Two-column grid: ctrl 1 | ctrl 2 -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;border-top:1px solid rgba(95,133,218,.1);">

                <div id="adminCtrlOpenTimeRunClockNote" style="display:none;grid-column:1/-1;padding:12px 18px;background:rgba(95,133,218,.09);border-bottom:1px solid rgba(95,133,218,.1);font-size:12px;color:rgba(215,220,245,.95);line-height:1.5;">
                    <i class="fas fa-clock" style="color:#5f85da;margin-right:8px;"></i>
                    <strong>Open Time (controller)</strong> — For any controller set to <em>Open Time</em>, there is no duration pick: cost uses the same open-time structure as the console, run for the full session, priced at that controller’s hourly rate when you end the session.
                </div>

                <!-- Controller 1 -->
                <div id="adminCtrl1Block" style="padding:16px 18px;border-right:1px solid rgba(95,133,218,.1);">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5f85da;margin-bottom:10px;">
                        <i class="fas fa-gamepad" style="margin-right:4px;"></i> Controller 1
                    </div>
                    <select name="rented_controller_id" id="controllerSelect"
                            style="width:100%;background:rgba(10,33,81,.6);border:1px solid rgba(95,133,218,.25);color:#e8eaf6;padding:8px 10px;border-radius:8px;font-size:13px;outline:none;margin-bottom:12px;"
                            onchange="onControllerSelectChange()">
                        <!-- Populated by onConsoleChange() -->
                    </select>
                    <div class="admin-ctrl-mode-wrap">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:8px;">
                            <i class="fas fa-sliders-h" style="margin-right:3px;"></i> Rental mode
                        </div>
                        <select name="controller_rental_mode_1" id="adminCtrlModeSelect1" class="admin-ctrl-dur-select"
                                onchange="onAdminCtrlRentalModeChange(1)" style="margin-bottom:12px;">
                            <option value="hourly">Hourly (set duration)</option>
                            <option value="open_time">Open Time (session clock)</option>
                        </select>
                    </div>
                    <div id="adminCtrlDurWrap1" class="admin-ctrl-dur-wrap">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:8px;">
                        <i class="fas fa-clock" style="margin-right:3px;"></i> Duration
                    </div>
                    <?php
                    $admin_ctrl_duration_opts = [];
                    for ($__m = 30; $__m <= 720; $__m += 30) {
                        $admin_ctrl_duration_opts[] = $__m;
                    }
                    $admin_ctrl_dur_lbl = function (int $mins): string {
                        $h = intdiv($mins, 60);
                        $r = $mins % 60;
                        if ($h > 0 && $r > 0) {
                            return "{$h}h {$r}m";
                        }
                        if ($h > 0) {
                            return $h === 1 ? '1 hr' : "{$h} hrs";
                        }
                        return "{$mins} min";
                    };
                    ?>
                    <select name="controller_rental_minutes" id="adminCtrlDurationSelect1" class="admin-ctrl-dur-select"
                            onchange="onAdminCtrlDurationChange(1)">
                        <option value="0">— Select duration —</option>
                        <?php foreach ($admin_ctrl_duration_opts as $mins): ?>
                        <option value="<?= $mins ?>"><?= $admin_ctrl_dur_lbl($mins) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                    <div id="adminCtrlCostPreview1" style="display:none;margin-top:8px;font-size:12px;color:#f1a83c;font-weight:700;"></div>
                </div>

                <!-- Controller 2 (hidden by default) -->
                <div id="adminCtrl2Block" style="display:none;padding:16px 18px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5f85da;margin-bottom:10px;">
                        <i class="fas fa-gamepad" style="margin-right:4px;"></i> Controller 2
                    </div>
                    <select name="rented_controller_id_2" id="controllerSelect2"
                            style="width:100%;background:rgba(10,33,81,.6);border:1px solid rgba(95,133,218,.25);color:#e8eaf6;padding:8px 10px;border-radius:8px;font-size:13px;outline:none;margin-bottom:12px;"
                            onchange="onControllerSelectChange()">
                        <!-- Populated by onConsoleChange() -->
                    </select>
                    <div class="admin-ctrl-mode-wrap">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:8px;">
                            <i class="fas fa-sliders-h" style="margin-right:3px;"></i> Rental mode
                        </div>
                        <select name="controller_rental_mode_2" id="adminCtrlModeSelect2" class="admin-ctrl-dur-select"
                                onchange="onAdminCtrlRentalModeChange(2)" style="margin-bottom:12px;">
                            <option value="hourly">Hourly (set duration)</option>
                            <option value="open_time">Open Time (session clock)</option>
                        </select>
                    </div>
                    <div id="adminCtrlDurWrap2" class="admin-ctrl-dur-wrap">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:8px;">
                        <i class="fas fa-clock" style="margin-right:3px;"></i> Duration
                    </div>
                    <select name="controller_rental_minutes_2" id="adminCtrlDurationSelect2" class="admin-ctrl-dur-select"
                            onchange="onAdminCtrlDurationChange(2)">
                        <option value="0">— Select duration —</option>
                        <?php foreach ($admin_ctrl_duration_opts as $mins): ?>
                        <option value="<?= $mins ?>"><?= $admin_ctrl_dur_lbl($mins) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                    <div id="adminCtrlCostPreview2" style="display:none;margin-top:8px;font-size:12px;color:#f1a83c;font-weight:700;"></div>
                </div>

            </div><!-- /grid -->

            <input type="hidden" name="controller_rental_fee_amt" id="controllerFeeAmt" value="0">

            <div id="ctrlDurationCapHint" style="display:none;font-size:11px;color:rgba(200,205,230,.88);padding:10px 18px 14px;border-top:1px solid rgba(95,133,218,.12);line-height:1.45;"></div>
        </div><!-- /controllerSelectContainer -->
    </div>
</div>


            <!-- ── Optional upfront payment (hourly only) ── -->
            <div id="startPaymentGroup" style="display:none;">
                <div style="background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.25);border-radius:10px;padding:14px;margin-bottom:16px;">
                    <label class="form-check" style="margin-bottom:12px;cursor:pointer;">
                        <input type="checkbox" id="collectNowToggle" name="collect_upfront" value="1"
                               onchange="toggleStartPaymentFields(this)">
                        <span style="font-size:13px;font-weight:600;color:#f0f0f0;">
                            <i class="fas fa-peso-sign" style="color:#20c8a1;margin-right:4px;"></i>
                            Collect payment now (optional)
                        </span>
                    </label>
                    <div id="startPaymentFields" style="display:none;">
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="start_payment_method" id="startPaymentMethodSelect">
                                <option value="cash">💵 Cash</option>
                                <option value="gcash">📱 GCash</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:6px">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                <label style="font-size:11px;color:#6b7fa8;text-transform:uppercase;letter-spacing:.6px;margin:0;">Amount Tendered</label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#888;cursor:pointer;font-weight:400;">
                                    <input type="checkbox" id="startTenderedToggle"
                                           style="width:13px;height:13px;accent-color:#8aa4e8;cursor:pointer;"
                                           onchange="toggleTendered('startTendered','startTenderedToggle','startCostAmt','startChangeDisplay')">
                                    <span style="color:#8aa4e8;">Different amount</span>
                                </label>
                            </div>
                            <!-- Flex row: ₱ prefix | input | lock icon -->
                            <div id="startTenderedWrapper" style="display:flex;align-items:center;border-radius:12px;border:1.5px solid rgba(95,133,218,.4);background:rgba(95,133,218,.07);overflow:hidden;transition:.2s;">
                                <span class="tendered-prefix" style="padding:0 4px 0 16px;font-size:22px;font-weight:900;color:#5f85da;flex-shrink:0;line-height:1;">₱</span>
                                <input type="number" id="startTendered" name="start_tendered" min="0" step="1" readonly
                                       style="flex:1;border:none;background:transparent;color:#8aa4e8;font-size:22px;font-weight:800;padding:14px 8px;outline:none;appearance:none;-moz-appearance:textfield;min-width:0;"
                                       oninput="calcChange('startTendered','startChangeDisplay','startCostAmt'); _syncStartBtn()">
                                <i id="startTenderedIcon" class="fas fa-lock"
                                   style="padding:0 16px;font-size:14px;color:#5f85da;flex-shrink:0;cursor:pointer;"
                                   title="Click to enter a different amount"
                                   onclick="var cb=document.getElementById('startTenderedToggle');cb.checked=!cb.checked;toggleTendered('startTendered','startTenderedToggle','startCostAmt','startChangeDisplay');"></i>
                            </div>
                            <p class="field-hint" id="startTenderedHintText">Pre-filled with session cost. Tick to enter a different amount.</p>
                        </div>
                        <span id="startCostAmt" style="display:none">0</span>
                        <div id="startChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:15px;font-weight:700;margin-bottom:4px;"></div>
                    </div>
                </div>
            </div>

            <!-- ── Mandatory upfront payment (unlimited only — fixed flat rate) ── -->
            <div id="unlimitedPaymentGroup" style="display:none;">
                <div style="background:rgba(241,225,170,.06);border:1px solid rgba(241,225,170,.25);border-radius:10px;padding:14px;margin-bottom:16px;">
                    <div style="font-size:12px;color:#f1e1aa;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
                        <i class="fas fa-infinity"></i> Collect Flat Rate Now
                    </div>
                    <div style="font-size:22px;font-weight:800;color:#f1e1aa;margin-bottom:12px;">
                        ₱<?= htmlspecialchars($settings['unlimited_rate'] ?? '300') ?>
                        <span style="font-size:12px;font-weight:400;color:#888;margin-left:6px;">fixed — no additional charge at end</span>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="unlimited_payment_method" id="unlimitedPaymentMethodSelect">
                            <option value="cash">💵 Cash</option>
                            <option value="gcash">📱 GCash</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:6px">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <label style="font-size:11px;color:#6b7fa8;text-transform:uppercase;letter-spacing:.6px;margin:0;">Amount Tendered</label>
                            
                        </div>
                        <!-- Flex row: ₱ prefix | input | lock icon -->
                        <div id="unlimTenderedWrapper" class="tendered-wrapper-unlocked" style="display:flex;align-items:center;border-radius:12px;border:1.5px solid rgba(241,225,170,.4);background:rgba(241,225,170,.07);overflow:hidden;transition:.2s;">
                            <span class="tendered-prefix" style="padding:0 4px 0 16px;font-size:22px;font-weight:900;color:#f1e1aa;flex-shrink:0;line-height:1;">₱</span>
                            <input type="number" id="unlimTendered" name="unlimited_tendered" min="<?=\htmlspecialchars($settings['unlimited_rate'] ?? 400)?>" step="1"
                                   style="flex:1;border:none;background:transparent;color:#f1e1aa;font-size:22px;font-weight:800;padding:14px 8px;outline:none;appearance:none;-moz-appearance:textfield;min-width:0;" placeholder="Enter amount..."
                                   oninput="calcChange('unlimTendered','unlimChangeDisplay','unlimCostAmt'); _syncStartBtn()">
                            
                        </div>
                        <p class="field-hint" id="unlimTenderedHintText">Exact or greater amount must be paid upfront.</p>
                    </div>
                    <div id="unlimChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:15px;font-weight:700;margin-bottom:4px;"></div>
                    <!-- Hidden cost holder for JS -->
                    <span id="unlimCostAmt" style="display:none"><?= $settings['unlimited_rate'] ?? 300 ?></span>
                </div>
            </div>


            <!-- ── Open Time reminder ── -->
            <div id="openTimeNote" style="display:none;background:rgba(95,133,218,.07);border:1px solid rgba(95,133,218,.2);border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:#8aa4e8;">
                <i class="fas fa-clock"></i> <strong>Open Time</strong> — no upfront payment needed. Cost is calculated and collected when the session ends.
            </div>

            <button type="submit" class="btn-prim btn-full">
                <i class="fas fa-play"></i> Start Session
            </button>

        </form>
        </div><!-- /.modal-body -->
    </div>
</div>

<!-- End Session Modal -->
<div class="modal" id="endSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="endSessionModalTitle"><i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session &amp; Collect Payment</h3>
            <button class="modal-close" onclick="closeModal('endSession')">&times;</button>
        </div>

        <div class="modal-body">
        <!-- Session info summary -->
        <div class="modal-banner danger" style="font-size:14px;">
            <i class="fas fa-stop-circle"></i>
            <strong id="endSessionSummary">—</strong>
        </div>

        <!-- Reservation-source notice (hidden until JS detects source_reservation_id > 0) -->
        <div id="endSessionResNotice"
             style="display:none;align-items:center;gap:10px;background:rgba(95,133,218,.1);
                    border:1px solid rgba(95,133,218,.35);border-radius:10px;
                    padding:10px 14px;margin-bottom:12px;font-size:13px;color:#8aa4e8;">
            <i class="fas fa-calendar-check" style="font-size:16px;flex-shrink:0;"></i>
            <div>
                <span style="font-weight:700;color:#f1e1aa;">From Reservation</span>
                <span class="res-notice-id" style="font-weight:700;color:#20c8a1;margin-left:4px;"></span>
                — upfront payment includes the reservation downpayment.
                <span class="res-nonrefundable-note" style="display:block;margin-top:3px;font-size:11px;color:#fb566b;font-weight:600;"></span>
            </div>
        </div>

        <!-- End controller add-on early (session continues) -->
        <div id="endCtrlRentalEarlyPanel" style="display:none;background:rgba(95,133,218,.1);border:1px solid rgba(95,133,218,.35);border-radius:12px;padding:14px 16px;margin-bottom:14px;">
            <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <div style="font-weight:700;color:#8aa4e8;font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-gamepad" style="margin-right:6px;"></i> Controller add-on
                    </div>
                    <div style="font-size:12px;color:#aaa;line-height:1.45;">
                        Customer returned extra controller(s) before this session ends. Re-rate the add-on to <strong style="color:#f1e1aa;">elapsed time now</strong>, reduce the line in Additional Requests, and mark units available. Totals in this modal refresh automatically.
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:stretch;gap:8px;min-width:160px;">
                    <button type="button" id="endCtrlRentalEarlyBtn" class="btn-sec" style="white-space:nowrap;padding:10px 14px;font-size:12px;">
                        <i class="fas fa-hand-holding"></i> End add-on &amp; recalculate
                    </button>
                    <span id="endCtrlRentalEarlyMsg" style="font-size:11px;color:#888;min-height:16px;"></span>
                </div>
            </div>
        </div>

        <!-- ── Early-end warning (shown when hourly session still has time left) ── -->
        <div id="endEarlyWarning" style="display:none;background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.45);border-radius:12px;padding:18px 20px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(241,168,60,.2);border:1px solid rgba(241,168,60,.4);display:flex;align-items:center;justify-content:center;font-size:18px;color:#f1a83c;flex-shrink:0;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                    <div style="font-weight:700;color:#f1e1aa;font-size:14px;margin-bottom:2px;">Session Time Not Yet Elapsed</div>
                    <div style="font-size:12px;color:#aaa;">The customer still has <strong id="endEarlyRemainingStr" style="color:#f1a83c;">—</strong> remaining on their booked session.</div>
                </div>
            </div>

            <!-- ── Refund Breakdown ── -->
            <div id="endEarlyBreakdown" style="background:rgba(0,0,0,.25);border-radius:10px;padding:14px 16px;margin-bottom:12px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:10px;">
                    <i class="fas fa-calculator" style="margin-right:5px;"></i> Refund Calculation
                </div>
                <!-- Row: Consumed -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;font-size:13px;">
                    <span style="color:#aaa;">
                        <i class="fas fa-play-circle" style="color:#20c8a1;margin-right:6px;font-size:11px;"></i>
                        Time Used <span id="endEarlyElapsedStr" style="color:#f1e1aa;font-family:monospace;font-size:12px;">(—)</span>
                    </span>
                    <span style="font-weight:700;color:#20c8a1;" id="endEarlyConsumedCost">₱0.00</span>
                </div>
                <!-- Row: Additional Fees (controller rental etc.) -->
                <div id="endEarlyExtrasRow" style="display:none;justify-content:space-between;align-items:center;margin-bottom:7px;font-size:13px;">
                    <span style="color:#aaa;">
                        <i class="fas fa-gamepad" style="color:#8aa4e8;margin-right:6px;font-size:11px;"></i>
                        Additional Fees <span id="endEarlyExtrasLabel" style="color:#8aa4e8;font-size:11px;"></span>
                    </span>
                    <span style="font-weight:700;color:#8aa4e8;" id="endEarlyExtrasAmt">+₱0.00</span>
                </div>
                <!-- Row: Paid -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;font-size:13px;padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,.07);">
                    <span style="color:#aaa;">
                        <i class="fas fa-receipt" style="color:#f1a83c;margin-right:6px;font-size:11px;"></i>
                        Amount Paid Upfront
                    </span>
                    <span style="font-weight:700;color:#f1e1aa;" id="endEarlyUpfrontStr">₱0.00</span>
                </div>
                <!-- Row: Refund -->
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;">
                    <span style="font-weight:700;color:#fff;">
                        <i class="fas fa-undo-alt" style="color:#fb566b;margin-right:6px;font-size:12px;"></i>
                        Refund to Customer
                    </span>
                    <span style="font-size:20px;font-weight:900;color:#fb566b;font-family:'Outfit',monospace;" id="endEarlyRefundAmt">₱0.00</span>
                </div>
                <div id="endEarlyNoRefundNote" style="display:none;margin-top:8px;font-size:12px;color:#888;text-align:right;">
                    <i class="fas fa-info-circle"></i> <span id="endEarlyNoRefundReason">No refund — consumed cost covers or exceeds amount paid.</span>
                </div>
            </div>

            <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:10px 14px;font-size:12px;color:#aaa;line-height:1.6;margin-bottom:12px;">
                <i class="fas fa-info-circle" style="color:#f1a83c;margin-right:5px;"></i>
                The customer only pays for <strong style="color:#f1e1aa;">time actually used</strong>. The refund is pre-calculated above.
            </div>

            <!-- Refund & End action -->
            <button type="button" id="endEarlyRefundBtn" class="btn-dang btn-full" style="padding:12px;">
                <i class="fas fa-undo-alt"></i>
                <span>Refund <span id="endEarlyRefundBtnAmt">—</span> &amp; End Session Early</span>
            </button>

        </div>


        <!-- Cost preview panel (shown for open_time; updated live) -->
        <div id="endCostPanel" style="display:none;background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Elapsed Time</div>
                    <div id="endElapsed" style="font-size:18px;font-weight:700;color:#f1e1aa;font-family:monospace;">—</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Estimated Cost</div>
                    <div id="endEstCost" style="font-size:26px;font-weight:800;color:#20c8a1;">—</div>
                    <!-- Extras pill badge (controller rental etc.) -->
                    <div id="endExtrasTag" style="display:none;margin-top:4px;font-size:11px;background:rgba(95,133,218,.15);border:1px solid rgba(95,133,218,.3);color:#8aa4e8;border-radius:20px;padding:2px 8px;display:inline-block;">
                        <i class="fas fa-gamepad" style="margin-right:3px;"></i><span id="endExtrasTagText"></span>
                    </div>
                </div>
            </div>
            <div id="endCostNote" style="margin-top:10px;font-size:12px;color:#aaa;"></div>
        </div>

        <form method="POST" id="endSessionForm" onsubmit="return syncTenderedAndSubmit(event)">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="session_id" id="endSessionId">
            <!-- Synced with the visible endTendered input on submit -->
            <input type="hidden" name="tendered_amount" id="endTenderedHidden">

            <!-- Shown for open_time / hourly-with-overtime; hidden for unlimited -->
            <div class="form-group" id="endPaymentMethodGroup">

                <!-- ── NEW: Transparent Billing Breakdown ── -->
                <div id="endSessionTransparentBreakdown" style="display:none; margin: 0 0 20px 0; padding: 18px; border-radius: 14px; background: rgba(95, 133, 218, 0.08); border: 1px solid rgba(95, 133, 218, 0.15); box-shadow: inset 0 0 15px rgba(0,0,0,0.2);">
                    <div style="font-size: 11px; font-weight: 700; color: #8aa4e8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-receipt" style="font-size: 13px;"></i> Payment Breakdown
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px; align-items: center;">
                            <span style="color: #aaa; font-weight: 500;">Base Session Fee (<span id="ebd-time-label" style="color:#f0f0f0;">0m</span>)</span>
                            <span style="color: #f0f0f0; font-weight: 600;" id="ebd-gross-cost">₱0.00</span>
                        </div>
                        <div id="ebd-extras-row" style="display: flex; justify-content: space-between; font-size: 14px; align-items: center;">
                            <span style="color: #aaa; font-weight: 500;">Additional Requests</span>
                            <span style="color: #f0f0f0; font-weight: 600;" id="ebd-extras-cost">₱0.00</span>
                        </div>
                        <div style="margin: 4px 0; height: 1px; background: rgba(255,255,255,0.06);"></div>
                        <div id="ebd-upfront-row" style="display: flex; justify-content: space-between; font-size: 14px; align-items: center;">
                            <span style="color: #aaa; font-weight: 500;"><i class="fas fa-check-circle" style="font-size:12px; color:#20c8a1; margin-right:4px;"></i> Upfront Paid</span>
                            <span style="color: #20c8a1; font-weight: 600;" id="ebd-upfront-paid">-₱0.00</span>
                        </div>
                        <div id="ebd-res-row" style="display: flex; justify-content: space-between; font-size: 14px; align-items: center;">
                            <span style="color: #aaa; font-weight: 500;"><i class="fas fa-star" style="font-size:12px; color:#f1a83c; margin-right:4px;"></i> Reservation Credit</span>
                            <span style="color: #20c8a1; font-weight: 600;" id="ebd-res-credit">-₱0.00</span>
                        </div>
                        <div style="margin-top: 8px; padding-top: 12px; border-top: 2px dashed rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 15px; font-weight: 700; color: #f0f0f0;">Final Balance Due</span>
                            <span style="font-size: 20px; font-weight: 800; color: #fb566b; text-shadow: 0 0 10px rgba(251,86,107,0.3);" id="ebd-final-due">₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- ── Amount to Pay (prominent bill display) ── -->
                <div id="endAmountDueBox" style="display:none;background:linear-gradient(135deg,rgba(251,86,107,.12),rgba(241,168,60,.08));border:1px solid rgba(251,86,107,.3);border-radius:12px;padding:18px 20px;margin-bottom:16px;text-align:center;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin-bottom:6px;">
                        <i class="fas fa-receipt" style="margin-right:4px;"></i> Amount to Pay
                    </div>
                    <div id="endAmountDueDisplay" style="font-size:42px;font-weight:900;color:#fb566b;line-height:1;letter-spacing:-1px;">₱0.00</div>
                    <div id="endAmountDueLabel" style="font-size:12px;color:#888;margin-top:6px;"></div>
                </div>

                <label id="endPaymentMethodLabel">Payment Method</label>
                <select name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
                </select>

                <!-- Tendered amount with pre-fill + optional unlock -->
                <div style="margin-top:14px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <label style="font-size:11px;color:#6b7fa8;text-transform:uppercase;letter-spacing:.6px;margin:0;">Amount Tendered</label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#888;cursor:pointer;font-weight:400;">
                            <input type="checkbox" id="endTenderedToggle" style="width:13px;height:13px;accent-color:#8aa4e8;cursor:pointer;" onchange="toggleTendered('endTendered','endTenderedToggle','endCostAmtHolder','endChangeDisplay')">
                            <span style="color:#8aa4e8;">Different amount</span>
                        </label>
                    </div>
                    <!-- Flex row: ₱ prefix | input | lock icon -->
                    <div id="endTenderedWrapper" style="display:flex;align-items:center;border-radius:12px;border:1.5px solid rgba(95,133,218,.4);background:rgba(95,133,218,.07);overflow:hidden;transition:.2s;">
                        <span style="padding:0 4px 0 16px;font-size:22px;font-weight:900;color:#5f85da;flex-shrink:0;line-height:1;">₱</span>
                        <input type="number" id="endTendered" min="0" step="0.01" readonly
                               style="flex:1;border:none;background:transparent;color:#8aa4e8;font-size:22px;font-weight:800;padding:14px 8px;outline:none;appearance:none;-moz-appearance:textfield;min-width:0;"
                               oninput="calcChange('endTendered','endChangeDisplay','endCostAmtHolder')">
                        <i id="endTenderedIcon" class="fas fa-lock"
                           style="padding:0 16px;font-size:14px;color:#5f85da;flex-shrink:0;cursor:pointer;"
                           title="Click to enter a different amount"
                           onclick="var cb=document.getElementById('endTenderedToggle');cb.checked=!cb.checked;toggleTendered('endTendered','endTenderedToggle','endCostAmtHolder','endChangeDisplay');"></i>
                    </div>
                    <p class="field-hint" id="endTenderedHintText">Pre-filled with amount due. Tick to enter a different amount.</p>
                    <input type="hidden" id="endCostAmtHolder" value="0">
                </div>
                <!-- Change / Insufficient display (warning only — does NOT block submission) -->
                <div id="endChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:16px;font-weight:800;margin-top:8px;"></div>
                <!-- Short-payment notice shown when tendered < due -->
                <div id="endShortNotice" style="display:none;margin-top:10px;background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.35);border-radius:8px;padding:10px 14px;font-size:13px;color:#f1a83c;">
                    <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>
                    <strong>Short payment</strong> — this will be recorded. The session will still end.
                </div>
            </div>

            <!-- Shown for unlimited / fully-prepaid hourly -->
            <div id="endPrepaidNote" style="display:none;" class="modal-banner success">
                <i class="fas fa-check-circle"></i> <strong>Payment already collected at session start.</strong> No additional charge at end.
            </div>

            <button type="submit" class="btn-dang btn-full" id="endSessionConfirmBtn" style="margin-top:4px;">
                <i class="fas fa-check-circle"></i> <span id="endSessionConfirmLabel">Confirm End &amp; Record Payment</span>
            </button>

        </form>
        </div><!-- /.modal-body -->
    </div>
</div>

<!-- ════ COLLECT OUTSTANDING BALANCE MODAL (mid-session, no end) ══════════ -->
<div class="modal" id="paySessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-peso-sign" style="color:#20c8a1;margin-right:8px"></i>Collect Balance
            </h3>
            <button class="modal-close" onclick="closePayModal()">&times;</button>
        </div>

        <div class="modal-body">
        <!-- Session info -->
        <div class="modal-banner success" style="font-size:14px;">
            <i class="fas fa-peso-sign"></i>
            <strong id="paySessionSummary">—</strong>
        </div>

        <!-- Live elapsed panel (open_time / hourly) -->
        <div id="payCostPanel" style="display:none;background:rgba(32,200,161,.06);border:1px solid rgba(32,200,161,.15);border-radius:10px;padding:12px 16px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;font-size:13px;color:#888;">
                <span>⏱ Elapsed: <strong id="payElapsed" style="color:#f1e1aa;font-family:monospace;">—</strong></span>
                <span>Running cost: <strong id="payEstCost" style="color:#20c8a1;">—</strong></span>
            </div>
            <div id="payCostBreakdown" style="margin-top:6px;font-size:11px;color:#666;"></div>
        </div>

        <!-- Prominent "Balance Due" display -->
        <div id="payAmountDueBox" style="background:linear-gradient(135deg,rgba(32,200,161,.14),rgba(32,200,161,.06));border:1px solid rgba(32,200,161,.4);border-radius:12px;padding:18px 20px;margin-bottom:14px;text-align:center;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin-bottom:6px;">
                <i class="fas fa-receipt" style="margin-right:4px;"></i> Balance Due
            </div>
            <div id="payAmountDueDisplay" style="font-size:42px;font-weight:900;color:#20c8a1;line-height:1;letter-spacing:-1px;">₱0.00</div>
            <div id="payAmountDueLabel" style="font-size:12px;color:#888;margin-top:6px;"></div>
        </div>

        <form method="POST" id="paySessionForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="collect_payment">
            <input type="hidden" name="session_id" id="paySessionId">

            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:6px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <label style="font-size:11px;color:#6b7fa8;text-transform:uppercase;letter-spacing:.6px;margin:0;">Amount Tendered</label>
                    
                </div>
                <!-- Flex row: ₱ prefix | input | lock icon -->
                <div id="payTenderedWrapper" style="display:flex;align-items:center;border-radius:12px;border:1.5px solid rgba(95,133,218,.4);background:rgba(95,133,218,.07);overflow:hidden;transition:.2s;">
                    <span style="padding:0 4px 0 16px;font-size:22px;font-weight:900;color:#5f85da;flex-shrink:0;line-height:1;">₱</span>
                    <input type="number" id="payTendered" name="tendered_amount" min="0" step="0.01" readonly
                           style="flex:1;border:none;background:transparent;color:#8aa4e8;font-size:22px;font-weight:800;padding:14px 8px;outline:none;appearance:none;-moz-appearance:textfield;min-width:0;"
                           oninput="calcChange('payTendered','payChangeDisplay','payAmount'); syncPayBtn()">
                    <i id="payTenderedIcon" class="fas fa-lock"
                       style="padding:0 16px;font-size:14px;color:#5f85da;flex-shrink:0;cursor:pointer;"
                       title="Click to enter a different amount"
                       onclick="var cb=document.getElementById('payTenderedToggle');cb.checked=!cb.checked;toggleTendered('payTendered','payTenderedToggle','payAmount','payChangeDisplay');"></i>
                </div>
                <p class="field-hint" id="payTenderedHintText">Pre-filled with balance due. Tick to enter a different amount.</p>
                <!-- Hidden field — stores the balance due for calcChange -->
                <input type="hidden" name="amount" id="payAmount" value="0">
            </div>
            <div id="payChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:15px;font-weight:700;margin-bottom:12px;"></div>
            <div id="payShortNotice" style="display:none;margin-top:10px;background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.35);border-radius:8px;padding:10px 14px;font-size:13px;color:#f1a83c;margin-bottom:10px;">
                <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>
                <strong>Short payment</strong> — the remaining shortfall will be recorded.
            </div>
            <button type="submit" id="payConfirmBtn" class="btn-prim btn-full">
                <i class="fas fa-check-circle"></i> <span id="payConfirmLabel">Record Payment</span>
            </button>

        </form>
        </div><!-- /.modal-body -->
    </div>
</div>

<!-- ════ REFUND MODAL ══════════════════════════════════════════════════════ -->
<div class="modal" id="refundSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-undo-alt" style="color:#f1a83c;margin-right:8px"></i>Issue Refund
            </h3>
            <button class="modal-close" onclick="closeModal('refundSession')">&times;</button>
        </div>

        <div class="modal-body">
        <div class="modal-banner warn" style="font-size:14px;flex-direction:column;gap:6px;">
            <strong id="refundSessionSummary">—</strong>
            <div style="font-size:13px;color:#888;">
                Total paid so far: <strong id="refundPaidSoFar" style="color:#20c8a1;">₱0.00</strong>
            </div>
        </div>

        <form method="POST" id="refundSessionForm">
            <?= csrfField() ?>
            <!-- action is overridden to 'early_end_session' when coming from early-end flow -->
            <input type="hidden" name="action" id="refundActionField" value="issue_refund">
            <input type="hidden" name="session_id" id="refundSessionId">
            <!-- For reservation-cancellation refunds -->
            <input type="hidden" name="reservation_id" id="refundReservationId" value="">
            <!-- Flag set by JS when this is an early-end (refund + end) submission -->
            <input type="hidden" name="early_end" id="refundEarlyEndFlag" value="0">
            <div class="form-group">
                <label>Refund Amount (₱) *</label>
                <input type="number" name="refund_amount" id="refundAmount" min="0" step="1"
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:20px;font-weight:700;box-sizing:border-box;"
                       placeholder="Enter amount to refund">
                <div id="refundMaxNote" style="font-size:11px;color:#888;margin-top:4px;"></div>
                <!-- Auto-calc breakdown (shown only for early-end flow) -->
                <div id="refundAutoCalcHint" style="display:none;margin-top:8px;font-size:12px;color:#f1e1aa;
                     background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.2);
                     border-radius:8px;padding:10px 12px;line-height:1.5;"></div>
            </div>
            <div class="form-group">
                <label>Reason (optional)</label>
                <input type="text" name="refund_reason" id="refundReason" maxlength="200"
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:14px;box-sizing:border-box;"
                       placeholder="e.g. Technical issue, customer complaint…">
            </div>
            <div class="modal-banner danger" style="margin-bottom:16px;font-size:12px;">
                <i class="fas fa-exclamation-triangle"></i> Refunds are recorded as negative transactions and <strong>cannot be undone</strong>.
            </div>
            <!-- Early-end note — shown only when triggered from early-end flow -->
            <div id="refundEarlyEndNote" style="display:none;background:rgba(241,168,60,.1);border:1px solid rgba(241,168,60,.3);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#f1a83c;">
                <i class="fas fa-stop-circle" style="margin-right:5px;"></i>
                <strong>Early End:</strong> Confirming will issue the refund above <strong>and immediately end the session</strong>.
            </div>
            <!-- Inline error display (populated by _showRefundError()) -->
            <div id="refundErrorMsg" style="display:none;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.35);border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#fb566b;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
                <span id="refundErrorText"></span>
            </div>
            <button type="button" id="refundConfirmBtn" class="btn-dang btn-full"
                    onclick="_submitRefundAjax()">
                <i class="fas fa-undo-alt"></i> <span id="refundConfirmLabel">Confirm Refund</span>
            </button>

        </form>
        </div><!-- /.modal-body -->
    </div>
</div>

<!-- ════ EXTEND SESSION MODAL ════════════════════════════════════════════ -->
<div class="modal" id="extendSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-clock" style="color:#8aa4e8;margin-right:8px"></i>Extend Session
            </h3>
            <button class="modal-close" onclick="closeModal('extendSession')">&times;</button>
        </div>

        <!-- Session info -->
        <div style="background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:14px">
            <strong id="extendSessionSummary">—</strong>
            <div style="margin-top:8px;font-size:13px;color:#888;">
                Current booked duration: <strong id="extendCurrentDuration" style="color:#8aa4e8;">—</strong>
            </div>
        </div>

        <form id="extendSessionForm" onsubmit="event.preventDefault(); submitExtendSession();">
            <!-- Hidden state fields used by openExtendModal() JS helper -->
            <input type="hidden" name="action" value="extend_session">
            <input type="hidden" name="session_id" id="extendSessionId">
            <input type="hidden" id="extendSessionMode" value="hourly">
            <input type="hidden" id="extendCostHolder" value="0">

            <!-- Pending extension requests (populated by loadPendingExtensions) -->
            <div id="extendPendingSection" style="display:none;margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#f1a83c;margin-bottom:8px;">
                    <i class="fas fa-clock"></i> Pending Customer Extension Requests
                </div>
                <div id="extendPendingList"></div>
            </div>

            <div class="form-group">
                <label>Add Time *</label>
                <select name="extra_minutes" id="extendMinutes" required onchange="updateExtendCost()">
                    <option value="" disabled selected>— Select additional time —</option>
                    <option value="15">+ 15 minutes</option>
                    <option value="30">+ 30 minutes — ₱50</option>
                    <option value="60">+ 1 hour — ₱80</option>
                    <option value="90">+ 1h 30m — ₱120</option>
                    <option value="120">+ 2 hours — ₱160</option>
                    <option value="180">+ 3 hours — ₱240</option>
                    <option value="240">+ 4 hours — ₱320</option>
                </select>
            </div>

            <!-- Cost preview (shown after time selection) -->
            <div id="extendCostPreview" style="display:none;background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.2);border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:14px;">
                Extension cost: <strong id="extendCostAmt" style="color:#8aa4e8;">₱0</strong>
                <span id="extendFreeNote" style="display:none;color:#20c8a1;font-size:12px;margin-left:8px;">
                    <i class="fas fa-gift"></i> No charge for this session type
                </span>
            </div>

            <!-- Extension cost information -->
            <div style="background:rgba(95,133,218,.07);border:1px solid rgba(95,133,218,.2);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#8aa4e8;">
                <i class="fas fa-info-circle"></i> Extension costs for hourly sessions are automatically added to the customer's Outstanding Balance and collected at the end of the session.
            </div>
            <div id="extendErrorMsg" style="display:none;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.35);border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#fb566b;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
                <span id="extendErrorText"></span>
            </div>
            <button type="submit" id="extendConfirmBtn" class="btn-prim btn-full">
                <i class="fas fa-clock"></i> <span id="extendConfirmLabel">Extend Session</span>
            </button>

        </form>
        
    </div>
</div>

<script>
/* ── Extend Session Modal ──────────────────────────────────────────── */
function openExtendModal(sessionId, customerName, unitNumber, bookedMinutes, rentalMode) {
    document.getElementById('extendSessionId').value   = sessionId;
    document.getElementById('extendSessionMode').value = rentalMode || 'hourly';
    document.getElementById('extendSessionSummary').textContent =
        customerName + ' — ' + unitNumber;

    const h = Math.floor(bookedMinutes / 60), m = bookedMinutes % 60;
    document.getElementById('extendCurrentDuration').textContent =
        bookedMinutes ? (h ? h+'h ' : '') + (m ? m+'m' : '') : '—';

    // Reset fields
    document.getElementById('extendMinutes').value = '';
    document.getElementById('extendCostPreview').style.display = 'none';
    if(document.getElementById('extendPaymentFields')) document.getElementById('extendPaymentFields').style.display = 'none';
    if(document.getElementById('extendTendered')) document.getElementById('extendTendered').value = '';
    if(document.getElementById('extendChangeDisplay')) document.getElementById('extendChangeDisplay').style.display = 'none';
    
    // Reset error state and button
    document.getElementById('extendErrorMsg').style.display = 'none';
    const btn = document.getElementById('extendConfirmBtn');
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.style.cursor = 'pointer';

    // Reset the tendered toggle to locked state
    const toggle = document.getElementById('extendTenderedToggle');
    if (toggle) { toggle.checked = false; _lockExtendTendered(); }

    // Load pending requests for this session
    loadPendingExtensions(sessionId);

    openModal('extendSession');
}

function loadPendingExtensions(sessionId) {
    const section = document.getElementById('extendPendingSection');
    const list    = document.getElementById('extendPendingList');
    section.style.display = 'none';
    list.innerHTML = '';

    fetch(`ajax/approve_extension.php?get_pending=1&session_id=${sessionId}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(function(data) {
            if (!data.pending || data.pending.length === 0) return;
            section.style.display = 'block';
            data.pending.forEach(function(req) {
                const cost = parseFloat(req.extra_cost);
                const costLabel = cost > 0 ? ` — ₱${cost.toFixed(0)}` : ' — Free';
                const card = document.createElement('div');
                card.style.cssText = 'background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.25);border-radius:8px;padding:10px 12px;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:13px;';
                card.innerHTML =
                    `<span><i class="fas fa-user" style="color:#f1a83c;margin-right:5px;"></i>` +
                    `+${req.extra_minutes} min${costLabel}</span>` +
                    `<div style="display:flex;gap:6px;">` +
                    `<button onclick="approveExt(${req.extension_id})" style="padding:5px 10px;border-radius:6px;background:rgba(32,200,161,.2);border:1px solid rgba(32,200,161,.5);color:#20c8a1;font-size:12px;font-weight:700;cursor:pointer;">` +
                    `<i class="fas fa-check"></i> Approve</button>` +
                    `<button onclick="denyExt(${req.extension_id})" style="padding:5px 10px;border-radius:6px;background:rgba(251,86,107,.15);border:1px solid rgba(251,86,107,.4);color:#fb566b;font-size:12px;font-weight:700;cursor:pointer;">` +
                    `<i class="fas fa-times"></i> Deny</button></div>`;
                list.appendChild(card);
            });
        })
        .catch(function() {});
}

function updateExtendCost() {
    const mins    = parseInt(document.getElementById('extendMinutes').value) || 0;
    const mode    = document.getElementById('extendSessionMode').value;
    const sessionId = document.getElementById('extendSessionId').value;
    const preview = document.getElementById('extendCostPreview');
    const payFlds = document.getElementById('extendPaymentFields');
    const freeNote = document.getElementById('extendFreeNote');
    const costEl  = document.getElementById('extendCostAmt');
    const holder  = document.getElementById('extendCostHolder');
    const errorMsg = document.getElementById('extendErrorMsg');
    const errorText = document.getElementById('extendErrorText');
    const btn = document.getElementById('extendConfirmBtn');

    if (!mins) { 
        preview.style.display = 'none'; 
        if(payFlds) payFlds.style.display = 'none'; 
        errorMsg.style.display = 'none';
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        return; 
    }

    // Check for conflict first
    fetch(`ajax/check_extend_conflict.php?session_id=${sessionId}&extra_minutes=${mins}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.conflict) {
                // Show conflict error, hide preview, disable button
                errorText.textContent = data.message;
                errorMsg.style.display = 'block';
                preview.style.display = 'none';
                if(payFlds) payFlds.style.display = 'none';
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            } else {
                // No conflict, proceed
                errorMsg.style.display = 'none';
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                preview.style.display = 'block';

                if (mode === 'open_time' || mode === 'unlimited') {
                    costEl.textContent = '₱0';
                    freeNote.style.display = 'inline-block';
                    if(payFlds) payFlds.style.display  = 'none';
                    holder.value = '0';
                } else {
                    // Hourly: straight ₱80/hr
                    const cost = computeExtCost(mins);
                    costEl.textContent = '₱' + cost;
                    freeNote.style.display = 'none';
                    if(payFlds) payFlds.style.display  = 'block';
                    holder.value = cost;
                    // Pre-fill tendered
                    const tendInput = document.getElementById('extendTendered');
                    if(tendInput) tendInput.value = cost;
                    const toggle = document.getElementById('extendTenderedToggle');
                    if (toggle) { toggle.checked = false; _lockExtendTendered(); }
                    const changeDisp = document.getElementById('extendChangeDisplay');
                    if(changeDisp) changeDisp.style.display = 'none';
                }
            }
        })
        .catch(err => {
            console.warn('[GSpot] Conflict check error:', err);
        });
}

// JS mirror of extension pricing: straight ₱80/hr, no session-start minimum
function computeExtCost(mins) {
    // ₱80/hr straight — 30 min = ₱40, 60 min = ₱80, etc.
    return Math.round((mins / 60) * 80);
}

/* ── Tendered field lock/unlock helpers ──────────────────────────────── */
function _lockExtendTendered() {
    const inp  = document.getElementById('extendTendered');
    const icon = document.getElementById('extendTenderedLockIcon');
    const hint = document.getElementById('extendTenderedHint');
    if (!inp) return;
    inp.readOnly = true;
    inp.style.borderColor  = 'rgba(95,133,218,.35)';
    inp.style.background   = 'rgba(95,133,218,.07)';
    inp.style.color        = '#8aa4e8';
    inp.style.cursor       = 'not-allowed';
    inp.style.paddingLeft  = '36px';
    if (icon) { icon.className = 'fas fa-lock'; icon.style.color = '#5f85da'; }
    if (hint) hint.style.display = 'block';
    // Hide change display when locked
    document.getElementById('extendChangeDisplay').style.display = 'none';
}

function toggleExtendTendered(cb) {
    const inp  = document.getElementById('extendTendered');
    const icon = document.getElementById('extendTenderedLockIcon');
    const hint = document.getElementById('extendTenderedHint');
    if (!inp) return;

    if (cb.checked) {
        // Unlock — editable, white style
        inp.readOnly = false;
        inp.style.borderColor  = 'rgba(255,255,255,.2)';
        inp.style.background   = 'rgba(255,255,255,.06)';
        inp.style.color        = '#fff';
        inp.style.cursor       = 'text';
        inp.style.paddingLeft  = '12px';
        if (icon) { icon.className = 'fas fa-unlock'; icon.style.color = '#20c8a1'; }
        if (hint) hint.style.display = 'none';
        inp.focus();
        inp.select();
    } else {
        // Re-lock and reset back to exact cost
        const cost = document.getElementById('extendCostHolder').value;
        inp.value = cost;
        _lockExtendTendered();
        document.getElementById('extendChangeDisplay').style.display = 'none';
    }
}

function submitExtendSession() {
    const sessionId = document.getElementById('extendSessionId').value;
    const mins      = document.getElementById('extendMinutes').value;
    const mode      = document.getElementById('extendSessionMode').value;
    const method    = 'cash';
    const tendered  = 0;

    if (!mins) { showInlineToast('Please select how much time to add.', 'error'); return; }

    const btn = document.getElementById('extendConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    const fd = new FormData();
    fd.append('session_id',     sessionId);
    fd.append('extra_minutes',  mins);
    fd.append('payment_method', method);
    fd.append('tendered', tendered);

    fetch('ajax/extend_session.php', { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json())
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-clock"></i> Extend Session';
            if (data.success) {
                closeModal('extendSession');
                const toastMsg = data.message ? data.message : 'Session extended! ₱' + (data.extra_cost || 0) + ' added to balance.';
                showInlineToast(toastMsg, 'success');
                
                if (typeof updateTimers === 'function') {
                    const row = document.querySelector(`tr[data-id="${sessionId}"]`);
                    if (row && mode === 'hourly') {
                        let curBooked = parseInt(row.dataset.booked) || 0;
                        curBooked += parseInt(data.total_added || mins);
                        row.dataset.booked = curBooked;
                        const h = Math.floor(curBooked / 60);
                        const m = curBooked % 60;
                        if (row.cells[4]) {
                            row.cells[4].textContent = h ? (m ? h+'h '+m+'m' : h+'h') : m+'m';
                        }
                        const extendBtn = row.querySelector('button[title="Extend Session"]');
                        if (extendBtn) {
                            const newOnclick = extendBtn.getAttribute('onclick').replace(/(openExtendModal\([^,]+,[^,]+,[^,]+,)\s*\d+/, `$1 ${curBooked}`);
                            extendBtn.setAttribute('onclick', newOnclick);
                        }
                    }
                }
                
                if (typeof updateLiveSection === 'function') {
                    updateLiveSection();
                } else {
                    fetch(location.href).then(res => res.text()).then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newPending = doc.getElementById('pendingPaymentsTable');
                        const oldPending = document.getElementById('pendingPaymentsTable');
                        if (newPending && oldPending) {
                            oldPending.innerHTML = newPending.innerHTML;
                        }
                    });
                }
            } else {
                showInlineToast(data.message || 'Extension failed.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-clock"></i> Extend Session';
            showInlineToast('Network error — please try again.', 'error');
        });
}


function approveExt(extId) {
    const method = 'cash';
    gspotConfirm('Approve extension?', function() {
        const fd = new FormData();
        fd.append('action', 'approve');
        fd.append('extension_id', extId);
        fd.append('payment_method', method);
        fd.append('tendered', 0);
        fetch('ajax/approve_extension.php', { method: 'POST', credentials: 'same-origin', body: fd })
            .then(r => r.json())
            .then(function(d) {
                if (d.success) {
                    showInlineToast('Extension approved! Added to balance.', 'success');
                    loadPendingExtensions(document.getElementById('extendSessionId').value);
                    if (typeof updateLiveSection === 'function') updateLiveSection();
                    else {
                        fetch(location.href).then(res => res.text()).then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newPending = doc.getElementById('pendingPaymentsTable');
                            const oldPending = document.getElementById('pendingPaymentsTable');
                            if (newPending && oldPending) oldPending.innerHTML = newPending.innerHTML;
                        });
                    }
                } else {
                    showInlineToast(d.message || 'Failed to approve.', 'error');
                }
            });
    }, { yesLabel: 'Approve', danger: false });
}

function _approveExtDummy() {
    fetch('ajax/approve_extension.php', { method: 'POST', credentials: 'same-origin', body: new FormData() })
        .then(r => r.json())
        .then(function(d) {
            if (d.success) {
                showInlineToast('Extension approved! Added to balance.', 'success');
                loadPendingExtensions(document.getElementById('extendSessionId').value);
                if (typeof updateLiveSection === 'function') updateLiveSection();
                else {
                    fetch(location.href).then(res => res.text()).then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newPending = doc.getElementById('pendingPaymentsTable');
                        const oldPending = document.getElementById('pendingPaymentsTable');
                        if (newPending && oldPending) oldPending.innerHTML = newPending.innerHTML;
                    });
                }
            } else {
                showInlineToast(d.message || 'Failed to approve.', 'error');
            }
        });
}


function denyExt(extId) {
    const fd = new FormData();
    fd.append('action', 'deny');
    fd.append('extension_id', extId);
    fd.append('note', 'Denied by staff');
    fetch('ajax/approve_extension.php', { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json())
        .then(function(d) {
            if (d.success) {
                showInlineToast('Extension denied.', 'success');
                loadPendingExtensions(document.getElementById('extendSessionId').value);
            } else {
                showInlineToast(d.message || 'Failed to deny.', 'error');
            }
        });
}
</script>


<!-- ════ ADD RESERVATION MODAL (admin) ══════════════════════════════════ -->
<div class="modal" id="addReservationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-calendar-plus" style="color:#20c8a1;margin-right:8px;"></i>Add Reservation
            </h3>
            <button class="modal-close" onclick="closeModal('addReservation')">&times;</button>
        </div>
        <div class="modal-body">
        <form method="POST" id="addReservationForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_reservation">
            <!-- Hidden field carries the resolved user_id -->
            <input type="hidden" name="user_id" id="arUserId" value="">
            <div class="form-group">
                <label>Customer <span class="req">*</span></label>
                <!-- Customer search widget (prefix: ar) -->
                <div class="cs-wrap" id="arWrap">
                    <div class="cs-input-row">
                        <i class="fas fa-search cs-icon"></i>
                        <input type="text" id="arQuery" class="cs-input"
                               placeholder="Search customer by name or email…"
                               autocomplete="off"
                               oninput="csSearch('ar')"
                               onkeydown="csKeyNav(event,'ar')">
                        <button type="button" class="cs-clear" id="arClear"
                                onclick="csDeselect('ar','required')">&times;</button>
                    </div>
                    <div class="cs-dropdown" id="arDropdown" role="listbox"></div>
                    <!-- Selected customer chip -->
                    <div class="cs-selected" id="arSelected">
                        <div class="cs-selected-avatar" id="arSelAvatar"></div>
                        <div class="cs-selected-info">
                            <div class="cs-selected-name" id="arSelName"></div>
                            <div class="cs-selected-email" id="arSelEmail"></div>
                        </div>
                        <button type="button" class="cs-deselect" title="Change customer"
                                onclick="csDeselect('ar','required')">&times;</button>
                    </div>
                    <p class="field-hint" id="arHint" style="color:#fb566b;display:none;">
                        <i class="fas fa-exclamation-circle" style="margin-right:4px;"></i>A registered customer must be selected.</p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Console <span class="req">*</span></label>
                    <select name="console_id" id="adminResConsoleSelect" required onchange="adminResSync()" disabled>
                        <option value="" disabled selected>— Select Date & Time first —</option>
                    </select>
                    <input type="hidden" name="console_type" id="adminResConsoleTypeHidden">
                </div>
                <div class="form-group">
                    <label>Rental Mode <span class="req">*</span></label>
                    <select name="rental_mode" id="resAdminModeSelect" required onchange="adminResOnModeChange()">
                        <option value="hourly">Hourly</option>
                        <option value="open_time">Open Time</option>
                        <option value="unlimited" id="arUnlimOption">Unlimited</option>
                    </select>
                    <div id="arUnlimRestrictedMsg" style="display:none; font-size:10px; color:#fb566b; margin-top:4px; font-weight:600;">
                        <i class="fas fa-exclamation-triangle"></i> Unlimited mode unavailable after 7:00 PM
                    </div>
                </div>
            </div>
            <div id="adminResDurGroup" class="form-group">
                <label>Duration <span class="req">*</span></label>
                <select name="planned_minutes" id="adminResPlannedMins" onchange="adminResSync()">
                    <option value="" disabled selected>— Select duration —</option>
                    <?php 
                    // Use same duration options as Start Session for consistency
                    foreach (getHourlyDurationOptions() as $opt): ?>
                        <option value="<?= $opt['paid'] ?>" 
                                data-total="<?= $opt['total'] ?>"
                                data-cost="<?= $opt['cost'] ?>">
                            <?= $opt['label_total'] ?> — ₱<?= round($opt['cost']) ?><?= $opt['label_bonus'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date <span class="req">*</span></label>
                    <input type="date" name="reserved_date" id="adminResDate" required
                           min="<?= date('Y-m-d') ?>"
                           max="<?= date('Y-m-d', strtotime('+90 days')) ?>"
                           onchange="adminResSync()">
                    <p class="field-hint">Reservations accepted up to 90 days in advance.</p>
                </div>
                <div class="form-group">
                    <label>Time <span class="req">*</span></label>
                    <select name="reserved_time" id="adminResTime" required onchange="adminResSync()">
                        <option value="" disabled selected>— Select time —</option>
                        <?php 
                        // Start at 8:00 AM, end at 11:30 PM (last slot)
                        for ($h = 8; $h <= 23; $h++) {
                            foreach (['00', '15', '30', '45'] as $m) {
                                if ($h == 23 && !in_array($m, ['00', '15', '30'])) continue; 
                                $val  = sprintf('%02d:%s', $h, $m);
                                $disp = date('g:i A', strtotime("2000-01-01 $val"));
                                echo "<option value=\"$val\">$disp</option>\n";
                            }
                        }
                        ?>
                    </select>
                    <p class="field-hint warn"><i class="fas fa-clock"></i> Operating hours: 8:00 AM – 12:00 AM</p>
                </div>
            </div>
            <div id="adminResConflictBox" style="display:none; margin-bottom:15px; padding:12px; border-radius:10px; background:rgba(251,86,107,.15); border:1px solid rgba(251,86,107,.3); color:#fb566b; font-size:13px;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                <span id="adminResConflictMsg"></span>
            </div>
            <div class="form-group" id="adminDpGroup" style="display:none;">
                <label style="display:flex;justify-content:space-between;align-items:center;">
                    Payment Amount (&#8369;)
                    <span id="adminDpHint" style="font-size:11px;color:#20c8a1;font-weight:600;"></span>
                </label>
                <input type="number" name="downpayment_amount" id="adminDpAmount" min="0" step="1"
                       readonly>
                <p class="field-hint"><i class="fas fa-lock" style="margin-right:4px;"></i>Fixed at 50% of session cost — collected to secure the booking.</p>
            </div>
            <div class="form-group" id="adminDpMethodGroup" style="display:none;">
                <label>Payment Method <span class="req">*</span></label>
                <select name="downpayment_method" id="adminDpMethodSelect">
                    <option value="cash">&#x1F4B5; Cash</option>
                    <option value="gcash">&#x1F4F1; GCash</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2" placeholder="Any notes…"></textarea>
            </div>
            <button type="submit" class="btn-prim btn-full" id="adminResSubmitBtn">
                <i class="fas fa-calendar-check"></i> Save Reservation
            </button>

        </form>
        </div><!-- /.modal-body -->
    </div>
</div>


<script>
/* ── Reservation modal helpers ───────────────────────────────────── */
/* ── Reservation modal helpers ───────────────────────────────────── */
function adminResOnModeChange() {
    const mode = document.getElementById('resAdminModeSelect').value;
    const timeVal = document.getElementById('adminResTime').value;

    // Strict Rule: Unlimited not allowed at 7:00 PM or later
    if (mode === 'unlimited' && timeVal) {
        const [h] = timeVal.split(':').map(Number);
        if (h >= 19) {
            alert('Unlimited mode is not available for bookings at 7:00 PM or later.');
            document.getElementById('resAdminModeSelect').value = 'hourly';
            adminResOnModeChange();
            return;
        }
    }

    const isDurGroup   = (mode === 'hourly');
    document.getElementById('adminResDurGroup').style.display = isDurGroup ? 'block' : 'none';

    // Reset downpayment whenever mode changes
    document.getElementById('adminDpAmount').value = '';
    document.getElementById('adminDpGroup').style.display      = 'none';
    document.getElementById('adminDpMethodGroup').style.display = 'none';
    document.getElementById('adminResPlannedMins').value       = '';
    document.getElementById('adminDpHint').textContent         = '';
    
    adminResSync();
}

/**
 * Syncs the console type hidden field, re-calculates downpayment,
 * and checks for real-time scheduling conflicts.
 */
function adminResSync() {
    const conSel = document.getElementById('adminResConsoleSelect');
    const opt    = conSel.options[conSel.selectedIndex];
    const type   = opt ? (opt.dataset.type || '') : '';
    const hidden = document.getElementById('adminResConsoleTypeHidden');
    if (hidden) hidden.value = type;

    // 1. Recalculate Downpayment
    adminResCalcDownpayment();

    // 2. Fetch Availability & Conflict Check
    const cid    = conSel.value;
    const date   = document.getElementById('adminResDate').value;
    const time   = document.getElementById('adminResTime').value;
    const durSel = document.getElementById('adminResPlannedMins');
    const mins   = durSel.value || 60;
    const mode   = document.getElementById('resAdminModeSelect').value;
    const box    = document.getElementById('adminResConflictBox');
    const msg    = document.getElementById('adminResConflictMsg');
    const btn    = document.getElementById('adminResSubmitBtn');

    // ── Time-based restrictions ──
    if (time) {
        const [h, m] = time.split(':').map(Number);
        const currentMins = h * 60 + m;
        const midnightMins = 24 * 60;
        const maxMins = midnightMins - currentMins;

        // Unlimited restriction
        const unlimOpt = document.getElementById('arUnlimOption');
        const unlimMsg = document.getElementById('arUnlimRestrictedMsg');
        if (h >= 19) {
            if (unlimOpt) unlimOpt.disabled = true;
            if (unlimMsg) unlimMsg.style.display = 'block';
            if (mode === 'unlimited') {
                document.getElementById('resAdminModeSelect').value = 'hourly';
                adminResOnModeChange();
            }
        } else {
            if (unlimOpt) unlimOpt.disabled = false;
            if (unlimMsg) unlimMsg.style.display = 'none';
        }

        // Duration restriction
        let firstValid = null;
        Array.from(durSel.options).forEach(opt => {
            if (opt.value === "" || opt.disabled) return;
            const val = parseInt(opt.value);
            const total = parseInt(opt.dataset.total || val);
            if (total > maxMins) {
                opt.disabled = true;
                opt.style.color = '#555';
            } else {
                opt.disabled = false;
                opt.style.color = '';
                if (!firstValid) firstValid = opt.value;
            }
        });

        if (durSel.value && durSel.options[durSel.selectedIndex].disabled) {
            durSel.value = firstValid || "";
        }
    }

    // Enable/Disable Console Select based on Date/Time
    if (!date || !time) {
        conSel.disabled = true;
        conSel.innerHTML = '<option value="" disabled selected>— Select Date & Time first —</option>';
        if (box) box.style.display = 'none';
        return;
    }

    // If Date/Time set but Console Select was disabled or has placeholder, fetch consoles
    if (conSel.disabled || conSel.options.length <= 1) {
        conSel.disabled = false;
        conSel.innerHTML = '<option value="" disabled selected>— Fetching Available Consoles… —</option>';
        
        fetch(`ajax/check_unit_availability.php?date=${date}&time=${time}&planned_minutes=${mins}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const currentVal = conSel.value;
                    conSel.innerHTML = '<option value="" disabled selected>— Select console —</option>';
                    let foundMatch = false;
                    
                    data.units.forEach(u => {
                        if (u.status === 'available') {
                            const opt = document.createElement('option');
                            opt.value = u.id;
                            opt.dataset.type = u.type;
                            opt.dataset.rate = u.rate;
                            opt.textContent = `#${u.unit} — ${u.type} (₱${Math.round(u.rate)}/hr)`;
                            if (u.id == currentVal) {
                                opt.selected = true;
                                foundMatch = true;
                            }
                            conSel.appendChild(opt);
                        }
                    });

                    // If previously selected unit is no longer available
                    if (currentVal && !foundMatch) {
                        if (box) {
                            box.style.display = 'block';
                            box.style.background = 'rgba(251,86,107,.15)';
                            box.style.borderColor = 'rgba(251,86,107,.3)';
                            box.style.color = '#fb566b';
                            msg.textContent = 'The previously selected console unit is no longer available for this slot. Please select another unit.';
                        }
                        btn.disabled = true;
                    } else {
                        if (box) box.style.display = 'none';
                        btn.disabled = false;
                    }
                }
            });
        return;
    }

    // If a console is selected, double-check it's still valid
    if (cid && date && time) {
        // Show loading state for conflict check
        if (msg) msg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying slot...';
        if (box) box.style.display = 'block';

        fetch(`ajax/check_reservation_conflict.php?console_id=${cid}&date=${date}&time=${time}&planned_minutes=${mins}&rental_mode=${mode}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.conflict) {
                box.style.display = 'block';
                box.style.background = 'rgba(251,86,107,.15)';
                box.style.borderColor = 'rgba(251,86,107,.3)';
                box.style.color = '#fb566b';
                msg.textContent = data.message;
                btn.disabled = true;
                btn.style.opacity = '0.5';
            } else {
                box.style.display = 'none';
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    }
}

/* Calculates 50% of the selected duration cost and fills the downpayment field */
function adminResCalcDownpayment() {
    const durSel = document.getElementById('adminResPlannedMins');
    const opt    = durSel.options[durSel.selectedIndex];
    const cost   = opt ? parseFloat(opt.dataset.cost || 0) : 0;
    
    const dpGroup    = document.getElementById('adminDpGroup');
    const dpMethod   = document.getElementById('adminDpMethodGroup');
    const dpInput    = document.getElementById('adminDpAmount');
    const dpHint     = document.getElementById('adminDpHint');

    if (!cost) {
        dpGroup.style.display  = 'none';
        dpMethod.style.display = 'none';
        dpInput.value          = '';
        dpHint.textContent     = '';
        return;
    }

    const dp = Math.ceil(cost * 0.5); // 50%, rounded up to nearest peso

    dpInput.value          = dp;
    dpHint.textContent     = `50% of ₱${Math.round(cost)}`;
    dpGroup.style.display  = 'block';
    dpMethod.style.display = 'block';
}

/* Legacy — kept for safety but no longer needed */
function adminDpChange() {
    const amt = parseFloat(document.getElementById('adminDpAmount').value) || 0;
    document.getElementById('adminDpMethodGroup').style.display = amt > 0 ? 'block' : 'none';
}


</script>

<script>
/* ═════════════════════════════════════════════════════════
   CUSTOMER SEARCH ENGINE
   Shared by all cs-wrap widgets on the page.
   Prefix convention: 'ss' = Start Session, 'ar' = Add Reservation
   Public API: csSearch(pfx), csKeyNav(e,pfx), csSelect(pfx,id,name,email), csDeselect(pfx,mode)
═════════════════════════════════════════════════════════ */
(function () {
    /* per-prefix debounce timer storage */
    const _timers  = {};
    /* currently keyboard-highlighted item index per prefix */
    const _focusIdx = {};

    /* ── helpers to get elements by prefix ───────────────────── */
    function $q(pfx, id) { return document.getElementById(pfx + id); }

    /* ── Bold-highlight the search term inside a string ──────────── */
    function highlight(str, q) {
        if (!q) return escHtml(str);
        const re  = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return escHtml(str).replace(re, '<span class="cs-highlight">$1</span>');
    }
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /* ── Close the dropdown for a prefix ───────────────────────── */
    function closeDropdown(pfx) {
        const dd = $q(pfx,'Dropdown');
        if (dd) { dd.classList.remove('open'); dd.innerHTML = ''; }
        _focusIdx[pfx] = -1;
    }

    /* ── Main search handler (debounced 220ms) ─────────────────── */
    window.csSearch = function (pfx) {
        const inp = $q(pfx,'Query');
        const clr = $q(pfx,'Clear');
        const q   = inp ? inp.value.trim() : '';

        /* show/hide clear button */
        if (clr) clr.classList.toggle('visible', q.length > 0);

        /* reset hidden id whenever user types */
        const hid = $q(pfx,'UserId');
        if (hid) hid.value = '';

        if (!q) { closeDropdown(pfx); return; }

        clearTimeout(_timers[pfx]);
        _timers[pfx] = setTimeout(function () {
            const dd = $q(pfx,'Dropdown');
            if (!dd) return;
            dd.innerHTML = '<div class="cs-status"><i class="fas fa-spinner fa-spin" style="margin-right:5px;"></i>Searching…</div>';
            dd.classList.add('open');

            fetch('ajax/search_customers.php?q=' + encodeURIComponent(q), { credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(results){ renderResults(pfx, results, q); })
                .catch(function(){
                    dd.innerHTML = '<div class="cs-status" style="color:#fb566b;"><i class="fas fa-exclamation-circle" style="margin-right:5px;"></i>Search error — try again.</div>';
                });
        }, 220);
    };

    /* ── Render search results into the dropdown ──────────────── */
    function renderResults(pfx, results, q) {
        const dd = $q(pfx,'Dropdown');
        if (!dd) return;
        _focusIdx[pfx] = -1;

        if (!results.length) {
            dd.innerHTML =
                '<div class="cs-status">' +
                '<i class="fas fa-user-slash" style="margin-right:5px;color:#fb566b;"></i>' +
                'No registered customer found for "<strong>' + escHtml(q) + '</strong>".' +
                '</div>';
            return;
        }

        dd.innerHTML = '';
        results.forEach(function(c, i) {
            const div = document.createElement('div');
            div.className = 'cs-item';
            div.setAttribute('role','option');
            div.dataset.idx = i;

            const initials = (c.full_name || '?').split(' ').map(function(w){ return w[0]; }).slice(0,2).join('').toUpperCase();
            div.innerHTML =
                '<div class="cs-item-avatar">' + escHtml(initials) + '</div>' +
                '<div>' +
                '  <div class="cs-item-name">' + highlight(c.full_name, q) + '</div>' +
                '  <div class="cs-item-email">' + highlight(c.email, q) + '</div>' +
                '</div>';

            div.addEventListener('mousedown', function(e) {
                /* mousedown fires before blur so we can steal focus safely */
                e.preventDefault();
                csSelect(pfx, c.user_id, c.full_name, c.email);
            });

            dd.appendChild(div);
        });
        dd.classList.add('open');
    }

    /* ── Keyboard navigation (up/down/enter/escape) ───────────── */
    window.csKeyNav = function (e, pfx) {
        const dd    = $q(pfx,'Dropdown');
        const items = dd ? Array.from(dd.querySelectorAll('.cs-item')) : [];
        if (!items.length && e.key !== 'Escape') return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            _focusIdx[pfx] = Math.min((_focusIdx[pfx] || -1) + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            _focusIdx[pfx] = Math.max((_focusIdx[pfx] || 0) - 1, 0);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const idx = _focusIdx[pfx] >= 0 ? _focusIdx[pfx] : 0;
            if (items[idx]) items[idx].dispatchEvent(new MouseEvent('mousedown'));
            return;
        } else if (e.key === 'Escape') {
            closeDropdown(pfx);
            return;
        } else {
            return;
        }

        /* highlight active item */
        items.forEach(function(it, i) {
            it.classList.toggle('active', i === _focusIdx[pfx]);
        });
        if (items[_focusIdx[pfx]]) {
            items[_focusIdx[pfx]].scrollIntoView({ block:'nearest' });
        }
    };

    /* ── Select a customer ────────────────────────────────────── */
    window.csSelect = function (pfx, userId, fullName, email) {
        /* populate hidden input */
        const hid = $q(pfx,'UserId');
        if (hid) hid.value = userId;

        /* hide search input, show selected chip */
        const inp  = $q(pfx,'Query');
        const clr  = $q(pfx,'Clear');
        const chip = $q(pfx,'Selected');

        if (inp)  { inp.value = ''; inp.style.display = 'none'; }
        if (clr)  clr.classList.remove('visible');
        if (chip) chip.classList.add('show');

        /* fill chip */
        const initials = (fullName || '?').split(' ').map(function(w){ return w[0]; }).slice(0,2).join('').toUpperCase();
        const ava  = $q(pfx,'SelAvatar');
        const nm   = $q(pfx,'SelName');
        const em   = $q(pfx,'SelEmail');
        if (ava) ava.textContent  = initials;
        if (nm)  nm.textContent   = fullName;
        if (em)  em.textContent   = email;

        /* hide validation hint */
        const hint = $q(pfx,'Hint');
        if (hint) hint.style.display = 'none';

        closeDropdown(pfx);
    };

    /* ── Deselect / clear ──────────────────────────────────────── */
    window.csDeselect = function (pfx, mode) {
        const hid  = $q(pfx,'UserId');
        const inp  = $q(pfx,'Query');
        const clr  = $q(pfx,'Clear');
        const chip = $q(pfx,'Selected');

        if (hid)  hid.value = '';
        if (inp)  { inp.value = ''; inp.style.display = ''; inp.focus(); }
        if (clr)  clr.classList.remove('visible');
        if (chip) chip.classList.remove('show');
        closeDropdown(pfx);
    };

    /* ── Close dropdown when clicking outside ─────────────────── */
    document.addEventListener('click', function (e) {
        ['ss','ar'].forEach(function(pfx) {
            const wrap = $q(pfx,'Wrap');
            if (wrap && !wrap.contains(e.target)) closeDropdown(pfx);
        });
    });

    /* ── Add Reservation form: validate customer is selected before submit ── */
    document.addEventListener('DOMContentLoaded', function() {
        /* guard Add Reservation: user_id must be set */
        const arForm = document.getElementById('addReservationForm');
        if (arForm) {
            arForm.addEventListener('submit', function(e) {
                const uid  = document.getElementById('arUserId');
                const hint = document.getElementById('arHint');
                if (!uid || !uid.value) {
                    e.preventDefault();
                    if (hint) hint.style.display = 'block';
                    const inp = document.getElementById('arQuery');
                    if (inp) { inp.focus(); inp.classList.add('cs-error'); }
                    return false;
                }
            });
        }
    });

    /* ── Hook into openModal() to auto-reset customer search widgets ───────
       This is more reliable than querySelectorAll with escaped quotes, and
       works regardless of how the modal is opened (button, JS call, etc.)  */
    const _origOpenModal = window.openModal;
    window.openModal = function(name) {
        if (name === 'addReservation') {
            /* Reset the Add Reservation customer widget */
            if (typeof csDeselect === 'function') csDeselect('ar', 'required');
            /* Also reset the form fields */
            const form = document.getElementById('addReservationForm');
            if (form) {
                const ct = form.querySelector('[name="console_type"]');
                const rm = document.getElementById('resAdminModeSelect');
                const pm = document.getElementById('adminResPlannedMins');
                if (ct) ct.value = '';
                if (rm) { rm.value = 'hourly'; adminResOnModeChange(); }
                if (pm) pm.value = '';
                const rdEl = form.querySelector('[name="reserved_date"]');
                const rtEl = form.querySelector('[name="reserved_time"]');
                const ntEl = form.querySelector('[name="notes"]');
                if (rdEl) rdEl.value = '';
                if (rtEl) rtEl.value = '';
                if (ntEl) ntEl.value = '';
            }
        } else if (name === 'startSession') {
            /* Reset the Start Session customer widget */
            if (typeof csDeselect === 'function') csDeselect('ss', 'walk-in');
        }
        if (typeof _origOpenModal === 'function') _origOpenModal(name);
    };

})();
</script>

<!-- ── Add Controller Modal ──────────────────────────────────────────── -->
<div class="modal" id="addControllerModal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3><i class="fas fa-gamepad" style="color:var(--clr-mint);margin-right:8px;"></i>Add Controller</h3>
            <button class="modal-close" onclick="closeModal('addController')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_controller">


            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Unit Number *</label>
                        <input type="text" name="ctrl_unit_number" required
                               placeholder="e.g. CTRL-01">
                        <div class="form-hint">Must be unique (e.g. CTRL-01)</div>
                    </div>
                    <div class="form-group">
                        <label>Controller Type *</label>
                    <select name="controller_type_id" required
                            onchange="this.form.controller_type.value = this.options[this.selectedIndex].dataset.name">
                        <option value="" disabled selected>— Select Type —</option>
                        <?php foreach ($controllerTypes as $ct): ?>
                            <option value="<?= $ct['type_id'] ?>"
                                    data-name="<?= htmlspecialchars($ct['type_name']) ?>">
                                <?= htmlspecialchars($ct['type_name']) ?>
                                <?php if (!empty($ct['parent_console_name'])): ?>
                                    (<?= htmlspecialchars($ct['parent_console_name']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Hidden: keeps the type name in sync for the legacy controller_type text column -->
                    <input type="hidden" name="controller_type" value="">
                </div>
                </div>


                <div class="form-group">
                    <label>Hourly Rate *</label>
                    <input type="number" step="0.01" name="hourly_rate" required value="20.00"
                           placeholder="e.g. 20.00">
                </div>

                <div class="form-group">
                    <textarea name="controller_notes" rows="2"
                              placeholder="e.g. Minor stick drift, Cable-only..."></textarea>
                </div>

                <!-- Live inventory preview -->
                <div style="background:rgba(32,200,161,.08);border:1px solid rgba(32,200,161,.2);
                            border-radius:10px;padding:14px 16px;margin-top:4px;">
                    <div style="font-size:12px;color:#888;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;font-weight:700;">
                        Current Inventory After Adding
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="font-size:28px;font-weight:900;color:#20c8a1;"><?= ($ctrlTotal ?? 0) + 1 ?></div>
                        <div style="font-size:13px;color:#ccc;">
                            total controllers<br>
                            <span style="color:#20c8a1;font-weight:700;"><?= ($ctrlAvailable ?? 0) + 1 ?> available</span>
                            for rental
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('addController')" class="btn-sec">
                    Cancel
                </button>
                <button type="submit" class="btn-prim">
                    <i class="fas fa-plus"></i> Add Controller
                </button>
            </div>

        </form>
    </div>
</div>

<!-- ── Edit Controller Modal ──────────────────────────────────────────── -->
<div class="modal" id="editControllerModal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:#8aa4e8;margin-right:8px;"></i>Edit Controller</h3>
            <button class="modal-close" onclick="closeModal('editController')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit_controller">
            <input type="hidden" name="controller_id" id="editCtrlId">

            <div class="modal-body">
                <div class="form-group">
                    <label>Unit Number *</label>
                    <input type="text" name="ctrl_unit_number" id="editCtrlUnit" required>
                </div>
                
                <div class="form-group">
                    <label>Hourly Rate *</label>
                    <input type="number" step="0.01" name="hourly_rate" id="editCtrlRate" required>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="controller_notes" id="editCtrlNotes" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('editController')" class="btn-sec">
                    Cancel
                </button>
                <button type="submit" class="btn-prim">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditControllerModal(id, unit, rate, notes) {
    document.getElementById('editCtrlId').value = id;
    document.getElementById('editCtrlUnit').value = unit;
    document.getElementById('editCtrlRate').value = parseFloat(rate).toFixed(2);
    document.getElementById('editCtrlNotes').value = notes;
    openModal('editController');
}
</script>