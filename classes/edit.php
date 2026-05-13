<?php

/**
 * classes/edit.php
 *
 * Displays and processes the Edit Class form.
 * Pre-fills form with existing class data and updates
 * the tblClass record on valid POST submission.
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

$id    = (int)($_GET['id'] ?? 0);
$stmt  = $pdo->prepare("SELECT * FROM tblClass WHERE classId = ?");
$stmt->execute([$id]);
$class = $stmt->fetch();
if (!$class) { header('Location: index.php'); exit; }

$grades = $pdo->query("SELECT gradeId, gradeName FROM tblGrade ORDER BY gradeId ASC")->fetchAll();
$staff  = $pdo->query("SELECT staffId, fullName, role FROM tblStaff WHERE isStaffActive = TRUE ORDER BY fullName ASC")->fetchAll();

$error = null;
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className      = trim($_POST['className']      ?? '');
    $gradeId        = (int)($_POST['gradeId']        ?? 0);
    $classTeacherID = (int)($_POST['classTeacherID'] ?? 0);

    if (!$className)          $error = 'Class name is required.';
    elseif (!$gradeId)        $error = 'Please select a grade.';
    elseif (!$classTeacherID) $error = 'Please select a class teacher.';
    else {
        // Check duplicate excluding self
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblClass WHERE className = ? AND gradeId = ? AND classId != ?");
        $stmt->execute([$className, $gradeId, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "A class named \"$className\" already exists in this grade.";
        }
    }

    if ($error) {
        $old = compact('className', 'gradeId', 'classTeacherID');
    } else {
        $stmt = $pdo->prepare("
            UPDATE tblClass SET className = ?, gradeId = ?, classTeacherID = ?
            WHERE classId = ?
        ");
        $stmt->execute([$className, $gradeId, $classTeacherID, $id]);
        $_SESSION['toast'] = "Class updated successfully.";
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
<title>Edit Class — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Edit Class</div>
    <div class="page-sub">Update class details, grade or assigned teacher.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="edit.php?id=<?= $id ?>">

        <div class="form-group">
          <label class="form-label">Class Name <span class="req">*</span></label>
          <input
            class="form-input"
            name="className"
            placeholder="e.g. 1A, 2B"
            value="<?= htmlspecialchars($old['className'] ?? $class['className']) ?>"
            autofocus
          >
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Grade <span class="req">*</span></label>
            <select class="form-select" name="gradeId">
              <option value="">Select grade…</option>
              <?php foreach ($grades as $g): ?>
                <?php $sel = ($old['gradeId'] ?? $class['gradeId']) == $g['gradeId'] ? 'selected' : ''; ?>
                <option value="<?= $g['gradeId'] ?>" <?= $sel ?>><?= htmlspecialchars($g['gradeName']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Class Teacher <span class="req">*</span></label>
            <select class="form-select" name="classTeacherID">
              <option value="">Select teacher…</option>
              <?php foreach ($staff as $s): ?>
                <?php $sel = ($old['classTeacherID'] ?? $class['classTeacherID']) == $s['staffId'] ? 'selected' : ''; ?>
                <option value="<?= $s['staffId'] ?>" <?= $sel ?>>
                  <?= htmlspecialchars($s['fullName']) ?> (<?= htmlspecialchars($s['role']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Class</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
