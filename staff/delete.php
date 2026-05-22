<?php

/**
 * staff/delete.php
 *
 * Presentation layer — Delete Staff confirmation page.
 * Uses the Staff middle layer class to check assignment and delete records.
 * Requires Administrator role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start session to access user login info and toast messages
session_start();

// Include authentication and restrict access to Administrators only
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Include database connection and Staff middle-layer class
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

/**
 * Create Staff class object
 * Used to perform staff-related operations
 */
$staffClass = new Staff(db());

/**
 * Get staff ID from URL (GET) or form submission (POST)
 * Convert to integer for security
 */
$id = (int)($_GET['id'] ?? $_POST['staffId'] ?? 0);

/**
 * Retrieve staff record by ID
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
 * Check whether this staff member is assigned to an active class
 * Prevent deletion if true
 */
$hasClasses = $staffClass->isAssignedToClass($id);

/**
 * Check if this staff member is the LAST ACTIVE Administrator
 * Prevent deletion because at least one admin must remain
 */
$isLastAdmin = (
    $staff['role'] === 'Administrator' &&
    $staff['isStaffActive'] &&
    $staffClass->countAdmins() <= 1
);

/**
 * Handle form submission (Delete action)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Prevent deleting the last active Administrator
    if ($isLastAdmin) {
        $_SESSION['toast_error'] =
            "Cannot delete: \"{$staff['fullName']}\" is the only Administrator. Assign another Administrator first.";

        header("Location: delete.php?id=$id");
        exit;
    }

    // Prevent deleting staff assigned to active classes
    if ($hasClasses) {
        $_SESSION['toast_error'] =
            "Cannot delete: {$staff['fullName']} is assigned to an active class.";

        header("Location: delete.php?id=$id");
        exit;
    }

    /**
     * If checks pass, delete the staff record
     */
    $staffClass->delete($id);

    // Success message
    $_SESSION['toast'] =
        "Staff account \"{$staff['fullName']}\" deleted permanently.";

    // Redirect to staff list page
    header('Location: index.php');
    exit;
}

/**
 * Retrieve any error message from session
 */
$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast_error']);

/**
 * Define badge color classes for roles
 */
$roleColor = [
    'Administrator' => 'badge-blue',
    'Teacher'       => 'badge-yellow',
    'Headteacher'   => 'badge-green'
];
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">

<!-- Shared metadata -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Staff — EduSync</title>

<!-- Page-specific stylesheet -->
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Overlay for mobile sidebar -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar navigation -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Logged-in user info -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> ·
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <!-- Page heading -->
    <div class="page-title">Delete Staff</div>
    <div class="page-sub">
      Review before permanently removing this account.
    </div>

    <!-- Delete confirmation card -->
    <div class="card" style="max-width:480px;">

      <!-- Show error message if available -->
      <?php if ($toastError): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($toastError) ?>
        </div>
      <?php endif; ?>

      <!-- Staff information preview -->
      <div style="padding:14px;background:var(--surface2);border-radius:var(--radius);margin-bottom:16px;">

        <!-- Staff full name -->
        <div style="font-weight:600;font-size:.95rem;">
          <?= htmlspecialchars($staff['fullName']) ?>
        </div>

        <!-- Username -->
        <div style="color:var(--text-muted);font-size:.82rem;margin-top:2px;">
          <code><?= htmlspecialchars($staff['username']) ?></code>
        </div>

        <!-- Role and active/inactive badges -->
        <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">

          <!-- Role badge -->
          <span class="badge <?= $roleColor[$staff['role']] ?? 'badge-gray' ?>">
            <?= htmlspecialchars($staff['role']) ?>
          </span>

          <!-- Status badge -->
          <?php if ($staff['isStaffActive']): ?>
            <span class="badge badge-green">● Active</span>
          <?php else: ?>
            <span class="badge badge-red">● Inactive</span>
          <?php endif; ?>

        </div>
      </div>

      <!-- Delete warning -->
      <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:14px;">
        Permanently delete
        <strong><?= htmlspecialchars($staff['fullName']) ?></strong>?
        This cannot be undone.
      </p>

      <!-- Conditional warning messages -->
      <?php if ($isLastAdmin): ?>

        <!-- Cannot delete last administrator -->
        <div class="callout callout-danger">
          <span>🛡️</span>
          <span>
            Cannot delete:
            <strong><?= htmlspecialchars($staff['fullName']) ?></strong>
            is the only Administrator.
            At least one Administrator must remain.
          </span>
        </div>

      <?php elseif ($hasClasses): ?>

        <!-- Cannot delete if assigned to class -->
        <div class="callout callout-danger">
          <span>🚫</span>
          <span>
            Cannot delete: this staff member is assigned to an active class.
            Reassign the class teacher first.
          </span>
        </div>

      <?php else: ?>

        <!-- GDPR suggestion -->
        <div class="callout callout-warn">
          <span>⚠️</span>
          <span>
            Under GDPR, consider <strong>deactivating</strong>
            instead of deleting to preserve audit history.
          </span>
        </div>

      <?php endif; ?>

      <!-- Action buttons -->
      <div class="modal-footer"
           style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:16px;">

        <!-- Cancel button -->
        <a href="index.php" class="btn btn-ghost">Cancel</a>

        <!-- Deactivate instead button -->
        <?php if ($staff['isStaffActive'] && !$hasClasses && !$isLastAdmin): ?>
          <form method="POST" action="toggle.php" style="display:inline;">
            <input type="hidden" name="staffId" value="<?= $id ?>">
            <button type="submit" class="btn btn-ghost">
              Deactivate Instead
            </button>
          </form>
        <?php endif; ?>

        <!-- Delete permanently button -->
        <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline;">
          <input type="hidden" name="staffId" value="<?= $id ?>">
          <button type="submit"
                  class="btn btn-danger"
                  <?= ($hasClasses || $isLastAdmin) ? 'disabled' : '' ?>>
            Delete Permanently
          </button>
        </form>

      </div>
    </div>

  </main>
</div>

<!-- Shared JavaScript -->
<script src="../shared/auth.js"></script>

</body>
</html>