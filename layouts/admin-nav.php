<?php
/**
 * GuardReport — Admin Navigation
 * File: layouts/admin-nav.php
 *
 * CHANGE LOG:
 *  - Removed "Add User" submenu link — user creation now happens inline
 *    on the Users page itself (modal), matching the Shifts/Roles pattern.
 *  - "Reports" converted to a submenu: Overview + Report Builder.
 */
$navItems = [
  ['label'=>'Dashboard', 'url'=>url('admin/dashboard'),  'icon'=>'ri-dashboard-3-line',    'page'=>'dashboard', 'perm'=>''],
  ['label'=>'Incidents', 'url'=>'#',                     'icon'=>'ri-alert-line',           'page'=>'incidents', 'perm'=>'incidents.view',
    'submenu'=>[
      ['label'=>'All Incidents',    'url'=>url('admin/incidents'),        'icon'=>'ri-list-check-2'],
      ['label'=>'Submit Incident',  'url'=>url('admin/incidents/create'), 'icon'=>'ri-add-circle-line'],
    ]
  ],
  ['label'=>'Sites',    'url'=>url('admin/sites'),       'icon'=>'ri-building-2-line',       'page'=>'sites',    'perm'=>'sites.view'],
  ['label'=>'Shifts',   'url'=>url('admin/shifts'),      'icon'=>'ri-calendar-schedule-line','page'=>'shifts',   'perm'=>'shifts.view'],
  ['label'=>'Reports',  'url'=>'#',                      'icon'=>'ri-bar-chart-box-line',    'page'=>'reports',  'perm'=>'reports.view',
    'submenu'=>[
      ['label'=>'Overview',        'url'=>url('admin/reports'),         'icon'=>'ri-pie-chart-2-line'],
      ['label'=>'Report Builder',  'url'=>url('admin/reports/builder'), 'icon'=>'ri-file-chart-line'],
    ]
  ],
  ['label'=>'Admin',    'url'=>'#',                      'icon'=>'ri-team-line',             'page'=>'users',    'perm'=>'users.view',
    'submenu'=>[
      ['label'=>'Users',            'url'=>url('admin/users'),           'icon'=>'ri-group-line'],
      ['label'=>'Roles',            'url'=>url('admin/roles'),           'icon'=>'ri-shield-check-line'],
    ]
  ],
];
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
?>

<div class="gr-nav-overlay" id="grNavOverlay" onclick="closeMobileNav()"></div>

