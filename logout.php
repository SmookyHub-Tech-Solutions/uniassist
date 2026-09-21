<?php
// =====================================================================
// logout.php — signs the visitor out.
// Steps: write "user logged out" in the audit log, wipe the session
// (forget who they were), then send them back to the login screen.
require_once __DIR__ . '/includes/auth.php';
audit('auth.logout');
session_unset(); session_destroy();
header('Location: ' . url('login.php')); exit;
