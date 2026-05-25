<?php

/**
 * staff/list.php
 *
 * Presentation layer — Staff Directory view.
 * Displays all staff members as profile cards.
 * Requires Administrator role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start the session to access $_SESSION variables (user role, name, etc.)
session_start();

// Load authentication helper and enforce Administrator-only access
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Load database connection factory and Staff model
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

/** @var Staff $staffClass — Instance of the Staff model */
$staffClass = new Staff(db());

/** @var array $staff — All staff records fetched from the database */
$staff = $staffClass->getAll();

// ── SUMMARY COUNTS ──────────────────────────────────────────────────────────
// Pre-compute totals for the stat cards at the top of the page

$total        = count($staff);

// Count staff where isStaffActive is truthy
$active       = count(array_filter($staff, fn($s) => $s['isStaffActive']));

$inactive     = $total - $active;

// Count by role for the stat cards
$admins       = count(array_filter($staff, fn($s) => $s['role'] === 'Administrator'));
$teachers     = count(array_filter($staff, fn($s) => $s['role'] === 'Teacher'));
$headteachers = count(array_filter($staff, fn($s) => $s['role'] === 'Headteacher'));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; /* Shared <meta> tags (favicon, CSP, etc.) */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Directory — EduSync</title>
<link rel="stylesheet" href="style.css"> <!-- Page-scoped styles; global design tokens live in style.css -->
<style>
  /* ════════════════════════════════════════════
     STAT SUMMARY ROW
     Four equal-width cards at the top of the page
     showing aggregate counts.
  ════════════════════════════════════════════ */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 equal columns on desktop */
    gap: 12px;
    margin-bottom: 24px;
  }
  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px 18px;
  }
  /* Small all-caps label above the number */
  .stat-card .stat-label {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 6px;
  }
  /* Large prominent number */
  .stat-card .stat-value {
    font-size: 1.7rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
  }
  /* Secondary note below the number, e.g. "3 inactive" */
  .stat-card .stat-sub {
    font-size: .72rem;
    color: var(--text-muted);
    margin-top: 4px;
  }

  /* ════════════════════════════════════════════
     STAFF CARD GRID
     Responsive auto-fill grid; each card shows
     one staff member's avatar, name, role badge,
     and action buttons.
  ════════════════════════════════════════════ */
  .staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); /* Fluid columns, min 220px */
    gap: 14px;
    margin-top: 20px;
  }
  .staff-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px 18px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
    transition: border-color var(--dur), box-shadow var(--dur); /* Smooth hover effect */
  }
  /* Subtle accent glow on hover */
  .staff-card:hover {
    border-color: var(--accent);
    box-shadow: 0 4px 18px rgba(96,165,250,.08);
  }
  /* Dim cards for inactive/deactivated staff */
  .staff-card.inactive {
    opacity: .6;
  }

  /* ── AVATAR CIRCLE ──────────────────────────
     Displays two-letter initials derived from
     the staff member's full name.
     Colour variants map to role:
       blue   → Administrator
       yellow → Teacher
       green  → Headteacher
  ──────────────────────────────────────────── */
  .staff-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: grid;
    place-items: center; /* Center initials both axes */
    font-weight: 700;
    font-size: 1.15rem;
    flex-shrink: 0;
    border: 2px solid var(--border);
  }
  .avatar-blue   { background: var(--accent-soft);   color: var(--accent); }
  .avatar-yellow { background: var(--warn-soft);     color: var(--warn); }
  .avatar-green  { background: var(--success-soft);  color: var(--success); }

  /* Staff full name, slightly smaller than a heading */
  .staff-card-name {
    font-weight: 600;
    font-size: .92rem;
    color: var(--text);
    line-height: 1.3;
  }
  /* Monospace pill showing the login username */
  .staff-card-username {
    font-family: var(--font-mono);
    font-size: .76rem;
    color: var(--text-muted);
    background: var(--surface2);
    padding: 2px 8px;
    border-radius: 4px;
  }
  /* Wrapper for badge + status + join date */
  .staff-card-meta {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    width: 100%;
  }
  /* Tiny "Joined YYYY-MM-DD" text */
  .staff-card-date {
    font-size: .72rem;
    color: var(--text-muted);
  }
  /* Edit / Activate|Deactivate buttons, separated by a top border */
  .staff-card-actions {
    display: flex;
    gap: 6px;
    width: 100%;
    justify-content: center;
    margin-top: 4px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
  }

  /* ════════════════════════════════════════════
     DIRECTORY TOOLBAR
     Search input + role filter + status filter
     + back link, all on one flex row.
  ════════════════════════════════════════════ */
  .dir-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
    flex-wrap: wrap; /* Stack gracefully on narrow viewports */
  }
  .dir-toolbar .search-bar { flex: 1; min-width: 180px; }

  /* ── EMPTY STATE ────────────────────────────
     Shown when no staff records exist in the DB.
  ──────────────────────────────────────────── */
  .dir-empty {
    text-align: center;
    padding: 64px 20px;
    color: var(--text-muted);
    grid-column: 1 / -1; /* Span all grid columns */
  }
  .dir-empty .empty-icon { font-size: 2.4rem; margin-bottom: 10px; }

  /* ── RESPONSIVE BREAKPOINTS ─────────────────
     Collapse the 4-column stat row to 2 on
     medium screens, and tighten card grid on
     very small screens.
  ──────────────────────────────────────────── */
  @media (max-width: 700px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 420px) {
    .stats-row { grid-template-columns: 1fr 1fr; }
    .staff-grid { grid-template-columns: 1fr 1fr; }
  }
