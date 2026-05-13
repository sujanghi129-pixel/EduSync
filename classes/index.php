<?php

/**
 * classes/index.php
 *
 * Displays the Classes list page.
 * Lists all classes with grade, teacher, status filters and search.
 * Requires an active session.
 *
 * @package EduSync
 * @author  Saimon Shrestha
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/ClassModel.php';

/** @var ClassModel $classModel - Middle layer instance */
$classModel = new ClassModel(db());
$pdo = db(); // Still needed for grades/staff dropdowns

// Fetch all classes with grade name and teacher name
$classes = $pdo->query("
    SELECT
        c.classId,
        c.className,
        c.isClassActive,
        c.classCreatedAt,
        g.gradeName,
        CONCAT(s.fullName) AS teacherName,
        s.staffId
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    LEFT JOIN tblStaff s ON s.staffId = c.classTeacherID
    ORDER BY g.gradeId ASC, c.className ASC
")->fetchAll();

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
<title>Classes — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Classes</div>
    <div class="page-sub">Manage classes, assign grades and class teachers.</div>

    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">✅ <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">⚠️ <?= htmlspecialchars($toastError) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search class or teacher…">
      </div>
      <select class="form-select" id="gradeFilter" style="width:auto;min-width:140px;">
        <option value="">All Grades</option>
        <?php
          $grades = $pdo->query("SELECT gradeId, gradeName FROM tblGrade ORDER BY gradeId ASC")->fetchAll();
          foreach ($grades as $g):
        ?>
          <option value="<?= htmlspecialchars($g['gradeName']) ?>"><?= htmlspecialchars($g['gradeName']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" id="statusFilter" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <a href="add.php" class="btn btn-primary">+ Add Class</a>
    </div>

    <div class="table-wrap">
      <table id="classTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Class Name</th>
            <th>Grade</th>
            <th>Class Teacher</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($classes)): ?>
            <tr><td colspan="7">
              <div class="empty-state">
                <div class="empty-icon">🏫</div>
                <p>No classes found. Add your first class to get started.</p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php foreach ($classes as $c): ?>
            <tr
              data-name="<?= strtolower(htmlspecialchars($c['className'])) ?>"
              data-teacher="<?= strtolower(htmlspecialchars($c['teacherName'] ?? '')) ?>"
              data-grade="<?= htmlspecialchars($c['gradeName'] ?? '') ?>"
              data-active="<?= $c['isClassActive'] ? '1' : '0' ?>"
            >
              <td><code><?= $c['classId'] ?></code></td>
              <td><strong><?= htmlspecialchars($c['className']) ?></strong></td>
              <td><span class="badge badge-green"><?= htmlspecialchars($c['gradeName'] ?? '—') ?></span></td>
              <td><?= htmlspecialchars($c['teacherName'] ?? '—') ?></td>
              <td>
                <?php if ($c['isClassActive']): ?>
                  <span class="badge badge-green">● Active</span>
                <?php else: ?>
                  <span class="badge badge-red">● Inactive</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($c['classCreatedAt']) ?></td>
              <td>
                <div class="action-row">
                  <a href="edit.php?id=<?= $c['classId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <form method="POST" action="toggle.php" style="display:inline;">
                    <input type="hidden" name="classId" value="<?= $c['classId'] ?>">
                    <button type="submit" class="btn btn-sm <?= $c['isClassActive'] ? 'btn-danger' : 'btn-success' ?>">
                      <?= $c['isClassActive'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                  <a href="delete.php?id=<?= $c['classId'] ?>" class="btn btn-danger btn-sm">Delete</a>
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
