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

    $agreed = isset($_POST['agree_terms']);

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$agreed) {
        $error = 'You must agree to the Terms and Conditions to register.';
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
    <link href="../assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="../assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        /* ── Terms & Conditions Agreement Block ── */
        .terms-agree-wrap {
            margin-bottom: 18px;
            border-radius: 12px;
            border: 1px solid rgba(32,200,161,.2);
            overflow: hidden;
            background: rgba(10,33,81,.35);
        }
        .terms-summary-toggle {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 12px 16px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,.75);
            user-select: none;
            transition: background .2s;
        }
        .terms-summary-toggle:hover { background: rgba(32,200,161,.07); color: #fff; }
        .terms-summary-box {
            padding: 14px 16px 12px;
            border-top: 1px solid rgba(255,255,255,.07);
            background: rgba(0,0,0,.2);
        }
        .terms-summary-box ul {
            padding-left: 18px;
            margin: 0 0 12px;
        }
        .terms-summary-box li {
            font-size: 12px;
            color: rgba(255,255,255,.65);
            margin-bottom: 6px;
            line-height: 1.6;
        }
        .terms-summary-box li strong { color: rgba(255,255,255,.9); }
        .terms-checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-top: 1px solid rgba(255,255,255,.07);
            cursor: pointer;
            font-size: 13px;
            color: rgba(255,255,255,.7);
            transition: background .2s;
        }
        .terms-checkbox-label:hover { background: rgba(32,200,161,.05); }
        .terms-checkbox-label input[type="checkbox"] { display: none; }
        .terms-checkbox-custom {
            width: 18px; height: 18px; flex-shrink: 0;
            border-radius: 5px;
            border: 2px solid rgba(32,200,161,.4);
            background: rgba(10,33,81,.5);
            display: inline-flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .terms-checkbox-label input:checked ~ .terms-checkbox-custom {
            background: #20c8a1;
            border-color: #20c8a1;
        }
        .terms-checkbox-label input:checked ~ .terms-checkbox-custom::after {
            content: '';
            width: 5px; height: 9px;
            border: 2px solid #0a0f1c;
            border-top: none; border-left: none;
            transform: rotate(45deg) translateY(-1px);
            display: block;
        }
        .terms-checkbox-label a { color: #20c8a1; text-decoration: underline; }
        .auth-btn:disabled { opacity: .55; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
    </style>
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
                                <div class="auth-logo brand-icon">
                        <a href="../index.php">
                            <span style="color:#f1e1aa">G</span><span style="color:#20c8a1">s</span><span style="color:#b37bec">p</span><span style="color:#fb566b">o</span><span style="color:#5f85da">t</span>
                            <span class="logo-text">GAMING HUB</span>
                        </a>
                    </div>
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

                        <!-- Terms & Conditions Agreement -->
                        <div class="terms-agree-wrap" id="termsAgreeWrap">
                            <div class="terms-summary-toggle" onclick="toggleTermsSummary()" id="termsToggleBtn">
                                <i class="fas fa-file-contract" style="color:#20c8a1;"></i>
                                <span>Read key Terms &amp; Conditions</span>
                                <i class="fas fa-chevron-down" id="termsChevron" style="margin-left:auto;font-size:11px;transition:.2s;"></i>
                            </div>
                            <div class="terms-summary-box" id="termsSummaryBox" style="display:none;">
                                <ul>

                                    <li>Gaming rates: <strong>₱50</strong> starter (30 min), <strong>₱80/hr</strong> standard, <strong>₱40</strong> per 30-min extension. <strong>Free 30 min</strong> per every 2 hrs played.</li>
                                    <li>Reservations require <strong>1 hour lead time</strong>. Late cancellations incur an inconvenience fee.</li>
                                    <li><strong>3 consecutive cancellations</strong> result in a 1-week reservation ban.</li>
                                    <li>Tournament bracket resets for late arrivals require <strong>management approval and unanimous winner consent</strong>.</li>
                                    <li>Harassment, cheating, or property damage may result in <strong>immediate removal and account ban</strong>.</li>
                                    <li>You are responsible for any damage caused to Hub equipment.</li>
                                </ul>
                                <a href="../terms.php" target="_blank" style="color:#20c8a1;font-size:12px;font-weight:600;">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i>Read full Terms &amp; Conditions
                                </a>
                            </div>
                            <label class="terms-checkbox-label" for="agree_terms">
                                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                                <span class="terms-checkbox-custom"></span>
                                <span>I have read and agree to the <a href="../terms.php" target="_blank">Terms &amp; Conditions</a></span>
                            </label>
                        </div>

                        <button type="submit" class="auth-btn" id="submitBtn" disabled>
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

        // Enable/disable submit based on checkbox
        const agreeChk = document.getElementById('agree_terms');
        const submitBtn = document.getElementById('submitBtn');
        agreeChk?.addEventListener('change', function () {
            submitBtn.disabled = !this.checked;
        });

        document.getElementById('registerForm')?.addEventListener('submit', function() {
            if (!agreeChk?.checked) return false;
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        function toggleTermsSummary() {
            const box = document.getElementById('termsSummaryBox');
            const chevron = document.getElementById('termsChevron');
            const isHidden = box.style.display === 'none';
            box.style.display = isHidden ? 'block' : 'none';
            chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    </script>
</body>
</html>
