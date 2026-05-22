<?php

/**
 * attendance/find.php
 *
 * Student Attendance Lookup.
 * Search for a specific student by name and view all their
 * attendance records with a per-status summary.
 *
 * Useful for parent queries, pastoral meetings, or spot checks.
 * Accessible by Administrator, Headteacher and Teacher.
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

$pdo      = db();
$attClass = new Attendance($pdo);

$query    = trim($_GET['q'] ?? '');
$students = [];
$records  = [];
$selected = null;
$studentId = (int)($_GET['studentId'] ?? 0);

// Search students by name
if ($query) {
    $stmt = $pdo->prepare("
        SELECT s.studentId, s.fullName, c.className, g.gradeName
        FROM tblStudent s
        LEFT JOIN tblClass c ON c.classId = s.classId
        LEFT JOIN tblGrade g ON g.gradeId = s.gradeId
        WHERE s.fullName LIKE ? AND s.isStudentActive = TRUE
        ORDER BY s.fullName ASC
        LIMIT 20
    ");
    $stmt->execute(['%' . $query . '%']);
    $students = $stmt->fetchAll();
}

// Load attendance history for selected student
if ($studentId) {
    $stmt = $pdo->prepare("
        SELECT s.studentId, s.fullName, c.className, g.gradeName
        FROM tblStudent s
        LEFT JOIN tblClass c ON c.classId = s.classId
        LEFT JOIN tblGrade g ON g.gradeId = s.gradeId
        WHERE s.studentId = ?
    ");
    $stmt->execute([$studentId]);
    $selected = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT a.attendanceId, a.date, a.status, a.notes,
               st.fullName AS markedByName
        FROM tblAttendance a
        LEFT JOIN tblStaff st ON st.staffId = a.markedById
        WHERE a.studentId = ?
        ORDER BY a.date DESC
    ");
    $stmt->execute([$studentId]);
    $records = $stmt->fetchAll();
}

// Summary
$summary = ['present' => 0, 'absent' => 0, 'late' => 0];
foreach ($records as $r) {
    if (isset($summary[$r['status']])) $summary[$r['status']]++;
}
$total = count($records);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find Student Attendance — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Find Student Attendance</div>
    <div class="page-sub">Search for a student to view their full attendance history.</div>

    <!-- Search form -->
    <form method="GET" action="find.php" class="card" style="max-width:500px;margin-bottom:24px;">
      <div class="form-group" style="margin-bottom:12px;">
        <label class="form-label">Student Name</label>
        <input class="form-input" type="text" name="q"
          value="<?= htmlspecialchars($query) ?>"
          placeholder="Type student name…" autofocus>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="find.php" class="btn btn-ghost">Reset</a>
        <a href="list.php" class="btn btn-ghost" style="margin-left:auto;">← Back to List</a>
      </div>
    </form>

    <!-- Search results -->
    <?php if ($query && !empty($students) && !$studentId): ?>
      <div class="table-wrap" style="max-width:600px;margin-bottom:24px;">
        <table>
          <thead>
            <tr><th>Student Name</th><th>Class</th><th>Grade</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['fullName']) ?></strong></td>
                <td><?= htmlspecialchars($s['className'] ?? '—') ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($s['gradeName'] ?? '—') ?></span></td>
                <td>
                  <a href="find.php?studentId=<?= $s['studentId'] ?>" class="btn btn-ghost btn-sm">View History</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php elseif ($query && empty($students) && !$studentId): ?>
      <div class="callout callout-warn">⚠️ No students found matching "<?= htmlspecialchars($query) ?>".</div>
    <?php endif; ?>

    <!-- Student attendance history -->
    <?php if ($selected): ?>
      <div style="margin-bottom:16px;">
        <strong><?= htmlspecialchars($selected['fullName']) ?></strong>
        <span style="color:var(--text-muted);font-size:.85rem;margin-left:8px;">
          <?= htmlspecialchars($selected['gradeName'] ?? '') ?> — <?= htmlspecialchars($selected['className'] ?? '') ?>
        </span>
      </div>

      <?php if ($total > 0): ?>
        <div class="report-summary" style="margin-bottom:20px;">
          <div class="summary-card"><div class="summary-value"><?= $total ?></div><div class="summary-label">Total</div></div>
          <div class="summary-card summary-present"><div class="summary-value"><?= $summary['present'] ?></div><div class="summary-label">Present</div></div>
          <div class="summary-card summary-late"><div class="summary-value"><?= $summary['late'] ?></div><div class="summary-label">Late</div></div>
          <div class="summary-card summary-absent"><div class="summary-value"><?= $summary['absent'] ?></div><div class="summary-label">Absent</div></div>
          <div class="summary-card">
            <div class="summary-value"><?= $total > 0 ? round(($summary['present'] / $total) * 100) : 0 ?>%</div>
            <div class="summary-label">Rate</div>
          </div>
        </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Date</th><th>Status</th><th>Remarks</th><th>Marked By</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($records)): ?>
              <tr><td colspan="5">
                <div class="empty-state">
                  <div class="empty-icon">📋</div>
                  <p>No attendance records found for this student.</p>
                </div>
              </td></tr>
            <?php else: ?>
              <?php foreach ($records as $r): ?>
              <tr>
                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td>
                  <?php if ($r['status'] === 'present'): ?>
                    <span class="badge badge-green">✓ Present</span>
                  <?php elseif ($r['status'] === 'late'): ?>
                    <span class="badge badge-yellow">⏱ Late</span>
                  <?php else: ?>
                    <span class="badge badge-red">✗ Absent</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['markedByName'] ?? '—') ?></td>
                <td>
                  <a href="edit.php?id=<?= $r['attendanceId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
