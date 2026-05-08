<?php

/**
 * index.php (root)
 *
 * Login page for the EduSync School Management System.
 *
 * Handles both GET (display form) and POST (process login) requests.
 * On successful authentication, stores the user in $_SESSION['user']
 * and redirects to the dashboard. On failure, displays an error message.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

session_start();
require_once __DIR__ . '/shared/db.php';


/**
 * Redirect to dashboard if already logged in.
 * Prevents logged-in users from seeing the login form again.
 */
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

$error = null;

/**
 * Process login form submission.
 * Uses the Staff middle layer class to retrieve and verify credentials.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {
        /** @var Staff $staffClass - Middle layer instance for authentication */
        $staffClass = new Staff(db());

        /**
         * Look up the staff member by username via the middle layer.
         * Only active accounts are returned by sp_GetStaffByUsername.
         *
         * @var array|false $user The matched staff row, or false if not found.
         */
        $user = $staffClass->getByUsername($username);

        /**
         * Verify the submitted password against the stored bcrypt hash
         * using the middle layer's verifyPassword method.
         * On success, store safe user fields in the session (never the password hash).
         */
        if ($user && $staffClass->verifyPassword($password, $user['passwordHash'])) {
            $_SESSION['user'] = [
                'staffId'  => $user['staffId'],
                'fullName' => $user['fullName'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ];
            header('Location: dashboard/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/shared/meta.php'; ?>
<title>EduSync — Login</title>
<link rel="stylesheet" href="shared/style.css">
<link rel="stylesheet" href="login.css">
</head>
<body>

<div class="login-wrap">
  <div class="login-card">

    <div class="login-logo">
      <div class="login-logo-icon">ES</div>
      <div class="login-logo-name">EduSync</div>
    </div>

    <div class="login-title">Welcome back</div>
    <div class="login-sub">Sign in to your school account</div>

    <?php if ($error): ?>
      <div class="callout callout-danger" style="margin-bottom:20px;">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input
          class="form-input"
          type="text"
          name="username"
          placeholder="e.g. sujan.ghimire"
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          autofocus
        >
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input
          class="form-input"
          type="password"
          name="password"
          placeholder="Enter your password"
        >
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
        Sign In
      </button>
    </form>

    <!-- Demo credentials for testing -->
    <div style="margin-top:16px;padding:12px 14px;background:var(--surface2);border-radius:var(--radius);border:1px solid var(--border);">
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">Demo Accounts</div>
      <div style="display:flex;flex-direction:column;gap:5px;">
        <div style="display:flex;justify-content:space-between;font-size:.78rem;">
          <span style="color:var(--text);">sujan.ghimire</span>
          <span style="color:var(--text-muted);">Administrator</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.78rem;">
          <span style="color:var(--text);">susma.thapa</span>
          <span style="color:var(--text-muted);">Teacher</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.78rem;">
          <span style="color:var(--text);">saimon.shrestha</span>
          <span style="color:var(--text-muted);">Headteacher</span>
        </div>
      </div>
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);font-size:.75rem;color:var(--text-muted);">
        Password for all: <code>password123</code>
      </div>
    </div>

    <div class="login-footer">
      Niels Brock Copenhagen Business College &mdash; EduSync v1.0
    </div>

  </div>
</div>

</body>
</html>
