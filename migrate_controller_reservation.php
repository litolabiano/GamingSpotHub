<?php
require_once __DIR__ . '/includes/db_config.php';
echo "<pre style='font-family:monospace;background:#0a1020;color:#20c8a1;padding:20px;border-radius:8px;'>";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Add controller columns to reservations
$cols = $conn->query("SHOW COLUMNS FROM reservations LIKE 'with_controller'")->num_rows;
if ($cols === 0) {
    $ok = $conn->query("ALTER TABLE reservations
        ADD COLUMN with_controller TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Did customer add a controller rental?' AFTER notes,
        ADD COLUMN controller_id   INT NULL COMMENT 'FK → controllers.controller_id' AFTER with_controller,
        ADD COLUMN controller_fee  DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Controller rental fee at time of booking' AFTER controller_id,
        ADD CONSTRAINT fk_res_controller FOREIGN KEY (controller_id) REFERENCES controllers(controller_id) ON DELETE SET NULL ON UPDATE CASCADE
    ");
    echo "1. Added with_controller / controller_id / controller_fee columns: " . ($ok ? "✓" : $conn->error) . "\n";
} else {
    echo "1. Columns already exist — skipped.\n";
}

// 2. Ensure controller_rental_fee exists in system_settings
$exists = $conn->query("SELECT setting_id FROM system_settings WHERE setting_key = 'controller_rental_fee'")->num_rows;
if (!$exists) {
    $conn->query("INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('controller_rental_fee','20.00','Additional controller rental fee per session in ₱')");
    echo "2. Inserted controller_rental_fee setting (₱20.00)\n";
} else {
    echo "2. controller_rental_fee already in system_settings ✓\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n<strong>── reservations columns (controller-related) ──</strong>\n";
$res = $conn->query("SHOW COLUMNS FROM reservations WHERE Field IN ('with_controller','controller_id','controller_fee','notes')");
while ($r = $res->fetch_assoc()) echo "  {$r['Field']} | {$r['Type']} | null={$r['Null']} | default={$r['Default']}\n";

echo "\n<strong style='color:#f1e1aa;'>Migration done ✓</strong>\n</pre>";
?>
