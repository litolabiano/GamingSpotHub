<?php
/**
 * PayMongo Configuration
 * Keys for GamingSpotHub — Good Spot Gaming Hub
 *
 * ⚠ NEVER commit real live keys to git. Add this file to .gitignore.
 */

// ── Test Keys (sandbox — no real money moves) ────────────────────────────────
define('PAYMONGO_SECRET_KEY', 'sk_test_VoVdWqnxHrxFKneyuc7Q8Ezg');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_CybAPj9ZV2Pqk9FSN7XynokS');

// ── Live Keys — uncomment ONLY after PayMongo KYC is approved ─────────────────
// define('PAYMONGO_SECRET_KEY', 'sk_live_FskpnQ3v8EZwTVbmJFGptTZw');
// define('PAYMONGO_PUBLIC_KEY', 'pk_live_GvSQoLfdBUb73JCH57G7zvFF');

// Base URL of PayMongo API
define('PAYMONGO_API_BASE', 'https://api.paymongo.com/v1');

// Site base URL — used for redirect URLs sent to PayMongo
// Change this when deploying (e.g. https://yourdomain.com)
if (!defined('SITE_URL')) {
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base      = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    define('SITE_URL', $protocol . '://' . $host . $base);
}
