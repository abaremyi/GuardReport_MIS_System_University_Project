<?php
/** GuardReport — Site API | File: modules/Sites/api/siteApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/models/SiteModel.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$auth   = new AuthMiddleware();
$input  = empty($_POST) ? (json_decode(file_get_contents('php://input'),true)?:[]) : $_POST;

try {
    $user = $auth->requireAuth(['sites.view']);
    $sm   = new SiteModel(Database::getConnection());

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    echo json_encode(['success'=>true,'data'=>$sm->getAll([
                        'status'=>$_GET['status']??'', 'search'=>$_GET['search']??''
                    ])]);
                    break;
                case 'get':
                    $site = $sm->getById((int)($_GET['id']??0));
                    echo json_encode($site ? ['success'=>true,'data'=>$site] : ['success'=>false,'message'=>'Not found']);
                    break;
                default: http_response_code(404); echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;

        case 'POST':
            $auth->requireAuth(['sites.create']);
            switch ($action) {
                case 'create':
                    if (empty($input['name'])||empty($input['address'])) {
                        echo json_encode(['success'=>false,'message'=>'Name and address are required']); break;
                    }
                    $id = $sm->create($input, (int)$user->user_id);
                    echo json_encode(['success'=>true,'message'=>'Site created','id'=>$id]);
                    break;
                case 'update':
                    $auth->requireAuth(['sites.update']);
                    $id = (int)($_GET['id']??0);
                    echo json_encode($sm->update($id,$input)
                        ? ['success'=>true,'message'=>'Site updated']
                        : ['success'=>false,'message'=>'Nothing changed']);
                    break;
                default: http_response_code(404); echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;

        case 'DELETE':
            $auth->requireAuth(['sites.delete']);
            $id = (int)($_GET['id']??0);
            $sm->delete($id);
            echo json_encode(['success'=>true,'message'=>'Site deleted']);
            break;

        default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}