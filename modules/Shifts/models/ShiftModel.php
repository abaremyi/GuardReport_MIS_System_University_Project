<?php
/** GuardReport — Shift Model | File: modules/Shifts/models/ShiftModel.php */
class ShiftModel {
    private PDO    $db;
    private string $t = 'shifts';

    public function __construct(?PDO $db = null) { $this->db = $db ?: Database::getConnection(); }

    public function getAll(array $f = []): array {
        $sql = "SELECT sh.*,
                CONCAT(g.firstname,' ',g.lastname) AS guard_name, g.phone AS guard_phone,
                si.name AS site_name,
                CONCAT(cb.firstname,' ',cb.lastname) AS created_by_name
                FROM {$this->t} sh
                LEFT JOIN users u ON u.id = sh.guard_id
                LEFT JOIN users g ON g.id = sh.guard_id
                LEFT JOIN sites si ON si.id = sh.site_id
                LEFT JOIN users cb ON cb.id = sh.created_by
                WHERE 1=1";
        $p = [];
        if (!empty($f['guard_id'])) { $sql .= " AND sh.guard_id=:gid";  $p[':gid'] = $f['guard_id']; }
        if (!empty($f['site_id']))  { $sql .= " AND sh.site_id=:sid";   $p[':sid'] = $f['site_id']; }
        if (!empty($f['status']))   { $sql .= " AND sh.status=:st";     $p[':st']  = $f['status']; }
        if (!empty($f['date_from'])){ $sql .= " AND sh.start_time>=:df";$p[':df']  = $f['date_from']; }
        if (!empty($f['date_to']))  { $sql .= " AND sh.end_time<=:dt";  $p[':dt']  = $f['date_to'] . ' 23:59:59'; }
        $sql .= " ORDER BY sh.start_time DESC";
        $s = $this->db->prepare($sql); $s->execute($p);
        return $s->fetchAll();
    }

    public function getById(int $id): ?array {
        $s = $this->db->prepare(
            "SELECT sh.*,
             CONCAT(g.firstname,' ',g.lastname) AS guard_name,
             si.name AS site_name
             FROM {$this->t} sh
             LEFT JOIN users g  ON g.id = sh.guard_id
             LEFT JOIN sites si ON si.id = sh.site_id
             WHERE sh.id=:id LIMIT 1"
        );
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    public function hasOverlap(int $guardId, string $start, string $end, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->t}
                WHERE guard_id=:gid AND status NOT IN ('cancelled','completed')
                AND start_time < :end AND end_time > :start";
        $p = [':gid'=>$guardId, ':start'=>$start, ':end'=>$end];
        if ($excludeId) { $sql .= " AND id!=:eid"; $p[':eid'] = $excludeId; }
        return (bool)$this->db->prepare($sql)->execute($p) && $this->db->query("SELECT ROW_COUNT()")->fetchColumn() > 0
            ?: (bool)$this->db->prepare($sql)->execute($p) && false
            ?: (function() use ($sql,$p) {
                $s=$this->db->prepare($sql); $s->execute($p); return (int)$s->fetchColumn() > 0;
            })();
    }

    public function create(array $d, int $createdBy): int {
        $s = $this->db->prepare(
            "INSERT INTO {$this->t} (guard_id,site_id,start_time,end_time,status,notes,created_by)
             VALUES (:gid,:sid,:st,:et,'scheduled',:n,:cb)"
        );
        $s->execute([
            ':gid'=>$d['guard_id'], ':sid'=>$d['site_id'],
            ':st'=>$d['start_time'], ':et'=>$d['end_time'],
            ':n'=>$d['notes']??null, ':cb'=>$createdBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $allowed = ['guard_id','site_id','start_time','end_time','status','notes'];
        $sets=[]; $p=[':id'=>$id];
        foreach ($allowed as $f) { if (array_key_exists($f,$d)) { $sets[]="$f=:$f"; $p[":$f"]=$d[$f]; } }
        if (empty($sets)) return false;
        return $this->db->prepare("UPDATE {$this->t} SET ".implode(',',$sets).",updated_at=NOW() WHERE id=:id")->execute($p);
    }

    public function delete(int $id): bool {
        $sh = $this->getById($id);
        if ($sh && $sh['status'] === 'active')
            throw new Exception('Cannot delete an active shift');
        return $this->db->prepare("DELETE FROM {$this->t} WHERE id=:id")->execute([':id'=>$id]);
    }

    public function getUpcoming(int $guardId): array {
        $s = $this->db->prepare(
            "SELECT sh.*, si.name AS site_name, si.address AS site_address
             FROM {$this->t} sh LEFT JOIN sites si ON si.id=sh.site_id
             WHERE sh.guard_id=:gid AND sh.start_time>=NOW() AND sh.status='scheduled'
             ORDER BY sh.start_time LIMIT 10"
        );
        $s->execute([':gid'=>$guardId]);
        return $s->fetchAll();
    }
}