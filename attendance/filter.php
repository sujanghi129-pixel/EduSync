<?php

/**
 * attendance/filter.php
 *
 * Advanced Attendance Filter.
 * Provides granular filtering by class, grade, status, date range,
 * and marked-by staff member. Results display in the same report
 * table as list.php but with expanded filter controls.
 *
 * This file contains only filter + display logic — no writes.
 * All filter parameters come via GET so results are bookmarkable.
 *
 * @package EduSync
 * @author  Laxman Giri
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator', 'Teacher', 'Headteacher']);
$sessionUser = $_SESSION['user'];

require_once __DIR__ . '/../shared/db.php';

$pdo = db();

// Filter inputs
$fClass  = (int)($_GET['classId']  ?? 0);
$fGrade  = (int)($_GET['gradeId']  ?? 0);
$fStatus = $_GET['status']         ?? '';
$fFrom   = $_GET['from']           ?? '';
$fTo     = $_GET['to']             ?? '';
$fStaff  = (int)($_GET['staffId']  ?? 0);

// Dropdown data
$classes = $pdo->query("
    SELECT c.classId, c.className, g.gradeName, g.gradeId
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    WHERE c.isClassActive = TRUE
    ORDER BY g.gradeId ASC, c.className ASC
")->fetchAll();

$grades = $pdo->query("SELECT gradeId, gradeName FROM tblGrade ORDER BY gradeId ASC")->fetchAll();
$staff  = $pdo->query("SELECT staffId, fullName FROM tblStaff ORDER BY fullName ASC")->fetchAll();

// Build query dynamically
$where  = ['1=1'];
$params = [];

if ($fClass) { $where[] = 'a.classId = ?';  $params[] = $fClass; }
if ($fGrade) { $where[] = 'c.gradeId = ?';  $params[] = $fGrade; }
if ($fStatus && in_array($fStatus, ['present','absent','late'])) {
    $where[] = 'a.status = ?'; $params[] = $fStatus;
}
if ($fFrom)  { $where[] = 'a.date >= ?'; $params[] = $fFrom; }
if ($fTo)    { $where[] = 'a.date <= ?'; $params[] = $fTo;   }
if ($fStaff) { $where[] = 'a.markedById = ?'; $params[] = $fStaff; }

$whereSQL = implode(' AND ', $where);

$records = [];
$hasFilter = $fClass || $fGrade || $fStatus || $fFrom || $fTo || $fStaff;

if ($hasFilter) {
    $stmt = $pdo->prepare("
        SELECT
            a.attendanceId, a.date, a.status, a.notes,
            s.fullName   AS studentName,
            c.className,
            g.gradeName,
            st.fullName  AS markedByName
        FROM tblAttendance a
        LEFT JOIN tblStudent s  ON s.studentId  = a.studentId
        LEFT JOIN tblClass   c  ON c.classId    = a.classId
        LEFT JOIN tblGrade   g  ON g.gradeId    = c.gradeId
        LEFT JOIN tblStaff   st ON st.staffId   = a.markedById
        WHERE $whereSQL
        ORDER BY a.date DESC, g.gradeId ASC, c.className ASC, s.fullName ASC
    ");
    $stmt->execute($params);
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
<title>Advanced Filter — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($sessionUser['role']) ?> · <?= htmlspecialchars($sessionUser['fullName']) ?></div>
    <div class="page-title">Advanced Filter</div>
    <div class="page-sub">Combine any filters — results update on submit.</div>

    <!-- Advanced Filter Form -->
    <form method="GET" action="filter.php" class="card" style="max-width:800px;margin-bottom:24px;">
      <div class="form-row" style="margin-bottom:12px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Grade</label>
          <select class="form-select" name="gradeId">
            <option value="">All Grades</option>
            <?php foreach ($grades as $g): ?>
              <option value="<?= $g['gradeId'] ?>" <?= $fGrade === (int)$g['gradeId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['gradeName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Class</label>
          <select class="form-select" name="classId">
            <option value="">All Classes</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['classId'] ?>" <?= $fClass === (int)$c['classId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['gradeName'] . ' — ' . $c['className']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row" style="margin-bottom:12px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="">All Statuses</option>
            <option value="present" <?= $fStatus === 'present' ? 'selected' : '' ?>>✓ Present</option>
            <option value="late"    <?= $fStatus === 'late'    ? 'selected' : '' ?>>⏱ Late</option>
            <option value="absent"  <?= $fStatus === 'absent'  ? 'selected' : '' ?>>✗ Absent</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Marked By</label>
          <select class="form-select" name="staffId">
            <option value="">Any Staff</option>
            <?php foreach ($staff as $st): ?>
              <option value="<?= $st['staffId'] ?>" <?= $fStaff === (int)$st['staffId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($st['fullName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row" style="margin-bottom:14px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">From Date</label>
          <input class="form-input" type="date" name="from" value="<?= htmlspecialchars($fFrom) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">To Date</label>
          <input class="form-input" type="date" name="to" value="<?= htmlspecialchars($fTo) ?>">
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="filter.php" class="btn btn-ghost">Reset</a>
        <a href="list.php"   class="btn btn-ghost" style="margin-left:auto;">← Back to List</a>
      </div>
    </form>

    <?php if (!$hasFilter): ?>
      <div class="callout callout-info" style="background:var(--accent-soft);border-left:3px solid var(--accent);color:var(--text);">
        ℹ️ Select at least one filter above and click Apply Filters to see results.
      </div>
    <?php else: ?>

      <!-- Summary -->
      <?php if ($total > 0): ?>
        <div class="report-summary">
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

      <!-- Results Table -->
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Date</th><th>Student</th><th>Class</th><th>Grade</th><th>Status</th><th>Remarks</th><th>Marked By</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($records)): ?>
              <tr><td colspan="8">
                <div class="empty-state">
                  <div class="empty-icon">🔍</div>
                  <p>No records match the selected filters.</p>
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
                <td><?= htmlspecialchars($r['markedByName'] ?? '—') ?></td>
                <td>
                  <div class="action-row">
                    <a href="edit.php?id=<?= $r['attendanceId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <a href="delete.php?id=<?= $r['attendanceId'] ?>" class="btn btn-danger btn-sm">Delete</a>
                  </div>
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
