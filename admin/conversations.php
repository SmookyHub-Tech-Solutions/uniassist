<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('admin');
$view = (int)($_GET['id'] ?? 0);
layout_top('Conversations', 'conversations');
if ($view) {
    $c = q('SELECT c.*, u.name, u.matric_number FROM conversations c JOIN users u ON u.id=c.user_id WHERE c.id=?', [$view])->fetch();
    if (!$c) exit('Not found.');
    echo '<a href="' . url('admin/conversations.php') . '" class="text-sm text-brand">← All conversations</a>';
    echo '<h1 class="text-xl font-bold text-navy my-4">' . e($c['name']) . ' (' . e($c['matric_number']) . ') · ' . e(fmt_date($c['created_at'])) . '</h1>';
    $msgs = q('SELECT * FROM messages WHERE conversation_id=? ORDER BY id', [$view])->fetchAll();
    echo '<div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-2 max-w-3xl">';
    foreach ($msgs as $m) {
        $me = $m['sender'] === 'student';
        echo '<div class="flex ' . ($me ? 'justify-end' : '') . '"><div class="max-w-[85%]"><div class="whitespace-pre-line rounded-2xl px-3 py-2 text-sm ' . ($me ? 'bg-brand text-white' : 'bg-slate-50 border border-teal/30') . '">' . e($m['message']) . '</div>'
           . (!$me && $m['intent'] ? '<div class="text-[11px] text-slate-400 mt-0.5">intent: ' . e($m['intent']) . ($m['confidence'] !== null ? ' · ' . round($m['confidence'] * 100) . '%' : '') . '</div>' : '') . '</div></div>';
    }
    echo '</div>'; layout_bottom(); exit;
}
$rows = q("SELECT c.*, u.name, u.matric_number, (SELECT COUNT(*) FROM messages m WHERE m.conversation_id=c.id) n,
  (SELECT message FROM messages m WHERE m.conversation_id=c.id AND m.sender='student' ORDER BY m.id LIMIT 1) first_q
  FROM conversations c JOIN users u ON u.id=c.user_id WHERE EXISTS(SELECT 1 FROM messages m WHERE m.conversation_id=c.id AND m.sender='student') ORDER BY c.id DESC LIMIT 200")->fetchAll();
?>
<h1 class="text-2xl font-bold text-navy mb-4">Conversations</h1>
<div class="space-y-3">
<?php foreach ($rows as $c): ?>
  <a href="?id=<?= $c['id'] ?>" class="block bg-white border border-slate-200 hover:border-brand rounded-2xl p-4">
    <div class="text-xs text-slate-400"><?= e($c['matric_number']) ?> · <?= e(fmt_date($c['created_at'])) ?> · <?= (int)$c['n'] ?> messages</div>
    <div class="font-semibold text-navy truncate"><?= e($c['first_q']) ?></div></a>
<?php endforeach; if (!$rows): ?><p class="text-sm text-slate-500">No conversations yet.</p><?php endif; ?>
</div>
<?php layout_bottom();
