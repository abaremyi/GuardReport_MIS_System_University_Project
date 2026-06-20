<?php
/**
 * GuardReport — Report Builder
 * File: modules/Reports/views/report-builder.php
 */
$pageTitle          = 'Report Builder';
$currentPage        = 'reports';
$requiredPermission = 'reports.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 3) . '/modules/Sites/models/SiteModel.php';
require_once dirname(__DIR__, 3) . '/modules/Authentication/models/UserModel.php';
require_once dirname(__DIR__, 1) . '/controllers/ReportBuilderController.php';

$rbCtrl  = new ReportBuilderController();
$catalog = $rbCtrl->catalog();

$sm     = new SiteModel(Database::getConnection());
$um     = new UserModel(Database::getConnection());
$sites  = $sm->getAll(['status' => 'active']);
$guards = $um->getGuards();
$roles  = Database::getConnection()->query("SELECT id, name FROM roles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$canExport = $isSuperAdmin || hasPermission($userPermissions, 'reports.export');

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<style>
/* Square corners throughout — no border-radius. */
.rb-types{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
@media(max-width:860px){.rb-types{grid-template-columns:repeat(2,1fr);}}
.rb-type-tile{background:#fff;border:1px solid var(--border);padding:20px 16px;text-align:center;cursor:pointer;transition:border-color .12s,background .12s;}
.rb-type-tile:hover{background:var(--bg);}
.rb-type-tile.active{border-color:var(--blue);border-width:2px;background:var(--bg);}
.rb-type-icon{width:46px;height:46px;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:21px;margin:0 auto 10px;}
.rb-type-tile.active .rb-type-icon{background:var(--blue);}
.rb-type-name{font-size:13.5px;font-weight:700;color:var(--text-mid);}

.rb-config{display:none;}
.rb-config.show{display:block;}
.rb-fields-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:6px;margin-top:6px;}
.rb-field-chk{display:flex;align-items:center;gap:7px;font-size:12.5px;padding:6px 8px;border:1px solid var(--border);cursor:pointer;}
.rb-field-chk input{accent-color:var(--blue);}
.rb-field-chk:hover{background:var(--bg);}
.rb-filters-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:14px;}
.rb-filters-row > div{min-width:160px;}

.rb-preview-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;}
.rb-result-count{font-size:12.5px;color:var(--text-muted);}
.rb-empty-state{padding:40px 0;text-align:center;color:var(--text-muted);}
</style>

<div class="gr-page-header">
  <div>
    <h1>Report Builder</h1>
    <div class="gr-breadcrumb">Build a custom report — pick the data, the fields, and the date range</div>
  </div>
</div>

<!-- STEP 1: TYPE -->
<div class="gr-card" style="margin-bottom:18px">
  <div class="gr-card-title" style="margin-bottom:14px"><i class="ri-stack-line" style="color:var(--blue)"></i> 1. Choose a Report Type</div>
  <div class="rb-types" id="typeGrid">
    <div class="rb-type-tile" data-type="incidents" onclick="selectType('incidents')">
      <div class="rb-type-icon"><i class="ri-alert-line"></i></div>
      <div class="rb-type-name">Incidents</div>
    </div>
    <div class="rb-type-tile" data-type="shifts" onclick="selectType('shifts')">
      <div class="rb-type-icon"><i class="ri-calendar-schedule-line"></i></div>
      <div class="rb-type-name">Shifts</div>
    </div>
    <div class="rb-type-tile" data-type="guards" onclick="selectType('guards')">
      <div class="rb-type-icon"><i class="ri-shield-user-line"></i></div>
      <div class="rb-type-name">Guards / Personnel</div>
    </div>
    <div class="rb-type-tile" data-type="sites" onclick="selectType('sites')">
      <div class="rb-type-icon"><i class="ri-building-2-line"></i></div>
      <div class="rb-type-name">Sites</div>
    </div>
  </div>
</div>

<!-- STEP 2: CONFIGURE -->
<div class="gr-card rb-config" id="configCard" style="margin-bottom:18px">
  <div class="gr-card-title" style="margin-bottom:6px"><i class="ri-equalizer-line" style="color:var(--blue)"></i> 2. Choose Fields &amp; Range</div>
  <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:4px" id="dateFieldLabel">Date range</div>

  <div class="rb-filters-row">
    <div>
      <label style="font-size:11.5px;font-weight:600;color:var(--text-muted)">From</label>
      <input type="date" id="rbDateFrom" class="gr-input">
    </div>
    <div>
      <label style="font-size:11.5px;font-weight:600;color:var(--text-muted)">To</label>
      <input type="date" id="rbDateTo" class="gr-input">
    </div>
    <div id="rbExtraFilters" style="display:flex;gap:12px;flex-wrap:wrap;"></div>
  </div>

  <div style="margin-top:18px">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <label style="font-size:11.5px;font-weight:600;color:var(--text-muted)">Fields to include</label>
      <div style="display:flex;gap:10px">
        <button type="button" class="gr-btn gr-btn-outline gr-btn-sm" onclick="selectAllFields(true)">Select All</button>
        <button type="button" class="gr-btn gr-btn-outline gr-btn-sm" onclick="selectAllFields(false)">Clear</button>
      </div>
    </div>
    <div class="rb-fields-grid" id="fieldsGrid"></div>
  </div>

  <div style="display:flex;justify-content:flex-end;margin-top:18px">
    <button class="gr-btn gr-btn-primary" onclick="generatePreview()"><i class="ri-play-circle-line"></i> Generate Preview</button>
  </div>
