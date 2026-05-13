<?php
/**
 * GamingSpotHub — Database Setup & Migration Tool v2.0
 *
 * Two modes:
 *   clean   — Creates a fresh database from gamingspothub_clean.sql (drops existing tables)
 *   migrate — Applies gamingspothub_migrate.sql patches to the existing live database
 *
 * Access: http://localhost/GamingSpotHub/database/setup_database.php
 * SECURITY: Remove or restrict this file after setup!
 */

// Disable mysqli exceptions so missing tables don't throw fatal errors
mysqli_report(MYSQLI_REPORT_OFF);

define('DB_HOST',     'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'gamingspothub');

$mode    = $_GET['mode'] ?? 'status';
$results = [];
$errors  = [];

// ── Connect ───────────────────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
if ($conn->connect_error) {
    die('<p style="color:red">MySQL connection failed: ' . $conn->connect_error . '</p>');
}

// Ensure database exists
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);
$conn->set_charset('utf8mb4');

// ── Table list (in dependency order) ─────────────────────────────────────────
$TABLES = [
    'users', 'system_settings', 'console_types', 'controller_types', 'games',
    'pricing_tiers', 'blocked_dates', 'shop_hours_overrides',
    'consoles', 'controllers',
    'gaming_sessions', 'session_extensions', 'additional_requests', 'transactions',
    'reservations', 'reservation_cancellations', 'reservation_reschedules',
    'tournaments', 'tournament_participants',
    'activity_logs', 'reports', 'notifications',
];

// ── Run SQL File ──────────────────────────────────────────────────────────────
function runSqlFile(mysqli $conn, string $filePath, array &$results, array &$errors): void {
    if (!file_exists($filePath)) {
        $errors[] = "File not found: $filePath";
        return;
    }
    $raw = file_get_contents($filePath);

    // Strip full-line comments and blank lines before splitting
    $lines = explode("\n", $raw);
    $cleaned = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue; // skip comment/blank lines
        }
        $cleaned[] = $line;
    }
    $sql = implode("\n", $cleaned);

    // Split on semicolons
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        if ($conn->query($stmt) === false) {
            $errors[] = $conn->error . '<br><small>' . htmlspecialchars(substr($stmt, 0, 120)) . '…</small>';
        } else {
            $results[] = '✓ ' . htmlspecialchars(substr($stmt, 0, 100)) . (strlen($stmt) > 100 ? '…' : '');
        }
    }
}

// ── Get current table status ──────────────────────────────────────────────────
function getTableStatus(mysqli $conn, array $tables): array {
    $status = [];
    foreach ($tables as $t) {
        $r = $conn->query("SELECT COUNT(*) AS cnt FROM `$t`");
        if ($r) {
            $status[$t] = ['exists' => true, 'rows' => (int)$r->fetch_assoc()['cnt']];
        } else {
            $status[$t] = ['exists' => false, 'rows' => 0];
        }
    }
    return $status;
}

// ── Actions ───────────────────────────────────────────────────────────────────
if ($mode === 'clean' && isset($_POST['confirm'])) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    foreach (array_reverse($TABLES) as $t) {
        $conn->query("DROP TABLE IF EXISTS `$t`");
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    runSqlFile($conn, __DIR__ . '/gamingspothub_clean.sql', $results, $errors);
} elseif ($mode === 'migrate' && isset($_POST['confirm'])) {
    runSqlFile($conn, __DIR__ . '/gamingspothub_migrate.sql', $results, $errors);
}

