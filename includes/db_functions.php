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
        'xbox_hourly_rate'   => 80.0,  // Xbox rate (may differ)
    ];

    $keys = "'bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes','ps5_hourly_rate','xbox_hourly_rate','session_min_charge'";
    $res  = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($keys)");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            switch ($row['setting_key']) {
                case 'bonus_paid_minutes': $rules['bonus_paid_minutes'] = (int)  $row['setting_value']; break;
                case 'bonus_free_minutes': $rules['bonus_free_minutes'] = (int)  $row['setting_value']; break;
                case 'max_hourly_minutes': $rules['max_hourly_minutes'] = (int)  $row['setting_value']; break;
                case 'ps5_hourly_rate':    $rules['hourly_rate']        = (float)$row['setting_value']; break;
                case 'xbox_hourly_rate':   $rules['xbox_hourly_rate']   = (float)$row['setting_value']; break;
                case 'session_min_charge': $rules['session_min_charge'] = (float)$row['setting_value']; break;
            }
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
        $cost  = ($paid <= 30) ? $minChg : round($paid / 60 * $rate, 2);

        // Human-readable label helpers
        $fmtMin = function(int $m): string {
            $h = intdiv($m, 60); $r = $m % 60;
            if ($h && $r) return "{$h}h {$r}m";
            if ($h)       return "{$h}" . ($h === 1 ? ' hr' : ' hrs');
            return "{$r} min";
        };

        $options[] = [
            'paid'        => $paid,
            'total'       => $total,
            'cost'        => $cost,
            'bonus'       => $bonus,
            'label_paid'  => $fmtMin($paid),
            'label_total' => $fmtMin($total),
            'label_bonus' => $bonus > 0 ? '+' . $fmtMin($bonus) . ' free' : '',
        ];
    }
    return $options;
}


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
        // Apply bonus: convert paid minutes → total play minutes (paid + free bonus)
        // This is the ONLY place that applies the bonus — all callers pass paid minutes.
        $stored_minutes = null;
        if ($rental_mode === 'hourly' && $planned_minutes !== null) {
            $stored_minutes = paidToTotalMinutes((int)$planned_minutes);
        }

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
    if ($minutes <= 0)  return 0;
    if ($minutes <= 4)  return 0;   // grace period
    if ($minutes <= 19) return 20;
    if ($minutes <= 34) return 40;
    if ($minutes <= 49) return 60;
    return 80; // 50–59 min = full hour charge
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
    $sql = "SELECT gs.*, u.full_name AS customer_name, c.console_name, c.console_type, c.unit_number,
                   COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount > 0), 0) AS upfront_paid,
                   COALESCE((SELECT SUM(ABS(t.amount)) FROM transactions t WHERE t.session_id = gs.session_id AND t.amount < 0), 0) AS refunded_amount
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
        $reservation_id = $conn->insert_id;

        // Record the downpayment as a transaction so it appears in financial reports
        if ($downpayment_amount > 0 && $downpayment_method !== null) {
            recordTransaction(
                null,                   // no session yet
                $user_id,               // who paid
                $downpayment_amount,    // amount paid
                $downpayment_method,    // payment method
                $user_id,               // processed_by = the customer (self-serve)
                $downpayment_amount,    // tendered = same as amount (exact)
                null,                   // no shortfall
                'Downpayment for reservation #' . $reservation_id
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

