<?php
/**
 * Quick email debug test
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/mail_config.php';
require_once __DIR__ . '/vendor/PHPMailer-6.9.1/src/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer-6.9.1/src/SMTP.php';
require_once __DIR__ . '/vendor/PHPMailer-6.9.1/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace;'>";
echo "=== PHPMailer Debug Test ===\n\n";

echo "MAIL_HOST: " . MAIL_HOST . "\n";
echo "MAIL_PORT: " . MAIL_PORT . "\n";
echo "MAIL_USERNAME: " . MAIL_USERNAME . "\n";
echo "MAIL_FROM_EMAIL: " . MAIL_FROM_EMAIL . "\n";
echo "MAIL_ENCRYPTION: " . MAIL_ENCRYPTION . "\n\n";

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_USERNAME, 'Test');
    $mail->isHTML(true);
    $mail->Subject = 'GamingSpotHub Test Email';
    $mail->Body    = '<h1>Test email works!</h1>';

    echo "Attempting to send...\n\n";
    $mail->send();
    echo "\n✅ EMAIL SENT SUCCESSFULLY!\n";
} catch (Exception $e) {
    echo "\n❌ FAILED: " . $mail->ErrorInfo . "\n";
}

echo "</pre>";
?>