</div>

<!-- STEP 3: PREVIEW -->
<div class="gr-card rb-config" id="previewCard">
  <div class="rb-preview-bar">
    <div>
      <div class="gr-card-title" style="margin-bottom:2px"><i class="ri-table-line" style="color:var(--blue)"></i> 3. Preview &amp; Export</div>
      <div class="rb-result-count" id="resultCount"></div>
    </div>
    <?php if ($canExport): ?>
      <button class="gr-btn gr-btn-primary" id="btnExport" onclick="exportReport()"><i class="ri-file-pdf-2-line"></i> Generate PDF Report</button>
    <?php endif; ?>
  </div>
  <div class="gr-table-wrap">
    <table class="gr-table" id="previewTable">
      <thead><tr id="previewHead"></tr></thead>
      <tbody id="previewBody"></tbody>
    </table>
  </div>
</div>

<?php
$catalogJs = json_encode($catalog);
$sitesJs   = json_encode(array_map(fn($s)=>['id'=>$s['id'],'name'=>$s['name']], $sites));
$guardsJs  = json_encode(array_map(fn($g)=>['id'=>$g['id'],'name'=>$g['firstname'].' '.$g['lastname']], $guards));
$rolesJs   = json_encode($roles);
$canExportJs = (int)$canExport;

$pageScripts = <<<JS
<script>
var CATALOG    = {$catalogJs};
var SITES      = {$sitesJs};
var GUARDS     = {$guardsJs};
var ROLES      = {$rolesJs};
var CAN_EXPORT = {$canExportJs};
var currentType = null;
var lastRows    = [];
var lastFields  = [];

var FILTER_DEFS = {
  incidents: [
    {key:'site_id', label:'Site',     opts:SITES.map(function(s){return {v:s.id,l:s.name};})},
    {key:'severity',label:'Severity', opts:[{v:'low',l:'Low'},{v:'medium',l:'Medium'},{v:'high',l:'High'},{v:'critical',l:'Critical'}]},
    {key:'status',  label:'Status',   opts:[{v:'open',l:'Open'},{v:'reviewing',l:'Reviewing'},{v:'resolved',l:'Resolved'},{v:'closed',l:'Closed'}]},
  ],
  shifts: [
    {key:'site_id', label:'Site',   opts:SITES.map(function(s){return {v:s.id,l:s.name};})},
    {key:'guard_id',label:'Guard',  opts:GUARDS.map(function(g){return {v:g.id,l:g.name};})},
    {key:'status',  label:'Status', opts:[{v:'scheduled',l:'Scheduled'},{v:'active',l:'Active'},{v:'completed',l:'Completed'},{v:'missed',l:'Missed'},{v:'cancelled',l:'Cancelled'}]},
  ],
  guards: [
    {key:'role_id', label:'Role', opts:ROLES.map(function(r){return {v:r.id,l:r.name};})},
  ],
  sites: [
    {key:'status', label:'Status', opts:[{v:'active',l:'Active'},{v:'inactive',l:'Inactive'},{v:'under_review',l:'Under Review'}]},
  ],
};

function selectType(type) {
  currentType = type;
  document.querySelectorAll('.rb-type-tile').forEach(function(t){ t.classList.toggle('active', t.dataset.type === type); });
  document.getElementById('configCard').classList.add('show');
  document.getElementById('previewCard').classList.remove('show');

  var def = CATALOG[type];
  document.getElementById('dateFieldLabel').textContent = def.date_field_label || 'Date range';
  document.getElementById('rbDateTo').value   = new Date().toISOString().slice(0,10);
  var from = new Date(); from.setDate(from.getDate()-30);
  document.getElementById('rbDateFrom').value = from.toISOString().slice(0,10);

  // Fields
  var grid = document.getElementById('fieldsGrid');
  grid.innerHTML = Object.keys(def.fields).map(function(key) {
    var checked = def.defaults.includes(key) ? 'checked' : '';
    return '<label class="rb-field-chk"><input type="checkbox" value="'+key+'" '+checked+'> '+def.fields[key]+'</label>';
  }).join('');

  // Filters
  var fc = document.getElementById('rbExtraFilters');
  fc.innerHTML = (FILTER_DEFS[type]||[]).map(function(f) {
    return '<div><label style="font-size:11.5px;font-weight:600;color:var(--text-muted)">'+f.label+'</label>'
      +'<select class="gr-select" data-filter-key="'+f.key+'"><option value="">All</option>'
      +f.opts.map(function(o){ return '<option value="'+o.v+'">'+o.l+'</option>'; }).join('')
      +'</select></div>';
  }).join('');

  document.getElementById('configCard').scrollIntoView({behavior:'smooth', block:'start'});
}

