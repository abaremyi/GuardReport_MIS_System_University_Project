<?php
/** GuardReport — User Model | File: modules/Authentication/models/UserModel.php 
 * Handles database interactions related to users, including authentication lookups, CRUD operations, and OTP management.
 * Note: For security, all password handling uses password_hash and password_verify with bcrypt. OTPs are stored with expiry and marked used after verification.
 * The getUserByEmailOrPhone and getUserById methods also fetch the user's role and permissions for easy access during authentication and authorization checks.
 * The createUser and updateUser methods log actions to the activity_log table for auditing purposes. The deleteUser method prevents deletion of super-admin accounts.
 * The getAllUsers method supports filtering by status, role, and search term across multiple fields
 *
 * SECURITY FIX (see consumeOtpAndResetPassword): the forgot-password flow's final
 * step previously changed the password without ever re-checking the OTP — anyone
 * who knew a user's email could reset their password with no code at all. The OTP
 * is now verified and consumed atomically at the moment the password is changed.
*/
class UserModel {
    private PDO    $db;
    private string $t = 'users';

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    /* ── Auth lookups ─────────────────────────────────── */
    public function getUserByEmailOrPhone(string $id): ?array {
        $sql = "SELECT u.*, r.name AS role_display,
                GROUP_CONCAT(DISTINCT p.`key` ORDER BY p.`key` SEPARATOR ',') AS perm_keys
                FROM {$this->t} u
                LEFT JOIN roles r         ON r.id = u.role_id
                LEFT JOIN role_permissions rp ON rp.role_id = r.id
                LEFT JOIN permissions p   ON p.id = rp.permission_id
                WHERE u.email=:id OR u.phone=:id OR u.username=:id
                GROUP BY u.id LIMIT 1";
        $s = $this->db->prepare($sql);
        $s->execute([':id' => $id]);
        $u = $s->fetch();
        if ($u) {
            $u['permissions']    = $u['perm_keys'] ? explode(',', $u['perm_keys']) : [];
            $u['is_super_admin'] = (bool)($u['is_super_admin'] ?? false);
        }
        return $u ?: null;
    }

    public function getUserById(int $id): ?array {
        $sql = "SELECT u.*, r.name AS role_display,
                GROUP_CONCAT(DISTINCT p.`key` ORDER BY p.`key` SEPARATOR ',') AS perm_keys
                FROM {$this->t} u
                LEFT JOIN roles r         ON r.id = u.role_id
                LEFT JOIN role_permissions rp ON rp.role_id = r.id
                LEFT JOIN permissions p   ON p.id = rp.permission_id
                WHERE u.id = :id GROUP BY u.id";
        $s = $this->db->prepare($sql);
        $s->execute([':id' => $id]);
        $u = $s->fetch();
        if ($u) {
            $u['permissions']    = $u['perm_keys'] ? explode(',', $u['perm_keys']) : [];
            $u['is_super_admin'] = (bool)($u['is_super_admin'] ?? false);
        }
        return $u ?: null;
    }

