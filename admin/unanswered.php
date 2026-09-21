<?php
// =====================================================================
// admin/unanswered.php — questions that beat the chatbot (admins only).
// Every time the bot admits defeat it files the question here (repeats
// counted). Admins turn the good ones into knowledge-base entries (which
// teaches the bot) or dismiss/restore noise. Dismissals are audited.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('admin'); // Only admins past this line.
// ---- 1. Handle dismiss / restore buttons -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $to = ($_POST['do'] ?? '') === 'dismiss' ? 'dismissed' : 'new';
    q('UPDATE unanswered_questions SET status=? WHERE id=?', [$to, (int)$_POST['id']]);
    audit('unanswered.' . ($to === 'dismissed' ? 'dismiss' : 'restore'), 'unanswered_questions', (int)$_POST['id']);
    redirect('admin/unanswered.php' . (($_GET['show'] ?? '') ? '?show=all' : ''));
}
$all = ($_GET['show'] ?? '') === 'all';
$rows = q('SELECT uq.*, u.matric_number FROM unanswered_questions uq LEFT JOIN users u ON u.id=uq.student_id ' . ($all ? '' : "WHERE uq.status='new' ") . 'ORDER BY uq.frequency DESC, uq.id DESC')->fetchAll();
layout_top('Unanswered Questions', 'unanswered');
page_head('Unanswered Questions', $all ? 'Every recorded question' : 'Questions the chatbot could not resolve',
  '<a class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-800 px-4 py-2 text-sm font-medium shadow-sm transition" href="?' . ($all ? '' : 'show=all') . '">' . ($all ? 'Show new only' : 'Show all') . '</a>');
?>
<div class="space-y-3">
<?php foreach ($rows as $r): ?>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card p-4">
    <div class="text-xs text-slate-400">Asked <?= (int)$r['frequency'] ?>× · <?= e($r['matric_number'] ?? '') ?> · <?= e(fmt_date($r['created_at'])) ?> · <?= pill($r['status'], $r['status'] === 'new' ? 'amber' : ($r['status'] === 'converted' ? 'green' : 'slate')) ?></div>
    <div class="font-semibold text-navy dark:text-white my-1"><?= e($r['question']) ?></div>
    <div class="flex gap-3 text-sm mt-2">
      <?php if ($r['status'] !== 'converted'): ?><a class="text-brand dark:text-blue-400 font-medium" href="<?= url('admin/kb_edit.php?from=' . $r['id']) ?>">Turn into KB entry</a><?php endif; ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button name="do" value="<?= $r['status'] === 'dismissed' ? 'restore' : 'dismiss' ?>" class="text-slate-600 dark:text-slate-300"><?= $r['status'] === 'dismissed' ? 'Restore' : 'Dismiss' ?></button></form>
    </div>
  </div>
<?php endforeach; if (!$rows) empty_state('Nothing here — you are all caught up.'); ?>
</div>
<?php layout_bottom();
