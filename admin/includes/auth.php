<?php
// admin/includes/auth.php

// Load config FIRST — needed for SESSION_* constants
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => SESSION_COOKIE_DOMAIN,     // '' = host-only
        'secure'   => SESSION_COOKIE_SECURE,     // true
        'httponly' => true,
        'samesite' => SESSION_COOKIE_SAMESITE,   // 'Lax'
    ]);
    session_start();
}

// Now load the full includes (db.php also requires config — require_once dedupes)
require_once __DIR__ . '/../../includes/db.php';

/**
 * Redirect to the admin login page — absolute URL, always HTTPS.
 */
function redirect_to_login(string $reason = ''): void {
    $url = ADMIN_LOGIN_URL;
    if ($reason) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'reason=' . urlencode($reason);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Require an authenticated admin. Redirects to login if not.
 */
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        redirect_to_login();
    }
    // 2-hour idle timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
        session_unset();
        session_destroy();
        redirect_to_login('timeout');
    }
    $_SESSION['last_activity'] = time();
}

/* csrf_token(), csrf_check(), flash() — unchanged */
/**
 * Get or create the CSRF token for this session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the submitted CSRF token. Die on failure.
 */
function csrf_check(): void {
    if (empty($_POST['_csrf']) || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $_POST['_csrf'])) {
        http_response_code(403);
        die('Invalid CSRF token. Refresh and try again.');
    }
}

/**
 * Flash messages — store once, read once.
 */
function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $out = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $out;
}