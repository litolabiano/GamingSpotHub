<!-- ════ MODALS ══════════════════════════════════════════════════════════════ -->

<!-- Start Session Modal -->
<div class="modal" id="startSessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-play-circle" style="color:#20c8a1;margin-right:8px"></i>Start New Session</h3>
            <button class="modal-close" onclick="closeModal('startSession')">&times;</button>
        </div>
        <form method="POST" id="startSessionForm">
            <input type="hidden" name="action" value="start_session">
            <input type="hidden" name="planned_minutes" id="plannedMinutesInput" value="">
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
                            <input type="number" id="startTendered" min="0" step="1" placeholder="e.g. 200"
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
                        <input type="number" id="unlimTendered" min="0" step="1" placeholder="e.g. 400"
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
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="endSessionModalTitle"><i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session &amp; Collect Payment</h3>
            <button class="modal-close" onclick="closeModal('endSession')">&times;</button>
        </div>

        <!-- Session info summary -->
        <div style="background:rgba(251,86,107,.08);border:1px solid rgba(251,86,107,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:14px">
            <strong id="endSessionSummary">—</strong>
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
                </div>
            </div>
            <div id="endCostNote" style="margin-top:10px;font-size:12px;color:#aaa;"></div>
        </div>

        <form method="POST" id="endSessionForm">
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="session_id" id="endSessionId">
            <!-- Synced with the visible endTendered input on submit -->
            <input type="hidden" name="tendered_amount" id="endTenderedHidden">

            <!-- Shown for open_time / hourly-with-overtime; hidden for unlimited -->
            <div class="form-group" id="endPaymentMethodGroup">

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
                    <option value="credit_card">💳 Credit Card</option>
                </select>

                <!-- Tendered amount -->
                <div style="margin-top:12px">
                    <label style="font-size:12px;color:#aaa;text-transform:uppercase;letter-spacing:.5px;">Amount Tendered (₱)</label>
                    <input type="number" id="endTendered" min="0" step="1" placeholder="e.g. 200"
                           style="width:100%;margin-top:6px;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:18px;font-weight:700;"
                           oninput="calcChange('endTendered','endChangeDisplay','endCostAmtHolder')">
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
            <div id="endPrepaidNote" style="display:none;background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:#20c8a1;">
                <i class="fas fa-check-circle"></i> <strong>Payment already collected at session start.</strong> No additional charge at end.
            </div>

            <button type="submit" class="btn btn-danger" id="endSessionConfirmBtn"
                    style="width:100%;justify-content:center;margin-top:4px;">
                <i class="fas fa-check-circle"></i> <span id="endSessionConfirmLabel">Confirm End &amp; Record Payment</span>
            </button>
        </form>
    </div>
</div>

<!-- ════ ADD RESERVATION MODAL (admin) ══════════════════════════════════ -->
<div class="modal" id="addReservationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-calendar-plus" style="color:#20c8a1;margin-right:8px;"></i>Add Reservation
            </h3>
            <button class="modal-close" onclick="closeModal('addReservation')">&times;</button>
        </div>
        <form method="POST" id="addReservationForm">
            <input type="hidden" name="action" value="add_reservation">
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
                    <label>Console Type *</label>
                    <select name="console_type" required>
                        <option value="">— Select —</option>
                        <option value="PS5">PS5</option>
                        <option value="Xbox Series X">Xbox Series X</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rental Mode *</label>
                    <select name="rental_mode" id="resAdminModeSelect" required onchange="adminResOnModeChange()">
                        <option value="hourly">Hourly</option>
                        <option value="open_time">Open Time</option>
                        <option value="unlimited">Unlimited</option>
                    </select>
                </div>
            </div>
            <div id="adminResDurGroup" class="form-group">
                <label>Duration *</label>
                <select name="planned_minutes" id="adminResPlannedMins">
                    <option value="">— Select —</option>
                    <option value="30">30 min — ₱50</option>
                    <option value="60">1 hr — ₱80</option>
                    <option value="90">1h 30m — ₱120</option>
                    <option value="120">2 hrs — ₱160</option>
                    <option value="150">2h 30m — ₱200</option>
                    <option value="180">3 hrs — ₱240</option>
                    <option value="240">4 hrs — ₱320</option>
                    <option value="300">5 hrs — ₱400</option>
                    <option value="360">6 hrs — ₱480</option>
                    <option value="480">8 hrs — ₱640</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="reserved_date" required min="<?= date('Y-m-d') ?>"
                           style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:14px;">
                </div>
                <div class="form-group">
                    <label>Time *</label>
                    <input type="time" name="reserved_time" required
                           style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:14px;">
                </div>
            </div>
            <div class="form-group">
                <label>Downpayment Amount (₱)</label>
                <input type="number" name="downpayment_amount" id="adminDpAmount" min="0" step="10"
                       placeholder="₱0 — leave blank to skip" onchange="adminDpChange()"
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:14px;">
            </div>
            <div class="form-group" id="adminDpMethodGroup" style="display:none;">
                <label>Payment Method</label>
                <select name="downpayment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
                    <option value="credit_card">💳 Credit Card</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2"
                          style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:14px;resize:vertical;"
                          placeholder="Any notes…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fas fa-calendar-check"></i> Save Reservation
            </button>
        </form>
    </div>
