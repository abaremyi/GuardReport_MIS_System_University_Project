<?php
/** GuardReport — Auth API | File: modules/Authentication/api/authApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 1) . '/controllers/AuthController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = array_merge($_GET, $_POST);
if ($method === 'POST' && empty($_POST)) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $input;
}
$ctrl = new AuthController();

switch ($action) {
    case 'login':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id  = trim($input['identifier'] ?? $input['email'] ?? '');
        $pwd = $input['password'] ?? '';
        if (!$id || !$pwd) { echo json_encode(['success'=>false,'message'=>'Email and password required']); break; }
        $res = $ctrl->login($id, $pwd);
        if ($res['success']) {
            setcookie('auth_token', $res['token'], [
                'expires'  => time() + 2700, 'path'     => '/',
                'httponly' => true,           'samesite' => 'Strict',
                'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
            ]);
            unset($res['token']);
            $res['redirect'] = url('admin/dashboard');
        }
        echo json_encode($res);
        break;

    case 'register':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        echo json_encode($ctrl->register($input));
        break;

    case 'verify-registration-otp':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $uid = (int)($input['user_id'] ?? 0);
        $otp = trim($input['otp'] ?? '');
        if (!$uid || !$otp) { echo json_encode(['success'=>false,'message'=>'user_id and otp required']); break; }
        echo json_encode($ctrl->verifyRegistrationOtp($uid, $otp));
        break;

    case 'resend-otp':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $uid = (int)($input['user_id'] ?? 0);
        if (!$uid) { echo json_encode(['success'=>false,'message'=>'user_id required']); break; }
        echo json_encode($ctrl->resendOtp($uid));
        break;

    case 'forgot-password':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $email = trim($input['email'] ?? '');
        if (!$email) { echo json_encode(['success'=>false,'message'=>'Email required']); break; }
        echo json_encode($ctrl->forgotPassword($email));
        break;

    case 'verify-otp':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        echo json_encode($ctrl->verifyOtp(trim($input['email'] ?? ''), trim($input['otp'] ?? '')));
        break;

    case 'reset-password':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        echo json_encode($ctrl->resetPassword(
            trim($input['email'] ?? ''), trim($input['otp'] ?? ''),
            $input['password'] ?? '', $input['confirm_password'] ?? ''
        ));
        break;

    case 'logout':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        echo json_encode($ctrl->logout());
        break;

    case 'heartbeat':
        echo json_encode(['success' => true, 'ts' => time()]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
}