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
    LEFT JOIN tblClass c ON c.gradeId = g.gradeId AND c.isClassActive = TRUE
    GROUP BY g.gradeId
    ORDER BY g.gradeId ASC
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
<title>Grades — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Grades</div>
    <div class="page-sub">Manage year groups. Each grade can contain multiple classes.</div>

    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">✅ <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">⚠️ <?= htmlspecialchars($toastError) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search grade name…">
      </div>
      <a href="add.php" class="btn btn-primary">+ Add Grade</a>
    </div>

    <div class="table-wrap">
      <table id="gradeTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Grade Name</th>
            <th>Description</th>
            <th>Classes</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($grades)): ?>
            <tr><td colspan="6">
              <div class="empty-state">
                <div class="empty-icon">🎓</div>
                <p>No grades found. Add your first grade to get started.</p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php foreach ($grades as $g): ?>
            <tr data-name="<?= strtolower(htmlspecialchars($g['gradeName'])) ?>">
              <td><code><?= $g['gradeId'] ?></code></td>
              <td><strong><?= htmlspecialchars($g['gradeName']) ?></strong></td>
              <td><?= htmlspecialchars($g['description'] ?? '—') ?></td>
              <td>
                <span class="badge badge-blue"><?= $g['classCount'] ?> class<?= $g['classCount'] != 1 ? 'es' : '' ?></span>
              </td>
              <td><?= htmlspecialchars($g['gradeCreatedAt']) ?></td>
              <td>
                <div class="action-row">
                  <a href="edit.php?id=<?= $g['gradeId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <a href="delete.php?id=<?= $g['gradeId'] ?>" class="btn btn-danger btn-sm">Delete</a>
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
