<?php
/** GuardReport — Users Management | File: modules/Authentication/views/users-management.php */
$pageTitle        = 'Users';
$currentPage      = 'users';
$requiredPermission = 'users.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/models/UserModel.php';

$um     = new UserModel(Database::getConnection());
$roles  = $um->getAllRoles();
$uStats = $um->getUserStats();

$canCreate     = $isSuperAdmin || hasPermission($userPermissions, 'users.create');
$canEdit       = $isSuperAdmin || hasPermission($userPermissions, 'users.update');
$canDelete     = $isSuperAdmin || hasPermission($userPermissions, 'users.delete');
$canDeactivate = $isSuperAdmin || hasPermission($userPermissions, 'users.deactivate');

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>User Management</h1>
    <div class="gr-breadcrumb">Guards, supervisors, and administrators</div>
  </div>
  <div class="gr-page-actions">
    <?php if ($canCreate): ?>
      <button class="gr-btn gr-btn-primary" onclick="openUserModal()">
        <i class="ri-user-add-line"></i> Add User
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Stats -->
<div class="gr-stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px">
  <?php foreach ([
    ['Total',     $uStats['total'],     'var(--navy)',  'ri-group-line'],
    ['Active',    $uStats['active'],    'var(--green)', 'ri-user-follow-line'],
    ['Pending',   $uStats['pending'],   'var(--amber)', 'ri-time-line'],
    ['Inactive',  $uStats['inactive'],  'var(--muted)', 'ri-user-unfollow-line'],
    ['Suspended', $uStats['suspended'], 'var(--red)',   'ri-user-forbid-line'],
  ] as [$l,$v,$c,$ic]): ?>
    <div class="gr-stat-card" style="--stat-color:<?= $c ?>">
      <div class="stat-label"><?= $l ?></div>
      <div class="stat-value"><?= $v ?></div>
      <i class="<?= $ic ?> stat-icon"></i>
    </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="gr-card mb-4">
  <div class="gr-filter-bar">
    <div class="gr-search-wrap">
      <i class="ri-search-line"></i>
      <input type="text" id="uSearch" class="gr-input" placeholder="Search name, email, phone…">
    </div>
    <select id="uRole" class="gr-select">
      <option value="">All Roles</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="uStatus" class="gr-select">
      <option value="">All Statuses</option>
      <option value="active">Active</option>
      <option value="pending">Pending</option>
      <option value="inactive">Inactive</option>
      <option value="suspended">Suspended</option>
    </select>
    <button class="gr-btn gr-btn-outline" onclick="loadUsers()"><i class="ri-search-line"></i> Search</button>
    <button class="gr-btn gr-btn-outline" onclick="clearUFilters()"><i class="ri-refresh-line"></i></button>
  </div>
</div>

<!-- Table -->
<div class="gr-card">
  <div class="gr-table-wrap">
    <table class="gr-table">
      <thead>
        <tr>
          <th>User</th><th>Role</th><th>Phone</th><th>Status</th>
          <th>Last Login</th><th>Joined</th><th></th>
        </tr>
      </thead>
      <tbody id="usersTbody">
        <tr><td colspan="7" style="text-align:center;padding:32px"><span class="spinner"></span></td></tr>
      </tbody>
    </table>
  </div>
  <div id="usersPagination" class="gr-pagination"></div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" style="display:none" class="gr-modal-backdrop">
  <div class="gr-modal" style="max-width:580px">
    <div class="gr-modal-header">
      <div class="gr-modal-title" id="userModalTitle">Add New User</div>
      <button onclick="closeUserModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-muted)">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="gr-modal-body">
      <input type="hidden" id="userId">
      <div class="gr-form-row">
        <div class="gr-form-group">
          <label>First Name <span class="req">*</span></label>
          <input id="uFirst" type="text" class="gr-input" autocomplete="off">
        </div>
        <div class="gr-form-group">
          <label>Last Name <span class="req">*</span></label>
          <input id="uLast" type="text" class="gr-input" autocomplete="off">
        </div>
      </div>
      <div class="gr-form-group">
        <label>Email <span class="req">*</span></label>
        <input id="uEmail" type="email" class="gr-input" autocomplete="off">
      </div>
      <div class="gr-form-row">
        <div class="gr-form-group">
          <label>Phone</label>
          <input id="uPhone" type="tel" class="gr-input" autocomplete="off">
        </div>
        <div class="gr-form-group">
          <label>Role <span class="req">*</span></label>
          <select id="uRoleSel" class="gr-select">
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="gr-form-row">
        <div class="gr-form-group">
          <label id="uPwdLabel">Password <span class="req">*</span></label>
          <input id="uPwd" type="password" class="gr-input" autocomplete="new-password" placeholder="Min 8 characters">
        </div>
        <div class="gr-form-group">
          <label id="uCpwdLabel">Confirm Password <span class="req">*</span></label>
          <input id="uCpwd" type="password" class="gr-input" autocomplete="new-password" placeholder="Repeat password">
        </div>
      </div>
      <div class="gr-form-group">
        <label>Account Status</label>
        <select id="uStatusSel" class="gr-select">
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>
    </div>
    <div class="gr-modal-footer">
      <button class="gr-btn gr-btn-outline" onclick="closeUserModal()">Cancel</button>
      <button class="gr-btn gr-btn-primary" onclick="saveUser()">
        <i class="ri-save-line"></i> Save User
      </button>
    </div>
  </div>
