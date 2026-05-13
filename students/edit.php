<?php

/**
 * students/edit.php
 *
 * Displays and processes the Edit Student form.
 * Pre-fills form with existing student data and updates
 * the tblStudent record on valid POST submission.
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

$id      = (int)($_GET['id'] ?? 0);
$stmt    = $pdo->prepare("SELECT * FROM tblStudent WHERE studentId = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit; }

$grades = $pdo->query("SELECT gradeId, gradeName FROM tblGrade WHERE isGradeActive = TRUE ORDER BY gradeId ASC")->fetchAll();

$error = null;
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['fullName']    ?? '');
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    $gradeId     = (int)($_POST['gradeId']    ?? 0);
    $classId     = (int)($_POST['classId']    ?? 0);

    if (!$fullName)                                               $error = 'Full name is required.';
    elseif (!$dateOfBirth)                                        $error = 'Date of birth is required.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth))  $error = 'Invalid date of birth.';
    elseif ($dateOfBirth > date('Y-m-d'))                        $error = 'Date of birth cannot be in the future.';
    elseif (!$gradeId)                                            $error = 'Please select a grade.';
    elseif (!$classId)                                            $error = 'Please select a class.';

    if ($error) {
        $old = compact('fullName', 'dateOfBirth', 'gradeId', 'classId');
    } else {
        $pdo->prepare("
            UPDATE tblStudent SET fullName=?, dateOfBirth=?, gradeId=?, classId=?
            WHERE studentId=?
        ")->execute([$fullName, $dateOfBirth, $gradeId, $classId, $id]);
        $_SESSION['toast'] = "Student updated successfully.";
        header('Location: index.php');
        exit;
    }
}

// Load classes for the current/selected grade
$currentGradeId = (int)($old['gradeId'] ?? $student['gradeId']);
$stmt = $pdo->prepare("
    SELECT classId, className
    FROM tblClass
    WHERE gradeId = ?
      AND isClassActive = 1
    ORDER BY className ASC
");$stmt->execute([$currentGradeId]);
$classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Student — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Edit Student</div>
    <div class="page-sub">Update student details, grade or class.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="edit.php?id=<?= $id ?>">

        <div class="form-group">
          <label class="form-label">Full Name <span class="req">*</span></label>
          <input class="form-input" name="fullName" placeholder="e.g. Alice Jensen"
            value="<?= htmlspecialchars($old['fullName'] ?? $student['fullName']) ?>" autofocus>
        </div>

        <div class="form-group">
          <label class="form-label">Date of Birth <span class="req">*</span></label>
          <input class="form-input" type="date" name="dateOfBirth"
            value="<?= htmlspecialchars($old['dateOfBirth'] ?? $student['dateOfBirth']) ?>"
            max="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Grade <span class="req">*</span></label>
            <select class="form-select" name="gradeId" id="gradeSelect">
              <option value="">Select grade…</option>
              <?php foreach ($grades as $g): ?>
                <?php $sel = ($old['gradeId'] ?? $student['gradeId']) == $g['gradeId'] ? 'selected' : ''; ?>
                <option value="<?= $g['gradeId'] ?>" <?= $sel ?>><?= htmlspecialchars($g['gradeName']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Class <span class="req">*</span></label>
            <select class="form-select" name="classId" id="classSelect">
              <?php foreach ($classes as $c): ?>
                <?php $sel = ($old['classId'] ?? $student['classId']) == $c['classId'] ? 'selected' : ''; ?>
                <option value="<?= $c['classId'] ?>" <?= $sel ?>><?= htmlspecialchars($c['className']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Student</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
<script src="script.js"></script>
</body>
</html>
