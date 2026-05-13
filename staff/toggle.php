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

session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/Staff.php';

/** @var Staff $staffClass - Middle layer instance */
$staffClass = new Staff(db());

$id    = (int)($_POST['staffId'] ?? 0);

/** @var array|false $staff - Staff record retrieved via middle layer */
$staff = $staffClass->getById($id);
if (!$staff) { header('Location: index.php'); exit; }

// Block deactivation if assigned to an active class
if ($staff['isStaffActive'] && $staffClass->isAssignedToClass($id)) {
    $_SESSION['toast_error'] = "Cannot deactivate: {$staff['fullName']} is assigned to an active class.";
    header('Location: index.php');
    exit;
}

// Toggle via middle layer
$staffClass->toggleStatus($id);
$newState = $staff['isStaffActive'] ? 'deactivated' : 'activated';
$_SESSION['toast'] = "Account \"{$staff['fullName']}\" {$newState}.";
header('Location: index.php');
exit;
