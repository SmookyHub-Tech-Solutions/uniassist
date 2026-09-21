<?php
require_once __DIR__ . '/db.php';

/* ---------- Audit log: who did what, when ---------- */
function audit_log_table(): void {
    static $done = false;
    if ($done) return; $done = true;
    db()->exec("CREATE TABLE IF NOT EXISTS audit_log(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      actor_id INTEGER REFERENCES users(id),
      actor_name TEXT, actor_role TEXT,
      action TEXT NOT NULL, entity TEXT, entity_id TEXT,
      details TEXT, ip TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
}

/**
 * Record an auditable event. Never breaks the request if logging fails.
 * $actor overrides the session user (e.g. self-registration, failed logins).
 */
function audit(string $action, ?string $entity = null, $entityId = null, ?string $details = null, ?array $actor = null): void {
    try {
        audit_log_table();
        if ($actor === null && isset($_SESSION['uid'])) {
            $actor = q('SELECT id,name,role FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null;
        }
        q('INSERT INTO audit_log(actor_id,actor_name,actor_role,action,entity,entity_id,details,ip) VALUES(?,?,?,?,?,?,?,?)',
            [$actor['id'] ?? null, $actor['name'] ?? null, $actor['role'] ?? null,
             $action, $entity, $entityId !== null ? (string)$entityId : null,
             $details !== null ? mb_substr($details, 0, 500) : null,
             $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { /* logging must never break the app */ }
}
