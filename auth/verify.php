<?php
/**
 * Good Spot Gaming Hub - Email Verification
 */
require_once __DIR__ . '/../includes/db_config.php';

$success = false;
$message = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['manual_token'])) {
    $token = strtoupper(trim($_POST['manual_token']));
}

if (empty($token)) {
    $message = 'No verification token provided.';
} else {
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE verification_token = ? AND verification_expires > NOW() AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        if ($stmt->execute()) {
            $success = true;
            $message = 'Your email has been verified successfully!';
        } else {
            $message = 'Verification failed. Please try again.';
        }
    } else {
        // Check if already verified
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE verification_token = ? AND email_verified = 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $success = true;
            $message = 'Your email was already verified!';
        } else {
            $message = 'Invalid or expired verification token.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Good Spot Gaming Hub</title>
    <link href="../assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="../assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-bg-effects">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="grid-lines"></div>
        </div>

        <div class="auth-brand-panel">
            <div class="brand-content">
                <?php if ($success): ?>
                    <div class="brand-icon">🎉</div>
                    <h2 class="brand-title">You're all <span class="highlight">set!</span></h2>
                    <p class="brand-description">Your account is verified and ready to go. Sign in to start booking gaming sessions and joining tournaments.</p>
                <?php else: ?>
                    <div class="brand-icon">⚠️</div>
                    <h2 class="brand-title">Verification <span class="highlight">issue</span></h2>
                    <p class="brand-description">The verification link may have expired or already been used. You can register again to get a new verification link.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="auth-form-panel">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-logo">
                        <a href="../index.php">
                            <span style="color:#f1e1aa">G</span><span style="color:#20c8a1">s</span><span style="color:#b37bec">p</span><span style="color:#fb566b">o</span><span style="color:#5f85da">t</span>
                            <span class="logo-text">GAMING HUB</span>
                        </a>
                    </div>

                    <div class="auth-result">
                        <?php if ($success): ?>
                            <div class="auth-result-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h1 class="auth-title">Email Verified!</h1>
                            <p class="auth-subtitle"><?= htmlspecialchars($message) ?></p>
                            <div style="margin-top: 30px;">
                                <a href="login.php?verified=1" class="auth-btn">Sign In Now</a>
                            </div>
                        <?php else: ?>
                            <div class="auth-result-icon error">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h1 class="auth-title">Verification Failed</h1>
                            <p class="auth-subtitle"><?= htmlspecialchars($message) ?></p>

                            <div class="auth-manual-entry" style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px;">
                                <p style="font-size: 13px; color: rgba(255,255,255,0.6); margin-bottom: 15px;">Link not working? Enter the 8-character code from your email manually:</p>
                                <form action="verify.php" method="POST" class="auth-form">
                                    <div class="form-group">
                                        <div class="input-wrapper">
                                            <i class="fas fa-key"></i>
                                            <input type="text" name="manual_token" class="form-control" placeholder="Enter Code (e.g. A1B2C3D4)" maxlength="8" required style="text-transform: uppercase; text-align: center; font-family: monospace; letter-spacing: 2px;">
                                        </div>
                                    </div>
                                    <button type="submit" class="auth-btn" style="margin-top: 10px;">Verify Code</button>
                                </form>
                            </div>

                            <div class="auth-links" style="margin-top: 20px;">
                                <p><a href="register.php"><i class="fas fa-arrow-left"></i> Back to Registration</a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
