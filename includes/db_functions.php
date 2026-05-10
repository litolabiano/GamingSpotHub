<?php
/**
 * Good Spot Gaming Hub - Database Helper Functions
 *
 * Reusable functions for common database operations.
 *
 * ─── Bonus Time Rule (Single Source of Truth) ─────────────────────────────
 * The shop awards free play time after every N paid minutes.
 * The rule lives ONLY in system_settings:
 *   bonus_paid_minutes  — every X paid min earns a bonus (default 120 = 2 hrs)
 *   bonus_free_minutes  — bonus size in minutes           (default 30)
 *   max_hourly_minutes  — max bookable paid minutes        (default 240 = 4 hrs)
 *
 * Use getPricingRules() to read them; use paidToTotalMinutes() everywhere
 * you need the real play time. Never hardcode 120 / 30 / 240 outside this file.
 * ──────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/db_config.php';

// ============================================================================
// WALK-IN CUSTOMER SYSTEM USER
// ============================================================================
// The walk-in user is a real row in `users` with role='walkin' and status='inactive'
// (cannot log in). All sessions/transactions for anonymous customers reference
// this ID so that NOT NULL foreign-key constraints and JOINs work correctly.
// Run migration_walkin.php once to create this user if it doesn't exist.
define('WALKIN_USER_ID', 0);

/**
 * Return the walk-in system user's ID.
 * Falls back to querying the DB in case the constant needs updating.
 */
function getWalkinUserId(): int {
    global $conn;
    if (!defined('WALKIN_USER_ID')) {
        $r = $conn->query("SELECT user_id FROM users WHERE role='walkin' LIMIT 1");
        return $r && $r->num_rows ? (int)$r->fetch_assoc()['user_id'] : 0;
    }
    return WALKIN_USER_ID;
}

/**
 * Log an activity performed by an admin or shopkeeper.
 * @param int $user_id The ID of the staff member performing the action.
 * @param string $action The category or title of the action (e.g. "Start Session").
 * @param string $details A readable description of what was done.
 */
function logActivity(int $user_id, string $action, string $details) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    return $stmt->execute();
}

// ============================================================================
// PRICING RULES (DB-DRIVEN — single source of truth)
// ============================================================================

/** @var array|null Module-level cache so we only query once per request. */
$_pricingRulesCache = null;

/**
 * Read bonus / pricing rules from system_settings.
 * Returns an array with keys:
 *   bonus_paid_minutes, bonus_free_minutes, max_hourly_minutes,
 *   hourly_rate (global default), session_min_charge.
 */
function getPricingRules(): array {
    global $conn, $_pricingRulesCache;
    if ($_pricingRulesCache !== null) return $_pricingRulesCache;

    $rules = [
        'bonus_paid_minutes' => 120,   // every 2 paid hrs
        'bonus_free_minutes' => 30,    // earn 30 min free
        'max_hourly_minutes' => 240,   // cap at 4 paid hrs
        'hourly_rate'        => 80.0,  // ₱80/hr default
        'session_min_charge' => 50.0,  // ₱50 for ≤30 min start
        'pricing_tiers'      => [],    // Dynamic tiers
        'console_rates_by_name' => []  // Rates mapped by console type name
    ];

    // Fetch system settings
    $keys = "'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','session_min_charge'";
    $res  = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($keys)");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            switch ($row['setting_key']) {
                case 'bonus_paid_minutes': $rules['bonus_paid_minutes'] = (int)  $row['setting_value']; break;
                case 'bonus_free_minutes': $rules['bonus_free_minutes'] = (int)  $row['setting_value']; break;
                case 'max_hourly_minutes': $rules['max_hourly_minutes'] = (int)  $row['setting_value']; break;
                case 'session_min_charge': $rules['session_min_charge'] = (float)$row['setting_value']; break;
            }
        }
    }

    // Fetch console rates
    $resRates = $conn->query("SELECT type_name, hourly_rate FROM console_types WHERE is_archived = 0");
    if ($resRates) {
        while ($row = $resRates->fetch_assoc()) {
            $rules['console_rates_by_name'][$row['type_name']] = (float)$row['hourly_rate'];
        }
    }

    // Fetch pricing tiers
    $resTiers = $conn->query("SELECT min_minutes, max_minutes, charge FROM pricing_tiers ORDER BY min_minutes ASC");
    if ($resTiers) {
        while ($row = $resTiers->fetch_assoc()) {
            $rules['pricing_tiers'][] = [
                'min'    => (int)$row['min_minutes'],
                'max'    => (int)$row['max_minutes'],
                'charge' => (float)$row['charge']
            ];
        }
    }

    $_pricingRulesCache = $rules;
    return $rules;
}

/**
 * Calculate bonus free minutes for a given paid duration.
 * e.g. calcBonusMinutes(120) = 30, calcBonusMinutes(240) = 60
 */
function calcBonusMinutes(int $paid_minutes, ?array $rules = null): int {
    $rules = $rules ?? getPricingRules();
    return (int) floor($paid_minutes / $rules['bonus_paid_minutes']) * $rules['bonus_free_minutes'];
}

/**
 * Convert PAID minutes to TOTAL play minutes (paid + bonus).
 * This is the only place that applies the bonus.
 * e.g. paidToTotalMinutes(120) = 150, paidToTotalMinutes(240) = 300
 */
function paidToTotalMinutes(int $paid_minutes, ?array $rules = null): int {
    return $paid_minutes + calcBonusMinutes($paid_minutes, $rules);
}

/**
 * Compute the base session cost for an hourly booking from its TOTAL planned minutes.
 * planned_minutes in the DB stores total play time (paid + free bonus), so we must
 * reverse-calculate the paid portion before applying the rate.
 *
 * e.g. planned=300 (4 paid hrs + 1 free hr) → paid=240 → ₱320
 *      planned=150 (2 paid hrs + 30 free min) → paid=120 → ₱160
 *      planned=30  (30 min, no bonus)          → session_min_charge
 */
function computeHourlySessionBaseCost(int $total_minutes, ?array $rules = null): float {
    $rules = $rules ?? getPricingRules();
    if ($total_minutes <= 0) return 0.0;
    if ($total_minutes <= 30) return $rules['session_min_charge'];

    $bp = $rules['bonus_paid_minutes'];  // e.g. 120
    $bf = $rules['bonus_free_minutes'];  // e.g. 30

    // Walk down from total_minutes to find the paid_minutes p such that
    // paidToTotalMinutes(p) === total_minutes.
    for ($p = $total_minutes; $p >= 0; $p--) {
        if ($p + (int)floor($p / $bp) * $bf === $total_minutes) {
            // Apply the new pricing structure: ₱50 for first 30m, straight hourly rate thereafter
            if ($p <= 0) return 0.0;
            if ($p <= 30) return (float)$rules['session_min_charge'];
            return (float)round($p / 60 * $rules['hourly_rate'], 2);
        }
    }
    // Fallback: no bonus found — treat all minutes as paid (shouldn't happen)
    return (float)round($total_minutes / 60 * $rules['hourly_rate'], 2);
}

/**
 * Generate the duration option list for hourly booking UIs.
 * Returns array of ['paid', 'total', 'cost', 'bonus', 'label_paid', 'label_total', 'label_bonus']
 * Step size is always 30 min; stops at max_hourly_minutes.
 */
function getHourlyDurationOptions(?array $rules = null): array {
    $rules  = $rules ?? getPricingRules();
    $max    = $rules['max_hourly_minutes'];
    $rate   = $rules['hourly_rate'];         // ₱/hr
    $minChg = $rules['session_min_charge'];  // ₱50 for ≤30 min

    $options = [];
    for ($paid = 30; $paid <= $max; $paid += 30) {
        $bonus = calcBonusMinutes($paid, $rules);
        $total = $paid + $bonus;
        // Pricing: ₱50 for first 30 mins, straight hourly rate thereafter
        $cost  = ($paid <= 30) ? $minChg : round($paid / 60 * $rate, 2);

        // Human-readable label helpers
        $fmtMin = function(int $m): string {
            $h = intdiv($m, 60); $r = $m % 60;
            if ($h && $r) return "{$h}h {$r}m";
            if ($h)       return "{$h}h";
            return "{$r}m";
        };

        $options[] = [
            'paid'        => $paid,
            'total'       => $total,
            'cost'        => $cost,
            'bonus'       => $bonus,
            'label_paid'  => $fmtMin($paid),
            'label_total' => $fmtMin($total),
            'label_bonus' => $bonus > 0 ? ' (+' . $fmtMin($bonus) . ')' : '',
        ];
    }
    return $options;
}


/**
 * Get all consoles, optionally filtered by status or type.
 */
function getConsoles($status = null, $type = null) {
    global $conn;
    $sql = "SELECT c.*, ct.type_name AS console_type, ct.hourly_rate
            FROM consoles c 
            LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id 
            WHERE 1=1";
    $params = [];
    $types = "";

    if ($status) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
        $types .= "s";
    } else {
        $sql .= " AND c.status != 'archived'";
    }
    if ($type) {
        $sql .= " AND ct.type_name = ?";
        $params[] = $type;
        $types .= "s";
    }

    $sql .= " ORDER BY ct.type_name, c.unit_number";
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

