<?php

// Base URL path — auto-detected so the app works on both:
//   http://lockedin.local/          (VirtualHost, root)
//   http://localhost/Lockedin/public/ (subfolder)
if (!defined('BASE_PATH')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Walk up to find the /public folder boundary
    $publicPos = strpos($scriptDir, '/Lockedin/public');
    if ($publicPos !== false) {
        define('BASE_PATH', substr($scriptDir, 0, $publicPos) . '/Lockedin/public');
    } else {
        define('BASE_PATH', rtrim($scriptDir, '/'));
    }
}
/**
 * config/database.php
 * Shared PDO connection — integration owner / team leader.
 *
 * This file creates $pdo for use by all services and repositories.
 * Never expose this file through a public URL.
 *
 * Configure:
 *   DB_HOST — MySQL host (default: 127.0.0.1)
 *   DB_PORT — MySQL port (default: 3306)
 *   DB_NAME — database name (workspace_db)
 *   DB_USER — MySQL username
 *   DB_PASS — MySQL password
 */

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'workspace_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // Set the session timezone to UTC so stored DATETIMEs are consistent
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    // Do not expose the DSN or credentials in the error message
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('The application is temporarily unavailable. Please try again later.');
}
