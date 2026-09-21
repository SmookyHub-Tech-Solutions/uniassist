<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/chatbot.php';
$u = require_role('student');
$c = student_cgpa((int)$u['id']);
$convs = (int)q('SELECT COUNT(*) c FROM conversations WHERE user_id=?', [$u['id']])->fetch()['c'];
$tix = (int)q("SELECT COUNT(*) c FROM support_tickets WHERE student_id=? AND status IN ('OPEN','IN PROGRESS')", [$u['id']])->fetch()['c'];
layout_top('Dashboard', 'dashboard');
?>
<h1 class="text-2xl font-bold text-navy">Hello, <?= e(explode(' ', $u['name'])[0]) ?> 👋</h1>
<p class="text-slate-500 text-sm mb-6">Ask a question, check your records, or get help from Student Support.</p>
<div class="grid sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white border border-slate-200 rounded-2xl p-5"><div class="text-xs text-slate-500">Current CGPA</div><div class="text-3xl font-bold text-navy"><?= $c ? number_format($c['cgpa'], 2) : '—' ?></div></div>
  <div class="bg-white border border-slate-200 rounded-2xl p-5"><div class="text-xs text-slate-500">Conversations</div><div class="text-3xl font-bold text-navy"><?= $convs ?></div></div>
  <div class="bg-white border border-slate-200 rounded-2xl p-5"><div class="text-xs text-slate-500">Active tickets</div><div class="text-3xl font-bold text-amber-600"><?= $tix ?></div></div>
</div>
<a href="<?= url("student/tickets.php") ?>" class="inline-block rounded-xl border border-slate-300 bg-white text-navy px-5 py-3 text-sm font-semibold mr-2">🎫 My tickets</a><a href="<?= url("student/chat.php") ?>" class="inline-block rounded-xl bg-teal text-white px-5 py-3 text-sm font-semibold hover:opacity-90">💬 Ask UniAssist</a>
<?php layout_bottom();
