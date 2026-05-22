<?php

/**
 * attendance/edit.php
 *
 * Edit a single attendance record.
 * Allows correcting a student's status and remarks for a specific date.
 * Notes field is shown only when status is Late or Absent.
 *
 * On GET  — renders the edit form pre-filled with current values.
 * On POST — validates via attendance_validation.php, updates via middle layer.
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

$id     = (int)($_GET['id'] ?? 0);
$record = $attClass->getById($id);
if (!$record) {
    header('Location: list.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');

    $errors = validateAttendanceUpdate($status, $notes);

    if (!$errors) {
        if ($status === 'present') $notes = '';
        $attClass->update($id, $status, $notes);
        $_SESSION['toast'] = "Attendance updated for {$record['studentName']}.";
        header('Location: list.php');
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

      <?php if ($errors): ?>
        <?php foreach ($errors as $err): ?>
          <div class="callout callout-danger" style="margin-bottom:12px;">⚠️ <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
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
            <?php $cur = $_POST['status'] ?? $record['status']; ?>
            <label class="status-btn <?= $cur === 'present' ? 'active-present' : '' ?>">
              <input type="radio" name="status" value="present"
                <?= $cur === 'present' ? 'checked' : '' ?>> ✓ Present
            </label>
            <label class="status-btn <?= $cur === 'late' ? 'active-late' : '' ?>">
              <input type="radio" name="status" value="late"
                <?= $cur === 'late' ? 'checked' : '' ?>> ⏱ Late
            </label>
            <label class="status-btn <?= $cur === 'absent' ? 'active-absent' : '' ?>">
              <input type="radio" name="status" value="absent"
                <?= $cur === 'absent' ? 'checked' : '' ?>> ✗ Absent
            </label>
          </div>
        </div>

        <div class="form-group" id="notesGroup" style="<?= $cur === 'present' ? 'display:none;' : '' ?>">
          <label class="form-label">Remarks / Reason</label>
          <input
            class="form-input"
            type="text"
            name="notes"
            placeholder="Reason for late / absent"
            value="<?= htmlspecialchars($_POST['notes'] ?? $record['notes'] ?? '') ?>"
          >
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="list.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Record</button>
        </div>

      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
<script src="script.js"></script>
</body>
</html>
