# GamingSpotHub — Changes Made

**Date:** February 21, 2026  
**Author:** Development Team  
**Version:** 1.1 — Authentication & Session Management Update

---

## Summary

This update implements a full user authentication system, session management, a universal navbar, and a premium UI/UX overhaul for all auth pages. Below is a detailed breakdown of every change.

---

## New Files Created

### Authentication Pages (`auth/`)

| File | Description |
|---|---|
| `auth/register.php` | User registration with email, full name, and password. Generates a verification token and sends a confirmation email via PHPMailer. Passwords are hashed using bcrypt. |
| `auth/login.php` | Login form that validates credentials with `password_verify()`. On success, stores `user_id`, `full_name`, `email`, and `role` in the PHP session. Redirects already-logged-in users to the homepage. |
| `auth/forgot_password.php` | Accepts an email address and generates a 1-hour reset token. Uses **anti-enumeration** — always shows the same success message whether the email exists or not, so attackers can't discover valid accounts. |
| `auth/reset_password.php` | Validates the reset token from the URL (`?token=xxx`), checks it hasn't expired using `NOW()` in MySQL, and lets the user set a new password. Clears the token after use. |
| `auth/verify.php` | Handles email verification links. Marks the user's `email_verified` column as `1` and clears the token. Shows animated success/error states. |
| `auth/logout.php` | Destroys the PHP session and redirects to the homepage. |

### Backend Includes (`includes/`)

| File | Description |
|---|---|
| `includes/session_helper.php` | Centralized session management. Contains: `isLoggedIn()`, `requireLogin()`, `requireRole($roles)`, `getCurrentUser()`, `getUserInitials()`, `getRoleBadge()`, `getBaseUrl()`. |
| `includes/navbar.php` | Universal, reusable navbar component. Uses `$base_url` (absolute URLs) so links work from any directory depth. Contains inline JavaScript for the user dropdown toggle. |
| `includes/mail_config.php` | SMTP configuration constants for PHPMailer (Gmail App Password). |
| `includes/mail_helper.php` | Two email functions: `sendVerificationEmail($email, $name, $token)` and `sendPasswordResetEmail($email, $name, $token)`. Both use branded HTML templates. |

### Database (`database/`)

| File | Description |
|---|---|
| `database/migration_auth.sql` | Migration script that adds auth-related columns to the `users` table: `email_verified`, `verification_token`, `verification_expires`, `reset_token`, `reset_expires`. |

### Styling (`assets/css/`)

| File | Description |
|---|---|
| `assets/css/auth.css` | Complete premium CSS for all auth pages. Features: animated floating orbs, grid overlay, split-screen layout (brand panel + form), glassmorphic cards, password strength meter, show/hide password toggle, micro-animations. |

### Vendor Libraries

| File | Description |
|---|---|
| `vendor/PHPMailer-6.9.1/` | PHPMailer library for sending SMTP emails through Gmail. |

---

## Modified Files

### `index.php`
- **Before:** 90+ lines of inline navbar HTML baked directly into the file.
- **After:** Single include line: `<?php include __DIR__ . '/includes/navbar.php'; ?>`. Added `session_helper.php` require at the top.

### `admin.php`
- **Before:** No access protection. Top bar showed hardcoded "Admin User" text.
- **After:**
  - Added `requireRole(['owner', 'shopkeeper'])` — redirects unauthorized users to the login page.
  - Top bar dynamically shows the logged-in user's name, initials avatar, and role badge.
  - Added "Back to Site" link in the sidebar to navigate back to `index.php`.

### `assets/css/style.css`
- Added user dropdown styles scoped under `#mainNav` to override Bootstrap's default white dropdown styling.
- Includes: gradient avatar, glassmorphic dropdown panel, animated open/close, role badge, danger hover on "Sign Out".

### `assets/js/main.js`
- Removed the user dropdown toggle JavaScript (moved into `navbar.php` as inline script to keep the navbar self-contained).

### `includes/mail_config.php`
- Fixed `MAIL_FROM_EMAIL` to match `MAIL_USERNAME` — Gmail requires the "From" address to match the authenticated SMTP account.

---

## Architecture Decisions

### Why a Universal Navbar?
The navbar was extracted from `index.php` into `includes/navbar.php` so it can be reused on any page. All links use absolute `$base_url` paths (e.g., `http://localhost/GamingSpotHub/auth/login.php`) instead of relative paths, so the navbar works correctly whether included from `index.php` (root) or `auth/login.php` (subdirectory).

### Why MySQL `NOW()` Instead of PHP `date()`?
The password reset expiry was changed from PHP's `date('Y-m-d H:i:s', strtotime('+1 hour'))` to MySQL's `DATE_ADD(NOW(), INTERVAL 1 HOUR)`. This prevents timezone mismatches — PHP and MySQL may use different timezone settings, which could make tokens appear expired immediately.

### Why `#mainNav` Scoped CSS?
Bootstrap 5 applies its own styles to common class names like `.dropdown-item` and `.dropdown-menu`. Our custom dropdown (which is NOT a Bootstrap dropdown component) was getting overridden with white backgrounds and blue link colors. Scoping all rules under `#mainNav` and adding `!important` on key properties ensures our dark glassmorphic theme wins.

---

## Session Flow

```
User visits site → session_helper.php starts session
                 ↓
         isLoggedIn() check
        /              \
     TRUE              FALSE
      ↓                  ↓
Avatar + Dropdown    Login/Register buttons
(name, role, email)
      ↓
  Click dropdown:
  - Dashboard (admin only)
  - Sign Out
```

## Password Reset Flow

```
1. User clicks "Forgot Password"
2. Enters email → forgot_password.php
3. Token generated → stored in DB with DATE_ADD(NOW(), INTERVAL 1 HOUR)
4. Email sent with reset link containing token
5. User clicks link → reset_password.php?token=xxx
6. Token validated against DB: reset_token = ? AND reset_expires > NOW()
7. User sets new password → bcrypt hash stored
8. Token cleared from DB
```
