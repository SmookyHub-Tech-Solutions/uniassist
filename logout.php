<?php
require_once __DIR__ . '/includes/auth.php';
audit('auth.logout');
session_unset(); session_destroy();
header('Location: ' . url('login.php')); exit;
