<?php
/** GuardReport — Sites | File: modules/Sites/views/sites.php */
$pageTitle        = 'Sites';
$currentPage      = 'sites';
$requiredPermission = 'sites.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/models/SiteModel.php';

$sm    = new SiteModel(Database::getConnection());
$sites = $sm->getAll();

$canCreate = $isSuperAdmin || hasPermission($userPermissions,'sites.create');
$canEdit   = $isSuperAdmin || hasPermission($userPermissions,'sites.update');
$canDelete = $isSuperAdmin || hasPermission($userPermissions,'sites.delete');

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>Sites</h1>
    <div class="gr-breadcrumb">Client premises and security locations</div>
  </div>
  <div class="gr-page-actions">
    <?php if ($canCreate): ?>
      <button class="gr-btn gr-btn-primary" onclick="openSiteModal()">
        <i class="ri-add-circle-line"></i> Add Site
      </button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px" id="sitesGrid">
<?php if (empty($sites)): ?>
  <div class="gr-empty" style="grid-column:1/-1"><i class="ri-building-2-line"></i><p>No sites added yet</p></div>
<?php else: ?>
  <?php foreach ($sites as $s):
    $statusColor = match($s['status']) { 'active'=>'var(--green)', 'inactive'=>'var(--text-muted)', default=>'var(--amber)' };
  ?>
    <div class="gr-card" style="border-top:3px solid <?= $statusColor ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
        <div>
          <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($s['name']) ?></div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:3px">
            <i class="ri-map-pin-line"></i> <?= htmlspecialchars($s['address']) ?>
          </div>
        </div>
        <span class="gr-badge gr-badge-<?= $s['status'] ?>"><?= $s['status'] ?></span>
      </div>
      <?php if (!empty($s['client_name'])): ?>
        <div style="font-size:13px;color:var(--text-mid);margin-bottom:8px">
          <i class="ri-user-star-line"></i> <?= htmlspecialchars($s['client_name']) ?>
          <?php if (!empty($s['client_phone'])): ?>
            · <?= htmlspecialchars($s['client_phone']) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-muted)">
          <i class="ri-alert-line"></i> <?= $s['incident_count'] ?> incident<?= $s['incident_count'] != 1 ? 's' : '' ?>
        </div>
        <div style="display:flex;gap:6px">
          <a href="<?= url('admin/incidents') ?>?site_id=<?= $s['id'] ?>" class="gr-btn gr-btn-outline gr-btn-sm">
            <i class="ri-eye-line"></i>
          </a>
          <?php if ($canEdit): ?>
            <button class="gr-btn gr-btn-outline gr-btn-sm" onclick='editSite(<?= json_encode($s) ?>)'>
              <i class="ri-edit-line"></i>
            </button>
          <?php endif; ?>
          <?php if ($canDelete && $s['incident_count'] == 0): ?>
            <button class="gr-btn gr-btn-outline gr-btn-sm" style="color:var(--red)" onclick="deleteSite(<?= $s['id'] ?>,'<?= addslashes($s['name']) ?>')">
              <i class="ri-delete-bin-line"></i>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Modal -->
<div id="siteModal" style="display:none" class="gr-modal-backdrop">
  <div class="gr-modal" style="max-width:560px">
    <div class="gr-modal-header">
      <div class="gr-modal-title" id="siteModalTitle">Add New Site</div>
      <button onclick="closeSiteModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-muted)"><i class="ri-close-line"></i></button>
    </div>
    <div class="gr-modal-body">
      <input type="hidden" id="siteId">
      <div class="gr-form-row">
        <div class="gr-form-group"><label>Site Name <span class="req">*</span></label><input id="siteName" type="text" class="gr-input"></div>
        <div class="gr-form-group"><label>City</label><input id="siteCity" type="text" class="gr-input"></div>
      </div>
      <div class="gr-form-group"><label>Address <span class="req">*</span></label><input id="siteAddr" type="text" class="gr-input"></div>
      <div class="gr-form-row">
        <div class="gr-form-group"><label>Client Name</label><input id="siteClient" type="text" class="gr-input"></div>
        <div class="gr-form-group"><label>Client Phone</label><input id="sitePhone" type="text" class="gr-input"></div>
      </div>
      <div class="gr-form-group"><label>Client Email</label><input id="siteEmail" type="email" class="gr-input"></div>
      <div class="gr-form-group"><label>Status</label>
        <select id="siteStatus" class="gr-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="under_review">Under Review</option>
        </select>
      </div>
    </div>
    <div class="gr-modal-footer">
      <button class="gr-btn gr-btn-outline" onclick="closeSiteModal()">Cancel</button>
      <button class="gr-btn gr-btn-primary" onclick="saveSite()"><i class="ri-save-line"></i> Save Site</button>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
function openSiteModal() {
  document.getElementById('siteId').value='';
  document.getElementById('siteModalTitle').textContent='Add New Site';
  ['siteName','siteCity','siteAddr','siteClient','sitePhone','siteEmail'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('siteStatus').value='active';
  document.getElementById('siteModal').style.display='flex';
}
function editSite(s) {
  document.getElementById('siteId').value=s.id;
  document.getElementById('siteModalTitle').textContent='Edit Site';
  document.getElementById('siteName').value=s.name||'';
  document.getElementById('siteCity').value=s.city||'';
  document.getElementById('siteAddr').value=s.address||'';
  document.getElementById('siteClient').value=s.client_name||'';
  document.getElementById('sitePhone').value=s.client_phone||'';
  document.getElementById('siteEmail').value=s.client_email||'';
  document.getElementById('siteStatus').value=s.status||'active';
  document.getElementById('siteModal').style.display='flex';
}
function closeSiteModal() { document.getElementById('siteModal').style.display='none'; }
function saveSite() {
  var id = document.getElementById('siteId').value;
  var body = {
    name:document.getElementById('siteName').value.trim(),
    address:document.getElementById('siteAddr').value.trim(),
    city:document.getElementById('siteCity').value.trim(),
    client_name:document.getElementById('siteClient').value.trim(),
    client_phone:document.getElementById('sitePhone').value.trim(),
    client_email:document.getElementById('siteEmail').value.trim(),
    status:document.getElementById('siteStatus').value,
  };
  if(!body.name||!body.address) { grError('','Site name and address are required'); return; }
  var url = id ? BASE_URL+'/api/sites?action=update&id='+id : BASE_URL+'/api/sites?action=create';
  grLoading(id?'Updating…':'Creating…');
  fetch(url,{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
    .then(function(r){ return r.json(); }).then(function(d){
      Swal.close();
      if(d.success) grSuccess('Saved',d.message,function(){ location.reload(); });
      else grError('',d.message);
    });
}
function deleteSite(id,name) {
  grConfirm('Delete '+name+'?','This site will be permanently removed.',function(){
    fetch(BASE_URL+'/api/sites?id='+id,{method:'DELETE',credentials:'include'})
      .then(function(r){ return r.json(); }).then(function(d){
        if(d.success) grSuccess('Deleted',d.message,function(){ location.reload(); });
        else grError('',d.message);
      });
  });
}
</script>
JS;
require_once get_layout('admin-scripts');