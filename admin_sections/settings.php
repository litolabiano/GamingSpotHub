<!-- ════ SETTINGS ═══════════════════════════════════════════════════════════ -->
<div class="page" id="settings">
    <form method="POST">
        <input type="hidden" name="action" value="save_settings">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

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
