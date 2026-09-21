<?php
// =====================================================================
// login.php — the sign-in screen (public page, logged-out only).
// A student or staff member types their matric number (or email) plus
// password. If both match an ACTIVE account, the app remembers them
// (a session) and sends them to their own dashboard. Wrong details or
// a deactivated account = back to login with an error. Every attempt,
// good or bad, is written to the audit log for security.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_page.php';
if (current_user()) redirect(home_for(current_user()['role'])); // Already signed in? Go home.

// ---- 1. Handle the submitted form (runs only after clicking Login) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $id = trim($_POST['identifier'] ?? '');
    $u = q('SELECT * FROM users WHERE matric_number = ? OR email = ?', [strtoupper($id), strtolower($id)])->fetch();
    if ($u && $u['status'] === 'active' && password_verify($_POST['password'] ?? '', $u['password'])) {
        // Success: fresh session id (stops session-stealing), remember the
        // user, log it, and send them to their own dashboard.
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        audit('auth.login', 'user', $u['id'], $u['email']);
        redirect(home_for($u['role']));
    }
    // Failure (wrong details OR deactivated account): one generic message
    // (never reveal WHICH half was wrong — that helps attackers), log the
    // attempt, and bounce back to login.
    flash('Invalid login details.');
    audit('auth.login_failed', 'user', null, mb_substr($id, 0, 120));
    redirect('login.php');
}
auth_top('Login', 'Welcome back', 'Log in with your matric number or email');
?>
<form method="post"><?= csrf_field() ?>
  <?php field('identifier', 'Matric number or email'); field('password', 'Password', 'password', '', true); ?>
  <button class="w-full rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white font-semibold py-2.5 text-sm shadow-sm transition active:scale-[.99]">Login</button>
</form>
<p class="text-sm text-slate-500 dark:text-slate-400 mt-4">New student? <a class="text-brand dark:text-blue-400 font-medium" href="<?= url('register.php') ?>">Create an account</a></p>
<?php auth_bottom();
