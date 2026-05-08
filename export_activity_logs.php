<?php
/**
 * export_activity_logs.php
 * Exports a detailed CSV, XLS, DOC, TXT, or PDF of activity logs for a specific Daily/Monthly/Yearly period.
 */
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';

requireRole(['owner', 'shopkeeper']);

$type = $_GET['type'] ?? 'daily';
$dateVal = $_GET['date'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv';
$logWhere = "";
$filenameSuffix = "";
$dateLabel = "";

if ($type === 'daily') {
    [$start, $end] = getOperatingDayBounds($dateVal);
    $logWhere = "l.created_at BETWEEN '$start' AND '$end'";
    $filenameSuffix = "Daily_" . $dateVal;
    $dateLabel = date('F j, Y', strtotime($dateVal));
} elseif ($type === 'monthly') {
    $safeDate = $conn->real_escape_string($dateVal);
    $logWhere = "DATE_FORMAT(l.created_at, '%Y-%m') = '$safeDate'";
    $filenameSuffix = "Monthly_" . $dateVal;
    $dateLabel = date('F Y', strtotime($dateVal . '-01'));
} elseif ($type === 'yearly') {
    $safeDate = (int)$dateVal;
    $logWhere = "YEAR(l.created_at) = $safeDate";
    $filenameSuffix = "Yearly_" . $safeDate;
    $dateLabel = "Year " . $safeDate;
}

$query = "SELECT l.log_id, l.created_at, u.full_name AS admin_name, l.action, l.details
          FROM activity_logs l
          JOIN users u ON l.user_id = u.user_id
          WHERE $logWhere
          ORDER BY l.created_at DESC";

$result = $conn->query($query);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$filename = "GamingSpotHub_Activity_Logs_" . $filenameSuffix;

if ($format === 'csv' || $format === 'txt') {
    $ext = ($format === 'csv') ? 'csv' : 'txt';
    $delimiter = ($format === 'csv') ? ',' : "\t";
    header('Content-Type: text/' . $ext . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Log ID', 'Timestamp', 'Admin Name', 'Action', 'Details'], $delimiter);
    foreach ($data as $row) {
        fputcsv($output, [
            $row['log_id'],
            date('M d, Y h:i A', strtotime($row['created_at'])),
            $row['admin_name'],
            $row['action'],
            $row['details']
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
    echo "<h2>GamingSpotHub - Activity Logs</h2>";
    echo "<p><strong>Period:</strong> $dateLabel</p>";
    echo "<table border='1' style='border-collapse:collapse; text-align:left;'>";
    echo "<tr><th>Log ID</th><th>Timestamp</th><th>Admin Name</th><th>Action</th><th>Details</th></tr>";
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $row['log_id'] . "</td>";
        echo "<td>" . date('M d, Y h:i A', strtotime($row['created_at'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['action']) . "</td>";
        echo "<td>" . htmlspecialchars($row['details']) . "</td>";
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
        <title>GamingSpotHub Activity Logs PDF</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 30px; color: #333; }
            .header { text-align: center; border-bottom: 2px solid #f1a83c; padding-bottom: 20px; margin-bottom: 30px; }
            .header h1 { margin: 0; color: #0a2151; }
            .header p { margin: 5px 0 0; color: #666; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #f8f9fa; color: #0a2151; font-weight: bold; }
            tr:nth-child(even) { background-color: #fbfbfb; }
            .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; }
            @media print {
                @page { size: portrait; margin: 10mm; }
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
            <h1>GamingSpotHub Activity Logs</h1>
            <p><strong>Report Period:</strong> <?= $dateLabel ?></p>
            <p>Generated on <?= date('F j, Y, g:i a') ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th style="width:140px;">Timestamp</th>
                    <th style="width:120px;">Admin Name</th>
                    <th style="width:120px;">Action</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td>#<?= $row['log_id'] ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['admin_name']) ?></td>
                    <td><span style="text-transform:uppercase;font-size:10px;font-weight:bold;"><?= htmlspecialchars($row['action']) ?></span></td>
                    <td><?= htmlspecialchars($row['details']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="footer">
            &copy; <?= date('Y') ?> GamingSpotHub. Strictly for internal administrative tracking.
        </div>
    </body>
    </html>
    <?php
    exit;
}
