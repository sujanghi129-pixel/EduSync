<?php

/**
 * attendance/delete.php
 *
 * Displays a confirmation page before permanently deleting
 * a single attendance record. On POST confirmation, deletes
 * the record via the Attendance middle layer.
 *
 * Accessible by Administrator and Headteacher only.
 *
 * @package EduSync
 * @author  Laxman Giri
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator', 'Headteacher']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Attendance.php';

/** @var Attendance $attClass - Middle layer instance */
$attClass = new Attendance(db());

$id     = (int)($_GET['id'] ?? $_POST['attendanceId'] ?? 0);
$record = $attClass->getById($id);
if (!$record) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hard-delete directly — sp_DeleteAttendanceById does not exist in this project
    $pdo = db();
    $pdo->prepare("DELETE FROM tblAttendance WHERE attendanceId = ?")->execute([$id]);
    $_SESSION['toast'] = "Attendance record for \"{$record['studentName']}\" on "
        . date('d M Y', strtotime($record['date'])) . " deleted.";
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Attendance Record — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Delete Attendance Record</div>
    <div class="page-sub">Review before permanently removing this record.</div>

    <div class="card" style="max-width:480px;">

      <!-- Record summary -->
      <div style="padding:14px;background:var(--surface2);border-radius:var(--radius);margin-bottom:16px;">
        <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($record['studentName'] ?? '—') ?></div>
        <div style="color:var(--text-muted);font-size:.82rem;margin-top:2px;">
          <?= htmlspecialchars($record['gradeName'] ?? '') ?> —
          <?= htmlspecialchars($record['className'] ?? '') ?> ·
          <?= date('d M Y', strtotime($record['date'])) ?>
        </div>
        <div style="margin-top:8px;">
          <?php if ($record['status'] === 'present'): ?>
            <span class="badge badge-green">✓ Present</span>
          <?php elseif ($record['status'] === 'late'): ?>
            <span class="badge badge-yellow">⏱ Late</span>
          <?php else: ?>
            <span class="badge badge-red">✗ Absent</span>
          <?php endif; ?>
          <?php if ($record['notes']): ?>
            <span style="font-size:.82rem;color:var(--text-muted);margin-left:8px;"><?= htmlspecialchars($record['notes']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="callout callout-warn">
        <span>⚠️</span>
        <span>This will permanently delete the attendance record. This action cannot be undone.</span>
      </div>

      <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
        <a href="list.php" class="btn btn-ghost">Cancel</a>
        <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline;">
          <input type="hidden" name="attendanceId" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger">Delete Permanently</button>
        </form>
      </div>

    </div>
  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>