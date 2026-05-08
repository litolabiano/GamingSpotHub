<!-- ════ SETTINGS ═══════════════════════════════════════════════════════════ -->
<div class="page" id="settings">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-cog" style="color:#f1a83c;margin-right:10px;"></i>Settings</h2>
            <p class="page-subtitle">Pricing rules, bonus time, and shop configuration</p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save_settings">
        <?= csrfField() ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

            <!-- ── Pricing ── -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-peso-sign" style="color:#20c8a1;margin-right:6px;"></i>Pricing</h3></div>

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
                <div class="form-group">
                    <label>Contact Inquiry Email</label>
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? 'goodspotgaminghub@gmail.com') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> This is where all contact form submissions will be sent.
                    </div>
                </div>
                <div class="form-group">
                    <label>Public System URL (Base URL)</label>
                    <input type="url" name="base_url" placeholder="http://192.168.x.x/GamingSpotHub" value="<?= htmlspecialchars($settings['base_url'] ?? '') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> <strong>Crucial for Email Verification:</strong> Set this to your Public IP or Domain (e.g. <code>http://112.198.x.x/GamingSpotHub</code>) so verification links work on mobile devices outside your local Wi-Fi.
                    </div>
                </div>
            </div>

            <!-- ── Email Configuration (Brevo API) ── -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-paper-plane" style="color:#20c8a1;margin-right:6px;"></i>Hassle-Free Email (Brevo API)</h3></div>
                
                <div style="background:rgba(32,200,161,.08);border:1px solid rgba(32,200,161,.25);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;color:#20c8a1;">
                    <i class="fas fa-bolt" style="color:#f1a83c;margin-right:6px;"></i> 
                    <strong>No SMTP setup required!</strong> Just enter your Brevo API Key to enable instant email notifications for all form submissions.
                </div>

                <div class="form-group">
                    <label>Brevo API Key (v3) — <span style="color:#fb566b">Must start with 'xkeysib-'</span></label>
                    <input type="password" name="brevo_api_key" placeholder="xkeysib-..." value="<?= htmlspecialchars($settings['brevo_api_key'] ?? '') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> Go to <strong>SMTP & API</strong> > <strong>API Keys</strong> tab. <br>
                        <strong style="color:#f1a83c">Note:</strong> Do NOT use the 'SMTP Key' (xsmtpsib-), use the 'API Key'.
                    </div>
                </div>

                <div class="form-group">
                    <label>Sender Email (Verified in Brevo)</label>
                    <input type="email" name="sender_email" placeholder="notifications@yourdomain.com" value="<?= htmlspecialchars($settings['sender_email'] ?? 'goodspotgaminghub@gmail.com') ?>">
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> This must be a <strong>verified sender</strong> in your Brevo account.
                    </div>
                </div>
            </div>

        </div>

        <div style="margin-top:16px;display:flex;align-items:center;gap:12px;">
            <button type="submit" class="btn-prim">
                <i class="fas fa-save"></i> Save All Settings
            </button>

            <span style="font-size:12px;color:#888;">
                <i class="fas fa-info-circle"></i>
                Saving rates will immediately update all console dropdowns and duration options across the system.
            </span>
        </div>
    </form>
</div>

