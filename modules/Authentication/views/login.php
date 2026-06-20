<?php
/** GuardReport — Login | File: modules/Authentication/views/login.php 
 * Description: Login and Forgot Password page for GuardReport. Provides a clean interface for users to sign in or reset their password. Utilizes JWT for authentication and includes client-side validation and feedback. The page is responsive and styled with the portal.css stylesheet. It also checks for existing authentication cookies to redirect already logged-in users to the dashboard. The forgot password flow includes email verification via OTP. Ensure that the corresponding API endpoints for authentication are properly implemented to handle the login and password reset requests securely.
 * functionality: User authentication (login/logout), password reset via email OTP, and session management using JWT. The page includes client-side validation and user feedback for a smooth user experience.
 * security: Implements secure authentication practices, including JWT for session management and secure handling of user credentials. The forgot password feature includes OTP verification to ensure that only the rightful owner can reset the password.
 * Note: Ensure that the server-side API endpoints for authentication are properly implemented to handle the login
*/
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';

// Redirect if already authenticated
if (!empty($_COOKIE['auth_token'])) {
    require_once dirname(__DIR__, 3) . '/helpers/JWTHandler.php';
    $jwt = new JWTHandler();
    if ($jwt->validateToken($_COOKIE['auth_token'])) {
        header('Location: ' . url('admin/dashboard')); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | GuardReport</title>
  <link rel="shortcut icon" href="<?= img_url('gr-icon.svg') ?>">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Sora:wght@700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/portal.css">
  <script>window.BASE_URL = "<?= BASE_URL ?>";</script>
</head>
<body>
<div class="gr-login-wrap">
  <div class="gr-login-box">

    <div class="gr-login-logo">
      <div class="logo-circle"><i class="ri-shield-check-fill"></i></div>
      <h1>GUARDREPORT</h1>
      <p>Security Incident Reporting System</p>
    </div>

    <!-- Tabs -->
    <div class="gr-login-tabs" id="loginTabs">
      <div class="gr-login-tab active" onclick="switchTab('login')">Sign In</div>
      <div class="gr-login-tab" onclick="switchTab('forgot')">Forgot Password</div>
    </div>

    <!-- Alert -->
    <div id="loginAlert" style="display:none" class="gr-alert gr-alert-danger">
      <i class="ri-error-warning-line"></i>
      <span id="loginAlertMsg"></span>
    </div>

    <!-- ── LOGIN FORM ───────────────────────── -->
    <div id="loginPanel">
      <div class="gr-form-group">
        <label for="identifier">Email / Phone / Username</label>
        <div class="gr-input-icon-wrap">
          <i class="ri-user-line icon"></i>
          <input id="identifier" type="text" class="gr-input" placeholder="Enter your email or phone"
            autocomplete="username" autofocus>
        </div>
      </div>
      <div class="gr-form-group">
        <label for="password">Password</label>
        <div class="gr-input-icon-wrap">
          <i class="ri-lock-line icon"></i>
          <input id="password" type="password" class="gr-input" placeholder="Enter your password"
            autocomplete="current-password"
            style="padding-right:38px">
          <button type="button" onclick="togglePwd()" id="pwdToggle"
            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:17px;">
            <i class="ri-eye-line" id="pwdIcon"></i>
          </button>
        </div>
      </div>
      <button class="gr-btn gr-btn-primary" style="width:100%;justify-content:center;padding:11px;" id="loginBtn" onclick="doLogin()">
        <i class="ri-login-box-line"></i> Sign In
      </button>
    </div>

    <div class="footer-credentials" style="margin-top:">
      <p>Demo Username: admin@guardreport.rw</p>
      <p>Demo Password: 12345</p>
    </div

    <!-- ── FORGOT PASSWORD ──────────────────── -->
    <div id="forgotPanel" style="display:none">
      <div id="fpStep1">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Enter your registered email to receive a reset code.</p>
        <div class="gr-form-group">
          <label>Email Address</label>
          <div class="gr-input-icon-wrap">
            <i class="ri-mail-line icon"></i>
            <input id="fpEmail" type="email" class="gr-input" placeholder="your@email.com">
          </div>
        </div>
        <button class="gr-btn gr-btn-primary" style="width:100%;justify-content:center;padding:11px;" onclick="doForgot()">
          <i class="ri-send-plane-line"></i> Send OTP
        </button>
      </div>
      <div id="fpStep2" style="display:none">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Enter the 6-digit code sent to your email.</p>
        <div class="gr-form-group">
          <label>OTP Code</label>
          <input id="fpOtp" type="text" class="gr-input" placeholder="000000" maxlength="6"
            style="text-align:center;font-size:24px;letter-spacing:12px;font-weight:700;">
        </div>
        <button class="gr-btn gr-btn-primary" style="width:100%;justify-content:center;padding:11px;" onclick="doVerifyOtp()">
          Verify Code
        </button>
      </div>
      <div id="fpStep3" style="display:none">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Choose a strong new password.</p>
        <div class="gr-form-group">
          <label>New Password</label>
          <input id="fpPwd1" type="password" class="gr-input" placeholder="Min 8 characters">
        </div>
        <div class="gr-form-group">
          <label>Confirm Password</label>
          <input id="fpPwd2" type="password" class="gr-input" placeholder="Repeat password">
        </div>
        <button class="gr-btn gr-btn-success" style="width:100%;justify-content:center;padding:11px;" onclick="doReset()">
          <i class="ri-lock-password-line"></i> Reset Password
        </button>
      </div>
    </div>

    <!-- OTP Verification (after register) -->
    <div id="otpPanel" style="display:none">
      <div class="gr-alert gr-alert-info" style="margin-bottom:16px">
        <i class="ri-information-line"></i>
        <span>A 6-digit code was sent to your email. Enter it below.</span>
      </div>
      <div class="gr-form-group">
        <label>Verification Code</label>
        <input id="otpCode" type="text" class="gr-input" placeholder="000000" maxlength="6"
          style="text-align:center;font-size:28px;letter-spacing:14px;font-weight:700;">
      </div>
      <button class="gr-btn gr-btn-primary" style="width:100%;justify-content:center;padding:11px;" onclick="doVerifyReg()">
        <i class="ri-check-line"></i> Verify Email
      </button>
      <button class="gr-btn gr-btn-outline" style="width:100%;justify-content:center;padding:11px;margin-top:8px;" onclick="doResend()">
        Resend Code
      </button>
    </div>

  </div><!-- /.gr-login-box -->
</div><!-- /.gr-login-wrap -->

<script>
var _uid = 0;
var _fpEmail = '';

function showAlert(msg, type='danger') {
  var a = document.getElementById('loginAlert');
  a.className = 'gr-alert gr-alert-'+type;
  a.querySelector('i').className = type==='danger' ? 'ri-error-warning-line' : 'ri-check-line';
  document.getElementById('loginAlertMsg').textContent = msg;
  a.style.display = 'flex';
}
function hideAlert() { document.getElementById('loginAlert').style.display = 'none'; }

function switchTab(tab) {
  hideAlert();
  document.querySelectorAll('.gr-login-tab').forEach(function(t,i){ t.classList.toggle('active', i===(tab==='login'?0:1)); });
  document.getElementById('loginPanel').style.display  = tab==='login' ? '' : 'none';
  document.getElementById('forgotPanel').style.display = tab==='forgot'? '' : 'none';
  document.getElementById('otpPanel').style.display    = 'none';
}

function togglePwd() {
  var inp = document.getElementById('password');
  var ic  = document.getElementById('pwdIcon');
  if (inp.type === 'password') { inp.type='text';     ic.className='ri-eye-off-line'; }
  else                          { inp.type='password'; ic.className='ri-eye-line'; }
}

function setBtn(id, loading, text) {
  var b = document.getElementById(id);
  if (!b) return;
  b.disabled = loading;
  if (loading) b.innerHTML = '<span class="spinner"></span> '+text;
}

/* ── LOGIN ─────────────────────────────────── */
function doLogin() {
  hideAlert();
  var id  = document.getElementById('identifier').value.trim();
  var pwd = document.getElementById('password').value;
  if (!id||!pwd) { showAlert('Please fill in both fields'); return; }
  setBtn('loginBtn', true, 'Signing in…');
  fetch(BASE_URL+'/api/auth?action=login', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({identifier:id, password:pwd})
  }).then(function(r){ return r.json(); }).then(function(d) {
    if (d.success) { window.location.href = d.redirect || BASE_URL+'/admin/dashboard'; }
    else { showAlert(d.message||'Login failed'); setBtn('loginBtn',false,''); document.getElementById('loginBtn').innerHTML='<i class="ri-login-box-line"></i> Sign In'; }
  }).catch(function(){ showAlert('Network error. Please try again.'); setBtn('loginBtn',false,''); document.getElementById('loginBtn').innerHTML='<i class="ri-login-box-line"></i> Sign In'; });
}

/* ── FORGOT PASSWORD ───────────────────────── */
function doForgot() {
  hideAlert();
  _fpEmail = document.getElementById('fpEmail').value.trim();
  if (!_fpEmail) { showAlert('Please enter your email'); return; }
  fetch(BASE_URL+'/api/auth?action=forgot-password', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({email:_fpEmail})
  }).then(function(r){ return r.json(); }).then(function(d) {
    showAlert(d.message, 'info');
    if (d.success) { document.getElementById('fpStep1').style.display='none'; document.getElementById('fpStep2').style.display=''; }
  });
}
function doVerifyOtp() {
  hideAlert();
  var otp = document.getElementById('fpOtp').value.trim();
  if (!otp) { showAlert('Enter the OTP code'); return; }
  fetch(BASE_URL+'/api/auth?action=verify-otp', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({email:_fpEmail, otp:otp})
  }).then(function(r){ return r.json(); }).then(function(d) {
    if (d.success) { document.getElementById('fpStep2').style.display='none'; document.getElementById('fpStep3').style.display=''; hideAlert(); }
    else showAlert(d.message);
  });
}
function doReset() {
  hideAlert();
  var p1 = document.getElementById('fpPwd1').value;
  var p2 = document.getElementById('fpPwd2').value;
  if (!p1||!p2) { showAlert('Both password fields are required'); return; }
  if (p1!==p2)  { showAlert('Passwords do not match'); return; }
  var otp = document.getElementById('fpOtp').value.trim();
  fetch(BASE_URL+'/api/auth?action=reset-password', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({email:_fpEmail, otp:otp, password:p1, confirm_password:p2})
  }).then(function(r){ return r.json(); }).then(function(d) {
    if (d.success) { showAlert('Password reset! You can now sign in.','success'); setTimeout(function(){ switchTab('login'); }, 2000); }
    else showAlert(d.message);
  });
}

/* ── OTP VERIFICATION (post-register) ─────── */
function doVerifyReg() {
  var otp = document.getElementById('otpCode').value.trim();
  if (!otp||!_uid) { showAlert('Enter the OTP code'); return; }
  fetch(BASE_URL+'/api/auth?action=verify-registration-otp', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:_uid, otp:otp})
  }).then(function(r){ return r.json(); }).then(function(d) {
    if (d.success) { showAlert(d.message,'success'); setTimeout(function(){ switchTab('login'); },2500); }
    else showAlert(d.message);
  });
}
function doResend() {
  if (!_uid) return;
  fetch(BASE_URL+'/api/auth?action=resend-otp', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:_uid})
  }).then(function(r){ return r.json(); }).then(function(d){ showAlert(d.message, d.success?'info':'danger'); });
}

/* Enter key submits login */
document.addEventListener('keydown', function(e) {
  if (e.key==='Enter') {
    var lp = document.getElementById('loginPanel');
    if (lp && lp.style.display!=='none') doLogin();
  }
});
</script>
</body>
</html>