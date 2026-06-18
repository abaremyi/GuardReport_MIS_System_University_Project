<?php
/** GuardReport — Dashboard | File: modules/Authentication/views/dashboard.php */
$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';

// Fetch quick stats
require_once dirname(__DIR__, 1) . '/models/UserModel.php';
require_once dirname(__DIR__, 3) . '/modules/Incidents/models/IncidentModel.php';

$um      = new UserModel(Database::getConnection());
$im      = new IncidentModel(Database::getConnection());
$stats   = $im->getStats();
$uStats  = $um->getUserStats();

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>Dashboard</h1>
    <div class="gr-breadcrumb">Welcome back, <strong><?= $userFullName ?></strong></div>
  </div>
  <div class="gr-page-actions">
    <?php if (hasPermission($userPermissions, 'incidents.create') || $isSuperAdmin): ?>
      <a href="<?= url('admin/incidents/create') ?>" class="gr-btn gr-btn-danger">
        <i class="ri-add-circle-line"></i> Report Incident
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- ── INCIDENT STATS ──────────────────────── -->
<div class="gr-stats-grid">
  <?php
  $cards = [
    ['label'=>'Total Incidents',  'value'=>$stats['total'],            'icon'=>'ri-file-list-3-line', 'color'=>'var(--navy)'],
    ['label'=>'Open',             'value'=>$stats['open_count'],       'icon'=>'ri-door-open-line',   'color'=>'var(--blue)'],
    ['label'=>'Under Review',     'value'=>$stats['reviewing_count'],  'icon'=>'ri-search-eye-line',  'color'=>'var(--amber)'],
    ['label'=>'Resolved',         'value'=>$stats['resolved_count'],   'icon'=>'ri-checkbox-circle-line','color'=>'var(--green)'],
    ['label'=>'Critical',         'value'=>$stats['critical_count'],   'icon'=>'ri-alarm-warning-line','color'=>'var(--red)'],
    ['label'=>'High Severity',    'value'=>$stats['high_count'],       'icon'=>'ri-alert-line',       'color'=>'var(--amber)'],
    ['label'=>'Active Guards',    'value'=>$uStats['active'],          'icon'=>'ri-shield-user-line', 'color'=>'var(--navy)'],
    ['label'=>'Pending Activation','value'=>$uStats['pending'],        'icon'=>'ri-time-line',        'color'=>'var(--amber)'],
  ];
  foreach ($cards as $c): ?>
    <div class="gr-stat-card" style="--stat-color:<?= $c['color'] ?>">
      <div class="stat-label"><?= $c['label'] ?></div>
      <div class="stat-value"><?= number_format($c['value']) ?></div>
      <i class="<?= $c['icon'] ?> stat-icon"></i>
    </div>
  <?php endforeach; ?>
</div>

<!-- ── CHARTS ─────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
  <div class="gr-card">
    <div class="gr-card-header">
      <div>
        <div class="gr-card-title">Incident Trend</div>
        <div class="gr-card-subtitle">Monthly submissions — last 6 months</div>
      </div>
    </div>
    <div class="gr-chart-box"><canvas id="trendChart"></canvas></div>
  </div>
  <div class="gr-card">
    <div class="gr-card-header">
      <div>
        <div class="gr-card-title">By Severity</div>
        <div class="gr-card-subtitle">Distribution of open incidents</div>
      </div>
    </div>
    <div class="gr-chart-box" style="display:flex;align-items:center;justify-content:center">
      <canvas id="sevChart" style="max-width:200px;max-height:200px"></canvas>
    </div>
  </div>
</div>

<!-- ── RECENT INCIDENTS ───────────────────── -->
<div class="gr-card">
  <div class="gr-card-header">
    <div>
      <div class="gr-card-title">Recent Incidents</div>
      <div class="gr-card-subtitle">Latest 10 submissions</div>
    </div>
    <a href="<?= url('admin/incidents') ?>" class="gr-btn gr-btn-outline gr-btn-sm">
      View All <i class="ri-arrow-right-line"></i>
    </a>
  </div>
  <div class="gr-table-wrap">
    <table class="gr-table">
      <thead>
        <tr>
          <th>#</th><th>Title</th><th>Site</th><th>Type</th>
          <th>Severity</th><th>Status</th><th>Date</th><th></th>
        </tr>
      </thead>
      <tbody id="recentTbody">
        <tr><td colspan="8" class="gr-empty" style="padding:24px;text-align:center">
          <span class="spinner"></span> Loading…
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Recent incidents
  fetch(BASE_URL+'/api/incidents?action=list&per_page=10', {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      var tbody = document.getElementById('recentTbody');
      if (!d.success || !d.data.data.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted)">No incidents yet</td></tr>';
        return;
      }
      var sevClass = {low:'low',medium:'medium',high:'high',critical:'critical'};
      var stsClass = {open:'open',reviewing:'reviewing',resolved:'resolved',closed:'closed'};
      tbody.innerHTML = d.data.data.map(function(i) {
        return '<tr>'
          +'<td style="font-family:monospace;font-size:12px;color:var(--text-muted)">#'+i.id+'</td>'
          +'<td style="font-weight:600;max-width:180px" class="truncate" title="'+i.title+'">'+i.title+'</td>'
          +'<td style="font-size:13px">'+( i.site_name||'—')+'</td>'
          +'<td style="font-size:13px">'+( i.type_name||'—')+'</td>'
          +'<td><span class="gr-badge gr-badge-'+sevClass[i.severity]+'">'+i.severity+'</span></td>'
          +'<td><span class="gr-badge gr-badge-'+stsClass[i.status]+'">'+i.status+'</span></td>'
          +'<td style="font-size:12px;color:var(--text-muted)">'+i.incident_date.slice(0,10)+'</td>'
          +'<td><a href="'+BASE_URL+'/admin/incidents/view?id='+i.id+'" class="gr-btn gr-btn-outline gr-btn-sm">'
          +'<i class="ri-eye-line"></i></a></td>'
          +'</tr>';
      }).join('');
    });

  // Charts
  fetch(BASE_URL+'/api/reports?action=trend', {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      var ctx = document.getElementById('trendChart').getContext('2d');
      new Chart(ctx, {
        type:'line',
        data:{ labels:d.data.labels, datasets:[{
          label:'Incidents', data:d.data.values,
          borderColor:'#dc2626', backgroundColor:'rgba(220,38,38,.1)',
          tension:.4, fill:true, pointBackgroundColor:'#dc2626', pointRadius:4
        }]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
          scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'#f1f5f9'}}} }
      });
    });

  fetch(BASE_URL+'/api/reports?action=by-severity', {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      var ctx = document.getElementById('sevChart').getContext('2d');
      new Chart(ctx, {
        type:'doughnut',
        data:{ labels:['Critical','High','Medium','Low'],
          datasets:[{ data:d.data, backgroundColor:['#dc2626','#d97706','#3b82f6','#16a34a'], borderWidth:0 }]},
        options:{ responsive:true, maintainAspectRatio:true, plugins:{legend:{position:'bottom',labels:{padding:12,font:{size:12}}}} }
      });
    });
});
</script>
JS;
require_once get_layout('admin-scripts');