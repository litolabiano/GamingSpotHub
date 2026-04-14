<!-- ════ MODALS ══════════════════════════════════════════════════════════════ -->

<!-- Start Session Modal -->
<div class="modal" id="startSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-play-circle" style="color:#20c8a1;margin-right:8px"></i>Start New Session</h3>
            <button class="modal-close" onclick="closeModal('startSession')">&times;</button>
        </div>
        <form method="POST" id="startSessionForm" novalidate>
            <input type="hidden" name="action" value="start_session">
            <input type="hidden" name="planned_minutes" id="plannedMinutesInput" value="">
            <!-- Inline validation error banner -->
            <div id="startFormError" style="display:none;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.4);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600;color:#fb566b;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i><span id="startFormErrorMsg"></span>
            </div>
            <div class="form-group">
                <label>Customer *</label>
                <select name="user_id" required>
                    <option value="">— Select customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Console *</label>
                    <select name="console_id" required>
                        <option value="">— Select console —</option>
                        <?php foreach ($availableConsoles as $con): ?>
                        <option value="<?= $con['console_id'] ?>"><?= htmlspecialchars($con['unit_number']) ?> — <?= $con['console_type'] ?> (₱<?= $con['hourly_rate'] ?>/hr)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rental Mode *</label>
                    <select name="rental_mode" id="rentalModeSelect" required onchange="onRentalModeChange()">
                        <option value="hourly">Hourly (pre-booked)</option>
                        <option value="open_time">Open Time (bracket pricing)</option>
                        <option value="unlimited">Unlimited (flat ₱<?= htmlspecialchars($settings['unlimited_rate'] ?? '300') ?>)</option>
                    </select>
                </div>
            </div>

            <!-- Duration picker — shown only for Hourly mode -->
            <div class="form-group" id="durationPickerGroup">
                <label>Duration *</label>
                <select id="durationSelect" onchange="updateSessionPreview()">
                    <option value="">— Select duration —</option>
                    <option value="30">30 minutes — ₱50</option>
                    <option value="60">1 hour — ₱80</option>
                    <option value="90">1 hr 30 min — ₱120</option>
                    <option value="120">2 hours — ₱160</option>
                    <option value="150">2 hrs 30 min — ₱200</option>
                    <option value="180">3 hours — ₱240</option>
                    <option value="210">3 hrs 30 min — ₱280</option>
                    <option value="240">4 hours — ₱320</option>
                    <option value="270">4 hrs 30 min — ₱360</option>
                    <option value="300">5 hours — ₱400</option>
                    <option value="330">5 hrs 30 min — ₱440</option>
                    <option value="360">6 hours — ₱480</option>
                    <option value="390">6 hrs 30 min — ₱520</option>
                    <option value="420">7 hours — ₱560</option>
                    <option value="450">7 hrs 30 min — ₱600</option>
                    <option value="480">8 hours — ₱640</option>
                </select>
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
                                <option value="credit_card">💳 Credit Card</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:6px">
                            <label>Amount Tendered (₱)</label>
                            <input type="number" id="startTendered" name="start_amount_tendered" min="0" step="1" placeholder="e.g. 200"
                                   style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:16px;"
                                   oninput="calcChange('startTendered','startChangeDisplay','startCostAmt')">
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
                            <option value="credit_card">💳 Credit Card</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:6px">
                        <label>Amount Tendered (₱)</label>
                        <input type="number" id="unlimTendered" name="unlim_amount_tendered" min="0" step="1" placeholder="e.g. 400"
                               style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:16px;"
                               oninput="calcChange('unlimTendered','unlimChangeDisplay','unlimCostAmt')">
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

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                <i class="fas fa-play"></i> Start Session
            </button>
        </form>
    </div>
</div>