</div>

<!-- View User Modal -->
<div id="viewModal" style="display:none" class="gr-modal-backdrop">
  <div class="gr-modal" style="max-width:500px">
    <div class="gr-modal-header">
      <div class="gr-modal-title">User Profile</div>
      <button onclick="closeViewModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-muted)">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="gr-modal-body" id="viewModalBody">Loading…</div>
    <div class="gr-modal-footer" id="viewModalFooter"></div>
  </div>
</div>

<?php
$canEditJs     = (int)$canEdit;
$canDeleteJs   = (int)$canDelete;
$canDeactJs    = (int)$canDeactivate;
$currentUserId = (int)$currentUser->user_id;
$pageScripts = <<<JS
<script>
var _canEdit   = {$canEditJs};
var _canDelete = {$canDeleteJs};
var _canDeact  = {$canDeactJs};
var _myId      = {$currentUserId};
var _uPage     = 1;

function loadUsers(page) {
  _uPage = page || 1;
  var p = new URLSearchParams({
    action:'list', page:_uPage,
    search:  document.getElementById('uSearch').value.trim(),
    role_id: document.getElementById('uRole').value,
    status:  document.getElementById('uStatus').value,
  });
  var tbody = document.getElementById('usersTbody');
  tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px"><span class="spinner"></span></td></tr>';
  fetch(BASE_URL+'/api/users?'+p,{credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) { tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--red)">Failed to load</td></tr>'; return; }
      renderUsers(d.data);
    });
}

function renderUsers(data) {
  var tbody = document.getElementById('usersTbody');
  var pager = document.getElementById('usersPagination');
  var rows  = Array.isArray(data) ? data : (data.data||[]);
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="gr-empty" style="padding:32px;text-align:center">No users found</td></tr>';
    pager.innerHTML = '';
    return;
  }
  tbody.innerHTML = rows.map(function(u) {
    var initials = (u.firstname||'?').charAt(0).toUpperCase()+(u.lastname||'?').charAt(0).toUpperCase();
    var photo = u.photo ? '<img src="'+BASE_URL+'/uploads/'+u.photo+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover">'
      : '<div style="width:36px;height:36px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">'+initials+'</div>';
    var actions = '<button onclick="viewUser('+u.id+')" class="gr-btn gr-btn-outline gr-btn-sm"><i class="ri-eye-line"></i></button> ';
    if(_canEdit) actions += '<button onclick="editUser('+u.id+')" class="gr-btn gr-btn-outline gr-btn-sm"><i class="ri-edit-line"></i></button> ';
    if(_canDelete && u.id !== _myId && !u.is_super_admin) actions += '<button onclick="deleteUser('+u.id+')" class="gr-btn gr-btn-outline gr-btn-sm" style="color:var(--red)"><i class="ri-delete-bin-line"></i></button>';
    return '<tr>'
      +'<td><div style="display:flex;align-items:center;gap:10px">'+photo
      +'<div><div style="font-weight:600;font-size:14px">'+escH(u.firstname+' '+u.lastname)+'</div>'
      +'<div style="font-size:12px;color:var(--text-muted)">'+escH(u.email)+'</div></div></div></td>'
      +'<td><span style="font-size:12px;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:2px 8px">'+escH(u.role_display||u.role_name||'—')+'</span></td>'
      +'<td style="font-size:13px">'+escH(u.phone||'—')+'</td>'
      +'<td><span class="gr-badge gr-badge-'+u.account_status+'">'+u.account_status+'</span></td>'
      +'<td style="font-size:12px;color:var(--text-muted)">'+(u.last_login?u.last_login.slice(0,10):'Never')+'</td>'
      +'<td style="font-size:12px;color:var(--text-muted)">'+u.created_at.slice(0,10)+'</td>'
      +'<td class="td-actions">'+actions+'</td>'
      +'</tr>';
  }).join('');
  if (data.last_page > 1) {
    var btns='';
    for(var p=1;p<=data.last_page;p++) btns+='<button onclick="loadUsers('+p+')" class="'+(p===data.page?'active':'')+'">'+p+'</button>';
    pager.innerHTML=btns;
  } else { pager.innerHTML=''; }
}

