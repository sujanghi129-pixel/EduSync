<?php

/**
 * attendance/login/check.php
 *
 * Processes the attendance login form POST.
 * Validates email + password, saves OTP to tblOTPLog, redirects to 2fa_verify.php.
 *
 * USERNAME REMOVED:
 *   $_SESSION['2fa_user'] no longer contains 'username'.
 *   Session keys: staffId, fullName, email, role.
 *
 * @package EduSync
 * @author  Laxman Giri
 */

require_once __DIR__ . '/../../shared/auth.php';
require_once __DIR__ . '/../../shared/db.php';
require_once __DIR__ . '/../../methods/LoginGuard.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gate.php');
    exit;
}

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    header('Location: gate.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = 'Please enter a valid email address.';
    header('Location: gate.php');
    exit;
}

$pdo   = db();
$guard = new LoginGuard($pdo);

// Check lockout before verifying password
$lockStatus = $guard->check($email);
if ($lockStatus['locked']) {
    $_SESSION['login_error'] = $lockStatus['message'];
    header('Location: gate.php');
    exit;
}

// Look up staff by email
$stmt = $pdo->prepare("CALL sp_GetStaffByEmail(?)");
$stmt->execute([$email]);
$staff = $stmt->fetch();
try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
$stmt->closeCursor();

$allowedRoles = ['Administrator', 'Teacher', 'Headteacher'];

if (!$staff
    || !password_verify($password, $staff['passwordHash'])
    || !$staff['isStaffActive']
    || !in_array($staff['role'], $allowedRoles, true)
) {
    $result = $guard->recordFailure($email);
    $_SESSION['login_error'] = $result['message'];
    header('Location: gate.php');
    exit;
}

// ── FIRST FACTOR PASSED — INITIATE 2FA ────────────────────────────────────────
$guard->clearFailures($email);

$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Save OTP to database (readable from phpMyAdmin → tblOTPLog)
try {
    $pdo->prepare("DELETE FROM tblOTPLog WHERE email = ? AND used = FALSE")->execute([$email]);
    $saved = $pdo->prepare("
        INSERT INTO tblOTPLog (email, otp, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
    ")->execute([$email, $otp]);
} catch (PDOException $e) {
    $saved = false;
}

if (!$saved) {
    $_SESSION['login_error'] = 'Could not generate your verification code. Please try again.';
    header('Location: gate.php');
    exit;
}

// Store 2FA staging keys — USERNAME REMOVED from '2fa_user'
$_SESSION['2fa_pending'] = true;
$_SESSION['2fa_staffId'] = $staff['staffId'];
$_SESSION['2fa_email']   = $staff['email'];
$_SESSION['2fa_otp']     = $otp;
$_SESSION['2fa_expires'] = time() + 600;

$_SESSION['2fa_user'] = [
    'staffId'  => $staff['staffId'],
    'fullName' => $staff['fullName'],
    'email'    => $staff['email'],  // username removed
    'role'     => $staff['role'],
];

// Store return URL so 2fa_verify.php redirects back to attendance after OTP success
$return = $_POST['return'] ?? '../../attendance/index.php';
if (strpos($return, '://') !== false || strpos($return, '//') === 0) {
    $return = '../../attendance/index.php';
}
$_SESSION['2fa_return'] = $return;

header('Location: ../../2fa_verify.php');
exit;