$tableStatus = getTableStatus($conn, $TABLES);
$existCount  = count(array_filter($tableStatus, fn($s) => $s['exists']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GamingSpotHub — Database Setup</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; padding: 2rem; }
  h1 { font-size: 1.8rem; color: #a78bfa; margin-bottom: .25rem; }
  .subtitle { color: #94a3b8; margin-bottom: 2rem; font-size: .9rem; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
  @media(max-width:768px) { .grid { grid-template-columns: 1fr; } }
  .card { background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid #334155; }
  .card h2 { font-size: 1rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 1rem; }
  .badge { display: inline-block; padding: .2rem .6rem; border-radius: 9999px; font-size: .75rem; font-weight: 600; }
  .badge-ok { background: #166534; color: #86efac; }
  .badge-missing { background: #7f1d1d; color: #fca5a5; }
  table { width: 100%; border-collapse: collapse; }
  td, th { padding: .45rem .75rem; text-align: left; font-size: .85rem; border-bottom: 1px solid #1e293b; }
  th { color: #64748b; font-weight: 600; }
  tr:hover td { background: #1e293b; }
  .btn { display: inline-block; padding: .6rem 1.4rem; border-radius: 8px; font-weight: 600; font-size: .9rem;
         cursor: pointer; border: none; text-decoration: none; transition: opacity .15s; }
  .btn:hover { opacity: .85; }
  .btn-purple { background: #7c3aed; color: #fff; }
  .btn-blue   { background: #1d4ed8; color: #fff; }
  .btn-red    { background: #dc2626; color: #fff; }
  .btn-gray   { background: #334155; color: #e2e8f0; }
  .actions { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
  .log { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 1rem;
         max-height: 300px; overflow-y: auto; font-family: monospace; font-size: .8rem; }
  .log-ok  { color: #4ade80; }
  .log-err { color: #f87171; }
  .warning { background: #451a03; border: 1px solid #b45309; border-radius: 8px; padding: 1rem;
             color: #fbbf24; margin-bottom: 1rem; font-size: .9rem; }
  .stat-bar { display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem; }
  .stat-num { font-size: 2rem; font-weight: 700; color: #a78bfa; }
  .stat-label { color: #94a3b8; font-size: .85rem; }
</style>
</head>
<body>

<h1>🎮 GamingSpotHub — Database Setup</h1>
<p class="subtitle">v2.0 · <?= DB_NAME ?> @ <?= DB_HOST ?></p>

<?php if (!empty($errors)): ?>
<div class="card" style="border-color:#dc2626;margin-bottom:1.5rem;">
  <h2 style="color:#f87171">⚠ Errors (<?= count($errors) ?>)</h2>
  <div class="log">
    <?php foreach ($errors as $e): ?>
      <div class="log-err">✗ <?= $e ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($results)): ?>
<div class="card" style="border-color:#16a34a;margin-bottom:1.5rem;">
  <h2 style="color:#4ade80">✓ Completed (<?= count($results) ?> statements)</h2>
  <div class="log">
    <?php foreach ($results as $r): ?>
      <div class="log-ok"><?= $r ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="grid">
  <!-- Status -->
  <div class="card">
    <h2>Database Status</h2>
    <div class="stat-bar">
      <span class="stat-num"><?= $existCount ?>/<?= count($TABLES) ?></span>
      <span class="stat-label">tables exist</span>
    </div>
    <table>
      <tr><th>Table</th><th>Status</th><th>Rows</th></tr>
      <?php foreach ($tableStatus as $t => $s): ?>
      <tr>
        <td><?= $t ?></td>
        <td><span class="badge <?= $s['exists'] ? 'badge-ok' : 'badge-missing' ?>">
          <?= $s['exists'] ? 'OK' : 'MISSING' ?>
        </span></td>
        <td><?= $s['exists'] ? number_format($s['rows']) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- Actions -->
  <div class="card">
    <h2>Actions</h2>

    <p style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem;">
      <strong style="color:#e2e8f0;">Migrate</strong> — applies ALTER TABLE patches to your existing live database (safe, no data loss).<br><br>
      <strong style="color:#e2e8f0;">Clean Install</strong> — drops all tables and rebuilds from scratch (⚠ all data will be lost).
    </p>

    <?php if ($mode === 'migrate'): ?>
      <div class="warning">You are about to run <strong>gamingspothub_migrate.sql</strong> on the live database. Review the file before proceeding.</div>
      <form method="POST" action="?mode=migrate">
        <div class="actions">
          <button name="confirm" value="1" class="btn btn-blue">▶ Run Migration Now</button>
          <a href="?" class="btn btn-gray">Cancel</a>
        </div>
      </form>
    <?php elseif ($mode === 'clean'): ?>
      <div class="warning">⚠ <strong>DANGER:</strong> This will DROP all <?= count($TABLES) ?> tables and ALL existing data before rebuilding. This cannot be undone!</div>
      <form method="POST" action="?mode=clean">
        <div class="actions">
          <button name="confirm" value="1" class="btn btn-red">🗑 DROP & Rebuild</button>
          <a href="?" class="btn btn-gray">Cancel</a>
        </div>
      </form>
    <?php else: ?>
      <div class="actions">
        <a href="?mode=migrate" class="btn btn-blue">🔧 Migrate Existing DB</a>
        <a href="?mode=clean" class="btn btn-red">🔄 Clean Install</a>
        <a href="?" class="btn btn-gray">↺ Refresh Status</a>
      </div>
    <?php endif; ?>

    <hr style="border-color:#334155;margin:1.5rem 0;">
    <h2 style="margin-bottom:.75rem;">SQL Files</h2>
    <table>
      <tr>
        <td><code>gamingspothub_clean.sql</code></td>
        <td><?= file_exists(__DIR__.'/gamingspothub_clean.sql')
            ? '<span class="badge badge-ok">Found ('.number_format(filesize(__DIR__.'/gamingspothub_clean.sql')).' B)</span>'
            : '<span class="badge badge-missing">Missing</span>' ?></td>
      </tr>
      <tr>
        <td><code>gamingspothub_migrate.sql</code></td>
        <td><?= file_exists(__DIR__.'/gamingspothub_migrate.sql')
            ? '<span class="badge badge-ok">Found ('.number_format(filesize(__DIR__.'/gamingspothub_migrate.sql')).' B)</span>'
            : '<span class="badge badge-missing">Missing</span>' ?></td>
      </tr>
    </table>
  </div>
</div>

<p style="color:#475569;font-size:.8rem;margin-top:1rem;">
  ⚠ Security: Restrict or delete <code>setup_database.php</code> after setup is complete.
</p>

</body>
</html>
