<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(?string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419); exit('Invalid or expired form token. Go back and refresh.');
    }
}
function flash(?string $msg = null, string $type = 'error') {
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
/* ---------- Shared UI components ---------- */
function page_head(string $title, string $sub = '', string $actions = ''): void {
    echo '<div class="flex flex-wrap items-start justify-between gap-3 mb-5"><div>'
        . '<h1 class="text-2xl font-extrabold text-navy dark:text-white tracking-tight">' . e($title) . '</h1>';
    if ($sub !== '') echo '<p class="text-sm text-slate-500 dark:text-slate-400 mt-1">' . e($sub) . '</p>';
    echo '</div>';
    if ($actions !== '') echo '<div class="flex flex-wrap gap-2">' . $actions . '</div>';
    echo '</div>';
}
function pill(string $text, string $tone = 'slate'): string {
    $c = ['green' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
          'slate' => 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
          'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
          'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
          'red' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
          'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300',
          'teal' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300'][$tone]
          ?? 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ' . $c . '">' . e($text) . '</span>';
}
function empty_state(string $msg): void {
    echo '<div class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-8 text-center text-sm text-slate-500 dark:text-slate-400 shadow-card">' . e($msg) . '</div>';
}
/* Password policy shared by registration + admin user management. Null = OK. */
function password_check(string $pw): ?string {
    if (strlen($pw) < PW_MIN_LEN) return 'Password must be at least ' . PW_MIN_LEN . ' characters.';
    if (!preg_match('/[a-z]/', $pw)) return 'Password needs a lowercase letter (a–z).';
    if (!preg_match('/[A-Z]/', $pw)) return 'Password needs an uppercase letter (A–Z).';
    if (!preg_match('/[0-9]/', $pw)) return 'Password needs a number (0–9).';
    return null;
}
function grade_points(string $grade): ?float {
    return ['A' => 5.0, 'B' => 4.0, 'C' => 3.0, 'D' => 2.0, 'E' => 1.0, 'F' => 0.0][strtoupper($grade)] ?? null;
}
