<?php
// =====================================================================
// student/dashboard.php — a student's home screen (students only).
// A friendly hello plus three live cards (CGPA, chat count, open
// tickets) and two big doors: "Ask UniAssist" (chatbot) and "My tickets".
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/chatbot.php'; // For the CGPA maths.
$u = require_role('student'); // Only students past this line.
// ---- 1. Gather this student's live numbers -----------------------------
$c = student_cgpa((int)$u['id']);
$convs = (int)q('SELECT COUNT(*) c FROM conversations WHERE user_id=?', [$u['id']])->fetch()['c'];
$tix = (int)q("SELECT COUNT(*) c FROM support_tickets WHERE student_id=? AND status IN ('OPEN','IN PROGRESS')", [$u['id']])->fetch()['c'];
layout_top('Dashboard', 'dashboard');
?>
<h1 class="text-2xl font-bold text-navy dark:text-white">Hello, <?= e(explode(' ', $u['name'])[0]) ?></h1>
<p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Ask a question, check your records, or get help from Student Support.</p>
<div class="grid sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-5"><div class="text-xs text-slate-500 dark:text-slate-400">Current CGPA</div><div class="text-3xl font-bold text-navy dark:text-white"><?= $c ? number_format($c['cgpa'], 2) : '—' ?></div></div>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-5"><div class="text-xs text-slate-500 dark:text-slate-400">Conversations</div><div class="text-3xl font-bold text-navy dark:text-white"><?= $convs ?></div></div>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-5"><div class="text-xs text-slate-500 dark:text-slate-400">Active tickets</div><div class="text-3xl font-bold text-amber-600"><?= $tix ?></div></div>
</div>
<a href="<?= url("student/tickets.php") ?>" class="inline-block rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-navy dark:text-white px-5 py-3 text-sm font-semibold mr-2"><i class="fa-solid fa-ticket text-brand dark:text-blue-400"></i> My tickets</a><a href="<?= url("student/chat.php") ?>" class="inline-block rounded-xl bg-teal text-white px-5 py-3 text-sm font-semibold hover:opacity-90"><i class="fa-solid fa-robot"></i> Ask UniAssist</a>
<?php layout_bottom();