/**
 * Add a new console to the database.
 * $type_id is the console_types.type_id FK.
 */
function addConsole($name, $type_id, $unit_number, $controller_count = 2) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO consoles (console_name, console_type_id, unit_number, controller_count, status) VALUES (?, ?, ?, ?, 'available')");
        $stmt->bind_param("sisi", $name, $type_id, $unit_number, $controller_count);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/**
 * Update console controller count — kept for BC; controller_count removed from schema,
 * this now does nothing but avoids fatal errors in callers.
 */
function updateConsoleControllerCount($console_id, $count) {
    return true; // controller_count column removed; counts derived from controllers table
}


/**
 * Permanently delete a console. 
 * Safely unlinks related sessions and reservations by adding a note about the deletion.
 */
function deleteConsole($console_id) {
    global $conn;

    // 1. Fetch console details for the note and activity log
    $stmt = $conn->prepare("
        SELECT c.console_name, c.unit_number, ct.type_name 
        FROM consoles c 
        LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id 
        WHERE c.console_id = ?
    ");
    $stmt->bind_param("i", $console_id);
    $stmt->execute();
    $console = $stmt->get_result()->fetch_assoc();
    
    if (!$console) return ['success' => false, 'message' => 'Console not found.'];
    
    $cName = $console['console_name'];
    $cUnit = $console['unit_number'];
    $cType = $console['type_name'] ?? 'Unknown Type';

    // 2. Check for ACTIVE sessions. If active, we shouldn't delete.
    $stmt = $conn->prepare("SELECT COUNT(*) as active_count FROM gaming_sessions WHERE console_id = ? AND status = 'active'");
    $stmt->bind_param("i", $console_id);
    $stmt->execute();
    $activeCount = $stmt->get_result()->fetch_assoc()['active_count'];
    
    if ($activeCount > 0) {
        return ['success' => false, 'message' => 'This console is currently in use. End the session before deleting.'];
    }

    // 3. Retain with a note: Update related sessions and reservations
    $notePrefix = "[Deleted Console: $cName ($cUnit)] ";
    
    // Update gaming_sessions
    $stmt = $conn->prepare("UPDATE gaming_sessions SET notes = CONCAT(?, IFNULL(notes, '')) WHERE console_id = ?");
    $stmt->bind_param("si", $notePrefix, $console_id);
    $stmt->execute();
    
    // Update reservations
    $stmt = $conn->prepare("UPDATE reservations SET notes = CONCAT(?, IFNULL(notes, '')) WHERE console_id = ?");
    $stmt->bind_param("si", $notePrefix, $console_id);
    $stmt->execute();

    // Update reservation_reschedules
    $stmt = $conn->prepare("UPDATE reservation_reschedules SET reason_detail = CONCAT(?, IFNULL(reason_detail, '')) WHERE console_id = ? OR old_console_id = ?");
    $stmt->bind_param("sii", $notePrefix, $console_id, $console_id);
    $stmt->execute();
    
    // Explicitly nullify references in reschedules (as they lack FK SET NULL)
    $stmt = $conn->prepare("UPDATE reservation_reschedules SET console_id = NULL WHERE console_id = ?");
    $stmt->bind_param("i", $console_id);
    $stmt->execute();
    $stmt = $conn->prepare("UPDATE reservation_reschedules SET old_console_id = NULL WHERE old_console_id = ?");
    $stmt->bind_param("i", $console_id);
    $stmt->execute();

    // 4. Perform the deletion
    $stmt = $conn->prepare("DELETE FROM consoles WHERE console_id = ?");
    $stmt->bind_param("i", $console_id);
    if ($stmt->execute()) {
        return [
            'success'      => true, 
            'console_name' => $cName, 
            'unit_number'  => $cUnit,
            'console_type' => $cType
        ];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
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

    // Resolve walk-in: empty / 0 user_id maps to the system walk-in user.
    if (!$user_id || $user_id <= 0) {
        $user_id = getWalkinUserId();
    }

    // Get the console's hourly rate from console_types (via FK)
    $stmt = $conn->prepare(
        "SELECT ct.hourly_rate FROM consoles c
         JOIN console_types ct ON c.console_type_id = ct.console_type_id
         WHERE c.console_id = ? AND c.status = 'available'"
    );
    $stmt->bind_param("i", $console_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Console is not available'];
    }

    $rate = $result->fetch_assoc()['hourly_rate'];

    $conn->begin_transaction();
    try {
        // Apply bonus: convert paid minutes → total play minutes (paid + free bonus)
        // This is the ONLY place that applies the bonus — all callers pass paid minutes.
        $stored_minutes = null;
        if ($rental_mode === 'hourly' && $planned_minutes !== null) {
            $stored_minutes = paidToTotalMinutes((int)$planned_minutes);
        }

        // ── Reservation Conflict Check ───────────────────────────────────────
        $tz = new DateTimeZone('Asia/Manila');
        $now = new DateTime('now', $tz);
        $nowStr = $now->format('Y-m-d H:i:s');
        
        $resStmt = $conn->prepare(
            "SELECT reserved_date, reserved_time FROM reservations 
              WHERE console_id = ? AND status IN ('reserved', 'pending') 
                AND CONCAT(reserved_date, ' ', reserved_time) > ? 
              ORDER BY reserved_date ASC, reserved_time ASC LIMIT 1"
        );
        $resStmt->bind_param("is", $console_id, $nowStr);
        $resStmt->execute();
        $nextRes = $resStmt->get_result()->fetch_assoc();

        if ($nextRes) {
            $resDt = new DateTime($nextRes['reserved_date'] . ' ' . $nextRes['reserved_time'], $tz);
            $diff = $now->diff($resDt);
            $minsAway = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;

            if ($rental_mode === 'open_time' || $rental_mode === 'unlimited') {
                $conn->rollback();
                return ['success' => false, 'message' => 'Cannot start Open Time or Unlimited session. This console has an upcoming reservation in ' . $minsAway . ' minutes.'];
            }

            if ($stored_minutes !== null && $stored_minutes > $minsAway) {
                $conn->rollback();
                return ['success' => false, 'message' => 'Session duration exceeds the available time window before the next reservation.'];
            }
        }
        // ───────────────────────────────────────────────────────────────────────

        // Create session
        $stmt = $conn->prepare(
            "INSERT INTO gaming_sessions (user_id, console_id, rental_mode, planned_minutes, hourly_rate, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisidi", $user_id, $console_id, $rental_mode, $stored_minutes, $rate, $created_by);
        $stmt->execute();
        $session_id = $conn->insert_id;

        // Mark console as in use
        updateConsoleStatus($console_id, 'in_use');

        // Record upfront payment if provided
        if ($tendered !== null && $payment_method !== null) {
            $session_cost = computeRentalFee($rental_mode, $stored_minutes ?? 0, $rate, 300, $stored_minutes);
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
    $duration = (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 60);

    resyncOpenTimeControllerRentalExtras($session_id, $duration);

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

        // ── Restore controller if one was rented for this session ──────────
        $checkCtrl = $conn->prepare(
            "SELECT description FROM additional_requests
             WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'"
        );
        $checkCtrl->bind_param('i', $session_id);
        $checkCtrl->execute();
        $ctrlReqs = $checkCtrl->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($ctrlReqs as $req) {
            if (preg_match('/ID(?:s)?:\s*([\d, ]+)/', $req['description'], $matches)) {
                $idsRaw = explode(',', $matches[1]);
                foreach ($idsRaw as $idRaw) {
                    $cid = (int)trim($idRaw);
                    if ($cid > 0) {
                        $conn->query("UPDATE controllers SET status = 'available' WHERE controller_id = $cid");
                    }
                }
            } else {
                // Fallback for old sessions without ID in description
                $resCtrl = $conn->query(
                    "SELECT c.controller_id 
                     FROM controllers c
                     WHERE c.status = 'in_use'
                     ORDER BY c.unit_number ASC LIMIT 1"
                );
                if ($resCtrl && $resCtrl->num_rows > 0) {
                    $cid = (int)$resCtrl->fetch_assoc()['controller_id'];
                    $conn->query("UPDATE controllers SET status = 'available' WHERE controller_id = $cid");
                }
            }
        }
        // ──────────────────────────────────────────────────────────────────

        $conn->commit();
        return ['success' => true, 'duration_minutes' => $duration, 'total_cost' => $total_cost];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================================
// SESSION EXTENSION FUNCTIONS
// ============================================================================

/**
 * Directly extend an active session (staff action — no approval step).
 *
 * - For 'hourly'    : bumps planned_minutes + extended_minutes, bills extra_cost now.
 * - For 'open_time' : only bumps extended_minutes (billing continues live at end).
 * - For 'unlimited' : bumps extended_minutes, no extra charge.
 *
 * Records a transaction for the extension payment.
 *
 * @return array ['success', 'extra_cost', 'extension_id'] | ['success'=>false, 'message']
 */
function extendSession($session_id, $extra_minutes, $payment_method, $processed_by, $tendered = null) {
    global $conn;

    if ($extra_minutes <= 0) {
        return ['success' => false, 'message' => 'Extra minutes must be greater than zero.'];
    }

    // Fetch active session
    $stmt = $conn->prepare(
        "SELECT gs.session_id, gs.user_id, gs.rental_mode, gs.planned_minutes,
                gs.extended_minutes, gs.hourly_rate,
                s.setting_value AS unlimited_rate
           FROM gaming_sessions gs
           LEFT JOIN system_settings s ON s.setting_key = 'unlimited_rate'
          WHERE gs.session_id = ? AND gs.status = 'active'"
    );
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();

    if (!$session) {
        return ['success' => false, 'message' => 'Session not found or not active.'];
    }

    $rental_mode  = $session['rental_mode'];
    $extra_cost   = 0.0;

    // Extensions bill at straight ₱80/hr — no session-start minimum.
    // (The ₱50 for 30 min only applies when starting a new session.)
    if ($rental_mode === 'hourly') {
        $extra_cost = round(($extra_minutes / 60) * (float)$session['hourly_rate'], 2);
    } elseif ($rental_mode === 'open_time') {
        $extra_cost = 0.0;   // billed live at session end
    } elseif ($rental_mode === 'unlimited') {
        $extra_cost = 0.0;   // flat rate already paid
    }

    $conn->begin_transaction();
    try {
        // Insert approved extension record
        $stmt = $conn->prepare(
            "INSERT INTO session_extensions
                (session_id, requested_by, approved_by, extra_minutes, extra_cost,
                 payment_method, status, note, resolved_at)
             VALUES (?, ?, ?, ?, ?, ?, 'approved', 'Direct extension by staff', NOW())"
        );
        $stmt->bind_param('iiidss', $session_id, $processed_by, $processed_by,
                          $extra_minutes, $extra_cost, $payment_method);
        $stmt->execute();
        $extension_id = $conn->insert_id;

        // Update session: bump planned_minutes (hourly) and always bump extended_minutes
        if ($rental_mode === 'hourly') {
            // Apply bonus to the extension.
            // planned_minutes already stores total play time (paid + bonus).
            // Back-calculate current paid time from DB total, then add new paid
            // and recompute the new bonus delta.
            $rules    = getPricingRules();
            $bp       = $rules['bonus_paid_minutes'];
            $bf       = $rules['bonus_free_minutes'];

            // Reverse: current paid = current total - current bonus
            // We know: total = paid + floor(paid/bp)*bf
            // For typical values: approximate paid from total
            $cur_total  = (int)($session['planned_minutes'] ?? 0);
            // Iterate to find exact paid (handles edge where multiple cycles apply)
            $cur_paid   = max(0, $cur_total);  // start guess
            for ($p = $cur_total; $p >= 0; $p -= $bf) {
                if ($p + calcBonusMinutes($p, $rules) === $cur_total) { $cur_paid = $p; break; }
            }

            $new_paid   = $cur_paid + $extra_minutes;
            $new_total  = paidToTotalMinutes($new_paid, $rules);
            $total_add  = $new_total - $cur_total;  // net minutes to add (paid + bonus delta)
            $bonus_delta = $total_add - $extra_minutes;

            $stmt = $conn->prepare(
                "UPDATE gaming_sessions
                    SET planned_minutes  = COALESCE(planned_minutes, 0) + ?,
                        extended_minutes = extended_minutes + ?
                  WHERE session_id = ?"
            );
            $stmt->bind_param('iii', $total_add, $extra_minutes, $session_id);
        } else {
            $total_add   = $extra_minutes;
            $bonus_delta = 0;
            $stmt = $conn->prepare(
                "UPDATE gaming_sessions SET extended_minutes = extended_minutes + ?
                  WHERE session_id = ?"
            );
            $stmt->bind_param('ii', $extra_minutes, $session_id);
        }
        $stmt->execute();

        // Record transaction if there's a cost to collect
        if ($extra_cost > 0) {
            $shortfall = ($tendered !== null && $tendered < $extra_cost)
                ? $extra_cost - $tendered : null;
            $actualPaid = ($tendered !== null) ? min((float)$tendered, $extra_cost) : $extra_cost;
            $note = 'Extension +' . $extra_minutes . ' min via staff (Extension #' . $extension_id . ')';
            recordTransaction(
                $session_id, $session['user_id'], $actualPaid,
                $payment_method, $processed_by,
                $tendered, $shortfall, $note
            );
        }

        $conn->commit();
        return [
            'success'      => true,
            'extra_cost'   => $extra_cost,
            'extension_id' => $extension_id,
            'bonus_earned' => $bonus_delta ?? 0,   // extra free minutes gained
            'total_added'  => $total_add  ?? $extra_minutes,
        ];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Customer requests a session extension (creates a 'pending' record).
 * No payment collected yet — staff collects on approval.
 *
 * @return array ['success', 'extension_id', 'estimated_cost'] | ['success'=>false, 'message']
 */
function requestExtension($session_id, $user_id, $extra_minutes) {
    global $conn;

    if ($extra_minutes <= 0) {
        return ['success' => false, 'message' => 'Extra minutes must be greater than zero.'];
    }

    // Confirm session is active AND belongs to this user
    $stmt = $conn->prepare(
        "SELECT session_id, rental_mode, hourly_rate FROM gaming_sessions
          WHERE session_id = ? AND user_id = ? AND status = 'active'"
    );
    $stmt->bind_param('ii', $session_id, $user_id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();

    if (!$session) {
        return ['success' => false, 'message' => 'Active session not found.'];
    }

    // Check no other pending extension from this user on this session
    $stmtChk = $conn->prepare(
        "SELECT extension_id FROM session_extensions
          WHERE session_id = ? AND status = 'pending' LIMIT 1"
    );
    $stmtChk->bind_param('i', $session_id);
    $stmtChk->execute();
    if ($stmtChk->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'You already have a pending extension request. Please wait for staff to approve it.'];
    }

    $rental_mode    = $session['rental_mode'];
    // Use straight ₱80/hr for extension estimates (no session-start minimum)
    $estimated_cost = ($rental_mode === 'hourly')
        ? round(($extra_minutes / 60) * (float)$session['hourly_rate'], 2)
        : 0.0;

    $stmt = $conn->prepare(
        "INSERT INTO session_extensions
            (session_id, requested_by, extra_minutes, extra_cost, status)
         VALUES (?, ?, ?, ?, 'pending')"
    );
    $stmt->bind_param('iiid', $session_id, $user_id, $extra_minutes, $estimated_cost);

    if ($stmt->execute()) {
        return [
            'success'        => true,
            'extension_id'   => $conn->insert_id,
            'estimated_cost' => $estimated_cost,
        ];
    }
    return ['success' => false, 'message' => 'Failed to save extension request.'];
}

/**
 * Staff approves a pending extension request.
 * Internally calls extendSession() to apply the time + record the transaction.
 *
 * @return array ['success', 'extra_cost', 'extension_id'] | ['success'=>false, 'message']
 */
function approveExtension($extension_id, $approved_by, $payment_method, $tendered = null) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT * FROM session_extensions WHERE extension_id = ? AND status = 'pending'"
    );
    $stmt->bind_param('i', $extension_id);
    $stmt->execute();
    $ext = $stmt->get_result()->fetch_assoc();

    if (!$ext) {
        return ['success' => false, 'message' => 'Pending extension not found.'];
    }

    // Mark extension as approved before calling extendSession
    $upd = $conn->prepare(
        "UPDATE session_extensions
            SET status = 'approved', approved_by = ?, payment_method = ?, resolved_at = NOW()
          WHERE extension_id = ?"
    );
    $upd->bind_param('isi', $approved_by, $payment_method, $extension_id);
    $upd->execute();

    // Apply the time + record payment
    return extendSession($ext['session_id'], $ext['extra_minutes'], $payment_method, $approved_by, $tendered);
}

/**
 * Staff denies a pending extension request.
 *
 * @return array ['success'] | ['success'=>false, 'message']
 */
function denyExtension($extension_id, $denied_by, $note = null) {
    global $conn;

    $stmt = $conn->prepare(
        "UPDATE session_extensions
            SET status = 'denied', approved_by = ?, note = ?, resolved_at = NOW()
          WHERE extension_id = ? AND status = 'pending'"
    );
    $stmt->bind_param('isi', $denied_by, $note, $extension_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Pending extension not found.'];
}

/**
 * Used for both Open Time billing and Hourly overtime calculation.

 *
 * Brackets: 1–4 min = ₱0 (grace), 5–19 min = ₱20,
 *           20–34 min = ₱40, 35–49 min = ₱60, 50–59 min = ₱80.
 */
function computePartialPeriodCost($minutes) {
    if ($minutes <= 0) return 0;
    
    $rules = getPricingRules();
    $tiers = $rules['pricing_tiers'] ?? [];
    
    // Sort just in case, but getPricingRules already orders by min_minutes
    foreach ($tiers as $tier) {
        if ($minutes >= $tier['min'] && $minutes <= $tier['max']) {
            return $tier['charge'];
        }
    }
    
    // Fallback to hourly rate if no tier matches (shouldn't happen with 0-59 covered)
    return $rules['hourly_rate'];
}

/**
 * Staff: end ONE specific controller's rental early on an active session.
 *
 * If the session has two controllers on a single additional_requests row,
 * the row is split:
 *   - ended controller → frozen [FIX:id:cost] at prorated elapsed time
 *   - remaining controller(s) → kept in the original row (cost adjusted)
 *
 * Returns refund_amount = original_cost_for_this_controller - prorated_cost.
 *
 * @return array{success:bool, message?:string, refund_amount?:float,
 *               prorated_cost?:float, original_cost?:float, elapsed_minutes?:int}
 */
function endSingleControllerEarly(int $session_id, int $controller_id, int $staff_user_id): array {
    global $conn;

    if ($session_id <= 0 || $controller_id <= 0) {
        return ['success' => false, 'message' => 'Invalid session or controller.'];
    }

    $conn->begin_transaction();
    try {
        // ── 1. Fetch active session ───────────────────────────────────────
        $s = $conn->prepare(
            "SELECT session_id, start_time FROM gaming_sessions WHERE session_id = ? AND status = 'active'"
        );
        $s->bind_param('i', $session_id);
        $s->execute();
        $session = $s->get_result()->fetch_assoc();
        if (!$session) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Session is not active.'];
        }

        $start   = new DateTime($session['start_time'], new DateTimeZone('Asia/Manila'));
        $now     = new DateTime('now',                  new DateTimeZone('Asia/Manila'));
        $elapsed = max(0, (int) floor(($now->getTimestamp() - $start->getTimestamp()) / 60));

        // ── 2. Find the controller_rental row containing this controller ──
        $ar = $conn->prepare(
            "SELECT request_id, description, extra_cost
               FROM additional_requests
              WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'"
        );
        $ar->bind_param('i', $session_id);
        $ar->execute();
        $rows = $ar->get_result()->fetch_all(MYSQLI_ASSOC);

        $targetRow = null;
        $allIds    = [];
        foreach ($rows as $row) {
            if (preg_match('/ID(?:s)?:\s*([\d,\s]+)/', $row['description'], $m)) {
                $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($m[1])))));
                if (in_array($controller_id, $ids, true)) {
                    $targetRow = $row;
                    $allIds    = $ids;
                    break;
                }
            }
        }
        if (!$targetRow) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Controller #' . $controller_id . ' not found in any active rental row for this session.'];
        }

        // ── 3. Fetch controller hourly rate ───────────────────────────────
        $cr = $conn->prepare('SELECT hourly_rate, unit_number FROM controllers WHERE controller_id = ?');
        $cr->bind_param('i', $controller_id);
        $cr->execute();
        $ctrlRow = $cr->get_result()->fetch_assoc();
        if (!$ctrlRow || (float)$ctrlRow['hourly_rate'] <= 0) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Controller rate not found.'];
        }
        $rate       = (float) $ctrlRow['hourly_rate'];
        $ctrlLabel  = 'Controller #' . $controller_id . ' (' . ($ctrlRow['unit_number'] ?? 'C' . $controller_id) . ')';

        // ── 4. Calculate original cost for THIS controller ────────────────
        $desc        = (string) $targetRow['description'];
        $totalCost   = (float) $targetRow['extra_cost'];
        $numCtrls    = count($allIds);

        // Parse [FIX:id:cost|...] to find this controller's original amount
        $fixParts = [];
        if (preg_match('/\[FIX:([^\]]+)\]/', $desc, $fm)) {
            foreach (explode('|', $fm[1]) as $pair) {
                $pair = trim($pair);
                if ($pair === '') continue;
                $parts = explode(':', $pair, 2);
                if (count($parts) === 2) {
                    $fixParts[(int)$parts[0]] = (float)$parts[1];
                }
            }
        }

        // Parse [OT_IDS:...] and [Mins:...] for open-time or minute-based billing
        $otIds = [];
        if (preg_match('/\[OT_IDS:([^\]]*)\]/', $desc, $om)) {
            $s2 = trim((string)$om[1]);
            if ($s2 !== '') {
                $otIds = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $s2))));
            }
        } elseif (strpos($desc, 'Open Time run-the-clock') !== false) {
            // Legacy open-time: all IDs are open-time
            $otIds = $allIds;
        }
        $minsMap = [];
        if (preg_match('/\[Mins:\s*([\d,\s]+)\]/', $desc, $mm)) {
            $minsMap = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($mm[1])))));
        }

        // Determine original cost for this controller
        $ctrlIdx      = array_search($controller_id, $allIds, true);
        $originalCost = 0.0;
        if (isset($fixParts[$controller_id])) {
            $originalCost = $fixParts[$controller_id];
        } elseif (in_array($controller_id, $otIds, true)) {
            // Open-time: original cost was the running total at elapsed — just use prorated as original
            $originalCost = computeControllerOpenTimeFeeForDuration($elapsed, $rate); // will net 0 refund
        } elseif ($minsMap !== [] && isset($minsMap[$ctrlIdx])) {
            $originalCost = round($rate * ($minsMap[$ctrlIdx] / 60), 2);
        } elseif ($numCtrls === 1 && $totalCost > 0) {
            $originalCost = $totalCost;
        } else {
            // Fallback: split evenly
            $originalCost = round($totalCost / $numCtrls, 2);
        }

        // ── 5. Prorate cost to elapsed time (₱10/30m rule) ────────────────
        $proratedCost = computeControllerOpenTimeFeeForDuration($elapsed, $rate);

        $refundAmt = max(0.0, round($originalCost - $proratedCost, 2));

        // ── 6. Update the additional_requests row ─────────────────────────
        $remainingIds = array_values(array_filter($allIds, fn($id) => $id !== $controller_id));

        if ($remainingIds === []) {
            // Only controller on this row — freeze entire row at elapsed
            $newDesc  = 'Controller rental (IDs: ' . $controller_id . ') [FIX:' . $controller_id . ':' . number_format($proratedCost, 2, '.', '') . '] [ENDED]';
            $newCost  = $proratedCost;
            $upd = $conn->prepare('UPDATE additional_requests SET description = ?, extra_cost = ? WHERE request_id = ?');
            $rid = (int) $targetRow['request_id'];
            $upd->bind_param('sdi', $newDesc, $newCost, $rid);
            $upd->execute();
        } else {
            // Multiple controllers — freeze ended one; keep rest in original row
            // Ended controller: new row with frozen cost
            $endedDesc = 'Controller rental (IDs: ' . $controller_id . ') [FIX:' . $controller_id . ':' . number_format($proratedCost, 2, '.', '') . '] [ENDED]';
            $ins = $conn->prepare(
                "INSERT INTO additional_requests (session_id, request_type, description, extra_cost, status, created_at)
                 VALUES (?, 'controller_rental', ?, ?, 'approved', NOW())"
            );
            $ins->bind_param('isd', $session_id, $endedDesc, $proratedCost);
            $ins->execute();

            // Rebuild remaining controllers row: rebuild fix parts minus ended controller
            $remainingFixParts = $fixParts;
            unset($remainingFixParts[$controller_id]);

            $remainingOtIds = array_values(array_filter($otIds, fn($id) => $id !== $controller_id));
            $remainingMinsMap = $minsMap;
            if (isset($remainingMinsMap[$ctrlIdx])) {
                array_splice($remainingMinsMap, $ctrlIdx, 1);
            }

            // Build new fix-parts string for remaining controllers
            $newPairs = [];
            foreach ($remainingIds as $i => $rid2) {
                if (isset($remainingFixParts[$rid2])) {
                    $newPairs[] = $rid2 . ':' . number_format($remainingFixParts[$rid2], 2, '.', '');
                } elseif (in_array($rid2, $remainingOtIds, true)) {
                    // stays as open-time — no fix part yet
                } elseif (isset($remainingMinsMap[$i])) {
                    $amt = round($rate * ($remainingMinsMap[$i] / 60), 2);
                    $newPairs[] = $rid2 . ':' . number_format($amt, 2, '.', '');
                }
            }

            $newRemDesc = 'Controller rental (IDs: ' . implode(', ', $remainingIds) . ')';
            if ($newPairs !== []) {
                $newRemDesc .= ' [FIX:' . implode('|', $newPairs) . ']';
            }
            if ($remainingOtIds !== []) {
                $newRemDesc .= ' [OT_IDS:' . implode(',', $remainingOtIds) . ']';
            }
            if ($remainingMinsMap !== [] && $newPairs === []) {
                $newRemDesc .= ' [Mins:' . implode(',', $remainingMinsMap) . ']';
            }

            // Recalculate remaining row cost
            $newRemCost = round($totalCost - $originalCost, 2);
            $upd2 = $conn->prepare('UPDATE additional_requests SET description = ?, extra_cost = ? WHERE request_id = ?');
            $origRid = (int) $targetRow['request_id'];
            $upd2->bind_param('sdi', $newRemDesc, $newRemCost, $origRid);
            $upd2->execute();
        }

        // ── 7. Mark controller as available ───────────────────────────────
        $cu = $conn->prepare("UPDATE controllers SET status = 'available' WHERE controller_id = ?");
        $cu->bind_param('i', $controller_id);
        $cu->execute();

        $conn->commit();

        logActivity(
            $staff_user_id,
            'Controller rental',
            "Ended {$ctrlLabel} early on session #{$session_id} (elapsed {$elapsed} min). " .
            "Prorated: ₱" . number_format($proratedCost, 2) . " / Original: ₱" . number_format($originalCost, 2) . " / Refund: ₱" . number_format($refundAmt, 2) . "."
        );

        return [
            'success'         => true,
            'message'         => $ctrlLabel . ' rental ended. Prorated to elapsed time.',
            'refund_amount'   => $refundAmt,
            'prorated_cost'   => $proratedCost,
            'original_cost'   => $originalCost,
            'elapsed_minutes' => $elapsed,
            'controller_label'=> $ctrlLabel,
        ];

    } catch (Throwable $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Compute cost for any duration using DB-driven bracket billing with
 * a FREE bonus every N paid minutes.
 *
 * Reads bonus_paid_minutes and bonus_free_minutes from system_settings.
 * Cycle = (bonus_paid_minutes + bonus_free_minutes) total minutes.
 *
 * Examples (default 120/30 rule):
 *   2:00 paid = ₱160,  2:30 (free zone) = ₱160,  4:00 paid = ₱320
 */
function computeTimedCost(int $minutes): float {
    $minutes = max(0, $minutes);
    if ($minutes === 0) return 0.0;

    $rules    = getPricingRules();
    $bp       = $rules['bonus_paid_minutes'];   // e.g. 120
    $bf       = $rules['bonus_free_minutes'];   // e.g. 30
    $rate     = $rules['hourly_rate'];          // e.g. 80
    $cyclePay = $bp / 60 * $rate;              // e.g. ₱160 per cycle
    $cycleLen = $bp + $bf;                     // e.g. 150 total min/cycle

    $fullCycles = (int) floor($minutes / $cycleLen);
    $cost       = $fullCycles * $cyclePay;
    $remainder  = $minutes % $cycleLen;

    if ($remainder > $bp) {
        // Inside the free window — charge the full paid block
        $cost += $cyclePay;
    } else {
        // Inside the paid window — hourly bracket billing
        $cost += (int) floor($remainder / 60) * $rate + computePartialPeriodCost($remainder % 60);
    }

    return (float) $cost;
}

/**
 * Controller add-on for Open Time: same bonus/bracket *timing* as computeTimedCost with *amounts*
 * driven by the controller's hourly rate (matches admin.js _controllerOpenTimeFee).
 */
function computeControllerOpenTimeFeeForDuration(int $total_minutes, float $controller_hourly_rate): float {
    if ($total_minutes < 5) return 0.0;
    return (float)(floor(($total_minutes + 25) / 30) * 10);
}

/**
 * Controller rows tagged as Open Time (per-controller) need extra_cost set from elapsed session minutes.
 * Fixed upfront portions ([FIX:...]) are kept; OT portion uses the same bonus/bracket clock as console.
 * Runs whenever a session end duration is known (any console rental_mode if OT controller lines exist).
 */
function resyncOpenTimeControllerRentalExtras(int $session_id, int $duration_minutes): void {
    global $conn;
    if ($session_id <= 0 || $duration_minutes < 0) {
        return;
    }

    $stmt = $conn->prepare(
        "SELECT request_id, description FROM additional_requests
         WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'"
    );
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $row) {
        $desc = (string) $row['description'];

        $fixed_total = 0.0;
        if (preg_match('/\[FIX:([^\]]+)\]/', $desc, $fm)) {
            foreach (explode('|', $fm[1]) as $pair) {
                $pair = trim($pair);
                if ($pair === '') {
                    continue;
                }
                $parts = explode(':', $pair, 2);
                if (count($parts) === 2) {
                    $fixed_total += (float) $parts[1];
                }
            }
        }

        $ot_ids = [];
        if (preg_match('/\[OT_IDS:([^\]]*)\]/', $desc, $om)) {
            $s = trim((string) $om[1]);
            if ($s !== '') {
                $ot_ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $s))));
            }
        } elseif (strpos($desc, 'Open Time run-the-clock') !== false && preg_match('/IDs:\s*([\d,\s]+)/', $desc, $im)) {
            // Legacy: entire line was Open Time; upfront was 0 until session end
            $ot_ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($im[1])))));
            $fixed_total = 0.0;
        }

        if ($ot_ids === []) {
            continue;
        }

        $ot_sum = 0.0;
        foreach ($ot_ids as $cid) {
            if ($cid <= 0) {
                continue;
            }
            $rStmt = $conn->prepare('SELECT hourly_rate FROM controllers WHERE controller_id = ?');
            $rStmt->bind_param('i', $cid);
            $rStmt->execute();
            $rateRow = $rStmt->get_result()->fetch_assoc();
            if (!$rateRow) {
                continue;
            }
            $ot_sum += computeControllerOpenTimeFeeForDuration($duration_minutes, (float) $rateRow['hourly_rate']);
        }

        $new_extra = round($fixed_total + $ot_sum, 2);
        $upd = $conn->prepare('UPDATE additional_requests SET extra_cost = ? WHERE request_id = ?');
        $rid = (int) $row['request_id'];
        $upd->bind_param('di', $new_extra, $rid);
        $upd->execute();
    }
}

