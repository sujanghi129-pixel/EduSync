<?php

/**
 * attendance/login/check.php
 *
 * Login gate for the Attendance module.
 * Processes the login form POST: validates credentials against
 * tblStaff, sets the session, and redirects to the Attendance
 * index (or a requested return URL).
 *
 * Only staff with roles Administrator, Teacher or Headteacher
 * are permitted access to the Attendance module.
 *
 * On failure, redirects back to gate.php with an error message
 * stored in the session.
 *
 * @package EduSync
 * @author  Laxman Giri
 */
// Load auth helpers — provides startSecureSession()
require_once __DIR__ . '/../../shared/auth.php';
require_once __DIR__ . '/../../shared/db.php';
require_once __DIR__ . '/../../shared/LoginGuard.php';

// Start session with Secure, HttpOnly, SameSite=Strict cookie flags
startSecureSession();

// Reject non-POST requests — this endpoint should never be reached via GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gate.php');
    exit;
}

// Normalise username to lowercase so lookup is case-insensitive
$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password']      ?? '';

// Fail fast on missing fields before touching the database
if (!$username || !$password) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: gate.php');
    exit;
}

$pdo   = db();
$guard = new LoginGuard($pdo);

// ── LOCKOUT CHECK ─────────────────────────────────────────────────────────────
// Check lockout status before querying credentials so we don't leak timing
// information about whether the username exists.
$lockStatus = $guard->check($username);
if ($lockStatus['locked']) {
    $_SESSION['login_error'] = $lockStatus['message'];
    header('Location: gate.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT staffId, fullName, username, passwordHash, role, isStaffActive
    FROM tblStaff
    WHERE username = ?
    LIMIT 1
");
$stmt->execute([$username]);
$staff = $stmt->fetch();

// Roles that may access the Attendance module
$allowedRoles = ['Administrator', 'Teacher', 'Headteacher'];

// Combine all failure conditions into one branch to prevent user enumeration:
// the response is identical whether the user doesn't exist, the password is
// wrong, the account is inactive, or the role is unauthorised.
if (!$staff
    || !password_verify($password, $staff['passwordHash'])
    || !$staff['isStaffActive']
    || !in_array($staff['role'], $allowedRoles, true)
) {
    // Record the failure; guard returns a message with remaining-attempts hint
    $result = $guard->recordFailure($username);
    $_SESSION['login_error'] = $result['message'];
    header('Location: gate.php');
    exit;
}

// ── SUCCESS ───────────────────────────────────────────────────────────────────
// Clear the failure counter so a successful login resets the lockout window
$guard->clearFailures($username);

// Regenerate the session ID on privilege escalation to prevent session fixation.
// true = delete the old session file from the server.
session_regenerate_id(true);

// Validate return URL — only allow relative paths to prevent open-redirect attacks
$return = $_POST['return'] ?? '../index.php';
if (strpos($return, '://') !== false || strpos($return, '//') === 0) {
    // Absolute URL detected — ignore it and fall back to the safe default
    $return = '../index.php';
}

// Write minimal session data — avoid storing sensitive fields like passwordHash
$_SESSION['user'] = [
    'staffId'  => $staff['staffId'],
    'fullName' => $staff['fullName'],
    'username' => $staff['username'],
    'role'     => $staff['role'],
];
header('Location: ' . $return);
exit;
