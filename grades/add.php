<?php

/**
 * grades/add.php
 *
 * Displays and processes the Add Grade form.
 * Validates the grade name, checks for duplicates
 * and inserts a new tblGrade record on success.
 *
 * @package EduSync
 * @author  Roshni Karki
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';

$error = $_SESSION['error'] ?? null;
$old   = $_SESSION['old']   ?? [];
unset($_SESSION['error'], $_SESSION['old']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gradeName   = trim($_POST['gradeName']   ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate
    if (!$gradeName) {
        $error = 'Grade name is required.';
    } else {
        // Check for duplicate
        $pdo  = db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblGrade WHERE gradeName = ?");
        $stmt->execute([$gradeName]);
        if ($stmt->fetchColumn() > 0) {
            $error = "A grade named \"$gradeName\" already exists.";
        }
    }

    if ($error) {
        $old = compact('gradeName', 'description');
    } else {
        $pdo  = db();
        $stmt = $pdo->prepare("INSERT INTO tblGrade (gradeName, description, gradeCreatedAt) VALUES (?, ?, NOW())");
        $stmt->execute([$gradeName, $description ?: null]);
        $_SESSION['toast'] = "Grade \"$gradeName\" created successfully.";
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
<title>Add Grade — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Add Grade</div>
    <div class="page-sub">Create a new year group. Classes can be assigned to it afterwards.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="add.php">

        <div class="form-group">
          <label class="form-label">Grade Name <span class="req">*</span></label>
          <input
            class="form-input"
            name="gradeName"
            placeholder="e.g. Year 1"
            value="<?= htmlspecialchars($old['gradeName'] ?? '') ?>"
            autofocus
          >
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px;">
            Examples: Year 1, Year 2, Year 3…
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <input
            class="form-input"
            name="description"
            placeholder="e.g. First year of secondary school"
            value="<?= htmlspecialchars($old['description'] ?? '') ?>"
          >
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Grade</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
