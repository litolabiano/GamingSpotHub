<?php
/**
 * Good Spot Gaming Hub - Shopkeeper Registration Page
 */
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    $agreed = isset($_POST['agree_terms']);

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $error = 'Phone number must be exactly 11 digits and start with 09 (e.g. 09171234567).';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$agreed) {
        $error = 'You must agree to the Staff Terms and Conditions to register.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            // Generate a shorter 8-character alphanumeric token for easier manual entry
            $token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $display_name = $full_name;

            $stmt = $conn->prepare(
                "INSERT INTO users (email, password_hash, full_name, phone, role, status, email_verified, verification_token, verification_expires)
                 VALUES (?, ?, ?, ?, 'shopkeeper', 'active', 0, ?, ?)"
            );
            $stmt->bind_param("ssssss", $email, $password_hash, $display_name, $phone, $token, $expires);

            if ($stmt->execute()) {
                $mailResult = sendVerificationEmail($email, $full_name, $token);
                if ($mailResult['success']) {
                    $success = 'Shopkeeper account registered! Please check your email to verify your account.';
                } else {
                    $success = 'Account created! However, we couldn\'t send the verification email. Please contact the administrator.';
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
    <title>Shopkeeper Registration - Good Spot Gaming Hub</title>
    <link href="../assets/fonts/inter/inter.css" rel="stylesheet">
    <link href="../assets/fonts/outfit/outfit.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        :root {
            --sk-accent: #f1a83c;
            --sk-accent-glow: rgba(241, 168, 60, 0.3);
        }
        .auth-brand-panel { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-right: 1px solid var(--sk-accent-glow); }
        .highlight { color: var(--sk-accent) !important; }
        .auth-btn { background: var(--sk-accent) !important; color: #000 !important; font-weight: 800 !important; }
        .auth-btn:hover { box-shadow: 0 0 20px var(--sk-accent-glow) !important; }
        
        .staff-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--sk-accent-glow);
            color: var(--sk-accent);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .terms-agree-wrap {
            border: 1px solid var(--sk-accent-glow);
            background: rgba(241, 168, 60, 0.05);
        }
        .terms-checkbox-custom { border-color: var(--sk-accent-glow); }
        .terms-checkbox-label input:checked ~ .terms-checkbox-custom { background: var(--sk-accent); border-color: var(--sk-accent); }


        /* ── Password Toggle ── */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            padding: 5px;
            z-index: 5;
            transition: color 0.2s;
        }
        .password-toggle:hover {
            color: var(--sk-accent);
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-bg-effects">
            <div class="orb orb-1" style="background: var(--sk-accent-glow)"></div>
            <div class="orb orb-2"></div>
            <div class="grid-lines"></div>
        </div>

        <div class="auth-brand-panel">
            <div class="brand-content">
                <div class="staff-badge"><i class="fas fa-shield-alt mr-2"></i>Staff Access</div>
                <div class="auth-logo brand-icon">
                    <a href="../index.php">
                        <span style="color:#f1e1aa">G</span><span style="color:#20c8a1">s</span><span style="color:#b37bec">p</span><span style="color:#fb566b">o</span><span style="color:#5f85da">t</span>
                        <span class="logo-text">GAMING HUB</span>
                    </a>
                </div>
                <h2 class="brand-title">Join as a <span class="highlight">Shopkeeper</span></h2>
                <p class="brand-description">Register as a member of our management team. You'll be able to manage sessions, handle payments, and maintain the hub's operations.</p>
                <ul class="brand-features">
                    <li><i class="fas fa-desktop"></i> Access Admin Dashboard</li>
                    <li><i class="fas fa-cash-register"></i> Manage Real-time Sessions</li>
                    <li><i class="fas fa-users"></i> Oversee Tournament Brackets</li>
                    <li><i class="fas fa-tools"></i> Monitor Console Maintenance</li>
                </ul>
            </div>
        </div>

        <div class="auth-form-panel">
            <div class="auth-container">
                <div class="auth-card">
                    <h1 class="auth-title">Shopkeeper Setup</h1>
                    <p class="auth-subtitle">Create your administrative staff account</p>

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
                            <label for="email">Work Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="staff@gspot.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
                            </div>
                        </div>


                        <div class="form-group">
                            <label for="phone">Phone Number <span style="color:var(--sk-accent);opacity:0.6;font-size:11px;">(11 digits starting with 09)</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                    placeholder="09171234567" value="<?= htmlspecialchars($phone ?? '') ?>" 
                                    required pattern="09[0-9]{9}" maxlength="11"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Min 8 characters" required minlength="8">
                                <button type="button" class="password-toggle" onclick="toggleAllPasswords()">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-shield-halved"></i>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle" onclick="toggleAllPasswords()">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>

                        <div class="terms-agree-wrap">
                            <label class="terms-checkbox-label" for="agree_terms">
                                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                                <span class="terms-checkbox-custom"></span>
                                <span>I agree to the <a href="../terms.php" target="_blank" style="color:var(--sk-accent)">Staff Code of Conduct</a> and Hub Policies.</span>
                            </label>
                        </div>

                        <button type="submit" class="auth-btn" id="submitBtn" disabled>
                            <span class="btn-text">Register as Shopkeeper</span>
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
        const agreeChk = document.getElementById('agree_terms');
        const submitBtn = document.getElementById('submitBtn');
        agreeChk?.addEventListener('change', function () {
            submitBtn.disabled = !this.checked;
        });

        function toggleAllPasswords() {
            const p1 = document.getElementById('password');
            const p2 = document.getElementById('confirm_password');
            const i1 = document.getElementById('toggleIcon1');
            const i2 = document.getElementById('toggleIcon2');
            
            const isPassword = p1.type === 'password';
            
            p1.type = isPassword ? 'text' : 'password';
            p2.type = isPassword ? 'text' : 'password';
            
            const newClass = isPassword ? 'fa-eye-slash' : 'fa-eye';
            i1.className = `fas ${newClass}`;
            i2.className = `fas ${newClass}`;
        }
    </script>
</body>
</html>
