<?php

/**
 * attendance/report.php
 *
 * Displays the Attendance Report page.
 * Allows filtering by class, date range (from/to).
 * Shows summary cards for total records, present, late, absent counts
 * and overall attendance rate, followed by a detailed records table.
 *
 * @package EduSync
 * @author  Laxman Giri
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

// Filters
$filterClass = (int)($_GET['classId'] ?? 0);
$filterFrom  = $_GET['from'] ?? date('Y-m-01');   // default: first of current month
$filterTo    = $_GET['to']   ?? date('Y-m-d');     // default: today

// Load classes for filter dropdown
$classes = $pdo->query("
    SELECT c.classId, c.className, g.gradeName
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    WHERE c.isClassActive = TRUE
    ORDER BY g.gradeId ASC, c.className ASC
")->fetchAll();

// Build attendance query
$where  = ['1=1'];
$params = [];

if ($filterClass) {
    $where[]  = 'a.classId = ?';
    $params[] = $filterClass;
}
if ($filterFrom) {
    $where[]  = 'a.date >= ?';
    $params[] = $filterFrom;
}
if ($filterTo) {
    $where[]  = 'a.date <= ?';
    $params[] = $filterTo;
}

$whereSQL = implode(' AND ', $where);

$records = $pdo->prepare("
    SELECT
        a.attendanceId,
        a.date,
        a.status,
        a.notes,
        s.fullName   AS studentName,
        c.className,
        g.gradeName,
        st.fullName  AS markedByName
    FROM tblAttendance a
    LEFT JOIN tblStudent  s  ON s.studentId  = a.studentId
    LEFT JOIN tblClass    c  ON c.classId    = a.classId
    LEFT JOIN tblGrade    g  ON g.gradeId    = c.gradeId
    LEFT JOIN tblStaff    st ON st.staffId   = a.markedById
    WHERE $whereSQL
    ORDER BY a.date DESC, g.gradeId ASC, c.className ASC, s.fullName ASC
");
$records->execute($params);
$records = $records->fetchAll();

// Summary counts
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
<title>Attendance Report — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Attendance Report</div>
    <div class="page-sub">Filter by class and date range to review attendance records.</div>

    <!-- Filters -->
    <form method="GET" action="report.php" class="card" style="max-width:700px;margin-bottom:24px;">
      <div class="form-row" style="margin-bottom:12px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Class</label>
          <select class="form-select" name="classId">
            <option value="">All Classes</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['classId'] ?>"
                <?= $filterClass === (int)$c['classId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['gradeName'] . ' — ' . $c['className']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">From</label>
          <input class="form-input" type="date" name="from" value="<?= htmlspecialchars($filterFrom) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">To</label>
          <input class="form-input" type="date" name="to" value="<?= htmlspecialchars($filterTo) ?>">
        </div>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="report.php" class="btn btn-ghost">Reset</a>
        <a href="index.php" class="btn btn-ghost" style="margin-left:auto;">← Mark Attendance</a>
      </div>
    </form>

    <!-- Summary cards -->
    <?php if ($total > 0): ?>
    <div class="report-summary">
      <div class="summary-card">
        <div class="summary-value"><?= $total ?></div>
        <div class="summary-label">Total Records</div>
      </div>
      <div class="summary-card summary-present">
        <div class="summary-value"><?= $summary['present'] ?></div>
        <div class="summary-label">Present</div>
      </div>
      <div class="summary-card summary-late">
        <div class="summary-value"><?= $summary['late'] ?></div>
        <div class="summary-label">Late</div>
      </div>
      <div class="summary-card summary-absent">
        <div class="summary-value"><?= $summary['absent'] ?></div>
        <div class="summary-label">Absent</div>
      </div>
      <div class="summary-card">
        <div class="summary-value">
          <?= $total > 0 ? round(($summary['present'] / $total) * 100) : 0 ?>%
        </div>
        <div class="summary-label">Attendance Rate</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Records table -->
    <div class="table-wrap">
      <table id="reportTable">
        <thead>
          <tr>
            <th>Date</th>
            <th>Student</th>
            <th>Class</th>
            <th>Grade</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Marked By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="7">
              <div class="empty-state">
                <div class="empty-icon">📋</div>
                <p>No attendance records found for the selected filters.</p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php foreach ($records as $r): ?>
            <tr>
              <td><?= date('d M Y', strtotime($r['date'])) ?></td>
              <td><strong><?= htmlspecialchars($r['studentName'] ?? '—') ?></strong></td>
              <td><?= htmlspecialchars($r['className'] ?? '—') ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($r['gradeName'] ?? '—') ?></span></td>
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
              <td>
                <a href="edit.php?id=<?= $r['attendanceId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
