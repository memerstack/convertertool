<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * HTML-escape a value.
 */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return the current script name (e.g. "index.php", "tool.php").
 */
function current_page(): string {
    return basename($_SERVER['SCRIPT_NAME'] ?? '');
}

/**
 * Return true if the given page is the current page.
 * Used to add aria-current="page" to nav links.
 */
function is_current(string $page): bool {
    return current_page() === $page;
}

/**
 * Safe "active nav" attribute helper.
 */
function nav_current(string $page): string {
    return is_current($page) ? ' aria-current="page"' : '';
}