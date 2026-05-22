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

// Start the session so user login data and messages can be accessed
session_start();

// Include authentication functions and ensure only Administrators can access this page
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Include database connection and Staff class (middle layer)
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

/**
 * Create Staff class object using database connection
 * This object handles validation and staff record creation
 */
$staffClass = new Staff(db());

// Variables for error messages and old form data
$error = null;
$old   = [];

/**
 * Check if form was submitted using POST method
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize form inputs
    $fullName = trim($_POST['fullName'] ?? '');      // Remove spaces
    $username = strtolower(trim($_POST['username'] ?? '')); // Convert username to lowercase
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
    elseif (!$password)
        $error = 'Password is required for new accounts.';
    elseif (strlen($password) < 6)
        $error = 'Password must be at least 6 characters.';
    elseif ($staffClass->usernameExists($username))
        $error = "Username \"$username\" is already taken.";

    /**
     * If validation fails:
     * Store old input values so the form is repopulated
     */
    if ($error) {
        $old = compact('fullName', 'username', 'role');
    } else {

        /**
         * If validation passes:
         * Create new staff record in database
         */
        $staffClass->create($fullName, $username, $password, $role);

        // Set success message for next page
        $_SESSION['toast'] = "Staff account for \"$fullName\" created successfully.";

        // Redirect back to staff listing page
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">

<!-- Shared metadata (favicon, styles, etc.) -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Staff — EduSync</title>

<!-- Page-specific stylesheet -->
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Overlay used for responsive navigation/sidebar -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation bar -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar navigation -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Logged-in user information -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> · 
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <!-- Page heading -->
    <div class="page-title">Add New Staff</div>
    <div class="page-sub">Create a new staff account and assign a role.</div>

    <!-- Form container -->
    <div class="card" style="max-width:540px;">

      <!-- Show validation error if one exists -->
      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Staff creation form -->
      <form method="POST" action="add.php">

        <!-- First row: Full name and username -->
        <div class="form-row">

          <!-- Full Name input -->
          <div class="form-group">
            <label class="form-label">
              Full Name <span class="req">*</span>
            </label>
            <input class="form-input" 
                   name="fullName" 
                   placeholder="e.g. Jane Smith"
                   value="<?= htmlspecialchars($old['fullName'] ?? '') ?>" 
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
                   value="<?= htmlspecialchars($old['username'] ?? '') ?>">
          </div>

        </div>

        <!-- Second row: Password and role -->
        <div class="form-row">

          <!-- Password input -->
          <div class="form-group">
            <label class="form-label">
              Password <span class="req">*</span>
            </label>
            <input class="form-input" 
                   name="password" 
                   type="password" 
                   placeholder="Min 6 characters">
          </div>

          <!-- Role dropdown -->
          <div class="form-group">
            <label class="form-label">
              Role <span class="req">*</span>
            </label>
            <select class="form-select" name="role">

              <!-- Default empty option -->
              <option value="">Select role…</option>

              <!-- Generate role options dynamically -->
              <?php foreach (['Administrator','Teacher','Headteacher'] as $r): ?>
                <option value="<?= $r ?>" 
                  <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>>
                  <?= $r ?>
                </option>
              <?php endforeach; ?>

            </select>
          </div>

        </div>

        <!-- Form action buttons -->
        <div class="modal-footer" 
             style="padding:16px 0 0;
                    border-top:1px solid var(--border);
                    margin-top:8px;">

          <!-- Cancel button -->
          <a href="index.php" class="btn btn-ghost">Cancel</a>

          <!-- Submit button -->
          <button type="submit" class="btn btn-primary">
            Save Staff
          </button>
        </div>

      </form>
    </div>

  </main>
</div>

<!-- Shared JavaScript for authentication/navigation -->
<script src="../shared/auth.js"></script>

</body>
</html>