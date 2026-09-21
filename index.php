<?php
// =====================================================================
// index.php — the front door of the app (the "/" address).
// It shows nothing itself: it simply sends every visitor to the right
// place — logged-in users to their own dashboard, everyone else to login.
require_once __DIR__ . '/includes/auth.php';
$u = current_user(); // Who is visiting? (null = a guest, array = logged in)
redirect($u ? home_for($u['role']) : 'login.php');
