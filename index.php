<?php

/**
 * index.php  (project root — main login page)
 *
 * WHAT THIS FILE DOES:
 *   This is the entry point for the entire EduSync system.
 *   It handles the two-step login process:
 *
 *   STEP 1 — First factor (this file):
 *     The staff member submits their email + password.
 *     If credentials are correct, a 6-digit OTP is generated,
 *     saved to tblOTPLog in the database, and the user is
 *     redirected to 2fa_verify.php to enter the code.
 *
 *   STEP 2 — Second factor (2fa_verify.php):
 *     The staff member enters the OTP they can read from
 *     phpMyAdmin → edusync → tblOTPLog.
 *     Only after the correct code is entered does the real
 *     $_SESSION['user'] get written and the user reach the dashboard.
 *
 * CHANGES FROM ORIGINAL:
 *   - Login field changed from username  →  email
 *   - Lookup stored procedure changed:
 *       sp_GetStaffByUsername  →  sp_GetStaffByEmail
 *   - Email format validated with filter_var() before any DB query
 *   - On password success: instead of writing the session directly,
 *       generateOtp() + storeOtp() are called and the user is sent
 *       to 2fa_verify.php with staging keys in $_SESSION['2fa_*']
 *   - Demo panel updated to show email addresses instead of usernames
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// ── DEPENDENCIES ─────────────────────────────────────────────────────────────

// auth.php provides startSecureSession() and requireLogin() helpers
require_once __DIR__ . '/shared/auth.php';

// db.php provides the db() singleton factory for PDO connections
require_once __DIR__ . '/shared/db.php';

// LoginGuard provides brute-force / lockout protection
require_once __DIR__ . '/methods/LoginGuard.php';

// Start the session with Secure, HttpOnly, SameSite=Strict cookie flags.
// Must be called before any output and before reading $_SESSION.
startSecureSession();

// ── HELPER FUNCTIONS ─────────────────────────────────────────────────────────

/**
 * generateOtp()
 *
 * Creates a cryptographically secure 6-digit one-time password.
 *
 * WHY random_int() and not rand()?
 *   random_int() draws from the operating system's CSPRNG (e.g. /dev/urandom
 *   on Linux). This makes the output unpredictable even if an attacker knows
 *   the seed or timing. rand() and mt_rand() are NOT cryptographically safe
 *   and must never be used for security tokens.
 *
 * WHY str_pad()?
 *   random_int(0, 999999) can return values like 7341 (only 4 digits).
 *   str_pad(..., 6, '0', STR_PAD_LEFT) forces it to always be 6 characters
 *   (e.g. "007341") so the OTP entry form always receives exactly 6 digits.
 *
 * @return string  Always exactly 6 numeric characters, zero-padded.
 */
function generateOtp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * storeOtp()
 *
 * Saves the generated OTP to tblOTPLog in the database.
 *
 * WHY store in DB instead of email?
 *   During development there is no mail server on XAMPP. Storing the OTP
 *   in the database lets the developer read it directly from phpMyAdmin
 *   (edusync → tblOTPLog) without needing any email infrastructure.
 *   In production, replace this function body with PHPMailer/SMTP sending.
 *
 * HOW it works:
 *   1. Deletes any previous UNUSED OTP for this email so only the latest
 *      code is valid (prevents accumulation of stale codes in the table).
 *   2. Inserts the new OTP with an expiry timestamp 10 minutes from now.
 *      DATE_ADD(NOW(), INTERVAL 10 MINUTE) is calculated by MySQL so it
 *      uses the DB server clock, avoiding PHP/MySQL clock drift issues.
 *
 * @param string $email     The staff member's email address (lookup key).
 * @param string $otp       The 6-digit code to store.
 * @param string $fullName  Staff member's name (available for future email use).
 * @return bool             TRUE on success, FALSE if a database error occurs.
 */
