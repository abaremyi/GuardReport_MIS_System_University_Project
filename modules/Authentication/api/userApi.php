<?php
/** GuardReport — User API | File: modules/Authentication/api/userApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/models/UserModel.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$auth   = new AuthMiddleware();
$input  = empty($_POST) ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

try {
    $user = $auth->requireAuth(['users.view']);
    $um   = new UserModel(Database::getConnection());
    $db   = Database::getConnection();

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $filters = [
                        'search'  => $_GET['search']  ?? null,
                        'role_id' => $_GET['role_id'] ?? null,
                        'status'  => $_GET['status']  ?? null,
                    ];
                    $page    = max(1, (int)($_GET['page'] ?? 1));
                    $perPage = (int)($_GET['per_page'] ?? 25);
                    $all     = $um->getAllUsers($filters);
                    $total   = count($all);
                    $sliced  = array_slice($all, ($page - 1) * $perPage, $perPage);
                    // Strip password hashes from response
                    $sliced = array_map(function($u){ unset($u['password'],$u['otp_code'],$u['otp_expiry']); return $u; }, $sliced);
                    echo json_encode(['success'=>true,'data'=>[
                        'data'      => $sliced,
                        'total'     => $total,
                        'page'      => $page,
                        'per_page'  => $perPage,
                        'last_page' => (int)ceil($total / $perPage),
                    ]]);
                    break;

                case 'get':
                    $id = (int)($_GET['id'] ?? 0);
                    $u  = $um->getUserById($id);
                    if (!$u) { echo json_encode(['success'=>false,'message'=>'User not found']); break; }
                    unset($u['password'], $u['otp_code'], $u['otp_expiry'], $u['perm_keys']);
                    echo json_encode(['success'=>true,'data'=>$u]);
                    break;

                case 'stats':
                    echo json_encode(['success'=>true,'data'=>$um->getUserStats()]);
                    break;

                case 'guards':
                    echo json_encode(['success'=>true,'data'=>$um->getGuards()]);
                    break;

                case 'notifications':
                    $uid = (int)$user->user_id;
                    $s   = $db->prepare(
                        "SELECT *, CONCAT(TIMESTAMPDIFF(MINUTE,created_at,NOW()),' min ago') AS time_ago
                         FROM notifications WHERE user_id=:uid ORDER BY created_at DESC LIMIT 20"
                    );
                    $s->execute([':uid' => $uid]);
                    echo json_encode(['success'=>true,'data'=>$s->fetchAll()]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;

        case 'POST':
            switch ($action) {
                case 'create':
                    $auth->requireAuth(['users.create']);
                    $req = ['firstname','lastname','email','password'];
                    foreach ($req as $f) if (empty($input[$f])) { echo json_encode(['success'=>false,'message'=>"$f is required"]); exit; }
                    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>'Invalid email']); exit; }
                    if ($um->emailExists($input['email'])) { echo json_encode(['success'=>false,'message'=>'Email already registered']); exit; }
                    if (!empty($input['phone']) && $um->phoneExists($input['phone'])) { echo json_encode(['success'=>false,'message'=>'Phone already registered']); exit; }
                    if (strlen($input['password']) < 8) { echo json_encode(['success'=>false,'message'=>'Password must be at least 8 characters']); exit; }
                    if ($input['password'] !== ($input['confirm_password'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Passwords do not match']); exit; }
                    $uid = $um->createUser([
                        'firstname'      => trim($input['firstname']),
                        'lastname'       => trim($input['lastname']),
                        'email'          => strtolower(trim($input['email'])),
                        'phone'          => $input['phone'] ?? null,
                        'password'       => $input['password'],
                        'role_id'        => (int)($input['role_id'] ?? 4),
                        'account_status' => $input['account_status'] ?? 'active',
                        'created_by'     => (int)$user->user_id,
                    ]);
                    echo json_encode(['success'=>true,'message'=>'User created successfully','id'=>$uid]);
                    break;

                case 'update':
                    $auth->requireAuth(['users.update']);
                    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'User id required']); break; }
                    $existing = $um->getUserById($id);
                    if (!$existing) { echo json_encode(['success'=>false,'message'=>'User not found']); break; }
                    if (!empty($input['email']) && $um->emailExists($input['email'], $id))
                        { echo json_encode(['success'=>false,'message'=>'Email already in use']); break; }
                    if (!empty($input['phone']) && $um->phoneExists($input['phone'], $id))
                        { echo json_encode(['success'=>false,'message'=>'Phone already in use']); break; }
                    if (!empty($input['password'])) {
                        if (strlen($input['password']) < 8) { echo json_encode(['success'=>false,'message'=>'Password must be ≥ 8 chars']); break; }
                        if ($input['password'] !== ($input['confirm_password'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Passwords do not match']); break; }
                    } else {
                        unset($input['password'], $input['confirm_password']);
                    }
                    // Prevent non-super-admin from escalating to super-admin
                    if (!$user->is_super_admin) unset($input['is_super_admin']);
                    $ok = $um->updateUser($id, $input);
                    echo json_encode($ok ? ['success'=>true,'message'=>'User updated'] : ['success'=>false,'message'=>'Nothing changed']);
                    break;

                case 'status':
                    $auth->requireAuth(['users.deactivate']);
                    $id     = (int)($_GET['id'] ?? $input['id'] ?? 0);
                    $status = $input['status'] ?? '';
                    if (!in_array($status, ['active','inactive','suspended','pending'])) { echo json_encode(['success'=>false,'message'=>'Invalid status']); break; }
                    $target = $um->getUserById($id);
                    if (!$target) { echo json_encode(['success'=>false,'message'=>'User not found']); break; }
                    if ($target['is_super_admin'] && !$user->is_super_admin) { echo json_encode(['success'=>false,'message'=>'Cannot change super-admin status']); break; }
                    $ok = $um->updateStatus($id, $status);
                    echo json_encode($ok ? ['success'=>true,'message'=>'Status updated to '.$status] : ['success'=>false,'message'=>'Update failed']);
                    break;

                case 'mark-notification-read':
                    $nid = (int)($input['id'] ?? 0);
                    $db->prepare("UPDATE notifications SET is_read=1 WHERE id=:id AND user_id=:uid")
                       ->execute([':id'=>$nid, ':uid'=>(int)$user->user_id]);
                    echo json_encode(['success'=>true]);
                    break;

                case 'mark-all-read':
                    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=:uid")
                       ->execute([':uid'=>(int)$user->user_id]);
                    echo json_encode(['success'=>true]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;

        case 'DELETE':
            $auth->requireAuth(['users.delete']);
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) { echo json_encode(['success'=>false,'message'=>'User id required']); break; }
            if ($id === (int)$user->user_id) { echo json_encode(['success'=>false,'message'=>'Cannot delete your own account']); break; }
            $um->deleteUser($id);
            echo json_encode(['success'=>true,'message'=>'User deleted']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}