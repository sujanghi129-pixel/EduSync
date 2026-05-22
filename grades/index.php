<?php

/**
 * grades/index.php
 *
 * Displays the Grades list page.
 * Shows all year groups with their active class count.
 * Requires an active session.
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */

// Start session
session_start();

// Check login and admin access
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);


// Get logged in user
$sessionUser = $_SESSION['user'];

// Load database
require_once __DIR__ . '/../shared/db.php';

// Database connection
$pdo = db();

// Fetch all grades with their class count
$grades = $pdo->query("
    SELECT g.*, COUNT(c.classId) AS classCount
    FROM tblGrade g
    LEFT JOIN tblClass c
        ON c.gradeId = g.gradeId
        AND c.isClassActive = TRUE
    GROUP BY g.gradeId
    ORDER BY g.gradeId ASC
")->fetchAll();

// Success message
$toast      = $_SESSION['toast'] ?? null;

// Error message
$toastError = $_SESSION['toast_error'] ?? null;

// Remove session messages
unset($_SESSION['toast'], $_SESSION['toast_error']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
<meta charset="UTF-8">

<!-- Shared meta file -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<!-- Responsive screen -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Page title -->
<title>Grades — EduSync</title>

<!-- CSS file -->
<link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Logged in user details -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($sessionUser['role']) ?>
      ·
      <?= htmlspecialchars($sessionUser['fullName']) ?>
    </div>

    <!-- Page title -->
    <div class="page-title">Grades</div>

    <!-- Page description -->
    <div class="page-sub">
      Manage year groups. Each grade can contain multiple classes.
    </div>

    <!-- Success toast message -->
    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">
        ✅ <?= htmlspecialchars($toast) ?>
      </div>
    <?php endif; ?>

    <!-- Error toast message -->
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">
        ⚠️ <?= htmlspecialchars($toastError) ?>
      </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">

      <!-- Search -->
      <div class="search-bar">
        <span>🔍</span>
        <input
          type="text"
          id="searchInput"
          placeholder="Search grade name..."
        >
      </div>

      <!-- Class count filter -->
      <select
        class="form-select"
        id="classFilter"
        style="width:auto; min-width:160px;"
      >
        <option value="">All Classes</option>
        <option value="0">No Classes</option>
        <option value="1">1+ Classes</option>
      </select>

      <!-- Status filter -->
      <select
        class="form-select"
        id="statusFilter"
        style="width:auto; min-width:170px;"
      >
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <!-- Add grade button -->
      <a href="add.php" class="btn btn-primary">
        + Add Grade
      </a>

    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table id="gradeTable">

        <!-- Table headings -->
        <thead>
          <tr>
            <th>ID</th>
            <th>Grade Name</th>
            <th>Description</th>
            <th>Classes</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>

        <!-- Show message if no grades -->
        <?php if (empty($grades)): ?>

          <tr>
            <td colspan="7">
              <div class="empty-state">
                <div class="empty-icon">🎓</div>
                <p>
                  No grades found.
                  Add your first grade to get started.
                </p>
              </div>
            </td>
          </tr>

        <?php else: ?>

          <!-- Loop through grades -->
          <?php foreach ($grades as $g): ?>

          <tr
            data-name="<?= strtolower(htmlspecialchars($g['gradeName'])) ?>"
            data-classcount="<?= $g['classCount'] ?>"
            data-status="<?= $g['classCount'] > 0 ? 'active' : 'inactive' ?>"
          >

            <!-- Grade ID -->
            <td>
              <code><?= $g['gradeId'] ?></code>
            </td>

            <!-- Grade name -->
            <td>
              <strong>
                <?= htmlspecialchars($g['gradeName']) ?>
              </strong>
            </td>

            <!-- Grade description -->
            <td>
              <?= htmlspecialchars($g['description'] ?? '—') ?>
            </td>

            <!-- Class count -->
            <td>
              <span class="badge badge-blue">
                <?= $g['classCount'] ?>
                class<?= $g['classCount'] != 1 ? 'es' : '' ?>
              </span>
            </td>

            <!-- Dynamic status -->
            <td>
              <?php if ($g['classCount'] > 0): ?>
                <span class="badge badge-green">
                  ● Active
                </span>
              <?php else: ?>
                <span class="badge badge-red">
                  ● Inactive
                </span>
              <?php endif; ?>
            </td>

            <!-- Created date -->
            <td>
              <?= htmlspecialchars($g['gradeCreatedAt']) ?>
            </td>

            <!-- Action buttons -->
            <td>
              <div class="action-row">

                <!-- Edit button -->
                <a
                  href="edit.php?id=<?= $g['gradeId'] ?>"
                  class="btn btn-ghost btn-sm"
                >
                  Edit
                </a>

                <!-- Delete button -->
                <a
                  href="delete.php?id=<?= $g['gradeId'] ?>"
                  class="btn btn-danger btn-sm"
                >
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

<!-- Shared JS -->
<script src="../shared/auth.js"></script>

<script>

// Search input
const searchInput =
    document.getElementById('searchInput');

// Class filter
const classFilter =
    document.getElementById('classFilter');

// Status filter
const statusFilter =
    document.getElementById('statusFilter');

// Table rows
const rows =
    document.querySelectorAll(
        '#gradeTable tbody tr'
    );

// Filter table function
function filterTable() {

    // Search text
    const searchValue =
        searchInput.value.toLowerCase();

    // Class filter value
    const classValue =
        classFilter.value;

    // Status filter value
    const statusValue =
        statusFilter.value;

    // Loop through rows
    rows.forEach(row => {

        // Grade name
        const name =
            row.dataset.name || '';

        // Class count
        const classCount =
            row.dataset.classcount || '';

        // Status
        const status =
            row.dataset.status || '';

        // Search filter
        const matchesSearch =
            name.includes(searchValue);

        // Class filter
        const matchesClass =
            classValue === '' ||
            (classValue === '0' &&
             classCount === '0') ||
            (classValue === '1' &&
             Number(classCount) > 0);

        // Status filter
        const matchesStatus =
            statusValue === '' ||
            status === statusValue;

        // Show or hide rows
        row.style.display =
            matchesSearch &&
            matchesClass &&
            matchesStatus
                ? ''
                : 'none';
    });
}

// Search event
searchInput.addEventListener(
    'input',
    filterTable
);

// Class filter event
classFilter.addEventListener(
    'change',
    filterTable
);

// Status filter event
statusFilter.addEventListener(
    'change',
    filterTable
);
</script>

</body>
</html>