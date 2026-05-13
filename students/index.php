<?php

/**
 * students/index.php
 *
 * Displays the Students list page.
 * Lists all students with name search, grade, class and status filters.
 * Requires an active session.
 *
 * @package EduSync
 * @author  Susma pandey
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Student.php';

/** @var Student $studentClass - Middle layer instance */
$studentClass = new Student(db());
$pdo = db(); // Still needed for grade/class dropdowns

$students = $pdo->query("
    SELECT
        s.studentId,
        s.fullName,
        s.dateOfBirth,
        s.isStudentActive,
        s.studentCreatedAt,
        g.gradeName,
        c.className
    FROM tblStudent s
    LEFT JOIN tblGrade g ON g.gradeId = s.gradeId
    LEFT JOIN tblClass c ON c.classId = s.classId
    ORDER BY g.gradeId ASC, c.className ASC, s.fullName ASC
")->fetchAll();

$toast      = $_SESSION['toast']       ?? null;
$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast'], $_SESSION['toast_error']);

// Load grades and classes for filter dropdowns
$grades  = $pdo->query("SELECT gradeId, gradeName FROM tblGrade ORDER BY gradeId ASC")->fetchAll();
$classes = $pdo->query("SELECT classId, className, gradeId FROM tblClass WHERE isClassActive = TRUE ORDER BY className ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Students</div>
    <div class="page-sub">Manage student enrolments, classes and grades.</div>

    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">✅ <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">⚠️ <?= htmlspecialchars($toastError) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search student name…">
      </div>
      <select class="form-select" id="gradeFilter" style="width:auto;min-width:130px;">
        <option value="">All Grades</option>
        <?php foreach ($grades as $g): ?>
          <option value="<?= htmlspecialchars($g['gradeName']) ?>"><?= htmlspecialchars($g['gradeName']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" id="classFilter" style="width:auto;min-width:130px;">
        <option value="">All Classes</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= htmlspecialchars($c['className']) ?>"><?= htmlspecialchars($c['className']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" id="statusFilter" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <a href="add.php" class="btn btn-primary">+ Add Student</a>
    </div>

    <div class="table-wrap">
      <table id="studentTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Date of Birth</th>
            <th>Grade</th>
            <th>Class</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="7">
              <div class="empty-state">
                <div class="empty-icon">🎓</div>
                <p>No students found. Add your first student to get started.</p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
            <tr
              data-name="<?= strtolower(htmlspecialchars($s['fullName'])) ?>"
              data-grade="<?= htmlspecialchars($s['gradeName'] ?? '') ?>"
              data-class="<?= htmlspecialchars($s['className'] ?? '') ?>"
              data-active="<?= $s['isStudentActive'] ? '1' : '0' ?>"
            >
              <td><code><?= $s['studentId'] ?></code></td>
              <td><strong><?= htmlspecialchars($s['fullName']) ?></strong></td>
              <td><?= date('d M Y', strtotime($s['dateOfBirth'])) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($s['gradeName'] ?? '—') ?></span></td>
              <td><span class="badge badge-green"><?= htmlspecialchars($s['className'] ?? '—') ?></span></td>
              <td>
                <?php if ($s['isStudentActive']): ?>
                  <span class="badge badge-green">● Active</span>
                <?php else: ?>
                  <span class="badge badge-red">● Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-row">
                  <a href="../student_profile.php?id=<?= $s['studentId'] ?>" class="btn btn-ghost btn-sm">View</a>
                  <a href="edit.php?id=<?= $s['studentId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <form method="POST" action="toggle.php" style="display:inline;">
                    <input type="hidden" name="studentId" value="<?= $s['studentId'] ?>">
                    <button type="submit" class="btn btn-sm <?= $s['isStudentActive'] ? 'btn-danger' : 'btn-success' ?>">
                      <?= $s['isStudentActive'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                  <a href="delete.php?id=<?= $s['studentId'] ?>" class="btn btn-danger btn-sm">Delete</a>
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
