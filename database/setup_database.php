<?php
/**
 * Good Spot Gaming Hub - Database Setup Script
 * 
 * Run this once to create the database, tables, and optionally load sample data.
 * Access via: http://localhost/GamingSpotHub/database/setup_database.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials (without selecting a database yet)
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gamingspothub';

$messages = [];
$errors = [];
$load_sample = isset($_GET['sample']) && $_GET['sample'] === 'yes';

// Step 1: Connect to MySQL
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</h2><p>Make sure XAMPP MySQL is running.</p>");
}
$messages[] = "✅ Connected to MySQL server";

// Step 2: Create database
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$messages[] = "✅ Database '$dbname' created (or already exists)";

// Step 3: Select database
$conn->select_db($dbname);
$conn->set_charset("utf8mb4");

// Step 4: Run schema.sql
$schema_file = __DIR__ . '/schema.sql';
if (file_exists($schema_file)) {
    $schema_sql = file_get_contents($schema_file);
    // Remove the CREATE DATABASE and USE statements (we already handled that)
    $schema_sql = preg_replace('/CREATE DATABASE.*?;/s', '', $schema_sql);
    $schema_sql = preg_replace('/USE\s+\w+;/s', '', $schema_sql);
    
    if ($conn->multi_query($schema_sql)) {
        // Consume all results
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        
        if ($conn->error) {
            $errors[] = "⚠️ Schema warning: " . $conn->error;
        } else {
            $messages[] = "✅ Schema tables created successfully";
        }
    } else {
        $errors[] = "❌ Schema error: " . $conn->error;
    }
} else {
    $errors[] = "❌ schema.sql not found at: $schema_file";
}

// Step 5: Verify tables
$expected_tables = [
    'users', 'consoles', 'gaming_sessions', 'additional_requests',
    'transactions', 'games', 'game_requests', 'tournaments',
    'tournament_participants', 'reports', 'system_settings'
];

$result = $conn->query("SHOW TABLES");
$existing_tables = [];
while ($row = $result->fetch_row()) {
    $existing_tables[] = $row[0];
}

$missing = array_diff($expected_tables, $existing_tables);
if (empty($missing)) {
    $messages[] = "✅ All 11 tables verified: " . implode(', ', $expected_tables);
} else {
    $errors[] = "❌ Missing tables: " . implode(', ', $missing);
}

// Step 6: Load sample data (optional)
if ($load_sample && empty($errors)) {
    $sample_file = __DIR__ . '/sample_data.sql';
    if (file_exists($sample_file)) {
        $sample_sql = file_get_contents($sample_file);
        $sample_sql = preg_replace('/USE\s+\w+;/s', '', $sample_sql);
        
        if ($conn->multi_query($sample_sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            
            if ($conn->error) {
                $errors[] = "⚠️ Sample data warning: " . $conn->error;
            } else {
                $messages[] = "✅ Sample data loaded successfully";
            }
        } else {
            $errors[] = "❌ Sample data error: " . $conn->error;
        }
    } else {
        $errors[] = "❌ sample_data.sql not found at: $sample_file";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamingSpotHub - Database Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #0a0a0a;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            max-width: 700px;
            width: 100%;
            background: #1a1a2e;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 0 40px rgba(108, 99, 255, 0.15);
            border: 1px solid rgba(108, 99, 255, 0.2);
        }
        h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #6c63ff, #e94560);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle { color: #888; margin-bottom: 2rem; }
        .message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .success { background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; }
        .error { background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; }
        .actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6c63ff, #e94560);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(108, 99, 255, 0.4); }
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: #ccc;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); }
        .table-list {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }
        .table-tag {
            background: rgba(108, 99, 255, 0.1);
            border: 1px solid rgba(108, 99, 255, 0.2);
            border-radius: 6px;
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
            text-align: center;
            color: #b0a8ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎮 Good Spot Gaming Hub</h1>
        <p class="subtitle">Database Setup Tool</p>

        <?php foreach ($messages as $msg): ?>
            <div class="message success"><?= $msg ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $err): ?>
            <div class="message error"><?= $err ?></div>
        <?php endforeach; ?>

        <?php if (!empty($existing_tables)): ?>
            <div class="table-list">
                <?php foreach ($expected_tables as $table): ?>
                    <div class="table-tag" style="<?= in_array($table, $existing_tables) ? '' : 'border-color: #ef4444; color: #ef4444;' ?>">
                        <?= in_array($table, $existing_tables) ? '✅' : '❌' ?> <?= $table ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="actions">
            <?php if (!$load_sample): ?>
                <a href="?sample=yes" class="btn btn-primary">📦 Load Sample Data</a>
            <?php endif; ?>
            <a href="/GamingSpotHub/" class="btn btn-secondary">🏠 Back to Homepage</a>
            <a href="/phpmyadmin/" class="btn btn-secondary" target="_blank">🗃️ Open phpMyAdmin</a>
        </div>
    </div>
</body>
</html>
