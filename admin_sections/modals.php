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
            <h3 class="modal-title"><i class="fas fa-stop-circle" style="color:#fb566b;margin-right:8px"></i>End Session &amp; Collect Payment</h3>
            <button class="modal-close" onclick="closeModal('endSession')">&times;</button>
        </div>
        <div style="background:rgba(251,86,107,.08);border:1px solid rgba(251,86,107,.2);border-radius:10px;padding:14px;margin-bottom:20px;font-size:14px">
            <strong id="endSessionSummary">—</strong>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="session_id" id="endSessionId">
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="gcash">📱 GCash</option>
                    <option value="credit_card">💳 Credit Card</option>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">
                <i class="fas fa-check-circle"></i> Confirm End &amp; Record Payment
            </button>
        </form>
    </div>
</div>
