<?php

/**
 * students/add.php
 *
 * Displays and processes the Add Student (Enrol) form.
 * Validates input and inserts a new tblStudent record on success.
 * The class dropdown is dynamically populated via AJAX when a grade is selected.
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

$grades = $pdo->query("SELECT gradeId, gradeName FROM tblGrade WHERE isGradeActive = TRUE ORDER BY gradeId ASC")->fetchAll();

$error = null;
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['fullName']    ?? '');
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    $gradeId     = (int)($_POST['gradeId']    ?? 0);
    $classId     = (int)($_POST['classId']    ?? 0);

    if (!$fullName)                                           $error = 'Full name is required.';
    elseif (!$dateOfBirth)                                    $error = 'Date of birth is required.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) $error = 'Invalid date of birth.';
    elseif ($dateOfBirth > date('Y-m-d'))                    $error = 'Date of birth cannot be in the future.';
    elseif (!$gradeId)                                        $error = 'Please select a grade.';
    elseif (!$classId)                                        $error = 'Please select a class.';

    if ($error) {
        $old = compact('fullName', 'dateOfBirth', 'gradeId', 'classId');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tblStudent (fullName, dateOfBirth, gradeId, classId, isStudentActive, studentCreatedAt)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$fullName, $dateOfBirth, $gradeId, $classId]);
        $_SESSION['toast'] = "Student \"$fullName\" enrolled successfully.";
        header('Location: index.php');
        exit;
    }
}

// Load classes for selected grade (or all if no grade selected yet)
$selectedGrade = (int)($old['gradeId'] ?? 0);
$classes = [];
if ($selectedGrade) {
    $stmt = $pdo->prepare("SELECT classId, className FROM tblClass WHERE gradeId = ? AND isStudentActive = 1 ORDER BY className ASC");
    $stmt->execute([$selectedGrade]);
    $classes = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Add Student</div>
    <div class="page-sub">Enrol a new student and assign them to a grade and class.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="add.php">

        <div class="form-group">
          <label class="form-label">Full Name <span class="req">*</span></label>
          <input class="form-input" name="fullName" placeholder="e.g. Alice Jensen"
            value="<?= htmlspecialchars($old['fullName'] ?? '') ?>" autofocus>
        </div>

        <div class="form-group">
          <label class="form-label">Date of Birth <span class="req">*</span></label>
          <input class="form-input" type="date" name="dateOfBirth"
            value="<?= htmlspecialchars($old['dateOfBirth'] ?? '') ?>"
            max="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Grade <span class="req">*</span></label>
            <select class="form-select" name="gradeId" id="gradeSelect">
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
            <label class="form-label">Class <span class="req">*</span></label>
            <select class="form-select" name="classId" id="classSelect">
              <option value="">Select grade first…</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['classId'] ?>"
                  <?= ($old['classId'] ?? 0) == $c['classId'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['className']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Enrol Student</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
<script src="script.js"></script>
</body>
</html>
