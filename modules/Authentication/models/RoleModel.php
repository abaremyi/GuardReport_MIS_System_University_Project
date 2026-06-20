<?php
/**
 * GuardReport — Role Model
 * File: modules/Authentication/models/RoleModel.php
 *
 * Ported from the SIPIS reference (column names already match
 * guardreport_db: permissions.key, permissions.module). Added:
 *   - system roles (is_system=1: Super Admin/Administrator/Supervisor/Guard)
 *     cannot be renamed or deleted from the UI.
 */
class RoleModel {
    private PDO $db;
    public function __construct(?PDO $db=null){ $this->db=$db?:Database::getConnection(); }

    public function getAllRoles(): array {
        return $this->db->query("SELECT r.*,COUNT(DISTINCT rp.permission_id) perm_count,COUNT(DISTINCT u.id) user_count
            FROM roles r LEFT JOIN role_permissions rp ON rp.role_id=r.id
            LEFT JOIN users u ON u.role_id=r.id GROUP BY r.id ORDER BY r.name")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getRoleById(int $id): ?array {
        $s=$this->db->prepare("SELECT * FROM roles WHERE id=:id"); $s->execute([':id'=>$id]); return $s->fetch()?:null;
    }
    public function createRole(array $d): int {
        if($this->nameExists($d['name'])) throw new Exception('Role name already exists');
        $this->db->prepare("INSERT INTO roles (name,description) VALUES (:n,:d)")->execute([':n'=>$d['name'],':d'=>$d['description']??null]);
        return (int)$this->db->lastInsertId();
    }
    public function updateRole(int $id,array $d): bool {
        $existing = $this->getRoleById($id);
        if (!$existing) throw new Exception('Role not found');
        if ((int)$existing['is_system'] === 1 && $d['name'] !== $existing['name']) {
            throw new Exception('Built-in system roles cannot be renamed');
        }
        if($this->nameExists($d['name'],$id)) throw new Exception('Role name already exists');
        return $this->db->prepare("UPDATE roles SET name=:n,description=:d WHERE id=:id")->execute([':n'=>$d['name'],':d'=>$d['description']??null,':id'=>$id]);
    }
    public function deleteRole(int $id): bool {
        $existing = $this->getRoleById($id);
        if (!$existing) throw new Exception('Role not found');
        if ((int)$existing['is_system'] === 1) {
            throw new Exception('Built-in system roles cannot be deleted');
        }
        $c=$this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id=:id"); $c->execute([':id'=>$id]);
        if($c->fetchColumn()>0) throw new Exception('Cannot delete role with assigned users');
        $this->db->prepare("DELETE FROM role_permissions WHERE role_id=:id")->execute([':id'=>$id]);
        return $this->db->prepare("DELETE FROM roles WHERE id=:id")->execute([':id'=>$id]);
    }
    public function nameExists(string $n,?int $ex=null): bool {
        $sql="SELECT COUNT(*) FROM roles WHERE name=:n"; $p=[':n'=>$n];
        if($ex){ $sql.=" AND id!=:id"; $p[':id']=$ex; }
        $s=$this->db->prepare($sql); $s->execute($p); return (bool)$s->fetchColumn();
    }
    public function getAllPermissions(): array {
        $rows=$this->db->query("SELECT * FROM permissions ORDER BY module,`key`")->fetchAll(PDO::FETCH_ASSOC);
        $g=[];
        foreach($rows as $p){ $p['action_label']=explode('.',$p['key'])[1]??$p['key']; $g[$p['module']][]=$p; }
        return $g;
    }
    public function getPermissionsFlat(): array {
        $rows=$this->db->query("SELECT * FROM permissions ORDER BY module,`key`")->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$p) $p['action_label']=explode('.',$p['key'])[1]??$p['key'];
        return $rows;
    }
    public function getRolePermissions(int $rid): array {
        $s=$this->db->prepare("SELECT permission_id FROM role_permissions WHERE role_id=:id");
        $s->execute([':id'=>$rid]); return $s->fetchAll(PDO::FETCH_COLUMN);
    }
    public function updateRolePermissions(int $rid,array $pids): bool {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM role_permissions WHERE role_id=:id")->execute([':id'=>$rid]);
            if(!empty($pids)){
                $ins=$this->db->prepare("INSERT INTO role_permissions (role_id,permission_id) VALUES (:r,:p)");
                foreach($pids as $pid) $ins->execute([':r'=>$rid,':p'=>$pid]);
            }
            $this->db->commit(); return true;
        } catch(Exception $e){ $this->db->rollBack(); throw $e; }
    }
    public function getStats(): array {
        return $this->db->query("SELECT COUNT(*) total_roles,
            (SELECT COUNT(*) FROM role_permissions) total_assignments,
            (SELECT COUNT(*) FROM permissions) total_permissions FROM roles")->fetch(PDO::FETCH_ASSOC)?:[];
    }
}
