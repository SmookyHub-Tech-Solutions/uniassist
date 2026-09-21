<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('admin');

audit_log_table();
$actions = q('SELECT DISTINCT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
$roles = ['admin', 'support', 'student'];

$fAction = in_array($_GET['action'] ?? '', $actions, true) ? $_GET['action'] : '';
$fRole = in_array($_GET['role'] ?? '', $roles, true) ? $_GET['role'] : '';
$fQ = trim($_GET['q'] ?? '');
$fFrom = trim($_GET['from'] ?? ''); $fTo = trim($_GET['to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 50;

$sql = 'SELECT * FROM audit_log WHERE 1=1'; $p = [];
if ($fAction) { $sql .= ' AND action=?'; $p[] = $fAction; }
if ($fRole) { $sql .= ' AND actor_role=?'; $p[] = $fRole; }
if ($fQ !== '') { $sql .= ' AND (actor_name LIKE ? OR details LIKE ? OR entity_id LIKE ?)'; $p = array_merge($p, ["%$fQ%", "%$fQ%", "%$fQ%"]); }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fFrom)) { $sql .= ' AND date(created_at) >= ?'; $p[] = $fFrom; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fTo)) { $sql .= ' AND date(created_at) <= ?'; $p[] = $fTo; }

$total = (int)q("SELECT COUNT(*) FROM ($sql)", $p)->fetchColumn();
$pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$rows = q($sql . ' ORDER BY id DESC LIMIT ? OFFSET ?', [...$p, $per, ($page - 1) * $per])->fetchAll();

$tone = fn(string $a) => str_starts_with($a, 'auth.') ? 'bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
  : (str_contains($a, 'delete') || str_contains($a, 'login_failed') ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'
  : (str_contains($a, 'create') || str_contains($a, 'register') ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300'
  : (str_contains($a, 'ticket') ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300')));
$qs = function (array $over = []) {
    $query = $_GET;
    foreach ($over as $k => $v) { if ($v === '' || $v === null) unset($query[$k]); else $query[$k] = $v; }
    return http_build_query($query);
};

layout_top('Audit Log', 'audit');
$in = 'rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition';
page_head('Audit Log', 'Every sign-in, admin change and ticket event · ' . number_format($total) . ' entr' . ($total === 1 ? 'y' : 'ies') . ', newest first');
?>
<form class="flex flex-wrap gap-2 mb-5">
  <input name="q" value="<?= e($fQ) ?>" placeholder="Search actor, details, id" class="<?= $in ?> w-56">
  <select name="action" class="<?= $in ?>"><option value="">All actions</option>
    <?php foreach ($actions as $a): ?><option <?= $fAction === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select>
  <select name="role" class="<?= $in ?>"><option value="">All roles</option>
    <?php foreach ($roles as $r): ?><option <?= $fRole === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
  <input type="date" name="from" value="<?= e($fFrom) ?>" class="<?= $in ?>"><input type="date" name="to" value="<?= e($fTo) ?>" class="<?= $in ?>">
  <button class="rounded-xl bg-navy hover:bg-slate-800 text-white px-4 text-sm font-semibold transition">Filter</button>
  <a href="<?= url('admin/audit.php') ?>" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-800 px-4 py-2 text-sm font-medium shadow-sm transition">Clear</a>
</form>
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card overflow-x-auto">
<table class="data-table w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-left"><tr>
  <th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold whitespace-nowrap">Time</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Actor</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Action</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Entity</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">Details</th><th class="px-3 py-2.5 text-[11px] uppercase tracking-wider font-semibold">IP</th>
</tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr class="border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-800 align-top">
    <td class="p-3 whitespace-nowrap text-slate-500 dark:text-slate-400"><?= e(fmt_date($r['created_at'])) ?></td>
    <td class="p-3"><span class="font-medium"><?= e($r['actor_name'] ?? '—') ?></span>
      <?php if ($r['actor_role']): ?><div class="text-xs text-slate-400"><?= e($r['actor_role']) ?></div><?php endif; ?></td>
    <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap <?= $tone($r['action']) ?>"><?= e($r['action']) ?></span></td>
    <td class="p-3 text-slate-500 dark:text-slate-400"><?= e(trim(($r['entity'] ?? '') . ($r['entity_id'] !== null ? ' #' . $r['entity_id'] : '')) ?: '—') ?></td>
    <td class="p-3 text-slate-600 dark:text-slate-300 max-w-md"><?= e($r['details'] ?? '—') ?></td>
    <td class="p-3 text-slate-400"><?= e($r['ip'] ?? '—') ?></td>
  </tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="p-6 text-center text-slate-500 dark:text-slate-400">No audit entries match these filters.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($pages > 1): ?>
<div class="flex items-center gap-2 mt-4 text-sm">
  <?php if ($page > 1): ?><a class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5" href="?<?= e($qs(['page' => $page - 1])) ?>">← Prev</a><?php endif; ?>
  <span class="text-slate-500 dark:text-slate-400">Page <?= $page ?> of <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5" href="?<?= e($qs(['page' => $page + 1])) ?>">Next →</a><?php endif; ?>
</div>
<?php endif; ?>
<?php layout_bottom();
