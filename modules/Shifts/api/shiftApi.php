<?php
/** GuardReport — Shift API | File: modules/Shifts/api/shiftApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/models/ShiftModel.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$auth   = new AuthMiddleware();
$input  = empty($_POST) ? (json_decode(file_get_contents('php://input'),true)?:[]) : $_POST;

try {
    $user = $auth->requireAuth(['shifts.view']);
    $sm   = new ShiftModel(Database::getConnection());

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $f = ['guard_id'=>$_GET['guard_id']??null,'site_id'=>$_GET['site_id']??null,
                          'status'=>$_GET['status']??null,'date_from'=>$_GET['date_from']??null,'date_to'=>$_GET['date_to']??null];
                    echo json_encode(['success'=>true,'data'=>$sm->getAll($f)]);
                    break;
                case 'get':
                    $sh = $sm->getById((int)($_GET['id']??0));
                    echo json_encode($sh ? ['success'=>true,'data'=>$sh] : ['success'=>false,'message'=>'Not found']);
                    break;
                case 'my-shifts':
                    echo json_encode(['success'=>true,'data'=>$sm->getUpcoming((int)$user->user_id)]);
                    break;
                default: http_response_code(404); echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;

        case 'POST':
            switch ($action) {
                case 'create':
                    $auth->requireAuth(['shifts.create']);
                    $req = ['guard_id','site_id','start_time','end_time'];
                    foreach ($req as $f) if (empty($input[$f])) { echo json_encode(['success'=>false,'message'=>"$f required"]); exit; }
                    if (strtotime($input['end_time']) <= strtotime($input['start_time']))
                        { echo json_encode(['success'=>false,'message'=>'End time must be after start time']); exit; }
                    $id = $sm->create($input, (int)$user->user_id);
                    echo json_encode(['success'=>true,'message'=>'Shift scheduled','id'=>$id]);
                    break;
                case 'update':
                    $auth->requireAuth(['shifts.update']);
                    $id = (int)($_GET['id']??0);
                    echo json_encode($sm->update($id,$input) ? ['success'=>true,'message'=>'Shift updated'] : ['success'=>false,'message'=>'Nothing changed']);
                    break;
                default: http_response_code(404); echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;

        case 'DELETE':
            $auth->requireAuth(['shifts.delete']);
            $id = (int)($_GET['id']??0);
            $sm->delete($id);
            echo json_encode(['success'=>true,'message'=>'Shift cancelled']);
            break;

        default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}