<?php

/**
 * staff/delete.php
 *
 * Presentation layer — Delete Staff confirmation page.
 * Uses the Staff middle layer class to check assignment and delete records.
 * Requires Administrator role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/Staff.php';

/** @var Staff $staffClass - Middle layer instance */
$staffClass = new Staff(db());

$id    = (int)($_GET['id'] ?? $_POST['staffId'] ?? 0);

/** @var array|false $staff - Staff record retrieved via middle layer */
$staff = $staffClass->getById($id);
if (!$staff) { header('Location: index.php'); exit; }

/** @var bool $hasClasses - Whether staff is assigned to an active class */
$hasClasses = $staffClass->isAssignedToClass($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasClasses) {
        $_SESSION['toast_error'] = "Cannot delete: {$staff['fullName']} is assigned to an active class.";
        header("Location: delete.php?id=$id");
        exit;
    }
    // Delete via middle layer
    $staffClass->delete($id);
    $_SESSION['toast'] = "Staff account \"{$staff['fullName']}\" deleted permanently.";
    header('Location: index.php');
    exit;
}

$toastError = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast_error']);
$roleColor  = ['Administrator'=>'badge-blue','Teacher'=>'badge-yellow','Headteacher'=>'badge-green'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../shared/meta.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Staff — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($_SESSION['user']['role']) ?> · <?= htmlspecialchars($_SESSION['user']['fullName']) ?></div>
    <div class="page-title">Delete Staff</div>
    <div class="page-sub">Review before permanently removing this account.</div>

    <div class="card" style="max-width:480px;">

      <?php if ($toastError): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($toastError) ?></div>
      <?php endif; ?>

      <div style="padding:14px;background:var(--surface2);border-radius:var(--radius);margin-bottom:16px;">
        <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($staff['fullName']) ?></div>
        <div style="color:var(--text-muted);font-size:.82rem;margin-top:2px;">
          <code><?= htmlspecialchars($staff['username']) ?></code>
        </div>
        <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
          <span class="badge <?= $roleColor[$staff['role']] ?? 'badge-gray' ?>"><?= htmlspecialchars($staff['role']) ?></span>
          <?php if ($staff['isStaffActive']): ?>
            <span class="badge badge-green">● Active</span>
          <?php else: ?>
            <span class="badge badge-red">● Inactive</span>
          <?php endif; ?>
        </div>
      </div>

      <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:14px;">
        Permanently delete <strong><?= htmlspecialchars($staff['fullName']) ?></strong>? This cannot be undone.
      </p>

      <?php if ($hasClasses): ?>
        <div class="callout callout-danger">
          <span>🚫</span>
          <span>Cannot delete: this staff member is assigned to an active class. Reassign the class teacher first.</span>
        </div>
      <?php else: ?>
        <div class="callout callout-warn">
          <span>⚠️</span>
          <span>Under GDPR, consider <strong>deactivating</strong> instead of deleting to preserve audit history.</span>
        </div>
      <?php endif; ?>

      <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:16px;">
        <a href="index.php" class="btn btn-ghost">Cancel</a>
        <?php if ($staff['isStaffActive'] && !$hasClasses): ?>
          <form method="POST" action="toggle.php" style="display:inline;">
            <input type="hidden" name="staffId" value="<?= $id ?>">
            <button type="submit" class="btn btn-ghost">Deactivate Instead</button>
          </form>
        <?php endif; ?>
        <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline;">
          <input type="hidden" name="staffId" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger" <?= $hasClasses ? 'disabled' : '' ?>>
            Delete Permanently
          </button>
        </form>
      </div>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
