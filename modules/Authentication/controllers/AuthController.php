<?php
/**
 * GuardReport — Auth Controller
 * File: modules/Authentication/controllers/AuthController.php
 *
 * CHANGE LOG:
 *  - resetPassword() previously accepted $otp but never validated it before
 *    changing the password — anyone who knew a user's email could reset it
 *    with no code at all. It now calls UserModel::consumeOtpAndResetPassword(),
 *    which validates + consumes the OTP and updates the password atomically.
 */
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/JWTHandler.php';
require_once dirname(__DIR__, 1) . '/models/UserModel.php';

use PHPMailer\PHPMailer\PHPMailer;

class AuthController {
    private PDO        $db;
    private UserModel  $um;
    private JWTHandler $jwt;

    public function __construct() {
        $this->db  = Database::getConnection();
        $this->um  = new UserModel($this->db);
        $this->jwt = new JWTHandler();
    }

    /* ── LOGIN ─────────────────────────────────────────── */
    public function login(string $id, string $pwd): array {
        $user = $this->um->getUserByEmailOrPhone($id);
        if (!$user) return ['success' => false, 'message' => 'Invalid credentials.'];

        if ($user['account_status'] === 'pending') {
            $emailVerified = empty($user['otp_code']);
            return $emailVerified
                ? ['success' => false, 'message' => 'Your email is verified. Waiting for administrator activation. You will be notified.']
                : ['success' => false, 'message' => 'Account pending. Please verify your email with the OTP sent to you.'];
        }
        if ($user['account_status'] !== 'active')
            return ['success' => false, 'message' => 'Account is ' . $user['account_status'] . '. Contact your administrator.'];

        if (!password_verify($pwd, $user['password']))
            return ['success' => false, 'message' => 'Invalid credentials.'];

        $this->um->updateLastLogin((int)$user['id']);
        $token = $this->jwt->generateToken($this->payload($user));
        return ['success' => true, 'token' => $token, 'user' => [
            'id'            => (int)$user['id'],
            'name'          => trim($user['firstname'] . ' ' . $user['lastname']),
            'email'         => $user['email'],
            'role'          => $user['role_display'] ?? $user['role_name'] ?? 'Guard',
            'is_super_admin'=> (bool)$user['is_super_admin'],
        ]];
    }

    /* ── REGISTER ──────────────────────────────────────── */
    public function register(array $d): array {
        foreach (['firstname', 'lastname', 'email', 'password'] as $f)
            if (empty($d[$f])) return ['success' => false, 'message' => "Field '$f' is required."];
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'message' => 'Invalid email address.'];
        if ($this->um->emailExists($d['email']))
            return ['success' => false, 'message' => 'Email already registered.'];
        if (!empty($d['phone']) && $this->um->phoneExists($d['phone']))
            return ['success' => false, 'message' => 'Phone already registered.'];
        if (strlen($d['password']) < 8)
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        if ($d['password'] !== ($d['confirm_password'] ?? ''))
            return ['success' => false, 'message' => 'Passwords do not match.'];

