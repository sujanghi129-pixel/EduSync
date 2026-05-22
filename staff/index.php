<?php

/**
 * staff/index.php
 *
 * Presentation layer — Staff Management list page.
 * Uses the Staff middle layer class to retrieve all staff records.
 * Requires Administrator role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start session to access logged-in user data and session messages
session_start();

// Include authentication and restrict page access to Administrators only
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Include database connection and Staff middle-layer class
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/staff.php';

/**
 * Create Staff class object
 * Used to retrieve staff records from database
 */
$staffClass = new Staff(db());

/**
 * Get all staff records
 */
$staff = $staffClass->getAll();

/**
 * Retrieve success or error toast messages from session
 */
$toast      = $_SESSION['toast'] ?? null;
$toastError = $_SESSION['toast_error'] ?? null;

/**
 * Remove messages after displaying them once
 */
unset($_SESSION['toast'], $_SESSION['toast_error']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">

<!-- Shared metadata -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — EduSync</title>

<!-- Page stylesheet -->
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

    <!-- Display current logged-in user -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> ·
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <!-- Page heading -->
    <div class="page-title">Staff Management</div>
    <div class="page-sub">
      Manage school staff accounts, roles and access control.
    </div>

    <!-- Success message -->
    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">
        ✅ <?= htmlspecialchars($toast) ?>
      </div>
    <?php endif; ?>

    <!-- Error message -->
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">
        ⚠️ <?= htmlspecialchars($toastError) ?>
      </div>
    <?php endif; ?>

    <!-- Toolbar section -->
    <div class="toolbar">

      <!-- Search input -->
      <div class="search-bar">
        <span>🔍</span>
        <input type="text"
               id="searchInput"
               placeholder="Search name or username…">
      </div>

      <!-- Filter by role -->
      <select class="form-select" id="roleFilter" style="width:auto;min-width:140px;">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Teacher">Teacher</option>
        <option value="Headteacher">Headteacher</option>
      </select>

      <!-- Filter by status -->
      <select class="form-select" id="statusFilter" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>

      <!-- Navigation buttons -->
      <a href="list.php" class="btn btn-ghost">👥 View List</a>
      <a href="add.php" class="btn btn-primary">+ Add Staff</a>

    </div>

    <!-- Staff table container -->
    <div class="table-wrap">
      <table id="staffTable">

        <!-- Table headers -->
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>

          <!-- Empty state if no staff records exist -->
          <?php if (empty($staff)): ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <div class="empty-icon">👥</div>
                  <p>No staff records found.</p>
                </div>
              </td>
            </tr>

          <?php else: ?>

            <?php
              /**
               * Badge color classes for staff roles
               */
              $roleColor = [
                'Administrator' => 'badge-blue',
                'Teacher'       => 'badge-yellow',
                'Headteacher'   => 'badge-green'
              ];
            ?>

            <!-- Loop through each staff member -->
            <?php foreach ($staff as $s): ?>

            <tr
              data-name="<?= strtolower(htmlspecialchars($s['fullName'])) ?>"
              data-username="<?= strtolower(htmlspecialchars($s['username'])) ?>"
              data-role="<?= htmlspecialchars($s['role']) ?>"
              data-active="<?= $s['isStaffActive'] ? '1' : '0' ?>"
            >

              <!-- Staff ID -->
              <td><code><?= $s['staffId'] ?></code></td>

              <!-- Staff full name -->
              <td><strong><?= htmlspecialchars($s['fullName']) ?></strong></td>

              <!-- Username -->
              <td><code><?= htmlspecialchars($s['username']) ?></code></td>

              <!-- Role badge -->
              <td>
                <span class="badge <?= $roleColor[$s['role']] ?? 'badge-gray' ?>">
                  <?= htmlspecialchars($s['role']) ?>
                </span>
              </td>

              <!-- Account creation date -->
              <td><?= htmlspecialchars($s['staffCreatedAt']) ?></td>

              <!-- Active/Inactive status -->
              <td>
                <?php if ($s['isStaffActive']): ?>
                  <span class="badge badge-green">● Active</span>
                <?php else: ?>
                  <span class="badge badge-red">● Inactive</span>
                <?php endif; ?>
              </td>

              <!-- Action buttons -->
              <td>
                <div class="action-row">

                  <!-- Edit button -->
                  <a href="edit.php?id=<?= $s['staffId'] ?>"
                     class="btn btn-ghost btn-sm">
                     Edit
                  </a>

                  <!-- Activate/Deactivate form -->
                  <form method="POST"
                        action="toggle.php"
                        style="display:inline;">

                    <input type="hidden"
                           name="staffId"
                           value="<?= $s['staffId'] ?>">

                    <button type="submit"
                            class="btn btn-sm <?= $s['isStaffActive'] ? 'btn-danger' : 'btn-success' ?>">

                      <?= $s['isStaffActive'] ? 'Deactivate' : 'Activate' ?>

                    </button>
                  </form>

                  <!-- Delete button -->
                  <a href="delete.php?id=<?= $s['staffId'] ?>"
                     class="btn btn-danger btn-sm">
                     Delete
                  </a>

                </div>
              </td>

            </tr>

            <?php endforeach; ?>

          <?php endif; ?>

        </tbody>
      </table>
    </div>

  </main>
</div>

<!-- Shared scripts -->
<script src="../shared/auth.js"></script>

<!-- Staff page search/filter functionality -->
<script src="script.js"></script>

</body>
</html>