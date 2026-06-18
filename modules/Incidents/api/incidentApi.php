<?php
/** GuardReport — Incident API | File: modules/Incidents/api/incidentApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/controllers/IncidentController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$auth   = new AuthMiddleware();
$ctrl   = new IncidentController();

$input = $_POST;
if (empty($_POST)) { $input = json_decode(file_get_contents('php://input'), true) ?: []; }

try {
    switch ($method) {

        case 'GET':
            $user = $auth->requireAuth(['incidents.view']);
            switch ($action) {
                case 'list':
                    $filters = [
                        'site_id'     => $_GET['site_id']    ?? null,
                        'type_id'     => $_GET['type_id']    ?? null,
                        'severity'    => $_GET['severity']   ?? null,
                        'status'      => $_GET['status']     ?? null,
                        'date_from'   => $_GET['date_from']  ?? null,
                        'date_to'     => $_GET['date_to']    ?? null,
                        'search'      => $_GET['search']     ?? null,
                        'reported_by' => $_GET['reporter']   ?? null,
                    ];
                    $page = max(1, (int)($_GET['page'] ?? 1));
                    echo json_encode(['success' => true, 'data' => $ctrl->index($filters, $page, $user)]);
                    break;

                case 'get':
                    $id = (int)($_GET['id'] ?? 0);
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
                    echo json_encode($ctrl->show($id, $user));
                    break;

                case 'types':
                    echo json_encode(['success' => true, 'data' => $ctrl->getTypes()]);
                    break;

                case 'stats':
                    $siteId = !empty($_GET['site_id']) ? (int)$_GET['site_id'] : null;
                    echo json_encode(['success' => true, 'data' => $ctrl->getStats($siteId)]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
            break;

        case 'POST':
            switch ($action) {
                case 'create':
                    $user = $auth->requireAuth(['incidents.create']);
                    echo json_encode($ctrl->store($input, $user));
                    break;

                case 'update':
                    $user = $auth->requireAuth(['incidents.update']);
                    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
                    echo json_encode($ctrl->update($id, $input, $user));
                    break;

                case 'status':
                    $user = $auth->requireAuth(['incidents.update']);
                    $id        = (int)($_GET['id'] ?? $input['id'] ?? 0);
                    $newStatus = $input['status'] ?? '';
                    $notes     = $input['notes']  ?? '';
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
                    echo json_encode($ctrl->changeStatus($id, $newStatus, $notes, $user));
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
            break;

        case 'DELETE':
            $user = $auth->requireAuth(['incidents.delete']);
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
            echo json_encode($ctrl->destroy($id, $user));
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}