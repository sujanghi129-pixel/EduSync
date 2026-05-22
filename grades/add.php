<?php

/**
 * grades/add.php
 *
 * Displays and processes the Add Grade form.
 * Validates the grade name, checks for duplicates
 * and inserts a new tblGrade record on success.
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */

// Start session
session_start();

// Check login and admin access
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Get logged in user details
$sessionUser = $_SESSION['user'];

// Connect database
require_once __DIR__ . '/../shared/db.php';

// Get old error and form data
$error = $_SESSION['error'] ?? null;
$old   = $_SESSION['old']   ?? [];

// Clear old session data
unset($_SESSION['error'], $_SESSION['old']);

// Run when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form values
    $gradeName   = trim($_POST['gradeName']   ?? '');
    $description = trim($_POST['description'] ?? '');

    // validates if grade name is empty
    if (!$gradeName) {
        $error = 'Grade name is required.';
    } else {

        // validates if grade already exists
        $pdo  = db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblGrade WHERE gradeName = ?");
        $stmt->execute([$gradeName]);

        // Show error if duplicate found
        if ($stmt->fetchColumn() > 0) {
            $error = "A grade named \"$gradeName\" already exists.";
        }
    }

    // Save old values if error exists
    if ($error) {
        $old = compact('gradeName', 'description');
    } else {

        // Insert new grade into database
        $pdo  = db();
        $stmt = $pdo->prepare("
            INSERT INTO tblGrade 
            (gradeName, description, gradeCreatedAt) 
            VALUES (?, ?, NOW())
        ");

        $stmt->execute([
            $gradeName,
            $description ?: null
        ]);

        // Success message
        $_SESSION['toast'] =
            "Grade \"$gradeName\" created successfully.";

        // Go back to grade page
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>

<meta charset="UTF-8">

<!-- Load shared meta -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<!-- Page title -->
<title>Add Grade — EduSync</title>

<!-- CSS file -->
<link rel="stylesheet" href="style.css">

</head>

<body>

<!-- Background overlay -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Show user role and name -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($sessionUser['role']) ?>
      ·
      <?= htmlspecialchars($sessionUser['fullName']) ?>
    </div>

    <!-- Page heading -->
    <div class="page-title">Add Grade</div>

    <!-- Small description -->
    <div class="page-sub">
      Create a new year group.
      Classes can be assigned to it afterwards.
    </div>

    <!-- Form card -->
    <div class="card" style="max-width:540px;">

      <!-- Show error message -->
      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Add grade form -->
      <form method="POST" action="add.php">

        <!-- Grade name field -->
        <div class="form-group">

          <label class="form-label">
            Grade Name
            <span class="req">*</span>
          </label>

          <input
            class="form-input"
            name="gradeName"
            placeholder="e.g. Year 1"
            value="<?= htmlspecialchars($old['gradeName'] ?? '') ?>"
            autofocus
          >

          <!-- Example text -->
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px;">
            Examples: Year 1, Year 2, Year 3…
          </div>

        </div>

        <!-- Description field -->
        <div class="form-group">

          <label class="form-label">
            Description
            <span style="color:var(--text-muted);font-weight:400;">
              (optional)
            </span>
          </label>

          <input
            class="form-input"
            name="description"
            placeholder="e.g. First year of secondary school"
            value="<?= htmlspecialchars($old['description'] ?? '') ?>"
          >

        </div>

        <!-- Buttons -->
        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">

          <!-- Cancel button -->
          <a href="index.php" class="btn btn-ghost">
            Cancel
          </a>

          <!-- Save button -->
          <button type="submit" class="btn btn-primary">
            Save Grade
          </button>

        </div>

      </form>
    </div>

  </main>
</div>

<!-- JS file -->
<script src="../shared/auth.js"></script>

</body>
</html>