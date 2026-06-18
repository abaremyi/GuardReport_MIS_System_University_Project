<?php
/** GuardReport — Reports | File: modules/Reports/views/reports.php */
$pageTitle        = 'Reports & Analytics';
$currentPage      = 'reports';
$requiredPermission = 'reports.view';
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 3) . '/modules/Sites/models/SiteModel.php';

$sm    = new SiteModel(Database::getConnection());
$sites = $sm->getAll(['status'=>'active']);

require_once get_layout('admin-head');
require_once get_layout('admin-nav');
?>

<div class="gr-page-header">
  <div>
    <h1>Reports &amp; Analytics</h1>
    <div class="gr-breadcrumb">Incident trends, site analysis, and guard activity</div>
  </div>
</div>

<!-- Filters -->
<div class="gr-card mb-6">
  <div class="gr-filter-bar">
    <select id="rpSite" class="gr-select">
      <option value="">All Sites</option>
      <?php foreach ($sites as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" id="rpFrom" class="gr-input" style="width:150px" value="<?= date('Y-m-d', strtotime('-12 months')) ?>">
    <span style="color:var(--text-muted);font-size:13px">to</span>
    <input type="date" id="rpTo"   class="gr-input" style="width:150px" value="<?= date('Y-m-d') ?>">
    <button class="gr-btn gr-btn-primary" onclick="loadAll()">
      <i class="ri-refresh-line"></i> Apply Filters
    </button>
  </div>
</div>

<!-- Summary Stats -->
<div class="gr-stats-grid" id="summaryStats">
  <?php foreach (['Total','Open','Resolved','Critical','Active Sites','Active Guards'] as $l): ?>
    <div class="gr-stat-card"><div class="stat-label"><?= $l ?></div><div class="stat-value"><span class="spinner"></span></div></div>
  <?php endforeach; ?>
</div>

<!-- Charts row 1 -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
  <div class="gr-card">
    <div class="gr-card-header">
      <div><div class="gr-card-title">Monthly Trend</div><div class="gr-card-subtitle">Incidents submitted per month</div></div>
    </div>
    <div class="gr-chart-box"><canvas id="trendChart"></canvas></div>
  </div>
  <div class="gr-card">
    <div class="gr-card-header">
      <div><div class="gr-card-title">By Severity</div><div class="gr-card-subtitle">Distribution</div></div>
    </div>
    <div class="gr-chart-box" style="display:flex;align-items:center;justify-content:center">
      <canvas id="sevChart" style="max-height:220px;max-width:220px"></canvas>
    </div>
  </div>
</div>

<!-- Charts row 2 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <div class="gr-card">
    <div class="gr-card-header">
      <div><div class="gr-card-title">By Incident Type</div></div>
    </div>
    <div class="gr-chart-box"><canvas id="typeChart"></canvas></div>
  </div>
  <div class="gr-card">
    <div class="gr-card-header">
      <div><div class="gr-card-title">By Site</div></div>
    </div>
    <div class="gr-chart-box"><canvas id="siteChart"></canvas></div>
  </div>
</div>

<!-- Guard Activity -->
<div class="gr-card">
  <div class="gr-card-header">
    <div><div class="gr-card-title">Guard Activity</div><div class="gr-card-subtitle">Submissions per guard in period</div></div>
  </div>
  <div class="gr-table-wrap">
    <table class="gr-table">
      <thead><tr><th>Guard</th><th>Submissions</th><th>Critical</th><th>Activity Bar</th></tr></thead>
      <tbody id="guardTable"><tr><td colspan="4" style="text-align:center;padding:24px"><span class="spinner"></span></td></tr></tbody>
    </table>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var _charts = {};

function params() {
  return new URLSearchParams({
    site_id:   document.getElementById('rpSite').value,
    date_from: document.getElementById('rpFrom').value,
    date_to:   document.getElementById('rpTo').value,
  }).toString();
}

function loadAll() {
  loadSummary(); loadTrend(); loadSeverity(); loadByType(); loadBySite(); loadGuards();
}

/* ── SUMMARY ─────────────────────────────── */
function loadSummary() {
  fetch(BASE_URL+'/api/reports?action=summary&'+params(), {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      var s = d.data;
      var defs = [
        {l:'Total Incidents',  v:s.total,         icon:'ri-file-list-3-line', col:'var(--navy)'},
        {l:'Open',             v:s.open_c,         icon:'ri-door-open-line',   col:'var(--blue)'},
        {l:'Resolved',         v:s.resolved_c,     icon:'ri-checkbox-circle-line',col:'var(--green)'},
        {l:'Critical',         v:s.critical_c,     icon:'ri-alarm-warning-line',col:'var(--red)'},
        {l:'Active Sites',     v:s.active_sites,   icon:'ri-building-2-line',  col:'var(--purple)'},
        {l:'Active Guards',    v:s.active_guards,  icon:'ri-shield-user-line', col:'var(--navy)'},
      ];
      var grid = document.getElementById('summaryStats');
      grid.innerHTML = defs.map(function(c) {
        return '<div class="gr-stat-card" style="--stat-color:'+c.col+'">'
          +'<div class="stat-label">'+c.l+'</div>'
          +'<div class="stat-value">'+c.v+'</div>'
          +'<i class="'+c.icon+' stat-icon"></i>'
          +'</div>';
      }).join('');
    });
}

/* ── TREND ───────────────────────────────── */
function loadTrend() {
  fetch(BASE_URL+'/api/reports?action=trend&'+params(), {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      if (_charts.trend) _charts.trend.destroy();
      var ctx = document.getElementById('trendChart').getContext('2d');
      _charts.trend = new Chart(ctx, {
        type:'line',
        data:{ labels:d.data.labels, datasets:[{
          label:'Incidents', data:d.data.values,
          borderColor:'#dc2626', backgroundColor:'rgba(220,38,38,.08)',
          tension:.4, fill:true, pointBackgroundColor:'#dc2626', pointRadius:4
        }]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
          scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{stepSize:1}}} }
      });
    });
}

