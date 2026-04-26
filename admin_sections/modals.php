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
            <button id="gspotConfirmNo" style="
                flex:1;padding:11px;border-radius:10px;
                background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);
                color:#aaa;font-size:14px;font-weight:600;cursor:pointer;
                transition:.18s;font-family:inherit;
            " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                <i class="fas fa-times" style="margin-right:6px"></i>Cancel
            </button>
            <button id="gspotConfirmYes" style="
                flex:1;padding:11px;border-radius:10px;
                background:linear-gradient(135deg,#20c8a1,#5f85da);
                border:none;color:#fff;font-size:14px;font-weight:700;cursor:pointer;
                box-shadow:0 4px 16px rgba(32,200,161,0.3);transition:.18s;font-family:inherit;
            " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
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
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-play-circle" style="color:#20c8a1;margin-right:8px"></i>Start New Session</h3>
            <button class="modal-close" onclick="closeModal('startSession')">&times;</button>
        </div>
        <form method="POST" id="startSessionForm">
            <input type="hidden" name="action" value="start_session">
            <input type="hidden" name="planned_minutes" id="plannedMinutesInput" value="">
            <div class="form-group">
                <label>Customer <span style="color:#888;font-size:11px;font-weight:400;">(optional — leave blank for walk-in)</span></label>
                <select name="user_id">
                    <option value="">— Walk-in / No account —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Console *</label>
                    <select name="console_id" required>
                        <option value="" disabled selected>— Select console —</option>
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
                    <option value="" disabled selected>— Select duration —</option>
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
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:6px">
                            <label>Amount Tendered (₱)</label>
                            <input type="number" id="startTendered" name="start_tendered" min="0" step="1" placeholder="e.g. 200"
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
                    <i class="fas fa-info-circle"></i> No refund — consumed cost covers or exceeds amount paid.
                </div>
            </div>

            <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:10px 14px;font-size:12px;color:#aaa;line-height:1.6;margin-bottom:12px;">
                <i class="fas fa-info-circle" style="color:#f1a83c;margin-right:5px;"></i>
                The customer only pays for <strong style="color:#f1e1aa;">time actually used</strong>. The refund is pre-calculated above.
            </div>

            <!-- Refund & End action -->
            <button type="button" id="endEarlyRefundBtn"
                    style="width:100%;padding:12px;border-radius:10px;
                           background:linear-gradient(135deg,rgba(241,168,60,.25),rgba(251,86,107,.15));
                           border:1px solid rgba(241,168,60,.5);color:#f1e1aa;
                           font-size:14px;font-weight:700;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;gap:8px;
                           transition:.2s;"
                    onmouseover="this.style.background='linear-gradient(135deg,rgba(241,168,60,.35),rgba(251,86,107,.25))'"
                    onmouseout="this.style.background='linear-gradient(135deg,rgba(241,168,60,.25),rgba(251,86,107,.15))'">
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
                </div>
            </div>
            <div id="endCostNote" style="margin-top:10px;font-size:12px;color:#aaa;"></div>
        </div>

        <form method="POST" id="endSessionForm" onsubmit="return syncTenderedAndSubmit(event)">
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

<!-- ════ COLLECT OUTSTANDING BALANCE MODAL (mid-session, no end) ══════════ -->
<div class="modal" id="paySessionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-peso-sign" style="color:#20c8a1;margin-right:8px"></i>Collect Balance
            </h3>
            <button class="modal-close" onclick="closePayModal()">&times;</button>
        </div>

        <!-- Session info -->
        <div style="background:rgba(32,200,161,.06);border:1px solid rgba(32,200,161,.15);border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:14px">
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
                <label style="font-size:12px;color:#aaa;text-transform:uppercase;letter-spacing:.5px;">Amount Tendered (₱)</label>
                <input type="number" id="payTendered" name="tendered_amount" min="0" step="1" placeholder="Enter customer's cash"
                       style="width:100%;margin-top:6px;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:18px;font-weight:700;box-sizing:border-box;"
                       oninput="calcChange('payTendered','payChangeDisplay','payAmount'); syncPayBtn()">
                <!-- Hidden field — stores the balance due for calcChange -->
                <input type="hidden" name="amount" id="payAmount" value="0">
            </div>
            <div id="payChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:15px;font-weight:700;margin-bottom:12px;"></div>
            <div id="payShortNotice" style="display:none;margin-top:10px;background:rgba(241,168,60,.12);border:1px solid rgba(241,168,60,.35);border-radius:8px;padding:10px 14px;font-size:13px;color:#f1a83c;margin-bottom:10px;">
                <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>
                <strong>Short payment</strong> — the remaining shortfall will be recorded.
            </div>
            <button type="submit" id="payConfirmBtn" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fas fa-check-circle"></i> <span id="payConfirmLabel">Record Payment</span>
            </button>
        </form>
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

        <div style="background:rgba(241,168,60,.08);border:1px solid rgba(241,168,60,.25);border-radius:10px;padding:14px;margin-bottom:16px;font-size:14px">
            <strong id="refundSessionSummary">—</strong>
            <div style="margin-top:8px;font-size:13px;color:#888;">
                Total paid so far: <strong id="refundPaidSoFar" style="color:#20c8a1;">₱0.00</strong>
            </div>
        </div>

        <form method="POST" id="refundSessionForm">
            <!-- action is overridden to 'early_end_session' when coming from early-end flow -->
            <input type="hidden" name="action" id="refundActionField" value="issue_refund">
            <input type="hidden" name="session_id" id="refundSessionId">
            <!-- Flag set by JS when this is an early-end (refund + end) submission -->
            <input type="hidden" name="early_end" id="refundEarlyEndFlag" value="0">
            <div class="form-group">
                <label>Refund Amount (₱) *</label>
                <input type="number" name="refund_amount" id="refundAmount" min="0" step="1"
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:20px;font-weight:700;box-sizing:border-box;"
                       placeholder="Enter amount to refund">
                <div id="refundMaxNote" style="font-size:11px;color:#888;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label>Reason (optional)</label>
                <input type="text" name="refund_reason" id="refundReason" maxlength="200"
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;font-size:14px;box-sizing:border-box;"
                       placeholder="e.g. Technical issue, customer complaint…">
            </div>
            <div style="background:rgba(251,86,107,.07);border:1px solid rgba(251,86,107,.2);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#fb566b;">
                <i class="fas fa-exclamation-triangle"></i> Refunds are recorded as negative transactions and cannot be undone.
            </div>
            <!-- Early-end note — shown only when triggered from early-end flow -->
            <div id="refundEarlyEndNote" style="display:none;background:rgba(241,168,60,.1);border:1px solid rgba(241,168,60,.3);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#f1a83c;">
                <i class="fas fa-stop-circle" style="margin-right:5px;"></i>
                <strong>Early End:</strong> Confirming will issue the refund above <strong>and immediately end the session</strong>.
            </div>
            <button type="button" id="refundConfirmBtn" class="btn btn-danger" style="width:100%;justify-content:center;"
                    onclick="submitRefundForm()">
                <i class="fas fa-undo-alt"></i> <span id="refundConfirmLabel">Confirm Refund</span>
            </button>
        </form>

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

        <div style="background:rgba(95,133,218,.08);border:1px solid rgba(95,133,218,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:14px">
            <strong id="extendSessionSummary">—</strong>
            <div style="margin-top:8px;font-size:13px;color:#888;">
                Current booked duration: <strong id="extendCurrentDuration" style="color:#8aa4e8;">—</strong>
            </div>
        </div>

        <form method="POST" id="extendSessionForm">
            <input type="hidden" name="action" value="extend_session">
            <input type="hidden" name="session_id" id="extendSessionId">
            <div class="form-group">
                <label>Add Time *</label>
                <select name="extra_minutes" id="extendMinutes" required>
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
            <div style="background:rgba(95,133,218,.07);border:1px solid rgba(95,133,218,.2);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#8aa4e8;">
                <i class="fas fa-info-circle"></i> This extends the <strong>booked duration</strong> only. Use the <strong>Pay</strong> button to collect the additional fee from the customer.
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;background:rgba(95,133,218,.25);border:1px solid #5f85da;color:#8aa4e8;">
                <i class="fas fa-clock"></i> Extend Session
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
                    <option value="" disabled selected>— Select customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Console Type *</label>
                    <select name="console_type" required>
                        <option value="" disabled selected>— Select —</option>
                        <option value="PS4">PS4</option>
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
                <select name="planned_minutes" id="adminResPlannedMins" onchange="adminResCalcDownpayment()">
                    <option value="" disabled selected>— Select —</option>
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
            <div class="form-group" id="adminDpGroup" style="display:none;">
                <label style="display:flex;justify-content:space-between;align-items:center;">
                    Downpayment Amount (₱)
                    <span id="adminDpHint" style="font-size:11px;color:#20c8a1;font-weight:600;"></span>
                </label>
                <input type="number" name="downpayment_amount" id="adminDpAmount" min="0" step="1"
                       readonly
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(32,200,161,.35);
                              background:rgba(32,200,161,.06);color:#20c8a1;font-size:15px;font-weight:700;
                              cursor:not-allowed;">
                <p style="font-size:11px;color:#888;margin:6px 0 0;"><i class="fas fa-lock" style="margin-right:4px;"></i>Fixed at 50% of session cost — collected to secure the booking.</p>
            </div>
            <div class="form-group" id="adminDpMethodGroup" style="display:none;">
                <label>Payment Method *</label>
                <select name="downpayment_method" id="adminDpMethodSelect">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
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
                    <option value="" disabled selected>— Select available console —</option>
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
    const isDurGroup   = (mode === 'hourly');
    document.getElementById('adminResDurGroup').style.display = isDurGroup ? 'block' : 'none';

    // Reset downpayment whenever mode changes
    document.getElementById('adminDpAmount').value = '';
    document.getElementById('adminDpGroup').style.display      = 'none';
    document.getElementById('adminDpMethodGroup').style.display = 'none';
    document.getElementById('adminResPlannedMins').value       = '';
    document.getElementById('adminDpHint').textContent         = '';
}

/* Calculates 50% of the selected duration cost and fills the downpayment field */
function adminResCalcDownpayment() {
    const mins = parseInt(document.getElementById('adminResPlannedMins').value) || 0;
    const dpGroup    = document.getElementById('adminDpGroup');
    const dpMethod   = document.getElementById('adminDpMethodGroup');
    const dpInput    = document.getElementById('adminDpAmount');
    const dpHint     = document.getElementById('adminDpHint');

    if (!mins) {
        dpGroup.style.display  = 'none';
        dpMethod.style.display = 'none';
        dpInput.value          = '';
        dpHint.textContent     = '';
        return;
    }

    // Mirror PHP pricing: 30 min = ₱50, otherwise ₱80/hr
    const fullCost = mins <= 30 ? 50 : (mins / 60 * 80);
    const dp       = Math.ceil(fullCost * 0.5); // 50%, rounded up to nearest peso

    dpInput.value          = dp;
    dpHint.textContent     = `50% of ₱${fullCost.toFixed(0)}`;
    dpGroup.style.display  = 'block';
    dpMethod.style.display = 'block';
}

/* Legacy — kept for safety but no longer needed */
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
