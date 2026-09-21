<?php
// =====================================================================
// api/chat.php — the chatbot's POST OFFICE (JSON in, JSON out).
// The chat screen sends small action packets (start/menu/category/
// issue/message/ticket/feedback); this file checks the visitor is a
// student who owns the conversation, does the work, saves BOTH sides
// of the chat to the database, and replies with bot text + buttons.
// Confidence rules: too unsure -> escalate; unsure -> ask "did you
// mean...?"; sure -> answer straight from verified data.
// =====================================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/chatbot.php';
header('Content-Type: application/json');

// ---- 1. Security gates: student only, correct secret token ------------
$user = current_user();
if (!$user || $user['role'] !== 'student') { http_response_code(401); echo json_encode(['error' => 'Not authorised']); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(419); echo json_encode(['error' => 'Bad token']); exit;
}
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $in['action'] ?? '';
$convId = (int)($in['conversation_id'] ?? 0);

function save_msg(int $conv, string $sender, string $text, ?string $intent = null, ?float $conf = null): int {
    // File one chat line (student OR bot) into the database, including
    // what the bot thought it meant, and stamp the conversation as fresh.
    q('INSERT INTO messages(conversation_id,sender,message,intent,confidence) VALUES(?,?,?,?,?)', [$conv, $sender, $text, $intent, $conf]);
    q("UPDATE conversations SET updated_at=CURRENT_TIMESTAMP WHERE id=?", [$conv]);
    return (int)db()->lastInsertId();
}
function reply(int $conv, array $r, ?string $intent = null, ?float $conf = null): void {
    // Send one bot answer: save it, offer thumbs up/down when the answer
    // wants feedback, remember ticket subjects, and output the JSON packet.
    $id = save_msg($conv, 'bot', $r['text'], $intent, $conf);
    if (!empty($r['feedback'])) {
        $r['buttons'][] = btn('Helpful', 'feedback', ['icon' => 'fa-solid fa-thumbs-up', 'rating' => 1, 'message_id' => $id]);
        $r['buttons'][] = btn('Not helpful', 'feedback', ['icon' => 'fa-solid fa-thumbs-down', 'rating' => 0, 'message_id' => $id]);
    }
    if (!empty($r['subject'])) $_SESSION['ticket_subject'][$conv] = $r['subject'];
    echo json_encode(['conversation_id' => $conv, 'text' => $r['text'], 'buttons' => $r['buttons'] ?? []]);
    exit;
}
function menu_reply(int $conv, string $text = 'How can I help you today? Pick a topic or type your question.'): void {
    // The topic menu: one button per active category plus "Other enquiry".
    $b = [];
    foreach (q("SELECT id,name,icon FROM categories WHERE status='active' ORDER BY id")->fetchAll() as $c)
        $b[] = btn($c['name'], 'category', ['icon' => $c['icon'], 'id' => (int)$c['id']]);
    $b[] = btn('Other enquiry', 'other', ['icon' => 'fa-solid fa-circle-question']);
    reply($conv, ['text' => $text, 'buttons' => $b]);
}

// Start (or validate) conversation — ownership enforced
if ($action === 'start') {
    q('INSERT INTO conversations(user_id) VALUES(?)', [$user['id']]);
    $convId = (int)db()->lastInsertId();
    menu_reply($convId, "Welcome to " . APP_NAME . ", " . explode(' ', $user['name'])[0] . ". How can I help you today?");
}
$own = q('SELECT id FROM conversations WHERE id=? AND user_id=?', [$convId, $user['id']])->fetch();
if (!$own) { http_response_code(403); echo json_encode(['error' => 'Invalid conversation']); exit; }

