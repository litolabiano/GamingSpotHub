<?php
$f = 'admin.php';
$c = file_get_contents($f);

// 1. Update JS to parse event_type
$old_js_html = <<<EOD
        row.innerHTML =
            '<div style="display:flex;align-items:center;gap:10px;">' +
            '<div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;background:rgba(32,200,161,.12);' +
            'border:1px solid rgba(32,200,161,.25);display:flex;align-items:center;justify-content:center;color:#20c8a1;font-size:13px;">' +
            '<i class="fas fa-calendar-check"></i></div>' +
            '<div style="min-width:0;flex:1;">' +
            '<div style="font-weight:600;font-size:13px;color:#f0f0f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
            (r.customer_name || 'A customer') + '</div>' +
            '<div style="font-size:11px;color:#888;margin-top:1px;">' +
            (r.console_type || '') + ' · ' + mode + (dateStr ? ' · ' + dateStr : '') + (timeStr ? ' ' + timeStr : '') +
            '</div></div>' +
            '<span style="background:rgba(241,168,60,.15);color:#f1a83c;border:1px solid rgba(241,168,60,.3);' +
            'border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;flex-shrink:0;">Pending</span>' +
            '</div>';
EOD;

$new_js_html = <<<EOD
        var evTitle = ''; var evIcon = ''; var evColor = ''; var evBg = ''; var evBadge = '';
        if (r.event_type === 'new_reservation') {
            evTitle = 'New Reservation'; evIcon = 'fa-calendar-check'; evColor = '#20c8a1'; evBg = 'rgba(32,200,161,.12)'; evBadge = '<span style="background:rgba(32,200,161,.15);color:#20c8a1;border:1px solid rgba(32,200,161,.3);border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;flex-shrink:0;">New</span>';
        } else if (r.event_type === 'reschedule_request') {
            evTitle = 'Reschedule Req.'; evIcon = 'fa-clock'; evColor = '#f1a83c'; evBg = 'rgba(241,168,60,.12)'; evBadge = '<span style="background:rgba(241,168,60,.15);color:#f1a83c;border:1px solid rgba(241,168,60,.3);border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;flex-shrink:0;">Pending</span>';
        } else if (r.event_type === 'cancellation') {
            evTitle = 'Cancelled'; evIcon = 'fa-ban'; evColor = '#fb566b'; evBg = 'rgba(251,86,107,.12)'; evBadge = '<span style="background:rgba(251,86,107,.15);color:#fb566b;border:1px solid rgba(251,86,107,.3);border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;flex-shrink:0;">Cancelled</span>';
        } else {
            evTitle = 'Notification'; evIcon = 'fa-bell'; evColor = '#5f85da'; evBg = 'rgba(95,133,218,.12)'; evBadge = '';
        }

        row.innerHTML =
            '<div style="display:flex;align-items:center;gap:10px;">' +
            '<div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;background:' + evBg + ';' +
            'border:1px solid ' + evBg.replace('.12', '.25') + ';display:flex;align-items:center;justify-content:center;color:' + evColor + ';font-size:13px;">' +
            '<i class="fas ' + evIcon + '"></i></div>' +
            '<div style="min-width:0;flex:1;">' +
            '<div style="font-weight:600;font-size:13px;color:#f0f0f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
            (r.customer_name || 'A customer') + '</div>' +
            '<div style="font-size:11px;color:#888;margin-top:1px;">' +
            evTitle + ' · ' + (r.console_type || '') + (dateStr ? ' · ' + dateStr : '') + (timeStr ? ' ' + timeStr : '') +
            '</div></div>' + evBadge +
            '</div>';
EOD;

$c = str_replace($old_js_html, $new_js_html, $c);

// 2. Change poller lastId to lastTime
$c = str_replace("let lastId = <?= \$initMaxResId ?>;", "let lastTime = <?= time() ?>;", $c);
$c = str_replace("const stored = parseInt(localStorage.getItem('gspot_last_res_id') || '0');", "", $c);
$c = str_replace("localStorage.setItem('gspot_last_res_id', lastId);", "", $c);
$c = str_replace("fetch('ajax/poll_notifications.php?last_id=' + lastId, { credentials: 'same-origin' })", "fetch('ajax/poll_notifications.php?last_time=' + lastTime, { credentials: 'same-origin' })", $c);
$c = str_replace("if (data.max_id > lastId) {", "if (data.max_time > lastTime) {", $c);
$c = str_replace("lastId = data.max_id;", "lastTime = data.max_time;", $c);

file_put_contents($f, $c);
echo "done\n";
