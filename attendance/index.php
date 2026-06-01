<?php

/**
 * attendance/index.php
 *
 * Entry point for the Attendance module.
 * Displays the Mark Attendance page: select class and date,
 * then mark each student as Present, Late or Absent.
 * If attendance already exists for the selection, pre-fills
 * the form and warns before overwriting.
 *
 * Sub-pages:
 *   list.php   — View / filter attendance report
 *   add.php    — Save (mark) attendance (POST handler)
 *   edit.php   — Edit a single record
 *   delete.php — Delete a single record
 *   find.php   — Look up a student's attendance history
 *   filter.php — Advanced filter / export
 *   login/     — Login gate helpers
 *   validation/— Server-side validation helpers
 *
 * @package EduSync
 * @author  Laxman Giri
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator', 'Teacher', 'Headteacher']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Attendance.php';
require_once __DIR__ . '/validation/attendance_validation.php';

/** @var Attendance $attClass - Middle layer instance */
$attClass = new Attendance(db());
$pdo      = db();

// Only active classes appear — archived classes should not accept new marks
$classes = $pdo->query("
    SELECT c.classId, c.className, g.gradeName
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    WHERE c.isClassActive = TRUE
    ORDER BY g.gradeId ASC, c.className ASC
")->fetchAll();

// Read-once toast values: consume from session then clear to prevent replaying
$toast      = $_SESSION['toast']       ?? null;
$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast'], $_SESSION['toast_error']);

$selectedClass = (int)($_GET['classId'] ?? 0);
// Default to today so the teacher doesn't have to change the date each morning
$selectedDate  = $_GET['date'] ?? date('Y-m-d');

$existing      = [];
$alreadyMarked = false;
$students      = [];
$classInfo     = null;

if ($selectedClass && $selectedDate) {
    // Check whether records already exist for this class/date combination
    $existing      = $attClass->getByClassDate($selectedClass, $selectedDate);
    $alreadyMarked = !empty($existing);

    // Only active students appear — withdrawn students shouldn't be marked
    $stmt = $pdo->prepare("
        SELECT s.studentId, s.fullName
        FROM tblStudent s
        WHERE s.classId = ? AND s.isStudentActive = TRUE
        ORDER BY s.fullName ASC
    ");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();

    // Fetch class/grade name for the attendance header displayed above the table
    $stmt = $pdo->prepare("
        SELECT c.className, g.gradeName
        FROM tblClass c
        LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
        WHERE c.classId = ?
    ");
    $stmt->execute([$selectedClass]);
    $classInfo = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mark Attendance — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Mark Attendance</div>
    <div class="page-sub">Select a class and date, then mark each student.</div>

    <?php if ($toast): ?>
      <div class="callout callout-success" id="toastMsg">✅ <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg">⚠️ <?= htmlspecialchars($toastError) ?></div>
    <?php endif; ?>

    <!-- Class + Date selector — GET so refreshing the page keeps the selection -->
    <form method="GET" action="index.php" class="attendance-selector card" style="max-width:560px;margin-bottom:24px;">
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Class <span class="req">*</span></label>
          <select class="form-select" name="classId">
            <option value="">Select class…</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['classId'] ?>" <?= $selectedClass === (int)$c['classId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['gradeName'] . ' — ' . $c['className']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Date <span class="req">*</span></label>
          <!-- max=today prevents marking future attendance -->
          <input class="form-input" type="date" name="date"
            value="<?= htmlspecialchars($selectedDate) ?>"
            max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div style="margin-top:14px;">
        <button type="submit" class="btn btn-primary">Load Students</button>
        <a href="list.php" class="btn btn-ghost" style="margin-left:6px;">View Report →</a>
      </div>
    </form>

    <?php if ($selectedClass && $selectedDate): ?>

      <?php if (empty($students)): ?>
        <!-- Class exists but has no active students — guide the teacher to add them -->
        <div class="callout callout-warn">
          ⚠️ No active students found in this class.
          <a href="../students/add.php" style="margin-left:6px;">Add students →</a>
        </div>
      <?php else: ?>

        <div class="attendance-header">
          <div>
            <div class="attendance-class"><?= htmlspecialchars($classInfo['gradeName'] . ' — ' . $classInfo['className']) ?></div>
            <div class="attendance-date"><?= date('l, d F Y', strtotime($selectedDate)) ?></div>
          </div>
          <?php if ($alreadyMarked): ?>
            <!-- Warn the teacher — re-submitting will replace all existing records for this date -->
            <span class="badge badge-yellow">⚠️ Already marked — submitting will overwrite</span>
          <?php endif; ?>
        </div>

        <!-- POST to add.php; classId and date travel as hidden fields -->
        <form method="POST" action="add.php">
          <input type="hidden" name="classId" value="<?= $selectedClass ?>">
          <input type="hidden" name="date"    value="<?= htmlspecialchars($selectedDate) ?>">

          <!-- Quick-mark buttons call markAll() in script.js -->
          <div class="quick-mark">
            <span style="font-size:.8rem;color:var(--text-muted);font-weight:500;">Mark all as:</span>
            <button type="button" class="btn btn-success btn-sm" onclick="markAll('present')">✓ All Present</button>
            <button type="button" class="btn btn-danger  btn-sm" onclick="markAll('absent')">✗ All Absent</button>
            <button type="button" class="btn btn-ghost   btn-sm" onclick="markAll('late')">⏱ All Late</button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student Name</th>
                  <th>Status</th>
                  <th>Remarks / Reason</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $i => $s): ?>
                  <?php
                    // Pre-fill with existing data when re-marking; default to present
                    $currentStatus = $existing[$s['studentId']]['status'] ?? 'present';
                    $currentNotes  = $existing[$s['studentId']]['notes']  ?? '';
                  ?>
                  <!-- data-status is read by script.js to set the initial row colour -->
                  <tr class="att-row" data-status="<?= $currentStatus ?>">
                    <td style="width:40px;color:var(--text-muted);"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($s['fullName']) ?></strong></td>
                    <td>
                      <div class="status-toggle">
                        <label class="status-btn <?= $currentStatus === 'present' ? 'active-present' : '' ?>">
                          <input type="radio" name="status[<?= $s['studentId'] ?>]" value="present"
                            <?= $currentStatus === 'present' ? 'checked' : '' ?>> ✓ Present
                        </label>
                        <label class="status-btn <?= $currentStatus === 'late' ? 'active-late' : '' ?>">
                          <input type="radio" name="status[<?= $s['studentId'] ?>]" value="late"
                            <?= $currentStatus === 'late' ? 'checked' : '' ?>> ⏱ Late
                        </label>
                        <label class="status-btn <?= $currentStatus === 'absent' ? 'active-absent' : '' ?>">
                          <input type="radio" name="status[<?= $s['studentId'] ?>]" value="absent"
                            <?= $currentStatus === 'absent' ? 'checked' : '' ?>> ✗ Absent
                        </label>
                      </div>
                    </td>
                    <td>
                      <!-- Notes hidden for present; display toggled live by script.js -->
                      <input
                        type="text"
                        class="form-input notes-input"
                        name="notes[<?= $s['studentId'] ?>]"
                        placeholder="Reason for late / absent"
                        value="<?= htmlspecialchars($currentNotes) ?>"
                        style="min-width:200px;<?= $currentStatus === 'present' ? 'display:none;' : '' ?>"
                      >
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div style="display:flex;gap:10px;margin-top:16px;">
            <!-- Label changes to "Update" when re-marking an already-saved date -->
            <button type="submit" class="btn btn-primary">
              <?= $alreadyMarked ? 'Update Attendance' : 'Save Attendance' ?>
            </button>
            <a href="index.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>

      <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<script src="../shared/auth.js"></script>
<script src="script.js"></script>
</body>
</html>
