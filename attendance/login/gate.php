<?php

/**
 * attendance/login/gate.php
 *
 * Attendance Module Login Gate.
 * Renders a standalone login form for users who are not yet
 * authenticated or whose session has expired.
 *
 * Typically reached via a redirect from shared/auth.php when
 * requireRole() fails. The requested URL is passed as ?return=
 * and forwarded through the form so check.php can redirect back.
 *
 * @package EduSync
 * @author  Laxman Giri
 */
session_start();

// Skip the login form entirely if a valid session already exists
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Read-once error: display it then clear so it doesn't replay on refresh
$error  = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// Sanitise return URL — only relative paths allowed to prevent open redirect
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
<link rel="stylesheet" href="../style.css">
<style>
  body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .login-wrap { width: 100%; max-width: 360px; padding: 16px; }
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
  <div class="login-logo">ES</div>
  <div class="login-title">EduSync</div>
  <div class="login-sub">Sign in to access the Attendance module</div>

  <?php if ($error): ?>
    <!-- Error message from check.php (bad credentials, lockout, etc.) -->
    <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" action="check.php">
      <!-- Pass the return URL through the form so check.php can redirect back after login -->
      <input type="hidden" name="return" value="<?= htmlspecialchars($return) ?>">

      <div class="form-group">
        <label class="form-label">Username <span class="req">*</span></label>
        <input class="form-input" type="text" name="username" autocomplete="username"
          placeholder="Enter your username" autofocus required>
      </div>

      <div class="form-group" style="margin-bottom:20px;">
        <label class="form-label">Password <span class="req">*</span></label>
        <input class="form-input" type="password" name="password" autocomplete="current-password"
          placeholder="Enter your password" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
    </form>
  </div>

  <p style="text-align:center;font-size:.78rem;color:var(--text-muted);margin-top:16px;">
    Only Administrator, Headteacher and Teacher accounts can access Attendance.
  </p>
</div>
</body>
</html>
