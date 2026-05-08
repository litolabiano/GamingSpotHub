<?php
/**
 * PayMongo Webhook Handler — GamingSpotHub
 *
 * Listens for PayMongo webhook events and keeps the reservations table
 * in sync with the actual payment status regardless of what happened in
 * the browser (e.g. user closed the tab, navigated away, network drop).
 *
 * Supported events:
 *   checkout_session.payment.paid     → mark reservation paid
 *   checkout_session.payment.failed   → mark reservation failed / cancel if pending
 *   checkout_session.expired          → mark reservation expired / cancel if pending
 *   payment.paid                      → fallback: mark reservation paid via payment_id
 *   payment.failed                    → fallback: mark reservation failed via payment_id
 *
 * Registration:
 *   Dashboard → https://dashboard.paymongo.com → Developers → Webhooks
 *   URL: https://yourdomain.com/GamingSpotHub/api/paymongo_webhook.php
 *   Events: checkout_session.payment.paid, checkout_session.payment.failed,
 *           checkout_session.expired, payment.paid, payment.failed
 *
 * Security:
 *   PayMongo signs every webhook with a HMAC-SHA256 signature using the
 *   Webhook Secret Key visible in the dashboard. We verify this before
 *   processing any payload.
 *
 * Docs: https://developers.paymongo.com/docs/webhooks
 */

// ── Bootstrap (no session, no login required — this is called by PayMongo) ─────
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/paymongo_config.php';

// ── Webhook secret key ────────────────────────────────────────────────────────
// Set this to the WEBHOOK secret shown in the PayMongo dashboard.
// It is DIFFERENT from the API secret key. Keep it safe.
// For local development (no public URL), skip signature verification.
if (!defined('PAYMONGO_WEBHOOK_SECRET')) {
    // TODO: Set your real webhook secret here, e.g.:
    // define('PAYMONGO_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxxxxxxxxx');
    define('PAYMONGO_WEBHOOK_SECRET', '');
}

// ── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ── Read raw payload ─────────────────────────────────────────────────────────
$rawBody = file_get_contents('php://input');
if (empty($rawBody)) {
    http_response_code(400);
    exit('Empty body');
}

// ── Verify PayMongo signature (skip in local/dev if no secret is set) ────────
if (PAYMONGO_WEBHOOK_SECRET !== '') {
    $sigHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
    if (!verifyPayMongoSignature($rawBody, $sigHeader, PAYMONGO_WEBHOOK_SECRET)) {
        http_response_code(401);
        error_log('[Webhook] Invalid signature — rejecting payload');
        exit('Unauthorized');
    }
}

// ── Decode payload ───────────────────────────────────────────────────────────
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit('Invalid JSON');
}

$eventType = $payload['data']['attributes']['type']   ?? '';
$eventData = $payload['data']['attributes']['data']   ?? [];
$resAttrs  = $eventData['attributes']                 ?? [];
$resId     = $eventData['id']                         ?? '';

error_log('[Webhook] Received event: ' . $eventType . ' | object_id: ' . $resId);

// Acknowledge the webhook immediately; do the DB work below.
http_response_code(200);
echo json_encode(['received' => true]);

// Flush the response so PayMongo considers it delivered
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ob_end_flush();
    flush();
}

// ── Route event to handler ───────────────────────────────────────────────────
switch ($eventType) {

    // ── Checkout Session: payment succeeded ──────────────────────────────────
    case 'checkout_session.payment.paid':
        $checkoutSessionId = $resId;  // cs_xxx
        $paymentStatus     = $resAttrs['payment_status'] ?? '';

        if ($paymentStatus === 'paid') {
            // Find the matching reservation by paymongo_source_id (= checkout session ID)
            $reservation = getReservationByPayMongoSource($checkoutSessionId);
            if ($reservation) {
                markReservationPaid(
                    $reservation['reservation_id'],
                    $checkoutSessionId,
                    $checkoutSessionId,  // payment_id not in this event; use cs_xxx
                    'paid'
                );
                error_log('[Webhook] Marked reservation #' . $reservation['reservation_id'] . ' as paid (cs=' . $checkoutSessionId . ')');
            } else {
                // No reservation yet — the browser hasn't finished PATH B.
                // Store a pending webhook record so PATH B can pick it up.
                // (Simple approach: just log; PATH B retries up to 4 times anyway.)
                error_log('[Webhook] No reservation found for cs=' . $checkoutSessionId . ' — may arrive before PATH B completes. OK.');
            }
        }
        break;

    // ── Checkout Session: payment failed ─────────────────────────────────────
    case 'checkout_session.payment.failed':
        $checkoutSessionId = $resId;
        $reservation = getReservationByPayMongoSource($checkoutSessionId);
        if ($reservation && in_array($reservation['status'], ['pending', 'reserved'])) {
            markReservationPayMongoStatus($reservation['reservation_id'], 'failed');
            // Optionally auto-cancel:
            // cancelReservationByWebhook($reservation['reservation_id'], 'paymongo_failed');
            error_log('[Webhook] Marked reservation #' . $reservation['reservation_id'] . ' paymongo_status=failed');
        }
        break;

    // ── Checkout Session: session expired without payment ────────────────────
    case 'checkout_session.expired':
        $checkoutSessionId = $resId;
        $reservation = getReservationByPayMongoSource($checkoutSessionId);
        if ($reservation && in_array($reservation['status'], ['pending', 'reserved'])) {
            markReservationPayMongoStatus($reservation['reservation_id'], 'expired');
            error_log('[Webhook] Marked reservation #' . $reservation['reservation_id'] . ' paymongo_status=expired (session expired)');
        }
        break;

    // ── Payment paid (generic — fallback for source-based flows) ─────────────
    case 'payment.paid':
        $paymentId = $resId;  // pay_xxx
        $reservation = getReservationByPayMongoPayment($paymentId);
        if ($reservation) {
            markReservationPaid(
                $reservation['reservation_id'],
                $reservation['paymongo_source_id'] ?? $paymentId,
                $paymentId,
                'paid'
            );
            error_log('[Webhook] payment.paid — marked reservation #' . $reservation['reservation_id'] . ' paid (pay=' . $paymentId . ')');
        }
        break;

    // ── Payment failed (generic fallback) ─────────────────────────────────────
    case 'payment.failed':
        $paymentId = $resId;
        $reservation = getReservationByPayMongoPayment($paymentId);
        if ($reservation && in_array($reservation['status'], ['pending', 'reserved'])) {
            markReservationPayMongoStatus($reservation['reservation_id'], 'failed');
            error_log('[Webhook] payment.failed — marked reservation #' . $reservation['reservation_id'] . ' failed (pay=' . $paymentId . ')');
        }
        break;

    default:
        error_log('[Webhook] Unhandled event type: ' . $eventType . ' — ignored.');
        break;
}

