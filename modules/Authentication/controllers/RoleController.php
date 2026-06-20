<?php
/** GuardReport — Role Controller | File: modules/Authentication/controllers/RoleController.php */
require_once dirname(__DIR__,1).'/models/RoleModel.php';
class RoleController {
    private RoleModel $rm;
    public function __construct(){ $this->rm=new RoleModel(); }
    public function index(): array  { return $this->rm->getAllRoles(); }
    public function show(int $id)   { return $this->rm->getRoleById($id); }
    public function getPermissions(){ return $this->rm->getAllPermissions(); }
    public function getPermissionsFlat(){ return $this->rm->getPermissionsFlat(); }
    public function getRolePermissions(int $rid){ return $this->rm->getRolePermissions($rid); }
    public function getStats()      { return $this->rm->getStats(); }
    public function store(array $d): array {
        try { if(empty($d['name'])) throw new Exception('Role name required');
              $id=$this->rm->createRole($d); return ['success'=>true,'message'=>'Role created','role_id'=>$id];
        } catch(Exception $e){ return ['success'=>false,'message'=>$e->getMessage()]; }
    }
    public function update(int $id,array $d): array {
        try { if(empty($d['name'])) throw new Exception('Role name required');
              $ok=$this->rm->updateRole($id,$d); return ['success'=>$ok,'message'=>$ok?'Role updated':'No changes'];
        } catch(Exception $e){ return ['success'=>false,'message'=>$e->getMessage()]; }
    }
    public function destroy(int $id): array {
        try { $ok=$this->rm->deleteRole($id); return ['success'=>$ok,'message'=>$ok?'Role deleted':'Failed'];
        } catch(Exception $e){ return ['success'=>false,'message'=>$e->getMessage()]; }
    }
    public function updateRolePermissions(int $rid,array $pids): array {
        try { $ok=$this->rm->updateRolePermissions($rid,array_map('intval',$pids)); return ['success'=>$ok,'message'=>$ok?'Permissions updated':'Failed'];
        } catch(Exception $e){ return ['success'=>false,'message'=>$e->getMessage()]; }
    }
}
