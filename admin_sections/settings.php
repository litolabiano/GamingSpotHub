<!-- ════ SETTINGS ═══════════════════════════════════════════════════════════ -->
<div class="page" id="settings">
    <form method="POST">
        <input type="hidden" name="action" value="save_settings">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Pricing</h3></div>
                <div class="form-group">
                    <label>PS5 Hourly Rate (₱)</label>
                    <input type="number" step="0.01" name="ps5_hourly_rate" value="<?= htmlspecialchars($settings['ps5_hourly_rate'] ?? '80') ?>">
                </div>
                <div class="form-group">
                    <label>Xbox Hourly Rate (₱)</label>
                    <input type="number" step="0.01" name="xbox_hourly_rate" value="<?= htmlspecialchars($settings['xbox_hourly_rate'] ?? '80') ?>">
                </div>
                <div class="form-group">
                    <label>Unlimited (whole day) Rate (₱)</label>
                    <input type="number" step="0.01" name="unlimited_rate" value="<?= htmlspecialchars($settings['unlimited_rate'] ?? '300') ?>">
                </div>
                <div class="form-group">
                    <label>Controller Rental Fee (₱)</label>
                    <input type="number" step="0.01" name="controller_rental_fee" value="<?= htmlspecialchars($settings['controller_rental_fee'] ?? '20') ?>">
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Shop Information</h3></div>
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
        <div style="margin-top:10px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        </div>
    </form>
</div>
