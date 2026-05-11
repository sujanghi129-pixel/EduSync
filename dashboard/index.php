<?php

/**
 * dashboard/index.php
 *
 * Role-based dashboard for the EduSync system.
 *
 * - Administrator: sees system-wide stats, today's attendance summary
 *                  and recently added staff.
 * - Teacher / Headteacher: sees their own profile, assigned class and grade,
 *                          and today's attendance for their class only.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

session_start();
require_once __DIR__ . '/../shared/auth.php';
requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$pdo  = (require_once __DIR__ . '/../shared/db.php') ?: null;

// Ensure db() is available
require_once __DIR__ . '/../shared/db.php';
$pdo = db();

$today = date('Y-m-d');

/* ══════════════════════════════════════════════
   ADMIN DATA
══════════════════════════════════════════════ */
if ($role === 'Administrator') {

    /**
     * System-wide statistics for the admin dashboard.
     */
    $totalStaff    = $pdo->query("SELECT COUNT(*) FROM tblStaff    WHERE isStaffActive   = TRUE")->fetchColumn();
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM tblStudent  WHERE isStudentActive = TRUE")->fetchColumn();
    $totalClasses  = $pdo->query("SELECT COUNT(*) FROM tblClass    WHERE isClassActive   = TRUE")->fetchColumn();
    $totalGrades   = $pdo->query("SELECT COUNT(*) FROM tblGrade    WHERE isGradeActive   = TRUE")->fetchColumn();

    $presentToday  = $pdo->query("SELECT COUNT(*) FROM tblAttendance WHERE date = '$today' AND status = 'present'")->fetchColumn();
    $absentToday   = $pdo->query("SELECT COUNT(*) FROM tblAttendance WHERE date = '$today' AND status = 'absent'")->fetchColumn();
    $lateToday     = $pdo->query("SELECT COUNT(*) FROM tblAttendance WHERE date = '$today' AND status = 'late'")->fetchColumn();

    $recentStaff   = $pdo->query("SELECT fullName, role, staffCreatedAt FROM tblStaff ORDER BY staffCreatedAt DESC LIMIT 5")->fetchAll();

/* ══════════════════════════════════════════════
   TEACHER / HEADTEACHER DATA
══════════════════════════════════════════════ */
} else {

    /**
     * Fetch the class assigned to this staff member as class teacher.
     *
     * @var array|false $assignedClass
     */
    $stmt = $pdo->prepare("
        SELECT c.classId, c.className, g.gradeName, g.gradeId
        FROM tblClass c
        LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
        WHERE c.classTeacherID = ? AND c.isClassActive = TRUE
        LIMIT 1
    ");
    $stmt->execute([$user['staffId']]);
    $assignedClass = $stmt->fetch();

    /**
     * Fetch staff profile details.
     *
     * @var array|false $profile
     */
    $stmt = $pdo->prepare("SELECT fullName, username, role, staffCreatedAt FROM tblStaff WHERE staffId = ?");
    $stmt->execute([$user['staffId']]);
    $profile = $stmt->fetch();

    /**
     * Fetch today's attendance for the assigned class.
     */
    $classAttendance = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
    if ($assignedClass) {
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as cnt
            FROM tblAttendance
            WHERE classId = ? AND date = ?
            GROUP BY status
        ");
        $stmt->execute([$assignedClass['classId'], $today]);
        foreach ($stmt->fetchAll() as $row) {
            $classAttendance[$row['status']] = (int)$row['cnt'];
        }
        $classAttendance['total'] = $classAttendance['present'] + $classAttendance['absent'] + $classAttendance['late'];

        /**
         * Count total students in the assigned class.
         *
         * @var int $totalStudentsInClass
         */
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblStudent WHERE classId = ? AND isStudentActive = TRUE");
        $stmt->execute([$assignedClass['classId']]);
        $totalStudentsInClass = (int)$stmt->fetchColumn();
    }
}

$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast_error']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($role) ?> · <?= htmlspecialchars($user['fullName']) ?></div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Welcome back, <?= htmlspecialchars(explode(' ', $user['fullName'])[0]) ?>.</div>

    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg" style="margin-bottom:20px;">
        🚫 <?= htmlspecialchars($toastError) ?>
      </div>
    <?php endif; ?>

    <?php if ($role === 'Administrator'): ?>
    <!-- ══════════════════════════════════════
         ADMIN DASHBOARD
    ══════════════════════════════════════ -->

      <!-- Stat cards -->
      <div class="grid-4" style="margin-bottom:28px;">
        <a href="../staff/index.php" class="stat-card stat-link">
          <div class="stat-icon">👤</div>
          <div class="stat-label">Active Staff</div>
          <div class="stat-value"><?= $totalStaff ?></div>
          <div class="stat-hint">View all staff →</div>
        </a>
        <a href="../students/index.php" class="stat-card stat-link">
          <div class="stat-icon">🎓</div>
          <div class="stat-label">Active Students</div>
          <div class="stat-value"><?= $totalStudents ?></div>
          <div class="stat-hint">View all students →</div>
        </a>
        <a href="../classes/index.php" class="stat-card stat-link">
          <div class="stat-icon">🏫</div>
          <div class="stat-label">Active Classes</div>
          <div class="stat-value"><?= $totalClasses ?></div>
          <div class="stat-hint">View all classes →</div>
        </a>
        <a href="../grades/index.php" class="stat-card stat-link">
          <div class="stat-icon">📚</div>
          <div class="stat-label">Active Grades</div>
          <div class="stat-value"><?= $totalGrades ?></div>
          <div class="stat-hint">View all grades →</div>
        </a>
      </div>

      <div class="grid-2">

        <!-- Today's system-wide attendance -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Today's Attendance</div>
            <a href="../attendance/index.php" class="btn btn-ghost btn-sm">Mark →</a>
          </div>
          <div class="attendance-summary">
            <div class="att-item att-present">
              <div class="att-count"><?= $presentToday ?></div>
              <div class="att-label">Present</div>
            </div>
            <div class="att-divider"></div>
            <div class="att-item att-absent">
              <div class="att-count"><?= $absentToday ?></div>
              <div class="att-label">Absent</div>
            </div>
            <div class="att-divider"></div>
            <div class="att-item" style="flex:1;text-align:center;padding:14px 0;background:var(--warn-soft);border-radius:var(--radius);">
              <div class="att-count" style="color:var(--warn);"><?= $lateToday ?></div>
              <div class="att-label">Late</div>
            </div>
          </div>
          <?php $attTotal = $presentToday + $absentToday + $lateToday;
                $attPct   = $attTotal > 0 ? round(($presentToday / $attTotal) * 100) : 0; ?>
          <div class="att-bar-wrap">
            <div class="att-bar"><div class="att-bar-fill" style="width:<?= $attPct ?>%"></div></div>
            <div class="att-bar-label"><?= $attPct ?>% attendance rate today (all classes)</div>
          </div>
          <a href="../attendance/report.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">View Full Report →</a>
        </div>

        <!-- Recently added staff -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Recently Added Staff</div>
            <a href="../staff/add.php" class="btn btn-ghost btn-sm">+ Add</a>
          </div>
          <?php if (empty($recentStaff)): ?>
            <div class="empty-state" style="padding:24px 0;">
              <div class="empty-icon">👥</div><p>No staff added yet.</p>
            </div>
          <?php else: ?>
            <?php $roleColor = ['Administrator'=>'badge-blue','Teacher'=>'badge-yellow','Headteacher'=>'badge-green']; ?>
            <div class="recent-list">
              <?php foreach ($recentStaff as $s): ?>
              <div class="recent-item">
                <div class="recent-avatar"><?= strtoupper(substr($s['fullName'], 0, 1)) ?></div>
                <div class="recent-info">
                  <div class="recent-name"><?= htmlspecialchars($s['fullName']) ?></div>
                  <div class="recent-meta"><?= htmlspecialchars($s['staffCreatedAt']) ?></div>
                </div>
                <span class="badge <?= $roleColor[$s['role']] ?? 'badge-gray' ?>"><?= htmlspecialchars($s['role']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

    <?php else: ?>
    <!-- ══════════════════════════════════════
         TEACHER / HEADTEACHER DASHBOARD
    ══════════════════════════════════════ -->

      <div class="grid-2" style="margin-bottom:24px;">

        <!-- Profile card -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">My Profile</div>
          </div>
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
            <div style="width:56px;height:56px;border-radius:50%;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;font-weight:700;font-size:1.2rem;flex-shrink:0;">
              <?= strtoupper(substr($user['fullName'], 0, 1)) ?>
            </div>
            <div>
              <div style="font-weight:700;font-size:1rem;color:var(--text);"><?= htmlspecialchars($user['fullName']) ?></div>
              <div style="font-size:.82rem;color:var(--text-muted);">@<?= htmlspecialchars($user['username']) ?></div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text-muted);">Role</span>
              <span class="badge <?= $role === 'Headteacher' ? 'badge-green' : 'badge-yellow' ?>"><?= htmlspecialchars($role) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text-muted);">Username</span>
              <code><?= htmlspecialchars($user['username']) ?></code>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;">
              <span style="color:var(--text-muted);">Member since</span>
              <span style="color:var(--text);"><?= htmlspecialchars($profile['staffCreatedAt'] ?? '—') ?></span>
            </div>
          </div>
        </div>

        <!-- Assigned class card -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">My Assigned Class</div>
            <?php if ($assignedClass): ?>
              <a href="../attendance/index.php?classId=<?= $assignedClass['classId'] ?>&date=<?= $today ?>" class="btn btn-primary btn-sm">Mark Today →</a>
            <?php endif; ?>
          </div>
          <?php if (!$assignedClass): ?>
            <div class="empty-state" style="padding:24px 0;">
              <div class="empty-icon">🏫</div>
              <p>You are not assigned to any class yet.<br>Contact your Administrator.</p>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
              <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Class</span>
                <span class="badge badge-green"><?= htmlspecialchars($assignedClass['className']) ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Grade / Year</span>
                <span class="badge badge-blue"><?= htmlspecialchars($assignedClass['gradeName']) ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Total Students</span>
                <strong style="color:var(--text);"><?= $totalStudentsInClass ?? 0 ?></strong>
              </div>
            </div>
            <a href="../my_class.php" class="btn btn-ghost btn-sm">View My Students →</a>
          <?php endif; ?>
        </div>

      </div>

      <!-- Today's attendance for assigned class -->
      <?php if ($assignedClass): ?>
      <div class="card" style="max-width:560px;">
        <div class="card-header">
          <div class="card-title">Today's Attendance — <?= htmlspecialchars($assignedClass['className']) ?></div>
          <span style="font-size:.78rem;color:var(--text-muted);"><?= date('d M Y') ?></span>
        </div>
        <?php if ($classAttendance['total'] === 0): ?>
          <div class="callout callout-warn" style="margin-bottom:0;">
            ⚠️ Attendance has not been marked yet for today.
            <a href="../attendance/index.php?classId=<?= $assignedClass['classId'] ?>&date=<?= $today ?>" style="margin-left:8px;font-weight:600;">Mark now →</a>
          </div>
        <?php else: ?>
          <div class="attendance-summary">
            <div class="att-item att-present">
              <div class="att-count"><?= $classAttendance['present'] ?></div>
              <div class="att-label">Present</div>
            </div>
            <div class="att-divider"></div>
            <div class="att-item" style="flex:1;text-align:center;padding:14px 0;background:var(--warn-soft);border-radius:var(--radius);">
              <div class="att-count" style="color:var(--warn);"><?= $classAttendance['late'] ?></div>
              <div class="att-label">Late</div>
            </div>
            <div class="att-divider"></div>
            <div class="att-item att-absent">
              <div class="att-count"><?= $classAttendance['absent'] ?></div>
              <div class="att-label">Absent</div>
            </div>
          </div>
          <?php $pct = $classAttendance['total'] > 0 ? round(($classAttendance['present'] / $classAttendance['total']) * 100) : 0; ?>
          <div class="att-bar-wrap" style="margin-top:12px;">
            <div class="att-bar"><div class="att-bar-fill" style="width:<?= $pct ?>%"></div></div>
            <div class="att-bar-label"><?= $pct ?>% attendance rate · <?= $classAttendance['total'] ?> of <?= $totalStudentsInClass ?? 0 ?> students marked</div>
          </div>
          <a href="../attendance/report.php?classId=<?= $assignedClass['classId'] ?>" class="btn btn-ghost btn-sm" style="margin-top:12px;">View Class Report →</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <?php endif; ?>

  </main>
</div>

<script src="../shared/auth.js"></script>
</body>
</html>
