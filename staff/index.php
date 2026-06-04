<?php

/**
 * staff/index.php
 *
 * Presentation layer — Staff Management list page.
 * Uses the Staff middle layer class to retrieve all staff records.
 * Requires Administrator role.
 *
 * USERNAME REMOVED:
 *   - data-username attribute changed to data-email on each <tr>
 *   - "Username" table header changed to "Email"
 *   - $s['username'] cell changed to $s['email']
 *   - Search placeholder updated to "Search name or email..."
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

// Instantiate the Staff model with a live PDO connection
$staffClass = new Staff(db());

// Retrieve all staff records from tblStaff via sp_GetAllStaff
$staff = $staffClass->getAll();

// Read one-shot toast messages from the session (set by add/edit/delete/toggle)
$toast      = $_SESSION['toast']       ?? null;
$toastError = $_SESSION['toast_error'] ?? null;

// Clear them immediately so they only display once
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

    <!-- Eyebrow: role · name of the logged-in user -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> ·
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <div class="page-title">Staff Management</div>
    <div class="page-sub">Manage school staff accounts, roles and access control.</div>

    <!-- Success toast (e.g. "Staff account created successfully") -->
    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">
        ✅ <?= htmlspecialchars($toast) ?>
      </div>
    <?php endif; ?>

    <!-- Error toast -->
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">
        ⚠️ <?= htmlspecialchars($toastError) ?>
      </div>
    <?php endif; ?>

    <!-- ── TOOLBAR ────────────────────────────────────────────────────────── -->
    <div class="toolbar">

      <!-- Search input — filters by fullName or email via script.js -->
      <div class="search-bar">
        <span>🔍</span>
        <input type="text"
               id="searchInput"
               placeholder="Search name or email…">
               <!-- CHANGED: was "Search name or username…" -->
      </div>

      <!-- Filter by role -->
      <select class="form-select" id="roleFilter" style="width:auto;min-width:140px;">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Teacher">Teacher</option>
        <option value="Headteacher">Headteacher</option>
      </select>

      <!-- Filter by active/inactive status -->
      <select class="form-select" id="statusFilter" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>

      <a href="list.php" class="btn btn-ghost">👥 View List</a>
      <a href="add.php"  class="btn btn-primary">+ Add Staff</a>

    </div>

    <!-- ── STAFF TABLE ────────────────────────────────────────────────────── -->
    <div class="table-wrap">
      <table id="staffTable">

        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th><!-- CHANGED: was "Username" -->
            <th>Role</th>
            <th>Created</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>

          <?php if (empty($staff)): ?>
            <!-- Empty state shown when no staff records exist in tblStaff -->
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
              // Badge colour classes mapped to each role value
              $roleColor = [
                'Administrator' => 'badge-blue',
                'Teacher'       => 'badge-yellow',
                'Headteacher'   => 'badge-green',
              ];
            ?>

            <?php foreach ($staff as $s): ?>

            <tr
              data-name="<?= strtolower(htmlspecialchars($s['fullName'])) ?>"
              data-email="<?= strtolower(htmlspecialchars($s['email'])) ?>"
              data-role="<?= htmlspecialchars($s['role']) ?>"
              data-active="<?= $s['isStaffActive'] ? '1' : '0' ?>"
            >

              <!-- Staff ID -->
              <td><code><?= $s['staffId'] ?></code></td>

              <!-- Full name -->
              <td><strong><?= htmlspecialchars($s['fullName']) ?></strong></td>

              <!-- Email address — CHANGED: was $s['username'] -->
              <td><code><?= htmlspecialchars($s['email']) ?></code></td>

              <!-- Role badge -->
              <td>
                <span class="badge <?= $roleColor[$s['role']] ?? 'badge-gray' ?>">
                  <?= htmlspecialchars($s['role']) ?>
                </span>
              </td>

              <!-- Account creation date/time -->
              <td><?= htmlspecialchars($s['staffCreatedAt']) ?></td>

              <!-- Active / Inactive status badge -->
              <td>
                <?php if ($s['isStaffActive']): ?>
                  <span class="badge badge-green">● Active</span>
                <?php else: ?>
                  <span class="badge badge-red">● Inactive</span>
                <?php endif; ?>
              </td>

              <!-- Action buttons: Edit, Activate/Deactivate, Delete -->
              <td>
                <div class="action-row">

                  <a href="edit.php?id=<?= $s['staffId'] ?>"
                     class="btn btn-ghost btn-sm">Edit</a>

                  <form method="POST" action="toggle.php" style="display:inline;">
                    <input type="hidden" name="staffId" value="<?= $s['staffId'] ?>">
                    <button type="submit"
                            class="btn btn-sm <?= $s['isStaffActive'] ? 'btn-danger' : 'btn-success' ?>">
                      <?= $s['isStaffActive'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>

                  <a href="delete.php?id=<?= $s['staffId'] ?>"
                     class="btn btn-danger btn-sm">Delete</a>

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

<script src="../shared/auth.js"></script>
<script src="script.js"></script>

</body>
</html>
