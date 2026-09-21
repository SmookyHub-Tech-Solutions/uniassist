<?php
// =====================================================================
// student/tickets.php — "My Tickets": every support ticket THIS student
// ever raised (students only), newest first. Tickets are born in the AI
// Assistant when the bot can't resolve something; this page just lists
// and links them. An empty mailbox gets a friendly empty-state card.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php'; // For status colours.
$u = require_role('student'); // Only students past this line.
// ---- 1. Fetch only this student's tickets, newest first ----------------
$rows = q('SELECT t.*, c.name cat FROM support_tickets t LEFT JOIN categories c ON c.id=t.category_id WHERE t.student_id=? ORDER BY t.id DESC', [$u['id']])->fetchAll();
layout_top('My Tickets', 'tickets');
page_head('My Tickets', 'Create tickets from the AI Assistant when it can\'t resolve your enquiry.');
?>
<div class="space-y-3">
<?php foreach ($rows as $t): ?>
  <a href="<?= url('student/ticket.php?id=' . $t['id']) ?>" class="lift block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 hover:border-brand rounded-2xl p-4 shadow-card">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0"><div class="text-xs text-slate-400"><?= e($t['ticket_number']) ?> · <?= e(fmt_date($t['created_at'])) ?></div>
      <div class="font-semibold text-navy dark:text-white truncate"><?= e($t['subject']) ?></div></div>
      <?= status_badge($t['status']) ?>
    </div>
  </a>
<?php endforeach; if (!$rows) empty_state('No tickets yet.'); ?>
</div>
<?php layout_bottom();