function escH(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
function clearUFilters(){ ['uSearch','uRole','uStatus'].forEach(function(id){ document.getElementById(id).value=''; }); loadUsers(); }

/* ── ADD/EDIT ────────────────────────────── */
function openUserModal(u) {
  document.getElementById('userId').value = u ? u.id : '';
  document.getElementById('userModalTitle').textContent = u ? 'Edit User' : 'Add New User';
  document.getElementById('uFirst').value  = u ? u.firstname  : '';
  document.getElementById('uLast').value   = u ? u.lastname   : '';
  document.getElementById('uEmail').value  = u ? u.email      : '';
  document.getElementById('uPhone').value  = u ? (u.phone||'') : '';
  document.getElementById('uRoleSel').value= u ? (u.role_id||4) : 4;
  document.getElementById('uStatusSel').value = u ? u.account_status : 'active';
  var isEdit = !!u;
  document.getElementById('uPwdLabel').innerHTML  = 'Password'  +(isEdit?'':' <span class="req">*</span>');
  document.getElementById('uCpwdLabel').innerHTML = 'Confirm'   +(isEdit?'':' <span class="req">*</span>');
  document.getElementById('uPwd').value  = '';
  document.getElementById('uCpwd').value = '';
  document.getElementById('uPwd').placeholder  = isEdit ? 'Leave blank to keep current' : 'Min 8 characters';
  document.getElementById('uCpwd').placeholder = isEdit ? 'Leave blank to keep current' : 'Repeat password';
  document.getElementById('userModal').style.display='flex';
}
function editUser(id) {
  fetch(BASE_URL+'/api/users?action=get&id='+id,{credentials:'include'})
    .then(function(r){return r.json();}).then(function(d){ if(d.success) openUserModal(d.data); });
}
function closeUserModal() { document.getElementById('userModal').style.display='none'; }

function saveUser() {
  var id = document.getElementById('userId').value;
  var body = {
    firstname: document.getElementById('uFirst').value.trim(),
    lastname:  document.getElementById('uLast').value.trim(),
    email:     document.getElementById('uEmail').value.trim(),
    phone:     document.getElementById('uPhone').value.trim(),
    role_id:   document.getElementById('uRoleSel').value,
    account_status: document.getElementById('uStatusSel').value,
    password:        document.getElementById('uPwd').value,
    confirm_password:document.getElementById('uCpwd').value,
  };
  if(!body.firstname||!body.lastname||!body.email) { grError('','First name, last name and email are required'); return; }
  if(!id && !body.password) { grError('','Password is required for new users'); return; }
  if(body.password && body.password!==body.confirm_password) { grError('','Passwords do not match'); return; }
  var url = id ? BASE_URL+'/api/users?action=update&id='+id : BASE_URL+'/api/users?action=create';
  grLoading(id?'Updating…':'Creating…');
  fetch(url,{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
    .then(function(r){return r.json();}).then(function(d){
      Swal.close();
      if(d.success) grSuccess('Saved',d.message,function(){ closeUserModal(); loadUsers(_uPage); });
      else grError('',d.message);
    });
}

/* ── VIEW ────────────────────────────────── */
function viewUser(id) {
  document.getElementById('viewModalBody').innerHTML='<div style="text-align:center;padding:24px"><span class="spinner"></span></div>';
  document.getElementById('viewModal').style.display='flex';
  fetch(BASE_URL+'/api/users?action=get&id='+id,{credentials:'include'})
    .then(function(r){return r.json();}).then(function(d){
      if(!d.success) { document.getElementById('viewModalBody').innerHTML='<p style="color:var(--red)">Failed to load user</p>'; return; }
      var u = d.data;
      var initials = (u.firstname||'?').charAt(0).toUpperCase()+(u.lastname||'?').charAt(0).toUpperCase();
      document.getElementById('viewModalBody').innerHTML =
        '<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">'
        +(u.photo?'<img src="'+BASE_URL+'/uploads/'+u.photo+'" style="width:56px;height:56px;border-radius:50%;object-fit:cover">'
          :'<div style="width:56px;height:56px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0">'+initials+'</div>')
        +'<div><div style="font-weight:700;font-size:17px">'+escH(u.firstname+' '+u.lastname)+'</div>'
        +'<div style="font-size:13px;color:var(--text-muted)">'+escH(u.email)+'</div>'
        +'<span class="gr-badge gr-badge-'+u.account_status+'" style="margin-top:4px">'+u.account_status+'</span></div></div>'
        +'<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">'
        +infoRow('Role',u.role_display||u.role_name||'—')+infoRow('Phone',u.phone||'—')
        +infoRow('Last Login',u.last_login?u.last_login.slice(0,10):'Never')+infoRow('Joined',u.created_at.slice(0,10))
        +'</div>';
      var footer = '';
      if(_canDeact && u.id!==_myId && !u.is_super_admin) {
        var newSt = u.account_status==='active' ? 'suspended' : 'active';
        var btnCls = u.account_status==='active' ? 'gr-btn-danger' : 'gr-btn-success';
        footer += '<button class="gr-btn '+btnCls+'" onclick="setStatus('+u.id+',\''+newSt+'\')">'+(u.account_status==='active'?'Suspend':'Activate')+'</button>';
      }
      if(_canEdit) footer += '<button class="gr-btn gr-btn-primary" onclick="closeViewModal();editUser('+u.id+')"><i class="ri-edit-line"></i> Edit</button>';
      document.getElementById('viewModalFooter').innerHTML = '<button class="gr-btn gr-btn-outline" onclick="closeViewModal()">Close</button>'+footer;
    });
}
function infoRow(l,v){ return '<div style="background:var(--bg);border-radius:8px;padding:10px 12px"><div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">'+l+'</div><div style="font-weight:600;font-size:13px">'+escH(v)+'</div></div>'; }
function closeViewModal() { document.getElementById('viewModal').style.display='none'; }

/* ── STATUS ──────────────────────────────── */
function setStatus(id,status) {
  grConfirm(status==='suspended'?'Suspend User?':'Activate User?',
    status==='suspended'?'The user will not be able to log in.':'The user will be able to log in again.',
    function(){
      fetch(BASE_URL+'/api/users?action=status&id='+id,{method:'POST',credentials:'include',
        headers:{'Content-Type':'application/json'},body:JSON.stringify({status:status})})
        .then(function(r){return r.json();}).then(function(d){
          if(d.success) grSuccess('Updated',d.message,function(){ closeViewModal(); loadUsers(_uPage); });
          else grError('',d.message);
        });
    });
}

/* ── DELETE ──────────────────────────────── */
function deleteUser(id) {
  grConfirm('Delete User?','This action cannot be undone.',function(){
    fetch(BASE_URL+'/api/users?id='+id,{method:'DELETE',credentials:'include'})
      .then(function(r){return r.json();}).then(function(d){
        if(d.success) loadUsers(_uPage);
        else grError('',d.message);
      });
  },'warning');
}

var _dt;
document.getElementById('uSearch').addEventListener('input',function(){ clearTimeout(_dt); _dt=setTimeout(function(){ loadUsers(1); },400); });
['uRole','uStatus'].forEach(function(id){ document.getElementById(id).addEventListener('change',function(){ loadUsers(1); }); });
loadUsers(1);
</script>
JS;
require_once get_layout('admin-scripts');