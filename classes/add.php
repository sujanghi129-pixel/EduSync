<?php

/**
 * classes/add.php
 *
 * Displays and processes the Add Class form.
 * Validates input, checks for duplicate class names within the same grade
 * and inserts a new tblClass record on success.
 *
 * @package EduSync
 * @author  Saimon
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

// Load grades and active staff for dropdowns
$grades = $pdo->query("SELECT gradeId, gradeName FROM tblGrade ORDER BY gradeId ASC")->fetchAll();
$staff  = $pdo->query("SELECT staffId, fullName, role FROM tblStaff WHERE isStaffActive = TRUE ORDER BY fullName ASC")->fetchAll();

$error = null;
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className       = trim($_POST['className']       ?? '');
    $gradeId         = (int)($_POST['gradeId']         ?? 0);
    $classTeacherID  = (int)($_POST['classTeacherID']  ?? 0);

    // Validate
    if (!$className)      $error = 'Class name is required.';
    elseif (!$gradeId)    $error = 'Please select a grade.';
    elseif (!$classTeacherID) $error = 'Please select a class teacher.';
    else {
        // Check duplicate class name within same grade
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblClass WHERE className = ? AND gradeId = ?");
        $stmt->execute([$className, $gradeId]);
        if ($stmt->fetchColumn() > 0) {
            $error = "A class named \"$className\" already exists in this grade.";
        }
    }

    if ($error) {
        $old = compact('className', 'gradeId', 'classTeacherID');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tblClass (className, gradeId, classTeacherID, isClassActive, classCreatedAt)
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$className, $gradeId, $classTeacherID]);
        $_SESSION['toast'] = "Class \"$className\" created successfully.";
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
<title>Add Class — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Add Class</div>
    <div class="page-sub">Create a new class and assign it a grade and class teacher.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (empty($grades)): ?>
        <div class="callout callout-warn" style="margin-bottom:16px;">
          ⚠️ No grades found. <a href="../grades/add.php">Add a grade first</a> before creating a class.
        </div>
      <?php endif; ?>

      <form method="POST" action="add.php">

        <div class="form-group">
          <label class="form-label">Class Name <span class="req">*</span></label>
          <input
            class="form-input"
            name="className"
            placeholder="e.g. 1A, 2B, Year 1 Alpha"
            value="<?= htmlspecialchars($old['className'] ?? '') ?>"
            autofocus
          >
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Grade <span class="req">*</span></label>
            <select class="form-select" name="gradeId">
              <option value="">Select grade…</option>
              <?php foreach ($grades as $g): ?>
                <option value="<?= $g['gradeId'] ?>"
                  <?= ($old['gradeId'] ?? 0) == $g['gradeId'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($g['gradeName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Class Teacher <span class="req">*</span></label>
            <select class="form-select" name="classTeacherID">
              <option value="">Select teacher…</option>
              <?php foreach ($staff as $s): ?>
                <option value="<?= $s['staffId'] ?>"
                  <?= ($old['classTeacherID'] ?? 0) == $s['staffId'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['fullName']) ?> (<?= htmlspecialchars($s['role']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary" <?= empty($grades) ? 'disabled' : '' ?>>Save Class</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
