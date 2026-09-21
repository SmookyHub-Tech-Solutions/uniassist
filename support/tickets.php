<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('support', 'admin');
$allowed = ['OPEN', 'IN PROGRESS', 'SOLVED', 'CLOSED'];
$f = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : '';
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT t.*, s.name sname, s.matric_number, a.name aname FROM support_tickets t JOIN users s ON s.id=t.student_id LEFT JOIN users a ON a.id=t.assigned_to WHERE 1=1';
$p = [];
if ($f) { $sql .= ' AND t.status=?'; $p[] = $f; }
if ($search !== '') { $sql .= ' AND (t.ticket_number LIKE ? OR t.subject LIKE ? OR s.matric_number LIKE ?)'; $p = array_merge($p, ["%$search%", "%$search%", "%$search%"]); }
$rows = q($sql . ' ORDER BY t.id DESC LIMIT 200', $p)->fetchAll();
layout_top('Tickets', 'tickets');
$in = 'rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition';
page_head('Tickets', count($rows) . ' shown');
?>
<form class="flex flex-wrap gap-2 mb-5">
  <input name="q" value="<?= e($search) ?>" placeholder="Search ticket no., subject, matric" class="<?= $in ?> w-64">
  <select name="status" class="<?= $in ?>">
    <option value="">All statuses</option>
    <?php foreach ($allowed as $s): ?><option <?= $f === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
  </select>
  <button class="rounded-xl bg-navy hover:bg-slate-800 text-white px-4 text-sm font-semibold transition">Filter</button>
</form>
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card overflow-x-auto">
<table class="data-table w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-left"><tr><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Ticket</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Student</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Subject</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Assigned</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Status</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Created</th></tr></thead><tbody>
<?php foreach ($rows as $t): ?>
  <tr class="border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-800">
    <td class="p-3"><a class="text-brand dark:text-blue-400 font-medium" href="<?= url('support/ticket.php?id=' . $t['id']) ?>"><?= e($t['ticket_number']) ?></a></td>
    <td class="p-3"><?= e($t['matric_number']) ?></td><td class="p-3"><?= e($t['subject']) ?></td>
    <td class="p-3"><?= e($t['aname'] ?? '—') ?></td><td class="p-3"><?= status_badge($t['status']) ?></td><td class="p-3 whitespace-nowrap"><?= e(fmt_date($t['created_at'])) ?></td>
  </tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="p-6 text-center text-slate-500 dark:text-slate-400">No tickets found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php layout_bottom();
