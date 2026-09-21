<?php
// =====================================================================
// admin/kb.php — browse the knowledge base (admins only): the approved
// question/answer cards the chatbot quotes. Search or filter by
// category, then edit, flip active/inactive, or permanently delete an
// entry. ("Add entry" lives on kb_edit.php.) All changes are audited.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('admin'); // Only admins past this line.
// ---- 1. Handle delete / activate-deactivate buttons --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $id = (int)($_POST['id'] ?? 0);
    $entry = $id ? q('SELECT question FROM knowledge_base WHERE id=?', [$id])->fetch() : null;
    if (($_POST['do'] ?? '') === 'delete') {
        q('DELETE FROM knowledge_base WHERE id=?', [$id]); flash('Entry deleted.', 'success');
        audit('kb.delete', 'knowledge_base', $id, mb_substr($entry['question'] ?? '', 0, 120));
    }
    if (($_POST['do'] ?? '') === 'toggle') {
        q("UPDATE knowledge_base SET status=CASE status WHEN 'active' THEN 'inactive' ELSE 'active' END, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$id]);
        audit('kb.toggle', 'knowledge_base', $id, mb_substr($entry['question'] ?? '', 0, 120));
    }
    redirect('admin/kb.php');
}
$search = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0);
$sql = 'SELECT kb.*, c.name cat, i.name issue FROM knowledge_base kb JOIN categories c ON c.id=kb.category_id LEFT JOIN issues i ON i.id=kb.issue_id WHERE 1=1'; $p = [];
if ($search !== '') { $sql .= ' AND (kb.question LIKE ? OR kb.answer LIKE ? OR kb.keywords LIKE ?)'; $p = ["%$search%", "%$search%", "%$search%"]; }
if ($cat) { $sql .= ' AND kb.category_id=?'; $p[] = $cat; }
$rows = q($sql . ' ORDER BY kb.id DESC', $p)->fetchAll();
$cats = q('SELECT id,name FROM categories ORDER BY name')->fetchAll();
layout_top('Knowledge Base', 'kb');
page_head('Knowledge Base', count($rows) . ' entries',
  '<a href="' . url('admin/kb_edit.php') . '" class="rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white px-4 py-2 text-sm font-semibold shadow-sm transition">+ Add entry</a>');
$in = 'rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition';
?>
<form class="flex flex-wrap gap-2 mb-5">
  <input name="q" value="<?= e($search) ?>" placeholder="Search knowledge" class="<?= $in ?> w-64">
  <select name="cat" class="<?= $in ?>"><option value="0">All categories</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <button class="rounded-xl bg-navy hover:bg-slate-800 text-white px-4 text-sm font-semibold transition">Search</button>
</form>
<div class="space-y-3">
<?php foreach ($rows as $r): ?>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0"><div class="text-xs text-slate-400"><?= e($r['cat']) ?><?= $r['issue'] ? ' › ' . e($r['issue']) : ' › (no issue linked)' ?> · updated <?= e(fmt_date($r['updated_at'])) ?></div>
        <div class="font-semibold text-navy dark:text-white"><?= e($r['question']) ?></div>
        <p class="text-sm text-slate-600 dark:text-slate-300 mt-1 line-clamp-2"><?= e($r['answer']) ?></p></div>
      <?= pill($r['status'], $r['status'] === 'active' ? 'green' : 'slate') ?>
    </div>
    <div class="flex gap-3 mt-3 text-sm">
      <a class="text-brand dark:text-blue-400 font-medium" href="<?= url('admin/kb_edit.php?id=' . $r['id']) ?>">Edit</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="do" value="toggle"><button class="text-slate-600 dark:text-slate-300">Toggle status</button></form>
      <form method="post" onsubmit="return confirm('Delete this entry?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="do" value="delete"><button class="text-red-600 dark:text-red-400">Delete</button></form>
    </div>
  </div>
<?php endforeach; if (!$rows) empty_state('No entries found. Add the first one with the button above.'); ?>
</div>
<?php layout_bottom();
