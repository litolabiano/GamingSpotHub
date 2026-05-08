<!-- ════ ACTIVITY LOGS ════════════════════════════════════════════════════ -->
<div class="page" id="activity_logs">
    <div class="page-header">
        <div class="page-title-group">
            <h2 class="page-title"><i class="fas fa-history" style="color:#f1a83c;margin-right:10px;"></i>Activity Logs</h2>
            <p class="page-subtitle">Track critical system actions and administrative changes</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:15px;padding:20px;">
            <h3 class="card-title"><i class="fas fa-list" style="margin-right:8px;font-size:14px;opacity:.7;"></i>Recent Activity</h3>
            
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;flex:1;justify-content:flex-end;">
                <!-- Search Bar -->
                <div class="asb-search" style="max-width:220px;flex:1;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="asb-input" id="logSearch" placeholder="Search details…" autocomplete="off">
                    <button class="asb-clear" title="Clear"><i class="fas fa-times"></i></button>
                </div>

                <!-- Action Filter -->
                <select class="asb-select" id="logActionFilter" style="width:140px;">
                    <option value="">All Actions</option>
                    <?php
                    $actions = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
                    while($a = $actions->fetch_assoc()) {
                        echo '<option value="'.htmlspecialchars($a['action']).'">'.htmlspecialchars($a['action']).'</option>';
                    }
                    ?>
                </select>

                <!-- Admin Filter -->
                <select class="asb-select" id="logAdminFilter" style="width:140px;">
                    <option value="">All Admins</option>
                    <?php
                    $admins = $conn->query("SELECT DISTINCT u.full_name FROM activity_logs l JOIN users u ON l.user_id = u.user_id ORDER BY u.full_name ASC");
                    while($adm = $admins->fetch_assoc()) {
                        echo '<option value="'.htmlspecialchars($adm['full_name']).'">'.htmlspecialchars($adm['full_name']).'</option>';
                    }
                    ?>
                </select>

                <!-- Date Range -->
                <div style="display:flex;align-items:center;gap:6px;background:rgba(10,33,81,.45);border:1px solid rgba(95,133,218,.22);border-radius:9px;padding:2px 8px;">
                    <i class="fas fa-calendar-alt" style="color:rgba(255,255,255,.3);font-size:12px;"></i>
                    <input type="date" class="asb-input" id="logDateFrom" title="From Date" style="border:none;background:none;padding:5px 2px;width:115px;">
                    <span style="color:rgba(255,255,255,.2);font-size:10px;">to</span>
                    <input type="date" class="asb-input" id="logDateTo" title="To Date" style="border:none;background:none;padding:5px 2px;width:115px;">
                </div>

                <span class="asb-count" id="logCount" style="min-width:70px;text-align:right;"></span>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table" id="logTable">
                <thead>
                    <tr>
                        <th style="width:160px;">Timestamp</th>
                        <th style="width:180px;">Admin</th>
                        <th style="width:160px;">Action</th>
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
                         LIMIT 500"
                    );
                    
                    if ($logQuery && $logQuery->num_rows > 0):
                        while ($l = $logQuery->fetch_assoc()):
                            $rawDate = date('Y-m-d', strtotime($l['created_at']));
                    ?>
                        <tr data-date="<?= $rawDate ?>" data-action="<?= htmlspecialchars($l['action']) ?>" data-admin="<?= htmlspecialchars($l['admin_name']) ?>">
                            <td style="color:#888;font-size:12px;">
                                <span style="display:none;"><?= $l['created_at'] ?></span><!-- for sorting if needed -->
                                <?= date('M d, Y', strtotime($l['created_at'])) ?><br>
                                <strong style="color:#aaa;"><?= date('h:i:s A', strtotime($l['created_at'])) ?></strong>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:26px;height:26px;border-radius:50%;background:rgba(241,168,60,.12);color:#f1a83c;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;border:1px solid rgba(241,168,60,.25);">
                                        <?= strtoupper(substr($l['admin_name'], 0, 1)) ?>
                                    </div>
                                    <span style="font-weight:600;color:#f0f0f0;font-size:13px;"><?= htmlspecialchars($l['admin_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span style="background:rgba(241,168,60,.08);color:#f1a83c;border:1px solid rgba(241,168,60,.2);border-radius:6px;padding:3px 9px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;display:inline-block;">
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
                        <tr class="empty-row">
                            <td colspan="4" style="text-align:center;padding:40px;color:#555;">
                                <i class="fas fa-history" style="font-size:2rem;display:block;margin-bottom:12px;opacity:.3;"></i>
                                No activity logs found yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="logPagination"></div>
        <div class="asb-no-results" id="logNoResults" style="padding:40px;"><i class="fas fa-filter" style="display:block;font-size:24px;margin-bottom:12px;opacity:.3;"></i>No logs match your filters.</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('logSearch');
    const actionFilter = document.getElementById('logActionFilter');
    const adminFilter = document.getElementById('logAdminFilter');
    const dateFrom = document.getElementById('logDateFrom');
    const dateTo = document.getElementById('logDateTo');
    const logTable = document.getElementById('logTable');
    
    if (!logTable) return;

    const paginator = new AdminPaginator('logTable', {
        pageSize: 15,
        pageSizes: [15, 30, 50, 100],
        paginationSel: '#logPagination',
        noResultsSel: '#logNoResults',
        countSel: '#logCount'
    });

    function applyFilters() {
        const q = searchInput.value.toLowerCase().trim();
        const act = actionFilter.value;
        const adm = adminFilter.value;
        const from = dateFrom.value;
        const to = dateTo.value;

        logTable.querySelectorAll('tbody tr:not(.empty-row)').forEach(row => {
            const rowDate = row.dataset.date;
            const rowAction = row.dataset.action;
            const rowAdmin = row.dataset.admin;
            const rowText = row.innerText.toLowerCase();

            const matchQ = !q || rowText.includes(q);
            const matchAct = !act || rowAction === act;
            const matchAdm = !adm || rowAdmin === adm;
            const matchFrom = !from || rowDate >= from;
            const matchTo = !to || rowDate <= to;

            row.classList.toggle('asb-hidden', !(matchQ && matchAct && matchAdm && matchFrom && matchTo));
        });

        const clearBtn = searchInput.parentElement.querySelector('.asb-clear');
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

        paginator.reset();
    }

    // Event listeners
    [searchInput, actionFilter, adminFilter, dateFrom, dateTo].forEach(el => {
        if (el) el.addEventListener('input', applyFilters);
        if (el && el.tagName === 'SELECT') el.addEventListener('change', applyFilters);
    });

    const clearBtn = searchInput.parentElement.querySelector('.asb-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        });
    }

    paginator.apply();
});
</script>
