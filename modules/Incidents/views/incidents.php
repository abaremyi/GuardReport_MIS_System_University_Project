<?php
/** GuardReport — Incidents List | File: modules/Incidents/views/incidents.php */
$pageTitle        = 'Incidents';
$currentPage      = 'incidents';
$requiredPermission = 'incidents.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/models/IncidentModel.php';
require_once dirname(__DIR__, 3) . '/modules/Sites/models/SiteModel.php';

$im    = new IncidentModel(Database::getConnection());
$sm    = new SiteModel(Database::getConnection());
$types = $im->getTypes();
$sites = $sm->getAll();

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>Incidents</h1>
    <div class="gr-breadcrumb">All reported security incidents</div>
  </div>
  <div class="gr-page-actions">
    <?php if ($isSuperAdmin || hasPermission($userPermissions,'incidents.create')): ?>
      <a href="<?= url('admin/incidents/create') ?>" class="gr-btn gr-btn-danger">
        <i class="ri-add-circle-line"></i> Report Incident
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Filters -->
<div class="gr-card mb-4">
  <div class="gr-filter-bar">
    <div class="gr-search-wrap">
      <i class="ri-search-line"></i>
      <input type="text" id="srchQ" class="gr-input" placeholder="Search incidents…">
    </div>
    <select id="srchSite" class="gr-select">
      <option value="">All Sites</option>
      <?php foreach ($sites as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="srchType" class="gr-select">
      <option value="">All Types</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="srchSev" class="gr-select">
      <option value="">Severity</option>
      <option value="critical">Critical</option>
      <option value="high">High</option>
      <option value="medium">Medium</option>
      <option value="low">Low</option>
    </select>
    <select id="srchStatus" class="gr-select">
      <option value="">Status</option>
      <option value="open">Open</option>
      <option value="reviewing">Reviewing</option>
      <option value="resolved">Resolved</option>
      <option value="closed">Closed</option>
    </select>
    <input type="date" id="srchFrom" class="gr-input" style="width:140px" title="From date">
    <input type="date" id="srchTo"   class="gr-input" style="width:140px" title="To date">
    <button class="gr-btn gr-btn-outline" onclick="loadIncidents(1)"><i class="ri-search-line"></i> Search</button>
    <button class="gr-btn gr-btn-outline" onclick="clearFilters()"><i class="ri-refresh-line"></i></button>
  </div>
</div>

<!-- List -->
<div id="incidentsList">
  <div class="gr-empty"><span class="spinner"></span></div>
</div>
<div id="pagination" class="gr-pagination"></div>

<?php
$canDelete = $isSuperAdmin || hasPermission($userPermissions, 'incidents.delete');
$pageScripts = <<<JS
<script>
var _page = 1;
var _canDelete = {$canDelete};

function loadIncidents(page) {
  _page = page || 1;
  var params = new URLSearchParams({
    action:'list', page:_page,
    search:   document.getElementById('srchQ').value.trim(),
    site_id:  document.getElementById('srchSite').value,
    type_id:  document.getElementById('srchType').value,
    severity: document.getElementById('srchSev').value,
    status:   document.getElementById('srchStatus').value,
    date_from:document.getElementById('srchFrom').value,
    date_to:  document.getElementById('srchTo').value,
  });
  document.getElementById('incidentsList').innerHTML = '<div class="gr-empty"><span class="spinner"></span></div>';
  fetch(BASE_URL+'/api/incidents?'+params, {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) { document.getElementById('incidentsList').innerHTML='<div class="gr-empty"><i class="ri-error-warning-line"></i><p>Failed to load incidents</p></div>'; return; }
      renderList(d.data);
    });
}

function renderList(data) {
  var list  = document.getElementById('incidentsList');
  var pager = document.getElementById('pagination');
  if (!data.data.length) {
    list.innerHTML = '<div class="gr-empty"><i class="ri-file-list-3-line"></i><p>No incidents found</p></div>';
    pager.innerHTML = '';
    return;
  }
  var sevIcon = {critical:'ri-alarm-warning-fill',high:'ri-alert-fill',medium:'ri-information-fill',low:'ri-checkbox-circle-fill'};
  var sevColor= {critical:'var(--red)',high:'var(--amber)',medium:'var(--blue-light)',low:'var(--green)'};
  list.innerHTML = data.data.map(function(i) {
    var evBadge = i.evidence_count>0 ? '<span class="gr-badge" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0"><i class="ri-image-line"></i> '+i.evidence_count+'</span>' : '';
    return '<a href="'+BASE_URL+'/admin/incidents/view?id='+i.id+'" class="gr-incident-card '+i.severity+'">'
      +'<div class="ic-icon" style="background:'+sevColor[i.severity]+'18;color:'+sevColor[i.severity]+'"><i class="'+sevIcon[i.severity]+'"></i></div>'
      +'<div class="ic-meta">'
      +'<div class="ic-title">#'+i.id+' — '+escHtml(i.title)+'</div>'
      +'<div class="ic-sub">'
      +'<span><i class="ri-building-2-line"></i>'+escHtml(i.site_name||'—')+'</span>'
      +'<span><i class="ri-price-tag-3-line"></i>'+escHtml(i.type_name||'—')+'</span>'
      +'<span><i class="ri-user-line"></i>'+escHtml(i.reporter_name||'—')+'</span>'
      +'<span><i class="ri-calendar-line"></i>'+i.incident_date.slice(0,10)+'</span>'
      +'</div>'
      +'</div>'
      +'<div class="ic-badges" style="flex-shrink:0">'
      +'<span class="gr-badge gr-badge-'+i.severity+'">'+i.severity+'</span>'
      +'<span class="gr-badge gr-badge-'+i.status+'">'+i.status+'</span>'
      +evBadge
      +'</div>'
      +'</a>';
  }).join('');

  // Pagination
  if (data.last_page > 1) {
    var btns = '';
    for (var p=1; p<=data.last_page; p++) {
      btns += '<button onclick="loadIncidents('+p+')" class="'+(p===data.page?'active':'')+'">'+p+'</button>';
    }
    pager.innerHTML = btns;
  } else { pager.innerHTML = ''; }
}

function clearFilters() {
  ['srchQ','srchSite','srchType','srchSev','srchStatus','srchFrom','srchTo'].forEach(function(id){
    document.getElementById(id).value='';
  });
  loadIncidents(1);
}

function escHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

// Debounced search on typing
var _dt;
document.getElementById('srchQ').addEventListener('input', function(){ clearTimeout(_dt); _dt=setTimeout(function(){ loadIncidents(1); },400); });
['srchSite','srchType','srchSev','srchStatus'].forEach(function(id){
  document.getElementById(id).addEventListener('change', function(){ loadIncidents(1); });
});

loadIncidents(1);
</script>
JS;
require_once get_layout('admin-scripts');