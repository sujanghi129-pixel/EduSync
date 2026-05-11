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

session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/Staff.php';

/** @var Staff $staffClass - Middle layer instance */
$staffClass = new Staff(db());

/** @var array $staff - All staff records from the data layer */
$staff = $staffClass->getAll();

$toast      = $_SESSION['toast']       ?? null;
$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast'], $_SESSION['toast_error']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($_SESSION['user']['role']) ?> · <?= htmlspecialchars($_SESSION['user']['fullName']) ?></div>
    <div class="page-title">Staff Management</div>
    <div class="page-sub">Manage school staff accounts, roles and access control.</div>

    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">✅ <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">⚠️ <?= htmlspecialchars($toastError) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search name or username…">
      </div>
      <select class="form-select" id="roleFilter" style="width:auto;min-width:140px;">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Teacher">Teacher</option>
        <option value="Headteacher">Headteacher</option>
      </select>
      <select class="form-select" id="statusFilter" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <a href="add.php" class="btn btn-primary">+ Add Staff</a>
    </div>

    <div class="table-wrap">
      <table id="staffTable">
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
          <?php if (empty($staff)): ?>
            <tr><td colspan="7">
              <div class="empty-state">
                <div class="empty-icon">👥</div>
                <p>No staff records found.</p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php
              $roleColor = [
                'Administrator' => 'badge-blue',
                'Teacher'       => 'badge-yellow',
                'Headteacher'   => 'badge-green'
              ];
            ?>
            <?php foreach ($staff as $s): ?>
            <tr
              data-name="<?= strtolower(htmlspecialchars($s['fullName'])) ?>"
              data-username="<?= strtolower(htmlspecialchars($s['username'])) ?>"
              data-role="<?= htmlspecialchars($s['role']) ?>"
              data-active="<?= $s['isStaffActive'] ? '1' : '0' ?>"
            >
              <td><code><?= $s['staffId'] ?></code></td>
              <td><strong><?= htmlspecialchars($s['fullName']) ?></strong></td>
              <td><code><?= htmlspecialchars($s['username']) ?></code></td>
              <td><span class="badge <?= $roleColor[$s['role']] ?? 'badge-gray' ?>"><?= htmlspecialchars($s['role']) ?></span></td>
              <td><?= htmlspecialchars($s['staffCreatedAt']) ?></td>
              <td>
                <?php if ($s['isStaffActive']): ?>
                  <span class="badge badge-green">● Active</span>
                <?php else: ?>
                  <span class="badge badge-red">● Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-row">
                  <a href="edit.php?id=<?= $s['staffId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <form method="POST" action="toggle.php" style="display:inline;">
                    <input type="hidden" name="staffId" value="<?= $s['staffId'] ?>">
                    <button type="submit" class="btn btn-sm <?= $s['isStaffActive'] ? 'btn-danger' : 'btn-success' ?>">
                      <?= $s['isStaffActive'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                  <a href="delete.php?id=<?= $s['staffId'] ?>" class="btn btn-danger btn-sm">Delete</a>
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