function storeOtp(string $email, string $otp, string $fullName): bool {
    try {
        $pdo = db();

        // Remove any existing unused OTPs for this email.
        // This prevents the table from filling with stale rows and ensures
        // only the most recently generated code is valid.
        $pdo->prepare("DELETE FROM tblOTPLog WHERE email = ? AND used = FALSE")
            ->execute([$email]);

        // Insert the new OTP. expires_at is set by MySQL to avoid clock skew.
        $stmt = $pdo->prepare("
            INSERT INTO tblOTPLog (email, otp, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");

        return $stmt->execute([$email, $otp]);

    } catch (PDOException $e) {
        // Return false so the caller can show an error to the user.
        // Do NOT expose $e->getMessage() to the browser (security risk).
        return false;
    }
}

// ── ALREADY LOGGED IN ────────────────────────────────────────────────────────
// If the user already has a valid session, skip the login form entirely.
// exit() after header() is required — PHP continues executing without it,
// which would render the login page before the browser follows the redirect.
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

// $error holds the message shown in the danger callout on a failed login.
// NULL means no POST has been attempted yet (fresh page load).
$error = null;

// ── HANDLE FORM SUBMISSION ────────────────────────────────────────────────────
// This block only runs when the form is submitted via POST.
// GET requests (initial page loads) fall through directly to the HTML below.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Normalise the email: trim whitespace and force lowercase.
    // This ensures "Sujan.Ghimire@EduSync.school " matches the DB value.
    $email = strtolower(trim($_POST['email'] ?? ''));

    // Password is taken as-is — bcrypt comparison is case-sensitive.
    $password = $_POST['password'] ?? '';

    // ── BASIC VALIDATION ─────────────────────────────────────────────────────
    // Check for empty inputs first, before touching the database or LoginGuard.
    if (!$email || !$password) {
        $error = 'Please enter your email and password.';

    // Validate email format with PHP's built-in filter.
    // This catches typos like "sujan@" or "sujan.ghimire" (no domain)
    // before any database query is made.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } else {

        $pdo   = db();
        $guard = new LoginGuard($pdo);   // Brute-force / lockout guard

        // ── LOCKOUT CHECK ─────────────────────────────────────────────────────
        // The guard checks tblLoginAttempts to see if this email is currently
        // locked out. This runs BEFORE password_verify() so locked accounts
        // never trigger a bcrypt comparison (which avoids timing-based
        // enumeration attacks where an attacker could tell "account exists"
        // by measuring bcrypt's deliberate slowness).
        $lockStatus = $guard->check($email);
        if ($lockStatus['locked']) {
            $error = $lockStatus['message'];  // e.g. "Locked for 14 more minutes"

        } else {

            // ── DATABASE LOOKUP ───────────────────────────────────────────────
            // NEW: uses sp_GetStaffByEmail instead of the old sp_GetStaffByUsername.
            // The stored procedure only returns rows where isStaffActive = TRUE,
            // so deactivated accounts cannot log in even with the correct password.
            $stmt = $pdo->prepare("CALL sp_GetStaffByEmail(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Drain all result sets that MySQL stored procedures leave open.
            // Without this, subsequent queries on the same connection fail
            // with "Commands out of sync" errors.
            try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
            $stmt->closeCursor();

            // ── PASSWORD VERIFICATION ─────────────────────────────────────────
            // password_verify() compares the submitted plaintext against the
            // bcrypt hash stored in the database. It is timing-safe by design —
            // it always takes the same amount of time whether the hash matches
            // or not, preventing timing-based username enumeration.
            if ($user && password_verify($password, $user['passwordHash'])) {

                // First factor passed — clear any previous failure counter.
                // This resets the lockout clock so the user starts fresh.
                $guard->clearFailures($email);

                // ── INITIATE 2FA ──────────────────────────────────────────────
                // IMPORTANT: $_SESSION['user'] is NOT written here.
                // Writing the session now would let the user skip 2FA by
                // navigating directly to dashboard/index.php.
                // Instead, we store staging keys under $_SESSION['2fa_*']
                // which 2fa_verify.php checks and clears after OTP confirmation.

                $otp = generateOtp();

                // Persist the OTP to the database so the developer can read it
                // from phpMyAdmin → edusync → tblOTPLog during development.
                if (!storeOtp($user['email'], $otp, $user['fullName'])) {
                    // DB write failed — tell the user to retry. Do not proceed
                    // to 2FA because we have no OTP to verify against.
                    $error = 'Could not generate your verification code. Please try again.';

                } else {
                    // Store the pending user data in the session.
                    // 2fa_verify.php will read these, verify the OTP, then
                    // move '2fa_user' into $_SESSION['user'] on success.
                    $_SESSION['2fa_pending'] = true;                    // Gate flag for 2fa_verify.php
                    $_SESSION['2fa_staffId'] = $user['staffId'];        // Used for audit logging
                    $_SESSION['2fa_email']   = $user['email'];          // Shown masked on verify page
                    $_SESSION['2fa_otp']     = $otp;                    // The code to compare against
                    $_SESSION['2fa_expires'] = time() + 600;            // 10-minute expiry (Unix timestamp)
                    $_SESSION['2fa_user']    = [                        // Full user data to promote on success
                        'staffId'  => $user['staffId'],
                        'fullName' => $user['fullName'],
                        'username' => $user['username'],
                        'email'    => $user['email'],
                        'role'     => $user['role'],
                    ];

                    // Redirect to the OTP entry page.
                    // exit() stops PHP executing any further code in this file.
                    header('Location: 2fa_verify.php');
                    exit;
                }

            } else {
                // Wrong email or wrong password.
                // recordFailure() increments the fail counter in tblLoginAttempts
                // and returns a message like "Invalid email or password. (4 attempts remaining)"
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
<!-- login.css: card layout, form fields, callout, demo panel, submit button -->
<link rel="stylesheet" href="login.css">
</head>
<body>

<!-- Full-viewport flex container: centres the card both horizontally and vertically -->
<div class="login-wrap">
  <div class="login-card">

    <!-- ── TOP ROW: LOGO + BACK BUTTON ──────────────────────────────────────── -->
    <div class="login-top-row">
      <!-- Logo: clicking returns the visitor to the landing page -->
      <a href="landing.php" class="login-logo">
        <img src="shared/logo.png" alt="EduSync Logo" class="login-logo-img">
        <span class="login-logo-name">EduSync</span>
      </a>
      <!-- Ghost pill link — secondary escape route back to the landing page -->
      <a href="landing.php" class="login-back-home">&#8592; Back to Home</a>
    </div>

    <div class="login-title">Welcome back</div>
    <div class="login-sub">Sign in to your school account</div>

    <!-- ── ERROR CALLOUT ─────────────────────────────────────────────────────
         Only rendered when $error is non-null (i.e. a POST attempt failed).
         htmlspecialchars() prevents XSS — error text may contain user input. -->
    <?php if ($error): ?>
      <div class="callout callout-danger" style="margin-bottom:20px;">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- ── LOGIN FORM ────────────────────────────────────────────────────────
         POST to self (index.php); the PHP block at the top handles the submission. -->
    <form method="POST" action="index.php">

      <!-- EMAIL FIELD (CHANGED from username)
           type="email" gives the browser built-in email keyboard on mobile
           and basic format validation before the form even submits. -->
      <div class="form-group">
        <label class="form-label">Email</label>
        <input
          class="form-input"
          type="email"
          name="email"
          placeholder="e.g. sujan.ghimire@edusync.school"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          <!-- value re-populates after a failed attempt so the user doesn't
               have to retype their email address. -->
          
          <!-- autofocus places the cursor here automatically on page load. -->
        
      </div>

      <!-- PASSWORD FIELD
           type="password" masks the characters as the user types.
           The value is intentionally NOT re-populated after a failed
           attempt — the user must retype it for security. -->
      <div class="form-group">
        <label class="form-label">Password</label>
        <input
          class="form-input"
          type="password"
          name="password"
          placeholder="Enter your password"
        >
      </div>

      <!-- SUBMIT BUTTON
           style="width:100%" makes the button span the full card width. -->
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
        Sign In
      </button>
    </form>

    <!-- ── DEMO CREDENTIALS PANEL ────────────────────────────────────────────
         For marking / testing only. REMOVE this entire block before deploying
         to production — it reveals account details publicly.
         Inline styles are used deliberately here to signal this is temporary. -->
    <div style="margin-top:16px;padding:12px 14px;background:var(--surface2);border-radius:var(--radius);border:1px solid var(--border);">

      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;">
        Demo Accounts
      </div>

      <!-- One row per demo account: email address on the left, role on the right -->
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

      <!-- Shared password for all demo accounts -->
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);font-size:.75rem;color:var(--text-muted);">
        Password for all: <code>password123</code>
      </div>

      <!-- Development hint: tells the developer where to find the OTP -->
      <div style="margin-top:6px;font-size:.72rem;color:var(--text-muted);">
        🔐 After login, check <strong>phpMyAdmin → tblOTPLog</strong> for your verification code.
      </div>
    </div><!-- /.demo panel -->

    <!-- Footer: copyright line -->
    <div class="login-footer">
      <p>&copy; 2026 EduSync &mdash; Student Record System.</p>
    </div>

  </div><!-- /.login-card -->
</div><!-- /.login-wrap -->

</body>
</html>
