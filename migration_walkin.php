<?php
/**
 * migration_walkin.php — Walk-in Customer Setup
 * Run once. Safe to re-run (idempotent).
 */
$c = new mysqli('localhost', 'root', '', 'gamingspothub');
if ($c->connect_error) die('DB error: ' . $c->connect_error . PHP_EOL);

// 1. Add 'walkin' to the role ENUM if not already present
$c->query("ALTER TABLE users MODIFY COLUMN role ENUM('customer','shopkeeper','owner','walkin') NOT NULL DEFAULT 'customer'");
echo ($c->error ? 'ENUM alter failed: ' . $c->error : 'Role ENUM updated') . PHP_EOL;

// 2. Check if walk-in user already exists
$ex = $c->query("SELECT user_id FROM users WHERE role='walkin' LIMIT 1");
if ($ex->num_rows > 0) {
    $row = $ex->fetch_assoc();
    echo 'Walk-in user already exists with ID: ' . $row['user_id'] . PHP_EOL;
    exit(0);
}

// 3. Insert the system walk-in user
//    email_verified=1 so no token flow triggers; status=inactive so they can't log in
$stmt = $c->prepare(
    "INSERT INTO users (email, password_hash, full_name, role, status, email_verified, created_at)
     VALUES ('walkin@system.local', '', 'Walk-in Customer', 'walkin', 'inactive', 1, NOW())"
);
$stmt->execute();
if ($c->error) {
    echo 'INSERT failed: ' . $c->error . PHP_EOL;
    exit(1);
}
$id = $c->insert_id;
echo 'Walk-in user created with ID: ' . $id . PHP_EOL;
echo PHP_EOL;
echo '>>> Update WALKIN_USER_ID in includes/db_functions.php to: ' . $id . PHP_EOL;
