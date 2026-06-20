<?php
/**
 * GuardReport — My Profile
 * File: modules/Authentication/views/profile.php
 */
$pageTitle          = 'My Profile';
$currentPage        = 'profile';
$requiredPermission = null;
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 1) . '/controllers/ProfileController.php';

$profileCtrl = new ProfileController();
$me          = $profileCtrl->getProfile($currentUser->user_id);

$timezones = ['Africa/Kigali','Africa/Nairobi','Africa/Lagos','UTC','Europe/London','Europe/Paris','America/New_York','Asia/Dubai'];
$initials  = strtoupper(substr($me['firstname'] ?? 'U', 0, 1) . substr($me['lastname'] ?? '', 0, 1));

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<style>
/* All new elements on this page are intentionally square-cornered — no border-radius. */
.pf-summary{display:flex;align-items:center;gap:20px;flex-wrap:wrap;border-left:4px solid var(--blue);}
.pf-summary-avatar{width:72px;height:72px;flex-shrink:0;border:1px solid var(--border);overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--navy);color:#fff;font-size:26px;font-weight:800;}
.pf-summary-avatar img{width:100%;height:100%;object-fit:cover;display:block;}
.pf-summary-name{font-size:18px;font-weight:700;color:var(--text-mid);}
.pf-summary-meta{font-size:13px;color:var(--text-muted);margin-top:3px;display:flex;gap:14px;flex-wrap:wrap;}
.pf-summary-meta span{display:inline-flex;align-items:center;gap:5px;}

.pf-layout{display:grid;grid-template-columns:230px 1fr;gap:20px;align-items:start;margin-top:20px;}
@media(max-width:860px){.pf-layout{grid-template-columns:1fr;}}

.pf-sidebar{background:#fff;border:1px solid var(--border);position:sticky;top:80px;}
.pf-sidebar-item{display:flex;align-items:center;gap:10px;width:100%;text-align:left;padding:12px 16px;font-size:13px;font-weight:600;color:var(--text-muted);background:none;border:none;border-left:3px solid transparent;cursor:pointer;}
.pf-sidebar-item:hover{background:var(--bg);color:var(--text-mid);}
.pf-sidebar-item.active{color:var(--blue);background:var(--bg);border-left-color:var(--blue);}
.pf-sidebar-divider{height:1px;background:var(--border);margin:4px 0;}

.pf-panel{display:none;}
.pf-panel.active{display:block;}

.pf-avatar-zone{width:108px;height:108px;position:relative;margin:0 auto 14px;cursor:pointer;border:1px solid var(--border);}
.pf-avatar-zone img,.pf-avatar-zone .pf-avatar-initials{width:108px;height:108px;display:block;object-fit:cover;}
.pf-avatar-initials{background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:800;}
.pf-avatar-btn{position:absolute;bottom:0;right:0;width:28px;height:28px;background:var(--blue);color:#fff;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:13px;}
.pf-upload-progress{width:108px;margin:8px auto 0;height:3px;background:var(--border);display:none;}
.pf-upload-progress-bar{height:100%;background:var(--blue);width:0%;transition:width .25s;}

.pf-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border);}
.pf-toggle-row:last-child{border-bottom:none;}
.pf-toggle-label{font-size:13px;font-weight:600;color:var(--text-mid);}
.pf-toggle-desc{font-size:12px;color:var(--text-muted);margin-top:2px;}
.pf-switch{position:relative;width:40px;height:22px;flex-shrink:0;}
.pf-switch input{opacity:0;width:0;height:0;position:absolute;}
.pf-switch-track{position:absolute;inset:0;background:var(--border);cursor:pointer;transition:.2s;}
.pf-switch input:checked + .pf-switch-track{background:var(--blue);}
.pf-switch-track::after{content:'';position:absolute;width:16px;height:16px;background:#fff;top:3px;left:3px;transition:.2s;}
.pf-switch input:checked + .pf-switch-track::after{left:21px;}

.pf-pw-bar{height:4px;background:var(--border);margin-top:8px;}
.pf-pw-fill{height:100%;width:0;transition:width .3s,background .3s;}
.pf-pw-label{font-size:11px;color:var(--text-muted);margin-top:4px;}

.pf-activity-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);}
.pf-activity-item:last-child{border-bottom:none;}
.pf-activity-icon{width:34px;height:34px;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:15px;flex-shrink:0;}
.pf-activity-text{font-size:13px;font-weight:600;color:var(--text-mid);}
.pf-activity-time{font-size:11.5px;color:var(--text-muted);margin-top:2px;}

