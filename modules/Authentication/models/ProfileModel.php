<?php
/**
 * GuardReport — Profile Model
 * File: modules/Authentication/models/ProfileModel.php
 *
 * Adapted from the SIPIS reference version for guardreport_db:
 *   - Dropped cover_photo (column does not exist on `users` in this schema)
 *   - getActivityLog() now reads the real `activity_log` table
 *     (module-tagged, shared with Incidents/Shifts) instead of a
 *     non-existent `user_activity_log` table
 *   - Settings/notifications use `user_settings` / `user_notification_settings`
 *     — see migrations/2026_06_19_profile_settings.sql
 *   - No `user_sessions` table exists, so active_sessions stays 0
 */
class ProfileModel {
    private $db;
    private $table = 'users';

    public function __construct($db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    public function getProfile($userId) {
        $sql = "
            SELECT u.*,
                   r.name          AS role_name,
                   u.is_super_admin AS is_super_admin,
                   CONCAT(u.firstname, ' ', u.lastname) AS full_name,
                   0 AS active_sessions
            FROM {$this->table} u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUserForPasswordCheck($userId) {
        $stmt = $this->db->prepare(
            "SELECT id, password FROM {$this->table} WHERE id = :id"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateProfile($userId, $data) {
        $allowed = [
            'firstname', 'lastname', 'username', 'email',
            'phone', 'bio', 'photo',
            'date_of_birth', 'gender', 'address',
        ];

        $sets   = [];
        $params = [':id' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]           = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($sets)) return false;

        $sql  = "UPDATE {$this->table} SET "
              . implode(', ', $sets)
              . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword($userId, $hashedPassword) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET password = :password, updated_at = NOW()
             WHERE id = :id"
        );
        return $stmt->execute([':id' => $userId, ':password' => $hashedPassword]);
    }

    /* ── Settings (user_settings) ─────────────────────────────────── */

    public function getUserSettings($userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_settings WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $settings = $this->createDefaultSettings($userId);
        }

        return $settings;
    }

    public function createDefaultSettings($userId) {
        $this->db->prepare(
            "INSERT IGNORE INTO user_settings
                (user_id, language, timezone, date_format, created_at)
             VALUES (:uid, 'en', 'Africa/Kigali', 'Y-m-d', NOW())"
        )->execute([':uid' => $userId]);

        return [
            'user_id'     => $userId,
            'language'    => 'en',
            'timezone'    => 'Africa/Kigali',
            'date_format' => 'Y-m-d',
        ];
    }

    public function updateSettings($userId, $data) {
        $allowed = ['language', 'timezone', 'date_format'];
        $sets    = [];
        $params  = [':uid' => $userId];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $sets[]          = "{$key} = :{$key}";
                $params[":{$key}"] = $data[$key];
            }
        }

        if (empty($sets)) return false;

        // Ensure a row exists, then update (avoids a separate exists-check round trip failing silently)
        $this->createDefaultSettings($userId);

        $sql  = "UPDATE user_settings SET "
              . implode(', ', $sets)
              . ", updated_at = NOW() WHERE user_id = :uid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /* ── Notification settings (user_notification_settings) ───────── */

    public function getNotificationSettings($userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_notification_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;

        return [
            'user_id' => $userId,
            'email_login' => 1, 'email_incident_updates' => 1,
            'email_shift_reminders' => 1, 'push_new_incidents' => 0,
        ];
    }

    public function updateNotificationSettings($userId, $settings) {
        $stmt = $this->db->prepare(
            "SELECT user_id FROM user_notification_settings WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $sql = "UPDATE user_notification_settings
                    SET email_login             = :email_login,
                        email_incident_updates   = :email_incident_updates,
                        email_shift_reminders     = :email_shift_reminders,
                        push_new_incidents        = :push_new_incidents,
                        updated_at                = NOW()
                    WHERE user_id = :user_id";
        } else {
            $sql = "INSERT INTO user_notification_settings
                        (user_id, email_login, email_incident_updates,
                         email_shift_reminders, push_new_incidents)
                    VALUES
                        (:user_id, :email_login, :email_incident_updates,
                         :email_shift_reminders, :push_new_incidents)";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array_merge([':user_id' => $userId], $settings));
    }

    /* ── Activity log (shared `activity_log` table) ───────────────── */

    public function getActivityLog($userId, $limit = 50) {
        $sql  = "SELECT id, action, module, description, ip_address, created_at
                 FROM activity_log
                 WHERE user_id = :uid
                 ORDER BY created_at DESC
                 LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,  \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ── Helpers ───────────────────────────────────────────────── */

    public function emailExists($email, $excludeId = null) {
        $sql    = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId) {
            $sql          .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
