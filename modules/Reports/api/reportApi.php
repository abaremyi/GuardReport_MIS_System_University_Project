<?php
/**
 * GuardReport — Report API
 * File: modules/Reports/api/reportApi.php
 *
 * CHANGE LOG:
 *  - Added 'meta', 'data' (GET) and 'export' (POST) actions for the new
 *    Report Builder, routed through ReportBuilderController. Folded into
 *    this existing file (rather than a new reportBuilderApi.php) so no
 *    new route has to be registered — /api/reports already works.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/controllers/ReportBuilderController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$auth   = new AuthMiddleware();

try {
    /* ── POST actions (Report Builder export) ───────────────────── */
    if ($method === 'POST') {
        switch ($action) {
            case 'export':
                $user  = $auth->requireAuth(['reports.export']);
                $rbCtrl = new ReportBuilderController();
                $input  = json_decode(file_get_contents('php://input'), true) ?: [];
                $result = $rbCtrl->export(
                    $input['type'] ?? '',
                    (array)($input['fields'] ?? []),
                    (array)($input['filters'] ?? []),
                    (int)$user->user_id
                );
                echo json_encode(['success' => true, 'data' => $result]);
                break;
            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
        exit;
    }

    /* ── GET actions ──────────────────────────────────────────────── */
    $user = $auth->requireAuth(['reports.view']);
    $db   = Database::getConnection();

    // Report Builder: field catalog + filtered data
    if ($action === 'meta') {
        $rbCtrl = new ReportBuilderController();
        echo json_encode(['success' => true, 'data' => $rbCtrl->catalog()]);
        exit;
    }
    if ($action === 'data') {
        $rbCtrl = new ReportBuilderController();
        $type    = $_GET['type'] ?? '';
        $filters = [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to'   => $_GET['date_to']   ?? null,
            'site_id'   => $_GET['site_id']   ?? null,
            'guard_id'  => $_GET['guard_id']  ?? null,
            'role_id'   => $_GET['role_id']   ?? null,
            'severity'  => $_GET['severity']  ?? null,
            'status'    => $_GET['status']    ?? null,
        ];
        echo json_encode(['success' => true, 'data' => $rbCtrl->data($type, $filters)]);
        exit;
    }

    $siteId   = !empty($_GET['site_id'])   ? (int)$_GET['site_id']   : null;
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-12 months'));
    $dateTo   = $_GET['date_to']   ?? date('Y-m-d');

    switch ($action) {

        case 'summary':
            $sWhere = $siteId ? " AND site_id=$siteId" : '';
            $dWhere = " AND incident_date BETWEEN :df AND :dt";
            $p = [':df'=>$dateFrom, ':dt'=>$dateTo.' 23:59:59'];
            $row = $db->prepare(
                "SELECT COUNT(*) total,
                 SUM(status='open')       open_c,
                 SUM(status='reviewing')  reviewing_c,
                 SUM(status='resolved')   resolved_c,
                 SUM(status='closed')     closed_c,
                 SUM(severity='critical') critical_c,
                 SUM(severity='high')     high_c
                 FROM incidents WHERE 1=1$sWhere$dWhere"
            );
            $row->execute($p);
            $sites_count = $db->query("SELECT COUNT(*) FROM sites WHERE status='active'")->fetchColumn();
            $guards_count= $db->query("SELECT COUNT(*) FROM users WHERE role_id=4 AND account_status='active'")->fetchColumn();
            $data = $row->fetch();
            $data['active_sites']  = (int)$sites_count;
            $data['active_guards'] = (int)$guards_count;
            echo json_encode(['success'=>true,'data'=>$data]);
            break;

        case 'trend':
            // Monthly incident counts for last 12 months
            $months = []; $values = [];
            for ($i = 11; $i >= 0; $i--) {
                $ts    = strtotime("-$i months");
                $y     = date('Y', $ts); $m = date('m', $ts);
                $label = date('M Y', $ts);
                $months[] = $label;
                $s = $db->prepare(
                    "SELECT COUNT(*) FROM incidents WHERE YEAR(incident_date)=:y AND MONTH(incident_date)=:m" .
                    ($siteId ? " AND site_id=$siteId" : '')
                );
                $s->execute([':y'=>$y, ':m'=>$m]);
                $values[] = (int)$s->fetchColumn();
            }
            echo json_encode(['success'=>true,'data'=>['labels'=>$months,'values'=>$values]]);
            break;

        case 'by-type':
            $sWhere = $siteId ? " AND i.site_id=$siteId" : '';
            $s = $db->prepare(
                "SELECT it.name, COUNT(i.id) AS count
                 FROM incidents i
                 LEFT JOIN incident_types it ON it.id=i.type_id
                 WHERE i.incident_date BETWEEN :df AND :dt$sWhere
                 GROUP BY i.type_id ORDER BY count DESC"
            );
            $s->execute([':df'=>$dateFrom,':dt'=>$dateTo.' 23:59:59']);
            $rows = $s->fetchAll();
            echo json_encode(['success'=>true,'data'=>['labels'=>array_column($rows,'name'),'values'=>array_map('intval',array_column($rows,'count'))]]);
            break;

        case 'by-severity':
            $sWhere = $siteId ? " AND site_id=$siteId" : '';
            $s = $db->prepare(
                "SELECT SUM(severity='critical') c, SUM(severity='high') h, SUM(severity='medium') m, SUM(severity='low') l
                 FROM incidents WHERE incident_date BETWEEN :df AND :dt$sWhere"
            );
            $s->execute([':df'=>$dateFrom,':dt'=>$dateTo.' 23:59:59']);
            $r = $s->fetch();
            echo json_encode(['success'=>true,'data'=>[(int)$r['c'],(int)$r['h'],(int)$r['m'],(int)$r['l']]]);
            break;

        case 'by-site':
            $s = $db->prepare(
                "SELECT si.name, COUNT(i.id) AS count
                 FROM incidents i
                 LEFT JOIN sites si ON si.id=i.site_id
                 WHERE i.incident_date BETWEEN :df AND :dt
                 GROUP BY i.site_id ORDER BY count DESC LIMIT 10"
            );
            $s->execute([':df'=>$dateFrom,':dt'=>$dateTo.' 23:59:59']);
            $rows = $s->fetchAll();
            echo json_encode(['success'=>true,'data'=>['labels'=>array_column($rows,'name'),'values'=>array_map('intval',array_column($rows,'count'))]]);
            break;

        case 'guard-activity':
            $s = $db->prepare(
                "SELECT CONCAT(u.firstname,' ',u.lastname) AS name, COUNT(i.id) AS submissions,
                 SUM(i.severity='critical') AS critical_count
                 FROM incidents i LEFT JOIN users u ON u.id=i.reported_by
                 WHERE i.incident_date BETWEEN :df AND :dt
                 GROUP BY i.reported_by ORDER BY submissions DESC LIMIT 15"
            );
            $s->execute([':df'=>$dateFrom,':dt'=>$dateTo.' 23:59:59']);
            echo json_encode(['success'=>true,'data'=>$s->fetchAll()]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Unknown report action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
