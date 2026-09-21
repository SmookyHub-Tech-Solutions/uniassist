<?php
// =====================================================================
// admin/dashboard.php — the admin's control tower (admins only).
// Plain picture: headline counts (students, chats, tickets...), four
// performance rates (resolution, escalation, feedback, speed), bar
// charts of the most-asked topics, plus which AI mode is running and
// the latest audit-log activity. All numbers are counted live.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/tickets.php';
$u = require_role('admin'); // Only admins past this line.
function one(string $sql, array $p = []) { return q($sql, $p)->fetchColumn(); } // One-number shortcut.

$stats = [
  'Students' => one("SELECT COUNT(*) FROM users WHERE role='student'"),
  'Conversations' => one("SELECT COUNT(*) FROM conversations"),
  'Questions asked' => one("SELECT COUNT(*) FROM messages WHERE sender='student'"),
  'Knowledge entries' => one("SELECT COUNT(*) FROM knowledge_base WHERE status='active'"),
  'Open tickets' => one("SELECT COUNT(*) FROM support_tickets WHERE status='OPEN'"),
  'In progress' => one("SELECT COUNT(*) FROM support_tickets WHERE status='IN PROGRESS'"),
  'Solved' => one("SELECT COUNT(*) FROM support_tickets WHERE status='SOLVED'"),
  'Unanswered' => one("SELECT COUNT(*) FROM unanswered_questions WHERE status='new'"),
];
$active = (int)one("SELECT COUNT(*) FROM conversations c WHERE EXISTS(SELECT 1 FROM messages m WHERE m.conversation_id=c.id AND m.sender='student')");
$esc = (int)one("SELECT COUNT(DISTINCT conversation_id) FROM support_tickets WHERE conversation_id IS NOT NULL");
$fbTotal = (int)one("SELECT COUNT(*) FROM feedback"); $fbPos = (int)one("SELECT COUNT(*) FROM feedback WHERE rating=1");
$avgHrs = one("SELECT AVG((julianday(resolved_at)-julianday(created_at))*24) FROM support_tickets WHERE resolved_at IS NOT NULL");
$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100) . '%' : '—';
$rates = [
  'Chatbot resolution rate' => $pct($active - $esc, $active),
  'Escalation rate' => $pct($esc, $active),
  'Positive feedback rate' => $pct($fbPos, $fbTotal),
  'Avg ticket resolution' => $avgHrs !== null && $avgHrs !== false ? number_format((float)$avgHrs, 1) . ' hrs' : '—',
];
$cats = q("SELECT c.name, COUNT(*) n FROM messages m JOIN issues i ON i.code=m.intent JOIN categories c ON c.id=i.category_id WHERE m.sender='bot' GROUP BY c.id ORDER BY n DESC LIMIT 6")->fetchAll();
$issues = q("SELECT i.name, COUNT(*) n FROM messages m JOIN issues i ON i.code=m.intent WHERE m.sender='bot' GROUP BY i.id ORDER BY n DESC LIMIT 6")->fetchAll();
function bars(array $rows): void {
  $max = max(array_column($rows, 'n') ?: [1]);
  foreach ($rows as $r) echo '<div class="mb-2"><div class="flex justify-between text-xs mb-0.5"><span>' . e($r['name']) . '</span><span class="text-slate-500 dark:text-slate-400">' . (int)$r['n'] . '</span></div><div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full"><div class="h-2 bg-teal rounded-full" style="width:' . round($r['n'] / $max * 100) . '%"></div></div></div>';
  if (!$rows) echo '<p class="text-sm text-slate-500 dark:text-slate-400">No data yet.</p>';
}
layout_top('Admin Dashboard', 'dashboard');
$icons = ['Students' => 'fa-solid fa-user-graduate', 'Conversations' => 'fa-solid fa-comments', 'Questions asked' => 'fa-solid fa-circle-question', 'Knowledge entries' => 'fa-solid fa-book-open', 'Open tickets' => 'fa-solid fa-ticket', 'In progress' => 'fa-solid fa-arrows-rotate', 'Solved' => 'fa-solid fa-check', 'Unanswered' => 'fa-solid fa-circle-exclamation'];
$tiles = ['Students' => 'from-violet-500 to-purple-700', 'Conversations' => 'from-blue-500 to-brand', 'Questions asked' => 'from-amber-500 to-orange-600', 'Knowledge entries' => 'from-teal-500 to-emerald-700', 'Open tickets' => 'from-rose-500 to-red-600', 'In progress' => 'from-sky-500 to-cyan-600', 'Solved' => 'from-green-500 to-emerald-600', 'Unanswered' => 'from-slate-500 to-slate-700'];
page_head('Admin Dashboard', 'Assistant performance, workload and recent activity');
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
<?php foreach ($stats as $l => $n): ?>
  <div class="stat-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl p-4 shadow-card flex items-center gap-3">
    <div class="stat-tile h-11 w-11 shrink-0 rounded-xl bg-gradient-to-br <?= $tiles[$l] ?? 'from-navy to-slate-700' ?> flex items-center justify-center text-white shadow-sm"><i class="<?= $icons[$l] ?? 'fa-solid fa-chart-column' ?>"></i></div>
    <div class="min-w-0"><div class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($l) ?></div><div class="text-2xl font-extrabold text-navy dark:text-white tracking-tight"><?= (int)$n ?></div></div>
  </div>
