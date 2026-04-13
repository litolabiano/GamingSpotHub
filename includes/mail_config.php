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

// Site URL (update for production)
define('SITE_URL', 'http://localhost/GamingSpotHub');

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
