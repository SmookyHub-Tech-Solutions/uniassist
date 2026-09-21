<?php
// =====================================================================
// student/conversation.php — replay ONE past chat (students only).
// Shows every message of the chat plus (when one grew out of it) a link
// to the resulting support ticket. The id in the address bar is checked
// against the logged-in student, so nobody can peek at anyone else's
// chats — strangers get a 404 "not found" as if it never existed.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('student'); // Only students past this line.
$id = (int)($_GET['id'] ?? 0);
// ---- 1. Load the chat ONLY if it belongs to this student ---------------
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
