<?php
/**
 * Good Spot Gaming Hub - Database Helper Functions
 * 
 * Reusable functions for common database operations.
 */

require_once __DIR__ . '/db_config.php';


// ============================================================================
// CONSOLE FUNCTIONS
// ============================================================================

/**
 * Get all consoles, optionally filtered by status or type.
 */
function getConsoles($status = null, $type = null) {
    global $conn;
    $sql = "SELECT * FROM consoles WHERE 1=1";
    $params = [];
    $types = "";

    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    if ($type) {
        $sql .= " AND console_type = ?";
        $params[] = $type;
        $types .= "s";
    }

    $sql .= " ORDER BY console_type, unit_number";
    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all available consoles.
 */
function getAvailableConsoles() {
    return getConsoles('available');
}

/**
 * Update console status (available, in_use, maintenance).
 */
function updateConsoleStatus($console_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE consoles SET status = ? WHERE console_id = ?");
    $stmt->bind_param("si", $status, $console_id);
    return $stmt->execute();
}


// ============================================================================
// GAMING SESSION FUNCTIONS
// ============================================================================

/**
 * Start a new gaming session.
 * Automatically marks the console as in_use.
 */
function startSession($user_id, $console_id, $rental_mode, $created_by) {
    global $conn;

    // Get the console's hourly rate
    $stmt = $conn->prepare("SELECT hourly_rate FROM consoles WHERE console_id = ? AND status = 'available'");
    $stmt->bind_param("i", $console_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Console is not available'];
    }

    $rate = $result->fetch_assoc()['hourly_rate'];

    $conn->begin_transaction();
    try {
        // Create session
        $stmt = $conn->prepare(
            "INSERT INTO gaming_sessions (user_id, console_id, rental_mode, hourly_rate, created_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisdi", $user_id, $console_id, $rental_mode, $rate, $created_by);
        $stmt->execute();
        $session_id = $conn->insert_id;

        // Mark console as in use
        updateConsoleStatus($console_id, 'in_use');

        $conn->commit();
        return ['success' => true, 'session_id' => $session_id];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * End a gaming session.
 * Computes duration and total cost, marks console as available.
 */
function endSession($session_id) {
    global $conn;

    // Get session details
    $stmt = $conn->prepare(
        "SELECT gs.*, c.console_id, s.setting_value AS unlimited_rate
         FROM gaming_sessions gs
         JOIN consoles c ON gs.console_id = c.console_id
         LEFT JOIN system_settings s ON s.setting_key = 'unlimited_rate'
         WHERE gs.session_id = ? AND gs.status = 'active'"
    );
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Session not found or already ended'];
    }

    $session = $result->fetch_assoc();
    $end_time = date('Y-m-d H:i:s');
    $start = new DateTime($session['start_time']);
    $end = new DateTime($end_time);
    $duration = (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);

    // Calculate cost based on rental mode
    $total_cost = computeRentalFee($session['rental_mode'], $duration, $session['hourly_rate'], $session['unlimited_rate'] ?? 300);

    // Add any approved additional request costs
    $stmt2 = $conn->prepare("SELECT COALESCE(SUM(extra_cost), 0) AS extras FROM additional_requests WHERE session_id = ? AND status = 'approved'");
    $stmt2->bind_param("i", $session_id);
    $stmt2->execute();
    $extras = $stmt2->get_result()->fetch_assoc()['extras'];
    $total_cost += $extras;

    $conn->begin_transaction();
    try {
        // Update session
        $stmt = $conn->prepare(
            "UPDATE gaming_sessions SET end_time = ?, duration_minutes = ?, total_cost = ?, status = 'completed'
             WHERE session_id = ?"
        );
        $stmt->bind_param("sidi", $end_time, $duration, $total_cost, $session_id);
        $stmt->execute();

        // Mark console as available
        updateConsoleStatus($session['console_id'], 'available');

        $conn->commit();
        return ['success' => true, 'duration_minutes' => $duration, 'total_cost' => $total_cost];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Compute rental fee based on mode.
 */
function computeRentalFee($rental_mode, $duration_minutes, $hourly_rate, $unlimited_rate = 300) {
    switch ($rental_mode) {
        case 'hourly':
            // Charge per hour, round up partial hours
            $hours = ceil($duration_minutes / 60);
            return $hours * $hourly_rate;

        case 'open_time':
            // Charge per minute (hourly rate / 60)
            $rate_per_minute = $hourly_rate / 60;
            return round($duration_minutes * $rate_per_minute, 2);

        case 'unlimited':
            // Flat rate for unlimited play
            return (float) $unlimited_rate;

        default:
            return 0;
    }
}

/**
 * Get active sessions with console and user info.
 */
function getActiveSessions() {
    global $conn;
    $sql = "SELECT gs.*, u.full_name AS customer_name, c.console_name, c.console_type, c.unit_number
            FROM gaming_sessions gs
            JOIN users u ON gs.user_id = u.user_id
            JOIN consoles c ON gs.console_id = c.console_id
            WHERE gs.status = 'active'
            ORDER BY gs.start_time DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get session history for a user.
 */
function getUserSessionHistory($user_id, $limit = 20) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT gs.*, c.console_name, c.console_type
         FROM gaming_sessions gs
         JOIN consoles c ON gs.console_id = c.console_id
         WHERE gs.user_id = ?
         ORDER BY gs.start_time DESC
         LIMIT ?"
    );
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// ============================================================================
// TRANSACTION FUNCTIONS
// ============================================================================

/**
 * Record a payment transaction.
 */
function recordTransaction($session_id, $user_id, $amount, $payment_method, $processed_by) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO transactions (session_id, user_id, amount, payment_method, payment_status, processed_by)
         VALUES (?, ?, ?, ?, 'completed', ?)"
    );
    $stmt->bind_param("iidsi", $session_id, $user_id, $amount, $payment_method, $processed_by);
    return $stmt->execute();
}


// ============================================================================
// TOURNAMENT FUNCTIONS
// ============================================================================

/**
 * Get upcoming or ongoing tournaments.
 */
function getUpcomingTournaments() {
    global $conn;
    $sql = "SELECT t.*, g.game_name,
                   (SELECT COUNT(*) FROM tournament_participants tp WHERE tp.tournament_id = t.tournament_id) AS current_participants
            FROM tournaments t
            JOIN games g ON t.game_id = g.game_id
            WHERE t.status IN ('upcoming', 'ongoing')
            ORDER BY t.start_date ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Register a participant in a tournament.
 */
function registerForTournament($tournament_id, $user_id) {
    global $conn;

    // Check if already registered
    $stmt = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $tournament_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Already registered for this tournament'];
    }

    // Check max participants
    $stmt = $conn->prepare(
        "SELECT t.max_participants,
                (SELECT COUNT(*) FROM tournament_participants tp WHERE tp.tournament_id = t.tournament_id) AS current_count
         FROM tournaments t WHERE t.tournament_id = ? AND t.status = 'upcoming'"
    );
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Tournament not found or registration closed'];
    }

    $tournament = $result->fetch_assoc();
    if ($tournament['current_count'] >= $tournament['max_participants']) {
        return ['success' => false, 'message' => 'Tournament is full'];
    }

    // Register
    $stmt = $conn->prepare("INSERT INTO tournament_participants (tournament_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $tournament_id, $user_id);
    $stmt->execute();

    return ['success' => true, 'participant_id' => $conn->insert_id];
}


// ============================================================================
// GAME FUNCTIONS
// ============================================================================

/**
 * Get all games, optionally filtered.
 */
function getGames($console_type = null, $new_only = false) {
    global $conn;
    $sql = "SELECT * FROM games WHERE is_available = 1";
    $params = [];
    $types = "";

    if ($console_type) {
        $sql .= " AND (console_type = ? OR console_type = 'Both')";
        $params[] = $console_type;
        $types .= "s";
    }
    if ($new_only) {
        $sql .= " AND is_new_release = 1";
    }

    $sql .= " ORDER BY added_date DESC, game_name ASC";
    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Submit a game installation request.
 */
function submitGameRequest($user_id, $game_name, $console_type, $message = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO game_requests (user_id, game_name, console_type, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $game_name, $console_type, $message);
    return $stmt->execute();
}


// ============================================================================
// REPORT FUNCTIONS
// ============================================================================

/**
 * Generate daily sales report data.
 */
function getDailySalesReport($date) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total_sessions,
                SUM(total_cost) AS total_revenue,
                AVG(duration_minutes) AS avg_duration,
                SUM(CASE WHEN rental_mode = 'hourly' THEN 1 ELSE 0 END) AS hourly_sessions,
                SUM(CASE WHEN rental_mode = 'open_time' THEN 1 ELSE 0 END) AS open_time_sessions,
                SUM(CASE WHEN rental_mode = 'unlimited' THEN 1 ELSE 0 END) AS unlimited_sessions
         FROM gaming_sessions
         WHERE DATE(start_time) = ? AND status = 'completed'"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get console usage statistics.
 */
function getConsoleUsageReport($date_from, $date_to) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT c.console_name, c.console_type, c.unit_number,
                COUNT(gs.session_id) AS total_sessions,
                COALESCE(SUM(gs.duration_minutes), 0) AS total_minutes,
                COALESCE(SUM(gs.total_cost), 0) AS total_revenue
         FROM consoles c
         LEFT JOIN gaming_sessions gs ON c.console_id = gs.console_id
              AND gs.status = 'completed'
              AND DATE(gs.start_time) BETWEEN ? AND ?
         GROUP BY c.console_id
         ORDER BY total_sessions DESC"
    );
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// ============================================================================
// SYSTEM SETTINGS FUNCTIONS
// ============================================================================

/**
 * Get a system setting value.
 */
function getSetting($key) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    return null;
}

/**
 * Update a system setting.
 */
function updateSetting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    return $stmt->execute();
}
?>
