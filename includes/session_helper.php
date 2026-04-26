<?php
/**
 * Good Spot Gaming Hub - Session Helper
 *
 * Centralized session management, authentication, and CSRF protection.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/auth/login.php');
        exit;
    }
}

/**
 * Restrict access to specific roles.
 * @param array|string $roles Allowed role(s)
 */
function requireRole($roles) {
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header('Location: ' . getBaseUrl() . '/index.php');
        exit;
    }
}

/**
 * Get current user session data.
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id'   => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
        'role'      => $_SESSION['role']
    ];
}

/**
 * Get user initials for avatar (e.g. "Juan Dela Cruz" → "JC").
 */
function getUserInitials() {
    if (!isLoggedIn()) return '?';
    $name = $_SESSION['full_name'] ?? '';
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper($parts[0][0] . $parts[count($parts) - 1][0]);
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Get role display label.
 */
function getRoleBadge() {
    $role = $_SESSION['role'] ?? 'customer';
    $labels = [
        'owner'      => 'Owner',
        'shopkeeper' => 'Shopkeeper',
        'customer'   => 'Gamer'
    ];
    return $labels[$role] ?? ucfirst($role);
}

/**
 * Get base URL for redirects.
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/GamingSpotHub';
}

// ============================================================================
// CSRF PROTECTION
// ============================================================================

/**
 * Return (and lazily create) the per-session CSRF token.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden <input> carrying the CSRF token.
 * Place inside every <form> that posts to admin.php.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the CSRF token from the current POST request.
 * Returns true on success. On failure, populates $message/$messageType and returns false.
 */
function verifyCsrf(string &$message, string &$messageType): bool {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        $message     = 'Security check failed — please reload the page and try again.';
        $messageType = 'error';
        return false;
    }
    return true;
}
