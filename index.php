<?php

/**
 * index.php  (project root — main login page)
 *
 * Handles the two-step login process:
 *   Step 1 (this file): email + password → generate OTP → save to tblOTPLog → redirect to 2fa_verify.php
 *   Step 2 (2fa_verify.php): enter OTP → write $_SESSION['user'] → redirect to dashboard
 *
 * USERNAME REMOVED:
 *   $_SESSION['2fa_user'] no longer contains a 'username' key.
 *   Session only stores: staffId, fullName, email, role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

require_once __DIR__ . '/shared/auth.php';
require_once __DIR__ . '/shared/db.php';
require_once __DIR__ . '/methods/LoginGuard.php';

startSecureSession();

// ── HELPERS ───────────────────────────────────────────────────────────────────

/**
 * generateOtp()
 * Returns a cryptographically secure 6-digit OTP string.
 * random_int() uses OS-level CSPRNG — safe for security tokens.
 * str_pad() ensures exactly 6 digits (e.g. "007341" not "7341").
 */
function generateOtp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * storeOtp()
 * Saves the OTP to tblOTPLog so it can be read from phpMyAdmin during development.
 * Deletes any previous unused OTP for this email first (one active code at a time).
 * In production: replace this function body with PHPMailer SMTP sending.
 */
function storeOtp(string $email, string $otp, string $fullName): bool {
    try {
        $pdo = db();

        // Remove old unused codes for this email (keeps the table clean)
        $pdo->prepare("DELETE FROM tblOTPLog WHERE email = ? AND used = FALSE")
            ->execute([$email]);

        // Insert new OTP — expires_at is set by MySQL to avoid PHP/DB clock drift
        $stmt = $pdo->prepare("
            INSERT INTO tblOTPLog (email, otp, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        return $stmt->execute([$email, $otp]);

    } catch (PDOException $e) {
        return false;
    }
}

// ── ALREADY LOGGED IN ────────────────────────────────────────────────────────
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

$error = null;

// ── HANDLE FORM SUBMISSION ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Normalise email: trim whitespace, force lowercase
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } else {

        $pdo   = db();
        $guard = new LoginGuard($pdo);

        // Check lockout before password_verify() to prevent timing oracle attacks
        $lockStatus = $guard->check($email);
        if ($lockStatus['locked']) {
            $error = $lockStatus['message'];

        } else {

            // Look up staff by email via stored procedure
            $stmt = $pdo->prepare("CALL sp_GetStaffByEmail(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
            $stmt->closeCursor();

            if ($user && password_verify($password, $user['passwordHash'])) {

                // First factor passed — clear lockout counter
                $guard->clearFailures($email);

                // Generate OTP and save to DB
                // $_SESSION['user'] is NOT written yet — 2fa_verify.php does that
                $otp = generateOtp();

                if (!storeOtp($user['email'], $otp, $user['fullName'])) {
                    $error = 'Could not generate your verification code. Please try again.';

                } else {
                    // Store 2FA staging keys in session
                    // USERNAME REMOVED: 'username' key no longer included in '2fa_user'
                    $_SESSION['2fa_pending'] = true;
                    $_SESSION['2fa_staffId'] = $user['staffId'];
                    $_SESSION['2fa_email']   = $user['email'];
                    $_SESSION['2fa_otp']     = $otp;
                    $_SESSION['2fa_expires'] = time() + 600; // 10 minutes

                    $_SESSION['2fa_user'] = [
                        'staffId'  => $user['staffId'],
                        'fullName' => $user['fullName'],
                        'email'    => $user['email'],  // username removed
                        'role'     => $user['role'],
                    ];

                    header('Location: 2fa_verify.php');
                    exit;
                }

            } else {
                $result = $guard->recordFailure($email);
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
<link rel="stylesheet" href="login.css">
</head>
<body>

<div class="login-wrap">
  <div class="login-card">

    <div class="login-top-row">
      <a href="landing.php" class="login-logo">
        <img src="shared/logo.png" alt="EduSync Logo" class="login-logo-img">
        <span class="login-logo-name">EduSync</span>
      </a>
      <a href="landing.php" class="login-back-home">&#8592; Back to Home</a>
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
        <label class="form-label">Email</label>
        <input
          class="form-input"
          type="email"
          name="email"
          placeholder="e.g. sujan.ghimire@edusync.school"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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

    <!-- Demo credentials panel — REMOVE before production -->
    <div style="margin-top:16px;padding:12px 14px;background:var(--surface2);border-radius:var(--radius);border:1px solid var(--border);">
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">Demo Accounts</div>
      <div style="display:flex;flex-direction:column;gap:5px;">
        <div style="display:flex;justify-content:space-between;font-size:.78rem;">
          <span style="color:var(--text);">sujan.ghimire@edusync.school</span>
          <span style="color:var(--text-muted);">Administrator</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.78rem;">
          <span style="color:var(--text);">susma.thapa@edusync.school</span>
          <span style="color:var(--text-muted);">Teacher</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.78rem;">
          <span style="color:var(--text);">saimon.shrestha@edusync.school</span>
          <span style="color:var(--text-muted);">Headteacher</span>
        </div>
      </div>
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);font-size:.75rem;color:var(--text-muted);">
        Password for all: <code>password123</code>
      </div>
      <div style="margin-top:6px;font-size:.72rem;color:var(--text-muted);">
        🔐 After login, check <strong>phpMyAdmin → tblOTPLog</strong> for your verification code.
      </div>
    </div>

    <div class="login-footer">
      <p>&copy; 2026 EduSync &mdash; Student Record System.</p>
    </div>

  </div>
</div>

</body>
</html>
