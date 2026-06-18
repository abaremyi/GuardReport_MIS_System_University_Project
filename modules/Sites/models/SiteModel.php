<?php
/** GuardReport — Site Model | File: modules/Sites/models/SiteModel.php */
class SiteModel {
    private PDO    $db;
    private string $t = 'sites';

    public function __construct(?PDO $db = null) { $this->db = $db ?: Database::getConnection(); }

    public function getAll(array $f = []): array {
        $sql = "SELECT s.*,
                CONCAT(u.firstname,' ',u.lastname) AS creator_name,
                (SELECT COUNT(*) FROM incidents i WHERE i.site_id=s.id) AS incident_count
                FROM {$this->t} s
                LEFT JOIN users u ON u.id = s.created_by
                WHERE 1=1";
        $p = [];
        if (!empty($f['status'])) { $sql .= " AND s.status=:st"; $p[':st'] = $f['status']; }
        if (!empty($f['search'])) {
            $sql .= " AND (s.name LIKE :s OR s.address LIKE :s OR s.client_name LIKE :s)";
            $p[':s'] = '%' . $f['search'] . '%';
        }
        $sql .= " ORDER BY s.name";
        $s = $this->db->prepare($sql); $s->execute($p);
        return $s->fetchAll();
    }

    public function getById(int $id): ?array {
        $s = $this->db->prepare(
            "SELECT s.*, CONCAT(u.firstname,' ',u.lastname) AS creator_name
             FROM {$this->t} s LEFT JOIN users u ON u.id=s.created_by
             WHERE s.id=:id LIMIT 1"
        );
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    public function create(array $d, int $userId): int {
        $s = $this->db->prepare(
            "INSERT INTO {$this->t} (name,address,city,client_name,client_phone,client_email,latitude,longitude,description,status,created_by)
             VALUES (:n,:a,:c,:cn,:cp,:ce,:lat,:lng,:d,:st,:cb)"
        );
        $s->execute([
            ':n'=>$d['name'],   ':a'=>$d['address'],        ':c'=>$d['city']??null,
            ':cn'=>$d['client_name']??null, ':cp'=>$d['client_phone']??null, ':ce'=>$d['client_email']??null,
            ':lat'=>$d['latitude']??null,   ':lng'=>$d['longitude']??null,
            ':d'=>$d['description']??null,  ':st'=>$d['status']??'active', ':cb'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $allowed = ['name','address','city','client_name','client_phone','client_email','latitude','longitude','description','status'];
        $sets=[]; $p=[':id'=>$id];
        foreach ($allowed as $f) { if (array_key_exists($f,$d)) { $sets[]="$f=:$f"; $p[":$f"]=$d[$f]; } }
        if (empty($sets)) return false;
        return $this->db->prepare("UPDATE {$this->t} SET ".implode(',',$sets).",updated_at=NOW() WHERE id=:id")->execute($p);
    }

    public function delete(int $id): bool {
        // Check for incidents
        $s = $this->db->prepare("SELECT COUNT(*) FROM incidents WHERE site_id=:id");
        $s->execute([':id'=>$id]);
        if ($s->fetchColumn() > 0) throw new Exception('Cannot delete a site that has incidents');
        return $this->db->prepare("DELETE FROM {$this->t} WHERE id=:id")->execute([':id'=>$id]);
    }
}