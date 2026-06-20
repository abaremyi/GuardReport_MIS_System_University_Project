<?php
/**
 * GuardReport — Roles & Permissions
 * File: modules/Authentication/views/roles-permissions-management.php
 */
$pageTitle          = 'Roles & Permissions';
$currentPage        = 'users';
$requiredPermission = 'roles.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/controllers/RoleController.php';

$roleCtrl  = new RoleController();
$roles     = $roleCtrl->index();
$allPerms  = $roleCtrl->getPermissions();
$stats     = $roleCtrl->getStats();

$canCreate = $isSuperAdmin || hasPermission($userPermissions, 'roles.create');
$canEdit   = $isSuperAdmin || hasPermission($userPermissions, 'roles.edit');
$canDelete = $isSuperAdmin || hasPermission($userPermissions, 'roles.delete');
$canAssign = $isSuperAdmin || hasPermission($userPermissions, 'roles.assign_permissions');

$firstRoleId   = !empty($roles) ? (int)(reset($roles)['id']) : 0;
$firstRoleName = !empty($roles) ? reset($roles)['name'] : '';

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<style>
/* No border-radius anywhere on this page — square corners throughout. */
.rp-layout{display:grid;grid-template-columns:300px 1fr;gap:18px;align-items:start;margin-top:18px;}
@media(max-width:1024px){.rp-layout{grid-template-columns:1fr;}}

.rp-roles-card{background:#fff;border:1px solid var(--border);}
.rp-roles-head{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.rp-role-item{padding:12px 16px;border-bottom:1px solid var(--border);border-left:3px solid transparent;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background .12s;}
.rp-role-item:last-child{border-bottom:none;}
.rp-role-item:hover{background:var(--bg);}
.rp-role-item.active{background:var(--bg);border-left-color:var(--blue);}
.rp-role-icon{width:32px;height:32px;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:15px;flex-shrink:0;border:1px solid var(--border);}
.rp-role-name{font-size:13px;font-weight:700;color:var(--text-mid);}
.rp-role-meta{font-size:11.5px;color:var(--text-muted);margin-top:1px;}
.rp-role-sys{font-size:9.5px;font-weight:700;letter-spacing:.04em;color:var(--purple);text-transform:uppercase;}

.rp-perm-card{background:#fff;border:1px solid var(--border);}
.rp-perm-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--bg);flex-wrap:wrap;gap:10px;}
.rp-perm-body{padding:18px 20px;}
.rp-module{border:1px solid var(--border);margin-bottom:10px;}
.rp-module-head{padding:9px 14px;background:var(--bg);font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-mid);display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;}
.rp-module-body{padding:10px 14px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;}
.rp-check-label{display:flex;align-items:center;gap:7px;font-size:12.5px;cursor:pointer;padding:5px 6px;transition:background .1s;}
.rp-check-label:hover{background:var(--bg);}
.rp-check-label input[type=checkbox]{width:14px;height:14px;flex-shrink:0;accent-color:var(--blue);}
.rp-action-badge{font-size:10px;font-weight:700;padding:1px 6px;background:var(--bg);color:var(--text-muted);white-space:nowrap;border:1px solid var(--border);}
.rp-mod-dot{width:7px;height:7px;flex-shrink:0;background:var(--blue);}

