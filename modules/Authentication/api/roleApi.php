<?php
/** GuardReport — Role API | File: modules/Authentication/api/roleApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}
require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/config.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';
require_once dirname(__DIR__,1).'/controllers/RoleController.php';

$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';
$auth=new AuthMiddleware();
$ctrl=new RoleController();
$input=$_POST;
if(empty($_POST)){ $input=json_decode(file_get_contents('php://input'),true)?:[]; }

try {
    switch($method){
        case 'GET':
            $auth->requireAuth(['roles.view']);
            switch($action){
                case 'list':            echo json_encode(['success'=>true,'data'=>$ctrl->index()]); break;
                case 'get':             echo json_encode(['success'=>true,'data'=>$ctrl->show((int)($_GET['id']??0))]); break;
                case 'permissions':     echo json_encode(['success'=>true,'data'=>$ctrl->getPermissions()]); break;
                case 'permissions-flat':echo json_encode(['success'=>true,'data'=>$ctrl->getPermissionsFlat()]); break;
                case 'role-permissions':echo json_encode(['success'=>true,'data'=>$ctrl->getRolePermissions((int)($_GET['role_id']??0))]); break;
                case 'stats':           echo json_encode(['success'=>true,'data'=>$ctrl->getStats()]); break;
                default: http_response_code(404); echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;
        case 'POST':
            switch($action){
                case 'create':
                    $auth->requireAuth(['roles.create']);
                    echo json_encode($ctrl->store($_POST?:$input)); break;
                case 'update':
                    $auth->requireAuth(['roles.edit']);
                    echo json_encode($ctrl->update((int)($_GET['id']??0),$_POST?:$input)); break;
                case 'update-permissions':
                    $auth->requireAuth(['roles.assign_permissions']);
                    $pids=$_POST['permissions']??$input['permissions']??[];
                    echo json_encode($ctrl->updateRolePermissions((int)($_GET['role_id']??0),(array)$pids)); break;
                default: http_response_code(404); echo json_encode(['success'=>false,'message'=>'Unknown action']);
            }
            break;
        case 'DELETE':
            $auth->requireAuth(['roles.delete']);
            $id=(int)($_GET['id']??$input['id']??0);
            echo json_encode($ctrl->destroy($id)); break;
        default:
            http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch(Exception $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
