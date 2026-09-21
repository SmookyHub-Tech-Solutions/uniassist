<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');
$id = (int)($_GET['id'] ?? 0);
$from = (int)($_GET['from'] ?? 0);   // unanswered question id
$e = $id ? q('SELECT * FROM knowledge_base WHERE id=?', [$id])->fetch() : null;
if ($id && !$e) { http_response_code(404); exit('Entry not found.'); }
$e = $e ?: ['category_id' => 0, 'issue_id' => 0, 'question' => '', 'answer' => '', 'keywords' => '', 'status' => 'active'];
if (!$id && $from && ($uq = q('SELECT question FROM unanswered_questions WHERE id=?', [$from])->fetch())) $e['question'] = $uq['question'];

$cats = q('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$issues = q('SELECT id,category_id,name FROM issues ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $d = [(int)$_POST['category_id'], (int)$_POST['issue_id'] ?: null, trim($_POST['question'] ?? ''), trim($_POST['answer'] ?? ''), trim($_POST['keywords'] ?? ''), $_POST['status'] === 'inactive' ? 'inactive' : 'active'];
    $err = null;
    if (!q('SELECT 1 FROM categories WHERE id=?', [$d[0]])->fetch()) $err = 'Choose a category.';
    elseif ($d[1] && !q('SELECT 1 FROM issues WHERE id=? AND category_id=?', [$d[1], $d[0]])->fetch()) $err = 'That issue does not belong to the chosen category.';
    elseif ($d[2] === '' || $d[3] === '') $err = 'Question and answer are required.';
    if ($err) { flash($err); redirect('admin/kb_edit.php?' . ($id ? "id=$id" : ($from ? "from=$from" : ''))); }
    if ($id) {
        q('UPDATE knowledge_base SET category_id=?,issue_id=?,question=?,answer=?,keywords=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [...$d, $id]);
        audit('kb.update', 'knowledge_base', $id, mb_substr($d[2], 0, 120));
    }
    else {
        q('INSERT INTO knowledge_base(category_id,issue_id,question,answer,keywords,status) VALUES(?,?,?,?,?,?)', $d);
        $nid = (int)db()->lastInsertId();
        audit('kb.create', 'knowledge_base', $nid, mb_substr($d[2], 0, 120));
        if ($from) {
            q("UPDATE unanswered_questions SET status='converted' WHERE id=?", [$from]);
            audit('unanswered.convert', 'unanswered_questions', $from, "converted to KB #$nid");
        }
    }
    flash('Knowledge entry saved.', 'success'); redirect('admin/kb.php');
}
layout_top($id ? 'Edit entry' : 'Add entry', 'kb');
$inp = 'mt-1.5 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition';
page_head(($id ? 'Edit' : 'Add') . ' knowledge entry', '', '<a href="' . url('admin/kb.php') . '" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-800 px-4 py-2 text-sm font-medium shadow-sm transition">← Knowledge base</a>');
?>
<form method="post" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/70 rounded-2xl shadow-card p-6 max-w-2xl space-y-4"><?= csrf_field() ?>
  <label class="block text-sm font-medium">Category
    <select name="category_id" id="cat" required class="<?= $inp ?>"><option value="">Select…</option>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$e['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
  <label class="block text-sm font-medium">Issue (optional — link it so the guided menu shows this answer)
    <select name="issue_id" id="issue" class="<?= $inp ?>"><option value="0">None</option>
      <?php foreach ($issues as $i): ?><option data-cat="<?= $i['category_id'] ?>" value="<?= $i['id'] ?>" <?= (int)$e['issue_id'] === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option><?php endforeach; ?></select></label>
  <label class="block text-sm font-medium">Question<input name="question" required maxlength="300" value="<?= e($e['question']) ?>" class="<?= $inp ?>"></label>
  <label class="block text-sm font-medium">Answer<textarea name="answer" rows="6" required class="<?= $inp ?>"><?= e($e['answer']) ?></textarea></label>
  <label class="block text-sm font-medium">Keywords (space separated — used when AI is unavailable)<input name="keywords" value="<?= e($e['keywords']) ?>" class="<?= $inp ?>"></label>
  <label class="block text-sm font-medium">Status<select name="status" class="<?= $inp ?>"><option value="active">Active</option><option value="inactive" <?= $e['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></label>
  <button class="rounded-xl bg-gradient-to-r from-brand to-blue-600 hover:from-blue-600 hover:to-brand text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition active:scale-[.99]">Save entry</button>
</form>
<script>
const cat = document.getElementById('cat'), issue = document.getElementById('issue');
function filter() { [...issue.options].forEach(o => { if (!o.dataset.cat) return; const ok = o.dataset.cat === cat.value; o.hidden = !ok; if (!ok && o.selected) issue.value = '0'; }); }
cat.onchange = filter; filter();
</script>
<?php layout_bottom();
