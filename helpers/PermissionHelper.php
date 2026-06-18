<?php
/** GuardReport — Permission Helper | File: helpers/PermissionHelper.php */
if (!function_exists('hasPermission')) {
    function hasPermission($perms, string $p): bool {
        global $currentUser, $isSuperAdmin;
        if (!empty($isSuperAdmin) || (!empty($currentUser) && !empty($currentUser->is_super_admin))) return true;
        return is_array($perms) && in_array($p, $perms);
    }
}
if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission($perms, array $check): bool {
        global $currentUser, $isSuperAdmin;
        if (!empty($isSuperAdmin) || (!empty($currentUser) && !empty($currentUser->is_super_admin))) return true;
        if (!is_array($perms)) return false;
        foreach ($check as $p) { if (in_array($p, $perms)) return true; }
        return false;
    }
}
if (!function_exists('hasAllPermissions')) {
    function hasAllPermissions($perms, array $check): bool {
        global $currentUser, $isSuperAdmin;
        if (!empty($isSuperAdmin) || (!empty($currentUser) && !empty($currentUser->is_super_admin))) return true;
        if (!is_array($perms)) return false;
        foreach ($check as $p) { if (!in_array($p, $perms)) return false; }
        return true;
    }
}
if (!function_exists('severityBadge')) {
    function severityBadge(string $s): string {
        $map = ['critical'=>['#7f1d1d','#fca5a5'], 'high'=>['#7c2d12','#fdba74'],
                'medium'=>['#78350f','#fde68a'], 'low'=>['#14532d','#86efac']];
        [$bg, $text] = $map[$s] ?? ['#1e3a5f','#93c5fd'];
        return "<span style='background:{$bg}22;color:{$bg};border:1px solid {$bg}44;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600'>".ucfirst($s)."</span>";
    }
}
if (!function_exists('statusBadge')) {
    function statusBadge(string $s): string {
        $map = ['open'=>'#1d4ed8', 'reviewing'=>'#d97706', 'resolved'=>'#16a34a', 'closed'=>'#64748b'];
        $c = $map[$s] ?? '#64748b';
        return "<span style='background:{$c}18;color:{$c};border:1px solid {$c}40;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600'>".ucfirst($s)."</span>";
    }
}