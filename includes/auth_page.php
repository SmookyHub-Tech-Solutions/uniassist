<?php
function auth_top(string $title, string $heading, string $sub): void { ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0F2A43">
<meta name="color-scheme" content="light dark">
<title><?= e($title) ?> · <?= APP_NAME ?></title>
<script>try{if((localStorage.getItem('uniassist-theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'))==='dark')document.documentElement.classList.add('dark')}catch(e){}</script>
<link rel="stylesheet" href="<?= url('assets/tailwind.min.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-100 dark:bg-slate-800 md:grid md:grid-cols-2 font-sans antialiased">
<div class="hidden md:flex flex-col justify-between p-14 text-white relative overflow-hidden" style="background:linear-gradient(160deg,#12334f 0%,#0F2A43 55%,#0a1e33 100%)">
  <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-brand/20 blur-3xl"></div>
  <div class="absolute -bottom-28 -left-20 h-80 w-80 rounded-full bg-teal-600/20 blur-3xl"></div>
  <div class="relative">
    <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center text-3xl shadow-pop mb-6">🎓</div>
    <h1 class="text-4xl font-extrabold tracking-tight"><?= APP_NAME ?></h1>
    <p class="mt-3 text-slate-300 max-w-sm leading-relaxed">Your intelligent academic support assistant. Understand → Guide → Retrieve → Resolve → Escalate.</p>
    <ul class="mt-8 space-y-3 text-sm text-slate-200">
      <li class="flex items-center gap-2.5"><span class="text-teal-100">✓</span> Check results, GPA &amp; CGPA instantly</li>
      <li class="flex items-center gap-2.5"><span class="text-teal-100">✓</span> Guided answers for registration, exams &amp; fees</li>
      <li class="flex items-center gap-2.5"><span class="text-teal-100">✓</span> Escalate to support staff in one tap</li>
    </ul>
  </div>
  <p class="relative text-xs text-slate-400">Maryam Abacha American University of Nigeria</p>
</div>
<div class="flex items-center justify-center p-6 py-12 relative bg-slate-100 dark:bg-slate-800 dark:bg-slate-950">
  <button id="themeBtn" type="button" aria-label="Toggle dark mode"
    class="absolute top-4 right-4 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 dark:bg-slate-800 p-2.5 text-slate-500 dark:text-slate-400 dark:text-amber-300 shadow-sm transition active:scale-95">🌙</button>
  <div class="w-full max-w-sm">
    <div class="md:hidden flex items-center gap-2.5 mb-6"><div class="h-10 w-10 rounded-xl bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center text-xl">🎓</div><span class="font-extrabold text-navy dark:text-white"><?= APP_NAME ?></span></div>
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-pop border border-slate-200 dark:border-slate-700/70 dark:border-slate-700/70 p-7">
    <h2 class="text-xl font-extrabold text-navy dark:text-white tracking-tight"><?= e($heading) ?></h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5 mt-0.5"><?= e($sub) ?></p>
    <?php if ($f = flash()): ?><div role="alert" class="flash mb-4 rounded-xl border-l-4 <?= $f['type']==='success'?'border-green-500':'border-red-500' ?> bg-slate-50 dark:bg-slate-800 px-3 py-2.5 text-sm <?= $f['type']==='success'?'text-green-700 dark:text-green-300':'text-red-700 dark:text-red-300' ?>"><?= e($f['msg']) ?></div><?php endif;
}
function auth_bottom(): void { echo '</div><p class="text-center text-xs text-slate-400 mt-5">🎓 ' . e(APP_NAME) . '</p></div></div>'
  . '<script>(function(){var t=document.getElementById("themeBtn");if(!t)return;'
  . 'function paint(){var dark=document.documentElement.classList.contains("dark");t.textContent=dark?"☀":"🌙";}'
  . 't.addEventListener("click",function(){var dark=document.documentElement.classList.toggle("dark");try{localStorage.setItem("uniassist-theme",dark?"dark":"light");}catch(e){}paint();});paint();})();</script>'
  . '</body></html>'; }
function field(string $name, string $label, string $type = 'text', string $val = ''): void {
    echo '<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">' . e($label) .
         '<input name="' . e($name) . '" type="' . e($type) . '" value="' . e($val) . '" required class="mt-1.5 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition"></label>';
}
