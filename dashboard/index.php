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

// Start (or resume) the session so $_SESSION is available throughout
session_start();

// Load auth helpers and enforce that the visitor is logged in (any role)
require_once __DIR__ . '/../shared/auth.php';
requireLogin();

// Shorthand references used throughout the file
$user = $_SESSION['user'];  // Full session array: staffId, fullName, username, role
$role = $user['role'];      // 'Administrator' | 'Teacher' | 'Headteacher'

// Load the database connection factory and obtain a PDO instance
require_once __DIR__ . '/../shared/db.php';
$pdo = db();

// Today's date in MySQL-compatible format — used in all attendance queries
$today = date('Y-m-d');


/* ══════════════════════════════════════════════════════════════
   ADMIN DATA BLOCK
   Runs only for Administrator role.
   Fetches system-wide counts and today's global attendance totals.
══════════════════════════════════════════════════════════════ */
if ($role === 'Administrator') {

    // ── SYSTEM-WIDE COUNTS ───────────────────────────────────────────────────
    // Each query counts only active records (soft-delete pattern).
    // fetchColumn() returns a single scalar value, avoiding a full fetch.

    $totalStaff    = $pdo->query("SELECT COUNT(*) FROM tblStaff    WHERE isStaffActive   = TRUE")->fetchColumn();
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM tblStudent  WHERE isStudentActive = TRUE")->fetchColumn();
    $totalClasses  = $pdo->query("SELECT COUNT(*) FROM tblClass    WHERE isClassActive   = TRUE")->fetchColumn();
    $totalGrades   = $pdo->query("SELECT COUNT(*) FROM tblGrade    WHERE isGradeActive   = TRUE")->fetchColumn();

    // ── TODAY'S ATTENDANCE (ALL CLASSES) ────────────────────────────────────
    // Three separate counts so each status can be displayed independently
    // and the attendance bar percentage can be calculated.
    $presentToday  = $pdo->query("SELECT COUNT(*) FROM tblAttendance WHERE date = '$today' AND status = 'present'")->fetchColumn();
    $absentToday   = $pdo->query("SELECT COUNT(*) FROM tblAttendance WHERE date = '$today' AND status = 'absent'")->fetchColumn();
    $lateToday     = $pdo->query("SELECT COUNT(*) FROM tblAttendance WHERE date = '$today' AND status = 'late'")->fetchColumn();

    // ── RECENTLY ADDED STAFF ─────────────────────────────────────────────────
    // Latest 5 staff members ordered by creation date descending,
    // shown in the "Recently Added Staff" card.
    $recentStaff = $pdo->query("
        SELECT fullName, role, staffCreatedAt
        FROM tblStaff
        ORDER BY staffCreatedAt DESC
        LIMIT 5
    ")->fetchAll();


/* ══════════════════════════════════════════════════════════════
   TEACHER / HEADTEACHER DATA BLOCK
   Runs for all non-Administrator roles.
   Data is scoped to the logged-in staff member's own class only.
══════════════════════════════════════════════════════════════ */
} else {

    // ── ASSIGNED CLASS ───────────────────────────────────────────────────────
    // Find the class where this staff member is the designated class teacher.
    // LEFT JOIN pulls in the grade name so we can display it without a second query.
    // LIMIT 1 guards against edge cases where a teacher is linked to multiple classes.
    $stmt = $pdo->prepare("
        SELECT c.classId, c.className, g.gradeName, g.gradeId
        FROM   tblClass c
        LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
        WHERE  c.classTeacherID = ?
          AND  c.isClassActive  = TRUE
        LIMIT 1
    ");
    $stmt->execute([$user['staffId']]);
    $assignedClass = $stmt->fetch();  // false if the teacher has no assigned class

    // ── STAFF PROFILE ────────────────────────────────────────────────────────
    // Fetch the full profile row so we can show staffCreatedAt ("Member since").
    // The session only stores a subset of fields, so a DB read is needed here.
    $stmt = $pdo->prepare("
        SELECT fullName, username, role, staffCreatedAt
        FROM   tblStaff
        WHERE  staffId = ?
    ");
    $stmt->execute([$user['staffId']]);
    $profile = $stmt->fetch();

    // ── TODAY'S CLASS ATTENDANCE ─────────────────────────────────────────────
    // Default all counts to zero; overwritten below if a class is assigned
    // and records exist. This prevents undefined-variable warnings in the view.
    $classAttendance = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];

    if ($assignedClass) {

        // GROUP BY status gives us all three counts in one query instead of three
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) AS cnt
            FROM   tblAttendance
            WHERE  classId = ?
              AND  date    = ?
            GROUP BY status
        ");
        $stmt->execute([$assignedClass['classId'], $today]);

        // Map each returned status row into the $classAttendance array
        foreach ($stmt->fetchAll() as $row) {
            $classAttendance[$row['status']] = (int)$row['cnt'];
        }

        // Derive the total from the three individual counts
        $classAttendance['total'] = $classAttendance['present']
                                  + $classAttendance['absent']
                                  + $classAttendance['late'];

        // ── TOTAL STUDENTS IN CLASS ──────────────────────────────────────────
        // Used in the attendance bar label: "X of Y students marked".
        // Counts only active students so withdrawn pupils don't skew the figure.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM   tblStudent
            WHERE  classId          = ?
              AND  isStudentActive  = TRUE
        ");
        $stmt->execute([$assignedClass['classId']]);
        $totalStudentsInClass = (int)$stmt->fetchColumn();
    }
}

// ── SESSION TOAST ────────────────────────────────────────────────────────────
// Read a one-shot error message set by another page (e.g. unauthorised access).
// Immediately unset it so it only displays once, not on every subsequent reload.
$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast_error']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; /* Shared <meta> tags (favicon, CSP, etc.) */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — EduSync</title>
<!-- Dashboard-scoped styles; design tokens (colours, spacing, radius) live in style.css -->
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Full-screen overlay used by sidebar/modal open states -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation bar — populated by shared/auth.js based on session role -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">
  <!-- Side navigation — populated by shared/auth.js -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Breadcrumb-style eyebrow: "Role · Full Name" -->
    <div class="page-eyebrow"><?= htmlspecialchars($role) ?> · <?= htmlspecialchars($user['fullName']) ?></div>
    <div class="page-title">Dashboard</div>
    <!-- First name only for a friendlier greeting; explode splits on the space -->
    <div class="page-sub">Welcome back, <?= htmlspecialchars(explode(' ', $user['fullName'])[0]) ?>.</div>

    <!-- ── ONE-SHOT ERROR TOAST ─────────────────────────────────────────────
         Rendered only when a previous page stored a toast_error in the session
         (e.g. trying to access a forbidden page). Cleared immediately in PHP above. -->
    <?php if ($toastError): ?>
      <div class="callout callout-danger" id="toastMsg" style="margin-bottom:20px;">
        🚫 <?= htmlspecialchars($toastError) ?>
      </div>
    <?php endif; ?>


    <?php if ($role === 'Administrator'): ?>
    <!-- ══════════════════════════════════════════════════════════════════════
         ADMINISTRATOR DASHBOARD
         Shows: 4 stat cards → attendance summary → recently added staff
    ══════════════════════════════════════════════════════════════════════ -->

      <!-- ── STAT CARDS ROW ────────────────────────────────────────────────
           4-column grid; each card links to its respective management page.
           stat-link adds a hover/cursor effect to the <a> wrapper. -->
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

      <!-- ── 2-COLUMN LOWER ROW ────────────────────────────────────────────
           Left: today's system-wide attendance | Right: recently added staff -->
      <div class="grid-2">

        <!-- TODAY'S ATTENDANCE CARD (system-wide) -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Today's Attendance</div>
            <a href="../attendance/index.php" class="btn btn-ghost btn-sm">Mark →</a>
          </div>

          <!-- Three coloured boxes: present (green), absent (red), late (yellow) -->
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
            <!-- Late box uses inline style because it doesn't have a dedicated att-* class -->
            <div class="att-item" style="flex:1;text-align:center;padding:14px 0;background:var(--warn-soft);border-radius:var(--radius);">
              <div class="att-count" style="color:var(--warn);"><?= $lateToday ?></div>
              <div class="att-label">Late</div>
            </div>
          </div>

          <?php
            // Calculate total records and the present percentage for the progress bar.
            // Guard against division-by-zero when no attendance has been marked yet.
            $attTotal = $presentToday + $absentToday + $lateToday;
            $attPct   = $attTotal > 0 ? round(($presentToday / $attTotal) * 100) : 0;
          ?>

          <!-- Progress bar: fill width is driven by the calculated percentage -->
          <div class="att-bar-wrap">
            <div class="att-bar">
              <div class="att-bar-fill" style="width:<?= $attPct ?>%"></div>
            </div>
            <div class="att-bar-label"><?= $attPct ?>% attendance rate today (all classes)</div>
          </div>

          <a href="../attendance/report.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">View Full Report →</a>
        </div><!-- /.card (attendance) -->


        <!-- RECENTLY ADDED STAFF CARD -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Recently Added Staff</div>
            <a href="../staff/add.php" class="btn btn-ghost btn-sm">+ Add</a>
          </div>

          <?php if (empty($recentStaff)): ?>
            <!-- Empty state: no staff records exist yet -->
            <div class="empty-state" style="padding:24px 0;">
              <div class="empty-icon">👥</div>
              <p>No staff added yet.</p>
            </div>

          <?php else: ?>
            <!-- Role → badge colour mapping used for each staff row -->
            <?php $roleColor = [
              'Administrator' => 'badge-blue',
              'Teacher'       => 'badge-yellow',
              'Headteacher'   => 'badge-green',
            ]; ?>

            <div class="recent-list">
              <?php foreach ($recentStaff as $s): ?>
              <div class="recent-item">
                <!-- Single-initial avatar: first character of the full name, uppercased -->
                <div class="recent-avatar"><?= strtoupper(substr($s['fullName'], 0, 1)) ?></div>
                <div class="recent-info">
                  <div class="recent-name"><?= htmlspecialchars($s['fullName']) ?></div>
                  <!-- Creation date shown as secondary metadata -->
                  <div class="recent-meta"><?= htmlspecialchars($s['staffCreatedAt']) ?></div>
                </div>
                <!-- Role badge; falls back to badge-gray for any unmapped role -->
                <span class="badge <?= $roleColor[$s['role']] ?? 'badge-gray' ?>">
                  <?= htmlspecialchars($s['role']) ?>
                </span>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div><!-- /.card (recent staff) -->

      </div><!-- /.grid-2 -->


    <?php else: ?>
    <!-- ══════════════════════════════════════════════════════════════════════
         TEACHER / HEADTEACHER DASHBOARD
         Shows: profile card + assigned class card → class attendance card
    ══════════════════════════════════════════════════════════════════════ -->

      <!-- ── TOP ROW: PROFILE + ASSIGNED CLASS ────────────────────────────── -->
      <div class="grid-2" style="margin-bottom:24px;">

        <!-- MY PROFILE CARD -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">My Profile</div>
          </div>

          <!-- Avatar circle + name/username header -->
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
            <!-- Initials avatar: first letter of full name in a coloured circle -->
            <div style="width:56px;height:56px;border-radius:50%;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;font-weight:700;font-size:1.2rem;flex-shrink:0;">
              <?= strtoupper(substr($user['fullName'], 0, 1)) ?>
            </div>
            <div>
              <div style="font-weight:700;font-size:1rem;color:var(--text);"><?= htmlspecialchars($user['fullName']) ?></div>
              <!-- @ prefix makes the username feel like a handle -->
              <div style="font-size:.82rem;color:var(--text-muted);">@<?= htmlspecialchars($user['username']) ?></div>
            </div>
          </div>

          <!-- Key–value profile rows, each separated by a bottom border -->
          <div style="display:flex;flex-direction:column;gap:8px;">

            <!-- Role row: badge colour differs between Teacher and Headteacher -->
            <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text-muted);">Role</span>
              <span class="badge <?= $role === 'Headteacher' ? 'badge-green' : 'badge-yellow' ?>"><?= htmlspecialchars($role) ?></span>
            </div>

            <!-- Username row: monospace <code> tag for visual distinction -->
            <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text-muted);">Username</span>
              <code><?= htmlspecialchars($user['username']) ?></code>
            </div>

            <!-- Member since: falls back to em-dash if profile row is missing -->
            <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;">
              <span style="color:var(--text-muted);">Member since</span>
              <span style="color:var(--text);"><?= htmlspecialchars($profile['staffCreatedAt'] ?? '—') ?></span>
            </div>
          </div>
        </div><!-- /.card (profile) -->


        <!-- MY ASSIGNED CLASS CARD -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">My Assigned Class</div>
            <?php if ($assignedClass): ?>
              <!-- Quick-link to mark today's attendance for this class -->
              <a href="../attendance/index.php?classId=<?= $assignedClass['classId'] ?>&date=<?= $today ?>" class="btn btn-primary btn-sm">Mark Today →</a>
            <?php endif; ?>
          </div>

          <?php if (!$assignedClass): ?>
            <!-- Empty state: teacher not yet linked to any class -->
            <div class="empty-state" style="padding:24px 0;">
              <div class="empty-icon">🏫</div>
              <p>You are not assigned to any class yet.<br>Contact your Administrator.</p>
            </div>

          <?php else: ?>
            <!-- Class details as key–value rows -->
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">

              <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Class</span>
                <span class="badge badge-green"><?= htmlspecialchars($assignedClass['className']) ?></span>
              </div>

              <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Grade / Year</span>
                <span class="badge badge-blue"><?= htmlspecialchars($assignedClass['gradeName']) ?></span>
              </div>

              <!-- Total active students used as context for the attendance bar below -->
              <div style="display:flex;justify-content:space-between;font-size:.855rem;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Total Students</span>
                <strong style="color:var(--text);"><?= $totalStudentsInClass ?? 0 ?></strong>
              </div>
            </div>

            <a href="../my_class.php" class="btn btn-ghost btn-sm">View My Students →</a>
          <?php endif; ?>
        </div><!-- /.card (assigned class) -->

      </div><!-- /.grid-2 -->


      <!-- ── CLASS ATTENDANCE CARD ─────────────────────────────────────────
           Only rendered when this teacher has an assigned class.
           Shows present/late/absent counts + percentage progress bar. -->
      <?php if ($assignedClass): ?>
      <div class="card" style="max-width:560px;">
        <div class="card-header">
          <!-- Dynamic title includes the class name for clarity -->
          <div class="card-title">Today's Attendance — <?= htmlspecialchars($assignedClass['className']) ?></div>
          <!-- Human-readable date shown on the right of the header -->
          <span style="font-size:.78rem;color:var(--text-muted);"><?= date('d M Y') ?></span>
        </div>

        <?php if ($classAttendance['total'] === 0): ?>
          <!-- Warning callout: no records marked yet for today -->
          <div class="callout callout-warn" style="margin-bottom:0;">
            ⚠️ Attendance has not been marked yet for today.
            <a href="../attendance/index.php?classId=<?= $assignedClass['classId'] ?>&date=<?= $today ?>" style="margin-left:8px;font-weight:600;">Mark now →</a>
          </div>

        <?php else: ?>
          <!-- Attendance summary boxes (same pattern as the admin card) -->
          <div class="attendance-summary">
            <div class="att-item att-present">
              <div class="att-count"><?= $classAttendance['present'] ?></div>
              <div class="att-label">Present</div>
            </div>
            <div class="att-divider"></div>
            <!-- Late uses inline style; no dedicated att-late class in this theme -->
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

          <?php
            // Present percentage for the class-level progress bar.
            // Uses classAttendance['total'] (marked records), not totalStudentsInClass,
            // so the bar reflects the submitted data, not the class roster.
            $pct = $classAttendance['total'] > 0
                 ? round(($classAttendance['present'] / $classAttendance['total']) * 100)
                 : 0;
          ?>

          <!-- Progress bar + label showing marked count vs total enrolled -->
          <div class="att-bar-wrap" style="margin-top:12px;">
            <div class="att-bar">
              <div class="att-bar-fill" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="att-bar-label">
              <?= $pct ?>% attendance rate · <?= $classAttendance['total'] ?> of <?= $totalStudentsInClass ?? 0 ?> students marked
            </div>
          </div>

          <!-- Link to the filtered class attendance report -->
          <a href="../attendance/report.php?classId=<?= $assignedClass['classId'] ?>" class="btn btn-ghost btn-sm" style="margin-top:12px;">View Class Report →</a>
        <?php endif; ?>
      </div><!-- /.card (class attendance) -->
      <?php endif; ?>

    <?php endif; /* end role branch */ ?>

  </main>
</div><!-- /.app-layout -->

<!-- Shared auth script: renders topnav + sidebar based on session role -->
<script src="../shared/auth.js"></script>
</body>
</html>