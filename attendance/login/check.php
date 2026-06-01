<?php

/**
 * attendance/login/check.php
 *
 * WHAT THIS FILE DOES:
 *   Processes the login form POST from attendance/login/gate.php.
 *   Validates the email + password, then initiates 2FA by saving
 *   an OTP to tblOTPLog and redirecting to the shared 2fa_verify.php.
 *
 * CHANGES FROM ORIGINAL:
 *   - Login field changed from  $_POST['username']  →  $_POST['email']
 *   - Lookup changed from  sp_GetStaffByUsername  →  sp_GetStaffByEmail
 *   - Email format validated with filter_var() before any DB query
 *   - Require path fixed:  /shared/LoginGuard.php  →  /methods/LoginGuard.php
 *   - On password success: instead of writing $_SESSION['user'] directly,
 *       the OTP is saved to tblOTPLog and staging keys are stored in
 *       $_SESSION['2fa_*'], then the user is sent to 2fa_verify.php
 *   - $_SESSION['2fa_return'] is set so that after the OTP is verified,
 *       the user lands back in the attendance module (not the dashboard)
 *
 * HOW TO FIND THE OTP DURING DEVELOPMENT:
 *   phpMyAdmin → edusync → tblOTPLog → read the otp column
 *
 * @package EduSync
 * @author  Laxman Giri
 */

// auth.php provides startSecureSession()
require_once __DIR__ . '/../../shared/auth.php';

// db.php provides db() PDO singleton
require_once __DIR__ . '/../../shared/db.php';

// LoginGuard provides brute-force / lockout protection
// NOTE: path is methods/ not shared/ — corrected from original
require_once __DIR__ . '/../../methods/LoginGuard.php';

// Start the session with Secure, HttpOnly, SameSite=Strict cookie flags.
startSecureSession();

// ── METHOD CHECK ──────────────────────────────────────────────────────────────
// Only accept POST requests. A GET to this file (e.g. someone visits it
// directly in the browser) redirects back to the login form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gate.php');
    exit;
}

// ── READ + NORMALISE INPUTS ───────────────────────────────────────────────────

// Normalise the email: trim whitespace, force lowercase.
// CHANGED: was $_POST['username'] in the original file.
$email    = strtolower(trim($_POST['email'] ?? ''));

// Password taken as-is — bcrypt comparison is case-sensitive.
$password = $_POST['password'] ?? '';

// ── BASIC VALIDATION ─────────────────────────────────────────────────────────
// Catch empty submissions before touching the database.
if (!$email || !$password) {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    header('Location: gate.php');
    exit;
}

// NEW: Validate email format. Catches obvious typos before any DB query.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = 'Please enter a valid email address.';
    header('Location: gate.php');
    exit;
}

$pdo   = db();
$guard = new LoginGuard($pdo);   // Brute-force / lockout guard

// ── LOCKOUT CHECK ─────────────────────────────────────────────────────────────
// Check tblLoginAttempts before verifying the password. Locked accounts never
// trigger a bcrypt comparison — this prevents timing-based enumeration.
$lockStatus = $guard->check($email);
if ($lockStatus['locked']) {
    $_SESSION['login_error'] = $lockStatus['message'];
    header('Location: gate.php');
    exit;
}

// ── DATABASE LOOKUP ───────────────────────────────────────────────────────────
// CHANGED: uses sp_GetStaffByEmail instead of the original sp_GetStaffByUsername.
// The stored procedure only returns rows where isStaffActive = TRUE.
$stmt = $pdo->prepare("CALL sp_GetStaffByEmail(?)");
$stmt->execute([$email]);
$staff = $stmt->fetch();

// Drain all result sets MySQL stored procedures leave open.
// Without this, subsequent queries on the same PDO connection fail.
try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
$stmt->closeCursor();

