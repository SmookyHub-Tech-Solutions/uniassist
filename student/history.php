<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('student');
$rows = q("SELECT c.*, cat.name cat,
   (SELECT message FROM messages m WHERE m.conversation_id=c.id AND m.sender='student' ORDER BY m.id LIMIT 1) first_q,
   (SELECT COUNT(*) FROM messages m WHERE m.conversation_id=c.id) n
   FROM conversations c LEFT JOIN categories cat ON cat.id=c.category_id
   WHERE c.user_id=? AND EXISTS(SELECT 1 FROM messages m WHERE m.conversation_id=c.id AND m.sender='student')
   ORDER BY c.id DESC", [$u['id']])->fetchAll();
layout_top('Conversations', 'history');
?>
<h1 class="text-2xl font-bold text-navy dark:text-white mb-5">Conversation History</h1>
<div class="space-y-3">
<?php foreach ($rows as $c): ?>
  <a href="<?= url('student/conversation.php?id=' . $c['id']) ?>" class="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-brand rounded-2xl p-4">
    <div class="text-xs text-slate-400"><?= e(fmt_date($c['created_at'])) ?><?= $c['cat'] ? ' · ' . e($c['cat']) : '' ?> · <?= (int)$c['n'] ?> messages</div>
    <div class="font-semibold text-navy dark:text-white truncate"><?= e($c['first_q']) ?></div>
  </a>
<?php endforeach; if (!$rows): ?><div class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-600 rounded-2xl p-8 text-center text-sm text-slate-500 dark:text-slate-400">No conversations yet.</div><?php endif; ?>
</div>
<?php layout_bottom();
