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

session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
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

$toast      = $_SESSION['toast'] ?? null;
$toastError = $_SESSION['toast_error'] ?? null;

unset($_SESSION['toast'], $_SESSION['toast_error']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Grades — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <div class="page-eyebrow">
      <?= htmlspecialchars($sessionUser['role']) ?>
      ·
      <?= htmlspecialchars($sessionUser['fullName']) ?>
    </div>

    <div class="page-title">Grades</div>

    <div class="page-sub">
      Manage year groups. Each grade can contain multiple classes.
    </div>

    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">
        ✅ <?= htmlspecialchars($toast) ?>
      </div>
    <?php endif; ?>

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

      <!-- Class Count Filter -->
      <select
        class="form-select"
        id="classFilter"
        style="width:auto; min-width:160px;"
      >
        <option value="">All Classes</option>
        <option value="0">No Classes</option>
        <option value="1">1+ Classes</option>
      </select>

      <!-- Status Filter -->
      <select
        class="form-select"
        id="statusFilter"
        style="width:auto; min-width:170px;"
      >
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <a href="add.php" class="btn btn-primary">
        + Add Grade
      </a>

    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table id="gradeTable">

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

          <?php foreach ($grades as $g): ?>

          <tr
            data-name="<?= strtolower(htmlspecialchars($g['gradeName'])) ?>"
            data-classcount="<?= $g['classCount'] ?>"
            data-status="<?= $g['classCount'] > 0 ? 'active' : 'inactive' ?>"
          >

            <!-- ID -->
            <td>
              <code><?= $g['gradeId'] ?></code>
            </td>

            <!-- Grade Name -->
            <td>
              <strong>
                <?= htmlspecialchars($g['gradeName']) ?>
              </strong>
            </td>

            <!-- Description -->
            <td>
              <?= htmlspecialchars($g['description'] ?? '—') ?>
            </td>

            <!-- Classes -->
            <td>
              <span class="badge badge-blue">
                <?= $g['classCount'] ?>
                class<?= $g['classCount'] != 1 ? 'es' : '' ?>
              </span>
            </td>

            <!-- Dynamic Status -->
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

            <!-- Created Date -->
            <td>
              <?= htmlspecialchars($g['gradeCreatedAt']) ?>
            </td>

            <!-- Actions -->
            <td>
              <div class="action-row">

                <a
                  href="edit.php?id=<?= $g['gradeId'] ?>"
                  class="btn btn-ghost btn-sm"
                >
                  Edit
                </a>

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

<script src="../shared/auth.js"></script>

<script>
const searchInput =
    document.getElementById('searchInput');

const classFilter =
    document.getElementById('classFilter');

const statusFilter =
    document.getElementById('statusFilter');

const rows =
    document.querySelectorAll(
        '#gradeTable tbody tr'
    );

function filterTable() {

    const searchValue =
        searchInput.value.toLowerCase();

    const classValue =
        classFilter.value;

    const statusValue =
        statusFilter.value;

    rows.forEach(row => {

        const name =
            row.dataset.name || '';

        const classCount =
            row.dataset.classcount || '';

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

        row.style.display =
            matchesSearch &&
            matchesClass &&
            matchesStatus
                ? ''
                : 'none';
    });
}

// Event listeners
searchInput.addEventListener(
    'input',
    filterTable
);

classFilter.addEventListener(
    'change',
    filterTable
);

statusFilter.addEventListener(
    'change',
    filterTable
);
</script>

</body>
</html>