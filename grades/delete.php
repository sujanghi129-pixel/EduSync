<?php

/**
 * grades/delete.php
 *
 * Displays the Delete Grade confirmation page.
 * Blocks deletion if the grade has active classes assigned to it.
 * Requires all classes to be reassigned or deleted first.
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/Grade.php';

/** @var Grade $gradeClass - Middle layer instance */
$gradeClass = new Grade(db());

$id    = (int)($_GET['id'] ?? $_POST['gradeId'] ?? 0);
$grade = $gradeClass->getById($id);

if (!$grade) { header('Location: index.php'); exit; }

/** @var int $classCount - Active classes in this grade */
$classCount  = $gradeClass->countClasses($id);
$hasClasses  = $classCount > 0;

/** @var int $studentCount - Active students in this grade */
$studentCount = $gradeClass->countStudents($id);
$hasStudents  = $studentCount > 0;

$blocked = $hasClasses || $hasStudents;

// Handle POST delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($blocked) {
        $reason = $hasClasses
            ? "Cannot delete: {$grade['gradeName']} has $classCount active class(es) assigned."
            : "Cannot delete: {$grade['gradeName']} has $studentCount active student(s) assigned.";
        $_SESSION['toast_error'] = $reason;
        header("Location: delete.php?id=$id");
        exit;
    }
    $gradeClass->delete($id);
    $_SESSION['toast'] = "Grade \"{$grade['gradeName']}\" deleted.";
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
<title>Delete Grade — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Delete Grade</div>
    <div class="page-sub">Review before permanently removing this grade.</div>

    <div class="card" style="max-width:480px;">

      <?php if ($toastError): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($toastError) ?></div>
      <?php endif; ?>

      <!-- Grade summary -->
      <div style="padding:14px;background:var(--surface2);border-radius:var(--radius);margin-bottom:16px;">
        <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($grade['gradeName']) ?></div>
        <?php if (!empty($grade['description'])): ?>
          <div style="color:var(--text-muted);font-size:.82rem;margin-top:2px;"><?= htmlspecialchars($grade['description']) ?></div>
        <?php endif; ?>
        <div style="margin-top:8px;">
          <span class="badge badge-blue"><?= $classCount ?> active class<?= $classCount != 1 ? 'es' : '' ?></span>
        </div>
      </div>

      <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:14px;">
        Permanently delete <strong><?= htmlspecialchars($grade['gradeName']) ?></strong>? This cannot be undone.
      </p>

      <?php if ($hasClasses): ?>
        <div class="callout callout-danger">
          <span>🚫</span>
          <span>Cannot delete: this grade has <strong><?= $classCount ?> active class<?= $classCount != 1 ? 'es' : '' ?></strong> assigned to it. Reassign or delete the classes first.</span>
        </div>
      <?php else: ?>
        <div class="callout callout-warn">
          <span>⚠️</span>
          <span>This grade has no active classes and is safe to delete.</span>
        </div>
      <?php endif; ?>

      <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:16px;">
        <a href="index.php" class="btn btn-ghost">Cancel</a>
        <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline;">
          <input type="hidden" name="gradeId" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger" <?= $hasClasses ? 'disabled' : '' ?>>
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
