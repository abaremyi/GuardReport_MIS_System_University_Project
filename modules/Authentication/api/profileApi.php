<?php
/**
 * GuardReport — Profile Management API Endpoint
 * File: modules/Authentication/api/profileApi.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/controllers/ProfileController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$auth = new AuthMiddleware();
$profileController = new ProfileController();

try {
    switch ($method) {
        case 'GET':
            $currentUser = $auth->requireAuth();

            switch ($action) {
                case 'get':
                    $result = $profileController->getProfile($currentUser->user_id);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'settings':
                    $result = $profileController->getSettings($currentUser->user_id);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'notification-settings':
                    $result = $profileController->getNotificationSettings($currentUser->user_id);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'activity':
                    $limit = (int)($_GET['limit'] ?? 50);
                    $result = $profileController->getActivityLog($currentUser->user_id, $limit);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'POST':
        case 'PUT':
            $currentUser = $auth->requireAuth();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            switch ($action) {
                case 'update-profile':
                    $result = $profileController->updateProfile($currentUser->user_id, $input, $_FILES);
                    echo json_encode($result);
                    break;

                case 'update-settings':
                    $result = $profileController->updateSettings($currentUser->user_id, $input);
                    echo json_encode($result);
                    break;

                case 'change-password':
                    $result = $profileController->changePassword($currentUser->user_id, $input);
                    echo json_encode($result);
                    break;

                case 'upload-avatar':
                    $result = $profileController->uploadAvatar($currentUser->user_id, $_FILES);
                    echo json_encode($result);
                    break;

                case 'update-notifications':
                    $result = $profileController->updateNotificationSettings($currentUser->user_id, $input);
                    echo json_encode($result);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
