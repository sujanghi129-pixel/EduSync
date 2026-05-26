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

// Load auth helpers — provides startSecureSession() and requireLogin()
require_once __DIR__ . '/shared/auth.php';

// Load the database connection factory function db()
require_once __DIR__ . '/shared/db.php';

// Load the brute-force / rate-limiting guard
require_once __DIR__ . '/shared/LoginGuard.php';

// Start session with Secure, HttpOnly, SameSite=Strict cookie flags
startSecureSession();

// ── ALREADY LOGGED IN ────────────────────────────────────────────────────────
// If a valid session already exists, skip the login form entirely and redirect
// the user straight to the dashboard.
// exit after header() is essential — without it PHP continues executing and
// could render the login page before the browser follows the redirect.
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

// Holds the error message shown in the callout; null means no error yet
$error = null;

// ── HANDLE FORM SUBMISSION (POST) ────────────────────────────────────────────
// Only runs when the login form is submitted; GET requests (initial page load)
// fall straight through to the HTML below.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Normalise username: trim whitespace and force lowercase so
    // "Sujan.Ghimire " matches the stored value "sujan.ghimire"
    $username = strtolower(trim($_POST['username'] ?? ''));

    // Password taken as-is; bcrypt comparison is case-sensitive
    $password = $_POST['password'] ?? '';

    // ── BASIC VALIDATION ─────────────────────────────────────────────────────
    // Catch empty submissions before hitting the database or the guard
    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {

        // Obtain a PDO connection from the shared factory
        $pdo   = db();
        $guard = new LoginGuard($pdo);

        // ── LOCKOUT CHECK ─────────────────────────────────────────────────────
        // Reject immediately if the account is currently locked.
        // This runs BEFORE password_verify() so locked accounts never
        // trigger a bcrypt comparison (avoids timing-based enumeration).
        $lockStatus = $guard->check($username);
        if ($lockStatus['locked']) {
            $error = $lockStatus['message'];
        } else {

            // ── DATABASE LOOKUP ───────────────────────────────────────────────
            // Look up the staff member via stored procedure.
            // sp_GetStaffByUsername filters to active accounts only
            // (isStaffActive = TRUE), so deactivated staff cannot log in
            // even with a correct password.
            $stmt = $pdo->prepare("CALL sp_GetStaffByUsername(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // ── PASSWORD VERIFICATION ─────────────────────────────────────────
            // password_verify() safely compares the plain-text submission against
            // the bcrypt hash stored in the database. It is timing-safe and will
            // not reveal whether the username or the password was wrong.
            //
            // On success: clear the failure counter and write the session.
            // On failure: record the attempt; the guard message includes a
            //             "X attempts remaining" hint for usability.
            if ($user && password_verify($password, $user['passwordHash'])) {

                // Clear any previous failure counter for this username
                $guard->clearFailures($username);

                // Regenerate the session ID on privilege escalation.
                // Invalidates the pre-login session ID so an attacker who
                // obtained it (e.g. via session fixation) cannot reuse it.
                // true = delete the old session file from the server.
                session_regenerate_id(true);

                // Populate session with the minimum required fields.
                // The passwordHash is deliberately excluded — it must never
                // appear in serialised session data.
                $_SESSION['user'] = [
                    'staffId'  => $user['staffId'],   // Used for ownership/permission checks
                    'fullName' => $user['fullName'],   // Displayed in the UI and eyebrow
                    'username' => $user['username'],   // Shown in profile cards
                    'role'     => $user['role'],       // Drives requireRole() access control
                ];

                // Redirect to dashboard; exit prevents any further output
                header('Location: dashboard/index.php');
                exit;

            } else {
                // Record the failure and get an updated error message
                $result = $guard->recordFailure($username);
                $error  = $result['message'];
            }
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
<!-- Login-specific styles: card layout, form, demo panel, back button -->
<link rel="stylesheet" href="login.css">
</head>
<body>

<!-- Full-viewport flex wrapper — centres the card vertically and horizontally -->
<div class="login-wrap">
  <div class="login-card">

    <!-- ── TOP ROW ──────────────────────────────────────────────────────────
         Flex row with the logo on the left and the "← Back to Home" ghost
         button on the right. Both link to landing.php. -->
    <div class="login-top-row">

      <!-- Logo: clicking it returns the visitor to the landing page -->
      <a href="landing.php" class="login-logo">
        <img src="shared/logo.png" alt="EduSync Logo" class="login-logo-img">
        <span class="login-logo-name">EduSync</span>
      </a>

      <!-- Ghost pill button — secondary escape route back to the landing page -->
      <a href="landing.php" class="login-back-home">&#8592; Back to Home</a>
    </div>

    <!-- Page heading and supporting subtitle -->
    <div class="login-title">Welcome back</div>
    <div class="login-sub">Sign in to your school account</div>

    <!-- ── ERROR CALLOUT ─────────────────────────────────────────────────────
         Only rendered when $error is non-null (i.e. a POST attempt failed).
         htmlspecialchars prevents XSS in case error text ever contains
         user-supplied input. -->
    <?php if ($error): ?>
      <div class="callout callout-danger" style="margin-bottom:20px;">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- ── LOGIN FORM ─────────────────────────────────────────────────────────
         POST to the same file; the PHP block at the top handles the submission. -->
    <form method="POST" action="index.php">

      <!-- Username field -->
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
        <!-- value re-populates the field after a failed attempt so the user
             doesn't have to retype their username.
             autofocus moves the cursor here automatically on page load. -->
      </div>

      <!-- Password field — type="password" masks the characters -->
      <div class="form-group">
        <label class="form-label">Password</label>
        <input
          class="form-input"
          type="password"
          name="password"
          placeholder="Enter your password"
        >
        <!-- Password is intentionally NOT re-populated after a failed attempt;
             the user must retype it for security. -->
      </div>

      <!-- Full-width submit button -->
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
        Sign In
      </button>
    </form>

    <!-- ── DEMO CREDENTIALS PANEL ────────────────────────────────────────────
         Displayed for marking/testing purposes only.
         Remove this entire block before deploying to production.
         Inline styles are used here because this panel is a temporary
         fixture — extracting it to login.css would imply permanence. -->
    <div style="margin-top:16px;padding:12px 14px;background:var(--surface2);border-radius:var(--radius);border:1px solid var(--border);">

      <!-- Panel heading: small all-caps label -->
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">Demo Accounts</div>

      <!-- One row per demo account: username left, role right -->
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

      <!-- Shared password note, visually separated by a top border -->
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);font-size:.75rem;color:var(--text-muted);">
        Password for all: <code>password123</code>
      </div>
    </div><!-- /.demo panel -->

    <!-- ── FOOTER ────────────────────────────────────────────────────────────
         Small copyright line at the bottom of the card. Purely informational. -->
    <div class="login-footer">
      <p>&copy; 2026 EduSync &mdash; Student Record System.</p>
    </div>

  </div><!-- /.login-card -->
</div><!-- /.login-wrap -->

</body>
</html>