        $otp    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $uid = $this->um->createUser([
            'firstname'      => trim($d['firstname']),
            'lastname'       => trim($d['lastname']),
            'username'       => $d['username'] ?? null,
            'email'          => strtolower(trim($d['email'])),
            'phone'          => $d['phone'] ?? null,
            'password'       => $d['password'],
            'role_id'        => 4,
            'account_status' => 'pending',
            'otp_code'       => $otp,
            'otp_expiry'     => $expiry,
        ]);
        $this->mail($d['email'], $d['firstname'], APP_NAME . ' — Verify Your Email', $this->verifyTpl($d['firstname'], $otp));
        return ['success' => true, 'message' => 'Account created! Check your email for the 6-digit code.', 'user_id' => $uid];
    }

    /* ── VERIFY REGISTRATION OTP ───────────────────────── */
    public function verifyRegistrationOtp(int $uid, string $otp): array {
        $ok = $this->um->verifyOtpFromUser($uid, $otp);
        if (!$ok) return ['success' => false, 'message' => 'Invalid or expired code.'];
        $this->db->prepare("UPDATE users SET otp_code=NULL,otp_expiry=NULL WHERE id=:id")->execute([':id' => $uid]);
        return ['success' => true, 'message' => 'Email verified! Your account is pending administrator activation.'];
    }

    /* ── RESEND OTP ────────────────────────────────────── */
    public function resendOtp(int $uid): array {
        $user = $this->um->getUserById($uid);
        if (!$user) return ['success' => false, 'message' => 'User not found.'];
        if ($user['account_status'] === 'active') return ['success' => false, 'message' => 'Account already active.'];
        $otp    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $this->um->updateOtp($uid, $otp, $expiry);
        $this->mail($user['email'], $user['firstname'], APP_NAME . ' — New Verification Code', $this->verifyTpl($user['firstname'], $otp));
        return ['success' => true, 'message' => 'New code sent to ' . $user['email']];
    }

    /* ── FORGOT PASSWORD ───────────────────────────────── */
    public function forgotPassword(string $email): array {
        $user = $this->um->findByEmail($email);
        if ($user) {
            $otp    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $this->um->createPasswordReset($email, $otp, $expiry);
            $this->mail($email, $user['firstname'], APP_NAME . ' — Password Reset OTP', $this->otpTpl($user['firstname'], $otp));
        }
        // Always the same response whether or not the email is registered —
        // this is intentional, it stops attackers from using this endpoint to
        // discover which emails exist in the system. Don't change it.
        return ['success' => true, 'message' => 'If that email is registered, an OTP has been sent.'];
    }

    public function verifyOtp(string $email, string $otp): array {
        // Read-only check for the UI's "Verify Code" step. The OTP is not
        // consumed here — see resetPassword()/consumeOtpAndResetPassword().
        return $this->um->verifyOtp($email, $otp)
            ? ['success' => true,  'message' => 'OTP verified.']
            : ['success' => false, 'message' => 'Invalid or expired OTP.'];
    }

    public function resetPassword(string $email, string $otp, string $pwd, string $confirm): array {
        if (empty($otp))        return ['success' => false, 'message' => 'Reset code is required.'];
        if (strlen($pwd) < 8)  return ['success' => false, 'message' => 'Password must be ≥ 8 characters.'];
        if ($pwd !== $confirm) return ['success' => false, 'message' => 'Passwords do not match.'];

        $hashed = password_hash($pwd, PASSWORD_BCRYPT);
        $ok = $this->um->consumeOtpAndResetPassword($email, $otp, $hashed);

        return $ok
            ? ['success' => true,  'message' => 'Password reset. You may now log in.']
            : ['success' => false, 'message' => 'Invalid or expired reset code. Please request a new one.'];
    }

    public function logout(): array {
        setcookie('auth_token', '', time() - 3600, '/');
        return ['success' => true];
    }

    /* ── JWT Payload ───────────────────────────────────── */
    private function payload(array $u): array {
        return [
            'user_id'        => (int)$u['id'],
            'username'       => $u['username'] ?? $u['email'],
            'firstname'      => $u['firstname'],
            'lastname'       => $u['lastname'],
            'email'          => $u['email'],
            'phone'          => $u['phone']   ?? null,
            'role_id'        => (int)($u['role_id'] ?? 4),
            'role_name'      => $u['role_display'] ?? $u['role_name'] ?? 'Guard',
            'is_super_admin' => (bool)($u['is_super_admin'] ?? false),
            'permissions'    => $u['permissions'] ?? [],
            'photo'          => $u['photo']    ?? null,
            'account_status' => $u['account_status'],
            'iat'            => time(),
            'exp'            => time() + 2700,
        ];
    }

    /* ── Mailer ────────────────────────────────────────── */
    private function mail(string $to, string $name, string $subj, string $body): void {
        try {
            if (!SMTP_USER) { error_log("SMTP not configured — skipping email to $to"); return; }
            $m = new PHPMailer(true);
            $m->isSMTP();
            $m->Host       = SMTP_HOST;
            $m->SMTPAuth   = true;
            $m->Username   = SMTP_USER;
            $m->Password   = SMTP_PASS;
            $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $m->Port       = 465;
            $m->setFrom(SMTP_USER, SMTP_FROM_NAME);
            $m->addAddress($to, $name);
            $m->Subject  = $subj;
            $m->isHTML(true);
            $m->Body     = $body;
            $m->send();
        } catch (Exception $e) { error_log('Mail: ' . $e->getMessage()); }
    }

    /* ── Email Templates ───────────────────────────────── */
    private function verifyTpl(string $name, string $otp): string {
        return <<<H
<!DOCTYPE html><html><body style="margin:0;padding:20px;background:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
<div style="background:#0F2744;padding:26px 30px;text-align:center;">
  <div style="font-size:22px;font-weight:800;color:#fff;letter-spacing:2px;">GUARDREPORT</div>
  <div style="color:#93c5fd;font-size:12px;margin-top:4px;">Security Guard Incident Reporting System</div>
</div>
<div style="padding:30px;">
  <p style="color:#333;">Hello <strong>$name</strong>,</p>
  <p style="color:#555;line-height:1.6;">Use this code to verify your account. Expires in <strong>30 minutes</strong>.</p>
  <div style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:10px;padding:26px;text-align:center;margin:22px 0;">
    <div style="font-size:32px;font-weight:800;letter-spacing:14px;color:#0F2744;font-family:monospace;">$otp</div>
  </div>
  <p style="color:#888;font-size:12px;">After verifying, an administrator will activate your account before you can log in.</p>
</div>
<div style="background:#f8fafc;padding:14px;text-align:center;color:#aaa;font-size:11px;">&copy; GuardReport System</div>
</div></body></html>
H;
    }

    private function otpTpl(string $name, string $otp): string {
        return <<<H
<!DOCTYPE html><html><body style="margin:0;padding:20px;background:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
<div style="background:#0F2744;padding:26px 30px;text-align:center;">
  <div style="font-size:22px;font-weight:800;color:#fff;letter-spacing:2px;">GUARDREPORT</div>
  <div style="color:#fca5a5;font-size:12px;margin-top:4px;">Password Reset</div>
</div>
<div style="padding:30px;">
  <p style="color:#333;">Hello <strong>$name</strong>,</p>
  <p style="color:#555;line-height:1.6;">Your password reset OTP — expires in <strong>15 minutes</strong>.</p>
  <div style="background:#fff8e1;border:2px dashed #d97706;border-radius:10px;padding:26px;text-align:center;margin:22px 0;">
    <div style="font-size:42px;font-weight:800;letter-spacing:14px;color:#92400e;font-family:monospace;">$otp</div>
  </div>
  <p style="color:#888;font-size:12px;">If you did not request this, please ignore this email.</p>
</div>
<div style="background:#f8fafc;padding:14px;text-align:center;color:#aaa;font-size:11px;">&copy; GuardReport System</div>
</div></body></html>
H;
    }
}