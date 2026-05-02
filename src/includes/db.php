<?php
/**
 * Database Connection — SQLite PDO
 *
 * The DB file lives at data/adminability.db relative to this file.
 * For local dev: when PHP runs out of /dist (Homebrew Apache serves from there)
 * the DB file is excluded from builds, so we fall back to ../../src/data/ so
 * local dev shares the canonical src copy. On live, the fallback never triggers.
 */

$db_path = __DIR__ . '/../data/adminability.db';

if (!file_exists($db_path)) {
    $srcFallback = __DIR__ . '/../../src/data/adminability.db';
    if (file_exists($srcFallback)) {
        $db_path = $srcFallback;
    }
}

$pdo = new PDO("sqlite:$db_path", null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Enable WAL mode for better concurrent read/write performance
$pdo->exec('PRAGMA journal_mode=WAL');
// Enable foreign keys (off by default in SQLite)
$pdo->exec('PRAGMA foreign_keys=ON');
