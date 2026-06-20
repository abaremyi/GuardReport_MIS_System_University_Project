<?php
/** GuardReport — Shifts | File: modules/Shifts/views/shifts.php */
$pageTitle        = 'Shifts';
$currentPage      = 'shifts';
$requiredPermission = 'shifts.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/models/ShiftModel.php';
require_once dirname(__DIR__, 3) . '/modules/Sites/models/SiteModel.php';
require_once dirname(__DIR__, 3) . '/modules/Authentication/models/UserModel.php';

$shm    = new ShiftModel(Database::getConnection());
$sm     = new SiteModel(Database::getConnection());
$um     = new UserModel(Database::getConnection());
$sites  = $sm->getAll(['status' => 'active']);
$guards = $um->getGuards();

$canCreate = $isSuperAdmin || hasPermission($userPermissions, 'shifts.create');
$canEdit   = $isSuperAdmin || hasPermission($userPermissions, 'shifts.update');
$canDelete = $isSuperAdmin || hasPermission($userPermissions, 'shifts.delete');

// Guards see only their own upcoming shifts
$myShifts = [];
if (!$isSuperAdmin && !hasPermission($userPermissions, 'shifts.create')) {
    $myShifts = $shm->getUpcoming((int)$currentUser->user_id);
}

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>Shifts</h1>
    <div class="gr-breadcrumb">Guard scheduling and shift management</div>
  </div>
  <div class="gr-page-actions">
    <?php if ($canCreate): ?>
      <button class="gr-btn gr-btn-primary" onclick="openShiftModal()">
        <i class="ri-calendar-event-line"></i> Schedule Shift
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!$isSuperAdmin && !hasPermission($userPermissions,'shifts.create')): ?>
  <!-- Guard view: my upcoming shifts -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:16px">
      <i class="ri-calendar-schedule-line" style="color:var(--blue)"></i> My Upcoming Shifts
    </div>
    <?php if (empty($myShifts)): ?>
      <div class="gr-empty"><i class="ri-calendar-check-line"></i><p>No upcoming shifts assigned</p></div>
    <?php else: ?>
      <?php foreach ($myShifts as $sh): ?>
        <div class="gr-card" style="margin-bottom:12px;border-left:4px solid var(--blue)">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
              <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($sh['site_name'] ?? '—') ?></div>
              <div style="font-size:12px;color:var(--text-muted);margin-top:3px">
                <i class="ri-map-pin-line"></i> <?= htmlspecialchars($sh['site_address'] ?? '') ?>
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:600;font-size:14px">
                <?= date('D, d M Y', strtotime($sh['start_time'])) ?>
              </div>
              <div style="font-size:13px;color:var(--text-mid)">
                <?= date('H:i', strtotime($sh['start_time'])) ?> –
                <?= date('H:i', strtotime($sh['end_time'])) ?>
              </div>
            </div>
            <span class="gr-badge gr-badge-<?= $sh['status'] ?>"><?= $sh['status'] ?></span>
          </div>
          <?php if (!empty($sh['notes'])): ?>
            <div style="margin-top:8px;font-size:13px;color:var(--text-muted);padding-top:8px;border-top:1px solid var(--border)">
              <?= htmlspecialchars($sh['notes']) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- Admin / Supervisor view: full list with filters -->
  <div class="gr-card mb-4">
    <div class="gr-filter-bar">
      <select id="shGuard" class="gr-select">
        <option value="">All Guards</option>
        <?php foreach ($guards as $g): ?>
          <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['firstname'].' '.$g['lastname']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="shSite" class="gr-select">
        <option value="">All Sites</option>
        <?php foreach ($sites as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="shStatus" class="gr-select">
        <option value="">All Statuses</option>
        <option value="scheduled">Scheduled</option>
        <option value="active">Active</option>
        <option value="completed">Completed</option>
        <option value="missed">Missed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <input type="date" id="shFrom" class="gr-input" style="width:145px" value="<?= date('Y-m-d') ?>">
      <input type="date" id="shTo"   class="gr-input" style="width:145px" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
      <button class="gr-btn gr-btn-outline" onclick="loadShifts()"><i class="ri-search-line"></i> Filter</button>
      <button class="gr-btn gr-btn-outline" onclick="clearSh()"><i class="ri-refresh-line"></i></button>
    </div>
  </div>

  <div class="gr-card">
    <div class="gr-table-wrap">
      <table class="gr-table">
        <thead>
          <tr>
            <th>#</th><th>Guard</th><th>Site</th><th>Start</th><th>End</th>
            <th>Duration</th><th>Status</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody id="shiftsTbody">
          <tr><td colspan="9" style="text-align:center;padding:28px"><span class="spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Schedule Shift Modal -->
<?php if ($canCreate): ?>
<div id="shiftModal" style="display:none" class="gr-modal-backdrop">
  <div class="gr-modal" style="max-width:540px">
    <div class="gr-modal-header">
      <div class="gr-modal-title" id="shModalTitle">Schedule Shift</div>
      <button onclick="closeShiftModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-muted)">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="gr-modal-body">
      <input type="hidden" id="shiftId">
      <div class="gr-form-row">
        <div class="gr-form-group">
          <label>Guard <span class="req">*</span></label>
          <select id="shGuardSel" class="gr-select">
            <option value="">Select guard…</option>
            <?php foreach ($guards as $g): ?>
              <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['firstname'].' '.$g['lastname']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="gr-form-group">
          <label>Site <span class="req">*</span></label>
          <select id="shSiteSel" class="gr-select">
            <option value="">Select site…</option>
            <?php foreach ($sites as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="gr-form-row">
        <div class="gr-form-group">
          <label>Start Date &amp; Time <span class="req">*</span></label>
          <input id="shStart" type="datetime-local" class="gr-input">
        </div>
        <div class="gr-form-group">
          <label>End Date &amp; Time <span class="req">*</span></label>
          <input id="shEnd" type="datetime-local" class="gr-input">
        </div>
      </div>
      <div class="gr-form-group">
        <label>Status</label>
        <select id="shStatusSel" class="gr-select">
          <option value="scheduled">Scheduled</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="gr-form-group">
        <label>Notes</label>
        <textarea id="shNotes" class="gr-textarea" rows="2" placeholder="Optional shift notes…" style="min-height:60px"></textarea>
      </div>
    </div>
    <div class="gr-modal-footer">
      <button class="gr-btn gr-btn-outline" onclick="closeShiftModal()">Cancel</button>
      <button class="gr-btn gr-btn-primary" onclick="saveShift()">
        <i class="ri-save-line"></i> Save Shift
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$canDeleteJs = (int)($canDelete);
$pageScripts = <<<JS
<script>
var _canDelete = {$canDeleteJs};

/*
 * FIX (was throwing "Uncaught SyntaxError: Invalid or unexpected token"):
 * The previous version embedded JSON.stringify(sh) directly inside an
 * inline onclick="" attribute. Any apostrophe, quote, or special character
 * in a guard name or shift note would break out of the attribute and
 * produce invalid inline JS. Shift data is now kept in this in-memory
 * cache, and onclick only ever passes a plain numeric id — nothing to escape.
 */
var _shiftsCache = {};

function loadShifts() {
  var p = new URLSearchParams({
    action:'list',
    guard_id: document.getElementById('shGuard').value,
    site_id:  document.getElementById('shSite').value,
    status:   document.getElementById('shStatus').value,
    date_from:document.getElementById('shFrom').value,
    date_to:  document.getElementById('shTo').value,
  });
  var tbody = document.getElementById('shiftsTbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px"><span class="spinner"></span></td></tr>';
  fetch(BASE_URL+'/api/shifts?'+p,{credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success || !d.data.length) {
        _shiftsCache = {};
        tbody.innerHTML = '<tr><td colspan="9" class="gr-empty" style="padding:28px;text-align:center">No shifts found</td></tr>';
        return;
      }
      _shiftsCache = {};
      tbody.innerHTML = d.data.map(function(sh) {
        _shiftsCache[sh.id] = sh;
        var start = new Date(sh.start_time);
        var end   = new Date(sh.end_time);
        var dur   = Math.round((end-start)/3600000);
        var actions = '<button type="button" onclick="editShift('+sh.id+')" class="gr-btn gr-btn-outline gr-btn-sm"><i class="ri-edit-line"></i></button> ';
        if(_canDelete) actions += '<button type="button" onclick="deleteShift('+sh.id+')" class="gr-btn gr-btn-outline gr-btn-sm" style="color:var(--red)"><i class="ri-delete-bin-line"></i></button>';
        return '<tr>'
          +'<td style="font-family:monospace;font-size:12px;color:var(--text-muted)">#'+sh.id+'</td>'
          +'<td style="font-weight:600">'+escHtml(sh.guard_name||'—')+'</td>'
          +'<td>'+escHtml(sh.site_name||'—')+'</td>'
          +'<td style="font-size:13px">'+fmtDt(sh.start_time)+'</td>'
          +'<td style="font-size:13px">'+fmtDt(sh.end_time)+'</td>'
          +'<td style="font-size:13px;color:var(--text-muted)">'+dur+'h</td>'
          +'<td><span class="gr-badge gr-badge-'+(sh.status==='scheduled'||sh.status==='active'?sh.status:'closed')+'">'+sh.status+'</span></td>'
          +'<td style="font-size:12px;color:var(--text-muted);max-width:120px" class="truncate">'+escHtml(sh.notes||'')+'</td>'
          +'<td class="td-actions">'+actions+'</td>'
          +'</tr>';
      }).join('');
    });
}
function fmtDt(s){ var d=new Date(s); return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'})+' '+d.toTimeString().slice(0,5); }
function escHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function clearSh(){ ['shGuard','shSite','shStatus'].forEach(function(id){ document.getElementById(id).value=''; }); loadShifts(); }

function openShiftModal() {
  document.getElementById('shiftId').value='';
  document.getElementById('shModalTitle').textContent='Schedule Shift';
  ['shGuardSel','shSiteSel'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('shStart').value='';
  document.getElementById('shEnd').value='';
  document.getElementById('shStatusSel').value='scheduled';
  document.getElementById('shNotes').value='';
  document.getElementById('shiftModal').style.display='flex';
}

function editShift(id) {
  var sh = _shiftsCache[id];
  if (!sh) return;
  document.getElementById('shiftId').value=sh.id;
  document.getElementById('shModalTitle').textContent='Edit Shift';
  document.getElementById('shGuardSel').value=sh.guard_id||'';
  document.getElementById('shSiteSel').value=sh.site_id||'';
  document.getElementById('shStart').value=sh.start_time?sh.start_time.replace(' ','T').slice(0,16):'';
  document.getElementById('shEnd').value=sh.end_time?sh.end_time.replace(' ','T').slice(0,16):'';
  document.getElementById('shStatusSel').value=sh.status||'scheduled';
  document.getElementById('shNotes').value=sh.notes||'';
  document.getElementById('shiftModal').style.display='flex';
}
function closeShiftModal() { document.getElementById('shiftModal').style.display='none'; }
function saveShift() {
  var id = document.getElementById('shiftId').value;
  var body = {
    guard_id:   document.getElementById('shGuardSel').value,
    site_id:    document.getElementById('shSiteSel').value,
    start_time: document.getElementById('shStart').value,
    end_time:   document.getElementById('shEnd').value,
    status:     document.getElementById('shStatusSel').value,
    notes:      document.getElementById('shNotes').value.trim(),
  };
  if(!body.guard_id||!body.site_id||!body.start_time||!body.end_time){ grError('','Guard, site, start and end times are required'); return; }
  var url = id ? BASE_URL+'/api/shifts?action=update&id='+id : BASE_URL+'/api/shifts?action=create';
  grLoading(id?'Updating…':'Scheduling…');
  fetch(url,{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
    .then(function(r){ return r.json(); }).then(function(d){
      Swal.close();
      if(d.success) grSuccess('Saved',d.message,function(){ closeShiftModal(); loadShifts(); });
      else grError('',d.message);
    });
}
function deleteShift(id) {
  grConfirm('Cancel Shift?','The shift will be removed.',function(){
    fetch(BASE_URL+'/api/shifts?id='+id,{method:'DELETE',credentials:'include'})
      .then(function(r){ return r.json(); }).then(function(d){
        if(d.success) loadShifts();
        else grError('',d.message);
      });
  });
}
loadShifts();
</script>
JS;
require_once get_layout('admin-scripts');
