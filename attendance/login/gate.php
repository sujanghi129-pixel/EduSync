<?php

/**
 * attendance/login/gate.php
 *
 * WHAT THIS FILE DOES:
 *   Renders the standalone login form for the Attendance module.
 *   Reached when a user tries to access the attendance area without
 *   an active session (requireRole() in auth.php redirects them here).
 *
 * CHANGE FROM ORIGINAL:
 *   - The login input field was changed from:
 *       type="text"   name="username"   label="Username"
 *     to:
 *       type="email"  name="email"      label="Email"
 *     This matches the new email-based authentication in check.php.
 *
 * @package EduSync
 * @author  Laxman Giri
 */

session_start();

// Already logged in — go straight to the attendance index
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Read and clear any error message set by check.php on a failed login attempt.
// The error is stored in the session so it survives the redirect from check.php.
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);   // Clear immediately so it only shows once

// Read the ?return= parameter passed by auth.php's requireRole() redirect.
// This tells check.php where to send the user after a successful login.
// Sanitised to only allow relative paths — never external URLs.
$return = $_GET['return'] ?? '../index.php';
if (strpos($return, '://') !== false || strpos($return, '//') === 0) {
    $return = '../index.php';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Login — EduSync</title>
<!-- Attendance module stylesheet — defines card, form, callout components -->
<link rel="stylesheet" href="../style.css">
<style>
  /* Centre the login card vertically and horizontally on the page */
  body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }

  /* Cap the card width and provide internal padding on small screens */
  .login-wrap { width: 100%; max-width: 360px; padding: 16px; }

  /* EduSync "ES" logo square shown above the form */
  .login-logo {
    width: 44px; height: 44px; border-radius: 10px; background: var(--accent);
    color: #fff; display: grid; place-items: center; font-weight: 700;
    font-size: 1rem; margin: 0 auto 20px;
  }

  .login-title { font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 4px; }
  .login-sub   { font-size: .85rem; color: var(--text-muted); text-align: center; margin-bottom: 24px; }
</style>
</head>
<body>
<div class="login-wrap">

  <!-- Logo: "ES" monogram -->
  <div class="login-logo">ES</div>

  <div class="login-title">EduSync</div>
  <div class="login-sub">Sign in to access the Attendance module</div>

  <!-- ── ERROR CALLOUT ───────────────────────────────────────────────────────
       Only shown when check.php redirected back here with a login_error.
       htmlspecialchars() prevents XSS in the error text. -->
  <?php if ($error): ?>
    <div class="callout callout-danger" style="margin-bottom:16px;">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- ── LOGIN FORM ──────────────────────────────────────────────────────────
       POSTs to check.php which validates credentials and initiates 2FA. -->
  <div class="card">
    <form method="POST" action="check.php">

      <!-- Pass the return URL through to check.php so it can be stored in
           $_SESSION['2fa_return'] and used after the OTP is verified. -->
      <input type="hidden" name="return" value="<?= htmlspecialchars($return) ?>">

      <!-- EMAIL FIELD — CHANGED from username in the original
           type="email" enables the browser's email keyboard on mobile
           and shows the @ symbol in mobile keyboards. -->
      <div class="form-group">
        <label class="form-label">Email <span class="req">*</span></label>
        <input
          class="form-input"
          type="email"
          name="email"
          autocomplete="email"
          placeholder="Enter your email address"
          autofocus
          required
        >
      </div>

      <!-- PASSWORD FIELD — unchanged from original -->
      <div class="form-group" style="margin-bottom:20px;">
        <label class="form-label">Password <span class="req">*</span></label>
        <input
          class="form-input"
          type="password"
          name="password"
          autocomplete="current-password"
          placeholder="Enter your password"
          required
        >
      </div>

      <!-- Submit button — spans full card width -->
      <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
    </form>
  </div>

  <!-- Role restriction notice -->
  <p style="text-align:center;font-size:.78rem;color:var(--text-muted);margin-top:16px;">
    Only Administrator, Headteacher and Teacher accounts can access Attendance.
  </p>

</div>
</body>
</html>
