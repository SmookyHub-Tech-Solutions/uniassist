<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('student');
$id = (int)($_GET['id'] ?? 0);
$t = q('SELECT * FROM support_tickets WHERE id=? AND student_id=?', [$id, $u['id']])->fetch();   // ownership check
if (!$t) { http_response_code(404); exit('Ticket not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $msg = trim($_POST['message'] ?? '');
    if ($t['status'] === 'CLOSED') flash('This ticket is closed. Start a new enquiry from the AI Assistant.');
    elseif ($msg === '') flash('Write a message first.');
    else {
        add_ticket_message($id, (int)$u['id'], 'student', mb_substr($msg, 0, 2000));
        if ($t['status'] === 'SOLVED') {
            q("UPDATE support_tickets SET status='IN PROGRESS', resolved_at=NULL WHERE id=?", [$id]);
            audit('ticket.reopen', 'support_tickets', $id, $t['ticket_number']);
        }
        audit('ticket.reply', 'support_tickets', $id, $t['ticket_number']);
        flash('Reply sent.', 'success');
    }
    redirect('student/ticket.php?id=' . $id);
}
layout_top($t['ticket_number'], 'tickets');
?>
<a href="<?= url('student/tickets.php') ?>" class="text-sm text-brand">← All tickets</a>
<div class="flex items-start justify-between gap-3 my-4">
  <div><div class="text-xs text-slate-400"><?= e($t['ticket_number']) ?> · Created <?= e(fmt_date($t['created_at'])) ?></div>
  <h1 class="text-xl font-bold text-navy"><?= e($t['subject']) ?></h1></div>
  <?= status_badge($t['status']) ?>
</div>
<?php render_transcript($t['conversation_id'] ? (int)$t['conversation_id'] : null); render_thread($id, 'student'); ?>
<?php if ($t['status'] !== 'CLOSED'): reply_form('Send reply'); else: ?>
  <p class="text-sm text-slate-500">This ticket is closed.</p>
<?php endif; layout_bottom();