.rp-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,30,50,.55);z-index:800;align-items:flex-start;justify-content:center;padding-top:8vh;overflow-y:auto;}
.rp-modal-overlay.open{display:flex;}
.rp-modal{background:#fff;width:480px;max-width:94vw;border:1px solid var(--border);}
.rp-modal-head{background:var(--navy);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;}
.rp-modal-body{padding:20px;}
</style>

<div class="gr-page-header">
  <div>
    <h1>Roles &amp; Permissions</h1>
    <div class="gr-breadcrumb">Define roles and control what each user group can access</div>
  </div>
  <div class="gr-page-actions">
    <?php if ($canCreate): ?>
      <button class="gr-btn gr-btn-primary" onclick="openCreateModal()"><i class="ri-add-circle-line"></i> New Role</button>
    <?php endif; ?>
  </div>
</div>

<div class="gr-stats-grid" style="margin-bottom:20px">
  <div class="gr-stat-card" style="--stat-color:var(--navy)">
    <div class="stat-label">Total Roles</div>
    <div class="stat-value"><?= (int)($stats['total_roles'] ?? 0) ?></div>
    <i class="ri-shield-star-line stat-icon"></i>
  </div>
  <div class="gr-stat-card" style="--stat-color:var(--purple)">
    <div class="stat-label">Permissions</div>
    <div class="stat-value"><?= (int)($stats['total_permissions'] ?? 0) ?></div>
    <i class="ri-key-2-line stat-icon"></i>
  </div>
  <div class="gr-stat-card" style="--stat-color:var(--blue)">
    <div class="stat-label">Assignments</div>
    <div class="stat-value"><?= (int)($stats['total_assignments'] ?? 0) ?></div>
    <i class="ri-links-line stat-icon"></i>
  </div>
</div>

<div class="rp-layout">
  <!-- ROLES LIST -->
  <div class="rp-roles-card">
    <div class="rp-roles-head">
      <span style="font-weight:700;font-size:13px">Roles</span>
      <span style="font-size:11.5px;color:var(--text-muted)"><?= count($roles) ?> total</span>
    </div>
    <?php foreach ($roles as $r): ?>
      <div class="rp-role-item<?= (int)$r['id']===$firstRoleId?' active':'' ?>"
           data-role-id="<?= $r['id'] ?>"
           onclick="selectRole(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>')">
        <div class="rp-role-icon"><i class="ri-shield-user-line"></i></div>
        <div style="flex:1">
          <div class="rp-role-name"><?= htmlspecialchars($r['name']) ?></div>
          <div class="rp-role-meta"><?= (int)$r['user_count'] ?> users · <?= (int)$r['perm_count'] ?> permissions</div>
        </div>
        <?php if ((int)$r['is_system'] === 1): ?>
          <span class="rp-role-sys" title="Built-in role">SYSTEM</span>
        <?php elseif ($canEdit || $canDelete): ?>
          <div style="display:flex;gap:4px" onclick="event.stopPropagation()">
            <?php if ($canEdit): ?>
              <button class="gr-btn gr-btn-outline gr-btn-sm" onclick="openEditModal(<?= $r['id'] ?>,'<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>','<?= htmlspecialchars($r['description'] ?? '', ENT_QUOTES) ?>')"><i class="ri-edit-line"></i></button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
              <button class="gr-btn gr-btn-outline gr-btn-sm" style="color:var(--red)" onclick="deleteRole(<?= $r['id'] ?>,'<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>')"><i class="ri-delete-bin-line"></i></button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- PERMISSION EDITOR -->
  <div class="rp-perm-card">
    <div class="rp-perm-head">
      <div>
        <div style="font-weight:700;font-size:14px" id="permCardTitle"><?= htmlspecialchars($firstRoleName) ?> — Permissions</div>
        <div style="font-size:12px;color:var(--text-muted)" id="permCardSub">Check permissions to grant access</div>
      </div>
      <?php if ($canAssign): ?>
        <button class="gr-btn gr-btn-primary gr-btn-sm" id="savePermBtn" onclick="savePermissions()"><i class="ri-save-line"></i> Save Permissions</button>
      <?php endif; ?>
    </div>
    <div class="rp-perm-body" id="permEditor">
      <div class="gr-empty" style="padding:24px 0"><span class="spinner"></span></div>
    </div>
  </div>
</div>

<!-- CREATE/EDIT ROLE MODAL -->
<div class="rp-modal-overlay" id="roleModal">
  <div class="rp-modal">
    <div class="rp-modal-head">
      <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:.06em;color:rgba(255,255,255,.55)" id="modalModeLabel">NEW ROLE</div>
        <div style="font-size:16px;font-weight:700;color:#fff" id="modalTitle">Create Role</div>
      </div>
      <button onclick="closeRoleModal()" style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,.7);font-size:20px"><i class="ri-close-line"></i></button>
    </div>
    <div class="rp-modal-body">
      <input type="hidden" id="modalRoleId">
      <div class="gr-form-group">
        <label>Role Name <span class="req">*</span></label>
        <input type="text" id="modalRoleName" class="gr-input" placeholder="e.g. Site Supervisor">
      </div>
      <div class="gr-form-group">
        <label>Description</label>
        <textarea id="modalRoleDesc" class="gr-textarea" rows="3" placeholder="What this role is for…"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button class="gr-btn gr-btn-outline" onclick="closeRoleModal()">Cancel</button>
        <button class="gr-btn gr-btn-primary" onclick="submitRole()"><i class="ri-save-line"></i> Save Role</button>
      </div>
    </div>
  </div>
