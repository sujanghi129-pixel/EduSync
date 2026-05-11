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
 * Protects a page by redirecting unauthenticated users to the login page.
 *
 * @return void
 */
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
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
