<?php
/**
 * Good Spot Gaming Hub - Reset Password
 */
session_start();
require_once __DIR__ . '/../includes/db_config.php';

$error = '';
$success = '';
$valid_token = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $valid_token = true;
        $user = $result->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $stmt->bind_param("si", $password_hash, $user['user_id']);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Good Spot Gaming Hub</title>
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

        <div class="auth-brand-panel">
            <div class="brand-content">
                <div class="brand-icon">🔑</div>
                <h2 class="brand-title">Set a new <span class="highlight">password</span></h2>
                <p class="brand-description">Choose a strong password that you haven't used before to keep your account secure.</p>
                <ul class="brand-features">
                    <li><i class="fas fa-check"></i> At least 8 characters</li>
                    <li><i class="fas fa-check"></i> Mix of upper and lower case</li>
                    <li><i class="fas fa-check"></i> Include numbers & symbols</li>
                </ul>
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

                    <?php if ($success): ?>
                        <div class="auth-result">
                            <div class="auth-result-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h1 class="auth-title">Password Updated!</h1>
                            <p class="auth-subtitle">Your password has been reset successfully</p>
                            <a href="login.php?reset=1" class="auth-btn">Sign In Now</a>
                        </div>

                    <?php elseif (!$valid_token): ?>
                        <div class="auth-result">
                            <div class="auth-result-icon error">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h1 class="auth-title">Link Expired</h1>
                            <p class="auth-subtitle">This password reset link is invalid or has expired</p>
                            <a href="forgot_password.php" class="auth-btn">Request New Link</a>
                        </div>

                    <?php else: ?>
                        <h1 class="auth-title">Set new password</h1>
                        <p class="auth-subtitle">Hi <?= htmlspecialchars($user['full_name']) ?>, choose a new password</p>

                        <?php if ($error): ?>
                            <div class="auth-alert auth-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?= htmlspecialchars($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <form class="auth-form" method="POST" action="" id="resetForm">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="strengthBars">
                                    <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
                                </div>
                                <div class="password-strength-text" id="strengthText"></div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-shield-halved"></i>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="auth-btn" id="submitBtn">
                                <span class="spinner"></span>
                                <span class="btn-text">Reset Password</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        const passwordInput = document.getElementById('password');
        const strengthBars = document.getElementById('strengthBars');
        const strengthText = document.getElementById('strengthText');

        passwordInput?.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            strengthBars.className = 'password-strength';
            if (val.length === 0) { strengthText.textContent = ''; return; }
            const levels = [
                { cls: 'weak', text: 'Weak', color: '#fb566b' },
                { cls: 'medium', text: 'Medium', color: '#f1e1aa' },
                { cls: 'strong', text: 'Strong', color: '#20c8a1' },
                { cls: 'very-strong', text: 'Very Strong', color: '#20c8a1' }
            ];
            const idx = Math.min(Math.max(score - 1, 0), 3);
            strengthBars.classList.add(levels[idx].cls);
            strengthText.textContent = levels[idx].text;
            strengthText.style.color = levels[idx].color;
        });

        document.getElementById('resetForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
