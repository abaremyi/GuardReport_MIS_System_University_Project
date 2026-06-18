<?php
/**
 * GuardReport — Admin Base Guard
 * File: helpers/admin-base.php
 * Include at the top of every admin view BEFORE any HTML output.
 *
 * Usage:
 *   $requiredPermission = 'incidents.view';  // optional
 *   require_once ROOT_PATH . '/helpers/admin-base.php';
 */
if (!defined('ROOT_PATH')) {
    $guessRoot = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        if (file_exists($guessRoot . '/config/paths.php')) {
            require_once $guessRoot . '/config/paths.php';
            break;
        }
        $guessRoot = dirname($guessRoot);
    }
    if (!defined('ROOT_PATH')) die('ROOT_PATH not defined.');
}
if (!defined('JWT_SECRET_KEY'))  require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/helpers/AuthMiddleware.php';
require_once ROOT_PATH . '/helpers/PermissionHelper.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$_reqPerms = [];
if (!empty($requiredPermission))
    $_reqPerms = is_array($requiredPermission) ? $requiredPermission : [$requiredPermission];

$_auth  = new AuthMiddleware();
$_token = $_COOKIE['auth_token'] ?? '';
if (!$_token) { header('Location: ' . url('login')); exit; }

try { $currentUser = $_auth->requireAuth($_reqPerms); }
catch (Exception $e) {
    setcookie('auth_token', '', time() - 3600, '/');
    header('Location: ' . url('logout'));
    exit;
}

/* ── Session timing (lockscreen warning only) ────────────── */
define('SESSION_TIMEOUT',   1800);
define('LOCK_WARNING_TIME', 60);

if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = time();

$timeSinceActivity = time() - $_SESSION['last_activity'];
$sessionRemaining  = max(0, SESSION_TIMEOUT - $timeSinceActivity);

$isHeartbeat = isset($_SERVER['HTTP_X_HEARTBEAT'])
            || (($_GET['action']  ?? '') === 'heartbeat')
            || (($_POST['action'] ?? '') === 'heartbeat');
if (!$isHeartbeat) $_SESSION['last_activity'] = time();

/* ── Convenience variables ───────────────────────────────── */
$userPermissions      = (array)($currentUser->permissions ?? []);
$isSuperAdmin         = !empty($currentUser->is_super_admin);
$userFullName         = trim(($currentUser->firstname ?? '') . ' ' . ($currentUser->lastname ?? ''));
if ($userFullName === '') $userFullName = $currentUser->email ?? 'User';
$userFullName         = htmlspecialchars($userFullName);
$userInitials         = strtoupper(
    substr($currentUser->firstname ?? '', 0, 1) .
    substr($currentUser->lastname  ?? '', 0, 1)
);
if ($userInitials === '') $userInitials = 'GR';
$userPhoto            = $currentUser->photo ?? '';
$sessionExpiryTimestamp = time() + $sessionRemaining;

if (empty($pageTitle))   $pageTitle   = APP_NAME;
if (empty($currentPage)) $currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

/* Inject JS globals before any layout includes */
echo '<script>
window.BASE_URL="' . BASE_URL . '";
window.SESSION_REMAINING=' . (int)$sessionRemaining . ';
window.SESSION_EXPIRY_TIMESTAMP=' . (int)$sessionExpiryTimestamp . ';
window.SESSION_TIMEOUT=' . SESSION_TIMEOUT . ';
window.LOCK_WARNING_TIME=' . LOCK_WARNING_TIME . ';
window.GR_USER={
  id:' . (int)($currentUser->user_id ?? 0) . ',
  name:"' . addslashes($userFullName) . '",
  email:"' . addslashes($currentUser->email ?? '') . '",
  initials:"' . $userInitials . '",
  role:"' . addslashes($currentUser->role_name ?? '') . '",
  role_id:' . (int)($currentUser->role_id ?? 4) . ',
  is_super_admin:' . ($isSuperAdmin ? 'true' : 'false') . ',
  photo:"' . (!empty($userPhoto) ? addslashes(upload_url($userPhoto)) : '') . '"
};
</script>';

unset($_reqPerms, $_token, $_auth, $timeSinceActivity, $isHeartbeat);