// ── Helper functions ─────────────────────────────────────────────────────────

/**
 * Verify the PayMongo webhook HMAC-SHA256 signature.
 *
 * The Paymongo-Signature header contains:
 *   t=<timestamp>,te=<signature_test>,li=<signature_live>
 *
 * @param string $body       Raw request body
 * @param string $header     Paymongo-Signature header value
 * @param string $secret     Webhook secret key (whsec_xxx)
 */
function verifyPayMongoSignature(string $body, string $header, string $secret): bool
{
    if (empty($header)) return false;

    $parts     = [];
    $timestamp = '';
    $signature = '';

    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
        if ($k === 't')                    $timestamp = $v;
        if (in_array($k, ['te', 'li']))    $signature = $v;  // prefer first found
    }

    if (!$timestamp || !$signature) return false;

    // Reject stale webhooks (older than 5 minutes)
    if (abs(time() - (int)$timestamp) > 300) {
        error_log('[Webhook] Stale timestamp: ' . $timestamp);
        return false;
    }

    $expected = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    return hash_equals($expected, $signature);
}

/**
 * Find a reservation row by its PayMongo checkout session ID.
 */
function getReservationByPayMongoSource(string $sessionId): ?array
{
    global $conn;
    if (!$sessionId) return null;
    $stmt = $conn->prepare(
        "SELECT reservation_id, status, paymongo_source_id FROM reservations
          WHERE paymongo_source_id = ? LIMIT 1"
    );
    $stmt->bind_param('s', $sessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/**
 * Find a reservation row by its PayMongo payment ID.
 */
function getReservationByPayMongoPayment(string $paymentId): ?array
{
    global $conn;
    if (!$paymentId) return null;
    $stmt = $conn->prepare(
        "SELECT reservation_id, status, paymongo_source_id FROM reservations
          WHERE paymongo_payment_id = ? LIMIT 1"
    );
    $stmt->bind_param('s', $paymentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/**
 * Mark a reservation as paid (webhook-confirmed).
 */
function markReservationPaid(int $reservationId, string $sourceId, string $paymentId, string $pmStatus): void
{
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE reservations
            SET paymongo_source_id  = COALESCE(NULLIF(paymongo_source_id,''), ?),
                paymongo_payment_id = COALESCE(NULLIF(paymongo_payment_id,''), ?),
                paymongo_status     = ?,
                downpayment_paid    = 1
          WHERE reservation_id = ?"
    );
    $stmt->bind_param('sssi', $sourceId, $paymentId, $pmStatus, $reservationId);
    $stmt->execute();
}

/**
 * Update only the paymongo_status column.
 */
function markReservationPayMongoStatus(int $reservationId, string $pmStatus): void
{
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE reservations SET paymongo_status = ? WHERE reservation_id = ?"
    );
    $stmt->bind_param('si', $pmStatus, $reservationId);
    $stmt->execute();
}

/**
 * Auto-cancel a reservation due to a PayMongo failure (optional — commented
 * out in the switch above; enable only if you want instant auto-cancellation).
 */
function cancelReservationByWebhook(int $reservationId, string $reason): void
{
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE reservations
            SET status            = 'cancelled',
                cancellation_reason = ?,
                cancel_reason_type  = 'payment_failed',
                cancelled_by        = NULL
          WHERE reservation_id = ?
            AND status IN ('pending','reserved')"
    );
    $stmt->bind_param('si', $reason, $reservationId);
    $stmt->execute();
}
