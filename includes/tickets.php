<?php
function status_badge(string $s): string {
    $c = ['OPEN' => 'bg-amber-100 text-amber-700', 'IN PROGRESS' => 'bg-blue-100 text-blue-700',
          'SOLVED' => 'bg-green-100 text-green-700', 'CLOSED' => 'bg-slate-200 text-slate-700'][$s] ?? 'bg-slate-100 text-slate-600';
    return '<span class="px-2 py-0.5 rounded-full text-xs font-semibold ' . $c . '">' . e($s) . '</span>';
}
function fmt_date(?string $d): string { return $d ? date('j M Y, g:i a', strtotime($d . ' UTC')) : ''; }

/** Chatbot transcript that led to the ticket (read-only). */
function render_transcript(?int $conv): void {
    if (!$conv) return;
    $msgs = q('SELECT sender,message,created_at FROM messages WHERE conversation_id=? ORDER BY id', [$conv])->fetchAll();
    if (!$msgs) return; ?>
    <details class="bg-white border border-slate-200 rounded-2xl mb-5">
      <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-navy">Chatbot conversation that led to this ticket (<?= count($msgs) ?> messages)</summary>
      <div class="px-5 pb-4 space-y-2 max-h-80 overflow-y-auto">
        <?php foreach ($msgs as $m): $me = $m['sender'] === 'student'; ?>
          <div class="flex <?= $me ? 'justify-end' : '' ?>"><div class="max-w-[85%] whitespace-pre-line rounded-2xl px-3 py-2 text-sm <?= $me ? 'bg-brand text-white' : 'bg-slate-50 border border-teal/30' ?>"><?= e($m['message']) ?></div></div>
        <?php endforeach; ?>
      </div>
    </details>
<?php }

/** Ticket message thread. $viewerType = 'student' or 'staff' decides which side is "me". */
function render_thread(int $ticketId, string $viewerType): void {
    $rows = q('SELECT tm.*, u.name FROM ticket_messages tm JOIN users u ON u.id=tm.sender_id WHERE ticket_id=? ORDER BY tm.id', [$ticketId])->fetchAll(); ?>
    <div class="bg-white border border-slate-200/70 rounded-2xl shadow-card p-5 mb-5 space-y-3">
      <?php if (!$rows): ?><p class="text-sm text-slate-500">No replies yet.</p><?php endif; ?>
      <?php foreach ($rows as $m): $me = ($m['sender_type'] === 'student') === ($viewerType === 'student'); ?>
        <div class="flex <?= $me ? 'justify-end' : '' ?>"><div class="max-w-[85%]">
          <div class="text-xs text-slate-400 mb-0.5 <?= $me ? 'text-right' : '' ?>"><?= e($m['name']) ?> · <?= e(fmt_date($m['created_at'])) ?></div>
          <div class="whitespace-pre-line rounded-2xl px-4 py-2.5 text-sm <?= $me ? 'bg-brand text-white' : 'bg-slate-100' ?>"><?= e($m['message']) ?></div>
        </div></div>
      <?php endforeach; ?>
    </div>
<?php }

function add_ticket_message(int $ticketId, int $senderId, string $type, string $text): void {
    q('INSERT INTO ticket_messages(ticket_id,sender_id,sender_type,message) VALUES(?,?,?,?)', [$ticketId, $senderId, $type, $text]);
    q('UPDATE support_tickets SET updated_at=CURRENT_TIMESTAMP WHERE id=?', [$ticketId]);
}
function reply_form(string $label = 'Send reply'): void { ?>
    <form method="post" class="bg-white border border-slate-200/70 rounded-2xl shadow-card p-5">
      <?= csrf_field() ?><input type="hidden" name="do" value="reply">
      <textarea name="message" rows="3" maxlength="2000" required placeholder="Write a message..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition"></textarea>
      <button class="mt-3 rounded-xl bg-brand hover:bg-blue-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition"><?= e($label) ?></button>
    </form>
<?php }
