<?php
/**
 * Good Spot Gaming Hub - Mail Helper
 * 
 * Functions for sending verification and password reset emails using PHPMailer.
 */

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/../vendor/PHPMailer-6.9.1/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer-6.9.1/src/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer-6.9.1/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Create a configured PHPMailer instance.
 */
function createMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->isHTML(true);
    return $mail;
}

/**
 * Send email verification link.
 */
function sendVerificationEmail($email, $fullName, $token) {
    try {
        $mail = createMailer();
        $mail->addAddress($email, $fullName);
        $mail->Subject = '🎮 Verify Your Good Spot Gaming Hub Account';

        $verifyUrl = SITE_URL . '/auth/verify.php?token=' . urlencode($token);

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; font-family: "Inter", "Segoe UI", sans-serif; background: #0d1117; }
                .container { max-width: 600px; margin: 0 auto; background: #1a1a2e; border-radius: 16px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #0a2151, #1a1a2e); padding: 40px 30px; text-align: center; }
                .header h1 { color: #20c8a1; font-size: 24px; margin: 0; }
                .header p { color: #888; margin-top: 8px; font-size: 14px; }
                .body { padding: 30px; color: #e0e0e0; }
                .body p { line-height: 1.6; margin-bottom: 16px; }
                .btn { display: inline-block; background: linear-gradient(135deg, #20c8a1, #5f85da); color: white !important; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 16px; }
                .btn-wrap { text-align: center; margin: 30px 0; }
                .footer { padding: 20px 30px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid rgba(255,255,255,0.05); }
                .code { background: rgba(32,200,161,0.1); border: 1px solid rgba(32,200,161,0.3); padding: 3px 8px; border-radius: 4px; font-family: monospace; color: #20c8a1; }
            </style>
        </head>
        <body>
            <div style="padding: 20px; background: #0d1117;">
                <div class="container">
                    <div class="header">
                        <h1>🎮 Good Spot Gaming Hub</h1>
                        <p>Email Verification</p>
                    </div>
                    <div class="body">
                        <p>Hey <strong>' . htmlspecialchars($fullName) . '</strong>! 👋</p>
                        <p>Welcome to Good Spot Gaming Hub! Please verify your email address to activate your account and start gaming.</p>
                        <div class="btn-wrap">
                            <a href="' . $verifyUrl . '" class="btn">✅ Verify My Email</a>
                        </div>
                        <p style="font-size: 13px; color: #888;">This link expires in <strong>24 hours</strong>. If you didn\'t create an account, please ignore this email.</p>
                    </div>
                    <div class="footer">
                        <p>Good Spot Gaming Hub &bull; Don Placido Avenue, Dasmariñas</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Hi $fullName, verify your email: $verifyUrl (expires in 24 hours)";
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

/**
 * Send password reset link.
 */
function sendPasswordResetEmail($email, $fullName, $token) {
    try {
        $mail = createMailer();
        $mail->addAddress($email, $fullName);
        $mail->Subject = '🔐 Reset Your Good Spot Gaming Hub Password';

        $resetUrl = SITE_URL . '/auth/reset_password.php?token=' . urlencode($token);

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; font-family: "Inter", "Segoe UI", sans-serif; background: #0d1117; }
                .container { max-width: 600px; margin: 0 auto; background: #1a1a2e; border-radius: 16px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #0a2151, #1a1a2e); padding: 40px 30px; text-align: center; }
                .header h1 { color: #e94560; font-size: 24px; margin: 0; }
                .header p { color: #888; margin-top: 8px; font-size: 14px; }
                .body { padding: 30px; color: #e0e0e0; }
                .body p { line-height: 1.6; margin-bottom: 16px; }
                .btn { display: inline-block; background: linear-gradient(135deg, #e94560, #b37bec); color: white !important; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 16px; }
                .btn-wrap { text-align: center; margin: 30px 0; }
                .footer { padding: 20px 30px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid rgba(255,255,255,0.05); }
            </style>
        </head>
        <body>
            <div style="padding: 20px; background: #0d1117;">
                <div class="container">
                    <div class="header">
                        <h1>🔐 Password Reset</h1>
                        <p>Good Spot Gaming Hub</p>
                    </div>
                    <div class="body">
                        <p>Hey <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
                        <p>We received a request to reset your password. Click the button below to set a new password:</p>
                        <div class="btn-wrap">
                            <a href="' . $resetUrl . '" class="btn">🔑 Reset Password</a>
                        </div>
                        <p style="font-size: 13px; color: #888;">This link expires in <strong>1 hour</strong>. If you didn\'t request a password reset, ignore this email — your password won\'t change.</p>
                    </div>
                    <div class="footer">
                        <p>Good Spot Gaming Hub &bull; Don Placido Avenue, Dasmariñas</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Hi $fullName, reset your password: $resetUrl (expires in 1 hour)";
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
?>
