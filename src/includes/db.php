<?php
/**
 * Database Connection - MySQL PDO
 * Auto-detects local vs production
 */

$db_host = 'localhost';
$db_name = 'paulgreb_adminability';
$db_charset = 'utf8mb4';

// Detect environment
$isLocal = (
    strpos($_SERVER['HTTP_HOST'] ?? '', '.test') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
);

if ($isLocal) {
    // Local development
    $db_user = 'root';
    $db_pass = '';
} else {
    // Production (BigScoots)
    $db_user = 'paulgreb_admin';
    $db_pass = 'TNxvGxrAFWPQ2WPPTN5tCFBG';
}

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