    /* ── Lists ────────────────────────────────────────── */
    public function getAllUsers(array $f = []): array {
        $sql = "SELECT u.*, r.name AS role_display
                FROM {$this->t} u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE 1=1";
        $p = [];
        if (!empty($f['status']))  { $sql .= " AND u.account_status=:status"; $p[':status'] = $f['status']; }
        if (!empty($f['role_id'])) { $sql .= " AND u.role_id=:rid";           $p[':rid']    = $f['role_id']; }
        if (!empty($f['search'])) {
            $sql .= " AND (u.firstname LIKE :s OR u.lastname LIKE :s OR u.email LIKE :s OR u.phone LIKE :s)";
            $p[':s'] = '%' . $f['search'] . '%';
        }
        $sql .= " ORDER BY u.created_at DESC";
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    public function getGuards(): array {
        $s = $this->db->prepare(
            "SELECT id, firstname, lastname, email, phone, account_status
             FROM {$this->t} WHERE role_id=4 AND account_status='active' ORDER BY firstname"
        );
        $s->execute();
        return $s->fetchAll();
    }

    /* ── CRUD ─────────────────────────────────────────── */
    public function createUser(array $d): int {
        $sql = "INSERT INTO {$this->t}
                (firstname,lastname,username,email,phone,phone_alt,password,
                 role_id,account_status,photo,bio,gender,date_of_birth,address,
                 created_by,otp_code,otp_expiry)
                VALUES
                (:fn,:ln,:un,:em,:ph,:pa,:pw,
                 :ri,:as,:photo,:bio,:gen,:dob,:addr,
                 :cb,:otp,:otpex)";
        $s = $this->db->prepare($sql);
        $s->execute([
            ':fn' => $d['firstname'],  ':ln' => $d['lastname'],
            ':un' => $d['username']    ?? null, ':em' => $d['email'],
            ':ph' => $d['phone']       ?? null, ':pa' => $d['phone_alt'] ?? null,
            ':pw' => password_hash($d['password'], PASSWORD_BCRYPT),
            ':ri' => $d['role_id']     ?? 4,
            ':as' => $d['account_status'] ?? 'pending',
            ':photo' => $d['photo']    ?? null, ':bio' => $d['bio'] ?? null,
            ':gen' => $d['gender']     ?? null, ':dob' => $d['date_of_birth'] ?? null,
            ':addr' => $d['address']   ?? null, ':cb'  => $d['created_by']   ?? null,
            ':otp' => $d['otp_code']   ?? null, ':otpex' => $d['otp_expiry'] ?? null,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->log($id, 'create', 'User account created');
        return $id;
    }

    public function updateUser(int $id, array $d): bool {
        $allowed = ['firstname','lastname','username','email','phone','phone_alt',
                    'role_id','account_status','photo','bio','gender','date_of_birth','address'];
        $sets = []; $p = [':id' => $id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=:$f"; $p[":$f"] = $d[$f]; }
        }
        if (!empty($d['password'])) {
            $sets[] = "password=:password";
            $p[':password'] = password_hash($d['password'], PASSWORD_BCRYPT);
        }
        if (empty($sets)) return false;
        $ok = $this->db->prepare(
            "UPDATE {$this->t} SET " . implode(',', $sets) . ",updated_at=NOW() WHERE id=:id"
        )->execute($p);
        if ($ok) $this->log($id, 'update', 'User account updated');
        return $ok;
    }

    public function deleteUser(int $id): bool {
        $u = $this->getUserById($id);
        if ($u && $u['is_super_admin']) throw new Exception('Cannot delete super-admin');
        $this->log($id, 'delete', 'User deleted');
        return $this->db->prepare("DELETE FROM {$this->t} WHERE id=:id")->execute([':id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool {
        $ok = $this->db->prepare(
            "UPDATE {$this->t} SET account_status=:s,updated_at=NOW() WHERE id=:id"
        )->execute([':s' => $status, ':id' => $id]);
        if ($ok) $this->log($id, 'status_change', "Status → $status");
        return $ok;
    }

    public function updateLastLogin(int $id): void {
        $this->db->prepare("UPDATE {$this->t} SET last_login=NOW() WHERE id=:id")->execute([':id' => $id]);
    }

    /* ── Checks ───────────────────────────────────────── */
    public function emailExists(string $email, ?int $exclude = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->t} WHERE email=:e";
        $p   = [':e' => $email];
        if ($exclude) { $sql .= " AND id!=:id"; $p[':id'] = $exclude; }
        $s = $this->db->prepare($sql); $s->execute($p);
        return (bool)$s->fetchColumn();
    }
    public function phoneExists(string $ph, ?int $exclude = null): bool {
        if (empty($ph)) return false;
        $sql = "SELECT COUNT(*) FROM {$this->t} WHERE phone=:p";
        $p   = [':p' => $ph];
        if ($exclude) { $sql .= " AND id!=:id"; $p[':id'] = $exclude; }
        $s = $this->db->prepare($sql); $s->execute($p);
        return (bool)$s->fetchColumn();
    }
    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare("SELECT * FROM {$this->t} WHERE email=:e LIMIT 1");
        $s->execute([':e' => $email]);
        return $s->fetch() ?: null;
    }

    /* ── OTP (registration) ────────────────────────────── */
    public function updateOtp(int $id, string $otp, string $expiry): void {
        $this->db->prepare("UPDATE {$this->t} SET otp_code=:o,otp_expiry=:e WHERE id=:id")
                 ->execute([':o' => $otp, ':e' => $expiry, ':id' => $id]);
    }
    public function verifyOtpFromUser(int $id, string $otp): bool {
        $s = $this->db->prepare("SELECT otp_code,otp_expiry FROM {$this->t} WHERE id=:id LIMIT 1");
        $s->execute([':id' => $id]);
        $r = $s->fetch();
        return $r && $r['otp_code'] === $otp && strtotime($r['otp_expiry']) > time();
    }

    /* ── Password reset (forgot-password flow) ─────────── */

    /** Used directly by admin-initiated password changes elsewhere — NOT by the forgot-password flow (see below). */
    public function updatePassword(string $email, string $pwd): bool {
        return $this->db->prepare(
            "UPDATE {$this->t} SET password=:p WHERE email=:e"
        )->execute([':p' => password_hash($pwd, PASSWORD_BCRYPT), ':e' => $email]);
    }

    public function createPasswordReset(string $email, string $otp, string $expiry): void {
        $this->db->prepare("DELETE FROM password_resets WHERE email=:e")->execute([':e' => $email]);
        $this->db->prepare("INSERT INTO password_resets (email,otp,expires_at) VALUES (:e,:o,:x)")
                 ->execute([':e' => $email, ':o' => $otp, ':x' => $expiry]);
    }

    /**
     * Read-only validity check — used by the "Verify Code" UI step so the
     * person gets immediate feedback. Does NOT consume the OTP, because the
     * actual password change (consumeOtpAndResetPassword, below) re-checks
     * and consumes it itself. This is what closes the bypass: the API no
     * longer trusts that a prior verify-otp call happened.
     */
    public function verifyOtp(string $email, string $otp): bool {
        $s = $this->db->prepare(
            "SELECT id FROM password_resets WHERE email=:e AND otp=:o AND expires_at>NOW() AND used=0 LIMIT 1"
        );
        $s->execute([':e' => $email, ':o' => $otp]);
        return (bool)$s->fetch();
    }

    /**
     * The only path that actually changes a password during the forgot-password
     * flow. Validates the OTP and marks it used in the same transaction as the
     * password update, so a request to reset-password can never succeed without
     * a currently-valid, unused, non-expired code for that exact email.
     */
    public function consumeOtpAndResetPassword(string $email, string $otp, string $newHashedPassword): bool {
        $s = $this->db->prepare(
            "SELECT id FROM password_resets WHERE email=:e AND otp=:o AND expires_at>NOW() AND used=0 LIMIT 1"
        );
        $s->execute([':e' => $email, ':o' => $otp]);
        $row = $s->fetch();
        if (!$row) return false;

        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE password_resets SET used=1 WHERE id=:id")
                     ->execute([':id' => $row['id']]);
            $this->db->prepare("UPDATE {$this->t} SET password=:p WHERE email=:e")
                     ->execute([':p' => $newHashedPassword, ':e' => $email]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ── Stats ────────────────────────────────────────── */
    public function getUserStats(): array {
        $r = $this->db->query(
            "SELECT COUNT(*) total,
             SUM(account_status='active')    active,
             SUM(account_status='pending')   pending,
             SUM(account_status='inactive')  inactive,
             SUM(account_status='suspended') suspended
             FROM {$this->t}"
        )->fetch();
        return $r ?? ['total'=>0,'active'=>0,'pending'=>0,'inactive'=>0,'suspended'=>0];
    }
    public function getAllRoles(): array {
        return $this->db->query("SELECT id,name,description FROM roles ORDER BY id")->fetchAll();
    }

    /* ── Audit log ────────────────────────────────────── */
    private function log(int $uid, string $action, string $desc): void {
        try {
            $this->db->prepare(
                "INSERT INTO activity_log (user_id,action,module,description,ip_address) VALUES (:u,:a,'users',:d,:ip)"
            )->execute([':u' => $uid, ':a' => $action, ':d' => $desc, ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Exception $e) { error_log('Log: ' . $e->getMessage()); }
    }
}