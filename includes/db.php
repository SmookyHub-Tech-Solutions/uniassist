<?php
// =====================================================================
// includes/db.php — the app's ONLY connection to the database.
// Plain picture: a single SQLite file (database/uniassist.sqlite).
// db() opens it once and reuses that connection; q() runs a safe,
// injection-proof query (values travel separately from the SQL text,
// so typed input can never hijack the database). Every page uses q().
// =====================================================================
require_once __DIR__ . '/../config.php';

function db(): PDO {
    // Open the SQLite file the first time, then hand back the same
    // connection on every later call (opening it each time would be slow).
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement {
    // Run one safe query. Example: q('SELECT * FROM users WHERE id=?', [$id]).
    // The question marks are filled in by the database itself, so user
    // input can never break out and run its own SQL (this is what stops
    // "SQL injection" hacking). Returns the answer rows to the caller.
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
