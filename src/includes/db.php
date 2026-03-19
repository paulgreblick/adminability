<?php
/**
 * Database Connection - SQLite PDO
 */

$db_path = __DIR__ . '/../data/adminability.db';

$pdo = new PDO("sqlite:$db_path", null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Enable WAL mode for better concurrent read/write performance
$pdo->exec('PRAGMA journal_mode=WAL');
// Enable foreign keys (off by default in SQLite)
$pdo->exec('PRAGMA foreign_keys=ON');
