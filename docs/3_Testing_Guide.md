# GamingSpotHub — Testing Guide

**Date:** February 21, 2026  
**Author:** Development Team

---

## Prerequisites

Before testing, make sure:

1. **XAMPP is running** — Apache and MySQL must be started.
2. **Database is set up** — The `gamingspothub` database exists with the `users` table.
3. **Migration is applied** — Run `database/migration_auth.sql` in phpMyAdmin.
4. **Email is configured** — `includes/mail_config.php` has valid Gmail App Password credentials.
5. **Base URL** — Access the site at `http://localhost/GamingSpotHub/`.

---

## Test 1: User Registration

### Steps
1. Go to `http://localhost/GamingSpotHub/auth/register.php`
2. Fill in:
   - **Full Name:** Test User
   - **Email:** a valid email you can check
   - **Password:** TestPass123!
   - **Confirm Password:** TestPass123!
3. Click **Create Account**

### Expected Results
| Check | Expected |
|---|---|
| Form submits | Loading spinner appears on the button |
| Success message | "Registration successful! Please check your email to verify your account." |
| Database | New row in `users` table with `email_verified = 0` and a `verification_token` |
| Email | Verification email arrives with a clickable link |
| Password stored | `password_hash` column contains a bcrypt hash (starts with `$2y$`) |

### Edge Cases to Test
- [ ] Submit with empty fields → should show "Please fill in all fields"
- [ ] Submit with mismatched passwords → should show "Passwords do not match"
- [ ] Submit with password < 8 chars → should show "Password must be at least 8 characters"
- [ ] Submit with an already-registered email → should show "Email address is already registered"
- [ ] Password strength meter updates in real-time as you type

---

## Test 2: Email Verification

### Steps
1. After registering, check your email inbox
2. Click the verification link (looks like: `http://localhost/GamingSpotHub/auth/verify.php?token=abc123...`)

### Expected Results
| Check | Expected |
|---|---|
| Valid token | Green checkmark animation + "Email Verified!" message |
| Database | `email_verified` changes to `1`, `verification_token` set to NULL |
| Invalid/reused token | Red X animation + "Invalid or expired verification link" |

### Edge Cases to Test
- [ ] Click the same link twice → second time should show "invalid or expired"
- [ ] Visit `verify.php` with no token → should show error state
- [ ] Visit `verify.php` with a random/fake token → should show error state

---

## Test 3: User Login

### Steps
1. Go to `http://localhost/GamingSpotHub/auth/login.php`
2. Enter the email and password you registered with
3. Click **Sign In**

### Expected Results
| Check | Expected |
|---|---|
| Correct credentials | Redirects to `index.php` |
| Navbar | Shows user avatar (initials), name, and role badge instead of Login/Register buttons |
| Session | `$_SESSION` contains `user_id`, `full_name`, `email`, `role` |
| Already logged in | Visiting `login.php` again should auto-redirect to homepage |

### Edge Cases to Test
- [ ] Wrong password → should show "Invalid email or password"
- [ ] Non-existent email → should show "Invalid email or password" (same message for security)
- [ ] Empty fields → should show "Please enter both email and password"
- [ ] Unverified email → should show "Please verify your email before logging in"

---

## Test 4: Navbar Dropdown (Logged In)

### Steps
1. Log in successfully
2. Look at the top-right of the navbar
3. Click on your avatar/name area

### Expected Results
| Check | Expected |
|---|---|
| Avatar | Round circle with gradient (teal→blue) showing your initials |
| Name | Your full name displayed next to the avatar |
| Role badge | Shows "GAMER", "STAFF", or "ADMIN" depending on role |
| Dropdown opens | Dark glassmorphic panel slides down with animation |
| Dropdown contents | Shows: avatar, name, email, divider, and "Sign Out" |
| Dashboard link | Only visible if role is `owner` or `shopkeeper` |
| Click outside | Dropdown closes |
| Sign Out | Redirects to homepage, navbar shows Login/Register buttons again |

### Testing Role Variants
To test different roles, update via phpMyAdmin:

```sql
-- Test as customer (no Dashboard link)
UPDATE users SET role = 'customer' WHERE email = 'your@email.com';

-- Test as shopkeeper (shows Dashboard link)
UPDATE users SET role = 'shopkeeper' WHERE email = 'your@email.com';

-- Test as owner (shows Dashboard link)
UPDATE users SET role = 'owner' WHERE email = 'your@email.com';
```

> **Important:** Log out and log back in after changing roles — the role is stored in the session at login time.

---

## Test 5: Forgot Password

### Steps
1. Go to `http://localhost/GamingSpotHub/auth/forgot_password.php`
2. Enter the email address of a registered account
3. Click **Send Reset Link**

