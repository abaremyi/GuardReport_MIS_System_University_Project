<?php
/** GuardReport — Logout | File: modules/Authentication/views/logout.php */
require_once dirname(__DIR__, 3) . '/config/paths.php';
setcookie('auth_token', '', time() - 3600, '/');
session_start();
session_destroy();
header('Location: ' . url('login'));
exit;