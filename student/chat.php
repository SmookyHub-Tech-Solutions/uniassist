<?php
// =====================================================================
// student/chat.php — the AI Assistant screen (students only).
// The pretty chat window lives here; the thinking happens in
// api/chat.php. On load it says hello and shows the topic menu; every
// typed message or tapped button travels to the API and the answer is
// drawn as a chat bubble (dots bounce while waiting). Nothing typed
// here is graded or official — it is guidance, escalated to humans
// whenever the bot is unsure.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('student'); // Only students past this line.
layout_top('AI Assistant', 'chat');
?>
<div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-3xl shadow-card flex flex-col overflow-hidden" style="height:calc(100dvh - 13rem);min-height:26rem">
  <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3 bg-gradient-to-r from-navy to-[#1a4265] text-white">
    <div class="h-9 w-9 rounded-full bg-white/15 flex items-center justify-center"><i class="fa-solid fa-robot"></i></div>
    <div><div class="font-semibold text-sm"><?= APP_NAME ?></div><div class="text-xs text-slate-300">Your academic support assistant · <span class="text-emerald-300">● online</span></div></div>
  </div>
  <div id="log" class="slim-scroll flex-1 overflow-y-auto p-5 space-y-3 bg-slate-50 dark:bg-slate-800"></div>
  <form id="form" class="p-3 border-t border-slate-200 dark:border-slate-700 flex gap-2 bg-white dark:bg-slate-900">
    <input id="text" maxlength="500" autocomplete="off" placeholder="Type your question..." class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition">
    <button class="rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white px-5 text-sm font-semibold shadow-sm transition active:scale-95" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</div>
<script>
const API = <?= json_encode(url('api/chat.php')) ?>, CSRF = <?= json_encode(csrf_token()) ?>;
let conv = 0;
const log = document.getElementById('log');

function bubble(text, who) {
  // Draw one chat bubble: 'me' = blue, right side; anything else = white,
  // left side. textContent (never innerHTML) so chat text can't run code.
  const w = document.createElement('div'); w.className = who === 'me' ? 'flex justify-end' : 'flex';
  const b = document.createElement('div');
  b.className = 'max-w-[85%] whitespace-pre-line rounded-2xl px-4 py-2.5 text-sm shadow-sm ' +
    (who === 'me' ? 'bg-brand text-white rounded-br-md' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-bl-md');
  b.textContent = text; w.appendChild(b); log.appendChild(w); log.scrollTop = log.scrollHeight; return b;
}
function buttons(list) {
  // Draw the tap-buttons under a bot answer (with an FA icon when the
  // server sent one). Tapping sends that button's action back to
  // api/chat.php (the row deletes itself to keep history tidy).
  if (!list || !list.length) return;
  const row = document.createElement('div'); row.className = 'flex flex-wrap gap-2';
  list.forEach(b => {
    const el = document.createElement('button');
    el.className = 'inline-flex items-center gap-1.5 rounded-full border border-teal text-teal hover:bg-teal hover:text-white px-3 py-1.5 text-xs font-medium transition';
    if (b.icon) { const ic = document.createElement('i'); ic.className = b.icon + ' fa-fw'; el.appendChild(ic); }
    const tx = document.createElement('span'); tx.textContent = b.label; el.appendChild(tx);
    el.onclick = () => { row.remove(); if (b.action !== 'menu' && b.action !== 'feedback') {} send(b); };
    row.appendChild(el);
  });
  log.appendChild(row); log.scrollTop = log.scrollHeight;
}
async function send(payload, echo) {
  // One round trip: optionally echo the student's text, show bouncing
  // dots, POST the action packet to api/chat.php, then swap the dots for
  // the real answer + its buttons. Network failure gets a plain apology.
  if (echo) bubble(echo, 'me');
  const typing = bubble('', 'bot');
  typing.innerHTML = '<span class="typing-dot">●</span> <span class="typing-dot">●</span> <span class="typing-dot">●</span>';
  try {
    const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ ...payload, conversation_id: conv }) });
    const d = await r.json(); typing.parentElement.remove();
    if (d.error) { bubble('Something went wrong: ' + d.error, 'bot'); return; }
    conv = d.conversation_id; bubble(d.text, 'bot'); buttons(d.buttons);
  } catch (e) { typing.textContent = 'Connection problem. Please try again.'; }
}
document.getElementById('form').onsubmit = e => {
  e.preventDefault(); const t = document.getElementById('text').value.trim(); if (!t) return;
  document.getElementById('text').value = ''; send({ action: 'message', text: t }, t);
};
send({ action: 'start' });
</script>
<?php layout_bottom();