.pf-info-box{background:var(--bg);border:1px solid var(--border);padding:12px 14px;font-size:12.5px;color:var(--text-muted);line-height:1.6;margin-bottom:18px;}
</style>

<div class="gr-page-header">
  <div>
    <h1>My Profile</h1>
    <div class="gr-breadcrumb">Account information, security, and preferences</div>
  </div>
</div>

<div class="gr-card pf-summary">
  <div class="pf-summary-avatar">
    <?php if (!empty($me['photo'])): ?>
      <img src="<?= upload_url($me['photo']) ?>" alt="">
    <?php else: ?>
      <?= htmlspecialchars($initials) ?>
    <?php endif; ?>
  </div>
  <div>
    <div class="pf-summary-name"><?= htmlspecialchars(($me['firstname'] ?? '').' '.($me['lastname'] ?? '')) ?></div>
    <div class="pf-summary-meta">
      <span><i class="ri-shield-user-line"></i> <?= htmlspecialchars($me['role_name'] ?? 'Guard') ?></span>
      <span><i class="ri-mail-line"></i> <?= htmlspecialchars($me['email'] ?? '') ?></span>
      <?php if (!empty($me['created_at'])): ?>
        <span><i class="ri-calendar-line"></i> Member since <?= date('M Y', strtotime($me['created_at'])) ?></span>
      <?php endif; ?>
      <?php if (!empty($me['last_login'])): ?>
        <span><i class="ri-time-line"></i> Last login <?= date('d M Y, H:i', strtotime($me['last_login'])) ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="pf-layout">
  <div class="pf-sidebar">
    <button class="pf-sidebar-item active" onclick="pfTab('basic',this)"><i class="ri-user-line"></i> Basic Info</button>
    <button class="pf-sidebar-item" onclick="pfTab('photo',this)"><i class="ri-camera-line"></i> Profile Photo</button>
    <div class="pf-sidebar-divider"></div>
    <button class="pf-sidebar-item" onclick="pfTab('password',this)"><i class="ri-lock-password-line"></i> Password</button>
    <button class="pf-sidebar-item" onclick="pfTab('activity',this)"><i class="ri-history-line"></i> Activity</button>
    <div class="pf-sidebar-divider"></div>
    <button class="pf-sidebar-item" onclick="pfTab('prefs',this)"><i class="ri-settings-3-line"></i> Preferences</button>
    <button class="pf-sidebar-item" onclick="pfTab('notifs',this)"><i class="ri-notification-3-line"></i> Notifications</button>
  </div>

  <div>
    <!-- BASIC INFO -->
    <div class="pf-panel active" id="pfBasic">
      <div class="gr-card">
        <div class="gr-card-title" style="margin-bottom:16px"><i class="ri-user-line" style="color:var(--blue)"></i> Basic Information</div>
        <div class="gr-form-row">
          <div class="gr-form-group">
            <label>First Name <span class="req">*</span></label>
            <input type="text" id="biFirst" class="gr-input" value="<?= htmlspecialchars($me['firstname'] ?? '') ?>">
          </div>
          <div class="gr-form-group">
            <label>Last Name <span class="req">*</span></label>
            <input type="text" id="biLast" class="gr-input" value="<?= htmlspecialchars($me['lastname'] ?? '') ?>">
          </div>
        </div>
        <div class="gr-form-row">
          <div class="gr-form-group">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" id="biEmail" class="gr-input" value="<?= htmlspecialchars($me['email'] ?? '') ?>">
          </div>
          <div class="gr-form-group">
            <label>Phone Number</label>
            <input type="tel" id="biPhone" class="gr-input" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="gr-form-row">
          <div class="gr-form-group">
            <label>Username</label>
            <input type="text" id="biUsername" class="gr-input" value="<?= htmlspecialchars($me['username'] ?? '') ?>">
          </div>
          <div class="gr-form-group">
            <label>Gender</label>
            <select id="biGender" class="gr-select">
              <option value="">Not specified</option>
              <option value="male"   <?= ($me['gender'] ?? '')==='male'  ?'selected':'' ?>>Male</option>
              <option value="female" <?= ($me['gender'] ?? '')==='female'?'selected':'' ?>>Female</option>
              <option value="other"  <?= ($me['gender'] ?? '')==='other' ?'selected':'' ?>>Other</option>
            </select>
          </div>
        </div>
        <div class="gr-form-row">
          <div class="gr-form-group">
            <label>Date of Birth</label>
            <input type="date" id="biDob" class="gr-input" value="<?= htmlspecialchars($me['date_of_birth'] ?? '') ?>">
          </div>
          <div class="gr-form-group">
            <label>Address</label>
            <input type="text" id="biAddress" class="gr-input" value="<?= htmlspecialchars($me['address'] ?? '') ?>">
          </div>
        </div>
        <div class="gr-form-group">
          <label>Bio</label>
          <textarea id="biBio" class="gr-textarea" rows="3" placeholder="A short introduction…"><?= htmlspecialchars($me['bio'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button class="gr-btn gr-btn-primary" id="btnSaveBasic" onclick="saveBasicInfo()"><i class="ri-save-line"></i> Save Changes</button>
        </div>
      </div>
    </div>

    <!-- PHOTO -->
    <div class="pf-panel" id="pfPhoto">
      <div class="gr-card" style="text-align:center">
        <div class="gr-card-title" style="margin-bottom:16px;text-align:left"><i class="ri-camera-line" style="color:var(--blue)"></i> Profile Photo</div>
        <div class="pf-avatar-zone" onclick="document.getElementById('avatarFileInput').click()" title="Click to change photo">
          <?php if (!empty($me['photo'])): ?>
            <img id="avatarPreviewImg" src="<?= upload_url($me['photo']) ?>" alt="">
          <?php else: ?>
            <div class="pf-avatar-initials" id="avatarInitials"><?= htmlspecialchars($initials) ?></div>
            <img id="avatarPreviewImg" style="display:none" alt="">
          <?php endif; ?>
          <div class="pf-avatar-btn"><i class="ri-camera-line"></i></div>
        </div>
        <div class="pf-upload-progress" id="uploadProgress"><div class="pf-upload-progress-bar" id="uploadProgressBar"></div></div>
        <input type="file" id="avatarFileInput" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="handleAvatarChange(this)">
        <p style="font-size:12.5px;color:var(--text-muted);margin-top:12px;line-height:1.6">
          Click the photo to upload a new one.<br>JPG, PNG, GIF or WEBP — max 2&nbsp;MB
        </p>
        <div id="avatarStatus" style="margin-top:8px;font-size:12.5px"></div>
        <div style="display:flex;justify-content:center;gap:10px;margin-top:16px">
          <button class="gr-btn gr-btn-primary gr-btn-sm" onclick="document.getElementById('avatarFileInput').click()"><i class="ri-upload-line"></i> Choose Photo</button>
          <?php if (!empty($me['photo'])): ?>
            <button class="gr-btn gr-btn-outline gr-btn-sm" style="color:var(--red)" onclick="removeAvatar()"><i class="ri-delete-bin-line"></i> Remove Photo</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PASSWORD -->
    <div class="pf-panel" id="pfPassword">
      <div class="gr-card">
        <div class="gr-card-title" style="margin-bottom:16px"><i class="ri-lock-password-line" style="color:var(--blue)"></i> Change Password</div>
        <div class="pf-info-box"><i class="ri-information-line"></i> Use at least 8 characters, mixing letters and numbers.</div>
        <div class="gr-form-group">
          <label>Current Password <span class="req">*</span></label>
          <input type="password" id="pwCurrent" class="gr-input" placeholder="Your current password">
        </div>
        <div class="gr-form-group">
          <label>New Password <span class="req">*</span></label>
          <input type="password" id="pwNew" class="gr-input" placeholder="Min. 8 characters" oninput="checkPwStrength(this.value)">
          <div class="pf-pw-bar"><div class="pf-pw-fill" id="pwStrengthBar"></div></div>
          <div class="pf-pw-label" id="pwStrengthLabel"></div>
        </div>
        <div class="gr-form-group">
          <label>Confirm New Password <span class="req">*</span></label>
          <input type="password" id="pwConfirm" class="gr-input" placeholder="Repeat new password">
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button class="gr-btn gr-btn-primary" id="btnChangePw" onclick="changePassword()"><i class="ri-lock-line"></i> Update Password</button>
        </div>
      </div>
    </div>

    <!-- ACTIVITY -->
    <div class="pf-panel" id="pfActivity">
      <div class="gr-card">
        <div class="gr-card-title" style="margin-bottom:8px"><i class="ri-history-line" style="color:var(--blue)"></i> Recent Activity</div>
        <div id="activityContainer">
          <div class="gr-empty" style="padding:24px 0"><span class="spinner"></span></div>
        </div>
      </div>
    </div>

    <!-- PREFERENCES -->
    <div class="pf-panel" id="pfPrefs">
      <div class="gr-card">
        <div class="gr-card-title" style="margin-bottom:16px"><i class="ri-settings-3-line" style="color:var(--blue)"></i> Preferences</div>
        <div class="gr-form-row">
          <div class="gr-form-group">
            <label>Language</label>
            <select id="prefLang" class="gr-select">
              <option value="en">English</option>
              <option value="fr">Français</option>
              <option value="rw">Kinyarwanda</option>
            </select>
          </div>
          <div class="gr-form-group">
            <label>Timezone</label>
            <select id="prefTz" class="gr-select">
              <?php foreach ($timezones as $tz): ?>
                <option value="<?= $tz ?>"><?= str_replace('_',' ',$tz) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="gr-form-group">
          <label>Date Format</label>
          <select id="prefDateFmt" class="gr-select">
            <option value="Y-m-d">YYYY-MM-DD</option>
            <option value="d/m/Y">DD/MM/YYYY</option>
            <option value="m/d/Y">MM/DD/YYYY</option>
            <option value="M d, Y">Mon DD, YYYY</option>
          </select>
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button class="gr-btn gr-btn-primary" id="btnSavePrefs" onclick="savePreferences()"><i class="ri-save-line"></i> Save Preferences</button>
        </div>
      </div>
    </div>

    <!-- NOTIFICATIONS -->
    <div class="pf-panel" id="pfNotifs">
      <div class="gr-card">
        <div class="gr-card-title" style="margin-bottom:8px"><i class="ri-notification-3-line" style="color:var(--blue)"></i> Notification Preferences</div>
        <div id="notifContainer">
          <div class="gr-empty" style="padding:24px 0"><span class="spinner"></span></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:14px">
          <button class="gr-btn gr-btn-primary" onclick="saveNotifications()"><i class="ri-save-line"></i> Save</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
var loadedTabs = {};

function pfTab(name, btn) {
  document.querySelectorAll('.pf-panel').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.pf-sidebar-item').forEach(function(b){ b.classList.remove('active'); });
  document.getElementById('pf'+name.charAt(0).toUpperCase()+name.slice(1)).classList.add('active');
  btn.classList.add('active');
  if (name === 'activity' && !loadedTabs.activity) { loadActivity(); loadedTabs.activity = true; }
  if (name === 'prefs'    && !loadedTabs.prefs)    { loadPrefs();    loadedTabs.prefs    = true; }
  if (name === 'notifs'   && !loadedTabs.notifs)   { loadNotifs();   loadedTabs.notifs   = true; }
}

/* ════ AVATAR ════ */
function handleAvatarChange(input) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];
  if (file.size > 2*1024*1024) { grError('Too Large','Image must be under 2 MB.'); return; }

  var reader = new FileReader();
  reader.onload = function(e) {
    var img = document.getElementById('avatarPreviewImg');
    img.src = e.target.result; img.style.display = 'block';
    var initials = document.getElementById('avatarInitials');
    if (initials) initials.style.display = 'none';
  };
  reader.readAsDataURL(file);
  uploadAvatar(file);
}

