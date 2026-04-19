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
function startSession($user_id, $console_id, $rental_mode, $created_by, $planned_minutes = null,
                      $tendered = null, $payment_method = null) {
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
        // Create session (planned_minutes stored for hourly pre-booking)
        $stmt = $conn->prepare(
            "INSERT INTO gaming_sessions (user_id, console_id, rental_mode, planned_minutes, hourly_rate, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        // Use execute() array so NULL user_id is passed correctly (bind_param coerces null→0 for 'i')
        $stmt->execute([$user_id, $console_id, $rental_mode, $planned_minutes, $rate, $created_by]);
        $session_id = $conn->insert_id;

        // Mark console as in use
        updateConsoleStatus($console_id, 'in_use');

        // Record upfront payment if provided
        if ($tendered !== null && $payment_method !== null) {
            $session_cost = computeRentalFee($rental_mode, $planned_minutes ?? 0, $rate, 300, $planned_minutes);
            $tendered     = (float) $tendered;
            $shortfall    = $session_cost - $tendered;

            recordTransaction(
                $session_id,
                $user_id,
                $session_cost,
                $payment_method,
                $created_by,
                $tendered,
                $shortfall > 0 ? $shortfall : null,
                $shortfall > 0 ? 'Short payment recorded at session start' : null
            );
        }

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
    $end_time = date_create('now', new DateTimeZone('Asia/Manila'))->format('Y-m-d H:i:s');
    $start = new DateTime($session['start_time']);
    $end = new DateTime($end_time);
    $duration = (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);

    // Calculate cost based on rental mode
    $total_cost = computeRentalFee($session['rental_mode'], $duration, $session['hourly_rate'], $session['unlimited_rate'] ?? 300, $session['planned_minutes'] ?? null);

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
 * Compute cost for a partial portion of an hour.
 * Used for both Open Time billing and Hourly overtime calculation.
 *
 * Brackets: 1–4 min = ₱0 (grace), 5–19 min = ₱20,
 *           20–34 min = ₱40, 35–49 min = ₱60, 50–59 min = ₱80.
 */
function computePartialPeriodCost($minutes) {
    if ($minutes <= 0)  return 0;
    if ($minutes <= 4)  return 0;   // grace period
    if ($minutes <= 19) return 20;
    if ($minutes <= 34) return 40;
    if ($minutes <= 49) return 60;
    return 80; // 50–59 min = full hour charge
}

/**
 * Compute cost for any duration using bracket billing with a
 * FREE 30-MINUTE BONUS every 2 paid hours.
 *
 * Billing cycle = 150 min:
 *   - First 120 min (2 hrs): billed at ₱80/hr with partial brackets
 *   - Next  30 min (free): no charge
 *   - Then repeat
 *
 * Examples: 2:00 = ₱160, 2:30 = ₱160, 2:45 = ₱180, 4:00 = ₱280
 */
function computeTimedCost($minutes) {
    $minutes = max(0, (int)$minutes);
    if ($minutes === 0) return 0.0;

    $cycleLength = 150;   // 120 min paid + 30 min free
    $cycleCost   = 160;   // ₱160 per 2-hour paid block

    $fullCycles = (int)floor($minutes / $cycleLength);
    $cost       = $fullCycles * $cycleCost;
    $remainder  = $minutes % $cycleLength;

    if ($remainder > 120) {
        // Within the free 30-min window — charge the full 2-hour block
        $cost += $cycleCost;
    } else {
        // Within the paid window — apply hourly bracket billing
        $fullHours  = (int)floor($remainder / 60);
        $partialMin = $remainder % 60;
        $cost      += $fullHours * 80 + computePartialPeriodCost($partialMin);
    }

    return (float)$cost;
}

/**
 * Compute rental fee based on mode.
 *
 * Hourly  – pre-booked duration; charge base cost + overtime brackets if over.
 *           Base: 30 min = ₱50, each full hour = ₱80.
 * Open Time – bracket billing from minute 1 (same bracket as overtime).
 * Unlimited – flat rate.
 */
function computeRentalFee($rental_mode, $duration_minutes, $hourly_rate, $unlimited_rate = 300, $planned_minutes = null) {
    switch ($rental_mode) {
        case 'hourly':
            if ($planned_minutes !== null && $planned_minutes > 0) {
                // Base cost for pre-booked duration
                $base_cost = ($planned_minutes <= 30)
                    ? 50.0
                    : (float) ($planned_minutes / 60 * 80);

                // Overtime beyond booked time
                $overtime = $duration_minutes - $planned_minutes;
                if ($overtime <= 0) return $base_cost; // ended on time or early
                return $base_cost + computeTimedCost($overtime);
            }
            // No pre-booking data: fall back to open-time bracket pricing
            return computeTimedCost($duration_minutes);

        case 'open_time':
            return computeTimedCost($duration_minutes);

        case 'unlimited':
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
    $sql = "SELECT gs.*, COALESCE(u.full_name, 'Walk-in') AS customer_name,
                   c.console_name, c.console_type, c.unit_number,
                   COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id), 0) AS upfront_paid
            FROM gaming_sessions gs
            LEFT JOIN users u ON gs.user_id = u.user_id
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
 *
 * @param int    $session_id
 * @param int    $user_id
 * @param float  $amount           Actual amount due / collected
 * @param string $payment_method
 * @param int    $processed_by
 * @param float|null $tendered     Amount handed by customer (may be less than $amount)
 * @param float|null $shortfall    How short the customer is (positive = shortage)
 * @param string|null $note       Free-text note (e.g. "Short payment recorded")
 */
function recordTransaction($session_id, $user_id, $amount, $payment_method, $processed_by,
                            $tendered = null, $shortfall = null, $note = null) {
    global $conn;

    // Ensure numeric types are correct; keep null as null (not coerced to 0).
    $tendered  = ($tendered  !== null) ? (float)$tendered  : null;
    $shortfall = ($shortfall !== null) ? (float)$shortfall : null;

    $stmt = $conn->prepare(
        "INSERT INTO transactions
            (session_id, user_id, amount, payment_method, payment_status, processed_by,
             tendered_amount, shortfall_amount, payment_note)
         VALUES (?, ?, ?, ?, 'completed', ?, ?, ?, ?)"
    );

    // execute([...]) correctly passes NULL for all parameter types,
    // unlike bind_param() which silently converts null→0 for d/i types in older PHP.
    return $stmt->execute([
        $session_id,
        $user_id,
        (float)$amount,
        $payment_method,
        $processed_by,
        $tendered,
        $shortfall,
        $note,
    ]);
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


// ============================================================================
// RESERVATION FUNCTIONS
// ============================================================================

/**
 * Create a new reservation.
 *
 * @param int    $user_id
 * @param string $console_type      'PS5' or 'Xbox Series X'
 * @param string $rental_mode
 * @param int|null $planned_minutes For hourly mode
 * @param string $reserved_date    YYYY-MM-DD
 * @param string $reserved_time    HH:MM
 * @param string|null $notes
 * @param float  $downpayment_amount
 * @param string|null $downpayment_method
 * @return array ['success'=>bool, 'reservation_id'=>int|'message'=>string]
 */
function createReservation(
    $user_id, $console_type, $rental_mode, $planned_minutes,
    $reserved_date, $reserved_time, $notes = null,
    $downpayment_amount = 0.0, $downpayment_method = null
) {
    global $conn;

    // Basic validation
    $today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
    if ($reserved_date < $today) {
        return ['success' => false, 'message' => 'Cannot reserve a past date.'];
    }

    $downpayment_paid = ($downpayment_amount > 0 && $downpayment_method !== null) ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO reservations
            (user_id, console_type, rental_mode, planned_minutes, reserved_date, reserved_time,
             notes, downpayment_amount, downpayment_method, downpayment_paid, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)"
    );
    $stmt->bind_param(
        'ississsdsii',
        $user_id, $console_type, $rental_mode, $planned_minutes,
        $reserved_date, $reserved_time, $notes,
        $downpayment_amount, $downpayment_method, $downpayment_paid,
        $user_id   // created_by = the customer themselves
    );

    if ($stmt->execute()) {
        return ['success' => true, 'reservation_id' => $conn->insert_id];
    }
    return ['success' => false, 'message' => $conn->error];
}

/**
 * Get reservations, optionally filtered by status and/or date.
 */
function getReservations($status = null, $date = null) {
    global $conn;
    $sql = "
        SELECT r.*,
               u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
               c.unit_number, c.console_name
          FROM reservations r
          JOIN users u ON r.user_id = u.user_id
          LEFT JOIN consoles c ON r.console_id = c.console_id
         WHERE 1=1
    ";
    $params = []; $types = '';

    if ($status) {
        if (is_array($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND r.status IN ($placeholders)";
            foreach ($status as $s) { $params[] = $s; $types .= 's'; }
        } else {
            $sql .= " AND r.status = ?";
            $params[] = $status; $types .= 's';
        }
    }
    if ($date) {
        $sql .= " AND r.reserved_date = ?";
        $params[] = $date; $types .= 's';
    }
    $sql .= " ORDER BY r.reserved_date ASC, r.reserved_time ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a single reservation by ID.
 */
function getReservation($reservation_id) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT r.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                c.unit_number, c.console_name
           FROM reservations r
           JOIN users u ON r.user_id = u.user_id
           LEFT JOIN consoles c ON r.console_id = c.console_id
          WHERE r.reservation_id = ?"
    );
    $stmt->bind_param('i', $reservation_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Update reservation status (confirm / cancel / no_show).
 * Optionally assign a specific console_id when confirming.
 */
function updateReservationStatus($reservation_id, $status, $console_id = null) {
    global $conn;
    if ($console_id) {
        $stmt = $conn->prepare(
            "UPDATE reservations SET status = ?, console_id = ? WHERE reservation_id = ?"
        );
        $stmt->bind_param('sii', $status, $console_id, $reservation_id);
    } else {
        $stmt = $conn->prepare(
            "UPDATE reservations SET status = ? WHERE reservation_id = ?"
        );
        $stmt->bind_param('si', $status, $reservation_id);
    }
    return $stmt->execute();
}

/**
 * Convert a confirmed reservation into an active gaming session.
 * Marks reservation as 'converted' and calls startSession().
 *
 * @param int $reservation_id
 * @param int $console_id       The actual console unit to assign
 * @param int $shopkeeper_id    Staff converting the reservation
 * @return array ['success'=>bool, ...]
 */
function convertReservationToSession($reservation_id, $console_id, $shopkeeper_id) {
    global $conn;

    $res = getReservation($reservation_id);
    if (!$res) {
        return ['success' => false, 'message' => 'Reservation not found'];
    }
    if (!in_array($res['status'], ['pending', 'confirmed'])) {
        return ['success' => false, 'message' => 'Reservation cannot be converted in its current status'];
    }

    // Start the session
    $result = startSession(
        $res['user_id'],
        $console_id,
        $res['rental_mode'],
        $shopkeeper_id,
        $res['planned_minutes']
    );

    if ($result['success']) {
        // ── Credit the reservation downpayment against the new session ──
        // The downpayment was physically collected at reservation time.
        // Recording it here links it to the session so that every balance
        // calculation (upfront_paid) correctly shows only the REMAINING amount.
        if (!empty($res['downpayment_amount']) && (float)$res['downpayment_amount'] > 0) {
            recordTransaction(
                $result['session_id'],
                $res['user_id'],
                (float)$res['downpayment_amount'],
                $res['downpayment_method'] ?? 'cash',
                $shopkeeper_id,
                (float)$res['downpayment_amount'],
                null,
                'Downpayment transferred from reservation #' . $reservation_id
            );
        }

        // Mark reservation as converted
        updateReservationStatus($reservation_id, 'converted', $console_id);
    }

    return $result;
}

/**
 * Get upcoming reservations for today and the next 7 days.
 */
function getUpcomingReservations($days = null) {
    global $conn;
    $today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');

    if ($days !== null) {
        $until = (new DateTime("+{$days} days", new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        $stmt = $conn->prepare(
            "SELECT r.*, u.full_name AS customer_name, u.phone AS customer_phone,
                    c.unit_number, c.console_name
               FROM reservations r
               JOIN users u ON r.user_id = u.user_id
               LEFT JOIN consoles c ON r.console_id = c.console_id
              WHERE r.reserved_date BETWEEN ? AND ?
                AND r.status IN ('pending','confirmed')
              ORDER BY r.reserved_date ASC, r.reserved_time ASC"
        );
        $stmt->bind_param('ss', $today, $until);
    } else {
        // Show ALL future reservations (no upper bound)
        $stmt = $conn->prepare(
            "SELECT r.*, u.full_name AS customer_name, u.phone AS customer_phone,
                    c.unit_number, c.console_name
               FROM reservations r
               JOIN users u ON r.user_id = u.user_id
               LEFT JOIN consoles c ON r.console_id = c.console_id
              WHERE r.reserved_date >= ?
                AND r.status IN ('pending','confirmed')
              ORDER BY r.reserved_date ASC, r.reserved_time ASC"
        );
        $stmt->bind_param('s', $today);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get reservations for the logged-in customer.
 */
function getMyReservations($user_id) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT r.*, c.unit_number, c.console_name
           FROM reservations r
           LEFT JOIN consoles c ON r.console_id = c.console_id
          WHERE r.user_id = ?
          ORDER BY r.reserved_date DESC, r.reserved_time DESC"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all cancelled reservations (any canceller) for admin refund management.
 * Includes customer and console info. Ordered newest cancellation first.
 */
function getCancelledReservations() {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT r.*, u.full_name AS customer_name, u.phone AS customer_phone,
                c.unit_number, c.console_name
           FROM reservations r
           JOIN users u ON r.user_id = u.user_id
           LEFT JOIN consoles c ON r.console_id = c.console_id
          WHERE r.status = 'cancelled'
          ORDER BY r.reserved_date DESC, r.reserved_time DESC"
    );
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