### Expected Results
| Check | Expected |
|---|---|
| Valid email | Shows: "If an account with that email exists, we've sent a password reset link." |
| Invalid email | Shows the **same message** (anti-enumeration — attacker can't tell if email exists) |
| Database | `reset_token` populated, `reset_expires` set to NOW() + 1 hour |
| Email | Password reset email arrives with a clickable link |

### Edge Cases to Test
- [ ] Submit empty email → should show "Please enter your email address"
- [ ] Submit invalid format → should show "Please enter a valid email address"
- [ ] Submit non-registered email → should show the same success message (security)

---

## Test 6: Reset Password

### Steps
1. Click the reset link from the email (looks like: `http://localhost/GamingSpotHub/auth/reset_password.php?token=abc123...`)
2. Enter a new password and confirm it
3. Click **Reset Password**

### Expected Results
| Check | Expected |
|---|---|
| Valid token | Shows the reset form with user's name: "Hi [Name], choose a new password" |
| Success | Green checkmark + "Password Updated!" + "Sign In Now" button |
| Database | `password_hash` updated, `reset_token` and `reset_expires` set to NULL |
| Login | Can log in with the new password |
| Old password | Old password no longer works |

### Edge Cases to Test
- [ ] Expired token (wait 1 hour or manually set `reset_expires` to past) → shows "Link Expired"
- [ ] Invalid/fake token → shows "Link Expired"
- [ ] Reuse a token after successful reset → shows "Link Expired" (token is cleared)
- [ ] Password < 8 chars → shows error
- [ ] Passwords don't match → shows error
- [ ] Password strength meter updates as you type

---

## Test 7: Admin Page Protection

### Steps
1. **As a guest (not logged in):** go to `http://localhost/GamingSpotHub/admin.php`
2. **As a customer:** log in with a `customer` account, then go to `admin.php`
3. **As an owner/shopkeeper:** log in with an `owner` account, then go to `admin.php`

### Expected Results
| Scenario | Expected |
|---|---|
| Not logged in | Redirects to `auth/login.php` |
| Logged in as `customer` | Redirects to `auth/login.php` |
| Logged in as `shopkeeper` | Admin dashboard loads normally |
| Logged in as `owner` | Admin dashboard loads normally |
| Admin topbar | Shows logged-in user's name and role (not "Admin User") |
| Sidebar | "Back to Site" link at the bottom returns to homepage |

---

## Test 8: Logout

### Steps
1. Log in with any account
2. Open the navbar dropdown
3. Click **Sign Out**

### Expected Results
| Check | Expected |
|---|---|
| Redirect | Goes back to the homepage |
| Navbar | Shows "Login" and "Register" buttons (guest state) |
| Session | All session data is destroyed |
| Protected pages | Visiting `admin.php` now redirects to login |

---

## Test 9: Navbar Universality

### Steps
1. Include the navbar on a new page by adding at the top of any PHP file:
```php
<?php include __DIR__ . '/../includes/navbar.php'; ?>
```
   (adjust the relative path based on file location)

### Expected Results
| Check | Expected |
|---|---|
| Links work | All navbar links (Home, About, etc.) point to the correct sections on the homepage |
| Login/Register | Links go to the correct auth pages regardless of current page location |
| Dropdown | Works the same as on the homepage |
| No broken styles | Avatar gradient, dropdown animation, and glassmorphism all render correctly |

---

## Quick Database Verification Queries

Run these in phpMyAdmin to verify data:

```sql
-- Check all users and their auth status
SELECT user_id, full_name, email, role, email_verified,
       verification_token IS NOT NULL AS has_verify_token,
       reset_token IS NOT NULL AS has_reset_token,
       reset_expires
FROM users;

-- Check if a reset token is still valid
SELECT full_name, reset_expires, 
       CASE WHEN reset_expires > NOW() THEN 'VALID' ELSE 'EXPIRED' END AS status
FROM users 
WHERE reset_token IS NOT NULL;

-- Manually verify a user's email (skip email)
UPDATE users SET email_verified = 1, verification_token = NULL 
WHERE email = 'test@example.com';

-- Manually set a user's role
UPDATE users SET role = 'owner' WHERE email = 'test@example.com';
```

---

## Checklist Summary

| # | Test | Status |
|---|---|---|
| 1 | Registration | ☐ |
| 2 | Email Verification | ☐ |
| 3 | Login | ☐ |
| 4 | Navbar Dropdown | ☐ |
| 5 | Forgot Password | ☐ |
| 6 | Reset Password | ☐ |
| 7 | Admin Protection | ☐ |
| 8 | Logout | ☐ |
| 9 | Navbar Universality | ☐ |
