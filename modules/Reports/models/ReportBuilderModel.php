<?php
/**
 * GuardReport — Report Builder Model
 * File: modules/Reports/models/ReportBuilderModel.php
 *
 * Backs the customizable Report Builder. Each report TYPE has a field
 * catalog (for the field-picker UI) and a query that returns full rows;
 * the caller (controller/view) is responsible for projecting only the
 * fields the user selected onto the screen/PDF — the model always
 * returns the complete row so switching selected fields never needs
 * a fresh request.
 *
 * NOTE: every SQL placeholder gets its OWN unique name, even when bound
 * to the same value (e.g. :df1 / :df2 / :df3 for the same date_from).
 * This codebase's PDO connection does not emulate prepares, so MariaDB
 * rejects a repeated named parameter within one statement.
 */
class ReportBuilderModel {
    private PDO $db;

    public const FIELD_CATALOG = [
        'incidents' => [
            'label' => 'Incidents',
            'date_field_label' => 'Incident date range',
            'fields' => [
                'id'             => 'ID',
                'title'          => 'Title',
                'type_name'      => 'Type',
                'site_name'      => 'Site',
                'reporter_name'  => 'Reported By',
                'severity'       => 'Severity',
                'status'         => 'Status',
                'incident_date'  => 'Incident Date',
                'location_note'  => 'Location Note',
                'description'    => 'Description',
                'evidence_count' => 'Evidence Count',
                'created_at'     => 'Submitted At',
            ],
            'defaults' => ['id','title','type_name','site_name','reporter_name','severity','status','incident_date'],
        ],
        'shifts' => [
            'label' => 'Shifts',
            'date_field_label' => 'Shift start date range',
            'fields' => [
                'id'               => 'ID',
                'guard_name'       => 'Guard',
                'site_name'        => 'Site',
                'start_time'       => 'Start',
                'end_time'         => 'End',
                'duration_hours'   => 'Duration (h)',
                'status'           => 'Status',
                'notes'            => 'Notes',
                'created_by_name'  => 'Scheduled By',
            ],
            'defaults' => ['id','guard_name','site_name','start_time','end_time','duration_hours','status'],
        ],
        'guards' => [
            'label' => 'Guards / Personnel',
            'date_field_label' => 'Activity counted within',
            'fields' => [
                'id'                        => 'ID',
                'full_name'                 => 'Name',
                'email'                     => 'Email',
                'phone'                     => 'Phone',
                'role_name'                 => 'Role',
                'account_status'            => 'Account Status',
                'total_shifts'              => 'Shifts in Range',
                'total_incidents_reported'  => 'Incidents Reported',
                'critical_incidents_reported'=> 'Critical Incidents',
                'last_login'                => 'Last Login',
            ],
            'defaults' => ['id','full_name','role_name','account_status','total_shifts','total_incidents_reported'],
        ],
        'sites' => [
            'label' => 'Sites',
            'date_field_label' => 'Activity counted within',
            'fields' => [
                'id'                 => 'ID',
                'name'               => 'Site Name',
                'address'            => 'Address',
                'city'               => 'City',
                'client_name'        => 'Client',
                'client_phone'       => 'Client Phone',
                'status'             => 'Status',
                'total_incidents'    => 'Incidents in Range',
                'critical_incidents' => 'Critical Incidents',
                'total_shifts'       => 'Shifts in Range',
                'created_at'         => 'Onboarded',
            ],
            'defaults' => ['id','name','address','client_name','status','total_incidents','total_shifts'],
        ],
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    public static function getCatalog(): array { return self::FIELD_CATALOG; }

    public function getData(string $type, array $f): array {
        switch ($type) {
            case 'incidents': return $this->incidents($f);
            case 'shifts':    return $this->shifts($f);
            case 'guards':    return $this->guards($f);
            case 'sites':     return $this->sites($f);
            default: throw new Exception('Unknown report type');
        }
    }

    private function incidents(array $f): array {
        $where = ['i.incident_date BETWEEN :df1 AND :dt1'];
        $p = [':df1'=>$f['date_from'], ':dt1'=>$f['date_to'].' 23:59:59'];
        if (!empty($f['site_id']))  { $where[] = 'i.site_id=:sid';  $p[':sid']  = $f['site_id']; }
        if (!empty($f['severity'])) { $where[] = 'i.severity=:sev'; $p[':sev']  = $f['severity']; }
        if (!empty($f['status']))   { $where[] = 'i.status=:sts';  $p[':sts']  = $f['status']; }

        $sql = "SELECT i.id, i.title, i.description, i.severity, i.status, i.incident_date,
                       i.location_note, i.created_at,
                       it.name AS type_name, si.name AS site_name,
                       CONCAT(u.firstname,' ',u.lastname) AS reporter_name,
                       (SELECT COUNT(*) FROM incident_evidence e WHERE e.incident_id = i.id) AS evidence_count
                FROM incidents i
                LEFT JOIN incident_types it ON it.id = i.type_id
                LEFT JOIN sites si          ON si.id = i.site_id
                LEFT JOIN users u           ON u.id  = i.reported_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY i.incident_date DESC";
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    private function shifts(array $f): array {
        $where = ['sh.start_time BETWEEN :df1 AND :dt1'];
        $p = [':df1'=>$f['date_from'].' 00:00:00', ':dt1'=>$f['date_to'].' 23:59:59'];
        if (!empty($f['guard_id'])) { $where[] = 'sh.guard_id=:gid'; $p[':gid'] = $f['guard_id']; }
        if (!empty($f['site_id']))  { $where[] = 'sh.site_id=:sid';  $p[':sid'] = $f['site_id']; }
        if (!empty($f['status']))   { $where[] = 'sh.status=:sts';  $p[':sts'] = $f['status']; }

        $sql = "SELECT sh.id, sh.start_time, sh.end_time, sh.status, sh.notes,
                       CONCAT(g.firstname,' ',g.lastname)  AS guard_name,
                       si.name AS site_name,
                       CONCAT(cb.firstname,' ',cb.lastname) AS created_by_name
                FROM shifts sh
                LEFT JOIN users g  ON g.id  = sh.guard_id
                LEFT JOIN sites si ON si.id = sh.site_id
                LEFT JOIN users cb ON cb.id = sh.created_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY sh.start_time DESC";
        $s = $this->db->prepare($sql);
        $s->execute($p);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['duration_hours'] = round((strtotime($r['end_time']) - strtotime($r['start_time'])) / 3600, 1);
        }
        return $rows;
    }

    private function guards(array $f): array {
        $where = ['1=1'];
        $p = [
            ':df1'=>$f['date_from'].' 00:00:00', ':dt1'=>$f['date_to'].' 23:59:59',
            ':df2'=>$f['date_from'].' 00:00:00', ':dt2'=>$f['date_to'].' 23:59:59',
            ':df3'=>$f['date_from'].' 00:00:00', ':dt3'=>$f['date_to'].' 23:59:59',
        ];
        if (!empty($f['role_id'])) { $where[] = 'u.role_id=:rid'; $p[':rid'] = $f['role_id']; }

        $sql = "SELECT u.id, CONCAT(u.firstname,' ',u.lastname) AS full_name, u.email, u.phone,
                       u.account_status, u.last_login, r.name AS role_name,
                       (SELECT COUNT(*) FROM shifts s WHERE s.guard_id = u.id AND s.start_time BETWEEN :df1 AND :dt1) AS total_shifts,
                       (SELECT COUNT(*) FROM incidents inc WHERE inc.reported_by = u.id AND inc.incident_date BETWEEN :df2 AND :dt2) AS total_incidents_reported,
                       (SELECT COUNT(*) FROM incidents inc WHERE inc.reported_by = u.id AND inc.severity = 'critical' AND inc.incident_date BETWEEN :df3 AND :dt3) AS critical_incidents_reported
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY full_name";
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sites(array $f): array {
        $where = ['1=1'];
        $p = [
            ':df1'=>$f['date_from'].' 00:00:00', ':dt1'=>$f['date_to'].' 23:59:59',
            ':df2'=>$f['date_from'].' 00:00:00', ':dt2'=>$f['date_to'].' 23:59:59',
            ':df3'=>$f['date_from'].' 00:00:00', ':dt3'=>$f['date_to'].' 23:59:59',
        ];
        if (!empty($f['status'])) { $where[] = 'si.status=:sts'; $p[':sts'] = $f['status']; }

        $sql = "SELECT si.id, si.name, si.address, si.city, si.client_name, si.client_phone, si.status, si.created_at,
                       (SELECT COUNT(*) FROM incidents inc WHERE inc.site_id = si.id AND inc.incident_date BETWEEN :df1 AND :dt1) AS total_incidents,
                       (SELECT COUNT(*) FROM incidents inc WHERE inc.site_id = si.id AND inc.severity = 'critical' AND inc.incident_date BETWEEN :df2 AND :dt2) AS critical_incidents,
                       (SELECT COUNT(*) FROM shifts s WHERE s.site_id = si.id AND s.start_time BETWEEN :df3 AND :dt3) AS total_shifts
                FROM sites si
                WHERE " . implode(' AND ', $where) . "
                ORDER BY si.name";
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
