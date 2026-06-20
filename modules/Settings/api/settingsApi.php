<?php
/** GuardReport — Settings API | File: modules/Settings/api/settingsApi.php */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/controllers/SettingsController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$auth   = new AuthMiddleware();
$ctrl   = new SettingsController();

try {
    switch ($method) {
        case 'GET':
            $auth->requireAuth(['settings.view']);
            switch ($action) {
                case 'list':
                    echo json_encode(['success' => true, 'data' => $ctrl->index()]);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
            break;

        case 'POST':
            $user = $auth->requireAuth(['settings.manage']);
            $input = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
            switch ($action) {
                case 'update':
                    echo json_encode($ctrl->update($input, (int)$user->user_id));
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
