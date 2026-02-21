<?php
/**
 * Good Spot Gaming Hub - Registration Page
 */
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare(
                "INSERT INTO users (email, password_hash, full_name, phone, role, status, email_verified, verification_token, verification_expires)
                 VALUES (?, ?, ?, ?, 'customer', 'active', 0, ?, ?)"
            );
            $stmt->bind_param("ssssss", $email, $password_hash, $full_name, $phone, $token, $expires);

            if ($stmt->execute()) {
                $mailResult = sendVerificationEmail($email, $full_name, $token);
                if ($mailResult['success']) {
                    $success = 'Registration successful! Please check your email to verify your account.';
                } else {
                    $success = 'Account created! However, we couldn\'t send the verification email. Please contact support.';
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Good Spot Gaming Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-page">
        <!-- Animated Background -->
        <div class="auth-bg-effects">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="grid-lines"></div>
        </div>

        <!-- Left Branding Panel -->
        <div class="auth-brand-panel">
            <div class="brand-content">
                <div class="brand-icon">🕹️</div>
                <h2 class="brand-title">Join the <span class="highlight">Good Spot</span> community</h2>
                <p class="brand-description">Create your account to start booking gaming sessions, join tournaments, and track your gaming journey.</p>
                <ul class="brand-features">
                    <li><i class="fas fa-user-shield"></i> Secure account with email verification</li>
                    <li><i class="fas fa-gamepad"></i> Book PS5 & Xbox sessions instantly</li>
                    <li><i class="fas fa-trophy"></i> Register for monthly tournaments</li>
                    <li><i class="fas fa-download"></i> Request game installations</li>
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

                    <h1 class="auth-title">Create your account</h1>
                    <p class="auth-subtitle">Start your gaming journey today</p>

                    <?php if ($error): ?>
                        <div class="auth-alert auth-alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="auth-alert auth-alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($success) ?></span>
                        </div>
                        <div class="auth-links">
                            <p><a href="login.php"><i class="fas fa-arrow-left"></i> Go to Sign In</a></p>
                        </div>
                    <?php else: ?>

                    <form class="auth-form" method="POST" action="" id="registerForm">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($full_name ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email ?? '') ?>" required autocomplete="email">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span style="color: rgba(255,255,255,0.25); font-weight: 400; text-transform: none;">(optional)</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="09171234567" value="<?= htmlspecialchars($phone ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
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
                            <label for="confirm_password">Confirm Password</label>
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
                            <span class="btn-text">Create Account</span>
                        </button>
                    </form>

                    <div class="auth-links">
                        <p>Already have an account? <a href="login.php">Sign in</a></p>
                    </div>

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

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBars = document.getElementById('strengthBars');
        const strengthText = document.getElementById('strengthText');

        passwordInput?.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            let label = '';

            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            strengthBars.className = 'password-strength';
            if (val.length === 0) {
                strengthText.textContent = '';
            } else if (score <= 1) {
                strengthBars.classList.add('weak');
                label = 'Weak';
                strengthText.style.color = '#fb566b';
            } else if (score <= 2) {
                strengthBars.classList.add('medium');
                label = 'Medium';
                strengthText.style.color = '#f1e1aa';
            } else if (score <= 3) {
                strengthBars.classList.add('strong');
                label = 'Strong';
                strengthText.style.color = '#20c8a1';
            } else {
                strengthBars.classList.add('very-strong');
                label = 'Very Strong';
                strengthText.style.color = '#20c8a1';
            }
            strengthText.textContent = label;
        });

        document.getElementById('registerForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