async function uploadAvatar(file) {
  var progress = document.getElementById('uploadProgress');
  var bar      = document.getElementById('uploadProgressBar');
  var status   = document.getElementById('avatarStatus');
  progress.style.display = 'block'; bar.style.width = '30%';
  status.innerHTML = '<span style="color:var(--text-muted)"><i class="ri-loader-4-line"></i> Uploading…</span>';

  var form = new FormData();
  form.append('avatar', file);

  try {
    var res  = await fetch(BASE_URL + '/api/profile?action=upload-avatar', { method:'POST', credentials:'include', body: form });
    bar.style.width = '90%';
    var data = await res.json();
    if (data.success) {
      bar.style.width = '100%';
      status.innerHTML = '<span style="color:var(--green)"><i class="ri-checkbox-circle-line"></i> Photo updated</span>';
      setTimeout(function(){ progress.style.display='none'; status.innerHTML=''; }, 2500);
    } else {
      status.innerHTML = '<span style="color:var(--red)"><i class="ri-error-warning-line"></i> '+data.message+'</span>';
      progress.style.display = 'none';
    }
  } catch (e) {
    status.innerHTML = '<span style="color:var(--red)"><i class="ri-wifi-off-line"></i> Network error. Please retry.</span>';
    progress.style.display = 'none';
  }
}

