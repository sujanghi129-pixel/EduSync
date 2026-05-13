<?php

/**
 * students/delete.php
 *
 * Displays the Delete Student confirmation page.
 * Warns if the student has existing attendance records,
 * which will also be deleted. Offers Deactivate Instead as an option.
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

$id   = (int)($_GET['id'] ?? $_POST['studentId'] ?? 0);
$stmt = $pdo->prepare("
    SELECT s.*, g.gradeName, c.className
    FROM tblStudent s
    LEFT JOIN tblGrade g ON g.gradeId = s.gradeId
    LEFT JOIN tblClass c ON c.classId = s.classId
    WHERE s.studentId = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit; }

// Check if student has attendance records
$stmt           = $pdo->prepare("SELECT COUNT(*) FROM tblAttendance WHERE studentId = ?");
$stmt->execute([$id]);
$attendanceCount = (int)$stmt->fetchColumn();
$hasAttendance   = $attendanceCount > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete attendance records first, then student
    $pdo->prepare("DELETE FROM tblAttendance WHERE studentId = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM tblStudent WHERE studentId = ?")->execute([$id]);
    $_SESSION['toast'] = "Student \"{$student['fullName']}\" deleted.";
    header('Location: index.php');
    exit;
}

$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast_error']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Student — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Delete Student</div>
    <div class="page-sub">Review before permanently removing this student.</div>

    <div class="card" style="max-width:480px;">

      <!-- Student summary -->
      <div style="padding:14px;background:var(--surface2);border-radius:var(--radius);margin-bottom:16px;">
        <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($student['fullName']) ?></div>
        <div style="color:var(--text-muted);font-size:.82rem;margin-top:2px;">
          DOB: <?= date('d M Y', strtotime($student['dateOfBirth'])) ?>
        </div>
        <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
          <span class="badge badge-blue"><?= htmlspecialchars($student['gradeName'] ?? '—') ?></span>
          <span class="badge badge-green"><?= htmlspecialchars($student['className'] ?? '—') ?></span>
          <?php if ($student['isStudentActive']): ?>
            <span class="badge badge-green">● Active</span>
          <?php else: ?>
            <span class="badge badge-red">● Inactive</span>
          <?php endif; ?>
        </div>
      </div>

      <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:14px;">
        Permanently delete <strong><?= htmlspecialchars($student['fullName']) ?></strong>? This cannot be undone.
      </p>

      <?php if ($hasAttendance): ?>
        <div class="callout callout-warn">
          <span>⚠️</span>
          <span>This student has <strong><?= $attendanceCount ?> attendance record(s)</strong>. These will also be deleted.</span>
        </div>
      <?php else: ?>
        <div class="callout callout-warn">
          <span>⚠️</span>
          <span>This student has no attendance records and is safe to delete.</span>
        </div>
      <?php endif; ?>

      <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:16px;">
        <a href="index.php" class="btn btn-ghost">Cancel</a>
        <?php if ($student['isStudentActive']): ?>
          <form method="POST" action="toggle.php" style="display:inline;">
            <input type="hidden" name="studentId" value="<?= $id ?>">
            <button type="submit" class="btn btn-ghost">Deactivate Instead</button>
          </form>
        <?php endif; ?>
        <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline;">
          <input type="hidden" name="studentId" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger">Delete Permanently</button>
        </form>
      </div>

    </div>
  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
