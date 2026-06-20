<?php
/**
 * GuardReport — Settings Model
 * File: modules/Settings/models/SettingsModel.php
 *
 * Backed by the existing `system_settings` (key/value/type/label) table.
 * SETTINGS_SCHEMA below is the single source of truth for which keys
 * exist, their type, default, group, and display label — both this
 * model and the settings.php view read from it.
 */
class SettingsModel {
    private PDO $db;

    public const SETTINGS_SCHEMA = [
        // General
        'app_name'         => ['type'=>'string',  'group'=>'general', 'label'=>'Application Name',        'default'=>'GuardReport'],
        'company_name'     => ['type'=>'string',  'group'=>'general', 'label'=>'Company / Agency Name',   'default'=>''],
        'app_timezone'      => ['type'=>'string',  'group'=>'general', 'label'=>'Default Timezone',        'default'=>'Africa/Kigali'],
        'date_format'       => ['type'=>'string',  'group'=>'general', 'label'=>'Default Date Format',     'default'=>'Y-m-d'],

        // Incidents
        'incident_auto_close_days' => ['type'=>'integer', 'group'=>'incidents', 'label'=>'Auto-close resolved incidents after (days)', 'default'=>30],
        'default_incident_severity'=> ['type'=>'string',  'group'=>'incidents', 'label'=>'Default Severity for New Incidents',          'default'=>'medium'],

        // Uploads & files
        'max_upload_size_mb'  => ['type'=>'integer', 'group'=>'uploads', 'label'=>'Max Evidence File Size (MB)', 'default'=>10],
        'allowed_file_types'  => ['type'=>'json',    'group'=>'uploads', 'label'=>'Allowed File Extensions',     'default'=>['jpg','jpeg','png','pdf','mp4']],

        // Security
        'session_timeout_minutes' => ['type'=>'integer', 'group'=>'security', 'label'=>'Session Timeout (minutes)',        'default'=>60],
        'password_min_length'     => ['type'=>'integer', 'group'=>'security', 'label'=>'Minimum Password Length',          'default'=>8],
        'force_password_change_days' => ['type'=>'integer', 'group'=>'security', 'label'=>'Force Password Change Every (days, 0 = never)', 'default'=>0],

        // Notifications (system-wide defaults / toggles)
        'notify_on_incident_submit' => ['type'=>'boolean', 'group'=>'notifications', 'label'=>'Notify supervisors when a new incident is submitted', 'default'=>true],
        'notify_on_shift_reminder'  => ['type'=>'boolean', 'group'=>'notifications', 'label'=>'Send shift reminder notifications',                    'default'=>true],

        // Reports
        'report_footer_text' => ['type'=>'string', 'group'=>'reports', 'label'=>'Report Footer / Confidentiality Note', 'default'=>'Confidential — for internal use only'],
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    public function getSchema(): array {
        return self::SETTINGS_SCHEMA;
    }

    /** Returns key => typed value, falling back to schema defaults for anything not yet in the DB. */
    public function getAll(): array {
        $rows = $this->db->query("SELECT `key`,`value`,`type` FROM system_settings")->fetchAll(PDO::FETCH_ASSOC);
        $stored = [];
        foreach ($rows as $r) $stored[$r['key']] = $this->cast($r['value'], $r['type']);

        $out = [];
        foreach (self::SETTINGS_SCHEMA as $key => $def) {
            $out[$key] = array_key_exists($key, $stored) ? $stored[$key] : $def['default'];
        }
        return $out;
    }

    public function updateMany(array $data, int $userId): int {
        $updated = 0;
        $this->db->beginTransaction();
        try {
            foreach (self::SETTINGS_SCHEMA as $key => $def) {
                if (!array_key_exists($key, $data)) continue;
                $value = $this->validateAndPrepare($key, $def, $data[$key]);
                $stmt = $this->db->prepare(
                    "INSERT INTO system_settings (`key`,`value`,`type`,`label`,`updated_at`)
                     VALUES (:k,:v,:t,:l,NOW())
                     ON DUPLICATE KEY UPDATE `value`=:v2, `updated_at`=NOW()"
                );
                $stmt->execute([
                    ':k'=>$key, ':v'=>$value, ':t'=>$def['type'], ':l'=>$def['label'], ':v2'=>$value,
                ]);
                $updated++;
            }
            if ($updated > 0) {
                $this->db->prepare(
                    "INSERT INTO activity_log (user_id,action,module,description,ip_address) VALUES (:u,'settings_update','settings',:d,:ip)"
                )->execute([
                    ':u'=>$userId,
                    ':d'=>"Updated $updated system setting(s)",
                    ':ip'=>$_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            }
            $this->db->commit();
            return $updated;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function cast($raw, string $type) {
        switch ($type) {
            case 'integer': return (int)$raw;
            case 'boolean': return in_array($raw, [1,'1',true,'true'], true);
            case 'json':    return json_decode($raw, true) ?? [];
            default:        return (string)$raw;
        }
    }

    private function validateAndPrepare(string $key, array $def, $value) {
        switch ($def['type']) {
            case 'integer':
                if (!is_numeric($value) || (int)$value < 0) throw new Exception("'{$def['label']}' must be a non-negative number");
                return (string)(int)$value;
            case 'boolean':
                return ($value === true || $value === 1 || $value === '1' || $value === 'true') ? '1' : '0';
            case 'json':
                if (is_string($value)) $value = array_filter(array_map('trim', explode(',', $value)));
                if (!is_array($value)) throw new Exception("'{$def['label']}' must be a list");
                return json_encode(array_values($value));
            default:
                return (string)$value;
        }
    }
}
