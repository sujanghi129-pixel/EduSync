<?php

/**
 * grades/edit.php
 *
 * Displays and processes the Edit Grade form.
 * Pre-fills the form with existing grade data and updates
 * the tblGrade record on valid POST submission.
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

$id    = (int)($_GET['id'] ?? 0);
$grade = $gradeClass->getById($id);

if (!$grade) { header('Location: index.php'); exit; }

$error = null;
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gradeName   = trim($_POST['gradeName']   ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$gradeName) {
        $error = 'Grade name is required.';
    } elseif ($gradeClass->nameExists($gradeName, $id)) {
        $error = "A grade named \"$gradeName\" already exists.";
    }

    if ($error) {
        $old = compact('gradeName', 'description');
    } else {
        $gradeClass->update($id, $gradeName, $description ?: null, 0);
        $_SESSION['toast'] = "Grade updated successfully.";
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Grade — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Edit Grade</div>
    <div class="page-sub">Update the year group name or description.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="edit.php?id=<?= $id ?>">

        <div class="form-group">
          <label class="form-label">Grade Name <span class="req">*</span></label>
          <input
            class="form-input"
            name="gradeName"
            placeholder="e.g. Year 1"
            value="<?= htmlspecialchars($old['gradeName'] ?? $grade['gradeName']) ?>"
            autofocus
          >
        </div>

        <div class="form-group">
          <label class="form-label">Description <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <input
            class="form-input"
            name="description"
            placeholder="e.g. First year of secondary school"
            value="<?= htmlspecialchars($old['description'] ?? $grade['description'] ?? '') ?>"
          >
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Grade</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