function removeAvatar() {
  grConfirm('Remove photo?','Your initials will be shown instead.', async function(){
    try {
      var res  = await fetch(BASE_URL+'/api/profile?action=update-profile', {
        method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({photo:''})
      });
      var data = await res.json();
      if (data.success) grSuccess('Removed','Profile photo removed.', function(){ location.reload(); });
      else grError('Error', data.message);
    } catch(e){ grError('Error','Network error.'); }
  });
}

/* ════ BASIC INFO ════ */
async function saveBasicInfo() {
  var btn = document.getElementById('btnSaveBasic');
  btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving…';
  try {
    var res = await fetch(BASE_URL+'/api/profile?action=update-profile', {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        firstname: document.getElementById('biFirst').value.trim(),
        lastname:  document.getElementById('biLast').value.trim(),
        email:     document.getElementById('biEmail').value.trim(),
        phone:     document.getElementById('biPhone').value.trim(),
        username:  document.getElementById('biUsername').value.trim(),
        gender:    document.getElementById('biGender').value,
        date_of_birth: document.getElementById('biDob').value,
        address:   document.getElementById('biAddress').value.trim(),
        bio:       document.getElementById('biBio').value.trim(),
      })
    });
    var data = await res.json();
    if (data.success) grSuccess('Saved!','Your profile has been updated.');
    else grError('Error', data.message || 'Could not save changes.');
  } catch(e){ grError('Error','Network error.'); }
  finally { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Save Changes'; }
}