function selectAllFields(state) {
  document.querySelectorAll('#fieldsGrid input[type=checkbox]').forEach(function(c){ c.checked = state; });
}

function collectFilters() {
  var filters = { date_from: document.getElementById('rbDateFrom').value, date_to: document.getElementById('rbDateTo').value };
  document.querySelectorAll('#rbExtraFilters [data-filter-key]').forEach(function(sel) {
    if (sel.value) filters[sel.dataset.filterKey] = sel.value;
  });
  return filters;
}
function collectFields() {
  return Array.prototype.map.call(document.querySelectorAll('#fieldsGrid input:checked'), function(c){ return c.value; });
}

function fmtCell(field, val) {
  if (val === null || val === undefined || val === '') return '—';
  if (field === 'severity' || field === 'status') {
    var cls = 'gr-badge gr-badge-' + (['open','active','scheduled'].includes(val) ? val : (val==='critical'||val==='high'?val:'closed'));
    return '<span class="'+cls+'">'+val+'</span>';
  }
  if (['start_time','end_time','incident_date','created_at','last_login'].includes(field)) {
    var d = new Date(val); return isNaN(d) ? val : d.toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
  }
  return String(val);
}
function escRb(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

async function generatePreview() {
  var fields = collectFields();
  if (!fields.length) return grError('Pick fields','Select at least one field to include.');
  lastFields = fields;
  var filters = collectFilters();
  if (!filters.date_from || !filters.date_to) return grError('Date range required','Please set both dates.');

  grLoading('Building report…');
  try {
    var qs = new URLSearchParams(Object.assign({type:currentType}, filters));
    var res  = await fetch(BASE_URL+'/api/reports?action=data&'+qs, {credentials:'include'});
    var data = await res.json();
    Swal.close();
    if (!data.success) return grError('Error', data.message);

    lastRows = data.data.rows;
    var def  = CATALOG[currentType];
    document.getElementById('resultCount').textContent = data.data.count + ' record(s) found';
    document.getElementById('previewHead').innerHTML = fields.map(function(f){ return '<th>'+def.fields[f]+'</th>'; }).join('');
    document.getElementById('previewBody').innerHTML = lastRows.length
      ? lastRows.map(function(r){ return '<tr>'+fields.map(function(f){ return '<td>'+fmtCell(f, r[f])+'</td>'; }).join('')+'</tr>'; }).join('')
      : '<tr><td colspan="'+fields.length+'" class="rb-empty-state">No records found for this range / filters</td></tr>';

    document.getElementById('previewCard').classList.add('show');
    document.getElementById('previewCard').scrollIntoView({behavior:'smooth', block:'start'});
  } catch(e) { Swal.close(); grError('Error','Network error while building the report.'); }
}

async function exportReport() {
  if (!CAN_EXPORT) return;
  var btn = document.getElementById('btnExport');
  btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Generating…';
  try {
    var res  = await fetch(BASE_URL+'/api/reports?action=export', {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ type: currentType, fields: lastFields, filters: collectFilters() })
    });
    var data = await res.json();
    if (!data.success) { grError('Error', data.message); return; }

    if (data.data.pdf_base64) {
      // Server rendered a true PDF (Dompdf installed) — download it directly.
      var bin = atob(data.data.pdf_base64);
      var bytes = new Uint8Array(bin.length);
      for (var i=0;i<bin.length;i++) bytes[i] = bin.charCodeAt(i);
      var blob = new Blob([bytes], {type:'application/pdf'});
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = currentType + '-report-' + new Date().toISOString().slice(0,10) + '.pdf';
      document.body.appendChild(a); a.click(); a.remove();
    } else {
      // No PDF library installed server-side — open the styled report and trigger
      // the browser's native Print dialog (user picks "Save as PDF").
      var w = window.open('', '_blank');
      w.document.open(); w.document.write(data.data.html); w.document.close();
      w.onload = function(){ w.focus(); w.print(); };
      setTimeout(function(){ try { w.focus(); w.print(); } catch(e){} }, 400);
    }
  } catch(e) { grError('Error','Network error while generating the report.'); }
  finally { btn.disabled = false; btn.innerHTML = '<i class="ri-file-pdf-2-line"></i> Generate PDF Report'; }
}
</script>
JS;
require_once get_layout('admin-scripts');
