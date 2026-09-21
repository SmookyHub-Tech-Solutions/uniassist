<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $do = $_POST['do'] ?? ''; $err = null;
    $code = strtolower(trim($_POST['code'] ?? '')); $name = trim($_POST['name'] ?? '');
    try {
        if ($do === 'add_cat') {
            if (!preg_match('/^[a-z0-9_]{2,40}$/', $code) || $name === '') $err = 'Code (lowercase letters, numbers, underscore) and name are required.';
            else {
                q('INSERT INTO categories(code,name,icon) VALUES(?,?,?)', [$code, mb_substr($name, 0, 60), mb_substr(trim($_POST['icon'] ?? '') ?: '📌', 0, 8)]);
                audit('category.create', 'category', (int)db()->lastInsertId(), "$code · $name");
            }
        } elseif ($do === 'add_issue') {
            if (!preg_match('/^[a-z0-9_]{2,40}$/', $code) || $name === '') $err = 'Code (lowercase letters, numbers, underscore) and name are required.';
            else {
                $cid = (int)$_POST['category_id'];
                q('INSERT INTO issues(category_id,code,name) VALUES(?,?,?)', [$cid, $code, mb_substr($name, 0, 80)]);
                audit('issue.create', 'issue', (int)db()->lastInsertId(), "$code · $name (cat #$cid)");
            }
        } elseif ($do === 'toggle_cat') {
            q("UPDATE categories SET status=CASE status WHEN 'active' THEN 'inactive' ELSE 'active' END WHERE id=?", [(int)$_POST['id']]);
            audit('category.toggle', 'category', (int)$_POST['id']);
        } elseif ($do === 'toggle_issue') {
            q("UPDATE issues SET status=CASE status WHEN 'active' THEN 'inactive' ELSE 'active' END WHERE id=?", [(int)$_POST['id']]);
            audit('issue.toggle', 'issue', (int)$_POST['id']);
        } elseif ($do === 'rename_cat' && $name !== '') {
            q('UPDATE categories SET name=? WHERE id=?', [mb_substr($name, 0, 60), (int)$_POST['id']]);
            audit('category.rename', 'category', (int)$_POST['id'], $name);
        } elseif ($do === 'rename_issue' && $name !== '') {
            q('UPDATE issues SET name=? WHERE id=?', [mb_substr($name, 0, 80), (int)$_POST['id']]);
            audit('issue.rename', 'issue', (int)$_POST['id'], $name);
        }
    } catch (PDOException $ex) { $err = 'That code already exists.'; }
    flash($err ?? 'Saved.', $err ? 'error' : 'success'); redirect('admin/categories.php');
}
$cats = q('SELECT * FROM categories ORDER BY id')->fetchAll();
$issues = [];
foreach (q('SELECT * FROM issues ORDER BY id')->fetchAll() as $i) $issues[$i['category_id']][] = $i;
layout_top('Categories & Issues', 'categories');
$in = 'rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition';
page_head('Categories & Issues', count($cats) . ' categories');
?>
<div class="rounded-2xl border border-blue-200 bg-blue-50/70 dark:bg-blue-950/60 p-4 mb-5 text-sm text-slate-600 dark:text-slate-300 shadow-card">💡 The AI's list of possible intents is built from issue codes automatically. Only the built-in record issues (cgpa, gpa, view_result, missing_result, incorrect_result) have special database behaviour; every other issue shows its linked knowledge-base answer.</div>
<?php foreach ($cats as $c): ?>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card p-5 mb-4">
    <div class="flex flex-wrap items-center gap-3 mb-3">
      <form method="post" class="flex gap-2 items-center"><?= csrf_field() ?><input type="hidden" name="do" value="rename_cat"><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <span><?= e($c['icon']) ?></span><input name="name" value="<?= e($c['name']) ?>" class="<?= $in ?> font-semibold"><button class="text-sm text-brand dark:text-blue-400">Rename</button></form>
      <span class="text-xs text-slate-400 font-mono"><?= e($c['code']) ?></span>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle_cat"><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="text-xs px-2 py-0.5 rounded-full <?= $c['status'] === 'active' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' ?>"><?= e($c['status']) ?></button></form>
    </div>
    <div class="space-y-2 ml-2">
    <?php foreach ($issues[$c['id']] ?? [] as $i): ?>
      <div class="flex flex-wrap items-center gap-3">
        <form method="post" class="flex gap-2"><?= csrf_field() ?><input type="hidden" name="do" value="rename_issue"><input type="hidden" name="id" value="<?= $i['id'] ?>">
          <input name="name" value="<?= e($i['name']) ?>" class="<?= $in ?>"><button class="text-sm text-brand dark:text-blue-400">Rename</button></form>
        <span class="text-xs text-slate-400"><?= e($i['code']) ?></span>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle_issue"><input type="hidden" name="id" value="<?= $i['id'] ?>">
          <button class="text-xs px-2 py-0.5 rounded-full <?= $i['status'] === 'active' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' ?>"><?= e($i['status']) ?></button></form>
      </div>
    <?php endforeach; ?>
    </div>
    <form method="post" class="flex flex-wrap gap-2 mt-4"><?= csrf_field() ?><input type="hidden" name="do" value="add_issue"><input type="hidden" name="category_id" value="<?= $c['id'] ?>">
      <input name="code" placeholder="issue_code" class="<?= $in ?>"><input name="name" placeholder="Issue name" class="<?= $in ?>"><button class="rounded-lg bg-navy text-white px-3 text-sm">+ Add issue</button></form>
  </div>
<?php endforeach; ?>
<form method="post" class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-600 rounded-2xl p-5 flex flex-wrap gap-2"><?= csrf_field() ?><input type="hidden" name="do" value="add_cat">
  <input name="icon" placeholder="Icon" class="<?= $in ?> w-20"><input name="code" placeholder="category_code" class="<?= $in ?>"><input name="name" placeholder="Category name" class="<?= $in ?>"><button class="rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white px-4 py-2 text-sm font-semibold shadow-sm transition">+ Add category</button></form>
<?php layout_bottom();
