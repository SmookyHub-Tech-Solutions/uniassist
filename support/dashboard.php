<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('support', 'admin');
$counts = ['OPEN' => 0, 'IN PROGRESS' => 0, 'SOLVED' => 0, 'CLOSED' => 0];
foreach (q('SELECT status, COUNT(*) n FROM support_tickets GROUP BY status')->fetchAll() as $r) $counts[$r['status']] = (int)$r['n'];
$mine = (int)q("SELECT COUNT(*) n FROM support_tickets WHERE assigned_to=? AND status='IN PROGRESS'", [$u['id']])->fetch()['n'];
$recent = q('SELECT t.*, s.name sname, s.matric_number FROM support_tickets t JOIN users s ON s.id=t.student_id ORDER BY t.id DESC LIMIT 8')->fetchAll();
layout_top('Support Dashboard', 'dashboard');
?>
<h1 class="text-2xl font-bold text-navy mb-5">Support Dashboard</h1>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
  <?php foreach ([['Open', $counts['OPEN'], 'text-amber-600'], ['In progress', $counts['IN PROGRESS'], 'text-brand'], ['Solved', $counts['SOLVED'], 'text-green-600'], ['Closed', $counts['CLOSED'], 'text-slate-600'], ['Assigned to me', $mine, 'text-teal']] as [$l, $n, $c]): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-4"><div class="text-xs text-slate-500"><?= $l ?></div><div class="text-3xl font-bold <?= $c ?>"><?= $n ?></div></div>
  <?php endforeach; ?>
</div>
<div class="flex items-center justify-between mb-3"><h2 class="font-semibold text-navy">Recent tickets</h2><a class="text-sm text-brand" href="<?= url('support/tickets.php') ?>">View all</a></div>
<div class="space-y-3">
<?php foreach ($recent as $t): ?>
  <a href="<?= url('support/ticket.php?id=' . $t['id']) ?>" class="block bg-white border border-slate-200 hover:border-brand rounded-2xl p-4">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0"><div class="text-xs text-slate-400"><?= e($t['ticket_number']) ?> · <?= e($t['matric_number']) ?></div><div class="font-semibold text-navy truncate"><?= e($t['subject']) ?></div></div>
      <?= status_badge($t['status']) ?>
    </div>
  </a>
<?php endforeach; if (!$recent): ?><p class="text-sm text-slate-500">No tickets yet.</p><?php endif; ?>
</div>
<?php layout_bottom();
