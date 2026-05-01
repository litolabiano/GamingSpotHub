<?php
/**
 * PayMongoService — GamingSpotHub
 *
 * Thin wrapper around the PayMongo REST API.
 * Supports:
 *   - createGCashSource()  → GCash Source-based redirect (most compatible)
 *   - getSource()          → Poll source payment status
 *   - createPayment()      → Charge a chargeable source
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
            return ['success' => false, 'message' => $msg, 'http_code' => $httpCode];
        }

        return ['success' => true, 'data' => $decoded['data'] ?? $decoded];
    }

    // ── Create a GCash Source ─────────────────────────────────────────────────
    /**
     * Creates a PayMongo Source for GCash payment.
     *
     * The customer is redirected to $redirect_success on payment success,
     * or $redirect_failed on failure / cancellation.
     *
     * Amount must be in CENTAVOS (e.g. ₱250.00 → 25000).
     *
     * @return array [
     *   'success'      => bool,
     *   'source_id'    => string,   // e.g. "src_xxxx"
     *   'checkout_url' => string,   // URL to redirect customer to
     *   'message'      => string,   // error message if success=false
     * ]
     */
    public static function createGCashSource(
        int    $amount_centavos,
        string $description,
        string $redirect_success,
        string $redirect_failed
    ): array {
        $result = self::request('POST', '/sources', [
            'data' => [
                'attributes' => [
                    'amount'   => $amount_centavos,
                    'currency' => 'PHP',
                    'type'     => 'gcash',
                    'redirect' => [
                        'success' => $redirect_success,
                        'failed'  => $redirect_failed,
                    ],
                    'billing' => [
                        'name' => 'Good Spot Gaming Hub Customer',
                    ],
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

    // ── Retrieve a Source ─────────────────────────────────────────────────────
    /**
     * Get the current status of a source.
     * Possible statuses: pending, chargeable, cancelled, consumed, expired.
     *
     * @return array ['success', 'status', 'amount', 'source_id'] or ['success'=>false, 'message']
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
            'amount'    => $attrs['amount'] ?? 0,   // centavos
            'type'      => $attrs['type'] ?? 'gcash',
        ];
    }

    // ── Charge a Chargeable Source ────────────────────────────────────────────
    /**
     * Create a Payment from a chargeable source.
     * Must be called after source status = 'chargeable'.
     *
     * @return array ['success', 'payment_id', 'status'] or ['success'=>false, 'message']
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
