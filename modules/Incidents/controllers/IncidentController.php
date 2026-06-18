<?php
/**
 * GuardReport — Incident Controller
 * File: modules/Incidents/controllers/IncidentController.php
 */
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/UploadHelper.php';
require_once dirname(__DIR__, 1) . '/models/IncidentModel.php';

class IncidentController {
    private IncidentModel $im;
    private PDO           $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->im = new IncidentModel($this->db);
    }

    /* ── LIST ────────────────────────────────────────── */
    public function index(array $filters, int $page, ?object $user): array {
        // Guards can only see their own reports
        if (!$user->is_super_admin && !in_array('incidents.view', (array)$user->permissions)) {
            $filters['reported_by'] = $user->user_id;
        }
        // Supervisors see all but filtering is still allowed
        return $this->im->getAll($filters, $page);
    }

    /* ── SHOW ────────────────────────────────────────── */
    public function show(int $id, ?object $user): array {
        $inc = $this->im->getById($id);
        if (!$inc) return ['success' => false, 'message' => 'Incident not found', 'code' => 404];

        // Guard can only see own incidents
        if (!$user->is_super_admin
            && !in_array('incidents.view', (array)$user->permissions)
            && (int)$inc['reported_by'] !== (int)$user->user_id
        ) return ['success' => false, 'message' => 'Access denied', 'code' => 403];

        return ['success' => true, 'data' => $inc];
    }

    /* ── CREATE ──────────────────────────────────────── */
    public function store(array $d, ?object $user): array {
        $required = ['title', 'description', 'type_id', 'site_id', 'incident_date'];
        foreach ($required as $f)
            if (empty($d[$f])) return ['success' => false, 'message' => "Field '$f' is required"];

        if (strlen(trim($d['title'])) < 5)
            return ['success' => false, 'message' => 'Title must be at least 5 characters'];
        if (strlen(trim($d['description'])) < 10)
            return ['success' => false, 'message' => 'Description must be at least 10 characters'];

        $severity = in_array($d['severity'] ?? '', ['low','medium','high','critical'])
            ? $d['severity'] : 'medium';

        $id = $this->im->create([
            'title'         => trim($d['title']),
            'description'   => trim($d['description']),
            'type_id'       => (int)$d['type_id'],
            'site_id'       => (int)$d['site_id'],
            'reported_by'   => (int)$user->user_id,
            'severity'      => $severity,
            'incident_date' => $d['incident_date'],
            'latitude'      => !empty($d['latitude'])  ? (float)$d['latitude']  : null,
            'longitude'     => !empty($d['longitude']) ? (float)$d['longitude'] : null,
            'location_note' => $d['location_note'] ?? null,
        ]);

        return ['success' => true, 'message' => 'Incident submitted successfully', 'id' => $id];
    }

    /* ── UPDATE ──────────────────────────────────────── */
    public function update(int $id, array $d, ?object $user): array {
        $inc = $this->im->getById($id);
        if (!$inc) return ['success' => false, 'message' => 'Incident not found'];

        // Only admin/supervisor can edit; guard can edit own OPEN incidents
        $canEdit = $user->is_super_admin
            || in_array('incidents.update', (array)$user->permissions)
            || ((int)$inc['reported_by'] === (int)$user->user_id && $inc['status'] === 'open');
        if (!$canEdit) return ['success' => false, 'message' => 'You cannot edit this incident'];

        if (isset($d['severity']) && !in_array($d['severity'], ['low','medium','high','critical']))
            return ['success' => false, 'message' => 'Invalid severity value'];

        $ok = $this->im->update($id, $d, (int)$user->user_id);
        return $ok
            ? ['success' => true,  'message' => 'Incident updated']
            : ['success' => false, 'message' => 'No changes made'];
    }

    /* ── STATUS CHANGE ───────────────────────────────── */
    public function changeStatus(int $id, string $newStatus, string $notes, ?object $user): array {
        $validStatuses = ['open', 'reviewing', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses))
            return ['success' => false, 'message' => 'Invalid status'];

        $inc = $this->im->getById($id);
        if (!$inc) return ['success' => false, 'message' => 'Incident not found'];

        $canChange = $user->is_super_admin
            || in_array('incidents.close',  (array)$user->permissions)
            || in_array('incidents.update', (array)$user->permissions);
        if (!$canChange) return ['success' => false, 'message' => 'Insufficient permissions'];

        // Prevent re-opening closed incidents without admin permission
        if ($inc['status'] === 'closed' && !$user->is_super_admin
            && !in_array('incidents.delete', (array)$user->permissions))
            return ['success' => false, 'message' => 'Closed incidents cannot be re-opened'];

        $ok = $this->im->changeStatus($id, $newStatus, (int)$user->user_id, trim($notes));
        return $ok
            ? ['success' => true, 'message' => 'Status updated to ' . $newStatus]
            : ['success' => false, 'message' => 'Update failed'];
    }

    /* ── DELETE ──────────────────────────────────────── */
    public function destroy(int $id, ?object $user): array {
        if (!$user->is_super_admin && !in_array('incidents.delete', (array)$user->permissions))
            return ['success' => false, 'message' => 'Insufficient permissions'];
        $inc = $this->im->getById($id);
        if (!$inc) return ['success' => false, 'message' => 'Incident not found'];
        $ok = $this->im->delete($id, (int)$user->user_id);
        return $ok
            ? ['success' => true,  'message' => 'Incident deleted']
            : ['success' => false, 'message' => 'Delete failed'];
    }

    /* ── EVIDENCE UPLOAD ─────────────────────────────── */
    public function uploadEvidence(int $incidentId, array $files, ?object $user): array {
        $inc = $this->im->getById($incidentId);
        if (!$inc) return ['success' => false, 'message' => 'Incident not found'];

        $canUpload = $user->is_super_admin
            || in_array('evidence.upload', (array)$user->permissions)
            || (int)$inc['reported_by'] === (int)$user->user_id;
        if (!$canUpload) return ['success' => false, 'message' => 'Access denied'];

        $uploaded = []; $errors = [];
        foreach ($files as $file) {
            $result = UploadHelper::uploadEvidence($file, $incidentId);
            if ($result['success']) {
                $evId = $this->im->addEvidence($incidentId, [
                    'file_path'   => $result['file_path'],
                    'file_name'   => $result['file_name'],
                    'file_type'   => $result['file_type'],
                    'file_size'   => $result['file_size'],
                    'uploaded_by' => (int)$user->user_id,
                ]);
                $uploaded[] = ['id' => $evId, 'file_name' => $result['file_name'], 'file_path' => $result['file_path']];
            } else {
                $errors[] = $file['name'] . ': ' . $result['message'];
            }
        }
        if (empty($uploaded) && !empty($errors))
            return ['success' => false, 'message' => implode('; ', $errors)];

        return ['success' => true, 'uploaded' => $uploaded, 'errors' => $errors,
                'message' => count($uploaded) . ' file(s) uploaded'];
    }

    /* ── DELETE EVIDENCE ─────────────────────────────── */
    public function deleteEvidence(int $evId, ?object $user): array {
        $ev = $this->im->getEvidenceById($evId);
        if (!$ev) return ['success' => false, 'message' => 'Evidence not found'];

        $canDelete = $user->is_super_admin
            || in_array('evidence.delete', (array)$user->permissions)
            || (int)$ev['uploaded_by'] === (int)$user->user_id;
        if (!$canDelete) return ['success' => false, 'message' => 'Access denied'];

        UploadHelper::deleteFile($ev['file_path']);
        $ok = $this->im->deleteEvidence($evId);
        return $ok ? ['success' => true, 'message' => 'Evidence removed'] : ['success' => false, 'message' => 'Delete failed'];
    }

    public function getTypes(): array { return $this->im->getTypes(); }
    public function getStats(?int $siteId = null): array { return $this->im->getStats($siteId); }
}