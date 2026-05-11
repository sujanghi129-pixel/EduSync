<?php

/**
 * attendance/edit.php
 *
 * Displays and processes the Edit Attendance Record form.
 * Allows correcting a single student's attendance status and remarks.
 * The notes field is shown only when status is Late or Absent.
 *
 * @package EduSync
 * @author  Laxman Rai
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator', 'Teacher', 'Headteacher']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/Attendance.php';

/** @var Attendance $attClass - Middle layer instance */
$attClass = new Attendance(db());
$pdo = db(); // Still needed for class/student lookups

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT a.*, s.fullName AS studentName, c.className, g.gradeName
    FROM tblAttendance a
    LEFT JOIN tblStudent s ON s.studentId = a.studentId
    LEFT JOIN tblClass   c ON c.classId   = a.classId
    LEFT JOIN tblGrade   g ON g.gradeId   = c.gradeId
    WHERE a.attendanceId = ?
");$stmt->execute([$id]);
$record = $stmt->fetch();
if (!$record) { header('Location: report.php'); exit; }

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');
    $validStatuses = ['present', 'absent', 'late'];

    if (!in_array($status, $validStatuses)) {
        $error = 'Please select a valid status.';
    } else {
        if ($status === 'present') $notes = '';
        // Update via middle layer
        $attClass->update($id, $status, $notes);
        $_SESSION['toast'] = "Attendance updated for {$record['studentName']}.";
        header('Location: report.php');
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
<title>Edit Attendance — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Edit Attendance</div>
    <div class="page-sub">Correct a single attendance record.</div>

    <div class="card" style="max-width:460px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Record summary -->
      <div style="padding:12px;background:var(--surface2);border-radius:var(--radius);margin-bottom:20px;">
        <div style="font-weight:600;"><?= htmlspecialchars($record['studentName'] ?? '—') ?></div>
        <div style="font-size:.82rem;color:var(--text-muted);margin-top:2px;">
          <?= htmlspecialchars($record['gradeName'] ?? '') ?> —
          <?= htmlspecialchars($record['className'] ?? '') ?> ·
          <?= date('d M Y', strtotime($record['date'])) ?>
        </div>
      </div>

      <form method="POST" action="edit.php?id=<?= $id ?>">

        <div class="form-group">
          <label class="form-label">Attendance Status <span class="req">*</span></label>
          <div class="status-toggle" style="margin-top:6px;">
            <label class="status-btn <?= $record['status'] === 'present' ? 'active-present' : '' ?>">
              <input type="radio" name="status" value="present"
                <?= $record['status'] === 'present' ? 'checked' : '' ?>> ✓ Present
            </label>
            <label class="status-btn <?= $record['status'] === 'late' ? 'active-late' : '' ?>">
              <input type="radio" name="status" value="late"
                <?= $record['status'] === 'late' ? 'checked' : '' ?>> ⏱ Late
            </label>
            <label class="status-btn <?= $record['status'] === 'absent' ? 'active-absent' : '' ?>">
              <input type="radio" name="status" value="absent"
                <?= $record['status'] === 'absent' ? 'checked' : '' ?>> ✗ Absent
            </label>
          </div>
        </div>

        <div class="form-group" id="notesGroup" style="<?= $record['status'] === 'present' ? 'display:none;' : '' ?>">
          <label class="form-label">Remarks / Reason</label>
          <input
            class="form-input"
            type="text"
            name="notes"
            placeholder="Reason for late / absent"
            value="<?= htmlspecialchars($record['notes'] ?? '') ?>"
          >
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="report.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Record</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
