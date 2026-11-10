<?php
// includes/config.php — production
// Shared by the public site (convertertool.in) and admin subdomain
// (admin.convertertool.in). Both read from the same database.

/* ═══════════════════════════════════════════════════════════
   Database
   ═══════════════════════════════════════════════════════════ */
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_convertertool');
define('DB_USER', 'u123456_admin');
define('DB_PASS', 'your-real-password');

/* ═══════════════════════════════════════════════════════════
   Public site (main domain)
   ═══════════════════════════════════════════════════════════ */
define('SITE_URL',  'https://convertertool.in');
define('BASE_PATH', '/');

/* ═══════════════════════════════════════════════════════════
   Admin subdomain
   ═══════════════════════════════════════════════════════════
   The admin panel is reachable ONLY at admin.convertertool.in.
   The public domain blocks /admin/ via .htaccess.
   ═══════════════════════════════════════════════════════════ */
define('ADMIN_URL',  'https://admin.convertertool.in');
define('ADMIN_HOST', 'admin.convertertool.in');

/* ═══════════════════════════════════════════════════════════
   Admin URLs — used by auth.php for redirects
   ═══════════════════════════════════════════════════════════ */
define('ADMIN_LOGIN_URL',     ADMIN_URL . '/index.php');
define('ADMIN_DASHBOARD_URL', ADMIN_URL . '/dashboard.php');

/* ═══════════════════════════════════════════════════════════
   Session cookie policy
   ═══════════════════════════════════════════════════════════
   We intentionally use a HOST-ONLY cookie (empty 'domain').
   This means:
     - On admin.convertertool.in, PHPSESSID is valid ONLY on that subdomain.
     - On convertertool.in, no admin session cookie exists at all.
     - An XSS on any public tool page cannot read/steal the admin session.

   Do NOT set 'domain' => '.convertertool.in' unless you have a
   specific reason to share the session across both hosts. If you
   ever need shared login (SSO), implement it as a separate
   mechanism (e.g. a signed JWT in a secure cookie), not as a
   shared PHP session.
   ═══════════════════════════════════════════════════════════ */
define('SESSION_COOKIE_DOMAIN', '');  // host-only — do not change
define('SESSION_COOKIE_SECURE', true); // HTTPS only
define('SESSION_COOKIE_SAMESITE', 'Lax');