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

        <form method="POST">
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="session_id" id="endSessionId">

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
                <div id="endChangeDisplay" style="display:none;border-radius:8px;padding:10px 14px;font-size:16px;font-weight:800;margin-top:8px;"></div>
            </div>

            <!-- Shown for unlimited / fully-prepaid hourly -->
            <div id="endPrepaidNote" style="display:none;background:rgba(32,200,161,.07);border:1px solid rgba(32,200,161,.2);border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:#20c8a1;">
                <i class="fas fa-check-circle"></i> <strong>Payment already collected at session start.</strong> No additional charge at end.
            </div>

            <button type="submit" class="btn btn-danger" id="endSessionConfirmBtn" style="width:100%;justify-content:center">
                <i class="fas fa-check-circle"></i> <span id="endSessionConfirmLabel">Confirm End &amp; Record Payment</span>
            </button>
        </form>
    </div>
</div>
