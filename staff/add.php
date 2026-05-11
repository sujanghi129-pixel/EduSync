<?php

/**
 * staff/add.php
 *
 * Presentation layer — Add New Staff form.
 * Uses the Staff middle layer class to validate and create records.
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

$error = null;
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // Validate inputs using middle layer methods
    if (!$fullName)                     $error = 'Full name is required.';
    elseif (!$username)                 $error = 'Username is required.';
    elseif (!$role)                     $error = 'Please select a role.';
    elseif (!$password)                 $error = 'Password is required for new accounts.';
    elseif (strlen($password) < 6)     $error = 'Password must be at least 6 characters.';
    elseif ($staffClass->usernameExists($username)) $error = "Username \"$username\" is already taken.";

    if ($error) {
        $old = compact('fullName', 'username', 'role');
    } else {
        // Create the record via the middle layer
        $staffClass->create($fullName, $username, $password, $role);
        $_SESSION['toast'] = "Staff account for \"$fullName\" created successfully.";
        header('Location: index.php');
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
<title>Add Staff — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow"><?= htmlspecialchars($_SESSION['user']['role']) ?> · <?= htmlspecialchars($_SESSION['user']['fullName']) ?></div>
    <div class="page-title">Add New Staff</div>
    <div class="page-sub">Create a new staff account and assign a role.</div>

    <div class="card" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="add.php">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="req">*</span></label>
            <input class="form-input" name="fullName" placeholder="e.g. Jane Smith"
              value="<?= htmlspecialchars($old['fullName'] ?? '') ?>" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Username <span class="req">*</span></label>
            <input class="form-input" name="username" placeholder="e.g. jane.smith"
              value="<?= htmlspecialchars($old['username'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password <span class="req">*</span></label>
            <input class="form-input" name="password" type="password" placeholder="Min 6 characters">
          </div>
          <div class="form-group">
            <label class="form-label">Role <span class="req">*</span></label>
            <select class="form-select" name="role">
              <option value="">Select role…</option>
              <?php foreach (['Administrator','Teacher','Headteacher'] as $r): ?>
                <option value="<?= $r ?>" <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Staff</button>
        </div>
      </form>
    </div>

  </main>
</div>
<script src="../shared/auth.js"></script>
</body>
</html>