/* ════ PASSWORD ════ */
function checkPwStrength(v) {
  var bar = document.getElementById('pwStrengthBar'), label = document.getElementById('pwStrengthLabel');
  var score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  var pct = (score/4)*100;
  var colors = ['var(--red)','var(--red)','#d97706','var(--green)','var(--green)'];
  var labels = ['Too short','Weak','Fair','Good','Strong'];
  bar.style.width = v ? pct+'%' : '0';
  bar.style.background = colors[score];
  label.textContent = v ? labels[score] : '';
}
async function changePassword() {
  var cur = document.getElementById('pwCurrent').value;
  var nw  = document.getElementById('pwNew').value;
  var cf  = document.getElementById('pwConfirm').value;
  if (!cur || !nw || !cf) return grError('Required','Please fill in all password fields.');
  if (nw.length < 8) return grError('Too Short','Password must be at least 8 characters.');
  if (nw !== cf) return grError('Mismatch','New passwords do not match.');
  var btn = document.getElementById('btnChangePw');
  btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Updating…';
  try {
    var res = await fetch(BASE_URL+'/api/profile?action=change-password', {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ current_password:cur, new_password:nw, confirm_password:cf })
    });
    var data = await res.json();
    if (data.success) {
      grSuccess('Done!','Password changed successfully.');
      ['pwCurrent','pwNew','pwConfirm'].forEach(function(id){ document.getElementById(id).value=''; });
      document.getElementById('pwStrengthBar').style.width='0';
      document.getElementById('pwStrengthLabel').textContent='';
    } else grError('Error', data.message);
  } catch(e){ grError('Error','Network error.'); }
  finally { btn.disabled = false; btn.innerHTML = '<i class="ri-lock-line"></i> Update Password'; }
}

