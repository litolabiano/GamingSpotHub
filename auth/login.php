<?php
/**
 * Good Spot Gaming Hub - Login Page
 */
session_start();
require_once __DIR__ . '/../includes/db_config.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, email, password_hash, full_name, role, status, email_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Please contact support.';
            } elseif (!$user['email_verified']) {
                $error = 'Please verify your email address first. Check your inbox for the verification link.';
            } elseif (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                switch ($user['role']) {
                    case 'owner':
                    case 'shopkeeper':
                        header('Location: ../admin.php');
                        break;
                    default:
                        header('Location: ../index.php');
                }
                exit;
            } else {
                $error = 'Incorrect email or password.';
            }
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Good Spot Gaming Hub</title>
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
                <div class="brand-icon">🎮</div>
                <h2 class="brand-title">Welcome back to <span class="highlight">Good Spot</span></h2>
                <p class="brand-description">Sign in to access your gaming sessions, track your history, and join upcoming tournaments.</p>
                <ul class="brand-features">
                    <li><i class="fas fa-gamepad"></i> Real-time console availability</li>
                    <li><i class="fas fa-trophy"></i> Monthly tournaments & prizes</li>
                    <li><i class="fas fa-chart-line"></i> Track your gaming history</li>
                    <li><i class="fas fa-bolt"></i> Instant session booking</li>
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

                    <h1 class="auth-title">Sign in to your account</h1>
                    <p class="auth-subtitle">Enter your credentials to continue</p>

                    <?php if ($error): ?>
                        <div class="auth-alert auth-alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['verified'])): ?>
                        <div class="auth-alert auth-alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Email verified successfully! You can now sign in.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['reset'])): ?>
                        <div class="auth-alert auth-alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Password reset successful! Sign in with your new password.</span>
                        </div>
                    <?php endif; ?>

                    <form class="auth-form" method="POST" action="" id="loginForm">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email ?? '') ?>" required autocomplete="email">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="forgot-link">
                            <a href="forgot_password.php">Forgot password?</a>
                        </div>

                        <button type="submit" class="auth-btn" id="submitBtn">
                            <span class="spinner"></span>
                            <span class="btn-text">Sign In</span>
                        </button>
                    </form>

                    <div class="auth-links">
                        <p>Don't have an account? <a href="register.php">Create one</a></p>
                    </div>
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

        document.getElementById('loginForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
