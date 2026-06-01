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

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gate.php');
    exit;
}

$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password']      ?? '';

if (!$username || !$password) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: gate.php');
    exit;
}

$pdo   = db();
$guard = new LoginGuard($pdo);   // Brute-force / lockout guard

// ── LOCKOUT CHECK ─────────────────────────────────────────────────────────────
// Reject before touching credentials if the account is already locked.
$lockStatus = $guard->check($username);
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

// Validate credentials and role
$allowedRoles = ['Administrator', 'Teacher', 'Headteacher'];

// Combine all failure conditions into one branch to prevent user enumeration:
// the response is identical whether the user doesn't exist, the password is
// wrong, the account is inactive, or the role is unauthorised.
if (!$staff
    || !password_verify($password, $staff['passwordHash'])
    || !$staff['isStaffActive']
    || !in_array($staff['role'], $allowedRoles, true)
) {
    // Record the failure and surface the remaining-attempts hint
    $result = $guard->recordFailure($username);
    $_SESSION['login_error'] = $result['message'];
    header('Location: gate.php');
    exit;
}

// ── SUCCESS ───────────────────────────────────────────────────────────────────
// Clear the failure counter before writing the session
$guard->clearFailures($username);

// Regenerate the session ID on privilege escalation to prevent session fixation.
// true = delete the old session file from the server.
session_regenerate_id(true);

// Set session
$_SESSION['user'] = [
    'staffId'  => $staff['staffId'],
    'fullName' => $staff['fullName'],
    'username' => $staff['username'],
    'email'    => $staff['email'],
    'role'     => $staff['role'],
];

// Redirect to intended page or default to attendance index
$return = $_POST['return'] ?? '../index.php';
// Sanitise to prevent open redirect — only allow relative paths
if (strpos($return, '://') !== false || strpos($return, '//') === 0) {
    $return = '../index.php';
}

header('Location: ' . $return);
exit;