<!-- End Session Modal -->
<div class="modal" id="endSessionModal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title" id="endSessionModalTitle">
                <i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session
            </h3>
            <button class="modal-close" onclick="closeModal('endSession')">&times;</button>
        </div>

        <!-- Session info summary -->
        <div style="background:rgba(251,86,107,.08);border:1px solid rgba(251,86,107,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:14px">
            <strong id="endSessionSummary">—</strong>
        </div>

        <!-- Cost / elapsed read-only panel -->
        <div id="endCostPanel" style="display:none;background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Elapsed Time</div>
                    <div id="endElapsed" style="font-size:18px;font-weight:700;color:#f1e1aa;font-family:monospace;">—</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Estimated Cost</div>
                    <div id="endEstCost" style="font-size:26px;font-weight:800;color:#20c8a1;">—</div>
                </div>
            </div>
            <div id="endCostNote" style="margin-top:10px;font-size:12px;color:#aaa;"></div>
        </div>

        <!-- Outstanding balance alert (shown when payment is still due) -->
        <div id="endPayWarning" style="display:none;background:rgba(241,168,60,.1);border:1px solid rgba(241,168,60,.35);border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:#f1a83c;">
            <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
            <strong>Outstanding payment of <span id="endPayWarningAmt">₱0.00</span> is still due.</strong>
            Please collect payment via the <strong>Pay</strong> button before or after ending.
        </div>

        <!-- Prepaid note (shown when fully paid) -->
        <div id="endPrepaidNote" style="display:none;background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:#20c8a1;">
            <i class="fas fa-check-circle"></i> <strong>Payment fully collected.</strong> No outstanding balance.
        </div>

        <form method="POST" id="endSessionForm" novalidate>
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="session_id" id="endSessionId">

            <div style="display:flex;gap:10px;">
                <!-- Collect Pay first (shown when balance due) -->
                <button type="button" id="endPayFirstBtn" style="display:none;flex:1;padding:13px;border-radius:10px;
                    background:linear-gradient(135deg,#20c8a1,#5f85da);border:none;color:#fff;
                    font-size:14px;font-weight:700;cursor:pointer;transition:.2s;"
                    onclick="_endPayFirst()">
                    <i class="fas fa-peso-sign"></i> Collect Payment First
                </button>
                <!-- Confirm end -->
                <button type="submit" class="btn btn-danger" id="endSessionConfirmBtn"
                    style="flex:1;justify-content:center;">
                    <i class="fas fa-stop-circle"></i>
                    <span id="endSessionConfirmLabel">Confirm End Session</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Refund Session Modal ══════════════════════════════════════════════════ -->
<div class="modal" id="refundSessionModal">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-undo-alt" style="color:#f1a83c;margin-right:8px"></i>Issue Refund
            </h3>
            <button class="modal-close" onclick="closeModal('refundSession')">&times;</button>
        </div>

        <div style="background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px;">
            <strong id="refundSessionInfo">—</strong>
        </div>

        <div style="background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;display:flex;justify-content:space-between;align-items:center;">
            <span style="color:#aaa;">Total Collected</span>
            <span id="refundMaxDisplay" style="font-size:18px;font-weight:800;color:#20c8a1;">₱0.00</span>
        </div>

        <form method="POST" id="refundForm" novalidate>
            <input type="hidden" name="action" value="refund_session">
            <input type="hidden" name="_section" value="sessions">
            <input type="hidden" name="session_id" id="refundSessionId">
            <input type="hidden" name="payment_method" id="refundMethodHidden" value="cash">

            <div id="refundFormError" style="display:none;background:rgba(251,86,107,.12);border:1px solid rgba(251,86,107,.4);border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#fb566b;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i><span id="refundFormErrorMsg"></span>
            </div>

            <div class="form-group">
                <label>Refund Amount (₱)</label>
                <input type="number" id="refundAmount" name="refund_amount" min="1" step="1" placeholder="e.g. 80"
                       style="font-size:20px;font-weight:700;text-align:center;">
            </div>

            <div id="refundPresets" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;"></div>

            <div class="form-group">
                <label>Refund Method</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                    <button type="button" class="refund-method-btn active" data-method="cash"
                        onclick="selectRefundMethod(this,'cash')"
                        style="padding:10px;border-radius:8px;border:1px solid rgba(32,200,161,.4);background:rgba(32,200,161,.12);color:#20c8a1;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;">
                        💵 Cash
                    </button>
                    <button type="button" class="refund-method-btn" data-method="gcash"
                        onclick="selectRefundMethod(this,'gcash')"
                        style="padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#aaa;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;">
                        📱 GCash
                    </button>
                    <button type="button" class="refund-method-btn" data-method="credit_card"
                        onclick="selectRefundMethod(this,'credit_card')"
                        style="padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#aaa;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;">
                        💳 Card
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>Reason <span style="font-weight:400;color:#666;">(optional)</span></label>
                <textarea name="refund_reason" id="refundReason" placeholder="e.g. Customer cancelled after 10 min, console issue..."></textarea>
            </div>

            <button type="button" class="btn btn-primary"
                style="width:100%;justify-content:center;background:linear-gradient(135deg,#f1a83c,#fb566b);border:none;"
                onclick="submitRefund()">
                <i class="fas fa-undo-alt"></i> Confirm Refund
            </button>
        </form>
    </div>
</div>

<!-- ══ Extend Session Modal ══════════════════════════════════════════════════ -->
<div class="modal" id="extendSessionModal">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-clock" style="color:#5f85da;margin-right:8px"></i>Extend Session
            </h3>
            <button class="modal-close" onclick="closeModal('extendSession')">&times;</button>
        </div>

        <div style="background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px;">
            <strong id="extendSessionInfo">—</strong>
        </div>

        <div style="background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Current Booked</div>
                <div id="extendCurrentBooked" style="font-size:20px;font-weight:800;color:#f1e1aa;">—</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">New Total</div>
                <div id="extendNewTotal" style="font-size:20px;font-weight:800;color:#20c8a1;">—</div>
            </div>
        </div>

        <form method="POST" id="extendForm" novalidate>
            <input type="hidden" name="action" value="extend_session">
            <input type="hidden" name="_section" value="sessions">
            <input type="hidden" name="session_id" id="extendSessionId">

            <div class="form-group">
                <label>Quick Add</label>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:4px;">
                    <button type="button" class="extend-preset-btn" data-min="30"
                        onclick="selectExtendPreset(this,30)"
                        style="padding:12px 0;border-radius:8px;border:1px solid rgba(95,133,218,.3);background:rgba(95,133,218,.1);color:#8aa4e8;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;">
                        +30m
                    </button>
                    <button type="button" class="extend-preset-btn" data-min="60"
                        onclick="selectExtendPreset(this,60)"
                        style="padding:12px 0;border-radius:8px;border:1px solid rgba(95,133,218,.3);background:rgba(95,133,218,.1);color:#8aa4e8;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;">
                        +1h
                    </button>
                    <button type="button" class="extend-preset-btn" data-min="90"
                        onclick="selectExtendPreset(this,90)"
                        style="padding:12px 0;border-radius:8px;border:1px solid rgba(95,133,218,.3);background:rgba(95,133,218,.1);color:#8aa4e8;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;">
                        +1.5h
                    </button>
                    <button type="button" class="extend-preset-btn" data-min="120"
                        onclick="selectExtendPreset(this,120)"
                        style="padding:12px 0;border-radius:8px;border:1px solid rgba(95,133,218,.3);background:rgba(95,133,218,.1);color:#8aa4e8;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;">
                        +2h
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>Custom Minutes</label>
                <input type="number" id="extendMinutes" name="add_minutes" min="1" step="1" placeholder="e.g. 45"
                       style="font-size:18px;font-weight:700;text-align:center;"
                       oninput="onExtendInput(this.value)">
            </div>

            <div id="extendCostNote" style="display:none;background:rgba(241,225,170,.07);border:1px solid rgba(241,225,170,.2);border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#f1e1aa;">
                <i class="fas fa-info-circle" style="margin-right:5px;"></i>
                Extra cost for <span id="extendCostMinutes"></span>: <strong id="extendCostAmt"></strong>
                <span style="color:#888;font-size:12px;margin-left:4px;">(collect at end)</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;background:linear-gradient(135deg,#5f85da,#20c8a1);border:none;">
                <i class="fas fa-plus-circle"></i> Confirm Extension
            </button>
        </form>
    </div>
</div>

<script>
/* ══════════════════════════════════════════════════════════════════════
   Refund Session Modal JS
   ══════════════════════════════════════════════════════════════════════ */
var _refundMax = 0;

function openRefundModal(sessionId, customerName, unitNumber, totalPaid) {
    _refundMax = totalPaid || 0;
    document.getElementById('refundSessionId').value = sessionId;
    document.getElementById('refundSessionInfo').textContent =
        'Refund for session #' + sessionId + ' — ' + customerName + ' on ' + unitNumber;
    document.getElementById('refundMaxDisplay').textContent = '₱' + _refundMax.toFixed(2);
    document.getElementById('refundAmount').value = '';
    document.getElementById('refundReason').value = '';
    document.getElementById('refundFormError').style.display = 'none';

    var presets = document.getElementById('refundPresets');
    presets.innerHTML = '';
    [['25%', 0.25], ['50%', 0.50], ['Full', 1.0]].forEach(function(pair) {
        var lbl = pair[0], pct = pair[1];
        var amt = (_refundMax * pct).toFixed(0);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = lbl + ' — ₱' + amt;
        btn.style.cssText = 'flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#ccc;font-size:12px;font-weight:600;cursor:pointer;transition:.2s;';
        btn.onclick = function() { document.getElementById('refundAmount').value = amt; };
        presets.appendChild(btn);
    });

    document.querySelectorAll('.refund-method-btn').forEach(function(b) {
        var active = b.dataset.method === 'cash';
        b.style.borderColor = active ? 'rgba(32,200,161,.4)' : 'rgba(255,255,255,.12)';
        b.style.background  = active ? 'rgba(32,200,161,.12)' : 'rgba(255,255,255,.04)';
        b.style.color       = active ? '#20c8a1' : '#aaa';
    });
    document.getElementById('refundMethodHidden').value = 'cash';
    openModal('refundSession');
}

function selectRefundMethod(btn, method) {
    document.querySelectorAll('.refund-method-btn').forEach(function(b) {
        b.style.borderColor = 'rgba(255,255,255,.12)';
        b.style.background  = 'rgba(255,255,255,.04)';
        b.style.color       = '#aaa';
    });
    btn.style.borderColor = 'rgba(32,200,161,.4)';
    btn.style.background  = 'rgba(32,200,161,.12)';
    btn.style.color       = '#20c8a1';
    document.getElementById('refundMethodHidden').value = method;
}

function submitRefund() {
    var amt = parseFloat(document.getElementById('refundAmount').value) || 0;
    var errDiv = document.getElementById('refundFormError');
    var errMsg = document.getElementById('refundFormErrorMsg');
    if (amt <= 0) {
        errMsg.textContent = 'Please enter a refund amount.';
        errDiv.style.display = 'block'; return;
    }
    if (amt > _refundMax) {
        errMsg.textContent = 'Cannot exceed ₱' + _refundMax.toFixed(2) + ' (total collected).';
        errDiv.style.display = 'block'; return;
    }
    errDiv.style.display = 'none';
    document.getElementById('refundForm').submit();
}

/* ══════════════════════════════════════════════════════════════════════
   Extend Session Modal JS
   ══════════════════════════════════════════════════════════════════════ */
var _extendCurrentMinutes = 0;

function openExtendModal(sessionId, customerName, unitNumber, currentPlanned) {
    _extendCurrentMinutes = currentPlanned || 0;
    document.getElementById('extendSessionId').value = sessionId;
    document.getElementById('extendSessionInfo').textContent =
        'Extend session #' + sessionId + ' — ' + customerName + ' on ' + unitNumber;

    var ch = Math.floor(_extendCurrentMinutes / 60);
    var cm = _extendCurrentMinutes % 60;
    document.getElementById('extendCurrentBooked').textContent =
        _extendCurrentMinutes > 0 ? (ch ? ch + 'h ' : '') + (cm ? cm + 'm' : (ch ? '' : '—')) : '—';
    document.getElementById('extendNewTotal').textContent = '—';
    document.getElementById('extendMinutes').value = '';
    document.getElementById('extendCostNote').style.display = 'none';

    document.querySelectorAll('.extend-preset-btn').forEach(function(b) {
        b.style.borderColor = 'rgba(95,133,218,.3)';
        b.style.background  = 'rgba(95,133,218,.1)';
        b.style.color       = '#8aa4e8';
    });
    openModal('extendSession');
}

function selectExtendPreset(btn, minutes) {
    document.querySelectorAll('.extend-preset-btn').forEach(function(b) {
        b.style.borderColor = 'rgba(95,133,218,.3)';
        b.style.background  = 'rgba(95,133,218,.1)';
        b.style.color       = '#8aa4e8';
    });
    btn.style.borderColor = 'rgba(32,200,161,.5)';
    btn.style.background  = 'rgba(32,200,161,.15)';
    btn.style.color       = '#20c8a1';
    document.getElementById('extendMinutes').value = minutes;
    onExtendInput(minutes);
}

function onExtendInput(val) {
    document.querySelectorAll('.extend-preset-btn').forEach(function(b) {
        if (parseInt(b.dataset.min) === parseInt(val)) {
            b.style.borderColor = 'rgba(32,200,161,.5)';
            b.style.background  = 'rgba(32,200,161,.15)';
            b.style.color       = '#20c8a1';
        } else {
            b.style.borderColor = 'rgba(95,133,218,.3)';
            b.style.background  = 'rgba(95,133,218,.1)';
            b.style.color       = '#8aa4e8';
        }
    });

    var add = parseInt(val) || 0;
    if (add <= 0) {
        document.getElementById('extendNewTotal').textContent = '—';
        document.getElementById('extendCostNote').style.display = 'none';
        return;
    }
    var newTotal = _extendCurrentMinutes + add;
    var nh = Math.floor(newTotal / 60);
    var nm = newTotal % 60;
    document.getElementById('extendNewTotal').textContent =
        (nh ? nh + 'h ' : '') + (nm ? nm + 'm' : '');

    // Extra cost estimate
    var extraCost = _timedCost(add);
    if (extraCost > 0) {
        var ah = Math.floor(add / 60);
        var am = add % 60;
        document.getElementById('extendCostMinutes').textContent =
            (ah ? ah + 'h ' : '') + (am ? am + 'm' : '');
        document.getElementById('extendCostAmt').textContent = '₱' + extraCost.toFixed(2);
        document.getElementById('extendCostNote').style.display = 'block';
    } else {
        document.getElementById('extendCostNote').style.display = 'none';
    }
}
</script>

<!-- ══ Pay / Collect Payment Modal (active session, no end) ════════════════ -->
<div class="modal" id="paySessionModal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h3 class="modal-title" id="payModalTitle">
                <i class="fas fa-peso-sign" style="color:#20c8a1;margin-right:8px"></i>Collect Payment
            </h3>
            <button class="modal-close" onclick="closePayModal()">&times;</button>
        </div>

        <!-- Session info banner -->
        <div style="background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px;">
            <strong id="paySessionInfo">—</strong>
        </div>

        <!-- Live cost panel -->
        <div id="payCostPanel" style="display:none;background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Elapsed Time</div>
                    <div id="payElapsed" style="font-size:18px;font-weight:700;color:#f1e1aa;font-family:monospace;">—</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Estimated Cost</div>
                    <div id="payEstCost" style="font-size:26px;font-weight:800;color:#20c8a1;">—</div>
                </div>
            </div>
            <div id="payCostNote" style="margin-top:10px;font-size:12px;color:#aaa;"></div>
        </div>

        <form method="POST" id="paySessionForm" novalidate>
            <input type="hidden" name="action" value="collect_payment">
            <input type="hidden" name="_section" value="sessions">
            <input type="hidden" name="session_id" id="paySessionId">

            <!-- Amount to Pay big display -->
            <div id="payAmountDueBox" style="display:none;background:linear-gradient(135deg,rgba(32,200,161,.12),rgba(95,133,218,.08));border:1px solid rgba(32,200,161,.3);border-radius:12px;padding:18px 20px;margin-bottom:16px;text-align:center;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin-bottom:6px;">
                    <i class="fas fa-receipt" style="margin-right:4px;"></i> Amount to Collect
                </div>
                <div id="payAmountDueDisplay" style="font-size:42px;font-weight:900;color:#20c8a1;line-height:1;letter-spacing:-1px;">₱0.00</div>
                <div id="payAmountDueLabel" style="font-size:12px;color:#888;margin-top:6px;"></div>
            </div>

            <div class="form-group" id="payPaymentGroup">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
                    <option value="credit_card">💳 Credit Card</option>
                </select>

                <div style="margin-top:12px;">
                    <label style="font-size:12px;color:#aaa;text-transform:uppercase;letter-spacing:.5px;">Amount Tendered (₱)</label>
                    <input type="number" id="payTendered" name="pay_tendered_display" min="0" step="1" placeholder="e.g. 200"
                           style="width:100%;margin-top:6px;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:18px;font-weight:700;"
                           oninput="calcPayChange()">
                </div>
                <!-- Hidden actual amount to record -->
                <input type="hidden" id="payAmountActual" name="pay_amount" value="0">
                <div id="payChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:16px;font-weight:800;margin-top:8px;"></div>
            </div>

            <button type="button" class="btn btn-primary" id="payConfirmBtn"
                style="width:100%;justify-content:center;background:linear-gradient(135deg,#20c8a1,#5f85da);border:none;"
                onclick="submitPayModal()">
                <i class="fas fa-check-circle"></i>
                <span id="payConfirmLabel">Collect Payment</span>
            </button>
        </form>
    </div>
</div>

<script>
/* ══════════════════════════════════════════════════════════════════════
   Pay / Collect Payment Modal JS
   ══════════════════════════════════════════════════════════════════════ */
var _payTimer       = null;
var _payDue         = 0;   // amount that must be collected

function closePayModal() {
    if (_payTimer) { clearInterval(_payTimer); _payTimer = null; }
    closeModal('paySession');
}

function openPayModal(sessionId, customerName, unitNumber, mode, startTs, plannedMinutes, upfrontPaid, unlimRate) {
    upfrontPaid   = upfrontPaid   || 0;
    unlimRate     = unlimRate     || 0;
    plannedMinutes = plannedMinutes || 0;

    // Parse Manila-time datetime string to Unix epoch
    if (typeof startTs === 'string') {
        startTs = Math.floor(new Date(startTs.replace(' ', 'T') + '+08:00').getTime() / 1000);
    }

    if (_payTimer) { clearInterval(_payTimer); _payTimer = null; }

    document.getElementById('paySessionId').value = sessionId;
    document.getElementById('payTendered').value  = '';
    document.getElementById('payChangeDisplay').style.display = 'none';
    document.getElementById('payAmountActual').value = '0';
    _payDue = 0;

    var modeLabel = mode === 'open_time' ? 'Open Time'
                  : mode === 'unlimited' ? 'Unlimited'
                  : 'Hourly';
    document.getElementById('paySessionInfo').textContent =
        'Session #' + sessionId + ' — ' + customerName + ' on ' + unitNumber + ' (' + modeLabel + ')';

    var panel      = document.getElementById('payCostPanel');
    var elapsedEl  = document.getElementById('payElapsed');
    var costEl     = document.getElementById('payEstCost');
    var noteEl     = document.getElementById('payCostNote');
    var dueBox     = document.getElementById('payAmountDueBox');
    var dueDisplay = document.getElementById('payAmountDueDisplay');
    var dueLabel   = document.getElementById('payAmountDueLabel');
    var confirmLbl = document.getElementById('payConfirmLabel');

    function setDue(amount, sublabel) {
        _payDue = amount;
        document.getElementById('payAmountActual').value = amount.toFixed(2);
        dueDisplay.textContent = '₱' + amount.toFixed(2);
        if (sublabel !== undefined) dueLabel.textContent = sublabel;
        dueBox.style.display = 'block';
        confirmLbl.textContent = 'Collect ₱' + amount.toFixed(2);
    }

    /* ── OPEN TIME: live ticking ── */
    if (mode === 'open_time' && startTs) {
        panel.style.display = 'block';
        noteEl.innerHTML = '<i class="fas fa-info-circle"></i> Open time — cost accumulates while session is active.';

        function tickPay() {
            var elapsed = Math.floor(Date.now() / 1000 - startTs);
            var mins    = Math.floor(elapsed / 60);
            var secs    = elapsed % 60;
            var h = Math.floor(mins / 60), m = mins % 60;
            elapsedEl.textContent = (h ? h + 'h ' : '') + String(m).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
            var dueCost   = _timedCost(mins);
            costEl.textContent = '₱' + dueCost.toFixed(2);
            var remaining = Math.max(0, dueCost - upfrontPaid);
            if (remaining > 0) {
                setDue(remaining, 'Est. ₱' + dueCost.toFixed(2) + (upfrontPaid > 0 ? ' — Prepaid: ₱' + upfrontPaid.toFixed(2) : ''));
            } else {
                dueBox.style.display = 'none';
                _payDue = 0;
                confirmLbl.textContent = 'Collect Payment';
            }
            if (document.getElementById('payTendered').value) calcPayChange();
        }
        tickPay();
        _payTimer = setInterval(tickPay, 1000);

    /* ── HOURLY ── */
    } else if (mode === 'hourly' && plannedMinutes && startTs) {
        panel.style.display = 'block';
        var elapsed  = Math.floor(Date.now() / 1000 - startTs);
        var mins     = Math.floor(elapsed / 60);
        var cost     = _hourlyCost(mins, plannedMinutes);
        var base     = plannedMinutes <= 30 ? 50 : (plannedMinutes / 60 * 80);
        var overtime = mins - plannedMinutes;
        var ph       = Math.floor(plannedMinutes / 60);
        var pm       = plannedMinutes % 60;
        var bookedStr = (ph ? ph + 'h ' : '') + (pm ? pm + 'm' : '');

        elapsedEl.textContent = String(Math.floor(mins/60)).padStart(2,'0') + 'h ' + String(mins%60).padStart(2,'0') + 'm';
        costEl.textContent    = '₱' + cost.toFixed(2);

        var remaining = Math.max(0, cost - upfrontPaid);
        if (remaining > 0) {
            var sub = overtime > 0
                ? 'Base ₱' + base.toFixed(2) + ' + overtime — Total: ₱' + cost.toFixed(2) + (upfrontPaid > 0 ? ' — Prepaid: ₱' + upfrontPaid.toFixed(2) : '')
                : 'Session cost ₱' + cost.toFixed(2) + (upfrontPaid > 0 ? ' — Prepaid: ₱' + upfrontPaid.toFixed(2) : '');
            setDue(remaining, sub);
            if (overtime > 0) {
                noteEl.innerHTML = '<i class="fas fa-clock"></i> Booked: <strong>' + bookedStr + '</strong> — Overtime: +' + overtime + ' min.';
            } else {
                noteEl.innerHTML = '<i class="fas fa-coins"></i> Partial payment recorded (₱' + upfrontPaid.toFixed(2) + ' of ₱' + cost.toFixed(2) + '). Collect remaining <strong>₱' + remaining.toFixed(2) + '</strong>.';
            }
        } else {
            noteEl.innerHTML = '<i class="fas fa-check-circle" style="color:#20c8a1"></i> Fully paid — no additional charge at this time.';
            dueBox.style.display = 'none';
            document.getElementById('payAmountActual').value = '0';
            confirmLbl.textContent = 'Record Extra Payment';
        }

    /* ── UNLIMITED ── */
    } else if (mode === 'unlimited') {
        panel.style.display = 'block';
        elapsedEl.textContent = '—';
        costEl.textContent    = unlimRate ? '₱' + unlimRate.toFixed(2) : 'Flat rate';
        var remaining = unlimRate > 0 ? Math.max(0, unlimRate - upfrontPaid) : 0;
        if (remaining > 0) {
            setDue(remaining, 'Flat rate ₱' + unlimRate.toFixed(2) + ' — Prepaid: ₱' + upfrontPaid.toFixed(2));
            noteEl.innerHTML = '<i class="fas fa-coins"></i> Partial payment recorded. Collect remaining <strong>₱' + remaining.toFixed(2) + '</strong>.';
        } else {
            noteEl.innerHTML = '<i class="fas fa-infinity"></i> Flat rate already collected in full.';
            dueBox.style.display = 'none';
            confirmLbl.textContent = 'Record Extra Payment';
        }

    } else {
        panel.style.display = 'none';
        confirmLbl.textContent = 'Collect Payment';
    }

    openModal('paySession');
}

function calcPayChange() {
    var tendered  = parseFloat(document.getElementById('payTendered').value) || 0;
    var due       = _payDue;
    var disp      = document.getElementById('payChangeDisplay');
    if (!tendered) { disp.style.display = 'none'; return; }
    var diff = tendered - due;
    disp.style.display = 'block';
    if (diff >= 0) {
        disp.style.background = 'rgba(32,200,161,.15)';
        disp.style.border     = '1px solid rgba(32,200,161,.3)';
        disp.style.color      = '#20c8a1';
        disp.innerHTML        = '<i class="fas fa-coins"></i> Change: <strong>₱' + diff.toFixed(2) + '</strong>';
    } else {
        disp.style.background = 'rgba(251,86,107,.15)';
        disp.style.border     = '1px solid rgba(251,86,107,.3)';
        disp.style.color      = '#fb566b';
        disp.innerHTML        = '<i class="fas fa-exclamation-circle"></i> Short by <strong>₱' + Math.abs(diff).toFixed(2) + '</strong>';
    }
}

function submitPayModal() {
    var actual = parseFloat(document.getElementById('payAmountActual').value) || 0;
    var tendered = parseFloat(document.getElementById('payTendered').value) || 0;

    // If a custom tendered amount was entered, record whichever is correct
    if (tendered > 0 && actual === 0) {
        // No computed due — just record what's tendered
        document.getElementById('payAmountActual').value = tendered.toFixed(2);
    } else if (tendered > 0 && tendered < actual) {
        // Partial — record what was given
        document.getElementById('payAmountActual').value = tendered.toFixed(2);
    }
    // else record computed due amount as-is

    if (parseFloat(document.getElementById('payAmountActual').value) <= 0) {
        alert('Please enter the amount to collect.');
        return;
    }
    document.getElementById('paySessionForm').submit();
}

// Clear pay timer when modal is closed via outside click
document.addEventListener('DOMContentLoaded', function () {
    var payModal = document.getElementById('paySessionModal');
    if (payModal) {
        payModal.addEventListener('click', function (e) {
            if (e.target === payModal) closePayModal();
        });
    }
});
</script>
