<!-- ════ ACTIVITY LOGS ════════════════════════════════════════════════════ -->
<div class="page" id="activity_logs">
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-history" style="color:#f1a83c;margin-right:10px;"></i>Activity Logs</h2>
            <p class="page-subtitle">Track critical system actions and administrative changes</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Activity</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:180px;">Timestamp</th>
                        <th style="width:150px;">Admin</th>
                        <th style="width:180px;">Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logQuery = $conn->query(
                        "SELECT l.*, u.full_name AS admin_name 
                         FROM activity_logs l 
                         JOIN users u ON l.user_id = u.user_id 
                         ORDER BY l.created_at DESC 
                         LIMIT 100"
                    );
                    
                    if ($logQuery && $logQuery->num_rows > 0):
                        while ($l = $logQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td style="color:#888;font-size:12px;">
                                <?= date('M d, Y', strtotime($l['created_at'])) ?><br>
                                <strong style="color:#aaa;"><?= date('h:i:s A', strtotime($l['created_at'])) ?></strong>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:28px;height:28px;border-radius:50%;background:rgba(95,133,218,.15);color:#5f85da;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:1px solid rgba(95,133,218,.25);">
                                        <?= strtoupper(substr($l['admin_name'], 0, 1)) ?>
                                    </div>
                                    <span style="font-weight:600;color:#f0f0f0;font-size:13px;"><?= htmlspecialchars($l['admin_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span style="background:rgba(241,168,60,.12);color:#f1a83c;border:1px solid rgba(241,168,60,.3);border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">
                                    <?= htmlspecialchars($l['action']) ?>
                                </span>
                            </td>
                            <td style="font-size:13px;color:#bbb;line-height:1.5;">
                                <?= htmlspecialchars($l['details']) ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:40px;color:#555;">
                                <i class="fas fa-history" style="font-size:2rem;display:block;margin-bottom:12px;opacity:.3;"></i>
                                No activity logs found yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
