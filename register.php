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
    elseif (substr($email, -strlen('@' . STUDENT_EMAIL_DOMAIN)) !== '@' . STUDENT_EMAIL_DOMAIN) $err = 'Use your MAAUN school email (@' . STUDENT_EMAIL_DOMAIN . ').';
    elseif (($pwErr = password_check($pw)) !== null) $err = $pwErr;
    elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'Passwords do not match.';
    elseif (q('SELECT 1 FROM users WHERE matric_number=? OR email=?', [$matric, $email])->fetch()) $err = 'Matric number or email already registered.';
    if ($err) { flash($err); redirect('register.php'); }
    q('INSERT INTO users(name,matric_number,email,password,role) VALUES(?,?,?,?,?)', [$name, $matric, $email, password_hash($pw, PASSWORD_DEFAULT), 'student']);
    $nid = (int)db()->lastInsertId();
    audit('auth.register', 'user', $nid, "$name <$email> ($matric)", ['id' => $nid, 'name' => $name, 'role' => 'student']);
    flash('Account created. You can log in now.', 'success');
    redirect('login.php');
}
auth_top('Register', 'Create your account', 'Student registration · school email required');
?>
<form method="post" id="regForm" novalidate><?= csrf_field() ?>
  <?php field('name', 'Full name'); field('matric', 'Matric number (MAAUN/23/CSC/049)'); ?>
  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">School email
    <input id="regEmail" name="email" type="email" required placeholder="you@<?= e(STUDENT_EMAIL_DOMAIN) ?>"
      class="mt-1.5 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition"></label>
  <p id="emailHint" class="text-xs mt-1 mb-3 text-slate-400">Must end with @<?= e(STUDENT_EMAIL_DOMAIN) ?></p>
  <?php field('password', 'Password', 'password'); ?>
  <ul id="pwList" class="text-xs space-y-1.5 mb-3 -mt-1">
    <li data-rule="len" class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="dot">○</span>At least <?= PW_MIN_LEN ?> characters</li>
    <li data-rule="lower" class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="dot">○</span>A lowercase letter (a–z)</li>
    <li data-rule="upper" class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="dot">○</span>An uppercase letter (A–Z)</li>
    <li data-rule="digit" class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="dot">○</span>A number (0–9)</li>
    <li data-rule="match" class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="dot">○</span>Passwords match</li>
  </ul>
  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Confirm password
    <input id="regPw2" name="password2" type="password" required autocomplete="new-password"
      class="mt-1.5 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition"></label>
  <button id="regBtn" class="w-full rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white font-semibold py-2.5 text-sm shadow-sm transition active:scale-[.99] disabled:opacity-50 disabled:cursor-not-allowed">Register</button>
</form>
<script>
(function(){
  var pw = document.querySelector('input[name="password"]'), pw2 = document.getElementById('regPw2'),
      email = document.getElementById('regEmail'), hint = document.getElementById('emailHint'),
      btn = document.getElementById('regBtn'), form = document.getElementById('regForm'),
      domain = <?= json_encode('@' . STUDENT_EMAIL_DOMAIN) ?>;
  var items = {}; document.querySelectorAll('#pwList li').forEach(function(li){ items[li.dataset.rule] = li; });
  function set(rule, ok){
    var li = items[rule]; if(!li) return;
    li.className = 'flex items-center gap-2 ' + (ok ? 'text-green-600 dark:text-green-400 font-medium' : 'text-slate-500 dark:text-slate-400');
    li.querySelector('.dot').textContent = ok ? '●' : '○';
    return ok;
  }
  function check(){
    var v = pw.value, ok = true;
    ok = set('len', v.length >= <?= PW_MIN_LEN ?>) && ok;
    ok = set('lower', /[a-z]/.test(v)) && ok;
    ok = set('upper', /[A-Z]/.test(v)) && ok;
    ok = set('digit', /[0-9]/.test(v)) && ok;
    ok = set('match', v !== '' && v === pw2.value) && ok;
    var em = email.value.trim().toLowerCase(), emOk = em !== '' && em.endsWith(domain);
    hint.className = 'text-xs mt-1 mb-3 ' + (!email.value ? 'text-slate-400' : (emOk ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'));
    hint.textContent = !email.value ? ('Must end with ' + domain) : (emOk ? '✓ School email looks good' : ('✗ Must end with ' + domain));
    email.setCustomValidity(emOk ? '' : 'Use your school email (' + domain + ')');
    pw.setCustomValidity(ok ? '' : 'Password does not meet all requirements.');
    btn.disabled = !(ok && emOk);
    return ok && emOk;
  }
  ['input','change'].forEach(function(ev){ pw.addEventListener(ev, check); pw2.addEventListener(ev, check); email.addEventListener(ev, check); });
  form.addEventListener('submit', function(e){ if(!check()){ e.preventDefault(); } });
  check();
})();
</script>
<p class="text-sm text-slate-500 dark:text-slate-400 mt-4">Already registered? <a class="text-brand dark:text-blue-400 font-medium" href="<?= url('login.php') ?>">Login</a></p>
<?php auth_bottom();
