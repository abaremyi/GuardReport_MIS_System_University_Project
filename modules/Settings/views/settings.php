<?php
/**
 * GuardReport — System Settings
 * File: modules/Settings/views/settings.php
 */
$pageTitle          = 'System Settings';
$currentPage        = 'settings';
$requiredPermission = 'settings.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/controllers/SettingsController.php';

$settingsCtrl = new SettingsController();
$payload      = $settingsCtrl->index();
$schema       = $payload['schema'];
$values       = $payload['values'];
$canManage    = $isSuperAdmin || hasPermission($userPermissions, 'settings.manage');

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<style>
/* Square corners throughout — no border-radius. */
.st-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px;}
@media(max-width:980px){.st-grid{grid-template-columns:1fr;}}
.st-card-icon{width:30px;height:30px;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:14px;}
.st-row{padding:13px 0;border-bottom:1px solid var(--border);}
.st-row:last-child{border-bottom:none;}
.st-row-head{display:flex;align-items:center;justify-content:space-between;gap:12px;}
.st-row-label{font-size:13px;font-weight:600;color:var(--text-mid);}
.st-row-desc{font-size:11.5px;color:var(--text-muted);margin-top:2px;}
.st-switch{position:relative;width:40px;height:22px;flex-shrink:0;}
.st-switch input{opacity:0;width:0;height:0;position:absolute;}
.st-switch-track{position:absolute;inset:0;background:var(--border);cursor:pointer;transition:.2s;}
.st-switch input:checked + .st-switch-track{background:var(--blue);}
.st-switch-track::after{content:'';position:absolute;width:16px;height:16px;background:#fff;top:3px;left:3px;transition:.2s;}
.st-switch input:checked + .st-switch-track::after{left:21px;}
.st-savebar{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px;margin-top:20px;z-index:50;}
.st-readonly-note{background:var(--bg);border:1px solid var(--border);padding:10px 14px;font-size:12.5px;color:var(--text-muted);margin-bottom:18px;}
</style>

<div class="gr-page-header">
  <div>
    <h1>System Settings</h1>
    <div class="gr-breadcrumb">Application-wide configuration for GuardReport</div>
  </div>
</div>

<?php if (!$canManage): ?>
  <div class="st-readonly-note"><i class="ri-lock-line"></i> You have view-only access to these settings. Contact an administrator to make changes.</div>
<?php endif; ?>

<div class="st-grid">

  <!-- GENERAL -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:4px"><div class="st-card-icon"><i class="ri-global-line"></i></div> General</div>
    <div class="st-row">
      <div class="st-row-label">Application Name</div>
      <input type="text" class="gr-input" data-key="app_name" data-type="string" value="<?= htmlspecialchars($values['app_name']) ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px">
    </div>
    <div class="st-row">
      <div class="st-row-label">Company / Agency Name</div>
      <div class="st-row-desc">Shown on the header of printed and PDF reports</div>
      <input type="text" class="gr-input" data-key="company_name" data-type="string" value="<?= htmlspecialchars($values['company_name']) ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px" placeholder="e.g. Acme Security Services Ltd">
    </div>
    <div class="st-row">
      <div class="st-row-label">Default Timezone</div>
      <select class="gr-select" data-key="app_timezone" data-type="string" <?= $canManage?'':'disabled' ?> style="margin-top:8px">
        <?php foreach (['Africa/Kigali','Africa/Nairobi','Africa/Lagos','UTC','Europe/London'] as $tz): ?>
          <option value="<?= $tz ?>" <?= $values['app_timezone']===$tz?'selected':'' ?>><?= $tz ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="st-row">
      <div class="st-row-label">Default Date Format</div>
      <select class="gr-select" data-key="date_format" data-type="string" <?= $canManage?'':'disabled' ?> style="margin-top:8px">
        <?php foreach (['Y-m-d'=>'YYYY-MM-DD','d/m/Y'=>'DD/MM/YYYY','m/d/Y'=>'MM/DD/YYYY'] as $fmt=>$lbl): ?>
          <option value="<?= $fmt ?>" <?= $values['date_format']===$fmt?'selected':'' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- INCIDENTS -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:4px"><div class="st-card-icon"><i class="ri-alert-line"></i></div> Incidents</div>
    <div class="st-row">
      <div class="st-row-label">Auto-close resolved incidents after (days)</div>
      <div class="st-row-desc">0 disables auto-closing entirely</div>
      <input type="number" min="0" class="gr-input" data-key="incident_auto_close_days" data-type="integer" value="<?= (int)$values['incident_auto_close_days'] ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px;max-width:140px">
    </div>
    <div class="st-row">
      <div class="st-row-label">Default Severity for New Incidents</div>
      <select class="gr-select" data-key="default_incident_severity" data-type="string" <?= $canManage?'':'disabled' ?> style="margin-top:8px">
        <?php foreach (['low','medium','high','critical'] as $s): ?>
          <option value="<?= $s ?>" <?= $values['default_incident_severity']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- UPLOADS -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:4px"><div class="st-card-icon"><i class="ri-upload-cloud-2-line"></i></div> Uploads &amp; Evidence Files</div>
    <div class="st-row">
      <div class="st-row-label">Max Evidence File Size (MB)</div>
      <input type="number" min="1" class="gr-input" data-key="max_upload_size_mb" data-type="integer" value="<?= (int)$values['max_upload_size_mb'] ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px;max-width:140px">
    </div>
    <div class="st-row">
      <div class="st-row-label">Allowed File Extensions</div>
      <div class="st-row-desc">Comma-separated, no dots — e.g. jpg, png, pdf, mp4</div>
      <input type="text" class="gr-input" data-key="allowed_file_types" data-type="json" value="<?= htmlspecialchars(implode(', ', (array)$values['allowed_file_types'])) ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px">
    </div>
  </div>

  <!-- SECURITY -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:4px"><div class="st-card-icon"><i class="ri-shield-keyhole-line"></i></div> Security</div>
    <div class="st-row">
      <div class="st-row-label">Session Timeout (minutes)</div>
      <input type="number" min="5" class="gr-input" data-key="session_timeout_minutes" data-type="integer" value="<?= (int)$values['session_timeout_minutes'] ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px;max-width:140px">
    </div>
    <div class="st-row">
      <div class="st-row-label">Minimum Password Length</div>
      <input type="number" min="6" max="32" class="gr-input" data-key="password_min_length" data-type="integer" value="<?= (int)$values['password_min_length'] ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px;max-width:140px">
    </div>
    <div class="st-row">
      <div class="st-row-label">Force Password Change Every (days)</div>
      <div class="st-row-desc">0 = never force a change</div>
      <input type="number" min="0" class="gr-input" data-key="force_password_change_days" data-type="integer" value="<?= (int)$values['force_password_change_days'] ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px;max-width:140px">
    </div>
  </div>

  <!-- NOTIFICATIONS -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:4px"><div class="st-card-icon"><i class="ri-notification-3-line"></i></div> Notifications</div>
    <div class="st-row st-row-head">
      <div>
        <div class="st-row-label">Notify supervisors on new incident submissions</div>
      </div>
      <label class="st-switch"><input type="checkbox" data-key="notify_on_incident_submit" data-type="boolean" <?= $values['notify_on_incident_submit']?'checked':'' ?> <?= $canManage?'':'disabled' ?>><span class="st-switch-track"></span></label>
    </div>
    <div class="st-row st-row-head">
      <div>
        <div class="st-row-label">Send shift reminder notifications</div>
      </div>
      <label class="st-switch"><input type="checkbox" data-key="notify_on_shift_reminder" data-type="boolean" <?= $values['notify_on_shift_reminder']?'checked':'' ?> <?= $canManage?'':'disabled' ?>><span class="st-switch-track"></span></label>
    </div>
  </div>

  <!-- REPORTS -->
  <div class="gr-card">
    <div class="gr-card-title" style="margin-bottom:4px"><div class="st-card-icon"><i class="ri-file-chart-line"></i></div> Reports</div>
    <div class="st-row">
      <div class="st-row-label">Report Footer / Confidentiality Note</div>
      <div class="st-row-desc">Printed at the bottom of every generated report and PDF</div>
      <input type="text" class="gr-input" data-key="report_footer_text" data-type="string" value="<?= htmlspecialchars($values['report_footer_text']) ?>" <?= $canManage?'':'disabled' ?> style="margin-top:8px">
    </div>
  </div>

</div>

<?php if ($canManage): ?>
<div class="st-savebar">
  <button class="gr-btn gr-btn-outline" onclick="location.reload()">Discard Changes</button>
  <button class="gr-btn gr-btn-primary" id="btnSaveSettings" onclick="saveSettings()"><i class="ri-save-line"></i> Save All Settings</button>
</div>
<?php endif; ?>

<?php
$pageScripts = <<<'JS'
<script>
async function saveSettings() {
  var btn = document.getElementById('btnSaveSettings');
  var payload = {};
  document.querySelectorAll('[data-key]').forEach(function(el) {
    var key = el.dataset.key, type = el.dataset.type;
    if (type === 'boolean') payload[key] = el.checked ? 1 : 0;
    else payload[key] = el.value;
  });
  btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving…';
  try {
    var res  = await fetch(BASE_URL+'/api/settings?action=update', {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    var data = await res.json();
    if (data.success) grSuccess('Saved!', data.message);
    else grError('Error', data.message);
  } catch(e) { grError('Error','Network error.'); }
  finally { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Save All Settings'; }
}
</script>
JS;
require_once get_layout('admin-scripts');
