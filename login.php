<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_page.php';
if (current_user()) redirect(home_for(current_user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $id = trim($_POST['identifier'] ?? '');
    $u = q('SELECT * FROM users WHERE matric_number = ? OR email = ?', [strtoupper($id), strtolower($id)])->fetch();
    if ($u && $u['status'] === 'active' && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        audit('auth.login', 'user', $u['id'], $u['email']);
        redirect(home_for($u['role']));
    }
    flash('Invalid login details.');
    audit('auth.login_failed', 'user', null, mb_substr($id, 0, 120));
    redirect('login.php');
}
auth_top('Login', 'Welcome back', 'Log in with your matric number or email');
?>
<form method="post"><?= csrf_field() ?>
  <?php field('identifier', 'Matric number or email'); field('password', 'Password', 'password'); ?>
  <button class="w-full rounded-xl bg-brand hover:bg-blue-700 text-white font-semibold py-2.5 text-sm shadow-sm transition active:scale-[.99]">Login</button>
</form>
<p class="text-sm text-slate-500 mt-4">New student? <a class="text-brand font-medium" href="<?= url('register.php') ?>">Create an account</a></p>
<?php auth_bottom();
