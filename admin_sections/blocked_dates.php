<!-- ════ BLOCKED DATES ══════════════════════════════════════════════════════ -->
<div class="page" id="blocked_dates">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-calendar-times" style="color:#fb566b;margin-right:10px;"></i>Blocked Dates Management</h2>
            <p class="page-subtitle">Prevent users from making reservations on specific dates</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(350px, 1fr));gap:24px;">
        
        <!-- Block a New Date -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Block a New Date</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="admin.php#blocked_dates">
                    <input type="hidden" name="action" value="block_date">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label>Date to Block</label>
                        <input type="date" name="blocked_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Reason / Note (Optional)</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Shop Holiday, Maintenance">
                    </div>
                    <button type="submit" class="btn-dang btn-full" style="margin-top:10px;">
                        <i class="fas fa-lock"></i> Block This Date
                    </button>

                </form>
            </div>
        </div>

        <!-- Blocked Dates List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Currently Blocked Dates</h3>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Added</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $blockedDates = getBlockedDates();
                        if (empty($blockedDates)): 
                        ?>
                            <tr>
                                <td colspan="4" style="text-align:center;padding:30px;color:#888;">No dates are currently blocked.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blockedDates as $bd): ?>
                                <tr>
                                    <td style="font-weight:700;color:#fb566b;">
                                        <?= date('M d, Y', strtotime($bd['blocked_date'])) ?>
                                    </td>
                                    <td style="color:#f0f0f0;">
                                        <?= htmlspecialchars($bd['reason'] ?: 'No reason provided') ?>
                                    </td>
                                    <td style="color:#888;font-size:12px;">
                                        <?= date('M d, Y', strtotime($bd['created_at'])) ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="admin.php#blocked_dates" onsubmit="return confirm('Unblock this date?')">
                                            <input type="hidden" name="action" value="unblock_date">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="blocked_date" value="<?= $bd['blocked_date'] ?>">
                                            <button type="submit" class="btn-sec btn-sm">
                                                <i class="fas fa-unlock"></i> Unblock
                                            </button>

                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="card" style="margin-top:24px;background:rgba(251,86,107,.05);border:1px dashed rgba(251,86,107,.3);">
        <div class="card-body" style="display:flex;gap:15px;align-items:flex-start;">
            <i class="fas fa-info-circle" style="color:#fb566b;font-size:20px;margin-top:3px;"></i>
            <div style="font-size:13px;line-height:1.6;color:rgba(255,255,255,.7);">
                <strong>About Date Blocking:</strong>
                <p style="margin:5px 0 0;">Blocked dates will appear unavailable on the user reservation calendar. Users will not be able to select these dates for new bookings. Existing reservations on these dates will <strong>not</strong> be automatically cancelled; you should manually reschedule or cancel them if the shop will be closed.</p>
            </div>
        </div>
    </div>
</div>
