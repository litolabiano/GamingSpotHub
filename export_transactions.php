<?php
/**
 * export_transactions.php
 * Exports a detailed CSV, XLS, DOC, TXT, or PDF of transactions for a specific Daily/Monthly/Yearly period.
 */
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';

requireRole(['owner', 'shopkeeper']);

$type = $_GET['type'] ?? 'daily';
$dateVal = $_GET['date'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv';
$txWhere = "";
$filenameSuffix = "";
$dateLabel = "";

if ($type === 'daily') {
    [$start, $end] = getOperatingDayBounds($dateVal);
    $txWhere = "t.transaction_date BETWEEN '$start' AND '$end'";
    $filenameSuffix = "Daily_" . $dateVal;
    $dateLabel = date('F j, Y', strtotime($dateVal));
} elseif ($type === 'monthly') {
    $safeDate = $conn->real_escape_string($dateVal);
    $txWhere = "DATE_FORMAT(t.transaction_date, '%Y-%m') = '$safeDate'";
    $filenameSuffix = "Monthly_" . $dateVal;
    $dateLabel = date('F Y', strtotime($dateVal . '-01'));
} elseif ($type === 'yearly') {
    $safeDate = (int)$dateVal;
    $txWhere = "YEAR(t.transaction_date) = $safeDate";
    $filenameSuffix = "Yearly_" . $safeDate;
    $dateLabel = "Year " . $safeDate;
}

$query = "SELECT t.transaction_id, t.transaction_date, u.full_name AS customer_name,
                 COALESCE(c.unit_number, '-') AS unit_number,
                 CASE
                     WHEN t.payment_note LIKE 'Downpayment%' THEN 'reservation'
                     WHEN t.amount < 0 THEN 'refund'
                     ELSE COALESCE(gs.rental_mode, 'other')
                 END AS rental_mode,
                 t.amount, t.payment_method, t.payment_status,
                 COALESCE(r.paymongo_payment_id, r.paymongo_source_id, '-') AS paymongo_id,
                 t.payment_note
          FROM transactions t
          JOIN users u ON t.user_id = u.user_id
          LEFT JOIN gaming_sessions gs ON t.session_id = gs.session_id
          LEFT JOIN consoles c ON gs.console_id = c.console_id
          LEFT JOIN reservations r
                ON t.payment_note LIKE '%reservation #%'
               AND r.reservation_id = CAST(
                     SUBSTRING_INDEX(SUBSTRING_INDEX(t.payment_note, '#', -1), ' ', 1)
                   AS UNSIGNED)
          WHERE $txWhere
          ORDER BY t.transaction_date DESC";

$result = $conn->query($query);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$filename = "GamingSpotHub_Transactions_" . $filenameSuffix;

if ($format === 'csv' || $format === 'txt') {
    $ext = ($format === 'csv') ? 'csv' : 'txt';
    $delimiter = ($format === 'csv') ? ',' : "\t";
    header('Content-Type: text/' . $ext . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Transaction ID', 'Date & Time', 'Customer', 'Console Unit', 'Transaction Type', 'Amount (PHP)', 'Payment Method', 'Status', 'PayMongo ID', 'Notes'], $delimiter);
    foreach ($data as $row) {
        fputcsv($output, [
            $row['transaction_id'],
            date('M d, Y h:i A', strtotime($row['transaction_date'])),
            $row['customer_name'],
            $row['unit_number'],
            ucfirst($row['rental_mode']),
            number_format((float)$row['amount'], 2),
            ucfirst($row['payment_method']),
            ucfirst($row['payment_status']),
            $row['paymongo_id'],
            $row['payment_note']
        ], $delimiter);
    }
    fclose($output);
    exit;

} elseif ($format === 'xls' || $format === 'doc') {
    $ext = ($format === 'xls') ? 'xls' : 'doc';
    $contentType = ($format === 'xls') ? 'application/vnd.ms-excel' : 'application/vnd.ms-word';
    
    header("Content-Type: $contentType");
    header("Content-Disposition: attachment; filename=\"$filename.$ext\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "<html><head><meta charset='UTF-8'></head><body>";
    echo "<h2>GamingSpotHub - Transactions Report</h2>";
    echo "<p><strong>Period:</strong> $dateLabel</p>";
    echo "<table border='1' style='border-collapse:collapse; text-align:left;'>";
    echo "<tr><th>Transaction ID</th><th>Date & Time</th><th>Customer</th><th>Console Unit</th><th>Transaction Type</th><th>Amount (PHP)</th><th>Payment Method</th><th>Status</th><th>PayMongo ID</th><th>Notes</th></tr>";
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $row['transaction_id'] . "</td>";
        echo "<td>" . date('M d, Y h:i A', strtotime($row['transaction_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['unit_number']) . "</td>";
        echo "<td>" . ucfirst($row['rental_mode']) . "</td>";
        echo "<td>" . number_format((float)$row['amount'], 2) . "</td>";
        echo "<td>" . ucfirst($row['payment_method']) . "</td>";
        echo "<td>" . ucfirst($row['payment_status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['paymongo_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['payment_note']) . "</td>";
        echo "</tr>";
    }
    echo "</table></body></html>";
    exit;

} elseif ($format === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>GamingSpotHub Transactions PDF</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 30px; color: #333; }
            .header { text-align: center; border-bottom: 2px solid #20c8a1; padding-bottom: 20px; margin-bottom: 30px; }
            .header h1 { margin: 0; color: #0a2151; }
            .header p { margin: 5px 0 0; color: #666; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #f8f9fa; color: #0a2151; font-weight: bold; }
            tr:nth-child(even) { background-color: #fbfbfb; }
            .amt { text-align: right; font-weight: bold; }
            .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; }
            @media print {
                @page { size: landscape; margin: 10mm; }
                body { padding: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print" style="text-align:center;margin-bottom:20px;background:#fff3cd;padding:10px;border-radius:6px;border:1px solid #ffe69c;color:#664d03;">
            <strong>Pro Tip:</strong> In the print dialog, change the "Destination" to <strong>"Save as PDF"</strong> to generate your PDF document.
        </div>
        <div class="header">
            <h1>GamingSpotHub Transactions</h1>
            <p><strong>Report Period:</strong> <?= $dateLabel ?></p>
            <p>Generated on <?= date('F j, Y, g:i a') ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date &amp; Time</th>
                    <th>Customer</th>
                    <th>Unit</th>
                    <th>Type</th>
                    <th class="amt">Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>PayMongo ID</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; foreach ($data as $row): 
                    if ($row['payment_status'] === 'completed') {
                        $total += (float)$row['amount'];
                    }
                ?>
                <tr>
                    <td>#<?= $row['transaction_id'] ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($row['transaction_date'])) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['unit_number']) ?></td>
                    <td><?= ucfirst($row['rental_mode']) ?></td>
                    <td class="amt">₱<?= number_format((float)$row['amount'], 2) ?></td>
                    <td><?= ucfirst($row['payment_method']) ?></td>
                    <td><?= ucfirst($row['payment_status']) ?></td>
                    <td><?= htmlspecialchars($row['paymongo_id'] === '-' ? '' : $row['paymongo_id']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align:right;">Total Collected Amount (Completed):</th>
                    <th class="amt" style="color:#20c8a1;">₱<?= number_format($total, 2) ?></th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>
        <div class="footer">
            &copy; <?= date('Y') ?> GamingSpotHub. Strictly for internal financial tracking.
        </div>
    </body>
    </html>
    <?php
    exit;
}
