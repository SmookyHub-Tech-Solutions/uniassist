<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $do = $_POST['do'] ?? ''; $id = (int)($_POST['id'] ?? 0); $err = null;
    $target = $id ? q('SELECT name,email,status FROM users WHERE id=?', [$id])->fetch() : null;
    if ($do === 'toggle') {
        if ($id === (int)$u['id']) $err = "You can't deactivate your own account.";
        else {
            q("UPDATE users SET status=CASE status WHEN 'active' THEN 'inactive' ELSE 'active' END WHERE id=?", [$id]);
            $to = q('SELECT status FROM users WHERE id=?', [$id])->fetchColumn();
            audit('user.toggle_status', 'user', $id, ($target['email'] ?? "#$id") . " → $to");
        }
    } elseif ($do === 'reset') {
        $pw = $_POST['password'] ?? '';
        if (($pwErr = password_check($pw)) !== null) $err = $pwErr;
        else {
            q('UPDATE users SET password=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
            audit('user.reset_password', 'user', $id, $target['email'] ?? "#$id");
        }
    } elseif ($do === 'create') {
        $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['support', 'admin'], true) ? $_POST['role'] : 'support';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Name and a valid email are required.';
        elseif (($pwErr = password_check($pw)) !== null) $err = $pwErr;
        elseif (q('SELECT 1 FROM users WHERE email=?', [$email])->fetch()) $err = 'Email already in use.';
        else {
            q('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role]);
            audit('user.create', 'user', (int)db()->lastInsertId(), "$name <$email> [$role]");
        }
    }
    flash($err ?? 'Saved.', $err ? 'error' : 'success'); redirect('admin/users.php' . (isset($_GET['role']) ? '?role=' . urlencode($_GET['role']) : ''));
}
$role = in_array($_GET['role'] ?? '', ['student', 'support', 'admin'], true) ? $_GET['role'] : '';
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM users WHERE 1=1'; $p = [];
if ($role) { $sql .= ' AND role=?'; $p[] = $role; }
if ($search !== '') { $sql .= ' AND (name LIKE ? OR email LIKE ? OR matric_number LIKE ?)'; $p = array_merge($p, ["%$search%", "%$search%", "%$search%"]); }
$rows = q($sql . ' ORDER BY role, name LIMIT 300', $p)->fetchAll();
layout_top('Users', 'users');
$in = 'rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition';
page_head('Users', count($rows) . ' accounts');
?>
<form class="flex flex-wrap gap-2 mb-4"><input name="q" value="<?= e($search) ?>" placeholder="Search name, email, matric" class="<?= $in ?> w-64">
  <select name="role" class="<?= $in ?>"><option value="">All roles</option><?php foreach (['student', 'support', 'admin'] as $r): ?><option <?= $role === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
  <button class="rounded-xl bg-navy hover:bg-slate-800 text-white px-4 text-sm font-semibold transition">Filter</button></form>
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card overflow-x-auto mb-6">
<table class="data-table w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-left"><tr>
  <th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Name</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Matric / Email</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Role</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Status</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Actions</th>
</tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr class="border-t border-slate-100 dark:border-slate-800 align-top hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-800">
    <td class="p-3 font-medium text-navy dark:text-white"><?= e($r['name']) ?></td><td class="p-3"><?= e($r['matric_number'] ?: '') ?><div class="text-xs text-slate-400"><?= e($r['email']) ?></div></td>
    <td class="p-3"><?= pill($r['role'], ['admin' => 'violet', 'support' => 'blue', 'student' => 'teal'][$r['role']] ?? 'slate') ?></td>
    <td class="p-3"><?= pill($r['status'], $r['status'] === 'active' ? 'green' : 'slate') ?></td>
    <td class="p-3"><div class="flex flex-wrap gap-2 items-center">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="text-brand dark:text-blue-400">Toggle</button></form>
      <form method="post" class="flex gap-1"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <input name="password" type="password" placeholder="New password" class="rounded border border-slate-300 dark:border-slate-600 px-2 py-1 text-xs w-32"><button class="text-brand dark:text-blue-400 text-xs">Reset</button></form>
    </div></td>
  </tr>
<?php endforeach; ?></tbody></table></div>
<h2 class="font-bold text-navy dark:text-white mb-2">Add support staff / admin</h2>
<form method="post" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card p-5 flex flex-wrap gap-2"><?= csrf_field() ?><input type="hidden" name="do" value="create">
  <input name="name" placeholder="Full name" required class="<?= $in ?>"><input name="email" type="email" placeholder="Email" required class="<?= $in ?>"><input name="password" type="password" placeholder="Password (8+, Aa1)" required class="<?= $in ?>">
  <select name="role" class="<?= $in ?>"><option value="support">Support staff</option><option value="admin">Administrator</option></select>
  <button class="rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white px-4 text-sm font-semibold shadow-sm transition">Create</button></form>
<?php layout_bottom();
