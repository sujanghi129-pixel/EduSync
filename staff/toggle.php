<?php

/**
 * staff/toggle.php
 *
 * Presentation layer — Activate / Deactivate staff account handler.
 * Uses the Staff middle layer class to check assignment and toggle status.
 * Requires Administrator role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start session to access user data and toast messages
session_start();

// Include authentication file and restrict access to Administrator role
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Include database connection and Staff middle-layer class
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

/**
 * Create Staff object using database connection
 */
$staffClass = new Staff(db());

/**
 * Get staff ID from submitted form
 */
$id = (int)($_POST['staffId'] ?? 0);

/**
 * Retrieve staff record from database
 */
$staff = $staffClass->getById($id);

/**
 * Redirect if staff record does not exist
 */
if (!$staff) {
    header('Location: index.php');
    exit;
}

/**
 * Block deactivation if staff is assigned to an active class
 */
if ($staff['isStaffActive'] && $staffClass->isAssignedToClass($id)) {
    $_SESSION['toast_error'] =
        "Cannot deactivate: {$staff['fullName']} is assigned to an active class.";

    header('Location: index.php');
    exit;
}

/**
 * Block deactivation if this is the last active Administrator
 */
if (
    $staff['isStaffActive'] &&
    $staff['role'] === 'Administrator' &&
    $staffClass->countAdmins() <= 1
) {
    $_SESSION['toast_error'] =
        "Cannot deactivate: \"{$staff['fullName']}\" is the only Administrator. At least one active Administrator must remain.";

    header('Location: index.php');
    exit;
}

/**
 * Toggle staff active/inactive status
 */
$staffClass->toggleStatus($id);

/**
 * Set new status message
 */
$newState = $staff['isStaffActive'] ? 'deactivated' : 'activated';

$_SESSION['toast'] =
    "Account \"{$staff['fullName']}\" {$newState}.";

/**
 * Redirect back to staff management page
 */
header('Location: index.php');
exit;