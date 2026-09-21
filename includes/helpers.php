<?php
// =====================================================================
// includes/helpers.php — small shared tools every page can use:
// e() escapes text so it can't run as code in the browser (stops
// cross-site scripting), url()/redirect() build safe links, the csrf_*
// trio blocks forged form submissions, flash() shows one-time messages,
// page_head()/pill()/empty_state() draw consistent page furniture, and
// password_check() enforces the password rule in one single place.
// =====================================================================
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(?string $token): void {
    // Guard at the top of every form handler: the submitted token must
    // match the one stored in the visitor's session, otherwise some OTHER
    // website forged this submission and we refuse it (error 419).
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419); exit('Invalid or expired form token. Go back and refresh.');
    }
}
function flash(?string $msg = null, string $type = 'error') {
    // One-time popup message ("Saved!", "Wrong password"). Calling with a
    // message stores it in the session; calling with none shows it once
    // and deletes it, so it never appears twice.
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
/* Traditional show/hide-password eye (SVG icons, no emoji) for any
   password box. $for = the input's id. Needs assets/toggle-password.js,
   which is already loaded on every page (layout + auth shells). */
function pw_toggle_btn(string $for): string {
    $eye = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
    $off = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';
    return '<button type="button" data-showfor="' . e($for) . '" aria-label="Show password" aria-pressed="false" title="Show password"'
        . ' class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">'
        . '<span class="eye-on">' . $eye . '</span><span class="eye-off hidden">' . $off . '</span></button>';
}
function grade_points(string $grade): ?float {
    return ['A' => 5.0, 'B' => 4.0, 'C' => 3.0, 'D' => 2.0, 'E' => 1.0, 'F' => 0.0][strtoupper($grade)] ?? null;
}