// ── CREDENTIAL + ROLE VALIDATION ─────────────────────────────────────────────
// Reject if:
//   - No staff row found for this email (account doesn't exist)
//   - Password doesn't match the stored bcrypt hash
//   - Account is not active (isStaffActive = FALSE)
//   - Role is not one that can access the Attendance module
$allowedRoles = ['Administrator', 'Teacher', 'Headteacher'];

if (!$staff
    || !password_verify($password, $staff['passwordHash'])
    || !$staff['isStaffActive']
    || !in_array($staff['role'], $allowedRoles, true)
) {
    // Record the failure — increments the counter in tblLoginAttempts
    // and locks the account after MAX_FAILS consecutive failures.
    $result = $guard->recordFailure($email);
    $_SESSION['login_error'] = $result['message'];
    header('Location: gate.php');
    exit;
}

// ── FIRST FACTOR PASSED — INITIATE 2FA ────────────────────────────────────────
// Clear any previous lockout counter now that credentials are confirmed.
$guard->clearFailures($email);

// Generate a 6-digit CSPRNG OTP.
// random_int() uses OS-level entropy — safe for security tokens.
// str_pad() ensures exactly 6 digits (e.g. "007341" not "7341").
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// ── SAVE OTP TO DATABASE ──────────────────────────────────────────────────────
// Deletes any existing unused OTP for this email, then inserts the new one
// with a 10-minute expiry window. The OTP is readable from:
//   phpMyAdmin → edusync → tblOTPLog → otp column
try {
    // Remove old unused codes for this email (keeps the table clean)
    $pdo->prepare("DELETE FROM tblOTPLog WHERE email = ? AND used = FALSE")
        ->execute([$email]);

    // Insert the new OTP. expires_at is set by MySQL to avoid clock skew.
    $saved = $pdo->prepare("
        INSERT INTO tblOTPLog (email, otp, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
    ")->execute([$email, $otp]);

} catch (PDOException $e) {
    $saved = false;
}

if (!$saved) {
    // DB write failed — inform the user and stay on the gate page.
    $_SESSION['login_error'] = 'Could not generate your verification code. Please try again.';
    header('Location: gate.php');
    exit;
}

// ── STORE 2FA STAGING KEYS IN SESSION ────────────────────────────────────────
// IMPORTANT: $_SESSION['user'] is NOT written here.
// The real session is only written by 2fa_verify.php after the OTP is confirmed.
// Staging keys are prefixed '2fa_' to make them easy to identify and clear.

$_SESSION['2fa_pending'] = true;                  // Gate flag — 2fa_verify.php checks this
$_SESSION['2fa_staffId'] = $staff['staffId'];     // Available for audit logging
$_SESSION['2fa_email']   = $staff['email'];       // Shown masked on the verify page
$_SESSION['2fa_otp']     = $otp;                  // The code to compare submitted input against
$_SESSION['2fa_expires'] = time() + 600;          // OTP valid for 10 minutes from now
$_SESSION['2fa_user']    = [                      // Full user array promoted on success
    'staffId'  => $staff['staffId'],
    'fullName' => $staff['fullName'],
    'username' => $staff['username'],
    'email'    => $staff['email'],
    'role'     => $staff['role'],
];

// ── SET RETURN URL FOR ATTENDANCE MODULE ──────────────────────────────────────
// After a successful OTP verification, the user should return to the
// attendance module rather than the main dashboard.
// 2fa_verify.php reads $_SESSION['2fa_return'] and redirects there on success.
$return = $_POST['return'] ?? '../../attendance/index.php';

// Sanitise: only allow relative paths — never redirect to an external URL.
if (strpos($return, '://') !== false || strpos($return, '//') === 0) {
    $return = '../../attendance/index.php';
}
$_SESSION['2fa_return'] = $return;

// ── REDIRECT TO SHARED 2FA PAGE ───────────────────────────────────────────────
// The shared 2fa_verify.php is at the project root (two levels up from here).
header('Location: ../../2fa_verify.php');
exit;
