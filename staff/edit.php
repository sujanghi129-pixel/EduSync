<?php

/**
 * staff/edit.php
 *
 * Presentation layer — Edit Staff form.
 * Uses the Staff middle layer class to retrieve and update records.
 * Requires Administrator role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start session to access logged-in user data and toast messages
session_start();

// Include authentication functions and allow only Administrator users
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Include database connection and Staff middle-layer class
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

/**
 * Create Staff class object using database connection
 */
$staffClass = new Staff(db());

/**
 * Get staff ID from the URL
 * Convert to integer for safer handling
 */
$id = (int)($_GET['id'] ?? 0);

/**
 * Retrieve staff record from database
 */
$staff = $staffClass->getById($id);

/**
 * If staff record is not found, redirect to staff list page
 */
if (!$staff) {
    header('Location: index.php');
    exit;
}

// Variables for validation error and old submitted values
$error = null;
$old   = [];

/**
 * Check if the form has been submitted
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize submitted form values
    $fullName = trim($_POST['fullName'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    /**
     * Validate form inputs
     */
    if (!$fullName)
        $error = 'Full name is required.';
    elseif (!$username)
        $error = 'Username is required.';
    elseif (!$role)
        $error = 'Please select a role.';
    elseif ($password && strlen($password) < 6)
        $error = 'Password must be at least 6 characters.';
    elseif ($staffClass->usernameExists($username, $id))
        $error = "Username \"$username\" is already taken.";

    /**
     * If validation fails, keep submitted data in the form
     */
    if ($error) {
        $old = compact('fullName', 'username', 'role');
    } else {

        /**
         * Update staff record using middle-layer method
         * Password is optional; if blank, existing password remains unchanged
         */
        $staffClass->update($id, $fullName, $username, $role, $password);

        // Store success message in session
        $_SESSION['toast'] = "Staff account updated successfully.";

        // Redirect to staff listing page
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">

<!-- Shared meta file -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Staff — EduSync</title>

<!-- Staff page stylesheet -->
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Overlay used for responsive sidebar/menu -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation bar -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar navigation -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Display current logged-in user role and name -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> ·
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <!-- Page title and subtitle -->
    <div class="page-title">Edit Staff</div>
    <div class="page-sub">Update staff account details and role.</div>

    <!-- Form card -->
    <div class="card" style="max-width:540px;">

      <!-- Display validation error if any -->
      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Edit staff form -->
      <form method="POST" action="edit.php?id=<?= $id ?>">

        <!-- First row: Full name and username -->
        <div class="form-row">

          <!-- Full name input -->
          <div class="form-group">
            <label class="form-label">
              Full Name <span class="req">*</span>
            </label>
            <input class="form-input"
                   name="fullName"
                   placeholder="e.g. Jane Smith"
                   value="<?= htmlspecialchars($old['fullName'] ?? $staff['fullName']) ?>"
                   autofocus>
          </div>

          <!-- Username input -->
          <div class="form-group">
            <label class="form-label">
              Username <span class="req">*</span>
            </label>
            <input class="form-input"
                   name="username"
                   placeholder="e.g. jane.smith"
                   value="<?= htmlspecialchars($old['username'] ?? $staff['username']) ?>">
          </div>

        </div>

        <!-- Second row: Password and role -->
        <div class="form-row">

          <!-- Optional password update -->
          <div class="form-group">
            <label class="form-label">Password</label>
            <input class="form-input"
                   name="password"
                   type="password"
                   placeholder="Leave blank to keep current">
          </div>

          <!-- Role dropdown -->
          <div class="form-group">
            <label class="form-label">
              Role <span class="req">*</span>
            </label>
            <select class="form-select" name="role">

              <!-- Default empty option -->
              <option value="">Select role…</option>

              <!-- Generate role options -->
              <?php foreach (['Administrator','Teacher','Headteacher'] as $r): ?>
                <?php
                  // Select submitted role if validation failed, otherwise current staff role
                  $sel = ($old['role'] ?? $staff['role']) === $r ? 'selected' : '';
                ?>
                <option value="<?= $r ?>" <?= $sel ?>>
                  <?= $r ?>
                </option>
              <?php endforeach; ?>

            </select>
          </div>

        </div>

        <!-- Form buttons -->
        <div class="modal-footer"
             style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">

          <!-- Cancel and return to staff list -->
          <a href="index.php" class="btn btn-ghost">Cancel</a>

          <!-- Submit updated staff details -->
          <button type="submit" class="btn btn-primary">
            Update Staff
          </button>

        </div>
      </form>
    </div>

  </main>
</div>

<!-- Shared authentication/navigation JavaScript -->
<script src="../shared/auth.js"></script>

</body>
</html>