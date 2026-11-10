<?php
// includes/db.php
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    die('Configuration missing. Copy config.example.php to config.php and fill in the values.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $ex) {
    http_response_code(500);
    error_log('DB error: ' . $ex->getMessage());
    die('Service temporarily unavailable.');
}

/**
 * Load all settings into an array.
 */
function load_settings(PDO $pdo): array {
    $out = [];
    foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $r) {
        $out[$r['setting_key']] = $r['setting_value'];
    }
    return $out;
}