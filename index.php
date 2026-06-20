<?php
/**
 * GuardReport — Main Router
 * File: index.php
 */
require_once 'config/paths.php';

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path   = str_replace('/index.php', '', $script_name);

if (strpos($request_uri, $base_path) === 0)
    $request_uri = substr($request_uri, strlen($base_path));

$parsed = parse_url($request_uri);
$path   = isset($parsed['path'])  ? rtrim($parsed['path'], '/') : '';
$query  = isset($parsed['query']) ? $parsed['query'] : '';

$routes = [
    /* ── Auth ────────────────────────────────────────── */
    ''                              => 'modules/Authentication/views/login.php',
    '/'                             => 'modules/Authentication/views/login.php',
    '/login'                        => 'modules/Authentication/views/login.php',
    '/logout'                       => 'modules/Authentication/views/logout.php',
    '/forgot-password'              => 'modules/Authentication/views/login.php',

    /* ── Auth API ─────────────────────────────────────── */
    '/api/auth'                     => 'modules/Authentication/api/authApi.php',
    '/api/users'                    => 'modules/Authentication/api/userApi.php',
    '/api/roles'                    => 'modules/Authentication/api/roleApi.php',
    '/api/profile'                  => 'modules/Authentication/api/profileApi.php',
    '/api/settings'                 => 'modules/Settings/api/settingsApi.php',

    /* ── Admin: Core ──────────────────────────────────── */
    '/admin/dashboard'              => 'modules/Authentication/views/dashboard.php',
    '/admin/users'                  => 'modules/Authentication/views/users-management.php',
    '/admin/users/add'              => 'modules/Authentication/views/users-add.php',
    '/admin/users/view'             => 'modules/Authentication/views/users-view.php',
    '/admin/roles'                  => 'modules/Authentication/views/roles-permissions-management.php',
    '/admin/profile'                => 'modules/Authentication/views/profile.php',
    '/admin/settings'               => 'modules/Settings/views/settings.php',

    /* ── Incidents ────────────────────────────────────── */
    '/admin/incidents'              => 'modules/Incidents/views/incidents.php',
    '/admin/incidents/create'       => 'modules/Incidents/views/incident-create.php',
    '/admin/incidents/view'         => 'modules/Incidents/views/incident-view.php',
    '/api/incidents'                => 'modules/Incidents/api/incidentApi.php',
    '/api/incidents/evidence'       => 'modules/Incidents/api/evidenceApi.php',

    /* ── Sites ────────────────────────────────────────── */
    '/admin/sites'                  => 'modules/Sites/views/sites.php',
    '/admin/sites/create'           => 'modules/Sites/views/site-create.php',
    '/admin/sites/view'             => 'modules/Sites/views/site-view.php',
    '/api/sites'                    => 'modules/Sites/api/siteApi.php',

    /* ── Shifts ───────────────────────────────────────── */
    '/admin/shifts'                 => 'modules/Shifts/views/shifts.php',
    '/api/shifts'                   => 'modules/Shifts/api/shiftApi.php',

    /* ── Reports ──────────────────────────────────────── */
    '/admin/reports'                => 'modules/Reports/views/reports.php',
    '/admin/reports/builder'         => 'modules/Reports/views/report-builder.php',
    '/api/reports'                  => 'modules/Reports/api/reportApi.php',
];

if (array_key_exists($path, $routes)) {
    $file = ROOT_PATH . '/' . $routes[$path];
    if (file_exists($file)) {
        if (!empty($query)) parse_str($query, $_GET);
        require_once $file;
    } else {
        http_response_code(404);
        require_once ROOT_PATH . '/modules/Authentication/views/404.php';
    }
} else {
    http_response_code(404);
    if (file_exists(ROOT_PATH . '/modules/Authentication/views/404.php')) {
        require_once ROOT_PATH . '/modules/Authentication/views/404.php';
    } else {
        echo '<!DOCTYPE html><html><head><title>404 — GuardReport</title>
        <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f4f8}
        .box{text-align:center}.box h1{font-size:5rem;font-weight:900;color:#0F2744;margin:0}
        .box p{color:#64748b}.box a{background:#0F2744;color:#fff;padding:.75rem 1.75rem;border-radius:99px;text-decoration:none;font-weight:700}</style></head>
        <body><div class="box"><h1>404</h1><p>Page not found: <code>' . htmlspecialchars($path) . '</code></p>
        <a href="' . (defined('BASE_URL') ? BASE_URL : '/') . '/admin/dashboard">Dashboard</a></div></body></html>';
    }
}