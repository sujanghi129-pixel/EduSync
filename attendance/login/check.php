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
session_start();
require_once __DIR__ . '/../../shared/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gate.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';

if (!$username || !$password) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: gate.php');
    exit;
}

$pdo  = db();
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
    $_SESSION['login_error'] = 'Invalid credentials or insufficient permissions.';
    header('Location: gate.php');
    exit;
}
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
