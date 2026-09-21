<?php
// =====================================================================
// includes/auth.php — "who are you, and are you allowed in here?"
// Loaded by EVERY page. It starts the login session, times it out
// after inactivity, and offers three helpers: current_user() (who is
// visiting, or nothing for guests), require_role() (block anyone
// without the right job title), and home_for() (each role's start page).
// =====================================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/audit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
if (isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > SESSION_TIMEOUT) {
    session_unset(); session_destroy(); session_start();
    flash('Your session timed out. Please log in again.');
}
$_SESSION['last_active'] = time();

function current_user(): ?array {
    // Who is visiting right now? Reads the login session, fetches that
    // person's row, and remembers the answer for the rest of this page
    // load (so we don't ask the database the same question twice).
    static $u = false;
    if ($u === false) {
        $u = isset($_SESSION['uid'])
            ? (q('SELECT id,name,matric_number,email,role,status FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null)
            : null;
    }
    return $u;
}
function require_role(string ...$roles): array {
    // Bouncer for protected pages. Guests and deactivated accounts are
    // sent to login; logged-in users with the WRONG job title get a
    // "403 Access denied" stop sign. Allowed users get their data back.
    $u = current_user();
    if (!$u || $u['status'] !== 'active') redirect('login.php');
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('Access denied.'); }
    return $u;
}
function home_for(string $role): string {
    return ['student' => 'student/dashboard.php', 'support' => 'support/dashboard.php', 'admin' => 'admin/dashboard.php'][$role] ?? 'login.php';
}
