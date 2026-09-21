<?php
// ---- MAAUN UniAssist configuration ----
define('APP_NAME', 'MAAUN UniAssist');
define('BASE_URL', (function () {
    // Auto-detect so the app works both under htdocs (/uniassist) and when
    // served directly from the project root with `php -S` (base = '').
    $docRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
    $appDir = str_replace('\\', '/', __DIR__);
    if ($docRoot !== '' && ($appDir === $docRoot || str_starts_with($appDir, $docRoot . '/'))) {
        return substr($appDir, strlen($docRoot)); // '' when docroot == project dir
    }
    return '/uniassist';            // folder name inside htdocs (XAMPP layout)
})());
define('DB_PATH', __DIR__ . '/database/uniassist.sqlite');

// Put your free-tier key from https://aistudio.google.com/apikey in the
// GEMINI_API_KEY env var or in a git-ignored `.env` file (see `.env.example`).
// Leave empty to use keyword matching only.
if (!getenv('GEMINI_API_KEY')) {
    $envFile = __DIR__ . '/.env';
    if (is_readable($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k); $v = trim($v, " \t\"'");
            if ($k !== '' && getenv($k) === false) { putenv("$k=$v"); $_ENV[$k] = $v; }
        }
    }
}
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-3.6-flash');

// Confidence thresholds
define('CONF_HIGH', 0.75);   // >= answer
define('CONF_LOW', 0.40);    // <  escalate, in between = clarify

define('SESSION_TIMEOUT', 1800); // 30 minutes
