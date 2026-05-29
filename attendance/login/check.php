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
$guard = new LoginGuard($pdo);

// ── LOCKOUT CHECK ─────────────────────────────────────────────────────────────
// Reject before touching credentials if the account is already locked.
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

// Validate credentials and role
$allowedRoles = ['Administrator', 'Teacher', 'Headteacher'];

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
// Redirect to intended page or default to attendance index
$return = $_POST['return'] ?? '../index.php';
// Sanitise to prevent open redirect — only allow relative paths
if (strpos($return, '://') !== false || strpos($return, '//') === 0) {
    $return = '../index.php';
}
// Set session
$_SESSION['user'] = [
    'staffId'  => $staff['staffId'],
    'fullName' => $staff['fullName'],
    'username' => $staff['username'],
    'role'     => $staff['role'],
];
header('Location: ' . $return);
exit;