</div>

<?php
$canAssignJs = (int)$canAssign;
$allPermsJs  = json_encode($allPerms);
$pageScripts = <<<JS
<script>
var ALL_PERMS    = {$allPermsJs};
var CAN_ASSIGN   = {$canAssignJs};
var selectedRoleId = {$firstRoleId};
var MODULE_COLORS  = ['#0F2744','#1d4ed8','#7c3aed','#dc2626','#16a34a','#d97706'];

function selectRole(id, name) {
    selectedRoleId = id;
    document.querySelectorAll('.rp-role-item').forEach(function(el){
        el.classList.toggle('active', +el.dataset.roleId === +id);
    });
    document.getElementById('permCardTitle').textContent = name + ' — Permissions';
    document.getElementById('permCardSub').textContent   = 'Check permissions to grant access';
    loadPermissions(id);
}

async function loadPermissions(roleId) {
    var editor = document.getElementById('permEditor');
    editor.innerHTML = '<div class="gr-empty" style="padding:24px 0"><span class="spinner"></span></div>';
    try {
        var res  = await fetch(BASE_URL+'/api/roles?action=role-permissions&role_id='+roleId, {credentials:'include'});
        var data = await res.json();
        renderPermEditor(new Set((data.success ? data.data : []).map(Number)));
    } catch(e) { renderPermEditor(new Set()); }
}

function renderPermEditor(assigned) {
    var modules = Object.keys(ALL_PERMS);
    if (!modules.length) {
        document.getElementById('permEditor').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:24px">No permissions found in database.</p>';
        return;
    }
    document.getElementById('permEditor').innerHTML = modules.map(function(mod, mi) {
        var perms  = ALL_PERMS[mod];
        var dot    = MODULE_COLORS[mi % MODULE_COLORS.length];
        var allIds = perms.map(function(p){ return p.id; });
        var allChk = allIds.every(function(id){ return assigned.has(+id); });
        return '<div class="rp-module">'
            +'<div class="rp-module-head" onclick="toggleModBody(this)">'
            +'<div style="display:flex;align-items:center;gap:8px">'
            +'<div class="rp-mod-dot" style="background:'+dot+'"></div>'
            +'<span>'+mod.charAt(0).toUpperCase()+mod.slice(1)+'</span>'
            +'<span style="font-size:10.5px;color:var(--text-muted);font-weight:400;text-transform:none">('+perms.length+')</span>'
            +'</div>'
            +'<label onclick="event.stopPropagation()" style="display:flex;align-items:center;gap:6px;font-size:11px;cursor:pointer;text-transform:none">'
            +'<input type="checkbox" class="mod-all-chk" data-ids="'+allIds.join(',')+'" '+(allChk?'checked':'')+' '+(!CAN_ASSIGN?'disabled':'')+' onchange="toggleAll(this)">All</label>'
            +'</div>'
            +'<div class="rp-module-body">'
            +perms.map(function(p) {
                return '<label class="rp-check-label">'
                    +'<input type="checkbox" class="perm-check" value="'+p.id+'" '+(assigned.has(+p.id)?'checked':'')+' '+(!CAN_ASSIGN?'disabled':'')+' onchange="syncMod(this)">'
                    +'<span style="flex:1">'+(p.action_label||p.key)+'</span>'
                    +'<span class="rp-action-badge">'+p.key+'</span>'
                    +'</label>';
            }).join('')
            +'</div></div>';
    }).join('');
}