</div>

<!-- ════ CONVERT RESERVATION → SESSION MODAL ══════════════════════════ -->
<div class="modal" id="convertReservationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-play-circle" style="color:#20c8a1;margin-right:8px;"></i>Start Session from Reservation
            </h3>
            <button class="modal-close" onclick="closeModal('convertReservation')">&times;</button>
        </div>
        <div id="convertResInfo" style="background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;color:#aaa;"></div>
        <form method="POST" id="convertReservationForm">
            <input type="hidden" name="action" value="convert_reservation">
            <input type="hidden" name="reservation_id" id="convertResId">
            <div class="form-group">
                <label>Assign Console Unit *</label>
                <select name="console_id" id="convertConsoleSelect" required>
                    <option value="">— Select available console —</option>
                    <?php foreach ($availableConsoles as $con): ?>
                    <option value="<?= $con['console_id'] ?>" data-type="<?= htmlspecialchars($con['console_type']) ?>">
                        <?= htmlspecialchars($con['unit_number']) ?> — <?= $con['console_type'] ?> (₱<?= $con['hourly_rate'] ?>/hr)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;">
                <i class="fas fa-play"></i> Start Session Now
            </button>
        </form>
    </div>
</div>

<script>
/* ── Reservation modal helpers ───────────────────────────────────── */
function adminResOnModeChange() {
    const mode = document.getElementById('resAdminModeSelect').value;
    document.getElementById('adminResDurGroup').style.display = (mode === 'hourly') ? 'block' : 'none';
}

function adminDpChange() {
    const amt = parseFloat(document.getElementById('adminDpAmount').value) || 0;
    document.getElementById('adminDpMethodGroup').style.display = amt > 0 ? 'block' : 'none';
}

function openConvertModal(res) {
    document.getElementById('convertResId').value = res.reservation_id;

    const mode   = res.rental_mode === 'open_time' ? 'Open Time' : res.rental_mode.charAt(0).toUpperCase() + res.rental_mode.slice(1);
    const dur    = res.planned_minutes ? ` — ${res.planned_minutes/60}h` : '';
    document.getElementById('convertResInfo').innerHTML =
        `<strong style="color:#fff;">${res.customer_name}</strong><br>` +
        `${res.console_type} · ${mode}${dur}<br>` +
        `${new Date(res.reserved_date).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})} at ${res.reserved_time.slice(0,5)}<br>` +
        (res.downpayment_amount > 0 ? `<span style="color:#20c8a1;">Downpayment: ₱${parseFloat(res.downpayment_amount).toFixed(2)} (${res.downpayment_method})</span>` : '');

    // Filter console dropdown to matching type
    const sel = document.getElementById('convertConsoleSelect');
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (opt.dataset.type === res.console_type) ? '' : 'none';
    });

    openModal('convertReservation');
}
</script>
