<?php
/** GuardReport — Incident View | File: modules/Incidents/views/incident-view.php */
$pageTitle        = 'Incident Detail';
$currentPage      = 'incidents';
$requiredPermission = 'incidents.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 3) . '/helpers/PermissionHelper.php';
require_once dirname(__DIR__, 1) . '/models/IncidentModel.php';

$id  = (int)($_GET['id'] ?? 0);
$im  = new IncidentModel(Database::getConnection());
$inc = $im->getById($id);
if (!$inc) { http_response_code(404); echo '<div class="gr-main"><div class="gr-alert gr-alert-danger"><i class="ri-error-warning-line"></i> Incident not found.</div></div>'; exit; }
$pageTitle = 'Incident #' . $id . ' — ' . htmlspecialchars($inc['title']);

$sevColors = ['critical'=>'#dc2626','high'=>'#d97706','medium'=>'#1d4ed8','low'=>'#16a34a'];
$sc = $sevColors[$inc['severity']] ?? '#64748b';

$canEdit   = $isSuperAdmin || hasPermission($userPermissions,'incidents.update');
$canDelete = $isSuperAdmin || hasPermission($userPermissions,'incidents.delete');
$canStatus = $isSuperAdmin || hasPermission($userPermissions,'incidents.update') || hasPermission($userPermissions,'incidents.close');

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1 style="font-size:18px">
      <span style="color:var(--text-muted);font-weight:400">#<?= $inc['id'] ?></span>
      <?= htmlspecialchars($inc['title']) ?>
    </h1>
    <div class="gr-breadcrumb">
      <a href="<?= url('admin/incidents') ?>">Incidents</a> › Detail
    </div>
  </div>
  <div class="gr-page-actions">
    <?php if ($canStatus): ?>
      <button class="gr-btn gr-btn-outline" onclick="openStatusModal()">
        <i class="ri-exchange-line"></i> Update Status
      </button>
    <?php endif; ?>
    <?php if ($canDelete): ?>
      <button class="gr-btn gr-btn-danger" onclick="deleteIncident()">
        <i class="ri-delete-bin-line"></i> Delete
      </button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

  <!-- Main Content -->
  <div>
    <!-- Meta card -->
    <div class="gr-card" style="margin-bottom:20px;border-top:4px solid <?= $sc ?>">
      <div style="display:flex;flex-wrap:wrap;gap:20px">
        <?php
        $meta = [
          ['i'=>'ri-price-tag-3-line','l'=>'Type',     'v'=>$inc['type_name'] ?? '—'],
          ['i'=>'ri-building-2-line', 'l'=>'Site',     'v'=>$inc['site_name'] ?? '—'],
          ['i'=>'ri-user-line',       'l'=>'Reported by','v'=>$inc['reporter_name'] ?? '—'],
          ['i'=>'ri-calendar-line',   'l'=>'Occurred', 'v'=>date('d M Y, H:i', strtotime($inc['incident_date']))],
          ['i'=>'ri-time-line',       'l'=>'Submitted','v'=>date('d M Y, H:i', strtotime($inc['created_at']))],
        ];
        foreach ($meta as $m): ?>
          <div style="min-width:160px">
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">
              <i class="<?= $m['i'] ?>"></i> <?= $m['l'] ?>
            </div>
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($m['v']) ?></div>
          </div>
        <?php endforeach; ?>
        <div style="min-width:140px">
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">Severity</div>
          <span class="gr-badge gr-badge-<?= $inc['severity'] ?>"><?= $inc['severity'] ?></span>
        </div>
        <div style="min-width:140px">
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">Status</div>
          <span class="gr-badge gr-badge-<?= $inc['status'] ?>"><?= $inc['status'] ?></span>
        </div>
      </div>
      <?php if (!empty($inc['location_note']) || !empty($inc['latitude'])): ?>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);font-size:13px;color:var(--text-mid)">
          <i class="ri-map-pin-line" style="color:var(--red)"></i>
          <?php if (!empty($inc['location_note'])): ?>
            <?= htmlspecialchars($inc['location_note']) ?>
          <?php endif; ?>
          <?php if (!empty($inc['latitude'])): ?>
            — GPS: <?= $inc['latitude'] ?>, <?= $inc['longitude'] ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <div class="gr-card" style="margin-bottom:20px">
      <div class="gr-card-title" style="margin-bottom:12px">Description</div>
      <p style="line-height:1.8;color:var(--text-mid)"><?= nl2br(htmlspecialchars($inc['description'])) ?></p>
    </div>

    <!-- Evidence -->
    <div class="gr-card" style="margin-bottom:20px">
      <div class="gr-card-header">
        <div class="gr-card-title"><i class="ri-image-line" style="color:var(--sky)"></i> Evidence (<?= count($inc['evidence']) ?>)</div>
        <?php if ($isSuperAdmin || hasPermission($userPermissions,'evidence.upload')): ?>
          <button class="gr-btn gr-btn-outline gr-btn-sm" onclick="document.getElementById('evFileInput').click()">
            <i class="ri-upload-2-line"></i> Upload
          </button>
          <input type="file" id="evFileInput" multiple accept="image/*,.pdf,.doc,.docx" style="display:none"
            onchange="uploadEvidence(this.files)">
        <?php endif; ?>
      </div>
      <?php if (empty($inc['evidence'])): ?>
        <div class="gr-empty"><i class="ri-image-off-line"></i><p>No evidence attached</p></div>
      <?php else: ?>
        <div class="gr-evidence-grid" id="evidenceGrid">
          <?php foreach ($inc['evidence'] as $ev):
            $isImg = str_starts_with($ev['file_type'], 'image/');
          ?>
            <div class="gr-evidence-item" id="ev<?= $ev['id'] ?>">
              <?php if ($isImg): ?>
                <img src="<?= upload_url($ev['file_path']) ?>" alt="<?= htmlspecialchars($ev['file_name']) ?>"
                  onclick="lightbox('<?= upload_url($ev['file_path']) ?>')">
              <?php else: ?>
                <div class="ev-icon" onclick="window.open('<?= upload_url($ev['file_path']) ?>')">
                  <i class="ri-file-pdf-line"></i>
                </div>
              <?php endif; ?>
              <div class="ev-name" title="<?= htmlspecialchars($ev['file_name']) ?>"><?= htmlspecialchars($ev['file_name']) ?></div>
              <?php if ($isSuperAdmin || hasPermission($userPermissions,'evidence.delete')
                     || (int)$ev['uploaded_by'] === (int)$currentUser->user_id): ?>
                <button onclick="deleteEvidence(<?= $ev['id'] ?>)"
                  style="width:100%;background:none;border:none;border-top:1px solid var(--border);padding:4px;cursor:pointer;color:var(--red);font-size:11px">
                  <i class="ri-delete-bin-line"></i> Remove
                </button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Status timeline -->
    <div class="gr-card">
      <div class="gr-card-title" style="margin-bottom:16px">
        <i class="ri-history-line"></i> Status History
      </div>
      <div class="gr-timeline">
        <?php if (empty($inc['updates'])): ?>
          <div class="gr-empty"><p>No updates yet</p></div>
        <?php else: ?>
          <?php foreach ($inc['updates'] as $upd):
            $dotIcon = match($upd['new_status']) {
              'open'       => '🔵', 'reviewing' => '🟡',
              'resolved'   => '🟢', 'closed'    => '⚫',
              default      => '⚪'
            };
          ?>
            <div class="gr-timeline-item">
              <div class="gr-timeline-dot-wrap">
                <div class="gr-timeline-dot"><?= $dotIcon ?></div>
                <div class="tl-line"></div>
              </div>
              <div class="gr-timeline-body">
                <div>
                  <span class="tl-actor"><?= htmlspecialchars($upd['actor_name'] ?? 'System') ?></span>
                  <span class="tl-action">
                    <?php if ($upd['old_status']): ?>
                      changed status from
                      <span class="gr-badge gr-badge-<?= $upd['old_status'] ?>"><?= $upd['old_status'] ?></span>
                      to
                    <?php else: ?>submitted as<?php endif; ?>
                    <span class="gr-badge gr-badge-<?= $upd['new_status'] ?>"><?= $upd['new_status'] ?></span>
                  </span>
                </div>
                <div class="tl-time"><?= date('d M Y, H:i', strtotime($upd['created_at'])) ?></div>
                <?php if (!empty($upd['notes'])): ?>
                  <div class="tl-note"><?= htmlspecialchars($upd['notes']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div>
    <?php if ($canStatus): ?>
    <div class="gr-card" style="margin-bottom:16px">
      <div class="gr-card-title" style="margin-bottom:12px">Quick Status Update</div>
      <select id="quickStatus" class="gr-select" style="margin-bottom:10px">
        <option value="">Change status to…</option>
        <?php foreach (['open','reviewing','resolved','closed'] as $s): ?>
          <option value="<?= $s ?>" <?= $inc['status']===$s?'disabled selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea id="quickNotes" class="gr-textarea" rows="3" placeholder="Review notes (optional)…" style="min-height:70px;margin-bottom:10px"></textarea>
      <button class="gr-btn gr-btn-primary" style="width:100%;justify-content:center" onclick="doQuickStatus()">
        <i class="ri-check-line"></i> Update Status
      </button>
    </div>
    <?php endif; ?>

    <div class="gr-card">
      <div class="gr-card-title" style="margin-bottom:12px">Reporter</div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0">
          <?= strtoupper(substr($inc['reporter_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div>
          <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($inc['reporter_name'] ?? '—') ?></div>
          <?php if (!empty($inc['reporter_phone'])): ?>
            <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($inc['reporter_phone']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div style="font-size:12px;color:var(--text-muted)">
        <div style="margin-bottom:4px"><i class="ri-building-2-line"></i> <?= htmlspecialchars($inc['site_name'] ?? '—') ?></div>
        <div><?= htmlspecialchars($inc['site_address'] ?? '') ?></div>
      </div>
    </div>
  </div>
</div>

<?php
$incId  = $id;
$pageScripts = <<<JS
<script>
var _incId = {$incId};

function lightbox(src) {
  Swal.fire({ imageUrl:src, imageAlt:'Evidence', showConfirmButton:false, width:'auto', background:'transparent' });
}

function doQuickStatus() {
  var s = document.getElementById('quickStatus').value;
  var n = document.getElementById('quickNotes').value.trim();
  if (!s) { grError('','Select a status first'); return; }
  grLoading('Updating…');
  fetch(BASE_URL+'/api/incidents?action=status&id='+_incId, {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({status:s, notes:n})
  }).then(function(r){ return r.json(); }).then(function(d){
    Swal.close();
    if (d.success) grSuccess('Updated', d.message, function(){ location.reload(); });
    else grError('', d.message);
  });
}

function deleteIncident() {
  grConfirm('Delete Incident?', 'This will permanently remove the incident and all evidence. This cannot be undone.', function() {
    grLoading('Deleting…');
    fetch(BASE_URL+'/api/incidents?id='+_incId, {method:'DELETE', credentials:'include'})
      .then(function(r){ return r.json(); }).then(function(d){
        Swal.close();
        if (d.success) { grSuccess('Deleted','Incident removed.', function(){ window.location.href=BASE_URL+'/admin/incidents'; }); }
        else grError('',d.message);
      });
  });
}

function uploadEvidence(files) {
  if (!files.length) return;
  var fd = new FormData();
  for (var i=0; i<files.length; i++) fd.append('files[]', files[i]);
  grLoading('Uploading…');
  fetch(BASE_URL+'/api/incidents/evidence?incident_id='+_incId, {
    method:'POST', credentials:'include', body:fd
  }).then(function(r){ return r.json(); }).then(function(d){
    Swal.close();
    if (d.success) grSuccess('Uploaded', d.message, function(){ location.reload(); });
    else grError('Upload failed', d.message);
  });
}

function deleteEvidence(evId) {
  grConfirm('Remove evidence?', 'The file will be permanently deleted.', function() {
    fetch(BASE_URL+'/api/incidents/evidence?id='+evId, {method:'DELETE', credentials:'include'})
      .then(function(r){ return r.json(); }).then(function(d){
        if (d.success) { var el=document.getElementById('ev'+evId); if(el) el.remove(); }
        else grError('',d.message);
      });
  });
}
</script>
JS;
require_once get_layout('admin-scripts');