<?php
/** GuardReport — Create Incident | File: modules/Incidents/views/incident-create.php */
$pageTitle        = 'Report Incident';
$currentPage      = 'incidents';
$requiredPermission = 'incidents.create';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/models/IncidentModel.php';
require_once dirname(__DIR__, 3) . '/modules/Sites/models/SiteModel.php';

$im    = new IncidentModel(Database::getConnection());
$sm    = new SiteModel(Database::getConnection());
$types = $im->getTypes();
$sites = $sm->getAll(['status' => 'active']);

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>Report Incident</h1>
    <div class="gr-breadcrumb">
      <a href="<?= url('admin/incidents') ?>">Incidents</a> › New Report
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

  <!-- Main Form -->
  <div class="gr-card">
    <div id="formAlert" style="display:none"></div>

    <div class="gr-form-group">
      <label for="title">Incident Title <span class="req">*</span></label>
      <input id="title" type="text" class="gr-input" placeholder="Brief descriptive title">
    </div>

    <div class="gr-form-row">
      <div class="gr-form-group">
        <label for="type_id">Incident Type <span class="req">*</span></label>
        <select id="type_id" class="gr-select">
          <option value="">Select type…</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= $t['id'] ?>" data-sev="<?= $t['default_severity'] ?>">
              <?= htmlspecialchars($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="gr-form-group">
        <label for="site_id">Site / Location <span class="req">*</span></label>
        <select id="site_id" class="gr-select">
          <option value="">Select site…</option>
          <?php foreach ($sites as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="gr-form-row">
      <div class="gr-form-group">
        <label for="severity">Severity <span class="req">*</span></label>
        <select id="severity" class="gr-select">
          <option value="low">Low — Minor, no immediate threat</option>
          <option value="medium" selected>Medium — Moderate, monitor closely</option>
          <option value="high">High — Serious, prompt action needed</option>
          <option value="critical">Critical — Immediate response required</option>
        </select>
      </div>
      <div class="gr-form-group">
        <label for="incident_date">Date &amp; Time of Incident <span class="req">*</span></label>
        <input id="incident_date" type="datetime-local" class="gr-input">
      </div>
    </div>

    <div class="gr-form-group">
      <label for="description">Description <span class="req">*</span></label>
      <textarea id="description" class="gr-textarea" rows="5"
        placeholder="Describe what happened in as much detail as possible — who was involved, what occurred, and any immediate actions taken."></textarea>
    </div>

    <div class="gr-form-group">
      <label for="location_note">Specific Location Note</label>
      <input id="location_note" type="text" class="gr-input" placeholder="e.g. Gate B, north corner, 2nd floor corridor">
      <div class="gr-form-hint">Optional — describe the exact spot within the site.</div>
    </div>

    <div class="gr-form-row">
      <div class="gr-form-group">
        <label for="latitude">Latitude (GPS)</label>
        <input id="latitude" type="number" step="any" class="gr-input" placeholder="e.g. -1.9441">
      </div>
      <div class="gr-form-group">
        <label for="longitude">Longitude (GPS)</label>
        <input id="longitude" type="number" step="any" class="gr-input" placeholder="e.g. 30.0619">
        <div class="gr-form-hint" style="margin-top:4px">
          <button type="button" onclick="getGPS()" style="background:none;border:none;color:var(--blue-light);cursor:pointer;font-size:12px;font-family:inherit;padding:0">
            <i class="ri-map-pin-2-line"></i> Use my current location
          </button>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <a href="<?= url('admin/incidents') ?>" class="gr-btn gr-btn-outline">
        <i class="ri-arrow-left-line"></i> Cancel
      </a>
      <button id="submitBtn" class="gr-btn gr-btn-danger" onclick="submitIncident()">
        <i class="ri-send-plane-line"></i> Submit Report
      </button>
    </div>
  </div>

  <!-- Evidence Upload Sidebar -->
  <div>
    <div class="gr-card">
      <div class="gr-card-title" style="margin-bottom:14px">
        <i class="ri-image-line" style="color:var(--sky)"></i> Evidence Files
      </div>
      <div class="gr-upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()"
        ondragover="event.preventDefault();this.classList.add('drag-over')"
        ondragleave="this.classList.remove('drag-over')"
        ondrop="handleDrop(event)">
        <i class="ri-upload-cloud-2-line"></i>
        <p>Drag &amp; drop files here or <strong>click to browse</strong></p>
        <p style="font-size:11px;margin-top:6px">Photos, PDFs — max 10 MB each</p>
      </div>
      <input type="file" id="fileInput" multiple accept="image/*,.pdf,.doc,.docx"
        style="display:none" onchange="addFiles(this.files)">
      <div id="fileList" style="margin-top:12px"></div>
      <p style="font-size:11px;color:var(--text-muted);margin-top:8px">
        Files will be uploaded after the report is submitted.
      </p>
    </div>

    <div class="gr-card" style="margin-top:16px">
      <div class="gr-card-title" style="margin-bottom:12px;font-size:13px">Severity Guide</div>
      <?php foreach ([
        ['critical','#dc2626','Immediate threat to life or property'],
        ['high',    '#d97706','Serious breach — act within the hour'],
        ['medium',  '#1d4ed8','Moderate — schedule investigation'],
        ['low',     '#16a34a','Minor — log for records'],
      ] as [$sev,$col,$desc]): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <span class="gr-badge gr-badge-<?= $sev ?>"><?= $sev ?></span>
          <span style="font-size:12px;color:var(--text-muted)"><?= $desc ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
var _pendingFiles = [];

// Set default datetime to now
document.getElementById('incident_date').value = new Date(Date.now() - new Date().getTimezoneOffset()*60000).toISOString().slice(0,16);

// Auto-set severity based on type
document.getElementById('type_id').addEventListener('change', function() {
  var opt = this.options[this.selectedIndex];
  var sev = opt.getAttribute('data-sev');
  if (sev) document.getElementById('severity').value = sev;
});

function getGPS() {
  if (!navigator.geolocation) { grError('GPS','Geolocation is not supported by your browser'); return; }
  navigator.geolocation.getCurrentPosition(function(p) {
    document.getElementById('latitude').value  = p.coords.latitude.toFixed(7);
    document.getElementById('longitude').value = p.coords.longitude.toFixed(7);
  }, function(e) { grError('GPS Error', e.message); });
}

function addFiles(files) {
  for (var i=0; i<files.length; i++) {
    _pendingFiles.push(files[i]);
  }
  renderFileList();
}
function handleDrop(e) {
  e.preventDefault();
  document.getElementById('uploadZone').classList.remove('drag-over');
  addFiles(e.dataTransfer.files);
}
function removeFile(idx) {
  _pendingFiles.splice(idx, 1);
  renderFileList();
}
function renderFileList() {
  var list = document.getElementById('fileList');
  if (!_pendingFiles.length) { list.innerHTML=''; return; }
  list.innerHTML = _pendingFiles.map(function(f, i) {
    var isImg = f.type.startsWith('image/');
    return '<div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)">'
      +'<i class="'+(isImg?'ri-image-line':'ri-file-line')+'" style="color:var(--text-muted)"></i>'
      +'<span style="flex:1;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+f.name+'</span>'
      +'<span style="font-size:11px;color:var(--text-muted)">'+(f.size/1024).toFixed(0)+'KB</span>'
      +'<button onclick="removeFile('+i+')" style="background:none;border:none;cursor:pointer;color:var(--red);font-size:14px"><i class="ri-close-line"></i></button>'
      +'</div>';
  }).join('');
}

function showFormAlert(msg, type='danger') {
  var el = document.getElementById('formAlert');
  el.className = 'gr-alert gr-alert-'+type;
  el.innerHTML = '<i class="ri-'+(type==='danger'?'error-warning':'check')+'-line"></i> '+msg;
  el.style.display = 'flex';
  el.scrollIntoView({behavior:'smooth', block:'nearest'});
}

async function submitIncident() {
  var btn  = document.getElementById('submitBtn');
  var title = document.getElementById('title').value.trim();
  var desc  = document.getElementById('description').value.trim();
  var typeId  = document.getElementById('type_id').value;
  var siteId  = document.getElementById('site_id').value;
  var idate   = document.getElementById('incident_date').value;
  var severity= document.getElementById('severity').value;

  if (!title)  { showFormAlert('Title is required'); return; }
  if (!typeId) { showFormAlert('Select an incident type'); return; }
  if (!siteId) { showFormAlert('Select a site'); return; }
  if (!idate)  { showFormAlert('Incident date/time is required'); return; }
  if (!desc)   { showFormAlert('Description is required'); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Submitting…';

  try {
    // 1. Create incident
    var res = await fetch(BASE_URL+'/api/incidents?action=create', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        title, description:desc, type_id:typeId, site_id:siteId,
        severity, incident_date:idate,
        location_note: document.getElementById('location_note').value.trim(),
        latitude:  document.getElementById('latitude').value  || null,
        longitude: document.getElementById('longitude').value || null,
      })
    });
    var data = await res.json();
    if (!data.success) { showFormAlert(data.message||'Submission failed'); btn.disabled=false; btn.innerHTML='<i class="ri-send-plane-line"></i> Submit Report'; return; }

    var incId = data.id;

    // 2. Upload evidence if any
    if (_pendingFiles.length) {
      btn.innerHTML = '<span class="spinner"></span> Uploading evidence…';
      var fd = new FormData();
      _pendingFiles.forEach(function(f){ fd.append('files[]', f); });
      await fetch(BASE_URL+'/api/incidents/evidence?incident_id='+incId, {
        method:'POST', credentials:'include', body:fd
      });
    }

    grSuccess('Incident Reported', 'Your incident report has been submitted successfully.', function() {
      window.location.href = BASE_URL+'/admin/incidents/view?id='+incId;
    });
  } catch(e) {
    showFormAlert('Network error: '+e.message);
    btn.disabled=false; btn.innerHTML='<i class="ri-send-plane-line"></i> Submit Report';
  }
}
</script>
JS;
require_once get_layout('admin-scripts');