/* ── SEVERITY ────────────────────────────── */
function loadSeverity() {
  fetch(BASE_URL+'/api/reports?action=by-severity&'+params(), {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      if (_charts.sev) _charts.sev.destroy();
      var ctx = document.getElementById('sevChart').getContext('2d');
      _charts.sev = new Chart(ctx, {
        type:'doughnut',
        data:{ labels:['Critical','High','Medium','Low'],
          datasets:[{data:d.data,backgroundColor:['#dc2626','#d97706','#1d4ed8','#16a34a'],borderWidth:0}]},
        options:{ responsive:true, maintainAspectRatio:true,
          plugins:{legend:{position:'bottom',labels:{padding:12,font:{size:11}}}} }
      });
    });
}

/* ── BY TYPE ─────────────────────────────── */
function loadByType() {
  fetch(BASE_URL+'/api/reports?action=by-type&'+params(), {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      if (_charts.type) _charts.type.destroy();
      var ctx = document.getElementById('typeChart').getContext('2d');
      _charts.type = new Chart(ctx, {
        type:'bar',
        data:{ labels:d.data.labels, datasets:[{
          label:'Incidents', data:d.data.values,
          backgroundColor:'rgba(29,78,216,.75)', borderRadius:6
        }]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
          scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{stepSize:1}}} }
      });
    });
}

/* ── BY SITE ─────────────────────────────── */
function loadBySite() {
  fetch(BASE_URL+'/api/reports?action=by-site&'+params(), {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      if (_charts.site) _charts.site.destroy();
      var ctx = document.getElementById('siteChart').getContext('2d');
      _charts.site = new Chart(ctx, {
        type:'bar',
        data:{ labels:d.data.labels, datasets:[{
          label:'Incidents', data:d.data.values,
          backgroundColor:'rgba(124,58,237,.7)', borderRadius:6
        }]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
          indexAxis:'y',
          scales:{x:{beginAtZero:true,grid:{display:false}},y:{grid:{display:false}}} }
      });
    });
}

/* ── GUARD ACTIVITY ──────────────────────── */
function loadGuards() {
  fetch(BASE_URL+'/api/reports?action=guard-activity&'+params(), {credentials:'include'})
    .then(function(r){ return r.json(); })
    .then(function(d) {
      var tbody = document.getElementById('guardTable');
      if (!d.success || !d.data.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted)">No data</td></tr>';
        return;
      }
      var max = Math.max.apply(null, d.data.map(function(r){ return parseInt(r.submissions)||0; }));
      tbody.innerHTML = d.data.map(function(r) {
        var pct = max>0 ? Math.round(r.submissions/max*100) : 0;
        return '<tr>'
          +'<td style="font-weight:600">'+r.name+'</td>'
          +'<td>'+r.submissions+'</td>'
          +'<td>'+(r.critical_count>0?'<span class="gr-badge gr-badge-critical">'+r.critical_count+'</span>':'—')+'</td>'
          +'<td><div style="height:8px;background:var(--bg);border-radius:4px;overflow:hidden">'
          +'<div style="height:100%;width:0;background:var(--blue);border-radius:4px;transition:width .9s" data-fill="'+pct+'%"></div>'
          +'</div></td>'
          +'</tr>';
      }).join('');
      // trigger fill animation
      document.querySelectorAll('[data-fill]').forEach(function(el){
        var t=el.getAttribute('data-fill'); el.style.width='0';
        requestAnimationFrame(function(){ el.style.transition='width 0.9s cubic-bezier(.4,0,.2,1)'; el.style.width=t; });
      });
    });
}

loadAll();
</script>
JS;
require_once get_layout('admin-scripts');