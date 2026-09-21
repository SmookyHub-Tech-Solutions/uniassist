<?php
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
    static $u = false;
    if ($u === false) {
        $u = isset($_SESSION['uid'])
            ? (q('SELECT id,name,matric_number,email,role,status FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null)
            : null;
    }
    return $u;
}
function require_role(string ...$roles): array {
    $u = current_user();
    if (!$u || $u['status'] !== 'active') redirect('login.php');
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('Access denied.'); }
    return $u;
}
function home_for(string $role): string {
    return ['student' => 'student/dashboard.php', 'support' => 'support/dashboard.php', 'admin' => 'admin/dashboard.php'][$role] ?? 'login.php';
}