<nav class="gr-nav">
  <a class="gr-nav-logo" href="<?= url('admin/dashboard') ?>">
    <div class="logo-icon"><i class="ri-shield-check-fill"></i></div>
    <div>
      <div class="logo-text">GUARDREPORT</div>
      <div class="logo-sub">Incident Management</div>
    </div>
  </a>

  <div class="gr-nav-links" id="grNavLinks">
    <?php foreach ($navItems as $item):
      if (!empty($item['perm']) && !$isSuperAdmin && !hasPermission($userPermissions, $item['perm'])) continue;
      $isActive = (isset($currentPage) && $currentPage === $item['page']);
      $hasSub   = !empty($item['submenu']);
      if ($hasSub && !$isActive) {
        foreach ($item['submenu'] as $sub) {
          $sp = parse_url($sub['url'], PHP_URL_PATH) ?? '';
          if ($sp && str_contains($currentUrl, $sp)) { $isActive = true; break; }
        }
      }
    ?>
      <?php if ($hasSub): ?>
        <div class="gr-nav-item<?= $isActive ? ' open' : '' ?>" data-has-submenu>
          <button class="gr-nav-link<?= $isActive ? ' active' : '' ?>">
            <i class="<?= $item['icon'] ?>"></i>
            <?= htmlspecialchars($item['label']) ?>
            <i class="ri-arrow-down-s-line sub-caret"></i>
          </button>
          <div class="gr-submenu">
            <?php foreach ($item['submenu'] as $sub):
              $sp = parse_url($sub['url'], PHP_URL_PATH) ?? '';
              $sa = $sp && str_contains($currentUrl, $sp);
            ?>
              <a href="<?= $sub['url'] ?>" class="<?= $sa ? 'sub-active' : '' ?>">
                <i class="<?= $sub['icon'] ?>"></i><?= htmlspecialchars($sub['label']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= $item['url'] ?>" class="gr-nav-link<?= $isActive ? ' active' : '' ?>">
          <i class="<?= $item['icon'] ?>"></i><?= htmlspecialchars($item['label']) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="gr-nav-right">
    <button class="gr-nav-icon-btn" id="grNotifBtn" title="Notifications" onclick="grToggleNotif()">
      <i class="ri-notification-3-line"></i>
      <span class="gr-notif-badge" id="grNotifBadge" style="display:none"></span>
    </button>

    <div id="grAvatarWrap" style="position:relative;">
      <div class="gr-nav-avatar" id="grAvatarBtn" title="<?= $userFullName ?>">
        <?php if (!empty($userPhoto)): ?>
          <img src="<?= upload_url($userPhoto) ?>" alt="<?= $userFullName ?>">
        <?php else: ?><?= $userInitials ?><?php endif; ?>
      </div>
      <div class="gr-avatar-dropdown" id="grAvatarMenu">
        <div class="dd-header">
          <div class="dd-name"><?= $userFullName ?></div>
          <div class="dd-email"><?= htmlspecialchars($currentUser->email ?? '') ?></div>
          <div class="dd-role"><i class="ri-shield-user-line"></i> <?= htmlspecialchars($currentUser->role_name ?? 'Guard') ?></div>
        </div>
        <a href="<?= url('admin/profile') ?>"><i class="ri-user-line"></i> My Profile</a>
        <?php if ($isSuperAdmin || hasPermission($userPermissions, 'settings.view')): ?>
          <a href="<?= url('admin/settings') ?>"><i class="ri-settings-4-line"></i> System Settings</a>
        <?php endif; ?>
        <div class="dd-divider"></div>
        <a href="#" class="danger" id="grLogoutBtn"><i class="ri-logout-box-r-line"></i> Sign Out</a>
      </div>
    </div>

    <button class="gr-hamburger" id="grHamburger" aria-label="Toggle navigation" aria-expanded="false">
      <i class="ri-menu-3-line"></i>
    </button>
  </div>
</nav>

<!-- Notification Panel -->
<div id="grNotifPanel">
  <div class="notif-panel-head">
    <span><i class="ri-notification-3-line"></i> Notifications</span>
    <div style="display:flex;gap:8px;align-items:center">
      <button onclick="grMarkAllRead()" style="background:none;border:none;cursor:pointer;color:var(--blue-light);font-size:12px;font-family:inherit;">Mark all read</button>
      <button onclick="_slideNotif(false)" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1rem;"><i class="ri-close-line"></i></button>
    </div>
  </div>
  <div id="grNotifList">
    <div class="gr-empty"><i class="ri-notification-off-line"></i><p>No new notifications</p></div>
  </div>
</div>

<main class="gr-main" id="grMain">

<script>
(function() {
  /* ── SUBMENU ─────────────────────────────────────────── */
  function closeAllSubmenus() {
    document.querySelectorAll('.gr-nav-item[data-has-submenu]').forEach(function(i){ i.classList.remove('open'); });
  }
  document.querySelectorAll('.gr-nav-item[data-has-submenu]').forEach(function(item) {
    var btn = item.querySelector('button.gr-nav-link');
    if (!btn) return;
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      var wasOpen = item.classList.contains('open');
      closeAllSubmenus();
      if (!wasOpen) item.classList.add('open');
    });
  });
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.gr-nav-item[data-has-submenu]') && window.innerWidth >= 1025) closeAllSubmenus();
  });

  /* ── MOBILE NAV ──────────────────────────────────────── */
  var _mobOpen = false;
  var hamburger = document.getElementById('grHamburger');
  var navLinks  = document.getElementById('grNavLinks');
  var overlay   = document.getElementById('grNavOverlay');
  function openMobileNav()  { _mobOpen=true;  navLinks.classList.add('mobile-open');    overlay.classList.add('visible');    hamburger.setAttribute('aria-expanded','true');  hamburger.querySelector('i').className='ri-close-line'; document.body.style.overflow='hidden'; }
  function closeMobileNav() { _mobOpen=false; navLinks.classList.remove('mobile-open'); overlay.classList.remove('visible'); hamburger.setAttribute('aria-expanded','false'); hamburger.querySelector('i').className='ri-menu-3-line'; document.body.style.overflow=''; }
  window.closeMobileNav = closeMobileNav;
  hamburger.addEventListener('click', function() { _mobOpen ? closeMobileNav() : openMobileNav(); });

  /* ── AVATAR DROPDOWN ─────────────────────────────────── */
  var avatarBtn  = document.getElementById('grAvatarBtn');
  var avatarWrap = document.getElementById('grAvatarWrap');
  var avatarMenu = document.getElementById('grAvatarMenu');
  var _avOpen = false;
  function openAvatar()  { _avOpen=true;  avatarMenu.classList.add('open'); }
  function closeAvatar() { _avOpen=false; avatarMenu.classList.remove('open'); }
  avatarBtn.addEventListener('click', function(e) { e.stopPropagation(); _avOpen ? closeAvatar() : openAvatar(); });
  document.addEventListener('click', function(e) { if (_avOpen && !avatarWrap.contains(e.target)) closeAvatar(); });

  /* ── NOTIFICATIONS ───────────────────────────────────── */
  var notifPanel = document.getElementById('grNotifPanel');
  var notifBtn   = document.getElementById('grNotifBtn');
  var _notifOpen = false;
  function _slideNotif(open) { _notifOpen=open; notifPanel.style.transform = open ? 'translateX(0)' : 'translateX(100%)'; }
  window._slideNotif = _slideNotif;
  function grToggleNotif() { _slideNotif(!_notifOpen); if (_notifOpen) closeAvatar(); }
  window.grToggleNotif = grToggleNotif;
  document.addEventListener('click', function(e) { if (_notifOpen && !notifBtn.contains(e.target) && !notifPanel.contains(e.target)) _slideNotif(false); });

  function grLoadNotifications() {
    fetch(window.BASE_URL+'/api/users?action=notifications', {credentials:'include'})
      .then(function(r){ return r.json(); })
      .then(function(d) {
        if (!d.success || !d.data || !d.data.length) return;
        var badge = document.getElementById('grNotifBadge');
        var list  = document.getElementById('grNotifList');
        var unread = d.data.filter(function(n){ return !n.is_read; }).length;
        if (unread > 0) badge.style.display = 'block'; else badge.style.display = 'none';
        list.innerHTML = d.data.map(function(n) {
          return '<div class="notif-item'+(n.is_read?'':' unread')+'" onclick="grMarkRead('+n.id+')">'
            +'<div class="ni-title">'+n.title+'</div>'
            +'<div class="ni-msg">'+n.message+'</div>'
            +'<div class="ni-time">'+n.time_ago+'</div>'
            +'</div>';
        }).join('');
      }).catch(function(){});
  }
  window.grMarkRead = function(id) {
    fetch(window.BASE_URL+'/api/users?action=mark-notification-read', {method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id})})
      .then(function(){ grLoadNotifications(); }).catch(function(){});
  };
  window.grMarkAllRead = function() {
    fetch(window.BASE_URL+'/api/users?action=mark-all-read', {method:'POST', credentials:'include'})
      .then(function(){ grLoadNotifications(); }).catch(function(){});
  };
  grLoadNotifications();
  setInterval(grLoadNotifications, 60000);

  /* ── LOGOUT ──────────────────────────────────────────── */
  document.getElementById('grLogoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({ icon:'question', title:'Sign Out?', text:'You will be signed out of GuardReport.',
      showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b',
      confirmButtonText:'Yes, sign out', cancelButtonText:'Cancel'
    }).then(function(r) {
      if (r.isConfirmed)
        fetch(window.BASE_URL+'/api/auth?action=logout', {method:'POST', credentials:'include'})
          .finally(function(){ window.location.href = window.BASE_URL+'/logout'; });
    });
  });

  /* ── HEARTBEAT ───────────────────────────────────────── */
  setInterval(function() {
    fetch(window.BASE_URL+'/api/auth?action=heartbeat', {method:'POST', credentials:'include'}).catch(function(){});
  }, 10*60*1000);

  /* ── GLOBAL SWAL HELPERS ─────────────────────────────── */
  window.grSuccess = function(t,m,cb) { Swal.fire({icon:'success',title:t,text:m,confirmButtonColor:'#0F2744'}).then(function(r){ if(r.isConfirmed&&cb) cb(); }); };
  window.grError   = function(t,m)    { Swal.fire({icon:'error',  title:t||'Error',text:m,confirmButtonColor:'#dc2626'}); };
  window.grConfirm = function(t,m,cb) { Swal.fire({icon:'warning',title:t,text:m,showCancelButton:true,confirmButtonColor:'#dc2626',cancelButtonColor:'#64748b',confirmButtonText:'Confirm',cancelButtonText:'Cancel'}).then(function(r){ if(r.isConfirmed) cb(); }); };
  window.grLoading = function(t)      { Swal.fire({title:t||'Processing…',allowOutsideClick:false,didOpen:function(){ Swal.showLoading(); }}); };

  /* ── DATA-FILL BARS ──────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-fill]').forEach(function(el) {
      var t = el.getAttribute('data-fill'); el.style.width='0';
      requestAnimationFrame(function(){ el.style.transition='width 0.9s cubic-bezier(.4,0,.2,1)'; el.style.width=t; });
    });
  });
})();
</script>
