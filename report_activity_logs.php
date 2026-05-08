<?php
/**
 * report_receipt.php
 * Generates a receipt-style printable report for Daily/Monthly/Yearly analytics.
 */
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';

requireRole(['owner', 'shopkeeper']);

$type = $_GET['type'] ?? 'daily';
$dateVal = $_GET['date'] ?? date('Y-m-d');
$txWhere = ""; $resWhere = "";
$dateLabel = "";

if ($type === 'daily') {
    [$start, $end] = getOperatingDayBounds($dateVal);
    $txWhere = "transaction_date BETWEEN '$start' AND '$end'";
    $resWhere = "created_at BETWEEN '$start' AND '$end'";
    $dateLabel = date('F j, Y', strtotime($dateVal));
} elseif ($type === 'monthly') {
    $safeDate = $conn->real_escape_string($dateVal);
    $txWhere = "DATE_FORMAT(transaction_date, '%Y-%m') = '$safeDate'";
    $resWhere = "DATE_FORMAT(created_at, '%Y-%m') = '$safeDate'";
    $dateLabel = date('F Y', strtotime($safeDate . '-01'));
} else {
    $safeDate = $conn->real_escape_string($dateVal);
    $txWhere = "YEAR(transaction_date) = '$safeDate'";
    $resWhere = "YEAR(created_at) = '$safeDate'";
    $dateLabel = $safeDate;
}

// 1. Transactions Revenue
$revTx = $conn->query("SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM transactions WHERE payment_status='completed' AND $txWhere")->fetch_assoc();
// 2. Reservations Downpayment
$revRes = $conn->query("SELECT COALESCE(SUM(downpayment_amount),0) AS total FROM reservations WHERE downpayment_paid=1 AND $resWhere")->fetch_assoc();

$totalRevenue = $revTx['total'] + $revRes['total'];
$totalTransactions = $revTx['cnt'];

