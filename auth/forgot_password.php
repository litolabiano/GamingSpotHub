<?php
/**
 * Good Spot Gaming Hub - Forgot Password
 */
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $token, $expires, $user['user_id']);
            $stmt->execute();

            sendPasswordResetEmail($email, $user['full_name'], $token);
        }

        $success = 'If an account with that email exists, we\'ve sent a password reset link. Check your inbox.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Good Spot Gaming Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        <!-- Left Branding Panel -->
        <div class="auth-brand-panel">
            <div class="brand-content">
                <div class="brand-icon">🔐</div>
                <h2 class="brand-title">Forgot your <span class="highlight">password?</span></h2>
                <p class="brand-description">No worries! Enter the email address linked to your account, and we'll send you a secure link to reset your password.</p>
                <ul class="brand-features">
                    <li><i class="fas fa-envelope"></i> Secure reset link via email</li>
                    <li><i class="fas fa-clock"></i> Link expires in 1 hour</li>
                    <li><i class="fas fa-shield-halved"></i> Your data stays safe</li>
                </ul>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="auth-form-panel">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-logo">
                        <a href="../index.php">
                            <span style="color:#f1e1aa">G</span><span style="color:#20c8a1">s</span><span style="color:#b37bec">p</span><span style="color:#fb566b">o</span><span style="color:#5f85da">t</span>
                            <span class="logo-text">GAMING HUB</span>
                        </a>
                    </div>

                    <h1 class="auth-title">Reset your password</h1>
                    <p class="auth-subtitle">We'll email you a link to reset your password</p>

                    <?php if ($error): ?>
                        <div class="auth-alert auth-alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="auth-alert auth-alert-success">
                            <i class="fas fa-paper-plane"></i>
                            <span><?= htmlspecialchars($success) ?></span>
                        </div>
                        <div class="auth-links" style="margin-top: 1.25rem;">
                            <p><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Sign In</a></p>
                        </div>
                    <?php else: ?>

                    <form class="auth-form" method="POST" action="" id="forgotForm">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email ?? '') ?>" required autocomplete="email">
                            </div>
                        </div>

                        <button type="submit" class="auth-btn" id="submitBtn">
                            <span class="spinner"></span>
                            <span class="btn-text">Send Reset Link</span>
                        </button>
                    </form>

                    <div class="auth-links">
                        <p>Remember your password? <a href="login.php">Sign in</a></p>
                        <p style="margin-top: 0.5rem;">Don't have an account? <a href="register.php">Register</a></p>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
