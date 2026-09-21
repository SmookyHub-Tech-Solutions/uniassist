<?php
function nav_for(string $role): array {
    if ($role === 'admin') return [
        'dashboard'     => ['Dashboard', 'admin/dashboard.php', '🏠'],
        'tickets'       => ['Tickets', 'support/tickets.php', '🎫'],
        'kb'            => ['Knowledge Base', 'admin/kb.php', '📚'],
        'unanswered'    => ['Unanswered', 'admin/unanswered.php', '❓'],
        'conversations' => ['Conversations', 'admin/conversations.php', '💬'],
        'categories'    => ['Categories & Issues', 'admin/categories.php', '🗂'],
        'users'         => ['Users', 'admin/users.php', '👥'],
        'audit'         => ['Audit Log', 'admin/audit.php', '🧾'],
    ];
    if ($role === 'support') return [
        'dashboard' => ['Dashboard', 'support/dashboard.php', '🏠'],
        'tickets'   => ['Tickets', 'support/tickets.php', '🎫'],
    ];
    return [
        'dashboard' => ['Dashboard', 'student/dashboard.php', '🏠'],
        'chat'      => ['AI Assistant', 'student/chat.php', '💬'],
        'records'   => ['Academic Records', 'student/records.php', '📊'],
        'tickets'   => ['Support Tickets', 'student/tickets.php', '🎫'],
        'history'   => ['Conversations', 'student/history.php', '🕘'],
    ];
}
function layout_top(string $title, string $active = ''): void {
    $u = current_user();
    $nav = nav_for($u['role'] ?? 'student');
    $initial = strtoupper(mb_substr($u['name'] ?? '?', 0, 1));
    ?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0F2A43">
<title><?= e($title) ?> · <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= url('assets/tailwind.min.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/app.css') ?>">
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen antialiased">
<div class="flex min-h-screen">
  <aside class="hidden md:flex w-72 flex-col fixed inset-y-0 px-5 py-6 text-white" style="background:linear-gradient(180deg,#12334f 0%,#0F2A43 45%,#0a1e33 100%)">
    <div class="flex items-center gap-3 mb-2">
      <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center text-xl shadow-pop">🎓</div>
      <div class="min-w-0"><div class="font-extrabold tracking-tight leading-tight"><?= APP_NAME ?></div>
      <div class="text-[11px] uppercase tracking-[0.14em] text-teal-100/70">Academic support</div></div>
    </div>
    <div class="mb-6"><span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider bg-white/10 rounded-full px-2.5 py-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span><?= e($u['role'] ?? '') ?></span></div>
    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 mb-2 px-1">Menu</div>
    <nav class="space-y-1 flex-1 overflow-y-auto slim-scroll">
      <?php foreach ($nav as $k => [$label, $href, $icon]): $on = $active === $k; ?>
        <a href="<?= url($href) ?>" class="side-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm <?= $on ? 'bg-white text-navy font-semibold shadow-card' : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">
          <span class="w-5 text-center text-base"><?= $icon ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="rounded-2xl bg-white/10 p-3.5 mt-4 flex items-center gap-3">
      <div class="h-10 w-10 shrink-0 rounded-full bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center font-bold"><?= e($initial) ?></div>
      <div class="min-w-0 flex-1"><div class="text-sm font-semibold truncate"><?= e($u['name'] ?? '') ?></div>
      <div class="text-xs text-slate-400 truncate"><?= e($u['matric_number'] ?? $u['email'] ?? '') ?></div></div>
      <a href="<?= url('logout.php') ?>" title="Log out" class="text-slate-300 hover:text-white text-lg px-1">↩</a>
    </div>
  </aside>
  <div class="flex-1 md:ml-72 min-w-0 flex flex-col min-h-screen">
    <header class="sticky top-0 z-20 bg-white/85 backdrop-blur border-b border-slate-200">
      <div class="max-w-6xl mx-auto px-4 md:px-8 h-16 flex items-center gap-3">
        <button id="menuBtn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobileDrawer"
          class="md:hidden rounded-xl border border-slate-300 bg-white p-2.5 text-navy shadow-sm transition active:scale-95">
          <span class="flex flex-col items-center justify-center gap-[5px] h-4 w-5"><span class="bar"></span><span class="bar"></span><span class="bar"></span></span>
        </button>
        <div class="md:hidden font-extrabold text-navy truncate">🎓 <?= APP_NAME ?></div>
        <div class="hidden md:block text-sm text-slate-400"><?= date('l, j F Y') ?></div>
        <div class="flex-1"></div>
        <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider bg-slate-100 text-slate-600 rounded-full px-2.5 py-1"><?= e($u['role'] ?? '') ?></span>
        <div class="hidden sm:flex items-center gap-2">
          <div class="h-8 w-8 rounded-full bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center text-xs font-bold text-white"><?= e($initial) ?></div>
          <div class="text-xs leading-tight"><div class="font-semibold text-navy max-w-[8rem] truncate"><?= e($u['name'] ?? '') ?></div>
          <a href="<?= url('logout.php') ?>" class="text-slate-400 hover:text-red-600">Log out</a></div>
        </div>
      </div>
    </header>
    <div id="drawerOverlay" class="md:hidden fixed inset-0 z-30 bg-navy/60 opacity-0 pointer-events-none"></div>
    <aside id="mobileDrawer" aria-label="Menu" class="md:hidden fixed inset-y-0 left-0 z-40 w-[19rem] max-w-[85vw] -translate-x-full flex flex-col px-5 py-6 text-white shadow-pop" style="background:linear-gradient(180deg,#12334f 0%,#0F2A43 45%,#0a1e33 100%)">
      <div class="flex items-center gap-3 mb-2">
        <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center text-xl">🎓</div>
        <div class="min-w-0 flex-1"><div class="font-extrabold tracking-tight leading-tight"><?= APP_NAME ?></div>
        <div class="text-[11px] uppercase tracking-[0.14em] text-teal-100/70">Academic support</div></div>
        <button id="drawerClose" type="button" aria-label="Close menu" class="rounded-xl bg-white/10 hover:bg-white/20 p-2 text-slate-200 transition active:scale-95">✕</button>
      </div>
      <div class="mb-6"><span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider bg-white/10 rounded-full px-2.5 py-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span><?= e($u['role'] ?? '') ?></span></div>
      <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 mb-2 px-1">Menu</div>
      <nav class="space-y-1 flex-1 overflow-y-auto slim-scroll">
        <?php foreach ($nav as $k => [$label, $href, $icon]): $on = $active === $k; ?>
          <a href="<?= url($href) ?>" class="side-link flex items-center gap-3 px-3.5 py-3 rounded-xl text-[15px] <?= $on ? 'bg-white text-navy font-semibold shadow-card' : 'text-slate-200 hover:bg-white/10 hover:text-white' ?>">
            <span class="w-5 text-center text-base"><?= $icon ?></span><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="rounded-2xl bg-white/10 p-3.5 mt-4 flex items-center gap-3">
        <div class="h-10 w-10 shrink-0 rounded-full bg-gradient-to-br from-brand to-teal-600 flex items-center justify-center font-bold"><?= e($initial) ?></div>
        <div class="min-w-0 flex-1"><div class="text-sm font-semibold truncate"><?= e($u['name'] ?? '') ?></div>
        <div class="text-xs text-slate-400 truncate"><?= e($u['matric_number'] ?? $u['email'] ?? '') ?></div></div>
      </div>
      <a href="<?= url('logout.php') ?>" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-red-500/15 hover:bg-red-500/25 text-red-200 px-3.5 py-2.5 text-sm font-semibold transition"><span>↩</span>Log out</a>
    </aside>
    <main class="flex-1 p-4 md:p-8"><div class="max-w-6xl mx-auto">
    <?php if ($f = flash()): ?>
      <div role="alert" class="flash mb-4 flex items-start gap-3 rounded-xl border-l-4 bg-white shadow-card px-4 py-3 text-sm <?= $f['type']==='success' ? 'border-green-500' : 'border-red-500' ?>">
        <span class="mt-0.5"><?= $f['type']==='success' ? '✅' : '⚠️' ?></span>
        <div class="flex-1 text-slate-700"><?= e($f['msg']) ?></div>
        <button onclick="this.parentElement.remove()" aria-label="Dismiss" class="text-slate-400 hover:text-slate-600">✕</button>
      </div>
    <?php endif;
}
function layout_bottom(): void {
  echo '</div></main><footer class="pb-6 text-center text-xs text-slate-400">🎓 ' . e(APP_NAME) . ' · Understand → Guide → Retrieve → Resolve → Escalate</footer></div></div>'
  . '<script>(function(){var b=document.getElementById("menuBtn"),d=document.getElementById("mobileDrawer"),o=document.getElementById("drawerOverlay"),c=document.getElementById("drawerClose");'
  . 'if(!b||!d||!o)return;function open(){d.classList.add("open");o.classList.add("show");document.body.classList.add("overflow-hidden");b.classList.add("open");b.setAttribute("aria-expanded","true");b.setAttribute("aria-label","Close menu");if(c)c.focus();}'
  . 'function shut(){d.classList.remove("open");o.classList.remove("show");document.body.classList.remove("overflow-hidden");b.classList.remove("open");b.setAttribute("aria-expanded","false");b.setAttribute("aria-label","Open menu");}'
  . 'b.addEventListener("click",function(){d.classList.contains("open")?shut():open();});'
  . 'if(c)c.addEventListener("click",shut);o.addEventListener("click",shut);'
  . 'document.addEventListener("keydown",function(e){if(e.key==="Escape")shut();});})();</script></body></html>';
}