// 3. Reservations Stats
$resStats = $conn->query("
    SELECT 
        COUNT(*) AS total_reservations,
        SUM(status = 'converted') AS completed_res,
        SUM(status = 'cancelled' OR status = 'no_show') AS cancelled_res,
        SUM(status = 'reserved' OR status = 'pending') AS pending_res
    FROM reservations WHERE $resWhere
")->fetch_assoc();

// Fetch recent transactions list
$txList = $conn->query("
    SELECT t.*, u.full_name 
    FROM transactions t 
    JOIN users u ON t.user_id=u.user_id 
    WHERE t.payment_status='completed' AND $txWhere 
    ORDER BY t.transaction_date ASC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report - <?= htmlspecialchars($dateLabel) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400&family=Outfit:wght@400;600;800&display=swap');
        
        body {
            font-family: 'Courier Prime', monospace;
            background: #e2e8f0;
            color: #1e293b;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        .receipt {
            background: #fff;
            width: 420px;
            padding: 30px 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }
        /* Jagged bottom edge effect */
        .receipt::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background-size: 20px 20px;
            background-image: radial-gradient(circle at 10px 0, transparent 10px, #fff 11px);
        }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin: 0 0 4px 0; letter-spacing: 1px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 13px; color: #64748b; }
        
        .divider { border-top: 1px dashed #cbd5e1; margin: 16px 0; }
        .flex { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px; }
        .flex.bold { font-weight: bold; }
        
        .section-title { font-weight: bold; text-align: center; margin: 20px 0 10px; text-transform: uppercase; font-size: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        
        .total-row { display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 2px dashed #1e293b; }
        
        .tx-list { font-size: 12px; margin-bottom: 20px; }
        .tx-list-wrapper { max-height: 350px; overflow-y: auto; padding-right: 5px; margin-bottom: 10px; }
        .tx-list-wrapper::-webkit-scrollbar { width: 6px; }
        .tx-list-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .tx-item { display: flex; justify-content: space-between; margin-bottom: 6px; page-break-inside: avoid; break-inside: avoid; }
        .tx-item.hidden-page { display: none; }
        
        .pagination { display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }
        .page-btn { background: #e2e8f0; border: none; padding: 4px 10px; cursor: pointer; border-radius: 4px; font-family: inherit; font-weight: bold; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .print-btn {
            position: fixed; top: 20px; right: 20px;
            background: #20c8a1; color: #fff; border: none; padding: 12px 24px; border-radius: 8px;
            font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer;
            box-shadow: 0 4px 10px rgba(32,200,161,0.3);
            transition: background 0.2s;
        }
        .print-btn:hover { background: #1ba887; }
        
        @page { margin: 1.5cm; }
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .receipt { box-shadow: none; margin: 0; width: 100%; max-width: 100%; padding: 0; }
            .receipt::after { display: none; }
            .print-btn { display: none; }
            .tx-list-wrapper { max-height: none !important; overflow: visible !important; }
            .tx-item.hidden-page { display: flex !important; } /* Force show all on print */
            .pagination { display: none !important; }
            .section-title { break-after: avoid; page-break-after: avoid; }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>

    <div class="receipt">
        <div class="header">
            <h1>GSpot Gaming Hub</h1>
            <p>Don Placido Avenue, Dasmariñas</p>
            <p>OFFICIAL OPERATIONS REPORT</p>
        </div>
        
        <div class="divider"></div>
        <div class="flex"><span>Report Type:</span> <span><?= ucfirst($type) ?></span></div>
        <div class="flex"><span>Date:</span> <span><?= htmlspecialchars($dateLabel) ?></span></div>
        <div class="flex"><span>Generated:</span> <span><?= date('m/d/Y h:i A') ?></span></div>
        <div class="divider"></div>

        <?php if ($totalTransactions == 0 && $resStats['total_reservations'] == 0 && $totalRevenue == 0): ?>
            <div style="text-align:center; padding: 40px 20px; color:#64748b;">
                <h3 style="margin:0 0 10px 0; font-family:'Outfit', sans-serif;">No Data Available</h3>
                <p style="margin:0; font-size:14px;">There are no transactions or reservations recorded for the selected timeframe.</p>
            </div>
        <?php else: ?>

            <div class="section-title">REVENUE & PROFIT</div>
            <div class="flex"><span>Session Transactions:</span> <span>₱<?= number_format($revTx['total'], 2) ?></span></div>
            <div class="flex"><span>Res. Downpayments:</span> <span>₱<?= number_format($revRes['total'], 2) ?></span></div>
            <div class="total-row"><span>Total Revenue:</span> <span>₱<?= number_format($totalRevenue, 2) ?></span></div>
            <div class="divider"></div>

        <div class="section-title">RESERVATIONS BREAKDOWN</div>
        <div class="flex"><span>Total Received:</span> <span><?= (int)$resStats['total_reservations'] ?></span></div>
        <div class="flex"><span>Completed/Showed:</span> <span><?= (int)$resStats['completed_res'] ?></span></div>
        <div class="flex"><span>Cancelled/No-Show:</span> <span><?= (int)$resStats['cancelled_res'] ?></span></div>
        <div class="flex"><span>Pending/Upcoming:</span> <span><?= (int)$resStats['pending_res'] ?></span></div>
        <div class="divider"></div>

        <div class="section-title">TRANSACTIONS (<?= count($txList) ?>)</div>
        <div class="tx-list">
            <?php if (empty($txList)): ?>
                <div style="text-align:center;color:#64748b;margin-top:10px;">No transactions recorded.</div>
            <?php else: ?>
                <div class="tx-list-wrapper" id="txWrapper">
                    <?php foreach ($txList as $idx => $tx): ?>
                        <div class="tx-item" data-index="<?= $idx ?>">
                            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:10px;">
                                #<?= $tx['transaction_id'] ?> - <?= htmlspecialchars($tx['full_name']) ?>
                            </span>
                            <span>₱<?= number_format($tx['amount'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="pagination" id="txPagination">
                    <button class="page-btn" id="btnPrev" onclick="changePage(-1)">Prev</button>
                    <span id="pageInfo">Page 1 of 1</span>
                    <button class="page-btn" id="btnNext" onclick="changePage(1)">Next</button>
                </div>
                
                <script>
                    const txItems = document.querySelectorAll('.tx-item');
                    const itemsPerPage = 20;
                    let currentPage = 1;
                    const totalPages = Math.ceil(txItems.length / itemsPerPage);
                    
                    function renderPage() {
                        if (txItems.length === 0) return;
                        
                        txItems.forEach((item, index) => {
                            const start = (currentPage - 1) * itemsPerPage;
                            const end = start + itemsPerPage;
                            if (index >= start && index < end) {
                                item.classList.remove('hidden-page');
                            } else {
                                item.classList.add('hidden-page');
                            }
                        });
                        
                        document.getElementById('pageInfo').innerText = `Page ${currentPage} of ${totalPages}`;
                        document.getElementById('btnPrev').disabled = (currentPage === 1);
                        document.getElementById('btnNext').disabled = (currentPage === totalPages);
                        
                        // Scroll to top of list container
                        document.getElementById('txWrapper').scrollTop = 0;
                    }
                    
                    function changePage(delta) {
                        currentPage += delta;
                        if (currentPage < 1) currentPage = 1;
                        if (currentPage > totalPages) currentPage = totalPages;
                        renderPage();
                    }
                    
                    if (totalPages > 1) {
                        renderPage();
                    } else if (totalPages === 1) {
                        document.getElementById('txPagination').style.display = 'none';
                    }
                </script>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <div class="divider"></div>
        <div style="text-align:center;font-size:12px;color:#64748b;margin-top:20px;">
            END OF REPORT<br>
            <i>Thank you for running GSpot Gaming Hub!</i>
        </div>
    </div>

</body>
</html>
