<?php
// =====================================================================
// student/records.php — a student's official results table (students).
// READ-ONLY report card: every course, units and grade, grouped by
// session/semester, with the live CGPA up top. Corrections are never
// made here — the page says to contact Student Support instead.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/chatbot.php'; // For the CGPA maths.
$u = require_role('student'); // Only students past this line.
// ---- 1. Pull this student's rows + CGPA ---------------------------------
$rows = q('SELECT * FROM academic_records WHERE student_id=? ORDER BY session, semester, course_code', [$u['id']])->fetchAll();
$c = student_cgpa((int)$u['id']);
layout_top('Academic Records', 'records');
?>
<div class="flex items-end justify-between mb-5">
  <div><h1 class="text-2xl font-bold text-navy dark:text-white">Academic Records</h1><p class="text-sm text-slate-500 dark:text-slate-400">Read-only. Contact Student Support for corrections.</p></div>
  <div class="text-right"><div class="text-xs text-slate-500 dark:text-slate-400">CGPA</div><div class="text-2xl font-bold text-navy dark:text-white"><?= $c ? number_format($c['cgpa'], 2) : '—' ?></div></div>
</div>
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-x-auto">
<table class="w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-left"><tr>
  <th class="p-3">Session</th><th class="p-3">Semester</th><th class="p-3">Course</th><th class="p-3">Title</th><th class="p-3">Units</th><th class="p-3">Grade</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr class="border-t border-slate-100 dark:border-slate-800"><td class="p-3"><?= e($r['session']) ?></td><td class="p-3"><?= e($r['semester']) ?></td><td class="p-3 font-medium"><?= e($r['course_code']) ?></td><td class="p-3"><?= e($r['course_title']) ?></td><td class="p-3"><?= (int)$r['credit_unit'] ?></td><td class="p-3 font-semibold"><?= e($r['grade']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="p-6 text-center text-slate-500 dark:text-slate-400">No records available yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php layout_bottom();
