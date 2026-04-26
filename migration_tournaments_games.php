<?php
/**
 * migration_tournaments_games.php
 * Fixes tournaments schema mismatch + creates games table.
 * Safe to re-run (idempotent checks per step).
 */
$c = new mysqli('localhost', 'root', '', 'gamingspothub');
if ($c->connect_error) die('DB error: ' . $c->connect_error);

$ok = 0; $err = 0;
function run($c, $sql, $label) {
    global $ok, $err;
    if ($c->query($sql)) { echo "✓ $label" . PHP_EOL; $ok++; }
    else { echo "✗ $label: " . $c->error . PHP_EOL; $err++; }
}

// 1. Add game_name column (if not present)
$cols = $c->query("SHOW COLUMNS FROM tournaments LIKE 'game_name'")->num_rows;
if ($cols === 0) {
    run($c, "ALTER TABLE tournaments ADD COLUMN game_name VARCHAR(150) NOT NULL DEFAULT '' AFTER game_id", 'Add game_name column');
} else { echo "– game_name column already exists" . PHP_EOL; }

// 2. Add created_by column (if not present)
$cols = $c->query("SHOW COLUMNS FROM tournaments LIKE 'created_by'")->num_rows;
if ($cols === 0) {
    run($c, "ALTER TABLE tournaments ADD COLUMN created_by INT NULL AFTER announcement", 'Add created_by column');
} else { echo "– created_by column already exists" . PHP_EOL; }

// 3. Fix status enum to include 'scheduled'
run($c, "ALTER TABLE tournaments MODIFY COLUMN status ENUM('upcoming','scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming'", 'Update status enum');

// 4. Fix console_type enum to include PS4
run($c, "ALTER TABLE tournaments MODIFY COLUMN console_type ENUM('PS5','Xbox Series X','PS4','PC','Multi') NOT NULL", 'Update console_type enum');

// 5. Drop old game_id FK/column if safe
$cols = $c->query("SHOW COLUMNS FROM tournaments LIKE 'game_id'")->num_rows;
if ($cols > 0) {
    // Drop FK if exists
    $c->query("ALTER TABLE tournaments DROP FOREIGN KEY IF EXISTS fk_tournament_game");
    run($c, "ALTER TABLE tournaments MODIFY COLUMN game_id INT NULL", 'Make game_id nullable');
    echo "  (game_id kept as nullable reference for future FK)" . PHP_EOL;
}

// 6. Create games table if not exists
$exists = $c->query("SHOW TABLES LIKE 'games'")->num_rows;
if ($exists === 0) {
    run($c, "CREATE TABLE games (
        game_id      INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        game_name    VARCHAR(150) NOT NULL,
        genre        VARCHAR(80) NOT NULL DEFAULT 'Action',
        console_type ENUM('PS5','Xbox Series X','PS4','PC','Multi') NOT NULL DEFAULT 'Multi',
        platform     VARCHAR(100) NULL,
        description  TEXT NULL,
        is_active    TINYINT(1) NOT NULL DEFAULT 1,
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'Create games table');
} else { echo "– games table already exists" . PHP_EOL; }

// 7. Seed a few starter games
$cnt = $c->query("SELECT COUNT(*) AS n FROM games")->fetch_assoc()['n'];
if ($cnt == 0) {
    $seeds = [
        ['FIFA 24',              'Sports',  'PS5'],
        ['God of War Ragnarok',  'Action',  'PS5'],
        ['Spider-Man 2',         'Action',  'PS5'],
        ['Call of Duty: MW3',    'Shooter', 'Multi'],
        ['Tekken 8',             'Fighting','PS5'],
        ['Mortal Kombat 1',      'Fighting','PS5'],
        ['Forza Horizon 5',      'Racing',  'Xbox Series X'],
        ['Halo Infinite',        'Shooter', 'Xbox Series X'],
        ['GTA V',                'Open World','Multi'],
        ['Minecraft',            'Sandbox', 'Multi'],
        ['NBA 2K24',             'Sports',  'Multi'],
        ['Elden Ring',           'RPG',     'Multi'],
    ];
    $stmt = $c->prepare("INSERT INTO games (game_name, genre, console_type) VALUES (?,?,?)");
    foreach ($seeds as $s) {
        $stmt->bind_param('sss', $s[0], $s[1], $s[2]);
        $stmt->execute();
    }
    echo "✓ Seeded " . count($seeds) . " starter games" . PHP_EOL;
}

echo PHP_EOL . "Done. OK=$ok  Errors=$err" . PHP_EOL;
