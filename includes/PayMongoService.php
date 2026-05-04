<?php
/**
 * PayMongoService — GamingSpotHub
 *
 * Thin wrapper around the PayMongo REST API.
 *
 * Primary flow  → Checkout Session (hosted checkout page)
 *   createCheckoutSession()   – creates a session, returns checkout_url + session_id
 *   getCheckoutSession()      – retrieves a session to verify payment_status
 *
 * Legacy / fallback helpers (kept for reference, not used in primary flow)
 *   createGCashSource()       – Source-based GCash redirect (old flow)
 *   getSource()               – Poll source payment status
 *   createPayment()           – Charge a chargeable source
 *
 * Docs: https://developers.paymongo.com/reference/checkout-session-resource
 */

require_once __DIR__ . '/paymongo_config.php';

class PayMongoService
{
    // ── Internal cURL helper ──────────────────────────────────────────────────

    private static function request(string $method, string $endpoint, array $payload = []): array
    {
        $url = PAYMONGO_API_BASE . $endpoint;
        $ch  = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'cURL error: ' . $error];
        }

        $decoded = json_decode($body, true);

        if ($httpCode >= 400) {
            $msg = $decoded['errors'][0]['detail'] ?? 'PayMongo API error (HTTP ' . $httpCode . ')';
            return ['success' => false, 'message' => $msg, 'http_code' => $httpCode, 'raw' => $decoded];
        }

        return ['success' => true, 'data' => $decoded['data'] ?? $decoded];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIMARY FLOW — Checkout Session (hosted page, visible in any browser)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Create a PayMongo Checkout Session.
     *
     * Returns a browser-visible checkout URL at checkout.paymongo.com — ideal
     * for demonstrations; the panelist/customer sees a real payment form with
     * GCash (and optionally card) as options.
     *
     * Amount must be in CENTAVOS (e.g. ₱26.00 → 2600).
     *
     * @param int    $amount_centavos   Amount to charge (centavos)
     * @param string $description       Line-item description shown on the page
     * @param string $success_url       Where PayMongo redirects after payment
     * @param string $cancel_url        Where PayMongo redirects on cancel/failure
     * @param string $billing_email     Customer e-mail (required by PayMongo)
     * @param string $billing_name      Customer display name
     * @param string $billing_phone     Customer phone (optional)
     *
     * @return array [
     *   'success'       => bool,
     *   'session_id'    => string,   // e.g. "cs_xxxx"
     *   'checkout_url'  => string,   // https://checkout.paymongo.com/...
     *   'payment_status'=> string,   // 'unpaid' at creation time
     *   'message'       => string,   // error message when success=false
     * ]
     */
    public static function createCheckoutSession(
        int    $amount_centavos,
        string $description,
        string $success_url,
        string $cancel_url,
        string $billing_email = '',
        string $billing_name  = '',
        string $billing_phone = ''
    ): array {
        $billing = [
            'email' => $billing_email ?: 'noreply@goodspothub.com',
            'name'  => $billing_name  ?: 'Good Spot Gaming Hub Customer',
        ];
        if ($billing_phone) {
            $billing['phone'] = $billing_phone;
        }

        $result = self::request('POST', '/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'billing'              => $billing,
                    'line_items'           => [[
                        'currency'   => 'PHP',
                        'amount'     => $amount_centavos,
                        'name'       => 'Reservation Fee — Good Spot Gaming Hub',
                        'description'=> $description,
                        'quantity'   => 1,
                    ]],
                    // Accept GCash and card — gives panelists both options to demo
                    'payment_method_types' => ['gcash', 'card'],
                    'success_url'          => $success_url,
                    'cancel_url'           => $cancel_url,
                    'statement_descriptor' => 'GOOD SPOT HUB',
                    'description'          => $description,
                    'send_email_receipt'   => false,
                    'show_description'     => true,
                    'show_line_items'      => true,
                ],
            ],
        ]);

        if (!$result['success']) {
            return $result;
        }

        $attrs = $result['data']['attributes'] ?? [];

        return [
            'success'        => true,
            'session_id'     => $result['data']['id'] ?? '',
            'checkout_url'   => $attrs['checkout_url'] ?? '',
            'payment_status' => $attrs['payment_status'] ?? 'unpaid',
        ];
    }

    /**
     * Retrieve a Checkout Session by its ID.
     *
     * Call this when PayMongo redirects the customer back to success_url.
     * Check payment_status === 'paid' before fulfilling the order.
     *
     * Possible payment_status values: unpaid | paid | expired
     *
     * @return array [
     *   'success'        => bool,
     *   'session_id'     => string,
     *   'payment_status' => string,
     *   'payment_id'     => string|null,  // first payment ID if paid
     *   'amount'         => int,           // centavos
     * ]
     */
    public static function getCheckoutSession(string $session_id): array
    {
        // Include payments in the response — reduces the need for a second call
        $result = self::request('GET', '/checkout_sessions/' . $session_id . '?include[]=payments');

        // Always log the raw result so we can diagnose issues
        error_log('[PayMongo] getCheckoutSession(' . $session_id . ') raw: ' . json_encode($result));

        if (!$result['success']) {
            return $result;
        }

        $attrs = $result['data']['attributes'] ?? [];

        // ── payment_status on the session itself ──────────────────────────────
        $paymentStatus = $attrs['payment_status'] ?? 'unpaid';

        // ── Also check the nested payment_intent status ───────────────────────
        // PayMongo sometimes updates the PI before the session-level status
        $pi           = $attrs['payment_intent'] ?? [];
        $piAttrs      = is_array($pi) ? ($pi['attributes'] ?? []) : [];
        $piStatus     = $piAttrs['status'] ?? '';              // e.g. 'succeeded'
        $piPayments   = $piAttrs['payments'] ?? [];
        $piId         = is_array($pi) ? ($pi['id'] ?? null) : null;

        // ── Also check top-level payments array ───────────────────────────────
        $topPayments  = $attrs['payments'] ?? [];
        $allPayments  = array_merge($topPayments, $piPayments);

        // Grab first payment ID from whatever arrays we have
        $paymentId = null;
        foreach ($allPayments as $p) {
            $pid = is_array($p) ? ($p['id'] ?? null) : null;
            if ($pid) { $paymentId = $pid; break; }
        }

        // Mark as paid if EITHER the session status is 'paid'
        // OR the payment intent reached 'succeeded'
        $isPaid = ($paymentStatus === 'paid') || ($piStatus === 'succeeded');
        if ($isPaid) {
            $paymentStatus = 'paid';
        }

        // ── Fallback: if paid but no payment_id yet, fetch it from the PI ─────
        // The checkout session may not embed the payments array even when paid.
        // Fetching the Payment Intent directly always returns the payments list.
        if ($isPaid && !$paymentId && $piId) {
            $piResult = self::request('GET', '/payment_intents/' . $piId . '?include[]=payments');
            if ($piResult['success']) {
                $piData    = $piResult['data']['attributes'] ?? [];
                $piPayList = $piData['payments'] ?? [];
                foreach ($piPayList as $p) {
                    $pid = is_array($p) ? ($p['id'] ?? null) : null;
                    if ($pid) { $paymentId = $pid; break; }
                }
                error_log('[PayMongo] PI payments fetch for ' . $piId . ': payment_id=' . ($paymentId ?? 'null'));
            }
        }

        // Total line-items amount
        $lineItems = $attrs['line_items'] ?? [];
        $amount    = !empty($lineItems) ? ($lineItems[0]['amount'] ?? 0) : 0;

        error_log('[PayMongo] session payment_status=' . $paymentStatus . ' pi_status=' . $piStatus . ' payment_id=' . ($paymentId ?? 'null'));

        return [
            'success'        => true,
            'session_id'     => $result['data']['id'] ?? $session_id,
            'payment_status' => $paymentStatus,
            'pi_status'      => $piStatus,
            'payment_id'     => $paymentId,
            'amount'         => $amount,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // LEGACY HELPERS — Source-based GCash (kept for reference / fallback)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Creates a PayMongo Source for GCash payment.
     * @deprecated  Prefer createCheckoutSession() — it shows a real browser page.
     */
    public static function createGCashSource(
        int    $amount_centavos,
        string $description,
        string $redirect_success,
        string $redirect_failed,
        string $billing_email = '',
        string $billing_name  = '',
        string $billing_phone = ''
    ): array {
        $billing = [
            'email' => $billing_email ?: 'noreply@goodspothub.com',
            'name'  => $billing_name  ?: 'Good Spot Gaming Hub Customer',
        ];
        if ($billing_phone) {
            $billing['phone'] = $billing_phone;
        }

        $result = self::request('POST', '/sources', [
            'data' => [
                'attributes' => [
                    'amount'               => $amount_centavos,
                    'currency'             => 'PHP',
                    'type'                 => 'gcash',
                    'redirect'             => [
                        'success' => $redirect_success,
                        'failed'  => $redirect_failed,
                    ],
                    'billing'              => $billing,
                    'statement_descriptor' => 'GOOD SPOT HUB',
                ],
            ],
        ]);

        if (!$result['success']) {
            return $result;
        }

        $attrs = $result['data']['attributes'] ?? [];

        return [
            'success'      => true,
            'source_id'    => $result['data']['id'] ?? '',
            'checkout_url' => $attrs['redirect']['checkout_url'] ?? '',
            'status'       => $attrs['status'] ?? 'pending',
        ];
    }

    /**
     * Get the current status of a Source.
     * @deprecated  Use getCheckoutSession() in the Checkout Session flow.
     */
    public static function getSource(string $source_id): array
    {
        $result = self::request('GET', '/sources/' . $source_id);

        if (!$result['success']) {
            return $result;
        }

        $attrs = $result['data']['attributes'] ?? [];

        return [
            'success'   => true,
            'source_id' => $result['data']['id'] ?? $source_id,
            'status'    => $attrs['status'] ?? 'unknown',
            'amount'    => $attrs['amount'] ?? 0,
            'type'      => $attrs['type'] ?? 'gcash',
        ];
    }

    /**
     * Create a Payment from a chargeable Source.
     * @deprecated  Not needed in the Checkout Session flow.
     */
    public static function createPayment(
        string $source_id,
        int    $amount_centavos,
        string $description
    ): array {
        $result = self::request('POST', '/payments', [
            'data' => [
                'attributes' => [
                    'amount'      => $amount_centavos,
                    'currency'    => 'PHP',
                    'description' => $description,
                    'source'      => [
                        'id'   => $source_id,
                        'type' => 'source',
                    ],
                ],
            ],
        ]);

        if (!$result['success']) {
            return $result;
        }

        $attrs = $result['data']['attributes'] ?? [];

        return [
            'success'    => true,
            'payment_id' => $result['data']['id'] ?? '',
            'status'     => $attrs['status'] ?? 'unknown',
        ];
    }

    // ── Helper: PHP float (pesos) → centavos int ─────────────────────────────
    public static function pesosToCentavos(float $pesos): int
    {
        return (int) round($pesos * 100);
    }
}