</style>
</head>
<body>
<!-- Full-screen overlay used by the sidebar/modal system -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation bar — populated by shared/auth.js -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">
  <!-- Side navigation — populated by shared/auth.js -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Breadcrumb-style eyebrow: "Role · Full Name" -->
    <div class="page-eyebrow"><?= htmlspecialchars($_SESSION['user']['role']) ?> · <?= htmlspecialchars($_SESSION['user']['fullName']) ?></div>
    <div class="page-title">Staff Directory</div>
    <div class="page-sub">A full overview of all staff members and their roles.</div>

    <!-- ════════════════════════════════════════
         STAT SUMMARY CARDS
         Pulls from the PHP counts computed above.
    ════════════════════════════════════════ -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Staff</div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-sub">All accounts</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active</div>
        <!-- Green accent for active count -->
        <div class="stat-value" style="color:var(--success);"><?= $active ?></div>
        <div class="stat-sub"><?= $inactive ?> inactive</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Teachers</div>
        <!-- Warning/yellow accent for teachers -->
        <div class="stat-value" style="color:var(--warn);"><?= $teachers ?></div>
        <div class="stat-sub"><?= $headteachers ?> headteacher<?= $headteachers !== 1 ? 's' : '' ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Admins</div>
        <!-- Blue accent for admins -->
        <div class="stat-value" style="color:var(--accent);"><?= $admins ?></div>
        <div class="stat-sub">Administrator<?= $admins !== 1 ? 's' : '' ?></div>
      </div>
    </div>

    <!-- ════════════════════════════════════════
         FILTER TOOLBAR
         All filtering is done client-side via JS
         (no page reload needed).
    ════════════════════════════════════════ -->
    <div class="dir-toolbar">
      <!-- Text search: matches against data-name and data-username -->
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="dirSearch" placeholder="Search name or username…">
      </div>

      <!-- Role filter: maps to data-role on each card -->
      <select class="form-select" id="dirRole" style="width:auto;min-width:140px;">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Teacher">Teacher</option>
        <option value="Headteacher">Headteacher</option>
      </select>

      <!-- Status filter: maps to data-active ("1" = active, "0" = inactive) -->
      <select class="form-select" id="dirStatus" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>

      <!-- Return to the tabular staff list -->
      <a href="index.php" class="btn btn-ghost">← Back to Table</a>
    </div>

    <!-- Live count label updated by filterCards() in JS -->
    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:16px;" id="dirCount">
      Showing <?= $total ?> staff member<?= $total !== 1 ? 's' : '' ?>
    </div>

    <!-- ════════════════════════════════════════
         STAFF CARD GRID
         Each card carries data-* attributes so
         the JS filter can work without a round-trip.
    ════════════════════════════════════════ -->
    <div class="staff-grid" id="staffGrid">

      <?php if (empty($staff)): ?>
        <!-- Empty state: shown when there are zero staff records -->
        <div class="dir-empty">
          <div class="empty-icon">👥</div>
          <p>No staff records found.</p>
        </div>

      <?php else: ?>
        <?php
          // Map each role to its avatar colour class
          $avatarClass = [
            'Administrator' => 'avatar-blue',
            'Teacher'       => 'avatar-yellow',
            'Headteacher'   => 'avatar-green',
          ];

          // Map each role to its badge colour class
          $badgeClass = [
            'Administrator' => 'badge-blue',
            'Teacher'       => 'badge-yellow',
            'Headteacher'   => 'badge-green',
          ];
        ?>

        <?php foreach ($staff as $s):
          // Build two-letter initials: first letter of first name + first letter of last name
          $parts    = explode(' ', trim($s['fullName']));
          $initials = strtoupper(
            substr($parts[0], 0, 1) .                        // First name initial
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '') // Last name initial (if present)
          );
        ?>

        <!--
          data-name     → lowercase full name for search matching
          data-username → lowercase username for search matching
          data-role     → exact role string for the role dropdown
          data-active   → "1" or "0" for the status dropdown
        -->
        <div
          class="staff-card <?= $s['isStaffActive'] ? '' : 'inactive' ?>"
          data-name="<?= strtolower(htmlspecialchars($s['fullName'])) ?>"
          data-username="<?= strtolower(htmlspecialchars($s['username'])) ?>"
          data-role="<?= htmlspecialchars($s['role']) ?>"
          data-active="<?= $s['isStaffActive'] ? '1' : '0' ?>"
        >
          <!-- Coloured initials avatar; colour driven by role -->
          <div class="staff-avatar <?= $avatarClass[$s['role']] ?? 'avatar-blue' ?>"><?= $initials ?></div>

          <!-- Display name and monospace username pill -->
          <div class="staff-card-name"><?= htmlspecialchars($s['fullName']) ?></div>
          <div class="staff-card-username"><?= htmlspecialchars($s['username']) ?></div>

          <!-- Role badge, active/inactive badge, and join date -->
          <div class="staff-card-meta">
            <span class="badge <?= $badgeClass[$s['role']] ?? 'badge-gray' ?>"><?= htmlspecialchars($s['role']) ?></span>

            <?php if ($s['isStaffActive']): ?>
              <span class="badge badge-green">● Active</span>
            <?php else: ?>
              <span class="badge badge-red">● Inactive</span>
            <?php endif; ?>

            <div class="staff-card-date">Joined <?= htmlspecialchars($s['staffCreatedAt']) ?></div>
          </div>

          <!-- Action buttons: Edit navigates to edit.php; toggle POSTs to toggle.php -->
          <div class="staff-card-actions">
            <a href="edit.php?id=<?= $s['staffId'] ?>" class="btn btn-ghost btn-sm">Edit</a>

            <!--
              Inline mini-form for activate/deactivate toggle.
              Button label and colour swap based on current active state.
            -->
            <form method="POST" action="toggle.php" style="display:inline;">
              <input type="hidden" name="staffId" value="<?= $s['staffId'] ?>">
              <button type="submit" class="btn btn-sm <?= $s['isStaffActive'] ? 'btn-danger' : 'btn-success' ?>">
                <?= $s['isStaffActive'] ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Shared auth script: renders topnav + sidebar based on session role -->
