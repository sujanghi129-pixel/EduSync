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

// Start (or resume) the PHP session so $_SESSION is available throughout the request
session_start();

// Load the database connection factory function db()
require_once __DIR__ . '/shared/db.php';

// ── ALREADY LOGGED IN ────────────────────────────────────────────────────────
// If a valid session already exists, skip the login form entirely and send the
// user straight to the dashboard. Using exit after header() is essential to
// prevent any further PHP execution after the redirect.
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

// Holds the error message shown in the callout box; null means no error yet
$error = null;

// ── HANDLE FORM SUBMISSION (POST) ────────────────────────────────────────────
// Only runs when the login form has been submitted; GET requests (first page
// load) fall straight through to the HTML below.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Normalise username: trim whitespace and force lowercase so
    // "Sujan.Ghimire " matches the stored value "sujan.ghimire"
    $username = strtolower(trim($_POST['username'] ?? ''));

    // Password is taken as-is; bcrypt comparison is case-sensitive
    $password = $_POST['password'] ?? '';

    // ── BASIC VALIDATION ─────────────────────────────────────────────────────
    // Catch empty submissions before hitting the database
    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {

        // Obtain a PDO connection from the shared factory
        $pdo = db();

        /**
         * Look up the staff member by username via stored procedure.
         *
         * sp_GetStaffByUsername filters to active accounts only
         * (isStaffActive = TRUE), so deactivated staff cannot log in
         * even with a correct password.
         *
         * Using a prepared statement prevents SQL injection — the username
         * is passed as a bound parameter, never interpolated into the query.
         *
         * @var array|false $user The matched staff row, or false if not found.
         */
        $stmt = $pdo->prepare("CALL sp_GetStaffByUsername(?)");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // ── PASSWORD VERIFICATION ─────────────────────────────────────────
        // password_verify() compares the plain-text submission against the
        // bcrypt hash stored in the database. It is timing-safe, so it won't
        // leak whether the username or password was wrong.
        //
        // On success:  populate the session with safe fields only.
        //              The passwordHash is intentionally excluded so it is
        //              never exposed in serialised session data.
        //
        // On failure:  use a generic error message — never reveal whether the
        //              username exists or if just the password was wrong.
        if ($user && password_verify($password, $user['passwordHash'])) {

            // Store only the fields the rest of the app needs
            $_SESSION['user'] = [
                'staffId'  => $user['staffId'],  // Used for ownership checks
                'fullName' => $user['fullName'],  // Displayed in the UI
                'username' => $user['username'],  // Shown in profile/eyebrow
                'role'     => $user['role'],      // Drives role-based access (requireRole)
            ];

            // Redirect to dashboard; exit prevents further output
            header('Location: dashboard/index.php');
            exit;

        } else {
            // Deliberately vague: do not confirm whether the username exists
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
<title>EduSync — Login</title>
<!-- Login-specific styles (card layout, logo, form) -->
<link rel="stylesheet" href="login.css">
</head>
<body>

<!-- Outer wrapper centres the card vertically and horizontally -->
<div class="login-wrap">
  <div class="login-card">

    <!-- ── LOGO / BRANDING ─────────────────────────────────────────────── -->
    <div class="login-logo">
      <img src="shared/logo.png" alt="EduSync Logo" class="login-logo-img">
      <span class="login-logo-name">EduSync</span>
    </div>

    <!-- Page heading and subheading -->
    <div class="login-title">Welcome back</div>
    <div class="login-sub">Sign in to your school account</div>

    <!-- ── ERROR CALLOUT ───────────────────────────────────────────────── -->
    <!-- Only rendered when $error is non-null (failed POST attempt) -->
    <?php if ($error): ?>
      <div class="callout callout-danger" style="margin-bottom:20px;">
        <!-- htmlspecialchars prevents XSS if error text ever contains user input -->
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- ── LOGIN FORM ──────────────────────────────────────────────────── -->
    <!-- POST to same file; PHP block above handles the submission -->
    <form method="POST" action="index.php">

     <!-- Username field -->
<div class="form-group">
  <label class="form-label">Username</label>
  <!-- Re-populate the field after a failed attempt so the user
       doesn't have to retype their username -->
  <input
    class="form-input"
    type="text"
    name="username"
    placeholder="e.g. sujan.ghimire"
    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
    autofocus
  >
</div>

<!-- Password field — type="password" masks the input -->
<div class="form-group">
  <label class="form-label">Password</label>
  <!-- Password is intentionally NOT re-populated after failure;
       the user must retype it for security -->
  <input
    class="form-input"
    type="password"
    name="password"
    placeholder="Enter your password"
  >
</div>
      <!-- Full-width submit button -->
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
        Sign In
      </button>
    </form>

    <!-- ── DEMO CREDENTIALS PANEL ──────────────────────────────────────── -->
    <!-- Displayed for marking/testing purposes; remove before production -->
    <div style="margin-top:16px;padding:12px 14px;background:var(--surface2);border-radius:var(--radius);border:1px solid var(--border);">

      <!-- Panel heading -->
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">Demo Accounts</div>

      <!-- One row per demo account: username on the left, role on the right -->
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

      <!-- Shared password note, separated by a subtle top border -->
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);font-size:.75rem;color:var(--text-muted);">
        Password for all: <code>password123</code>
      </div>
    </div>

    <!-- ── FOOTER ──────────────────────────────────────────────────────── -->
    <div class="login-footer">
      <p>&copy; 2026 EduSync &mdash; Student Record System.</p>
    </div>

  </div><!-- /.login-card -->
</div><!-- /.login-wrap -->

</body>
</html>