<?php endforeach; ?>
</div>
<h2 class="font-bold text-navy dark:text-white mb-3">Performance</h2>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
<?php foreach ($rates as $l => $v): ?><div class="rounded-2xl p-4 text-white shadow-card" style="background:linear-gradient(150deg,#12334f,#0a1e33)"><div class="text-xs text-slate-300"><?= e($l) ?></div><div class="text-2xl font-extrabold tracking-tight"><?= e($v) ?></div></div><?php endforeach; ?>
</div>
<div class="grid lg:grid-cols-2 gap-4 mb-4">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl p-5 shadow-card"><h3 class="font-bold text-navy dark:text-white mb-3">Most asked categories</h3><?php bars($cats); ?></div>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl p-5 shadow-card"><h3 class="font-bold text-navy dark:text-white mb-3">Most common issues</h3><?php bars($issues); ?></div>
</div>
<div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl p-5 shadow-card">
    <h3 class="font-bold text-navy dark:text-white mb-3">AI assistant</h3>
    <?php if (GEMINI_API_KEY === ''): ?>
      <div class="flex items-start gap-3"><?= pill('Keyword-matching mode', 'amber') ?></div>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">No Gemini key set — the chatbot classifies questions with built-in keyword rules. Set the <code class="text-xs bg-slate-100 dark:bg-slate-800 rounded px-1.5 py-0.5">GEMINI_API_KEY</code> environment variable (or in <code class="text-xs bg-slate-100 dark:bg-slate-800 rounded px-1.5 py-0.5">config.php</code>) to enable Gemini intent detection.</p>
    <?php else: ?>
      <div class="flex items-start gap-3"><?= pill('Gemini enabled', 'green') ?></div>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Intent detection via <code class="text-xs bg-slate-100 dark:bg-slate-800 rounded px-1.5 py-0.5"><?= e(GEMINI_MODEL) ?></code>, with keyword matching as automatic fallback.</p>
    <?php endif; ?>
  </div>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl p-5 shadow-card">
    <div class="flex items-center justify-between mb-3"><h3 class="font-bold text-navy dark:text-white">Recent activity</h3>
      <a href="<?= url('admin/audit.php') ?>" class="text-xs font-semibold text-brand dark:text-blue-400">Full log →</a></div>
    <?php audit_log_table(); $recent = q('SELECT actor_name,action,created_at FROM audit_log ORDER BY id DESC LIMIT 6')->fetchAll(); ?>
    <?php if (!$recent): ?><p class="text-sm text-slate-500 dark:text-slate-400">No activity recorded yet.</p><?php endif; ?>
    <ul class="space-y-2">
    <?php foreach ($recent as $r): ?>
      <li class="flex items-center gap-2.5 text-sm"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-teal-600"></span>
        <span class="font-medium text-navy dark:text-white truncate"><?= e($r['actor_name'] ?? 'System') ?></span>
        <span class="text-slate-500 dark:text-slate-400 truncate"><?= e($r['action']) ?></span>
        <span class="ml-auto text-xs text-slate-400 whitespace-nowrap"><?= e(fmt_date($r['created_at'])) ?></span></li>
    <?php endforeach; ?>
    </ul>
  </div>
</div>
<p class="text-xs text-slate-400 mt-4">Resolution rate = conversations with a student message that never became a ticket. Confidence thresholds live in config.php.</p>
<?php layout_bottom();
