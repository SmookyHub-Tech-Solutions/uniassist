<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('student');
$id = (int)($_GET['id'] ?? 0);
$c = q('SELECT * FROM conversations WHERE id=? AND user_id=?', [$id, $u['id']])->fetch();
if (!$c) { http_response_code(404); exit('Conversation not found.'); }
$tk = q('SELECT id,ticket_number,status FROM support_tickets WHERE conversation_id=?', [$id])->fetch();
layout_top('Conversation', 'history');
?>
<a href="<?= url('student/history.php') ?>" class="text-sm text-brand dark:text-blue-400">← History</a>
<div class="flex items-center justify-between my-4">
  <h1 class="text-xl font-bold text-navy dark:text-white"><?= e(fmt_date($c['created_at'])) ?></h1>
  <?php if ($tk): ?><a href="<?= url('student/ticket.php?id=' . $tk['id']) ?>" class="text-sm text-brand dark:text-blue-400"><?= e($tk['ticket_number']) ?> <?= status_badge($tk['status']) ?></a><?php endif; ?>
</div>
<?php render_transcript($id); layout_bottom();
