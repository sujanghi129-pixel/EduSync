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

session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

/** @var Staff $staffClass */
$staffClass = new Staff(db());

/** @var array $staff */
$staff = $staffClass->getAll();

$total      = count($staff);
$active     = count(array_filter($staff, fn($s) => $s['isStaffActive']));
$inactive   = $total - $active;
$admins     = count(array_filter($staff, fn($s) => $s['role'] === 'Administrator'));
$teachers   = count(array_filter($staff, fn($s) => $s['role'] === 'Teacher'));
$headteachers = count(array_filter($staff, fn($s) => $s['role'] === 'Headteacher'));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Directory — EduSync</title>
<link rel="stylesheet" href="style.css">
<style>
  /* ── DIRECTORY PAGE ── */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 24px;
  }
  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px 18px;
  }
  .stat-card .stat-label {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 6px;
  }
  .stat-card .stat-value {
    font-size: 1.7rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
  }
  .stat-card .stat-sub {
    font-size: .72rem;
    color: var(--text-muted);
    margin-top: 4px;
  }

  /* ── STAFF CARD GRID ── */
  .staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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
    transition: border-color var(--dur), box-shadow var(--dur);
  }
  .staff-card:hover {
    border-color: var(--accent);
    box-shadow: 0 4px 18px rgba(96,165,250,.08);
  }
  .staff-card.inactive {
    opacity: .6;
  }

  /* Avatar circle with initials */
  .staff-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-weight: 700;
    font-size: 1.15rem;
    flex-shrink: 0;
    border: 2px solid var(--border);
  }
  .avatar-blue   { background: var(--accent-soft);   color: var(--accent); }
  .avatar-yellow { background: var(--warn-soft);     color: var(--warn); }
  .avatar-green  { background: var(--success-soft);  color: var(--success); }

  .staff-card-name {
    font-weight: 600;
    font-size: .92rem;
    color: var(--text);
    line-height: 1.3;
  }
  .staff-card-username {
    font-family: var(--font-mono);
    font-size: .76rem;
    color: var(--text-muted);
    background: var(--surface2);
    padding: 2px 8px;
    border-radius: 4px;
  }
  .staff-card-meta {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    width: 100%;
  }
  .staff-card-date {
    font-size: .72rem;
    color: var(--text-muted);
  }
  .staff-card-actions {
    display: flex;
    gap: 6px;
    width: 100%;
    justify-content: center;
    margin-top: 4px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
  }

  /* ── TOOLBAR ── */
  .dir-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
    flex-wrap: wrap;
  }
  .dir-toolbar .search-bar { flex: 1; min-width: 180px; }

  /* ── EMPTY ── */
  .dir-empty {
    text-align: center;
    padding: 64px 20px;
    color: var(--text-muted);
    grid-column: 1 / -1;
  }
  .dir-empty .empty-icon { font-size: 2.4rem; margin-bottom: 10px; }

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
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($_SESSION['user']['role']) ?> · <?= htmlspecialchars($_SESSION['user']['fullName']) ?></div>
    <div class="page-title">Staff Directory</div>
    <div class="page-sub">A full overview of all staff members and their roles.</div>

    <!-- ── STAT SUMMARY ── -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Staff</div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-sub">All accounts</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active</div>
        <div class="stat-value" style="color:var(--success);"><?= $active ?></div>
        <div class="stat-sub"><?= $inactive ?> inactive</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Teachers</div>
        <div class="stat-value" style="color:var(--warn);"><?= $teachers ?></div>
        <div class="stat-sub"><?= $headteachers ?> headteacher<?= $headteachers !== 1 ? 's' : '' ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Admins</div>
        <div class="stat-value" style="color:var(--accent);"><?= $admins ?></div>
        <div class="stat-sub">Administrator<?= $admins !== 1 ? 's' : '' ?></div>
      </div>
    </div>

    <!-- ── TOOLBAR ── -->
    <div class="dir-toolbar">
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="dirSearch" placeholder="Search name or username…">
      </div>
      <select class="form-select" id="dirRole" style="width:auto;min-width:140px;">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Teacher">Teacher</option>
        <option value="Headteacher">Headteacher</option>
      </select>
      <select class="form-select" id="dirStatus" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <a href="index.php" class="btn btn-ghost">← Back to Table</a>
    </div>
    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:16px;" id="dirCount">
      Showing <?= $total ?> staff member<?= $total !== 1 ? 's' : '' ?>
    </div>

    <!-- ── CARD GRID ── -->
    <div class="staff-grid" id="staffGrid">
      <?php if (empty($staff)): ?>
        <div class="dir-empty">
          <div class="empty-icon">👥</div>
          <p>No staff records found.</p>
        </div>
      <?php else: ?>
        <?php
          $avatarClass = [
            'Administrator' => 'avatar-blue',
            'Teacher'       => 'avatar-yellow',
            'Headteacher'   => 'avatar-green',
          ];
          $badgeClass = [
            'Administrator' => 'badge-blue',
            'Teacher'       => 'badge-yellow',
            'Headteacher'   => 'badge-green',
          ];
        ?>
        <?php foreach ($staff as $s):
          // Build initials from full name
          $parts    = explode(' ', trim($s['fullName']));
          $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        ?>
        <div
          class="staff-card <?= $s['isStaffActive'] ? '' : 'inactive' ?>"
          data-name="<?= strtolower(htmlspecialchars($s['fullName'])) ?>"
          data-username="<?= strtolower(htmlspecialchars($s['username'])) ?>"
          data-role="<?= htmlspecialchars($s['role']) ?>"
          data-active="<?= $s['isStaffActive'] ? '1' : '0' ?>"
        >
          <div class="staff-avatar <?= $avatarClass[$s['role']] ?? 'avatar-blue' ?>"><?= $initials ?></div>

          <div class="staff-card-name"><?= htmlspecialchars($s['fullName']) ?></div>
          <div class="staff-card-username"><?= htmlspecialchars($s['username']) ?></div>

          <div class="staff-card-meta">
            <span class="badge <?= $badgeClass[$s['role']] ?? 'badge-gray' ?>"><?= htmlspecialchars($s['role']) ?></span>
            <?php if ($s['isStaffActive']): ?>
              <span class="badge badge-green">● Active</span>
            <?php else: ?>
              <span class="badge badge-red">● Inactive</span>
            <?php endif; ?>
            <div class="staff-card-date">Joined <?= htmlspecialchars($s['staffCreatedAt']) ?></div>
          </div>

          <div class="staff-card-actions">
            <a href="edit.php?id=<?= $s['staffId'] ?>" class="btn btn-ghost btn-sm">Edit</a>
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
<script src="../shared/auth.js"></script>
<script>
/**
 * Live filter for the staff card grid.
 * Reads data-* attributes set on each .staff-card.
 */
(function () {
  const searchEl = document.getElementById('dirSearch');
  const roleEl   = document.getElementById('dirRole');
  const statusEl = document.getElementById('dirStatus');
  const countEl  = document.getElementById('dirCount');

  function filterCards() {
    const q      = searchEl.value.toLowerCase();
    const role   = roleEl.value;
    const status = statusEl.value;
    let   visible = 0;

    document.querySelectorAll('.staff-card').forEach(card => {
      const matchQ      = !q      || card.dataset.name.includes(q) || card.dataset.username.includes(q);
      const matchRole   = !role   || card.dataset.role   === role;
      const matchStatus = !status || card.dataset.active === status;
      const show        = matchQ && matchRole && matchStatus;

      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    countEl.textContent = 'Showing ' + visible + ' staff member' + (visible !== 1 ? 's' : '');
  }

  searchEl.addEventListener('input',  filterCards);
  roleEl.addEventListener('change',   filterCards);
  statusEl.addEventListener('change', filterCards);
}());
</script>
</body>
</html>