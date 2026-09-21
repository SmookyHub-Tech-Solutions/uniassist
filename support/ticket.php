<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('support', 'admin');
$id = (int)($_GET['id'] ?? 0);
$t = q('SELECT t.*, s.name sname, s.matric_number, s.email semail, a.name aname FROM support_tickets t JOIN users s ON s.id=t.student_id LEFT JOIN users a ON a.id=t.assigned_to WHERE t.id=?', [$id])->fetch();
if (!$t) { http_response_code(404); exit('Ticket not found.'); }

$isOwner = $u['role'] === 'admin' || (int)$t['assigned_to'] === (int)$u['id'];
$next = ['OPEN' => [], 'IN PROGRESS' => ['SOLVED'], 'SOLVED' => ['CLOSED', 'IN PROGRESS'], 'CLOSED' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $do = $_POST['do'] ?? '';
    if ($do === 'accept') {
        if ($t['status'] === 'OPEN' && !$t['assigned_to']) {
            q("UPDATE support_tickets SET status='IN PROGRESS', assigned_to=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='OPEN' AND assigned_to IS NULL", [$u['id'], $id]);
            audit('ticket.accept', 'support_tickets', $id, $t['ticket_number']);
            flash('Ticket accepted.', 'success');
        } else flash('This ticket was already accepted.');
    } elseif (!$isOwner) {
        flash('Accept the ticket first, or it is assigned to someone else.');
    } elseif ($do === 'reply') {
        $msg = trim($_POST['message'] ?? '');
        if ($t['status'] === 'CLOSED') flash('Ticket is closed.');
        elseif ($msg === '') flash('Write a message first.');
        else {
            add_ticket_message($id, (int)$u['id'], 'staff', mb_substr($msg, 0, 2000));
            audit('ticket.reply', 'support_tickets', $id, $t['ticket_number']);
            flash('Reply sent.', 'success');
        }
    } elseif ($do === 'status') {
        $to = $_POST['to'] ?? '';
        if (in_array($to, $next[$t['status']], true)) {
            q('UPDATE support_tickets SET status=?, updated_at=CURRENT_TIMESTAMP, resolved_at=? WHERE id=?', [$to, $to === 'SOLVED' ? gmdate('Y-m-d H:i:s') : ($to === 'IN PROGRESS' ? null : $t['resolved_at']), $id]);
            audit('ticket.status', 'support_tickets', $id, $t['ticket_number'] . ": {$t['status']} → $to");
            flash("Status changed to $to.", 'success');
        } else flash('That status change is not allowed.');
    }
    redirect('support/ticket.php?id=' . $id);
}
layout_top($t['ticket_number'], 'tickets');
?>
<a href="<?= url('support/tickets.php') ?>" class="text-sm text-brand">← All tickets</a>
<div class="flex items-start justify-between gap-3 my-4">
  <div><div class="text-xs text-slate-400"><?= e($t['ticket_number']) ?> · <?= e($t['sname']) ?> (<?= e($t['matric_number']) ?>) · <?= e(fmt_date($t['created_at'])) ?></div>
  <h1 class="text-xl font-bold text-navy"><?= e($t['subject']) ?></h1>
  <div class="text-xs text-slate-500 mt-1">Assigned to: <?= e($t['aname'] ?? 'nobody yet') ?></div></div>
  <?= status_badge($t['status']) ?>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  <?php if ($t['status'] === 'OPEN' && !$t['assigned_to']): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="accept"><button class="rounded-lg bg-teal text-white px-4 py-2 text-sm font-semibold">Accept ticket</button></form>
  <?php endif; ?>
  <?php if ($isOwner): foreach ($next[$t['status']] as $to): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="status"><input type="hidden" name="to" value="<?= e($to) ?>">
      <button class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2 text-sm font-medium">Mark <?= e(ucwords(strtolower($to))) ?></button></form>
  <?php endforeach; endif; ?>
</div>

<?php if ($t['description']): ?><div class="bg-white border border-slate-200 rounded-2xl p-4 mb-5 text-sm text-slate-600"><?= e($t['description']) ?></div><?php endif; ?>
<?php render_transcript($t['conversation_id'] ? (int)$t['conversation_id'] : null); render_thread($id, 'staff'); ?>
<?php if ($isOwner && in_array($t['status'], ['IN PROGRESS', 'SOLVED'], true)) reply_form('Reply to student');
elseif ($t['status'] === 'OPEN') echo '<p class="text-sm text-slate-500">Accept this ticket to reply.</p>';
elseif ($t['status'] === 'CLOSED') echo '<p class="text-sm text-slate-500">This ticket is closed.</p>'; ?>
<?php layout_bottom();