/**
 * Rebuild a controller_rental line for early hardware return: charge only through elapsed
 * session minutes, freeze open-time style fees at elapsed, remove [OT_IDS] so
 * resyncOpenTimeControllerRentalExtras() does not add again at session end.
 *
 * @return array{description:string,extra_cost:float,controller_ids:int[]}|null
 */
function rebuildControllerRentalLineForEarlyEnd(string $description, float $previous_cost, int $elapsed_minutes, mysqli $conn): ?array {
    $elapsed_minutes = max(0, $elapsed_minutes);
    $desc            = trim($description);

    $ids = [];
    if (preg_match('/ID(?:s)?:\s*([\d,\s]+)/', $desc, $m)) {
        $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($m[1])))));
    }
    if ($ids === []) {
        return null;
    }

    $fix_parts = [];
    if (preg_match('/\[FIX:([^\]]+)\]/', $desc, $fm)) {
        foreach (explode('|', $fm[1]) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $fix_parts[(int) $parts[0]] = (float) $parts[1];
            }
        }
    }

    $ot_ids = [];
    if (preg_match('/\[OT_IDS:([^\]]*)\]/', $desc, $om)) {
        $s = trim((string) $om[1]);
        if ($s !== '') {
            $ot_ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $s))));
        }
    }

    $mins_map = [];
    if (preg_match('/\[Mins:\s*([\d,\s]+)\]/', $desc, $mm)) {
        $mins_map = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($mm[1])))));
    }

    if ($ot_ids === [] && strpos($desc, 'Open Time run-the-clock') !== false) {
        foreach ($ids as $cid) {
            if (!isset($fix_parts[$cid])) {
                $ot_ids[] = $cid;
            }
        }
        $ot_ids = array_values(array_unique($ot_ids));
    }

    $per_controller = [];

    foreach ($ids as $i => $cid) {
        if ($cid <= 0) {
            continue;
        }
        $rStmt = $conn->prepare('SELECT hourly_rate FROM controllers WHERE controller_id = ?');
        $rStmt->bind_param('i', $cid);
        $rStmt->execute();
        $rateRow = $rStmt->get_result()->fetch_assoc();
        if (!$rateRow) {
            return null;
        }
        $rate = (float) ($rateRow['hourly_rate'] ?? 0);
        if ($rate <= 0.00001) {
            return null;
        }

        // Standardized ₱10/30m rule for all returns
        $amt = computeControllerOpenTimeFeeForDuration($elapsed_minutes, $rate);
        $per_controller[$cid] = $amt;
    }

    if (count($per_controller) !== count($ids)) {
        return null;
    }

    $pairs = [];
    foreach ($ids as $cid) {
        $pairs[] = $cid . ':' . number_format($per_controller[$cid], 2, '.', '');
    }

    $new_desc  = 'Controller rental (IDs: ' . implode(', ', $ids) . ') [FIX:' . implode('|', $pairs) . ']';
    $new_total = round(array_sum($per_controller), 2);

    return [
        'description'     => $new_desc,
        'extra_cost'      => $new_total,
        'controller_ids'  => $ids,
    ];
}