<script src="../shared/auth.js"></script>

<script>
/**
 * Live client-side filter for the staff card grid.
 *
 * Reads data-* attributes set on each .staff-card during PHP rendering
 * and hides/shows cards instantly without a page reload.
 *
 * Filters applied:
 *   - Text search  → matches data-name  OR data-username (case-insensitive)
 *   - Role filter  → exact match on data-role
 *   - Status filter→ exact match on data-active ("1" | "0")
 *
 * All three filters are AND-combined; a card must satisfy every active
 * filter to remain visible.
 */
(function () {
  // Grab all filter controls and the live count label
  const searchEl = document.getElementById('dirSearch');
  const roleEl   = document.getElementById('dirRole');
  const statusEl = document.getElementById('dirStatus');
  const countEl  = document.getElementById('dirCount');

  /**
   * Iterates every .staff-card, evaluates the three filter conditions,
   * toggles visibility via display style, and updates the count label.
   */
  function filterCards() {
    const q      = searchEl.value.toLowerCase(); // Normalise search to lowercase
    const role   = roleEl.value;                 // "" means "no filter"
    const status = statusEl.value;               // "" | "1" | "0"
    let   visible = 0;

    document.querySelectorAll('.staff-card').forEach(card => {
      // Text match: empty query always passes
      const matchQ      = !q      || card.dataset.name.includes(q) || card.dataset.username.includes(q);

      // Role match: empty selection always passes
      const matchRole   = !role   || card.dataset.role   === role;

      // Status match: empty selection always passes
      const matchStatus = !status || card.dataset.active === status;

      // Card is visible only when ALL conditions are satisfied
      const show = matchQ && matchRole && matchStatus;

      card.style.display = show ? '' : 'none';
      if (show) visible++; // Tally for the count label
    });

    // Update the "Showing N staff member(s)" label
    countEl.textContent = 'Showing ' + visible + ' staff member' + (visible !== 1 ? 's' : '');
  }

  // Attach listeners — input fires on every keystroke; change fires on select pick
  searchEl.addEventListener('input',  filterCards);
  roleEl.addEventListener('change',   filterCards);
  statusEl.addEventListener('change', filterCards);
}());
</script>
</body>
</html>