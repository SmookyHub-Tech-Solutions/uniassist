<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_page.php';
if (current_user()) redirect(home_for(current_user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $name = trim($_POST['name'] ?? ''); $matric = strtoupper(trim($_POST['matric'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
    $err = null;
    if ($name === '' || strlen($name) > 100) $err = 'Enter your full name.';
    elseif (!preg_match('#^MAAUN/\d{2}/[A-Z]{2,4}/\d{3,4}$#', $matric)) $err = 'Matric format should look like MAAUN/23/CSC/049.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
    elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
    elseif (q('SELECT 1 FROM users WHERE matric_number=? OR email=?', [$matric, $email])->fetch()) $err = 'Matric number or email already registered.';
    if ($err) { flash($err); redirect('register.php'); }
    q('INSERT INTO users(name,matric_number,email,password,role) VALUES(?,?,?,?,?)', [$name, $matric, $email, password_hash($pw, PASSWORD_DEFAULT), 'student']);
    $nid = (int)db()->lastInsertId();
    audit('auth.register', 'user', $nid, "$name <$email> ($matric)", ['id' => $nid, 'name' => $name, 'role' => 'student']);
    flash('Account created. You can log in now.', 'success');
    redirect('login.php');
}
auth_top('Register', 'Create your account', 'Student registration');
?>
<form method="post"><?= csrf_field() ?>
  <?php field('name', 'Full name'); field('matric', 'Matric number (MAAUN/23/CSC/049)'); field('email', 'Email', 'email'); field('password', 'Password (min 8 chars)', 'password'); ?>
  <button class="w-full rounded-xl bg-brand hover:bg-blue-700 text-white font-semibold py-2.5 text-sm shadow-sm transition active:scale-[.99]">Register</button>
</form>
<p class="text-sm text-slate-500 mt-4">Already registered? <a class="text-brand font-medium" href="<?= url('login.php') ?>">Login</a></p>
<?php auth_bottom();