/* ════ ACTIVITY ════ */
function loadActivity() {
  var el = document.getElementById('activityContainer');
  fetch(BASE_URL+'/api/profile?action=activity&limit=30', {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success || !d.data.length) {
        el.innerHTML = '<div class="gr-empty" style="padding:24px 0"><i class="ri-history-line"></i><p>No activity recorded yet</p></div>';
        return;
      }
      var icons = { create:'ri-add-circle-line', status_change:'ri-loop-right-line', profile_update:'ri-user-settings-line', password_change:'ri-lock-line', update:'ri-edit-line', delete:'ri-delete-bin-line' };
      el.innerHTML = d.data.map(function(a) {
        var icon = icons[a.action] || 'ri-information-line';
        return '<div class="pf-activity-item">'
          +'<div class="pf-activity-icon"><i class="'+icon+'"></i></div>'
          +'<div><div class="pf-activity-text">'+escHtmlPf(a.description||a.action)+'</div>'
          +'<div class="pf-activity-time">'+new Date(a.created_at).toLocaleString('en-GB')+'</div></div>'
          +'</div>';
      }).join('');
    }).catch(function(){ el.innerHTML = '<div class="gr-empty" style="padding:24px 0">Could not load activity</div>'; });
}
function escHtmlPf(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

/* ════ PREFERENCES ════ */
function loadPrefs() {
  fetch(BASE_URL+'/api/profile?action=settings', {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      document.getElementById('prefLang').value = d.data.language || 'en';
      document.getElementById('prefTz').value    = d.data.timezone || 'Africa/Kigali';
      document.getElementById('prefDateFmt').value = d.data.date_format || 'Y-m-d';
    });
}
async function savePreferences() {
  var btn = document.getElementById('btnSavePrefs');
  btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving…';
  try {
    var res = await fetch(BASE_URL+'/api/profile?action=update-settings', {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        language: document.getElementById('prefLang').value,
        timezone: document.getElementById('prefTz').value,
        date_format: document.getElementById('prefDateFmt').value,
      })
    });
    var data = await res.json();
    if (data.success) grSuccess('Saved!','Preferences updated.');
    else grError('Error', data.message);
  } catch(e){ grError('Error','Network error.'); }
  finally { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Save Preferences'; }
}

/* ════ NOTIFICATIONS ════ */
var NOTIF_DEFS = [
  {id:'email_login',            label:'Login Alerts',            desc:'Email me when a new login is detected on my account'},
  {id:'email_incident_updates',  label:'Incident Status Updates', desc:'Email me when an incident I reported changes status'},
  {id:'email_shift_reminders',   label:'Shift Reminders',         desc:'Email me ahead of my upcoming scheduled shifts'},
  {id:'push_new_incidents',      label:'New Incident Alerts',     desc:'Notify me in-app when a new incident is submitted (supervisors/admins)'},
];
function loadNotifs() {
  var el = document.getElementById('notifContainer');
  fetch(BASE_URL+'/api/profile?action=notification-settings', {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      var vals = d.success ? d.data : {};
      el.innerHTML = NOTIF_DEFS.map(function(n) {
        var checked = (vals[n.id] === undefined ? 1 : vals[n.id]) ? 'checked' : '';
        return '<div class="pf-toggle-row">'
          +'<div><div class="pf-toggle-label">'+n.label+'</div><div class="pf-toggle-desc">'+n.desc+'</div></div>'
          +'<label class="pf-switch"><input type="checkbox" id="notif_'+n.id+'" '+checked+'><span class="pf-switch-track"></span></label>'
          +'</div>';
      }).join('');
    });
}
async function saveNotifications() {
  var payload = {};
  NOTIF_DEFS.forEach(function(n){ payload[n.id] = document.getElementById('notif_'+n.id).checked ? 1 : 0; });
  try {
    var res = await fetch(BASE_URL+'/api/profile?action=update-notifications', {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    var data = await res.json();
    if (data.success) grSuccess('Saved!','Notification preferences updated.');
    else grError('Error', data.message);
  } catch(e){ grError('Error','Network error.'); }
}
</script>
JS;
require_once get_layout('admin-scripts');
