<?php
$content = file_get_contents('admin_sections/modals.php');

$content = str_replace('<form id="extendSessionForm">', '<form id="extendSessionForm" onsubmit="event.preventDefault(); submitExtendSession();">', $content);

$pattern1 = '/<!-- Payment fields \(shown for hourly extensions\) -->.*?<div style="background:rgba\(95,133,218,\.07\)[^>]*>.*?<\/div>/s';
$info_block = '<!-- Extension cost information -->
            <div style="background:rgba(95,133,218,.07);border:1px solid rgba(95,133,218,.2);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#8aa4e8;">
                <i class="fas fa-info-circle"></i> Extension costs for hourly sessions are automatically added to the customer\'s Outstanding Balance and collected at the end of the session.
            </div>';
$content = preg_replace($pattern1, $info_block, $content);

$pattern2 = '/<script>\s*\(\s*function\(\)\s*\{\s*document\.getElementById\(\'extendSessionForm\'\)\.addEventListener\(\'submit\'.*?\}\)\(\);\s*<\/script>/s';
$content = preg_replace($pattern2, '', $content);

$pattern3 = '/function submitExtendSession\(\)\s*\{.*?\}(?=\s*function approveExt)/s';
$submit_func = <<<'EOD'
function submitExtendSession() {
    const sessionId = document.getElementById('extendSessionId').value;
    const mins      = document.getElementById('extendMinutes').value;
    const mode      = document.getElementById('extendSessionMode').value;
    const method    = 'cash';
    const tendered  = 0;

    if (!mins) { showInlineToast('Please select how much time to add.', 'error'); return; }

    const btn = document.getElementById('extendConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    const fd = new FormData();
    fd.append('session_id',     sessionId);
    fd.append('extra_minutes',  mins);
    fd.append('payment_method', method);
    fd.append('tendered', tendered);

    fetch('ajax/extend_session.php', { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json())
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-clock"></i> Extend Session';
            if (data.success) {
                closeModal('extendSession');
                const toastMsg = data.message ? data.message : 'Session extended! ₱' + (data.extra_cost || 0) + ' added to balance.';
                showInlineToast(toastMsg, 'success');
                
                if (typeof updateTimers === 'function') {
                    const row = document.querySelector(`tr[data-id="${sessionId}"]`);
                    if (row && mode === 'hourly') {
                        let curBooked = parseInt(row.dataset.booked) || 0;
                        curBooked += parseInt(data.total_added || mins);
                        row.dataset.booked = curBooked;
                        const h = Math.floor(curBooked / 60);
                        const m = curBooked % 60;
                        if (row.cells[4]) {
                            row.cells[4].textContent = h ? (m ? h+'h '+m+'m' : h+'h') : m+'m';
                        }
                        const extendBtn = row.querySelector('button[title="Extend Session"]');
                        if (extendBtn) {
                            const newOnclick = extendBtn.getAttribute('onclick').replace(/(openExtendModal\([^,]+,[^,]+,[^,]+,)\s*\d+/, `$1 ${curBooked}`);
                            extendBtn.setAttribute('onclick', newOnclick);
                        }
                    }
                }
                
                if (typeof updateLiveSection === 'function') {
                    updateLiveSection();
                } else {
                    fetch(location.href).then(res => res.text()).then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newPending = doc.getElementById('pendingPaymentsTable');
                        const oldPending = document.getElementById('pendingPaymentsTable');
                        if (newPending && oldPending) {
                            oldPending.innerHTML = newPending.innerHTML;
                        }
                    });
                }
            } else {
                showInlineToast(data.message || 'Extension failed.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-clock"></i> Extend Session';
            showInlineToast('Network error — please try again.', 'error');
        });
}
EOD;
$content = preg_replace($pattern3, $submit_func . "\n", $content);

$pattern4 = '/function approveExt\(extId\)\s*\{.*?\}(?=\s*function _approveExtDummy)/s';
$approve_func = <<<'EOD'
function approveExt(extId) {
    const method = 'cash';
    gspotConfirm('Approve extension?', function() {
        const fd = new FormData();
        fd.append('action', 'approve');
        fd.append('extension_id', extId);
        fd.append('payment_method', method);
        fd.append('tendered', 0);
        fetch('ajax/approve_extension.php', { method: 'POST', credentials: 'same-origin', body: fd })
            .then(r => r.json())
            .then(function(d) {
                if (d.success) {
                    showInlineToast('Extension approved! Added to balance.', 'success');
                    loadPendingExtensions(document.getElementById('extendSessionId').value);
                    if (typeof updateLiveSection === 'function') updateLiveSection();
                    else {
                        fetch(location.href).then(res => res.text()).then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newPending = doc.getElementById('pendingPaymentsTable');
                            const oldPending = document.getElementById('pendingPaymentsTable');
                            if (newPending && oldPending) oldPending.innerHTML = newPending.innerHTML;
                        });
                    }
                } else {
                    showInlineToast(d.message || 'Failed to approve.', 'error');
                }
            });
    }, { yesLabel: 'Approve', danger: false });
}
EOD;
$content = preg_replace($pattern4, $approve_func . "\n", $content);

$pattern5 = '/function _approveExtDummy\(\)\s*\{.*?\}(?=\s*function denyExt)/s';
$dummy_func = <<<'EOD'
function _approveExtDummy() {
    fetch('ajax/approve_extension.php', { method: 'POST', credentials: 'same-origin', body: new FormData() })
        .then(r => r.json())
        .then(function(d) {
            if (d.success) {
                showInlineToast('Extension approved! Added to balance.', 'success');
                loadPendingExtensions(document.getElementById('extendSessionId').value);
                if (typeof updateLiveSection === 'function') updateLiveSection();
                else {
                    fetch(location.href).then(res => res.text()).then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newPending = doc.getElementById('pendingPaymentsTable');
                        const oldPending = document.getElementById('pendingPaymentsTable');
                        if (newPending && oldPending) oldPending.innerHTML = newPending.innerHTML;
                    });
                }
            } else {
                showInlineToast(d.message || 'Failed to approve.', 'error');
            }
        });
}
EOD;
$content = preg_replace($pattern5, $dummy_func . "\n", $content);

file_put_contents('admin_sections/modals.php', $content);
echo "Fixed!\n";
