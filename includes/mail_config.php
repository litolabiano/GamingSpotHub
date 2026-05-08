<?php
/**
 * Good Spot Gaming Hub - Mail Configuration
 * 
 * PHPMailer configuration for email verification and password reset.
 * Update these settings with your actual SMTP credentials.
 */

// SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');          // SMTP server
define('MAIL_PORT', 587);                        // SMTP port (587 for TLS)
define('MAIL_USERNAME', 'goodspotgaminghub@gmail.com'); // Your email address
define('MAIL_PASSWORD', 'izqo uovr zxgq nmlg');     // Gmail App Password (NOT your regular password)
define('MAIL_ENCRYPTION', 'tls');                // tls or ssl
define('MAIL_FROM_EMAIL', 'goodspotgaminghub@gmail.com');
define('MAIL_FROM_NAME', 'Good Spot Gaming Hub');

// Site URL (Prioritize database configuration for multi-device/network access)
$dbBaseUrl = function_exists('getSetting') ? getSetting('base_url') : '';
if (!empty($dbBaseUrl)) {
    $siteUrl = rtrim($dbBaseUrl, '/');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // If running locally, swap "localhost" with the actual local IPv4 address 
    // so that mobile devices on the same Wi-Fi can correctly resolve the verification link
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $localIP = gethostbyname(gethostname());
        if ($localIP && $localIP !== '127.0.0.1') {
            $host = str_replace(['localhost', '127.0.0.1'], $localIP, $host);
        }
    }
    $siteUrl = $protocol . $host;
}

define('SITE_URL', $siteUrl . '/GamingSpotHub');

/*
 * ============================================================================
 * HOW TO SET UP GMAIL APP PASSWORD:
 * ============================================================================
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification
 * 3. Go to https://myaccount.google.com/apppasswords
 * 4. Generate a new App Password for "Mail"
 * 5. Copy the 16-character password and paste it above as MAIL_PASSWORD
 * ============================================================================
 */
?>
