<?php

/**
 * classes/delete.php
 *
 * Displays the Delete Class confirmation page.
 * Blocks deletion if the class has active students enrolled.
 * Offers a Deactivate Instead option as a safer alternative.
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

$id    = (int)($_GET['id'] ?? $_POST['classId'] ?? 0);
$stmt  = $pdo->prepare("
    SELECT c.*, g.gradeName, s.fullName AS teacherName
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    LEFT JOIN tblStaff s ON s.staffId = c.classTeacherID
    WHERE c.classId = ?
");
$stmt->execute([$id]);
$class = $stmt->fetch();
if (!$class) { header('Location: index.php'); exit; }

// Count students enrolled in this class
$stmt         = $pdo->prepare("SELECT COUNT(*) FROM tblStudent WHERE classId = ? AND isStudentActive = TRUE");
$stmt->execute([$id]);
$studentCount = (int)$stmt->fetchColumn();
$hasStudents  = $studentCount > 0;

// Handle POST delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasStudents) {
        $_SESSION['toast_error'] = "Cannot delete: \"{$class['className']}\" has $studentCount active student(s) enrolled.";
        header("Location: delete.php?id=$id");
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM tblClass WHERE classId = ?");
    $stmt->execute([$id]);
    $_SESSION['toast'] = "Class \"{$class['className']}\" deleted.";
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
<title>Delete Class — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Delete Class</div>
    <div class="page-sub">Review before permanently removing this class.</div>

    <div class="card" style="max-width:480px;">

      <?php if ($toastError): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($toastError) ?></div>
      <?php endif; ?>

      <!-- Class summary -->
      <div style="padding:14px;background:var(--surface2);border-radius:var(--radius);margin-bottom:16px;">
        <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($class['className']) ?></div>
        <div style="color:var(--text-muted);font-size:.82rem;margin-top:2px;">
          Teacher: <?= htmlspecialchars($class['teacherName'] ?? '—') ?>
        </div>
        <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
          <span class="badge badge-green"><?= htmlspecialchars($class['gradeName'] ?? '—') ?></span>
          <span class="badge badge-blue"><?= $studentCount ?> student<?= $studentCount != 1 ? 's' : '' ?></span>
          <?php if ($class['isClassActive']): ?>
            <span class="badge badge-green">● Active</span>
          <?php else: ?>
            <span class="badge badge-red">● Inactive</span>
          <?php endif; ?>
        </div>
      </div>

      <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:14px;">
        Permanently delete <strong><?= htmlspecialchars($class['className']) ?></strong>? This cannot be undone.
      </p>

      <?php if ($hasStudents): ?>
        <div class="callout callout-danger">
          <span>🚫</span>
          <span>Cannot delete: this class has <strong><?= $studentCount ?> active student<?= $studentCount != 1 ? 's' : '' ?></strong> enrolled. Reassign or remove them first.</span>
        </div>
      <?php else: ?>
        <div class="callout callout-warn">
          <span>⚠️</span>
          <span>This class has no active students and is safe to delete.</span>
        </div>
      <?php endif; ?>

      <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:16px;">
        <a href="index.php" class="btn btn-ghost">Cancel</a>
        <?php if (!$hasStudents && $class['isClassActive']): ?>
          <form method="POST" action="toggle.php" style="display:inline;">
            <input type="hidden" name="classId" value="<?= $id ?>">
            <button type="submit" class="btn btn-ghost">Deactivate Instead</button>
          </form>
        <?php endif; ?>
        <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline;">
          <input type="hidden" name="classId" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger" <?= $hasStudents ? 'disabled' : '' ?>>
            Delete Permanently
          </button>
        </form>
      </div>

    </div>
  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
