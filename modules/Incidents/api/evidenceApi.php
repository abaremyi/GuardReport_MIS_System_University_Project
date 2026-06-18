<?php
/** GuardReport — Evidence API | 
 * File: modules/Incidents/api/evidenceApi.php 
 * Functionality: Handles file uploads and deletions for incident evidence.
 * Utilizes AuthMiddleware for authentication and IncidentController for business logic.
 * Endpoints: 
 * POST /api/evidence?incident_id=123 - Upload one or more files for a specific incident.
 * DELETE /api/evidence?id=456 - Delete a specific evidence file by its ID.
 * Security: Validates file types, sizes, and user permissions. Returns JSON responses with success status and messages.
 * Note: Ensure that the server's PHP configuration allows for file uploads and that the UPLOADS_PATH is correctly set in the config. Always keep security in mind when handling file uploads to prevent vulnerabilities.
 * 
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/controllers/IncidentController.php';

$method = $_SERVER['REQUEST_METHOD'];
$auth   = new AuthMiddleware();
$ctrl   = new IncidentController();

try {
    if ($method === 'POST') {
        $user       = $auth->requireAuth(['evidence.upload']);
        $incidentId = (int)($_GET['incident_id'] ?? $_POST['incident_id'] ?? 0);
        if (!$incidentId) { echo json_encode(['success'=>false,'message'=>'incident_id required']); exit; }
        if (empty($_FILES)) { echo json_encode(['success'=>false,'message'=>'No files uploaded']); exit; }

        // Normalise $_FILES into a flat array of individual files
        $files = [];
        if (isset($_FILES['files'])) {
            $f = $_FILES['files'];
            if (is_array($f['name'])) {
                for ($i = 0; $i < count($f['name']); $i++) {
                    $files[] = [
                        'name'     => $f['name'][$i],
                        'type'     => $f['type'][$i],
                        'tmp_name' => $f['tmp_name'][$i],
                        'error'    => $f['error'][$i],
                        'size'     => $f['size'][$i],
                    ];
                }
            } else {
                $files[] = $f;
            }
        } elseif (isset($_FILES['file'])) {
            $files[] = $_FILES['file'];
        }

        if (empty($files)) { echo json_encode(['success'=>false,'message'=>'No valid files found']); exit; }
        echo json_encode($ctrl->uploadEvidence($incidentId, $files, $user));

    } elseif ($method === 'DELETE') {
        $user = $auth->requireAuth(['evidence.delete']);
        $id   = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
        echo json_encode($ctrl->deleteEvidence($id, $user));

    } else {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}