/**
 * Staff: end controller add-on early on an active session — re-rate and release hardware.
 *
 * @return array{success:bool,message?:string,extras_total?:float,elapsed_minutes?:int}
 */
function endControllerRentalEarly(int $session_id, int $staff_user_id): array {
    global $conn;

    if ($session_id <= 0) {
        return ['success' => false, 'message' => 'Invalid session.'];
    }

    $conn->begin_transaction();
    try {
        $sessStmt = $conn->prepare(
            "SELECT session_id, start_time, status FROM gaming_sessions WHERE session_id = ? AND status = 'active'"
        );
        $sessStmt->bind_param('i', $session_id);
        $sessStmt->execute();
        $session = $sessStmt->get_result()->fetch_assoc();

        if (!$session) {
            $conn->rollback();

            return ['success' => false, 'message' => 'Session is not active.'];
        }

        $start   = new DateTime($session['start_time'], new DateTimeZone('Asia/Manila'));
        $now     = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $elapsed = max(0, (int) floor(($now->getTimestamp() - $start->getTimestamp()) / 60));

        $arStmt = $conn->prepare(
            "SELECT request_id, description, extra_cost FROM additional_requests
              WHERE session_id = ? AND request_type = 'controller_rental' AND status = 'approved'"
        );
        $arStmt->bind_param('i', $session_id);
        $arStmt->execute();
        $rows = $arStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($rows === []) {
            $conn->rollback();

            return ['success' => false, 'message' => 'No controller rental on this session.'];
        }

        foreach ($rows as $row) {
            $reb = rebuildControllerRentalLineForEarlyEnd(
                (string) $row['description'],
                (float) $row['extra_cost'],
                $elapsed,
                $conn
            );
            if ($reb === null) {
                $conn->rollback();

                return ['success' => false, 'message' => 'Could not parse controller rental line. Update the record or end the session normally.'];
            }

            $upd = $conn->prepare('UPDATE additional_requests SET description = ?, extra_cost = ? WHERE request_id = ?');
            $rid = (int) $row['request_id'];
            $upd->bind_param('sdi', $reb['description'], $reb['extra_cost'], $rid);
            $upd->execute();

            foreach ($reb['controller_ids'] as $cid) {
                if ($cid <= 0) {
                    continue;
                }
                $cu = $conn->prepare("UPDATE controllers SET status = 'available' WHERE controller_id = ?");
                $cu->bind_param('i', $cid);
                $cu->execute();
            }
        }

        $conn->commit();

        $sumStmt = $conn->prepare(
            'SELECT COALESCE(SUM(extra_cost), 0) AS t FROM additional_requests WHERE session_id = ? AND status = \'approved\''
        );
        $sumStmt->bind_param('i', $session_id);
        $sumStmt->execute();
        $new_tot = (float) $sumStmt->get_result()->fetch_assoc()['t'];

        logActivity(
            $staff_user_id,
            'Controller rental',
            "Ended controller rental early for session #{$session_id} (elapsed {$elapsed} min). Approved extras now ₱" . number_format($new_tot, 2) . '.'
        );

        return [
            'success'          => true,
            'message'          => 'Controller rental ended early. Fees recalculated to time used; units marked available.',
            'extras_total'     => $new_tot,
            'elapsed_minutes'  => $elapsed,
        ];
    } catch (Throwable $e) {
        $conn->rollback();

        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Compute the cost for an INITIAL session start (incorporates the ₱50 min charge).
 * Applies the ₱10 surcharge over the standard ₱80/hr (₱40/30m) rate for the first 30m.
 */
function computeInitialSessionCost(int $total_minutes): float {
    if ($total_minutes <= 0) return 0.0;
    $rules = getPricingRules();
    $standardCost = computeTimedCost($total_minutes);
    // We ensure it's at least the session_min_charge (₱50), but don't arbitrarily add ₱10 for > 30 min
    return (float) max($rules['session_min_charge'], $standardCost);
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
    $rules = getPricingRules();
    switch ($rental_mode) {
        case 'hourly':
            if ($planned_minutes !== null && $planned_minutes > 0) {
                // Overtime beyond booked time
                $overtime = $duration_minutes - $planned_minutes;

                if ($overtime <= 0) {
                    // Ended on time or early — charge for ACTUAL time used, not planned duration.
                    // This prevents a 1h45m booking ended after 2 minutes from billing ₱140.
                    return computeTimedCost($duration_minutes);
                }

                // computeTimedCost handles bonus-free cycles; raw multiplication
                // incorrectly bills free bonus minutes at full rate.
                $base_cost = computeInitialSessionCost((int)$planned_minutes);
                return $base_cost + computeTimedCost($overtime);
            }
            // No pre-booking data: fall back to open-time (initial session) pricing
            return computeInitialSessionCost($duration_minutes);


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
    $sql = "SELECT gs.*, u.full_name AS customer_name, u.email AS customer_email, c.console_name, ct.type_name AS console_type, c.unit_number,
                   gs.source_reservation_id,
                   COALESCE(r.downpayment_amount, 0) AS reservation_downpayment,
                   COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount > 0), 0) AS upfront_paid,
                   COALESCE((SELECT SUM(ABS(t.amount)) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount < 0), 0) AS refunded_amount
            FROM gaming_sessions gs
            JOIN users u ON gs.user_id = u.user_id
            JOIN consoles c ON gs.console_id = c.console_id
            LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id
            LEFT JOIN reservations r ON r.reservation_id = gs.source_reservation_id
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
        "SELECT gs.*, c.console_name, ct.type_name AS console_type
         FROM gaming_sessions gs
         JOIN consoles c      ON gs.console_id    = c.console_id
         LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id
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

    // Check if registration is open
    $stmt = $conn->prepare(
        "SELECT t.tournament_id
         FROM tournaments t WHERE t.tournament_id = ? AND t.status = 'upcoming'"
    );
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Tournament not found or registration closed'];
    }

    // Register — set registered_by = user_id for self-registration (customer registered themselves)
    $stmt = $conn->prepare("INSERT INTO tournament_participants (tournament_id, user_id, registered_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $tournament_id, $user_id, $user_id);
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
    [$start, $end] = getOperatingDayBounds($date);
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total_sessions,
                SUM(total_cost) AS total_revenue,
                AVG(duration_minutes) AS avg_duration,
                SUM(CASE WHEN rental_mode = 'hourly' THEN 1 ELSE 0 END) AS hourly_sessions,
                SUM(CASE WHEN rental_mode = 'open_time' THEN 1 ELSE 0 END) AS open_time_sessions,
                SUM(CASE WHEN rental_mode = 'unlimited' THEN 1 ELSE 0 END) AS unlimited_sessions
         FROM gaming_sessions
         WHERE start_time BETWEEN ? AND ? AND status = 'completed'"
    );
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get console usage statistics.
 */
function getConsoleUsageReport($date_from, $date_to) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT c.console_name, ct.type_name AS console_type, c.unit_number,
                COUNT(gs.session_id) AS total_sessions,
                COALESCE(SUM(gs.duration_minutes), 0) AS total_minutes,
                COALESCE(SUM(gs.total_cost), 0) AS total_revenue
         FROM consoles c
         LEFT JOIN console_types ct ON c.console_type_id = ct.console_type_id
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
    // Upsert — works whether the row already exists or not.
    $stmt = $conn->prepare(
        "INSERT INTO system_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->bind_param("ss", $key, $value);
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
    $downpayment_amount = 0.0, $downpayment_method = null,
    $preferred_unit_id = null,
    $payment_proof = null,   // uploaded GCash screenshot filename
    $controller_id = null,   // optional: selected controller FK
    $controller_id_2 = null, // optional: 2nd selected controller FK
    $controller_fee = 0.0    // fee snapshot from system_settings at booking time
) {
    global $conn;

    // ── One active reservation per user ──────────────────────────────────────
    // A user may not hold more than one pending/confirmed reservation at a time.
    // They must cancel the existing one before making a new booking.
    $activeCheck = $conn->prepare(
        "SELECT reservation_id, reserved_date, reserved_time
           FROM reservations
          WHERE user_id = ? AND status IN ('pending','reserved')
          LIMIT 1"
    );
    $activeCheck->bind_param('i', $user_id);
    $activeCheck->execute();
    $existing = $activeCheck->get_result()->fetch_assoc();
    if ($existing) {
        return [
            'success'            => false,
            'message'            => 'You already have an active reservation (Reservation #'
                                  . $existing['reservation_id'] . ' on '
                                  . date('M d, Y', strtotime($existing['reserved_date'])) . ' at '
                                  . date('g:i A', strtotime($existing['reserved_time']))
                                  . '). Please cancel it first before making a new booking.',
            'existing_id'        => $existing['reservation_id'],
        ];
    }

    $today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
    if ($reserved_date < $today) {
        return ['success' => false, 'message' => 'Cannot reserve a past date.'];
    }

    if (isDateBlocked($reserved_date)) {
        return ['success' => false, 'message' => 'The selected date is currently blocked for reservations. Please choose another date.'];
    }

    // ── Opening Hours Validation (12:00 PM - 12:00 AM) ────────────────────────
    $hour = (int)date('H', strtotime($reserved_time));
    if ($hour < 12) {
        return ['success' => false, 'message' => 'Reservations can only be made from 12:00 PM (noon) onwards.'];
    }
    // Last possible start time is 11:30 PM (23:30)
    if ($reserved_time > '23:30') {
        return ['success' => false, 'message' => 'Reservations must be no later than 11:30 PM.'];
    }


    // Payment proof is required — payment_proof_status starts as 'pending' (awaiting admin verify)
    $downpayment_paid  = 0;
    $preferred_unit_id = $preferred_unit_id ? (int)$preferred_unit_id : null;
    $proof_status      = $payment_proof ? 'pending' : null;
    $controller_id     = $controller_id ? (int)$controller_id : null;
    $controller_id_2   = $controller_id_2 ? (int)$controller_id_2 : null;
    $with_controller   = ($controller_id || $controller_id_2) ? 1 : 0;
    $controller_fee    = $with_controller ? (float)$controller_fee : 0.0;

    // Lookup console_type_id
    $ctQ = $conn->prepare("SELECT console_type_id FROM console_types WHERE type_name = ? AND is_archived = 0");
    $ctQ->bind_param('s', $console_type);
    $ctQ->execute();
    $ctRow = $ctQ->get_result()->fetch_assoc();
    if (!$ctRow) {
        return ['success' => false, 'message' => 'Invalid console type: ' . htmlspecialchars($console_type)];
    }
    $console_type_id = (int)$ctRow['console_type_id'];

    $stmt = $conn->prepare(
        "INSERT INTO reservations
            (user_id, console_id, console_type_id, rental_mode, planned_minutes, reserved_date, reserved_time,
             notes, with_controller, controller_id, controller_id_2, controller_fee,
             downpayment_amount, downpayment_method, downpayment_paid,
             payment_proof, payment_proof_status, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reserved', ?)"
    );
    $stmt->bind_param(
        'iiisisssiiiddsissi',
        $user_id, $preferred_unit_id, $console_type_id, $rental_mode, $planned_minutes,
        $reserved_date, $reserved_time, $notes,
        $with_controller, $controller_id, $controller_id_2, $controller_fee,
        $downpayment_amount, $downpayment_method, $downpayment_paid,
        $payment_proof, $proof_status,
        $user_id
    );



    if ($stmt->execute()) {
        $reservation_id = $conn->insert_id;

        // Record the downpayment as a transaction so it appears in financial reports
        if ($downpayment_amount > 0 && $downpayment_method !== null) {
            $ctrlNote = $with_controller ? ' (+₱' . number_format($controller_fee,2) . ' controller rental)' : '';
            recordTransaction(
                null,
                $user_id,
                $downpayment_amount,
                $downpayment_method,
                $user_id,
                $downpayment_amount,
                null,
                'Downpayment for reservation #' . $reservation_id . $ctrlNote
            );
        }

        return ['success' => true, 'reservation_id' => $reservation_id];
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
    if (!in_array($res['status'], ['pending', 'reserved'])) {
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
        // ── Handle Pre-Booked Controllers ──────────────────────────────────────
        // If the customer reserved extra controllers online, they must be converted
        // into additional_requests so the session billing system can pick them up.
        // It also marks those specific controllers as 'in_use'.
        if (!empty($res['with_controller']) && $res['with_controller'] == 1) {
            $ctrlFee = (float)($res['controller_fee'] ?? 0);
            
            $ctrlIds = [];
            if (!empty($res['controller_id'])) $ctrlIds[] = (int)$res['controller_id'];
            if (!empty($res['controller_id_2'])) $ctrlIds[] = (int)$res['controller_id_2'];

            $desc = 'Pre-booked controller rental fee';
            if (!empty($ctrlIds)) {
                $desc .= ' (IDs: ' . implode(', ', $ctrlIds) . ')';
            }

            if ($ctrlFee > 0) {
                // Record the cost in additional_requests
                $arStmt = $conn->prepare(
                    "INSERT INTO additional_requests (session_id, request_type, description, extra_cost, status)
                     VALUES (?, 'controller_rental', ?, ?, 'approved')"
                );
                $arStmt->bind_param('isd', $result['session_id'], $desc, $ctrlFee);
                $arStmt->execute();
            }

            // Mark reserved controllers as 'in_use'

            if (!empty($ctrlIds)) {
                $placeholders = implode(',', array_fill(0, count($ctrlIds), '?'));
                $cTypes = str_repeat('i', count($ctrlIds));
                $updCtrl = $conn->prepare("UPDATE controllers SET status = 'in_use' WHERE controller_id IN ($placeholders) AND status = 'available'");
                if ($updCtrl) {
                    $updCtrl->bind_param($cTypes, ...$ctrlIds);
                    $updCtrl->execute();
                }
            }
        }

        // ── Link the existing reservation downpayment transaction to the new session ──
        // createReservation() already recorded the downpayment with session_id = NULL.
        // Instead of adding a duplicate transaction, we UPDATE that existing row to
        // attach the real session_id and update the note — this keeps a single
        // clean ledger entry and makes refund calculations work correctly.
        if (!empty($res['downpayment_amount']) && (float)$res['downpayment_amount'] > 0) {
            $existingNoteMatch = 'Downpayment for reservation #' . $reservation_id . '%';
            $newNote           = 'Downpayment transferred from reservation #' . $reservation_id;

            // UPDATE the existing reservation downpayment transaction to link it to
            // the new session. This avoids double-recording and ensures refund
            // calculations (WHERE session_id = ?) correctly find the payment.
            $linkStmt = $conn->prepare(
                "UPDATE transactions
                    SET session_id   = ?,
                        processed_by = ?,
                        payment_note = ?
                  WHERE payment_note LIKE ?
                    AND user_id      = ?
                    AND session_id IS NULL
                  LIMIT 1"
            );
            $linkStmt->bind_param('iissi',
                $result['session_id'],
                $shopkeeper_id,
                $newNote,
                $existingNoteMatch,
                $res['user_id']
            );
            $linkStmt->execute();

            // Safety net: if no existing transaction was found (old reservation
            // created before this fix), insert one now so the refund system works.
            if ($linkStmt->affected_rows === 0) {
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
        }

        // ── Stamp the session with its source reservation for auditability ──
        // This allows the refund modal, reports, and billing to detect that this
        // session originated from a pre-paid reservation.
        $stampStmt = $conn->prepare(
            "UPDATE gaming_sessions SET source_reservation_id = ? WHERE session_id = ?"
        );
        $stampStmt->bind_param('ii', $reservation_id, $result['session_id']);
        $stampStmt->execute();

        // Mark reservation as converted
        updateReservationStatus($reservation_id, 'converted', $console_id);

        // ── Reset 3-strike cancellation counter (player honoured their booking) ──
        $reset = $conn->prepare(
            "UPDATE users SET consecutive_cancellations = 0, reservation_banned_until = NULL WHERE user_id = ?"
        );
        $reset->bind_param('i', $res['user_id']);
        $reset->execute();
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
            "SELECT r.*, ct.type_name AS console_type, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                    c.unit_number, c.console_name,
                    ctrl.unit_number AS ctrl_unit, ctrl_t.type_name AS ctrl_type,
                    ctrl2.unit_number AS ctrl2_unit, ctrl2_t.type_name AS ctrl2_type
               FROM reservations r
               JOIN users u ON r.user_id = u.user_id
               LEFT JOIN consoles c ON r.console_id = c.console_id
               LEFT JOIN console_types ct ON r.console_type_id = ct.console_type_id
               LEFT JOIN controllers ctrl ON r.controller_id = ctrl.controller_id
               LEFT JOIN controller_types ctrl_t ON ctrl.controller_type_id = ctrl_t.controller_type_id
               LEFT JOIN controllers ctrl2 ON r.controller_id_2 = ctrl2.controller_id
               LEFT JOIN controller_types ctrl2_t ON ctrl2.controller_type_id = ctrl2_t.controller_type_id
              WHERE r.reserved_date BETWEEN ? AND ?
                AND r.status IN ('pending','reserved')
              ORDER BY r.reserved_date ASC, r.reserved_time ASC"
        );
        $stmt->bind_param('ss', $today, $until);
    } else {
        // Show ALL future reservations (no upper bound)
        $stmt = $conn->prepare(
            "SELECT r.*, ct.type_name AS console_type, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                    c.unit_number, c.console_name,
                    ctrl.unit_number AS ctrl_unit, ctrl_t.type_name AS ctrl_type,
                    ctrl2.unit_number AS ctrl2_unit, ctrl2_t.type_name AS ctrl2_type
               FROM reservations r
               JOIN users u ON r.user_id = u.user_id
               LEFT JOIN consoles c ON r.console_id = c.console_id
               LEFT JOIN console_types ct ON r.console_type_id = ct.console_type_id
               LEFT JOIN controllers ctrl ON r.controller_id = ctrl.controller_id
               LEFT JOIN controller_types ctrl_t ON ctrl.controller_type_id = ctrl_t.controller_type_id
               LEFT JOIN controllers ctrl2 ON r.controller_id_2 = ctrl2.controller_id
               LEFT JOIN controller_types ctrl2_t ON ctrl2.controller_type_id = ctrl2_t.controller_type_id
              WHERE r.reserved_date >= ?
                AND r.status IN ('pending','reserved')
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
        "SELECT r.*, ct.type_name AS console_type, c.unit_number, c.console_name,
                ctrl.unit_number AS ctrl_unit, ctrl_t.type_name AS ctrl_type,
                ctrl2.unit_number AS ctrl2_unit, ctrl2_t.type_name AS ctrl2_type
           FROM reservations r
           LEFT JOIN consoles c ON r.console_id = c.console_id
           LEFT JOIN console_types ct ON r.console_type_id = ct.console_type_id
           LEFT JOIN controllers ctrl ON r.controller_id = ctrl.controller_id
           LEFT JOIN controller_types ctrl_t ON ctrl.controller_type_id = ctrl_t.controller_type_id
           LEFT JOIN controllers ctrl2 ON r.controller_id_2 = ctrl2.controller_id
           LEFT JOIN controller_types ctrl2_t ON ctrl2.controller_type_id = ctrl2_t.controller_type_id
          WHERE r.user_id = ?
          ORDER BY r.reserved_date DESC, r.reserved_time DESC"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Strip internal/system lines from reservation notes for customer-facing UI
 * (e.g. "[Controller rental modes: ...]" appended at booking).
 */
function reservationNotesForCustomerDisplay(?string $notes): string {
    if ($notes === null || trim($notes) === '') {
        return '';
    }
    $clean = preg_replace('/\R?\s*\[Controller rental modes:\s*[^\]]+\]\s*/iu', "\n", $notes);
    $clean = preg_replace('/\n{3,}/', "\n\n", $clean);
    return trim($clean);
}

/**
 * Get all cancelled reservations (any canceller) for admin refund management.
 * Includes customer and console info. Ordered newest cancellation first.
 */
function getCancelledReservations() {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT r.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                c.unit_number, c.console_name, ct.type_name AS console_type,
                rc.cancel_reason_type, rc.cancel_reason_detail,
                rc.cancelled_at,
                ctrl.unit_number AS ctrl_unit, ctrl_t.type_name AS ctrl_type,
                ctrl2.unit_number AS ctrl2_unit, ctrl2_t.type_name AS ctrl2_type
           FROM reservations r
           JOIN users u ON r.user_id = u.user_id
           LEFT JOIN consoles c ON r.console_id = c.console_id
           LEFT JOIN console_types ct ON r.console_type_id = ct.console_type_id
           LEFT JOIN controllers ctrl ON r.controller_id = ctrl.controller_id
           LEFT JOIN controller_types ctrl_t ON ctrl.controller_type_id = ctrl_t.controller_type_id
           LEFT JOIN controllers ctrl2 ON r.controller_id_2 = ctrl2.controller_id
           LEFT JOIN controller_types ctrl2_t ON ctrl2.controller_type_id = ctrl2_t.controller_type_id
           LEFT JOIN reservation_cancellations rc ON rc.reservation_id = r.reservation_id
          WHERE r.status IN ('cancelled', 'no_show')
          ORDER BY r.updated_at DESC"
    );
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Returns the current "operating day" date string (Y-m-d).
 * The operating day runs from 06:00:00 to 05:59:59 the next calendar day.
 */
function getOperatingDay($datetime = 'now') {
    $tz = new DateTimeZone('Asia/Manila');
    $dt = new DateTime($datetime, $tz);
    $hour = (int)$dt->format('G'); // 0-23
    
    if ($hour < 6) {
        // If before 6 AM, it belongs to the previous calendar day's operating day
        $dt->modify('-1 day');
    }
    
    return $dt->format('Y-m-d');
}

/**
 * Given an operating day date string (Y-m-d), returns the exact start and end timestamps.
 * e.g. "2026-05-07" returns ['2026-05-07 06:00:00', '2026-05-08 05:59:59']
 */
function getOperatingDayBounds($operating_date) {
    $start = $operating_date . ' 06:00:00';
    $end_dt = new DateTime($operating_date);
    $end_dt->modify('+1 day');
    $end = $end_dt->format('Y-m-d') . ' 05:59:59';
    return [$start, $end];
}

// ============================================================================
// CONSOLE TYPES (DYNAMIC LIST)
// ============================================================================

/**
 * Get all available console types from the database.
 */
// ============================================================================
// CONSOLE TYPE FUNCTIONS  (table: console_types)
// ============================================================================

/**
 * Get console types from the console_types table.
 * $onlyActive = true  → only non-archived rows
 * $onlyActive = false → all rows (used to build archived list)
 */
function getConsoleTypes(bool $onlyActive = true): array {
    global $conn;
    $check = $conn->query("SHOW TABLES LIKE 'console_types'");
    if (!$check || $check->num_rows === 0) return [];

    $where = $onlyActive ? "WHERE is_archived = 0" : "";
    $res = $conn->query("SELECT * FROM console_types $where ORDER BY type_name ASC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Add a new console type.
 * Returns true on success, false on duplicate or error.
 */
function addConsoleType(string $typeName, float $hourlyRate = 80.00): bool {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO console_types (type_name, hourly_rate) VALUES (?, ?)");
    $stmt->bind_param("sd", $typeName, $hourlyRate);
    try { return $stmt->execute(); } catch (Exception $e) { return false; }
}

/**
 * Archive a console type AND archive all consoles of that type.
 */
function archiveConsoleType(int $typeId): bool {
    global $conn;
    $conn->begin_transaction();
    try {
        $stmtName = $conn->prepare("SELECT type_name FROM console_types WHERE console_type_id = ?");
        $stmtName->bind_param("i", $typeId);
        $stmtName->execute();
        $row = $stmtName->get_result()->fetch_assoc();
        if (!$row) { $conn->rollback(); return false; }

        $typeName = $row['type_name'];
        $conn->prepare("UPDATE consoles SET status = 'archived' WHERE console_type_id = ?")->execute([$typeId]) ;
        $s = $conn->prepare("UPDATE console_types SET is_archived = 1 WHERE console_type_id = ?");
        $s->bind_param("i", $typeId); $s->execute();
        $conn->commit(); return true;
    } catch (Exception $e) { $conn->rollback(); return false; }
}

/** Restore an archived console type. */
function restoreConsoleType(int $typeId): bool {
    global $conn;
    $stmt = $conn->prepare("UPDATE console_types SET is_archived = 0 WHERE console_type_id = ?");
    $stmt->bind_param("i", $typeId);
    return $stmt->execute();
}

/** Permanently delete a console type. */
function deleteConsoleType(int $typeId): bool {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM console_types WHERE console_type_id = ?");
    $stmt->bind_param("i", $typeId);
    return $stmt->execute();
}

// ============================================================================
// CONTROLLER TYPE FUNCTIONS  (table: controller_types)
// ============================================================================

/**
 * Get controller types from the controller_types table.
 * Joins console_types to expose parent_console_name.
 * $onlyActive = true  → only non-archived
 * $consoleTypeId      → optional filter: only controllers for that console
 */
function getControllerTypes(bool $onlyActive = true, ?int $consoleTypeId = null): array {
    global $conn;
    $check = $conn->query("SHOW TABLES LIKE 'controller_types'");
    if (!$check || $check->num_rows === 0) return [];

    $conditions = [];
    if ($onlyActive)            $conditions[] = "ct.is_archived = 0";
    if ($consoleTypeId !== null) $conditions[] = "ct.console_type_id = " . (int)$consoleTypeId;
    $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

    $res = $conn->query(
        "SELECT ct.*, c.type_name AS parent_console_name
         FROM controller_types ct
         LEFT JOIN console_types c ON c.console_type_id = ct.console_type_id
         $where
         ORDER BY ct.type_name ASC"
    );
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Add a new controller type.
 * $consoleTypeId: optional FK to console_types.type_id (the platform it belongs to).
 */
function addControllerType(string $typeName, ?int $consoleTypeId = null): bool {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO controller_types (type_name, console_type_id) VALUES (?, ?)");
    $stmt->bind_param("si", $typeName, $consoleTypeId);
    try { return $stmt->execute(); } catch (Exception $e) { return false; }
}

/** Archive a controller type. */
function archiveControllerType(int $typeId): bool {
    global $conn;
    $stmt = $conn->prepare("UPDATE controller_types SET is_archived = 1 WHERE controller_type_id = ?");
    $stmt->bind_param("i", $typeId);
    return $stmt->execute();
}

/** Restore an archived controller type. */
function restoreControllerType(int $typeId): bool {
    global $conn;
    $stmt = $conn->prepare("UPDATE controller_types SET is_archived = 0 WHERE controller_type_id = ?");
    $stmt->bind_param("i", $typeId);
    return $stmt->execute();
}

/**
 * Permanently delete a controller type.
 * Detaches any controllers using it first (sets their console_type_id to NULL).
 */
function deleteControllerType(int $typeId): bool {
    global $conn;
    $conn->query("UPDATE controllers SET controller_type_id = NULL WHERE controller_type_id = $typeId");
    $stmt = $conn->prepare("DELETE FROM controller_types WHERE controller_type_id = ?");
    $stmt->bind_param("i", $typeId);
    return $stmt->execute();
}

// ============================================================================
// DATE BLOCKING FUNCTIONS
// ============================================================================

/**
 * Get all blocked dates.
 */
function getBlockedDates() {
    global $conn;
    $res = $conn->query("SELECT * FROM blocked_dates ORDER BY blocked_date ASC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Block a specific date.
 */
function blockDate($date, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT IGNORE INTO blocked_dates (blocked_date, reason) VALUES (?, ?)");
    $stmt->bind_param("ss", $date, $reason);
    return $stmt->execute();
}

/**
 * Unblock a specific date.
 */
function unblockDate($date) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM blocked_dates WHERE blocked_date = ?");
    $stmt->bind_param("s", $date);
    return $stmt->execute();
}

/**
 * Check if a date is blocked.
 */
function isDateBlocked($date) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM blocked_dates WHERE blocked_date = ? LIMIT 1");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
?>
