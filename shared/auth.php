<?php

/**
 * shared/auth.php
 *
 * Server-side authentication and role-based access control helpers.
 * Include this file in any page that requires a logged-in user.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

/**
 * Configures secure session cookie flags and starts (or resumes) the session.
 *
 * Must be called before any session_start() so the cookie attributes are set
 * before PHP writes the Set-Cookie header. Calling it multiple times within
 * a request is safe — session_status() guards against double-starting.
 *
 * Flags applied:
 *   lifetime  0        — cookie expires when the browser closes (no persistent cookie)
 *   path      /        — cookie sent on every request to this host
 *   domain    ''       — current host only; no subdomain sharing
 *   secure    true     — cookie only sent over HTTPS
 *   httponly  true     — JavaScript cannot read the cookie (blocks XSS theft)
 *   samesite  Strict   — cookie not sent on cross-site requests (blocks CSRF)
 *
 * Note: set secure=false if running on plain HTTP in local development;
 * on production the server must serve over HTTPS for the Secure flag to matter.
 *
 * @return void
 */
function startSecureSession(): void {
    if (session_status() !== PHP_SESSION_NONE) return;

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

/**
 * Protects a page by redirecting unauthenticated users to the login page.
 *
 * @return void
 */
function requireLogin(): void {
    startSecureSession();
    if (empty($_SESSION['user'])) {
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/../index.php');
        exit;
    }
}

/**
 * Checks if the current user has one of the allowed roles.
 * Redirects to dashboard with an "Access Denied" message if not.
 *
 * @param  array $allowedRoles - Roles permitted to access the page.
 * @return void
 */
function requireRole(array $allowedRoles): void {
    requireLogin();
    $userRole = $_SESSION['user']['role'] ?? '';
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['toast_error'] = 'Access denied. You do not have permission to view that page.';
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/../dashboard/index.php');
        exit;
    }
}

/**
 * Returns true if the current user is an Administrator.
 *
 * @return bool
 */
function isAdmin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'Administrator';
}

/**
 * Returns the currently logged-in user's session data.
 *
 * @return array
 */
function currentUser(): array {
    return $_SESSION['user'] ?? [];
}