function toggleModBody(head) {
    var b = head.nextElementSibling;
    b.style.display = b.style.display === 'none' ? '' : 'none';
}
function toggleAll(chk) {
    var ids = chk.dataset.ids.split(',').map(Number);
    document.querySelectorAll('.perm-check').forEach(function(c){ if (ids.includes(+c.value)) c.checked = chk.checked; });
}
function syncMod(chk) {
    var body = chk.closest('.rp-module-body');
    var allChk = body.previousElementSibling.querySelector('.mod-all-chk');
    if (!allChk) return;
    var ids = allChk.dataset.ids.split(',').map(Number);
    allChk.checked = ids.every(function(id){ var c = document.querySelector('.perm-check[value="'+id+'"]'); return c && c.checked; });
}

async function savePermissions() {
    if (!selectedRoleId) return;
    var ids = Array.prototype.map.call(document.querySelectorAll('.perm-check:checked'), function(c){ return c.value; });
    grLoading('Saving permissions…');
    try {
        var form = new FormData();
        ids.forEach(function(id){ form.append('permissions[]', id); });
        var res  = await fetch(BASE_URL+'/api/roles?action=update-permissions&role_id='+selectedRoleId, {method:'POST',credentials:'include',body:form});
        var data = await res.json();
        Swal.close();
        if (data.success) grSuccess('Saved!','Permissions updated');
        else grError('Error', data.message);
    } catch(e) { Swal.close(); grError('Error','Network error'); }
}

function openCreateModal() {
    document.getElementById('modalRoleId').value='';
    document.getElementById('modalRoleName').value='';
    document.getElementById('modalRoleDesc').value='';
    document.getElementById('modalModeLabel').textContent='NEW ROLE';
    document.getElementById('modalTitle').textContent='Create Role';
    document.getElementById('roleModal').classList.add('open');
    setTimeout(function(){ document.getElementById('modalRoleName').focus(); },100);
}
function openEditModal(id,name,desc) {
    document.getElementById('modalRoleId').value=id;
    document.getElementById('modalRoleName').value=name;
    document.getElementById('modalRoleDesc').value=desc;
    document.getElementById('modalModeLabel').textContent='EDIT ROLE';
    document.getElementById('modalTitle').textContent='Edit Role';
    document.getElementById('roleModal').classList.add('open');
}
function closeRoleModal() { document.getElementById('roleModal').classList.remove('open'); }

async function submitRole() {
    var id   = document.getElementById('modalRoleId').value;
    var name = document.getElementById('modalRoleName').value.trim();
    var desc = document.getElementById('modalRoleDesc').value.trim();
    if (!name) return grError('Validation','Role name is required');
    grLoading(id?'Updating…':'Creating…');
    var form = new FormData(); form.append('name',name); form.append('description',desc);
    try {
        var res  = await fetch(id?(BASE_URL+'/api/roles?action=update&id='+id):(BASE_URL+'/api/roles?action=create'), {method:'POST',credentials:'include',body:form});
        var data = await res.json();
        Swal.close();
        if (data.success) grSuccess('Saved!', data.message, function(){ location.reload(); });
        else grError('Error', data.message);
    } catch(e) { Swal.close(); grError('Error','Network error'); }
}
async function deleteRole(id,name) {
    grConfirm('Delete Role?','Remove "'+name+'"? Users assigned will lose access.', async function(){
        grLoading('Deleting…');
        try {
            var res  = await fetch(BASE_URL+'/api/roles?action=delete&id='+id, {method:'DELETE',credentials:'include'});
            var data = await res.json();
            Swal.close();
            if (data.success) grSuccess('Deleted!', data.message, function(){ location.reload(); });
            else grError('Error', data.message);
        } catch(e) { Swal.close(); grError('Error','Network error'); }
    });
}
document.getElementById('roleModal').addEventListener('click', function(e){ if (e.target === this) closeRoleModal(); });

if (selectedRoleId) loadPermissions(selectedRoleId);
</script>
JS;
require_once get_layout('admin-scripts');
