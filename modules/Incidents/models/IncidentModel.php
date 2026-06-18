<?php
/** GuardReport — Incident Model | File: modules/Incidents/models/IncidentModel.php */
class IncidentModel {
    private PDO    $db;
    private string $t  = 'incidents';
    private string $te = 'incident_evidence';
    private string $tu = 'incident_updates';

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    /* ── LIST (with filters) ─────────────────────────── */
    public function getAll(array $f = [], int $page = 1, int $perPage = 20): array {
        $where = ['1=1']; $p = [];

        if (!empty($f['site_id']))   { $where[] = "i.site_id=:sid";      $p[':sid']  = $f['site_id']; }
        if (!empty($f['type_id']))   { $where[] = "i.type_id=:tid";      $p[':tid']  = $f['type_id']; }
        if (!empty($f['severity']))  { $where[] = "i.severity=:sev";     $p[':sev']  = $f['severity']; }
        if (!empty($f['status']))    { $where[] = "i.status=:sts";       $p[':sts']  = $f['status']; }
        if (!empty($f['reported_by'])){ $where[]= "i.reported_by=:rep";  $p[':rep']  = $f['reported_by']; }
        if (!empty($f['date_from'])) { $where[] = "i.incident_date>=:df";$p[':df']   = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = "i.incident_date<=:dt";$p[':dt']   = $f['date_to'] . ' 23:59:59'; }
        if (!empty($f['search'])) {
            $where[] = "(i.title LIKE :s OR i.description LIKE :s)";
            $p[':s'] = '%' . $f['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM {$this->t} i WHERE $whereStr";
        $s = $this->db->prepare($countSql); $s->execute($p);
        $total = (int)$s->fetchColumn();

        $sql = "SELECT i.*,
                it.name AS type_name, it.icon AS type_icon,
                si.name AS site_name,
                CONCAT(u.firstname,' ',u.lastname) AS reporter_name,
                u.photo AS reporter_photo,
                (SELECT COUNT(*) FROM {$this->te} e WHERE e.incident_id=i.id) AS evidence_count
                FROM {$this->t} i
                LEFT JOIN incident_types it ON it.id = i.type_id
                LEFT JOIN sites si          ON si.id = i.site_id
                LEFT JOIN users u           ON u.id  = i.reported_by
                WHERE $whereStr
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset";
        $s = $this->db->prepare($sql);
        foreach ($p as $k => $v) $s->bindValue($k, $v);
        $s->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $s->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $s->execute();
        $rows = $s->fetchAll();

        return [
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => (int)ceil($total / $perPage),
        ];
    }

    /* ── SINGLE with evidence + updates ─────────────── */
    public function getById(int $id): ?array {
        $sql = "SELECT i.*,
                it.name AS type_name, it.icon AS type_icon, it.default_severity,
                si.name AS site_name, si.address AS site_address,
                CONCAT(u.firstname,' ',u.lastname) AS reporter_name,
                u.photo AS reporter_photo, u.phone AS reporter_phone
                FROM {$this->t} i
                LEFT JOIN incident_types it ON it.id = i.type_id
                LEFT JOIN sites si          ON si.id = i.site_id
                LEFT JOIN users u           ON u.id  = i.reported_by
                WHERE i.id=:id LIMIT 1";
        $s = $this->db->prepare($sql); $s->execute([':id' => $id]);
        $inc = $s->fetch();
        if (!$inc) return null;

        // Evidence
        $s = $this->db->prepare(
            "SELECT e.*, CONCAT(u.firstname,' ',u.lastname) AS uploader_name
             FROM {$this->te} e LEFT JOIN users u ON u.id=e.uploaded_by
             WHERE e.incident_id=:id ORDER BY e.created_at"
        );
        $s->execute([':id' => $id]);
        $inc['evidence'] = $s->fetchAll();

        // Status history
        $s = $this->db->prepare(
            "SELECT iu.*, CONCAT(u.firstname,' ',u.lastname) AS actor_name
             FROM {$this->tu} iu LEFT JOIN users u ON u.id=iu.user_id
             WHERE iu.incident_id=:id ORDER BY iu.created_at"
        );
        $s->execute([':id' => $id]);
        $inc['updates'] = $s->fetchAll();

        return $inc;
    }

    /* ── CREATE ──────────────────────────────────────── */
    public function create(array $d): int {
        $sql = "INSERT INTO {$this->t}
                (title,description,type_id,site_id,reported_by,severity,status,incident_date,latitude,longitude,location_note)
                VALUES (:title,:desc,:tid,:sid,:rep,:sev,'open',:idate,:lat,:lng,:locnote)";
        $s = $this->db->prepare($sql);
        $s->execute([
            ':title'   => $d['title'],
            ':desc'    => $d['description'],
            ':tid'     => $d['type_id'],
            ':sid'     => $d['site_id'],
            ':rep'     => $d['reported_by'],
            ':sev'     => $d['severity']     ?? 'medium',
            ':idate'   => $d['incident_date'],
            ':lat'     => $d['latitude']     ?? null,
            ':lng'     => $d['longitude']    ?? null,
            ':locnote' => $d['location_note']?? null,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->logUpdate($id, $d['reported_by'], null, 'open', 'Incident submitted');
        $this->actLog($d['reported_by'], 'create', "Incident #$id submitted: " . $d['title']);
        return $id;
    }

    /* ── UPDATE ──────────────────────────────────────── */
    public function update(int $id, array $d, int $userId): bool {
        $allowed = ['title','description','type_id','site_id','severity','incident_date','latitude','longitude','location_note'];
        $sets = []; $p = [':id' => $id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=:$f"; $p[":$f"] = $d[$f]; }
        }
        if (empty($sets)) return false;
        $ok = $this->db->prepare(
            "UPDATE {$this->t} SET " . implode(',', $sets) . ",updated_at=NOW() WHERE id=:id"
        )->execute($p);
        if ($ok) $this->actLog($userId, 'update', "Incident #$id updated");
        return $ok;
    }

    /* ── STATUS CHANGE ───────────────────────────────── */
    public function changeStatus(int $id, string $newStatus, int $userId, string $notes = ''): bool {
        $inc = $this->db->prepare("SELECT status FROM {$this->t} WHERE id=:id LIMIT 1");
        $inc->execute([':id' => $id]); $row = $inc->fetch();
        if (!$row) return false;
        $oldStatus = $row['status'];
        $ok = $this->db->prepare(
            "UPDATE {$this->t} SET status=:s,updated_at=NOW() WHERE id=:id"
        )->execute([':s' => $newStatus, ':id' => $id]);
        if ($ok) {
            $this->logUpdate($id, $userId, $oldStatus, $newStatus, $notes);
            $this->actLog($userId, 'status_change', "Incident #$id: $oldStatus → $newStatus");
        }
        return $ok;
    }

    /* ── DELETE ──────────────────────────────────────── */
    public function delete(int $id, int $userId): bool {
        // Evidence files are deleted by CASCADE; also remove physical files
        $s = $this->db->prepare("SELECT file_path FROM {$this->te} WHERE incident_id=:id");
        $s->execute([':id' => $id]);
        foreach ($s->fetchAll() as $ev) {
            $full = UPLOADS_PATH . '/' . $ev['file_path'];
            if (file_exists($full)) unlink($full);
        }
        $ok = $this->db->prepare("DELETE FROM {$this->t} WHERE id=:id")->execute([':id' => $id]);
        if ($ok) $this->actLog($userId, 'delete', "Incident #$id deleted");
        return $ok;
    }

    /* ── EVIDENCE ────────────────────────────────────── */
    public function addEvidence(int $incidentId, array $d): int {
        $s = $this->db->prepare(
            "INSERT INTO {$this->te} (incident_id,file_path,file_name,file_type,file_size,uploaded_by)
             VALUES (:iid,:fp,:fn,:ft,:fs,:ub)"
        );
        $s->execute([
            ':iid' => $incidentId,
            ':fp'  => $d['file_path'],
            ':fn'  => $d['file_name'],
            ':ft'  => $d['file_type'],
            ':fs'  => $d['file_size'],
            ':ub'  => $d['uploaded_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getEvidenceById(int $id): ?array {
        $s = $this->db->prepare("SELECT * FROM {$this->te} WHERE id=:id LIMIT 1");
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    public function deleteEvidence(int $id): bool {
        return $this->db->prepare("DELETE FROM {$this->te} WHERE id=:id")->execute([':id' => $id]);
    }

    /* ── TYPES ───────────────────────────────────────── */
    public function getTypes(): array {
        return $this->db->query(
            "SELECT * FROM incident_types WHERE is_active=1 ORDER BY sort_order,name"
        )->fetchAll();
    }

    /* ── STATS ───────────────────────────────────────── */
    public function getStats(?int $siteId = null): array {
        $where = $siteId ? "WHERE site_id=$siteId" : '';
        $r = $this->db->query(
            "SELECT
             COUNT(*) total,
             SUM(status='open')       open_count,
             SUM(status='reviewing')  reviewing_count,
             SUM(status='resolved')   resolved_count,
             SUM(status='closed')     closed_count,
             SUM(severity='critical') critical_count,
             SUM(severity='high')     high_count
             FROM {$this->t} $where"
        )->fetch();
        return $r ?? array_fill_keys(['total','open_count','reviewing_count','resolved_count','closed_count','critical_count','high_count'], 0);
    }

    /* ── HELPERS ─────────────────────────────────────── */
    private function logUpdate(int $incidentId, int $userId, ?string $old, string $new, string $notes): void {
        $this->db->prepare(
            "INSERT INTO {$this->tu} (incident_id,user_id,old_status,new_status,notes) VALUES (:iid,:uid,:old,:new,:notes)"
        )->execute([':iid'=>$incidentId,':uid'=>$userId,':old'=>$old,':new'=>$new,':notes'=>$notes]);
    }
    private function actLog(int $uid, string $action, string $desc): void {
        try {
            $this->db->prepare(
                "INSERT INTO activity_log (user_id,action,module,description,ip_address) VALUES (:u,:a,'incidents',:d,:ip)"
            )->execute([':u'=>$uid,':a'=>$action,':d'=>$desc,':ip'=>$_SERVER['REMOTE_ADDR']??null]);
        } catch (Exception $e) { error_log('Log: '.$e->getMessage()); }
    }
}