// ---- 3. Route the action (one branch per button/message type) --------
switch ($action) {
    case 'menu': menu_reply($convId);

    case 'category':
        $cat = q('SELECT * FROM categories WHERE id=?', [(int)$in['id']])->fetch();
        if (!$cat) menu_reply($convId);
        save_msg($convId, 'student', $cat['icon'] . ' ' . $cat['name']);
        q('UPDATE conversations SET category_id=? WHERE id=?', [$cat['id'], $convId]);
        $b = [];
        foreach (q("SELECT id,name FROM issues WHERE category_id=? AND status='active'", [$cat['id']])->fetchAll() as $i)
            $b[] = btn($i['name'], 'issue', ['id' => (int)$i['id']]);
        $b[] = btn('Other', 'other'); $b[] = btn('Back', 'menu', ['icon' => 'fa-solid fa-arrow-left']);
        reply($convId, ['text' => "What do you need help with in {$cat['name']}?", 'buttons' => $b]);

    case 'issue':
        $iss = q('SELECT * FROM issues WHERE id=?', [(int)$in['id']])->fetch();
        if (!$iss) menu_reply($convId);
        save_msg($convId, 'student', $iss['name']);
        reply($convId, resolve_intent($convId, $user, (string)$iss['code'], null, null, $iss['name']), $iss['code'], 1.0);

    case 'other':
        reply($convId, ['text' => "Type your question below, or contact Student Support directly.", 'buttons' => [btn('Create Support Ticket', 'ticket', ['icon' => 'fa-solid fa-ticket']), btn('Main menu', 'menu', ['icon' => 'fa-solid fa-arrow-left'])]]);

    case 'message':
        // The student typed free text: save it, check for a follow-up the
        // bot was waiting for (e.g. "which course?"), otherwise understand
        // it (Gemini first, keywords as backup) and apply the confidence
        // rules: unsure -> escalate, shaky -> "did you mean...?", sure ->
        // answer from verified records.
        $text = trim(mb_substr((string)($in['text'] ?? ''), 0, 500));
        if ($text === '') menu_reply($convId);
        save_msg($convId, 'student', $text);

        // Follow-up to a question the bot asked (e.g. which course?)
        $p = $_SESSION['pending'][$convId] ?? null;
        if ($p) {
            $course = extract_course($text);
            if ($course) reply($convId, resolve_intent($convId, $user, $p['intent'], $course, $p['semester'] ?? null, $text), $p['intent'], 1.0);
            unset($_SESSION['pending'][$convId]);
        }

        $ai = gemini_intent($text);
        $ai_ok = $ai !== null;
        $res = $ai ?? keyword_intent($text);
        $intent = $res['intent']; $conf = (float)$res['confidence'];
        $course = extract_course($text) ?? (isset($res['course']) && $res['course'] !== 'null' ? strtoupper((string)$res['course']) : null);
        $sem = extract_semester($text) ?? ($res['semester'] ?? null);
        if ($sem === 'null') $sem = null;

        if ($intent === 'other' || $conf < CONF_LOW) {
            $k = keyword_intent($text);
            if ($k['intent'] !== 'other' && $k['confidence'] >= 0.5) { $intent = $k['intent']; $conf = max($conf, $k['confidence']); }
        }
        if (strpos($intent, 'kb:') === 0 && $conf >= CONF_LOW) reply($convId, resolve_intent($convId, $user, $intent, null, null, $text), $intent, $conf);
        if ($intent === 'other' || $conf < CONF_LOW) reply($convId, escalate_offer($convId, $user, $text), $intent, $conf);
        if ($conf < CONF_HIGH) {
            $iss = q('SELECT id,name FROM issues WHERE code=?', [$intent])->fetch();
            reply($convId, ['text' => "Just to be sure, do you mean “{$iss['name']}”?", 'buttons' => [btn('Yes', 'issue', ['id' => (int)$iss['id']]), btn('No, show topics', 'menu'), btn('Contact Support', 'ticket', ['icon' => 'fa-solid fa-ticket'])]], $intent, $conf);
        }
        reply($convId, resolve_intent($convId, $user, $intent, $course, $sem, $text), $intent, $conf);

    case 'ticket':
        // Escalate this chat to a human: create ONE support ticket
        // (numbered SUP-000123...) carrying the chat transcript along, so
        // staff see the full story and the student never repeats themself.
        // A second tap while one is open just points at the existing ticket.
        $last = q("SELECT message FROM messages WHERE conversation_id=? AND sender='student' ORDER BY id DESC LIMIT 1", [$convId])->fetch();
        $subject = $_SESSION['ticket_subject'][$convId] ?? mb_substr($last['message'] ?? 'Student enquiry', 0, 80);
        $catId = q('SELECT category_id FROM conversations WHERE id=?', [$convId])->fetch()['category_id'];
        $desc = "Escalated from chatbot conversation #$convId. Last student message: " . ($last['message'] ?? '');
        $dup = q("SELECT ticket_number FROM support_tickets WHERE conversation_id=? AND status IN ('OPEN','IN PROGRESS')", [$convId])->fetch();
        if ($dup) reply($convId, ['text' => "A ticket for this conversation is already open: {$dup['ticket_number']}.", 'buttons' => [btn('Main menu', 'menu', ['icon' => 'fa-solid fa-arrow-left'])]]);
        q('INSERT INTO support_tickets(student_id,conversation_id,category_id,subject,description) VALUES(?,?,?,?,?)', [$user['id'], $convId, $catId, $subject, $desc]);
        $tid = (int)db()->lastInsertId(); $num = sprintf('SUP-%06d', $tid);
        q('UPDATE support_tickets SET ticket_number=? WHERE id=?', [$num, $tid]);
        audit('ticket.create', 'support_tickets', $tid, "$num · $subject", ['id' => $user['id'], 'name' => $user['name'], 'role' => 'student']);
        reply($convId, ['text' => "✅ Ticket $num created. Support staff can see this conversation, so you won't need to explain again. Track it under Support Tickets.", 'buttons' => [btn('Main menu', 'menu', ['icon' => 'fa-solid fa-arrow-left'])]]);

    case 'feedback':
        q('INSERT INTO feedback(conversation_id,message_id,student_id,rating) VALUES(?,?,?,?)', [$convId, (int)$in['message_id'], $user['id'], (int)!!$in['rating']]);
        reply($convId, ['text' => $in['rating'] ? 'Thanks for the feedback!' : 'Sorry about that. You can create a support ticket for further help.', 'buttons' => $in['rating'] ? [btn('Main menu', 'menu', ['icon' => 'fa-solid fa-arrow-left'])] : ticket_buttons()]);
}
http_response_code(400); echo json_encode(['error' => 'Unknown action']);
