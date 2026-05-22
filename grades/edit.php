<?php

/**
 * grades/edit.php
 *
 * Displays and processes the Edit Grade form.
 * Pre-fills the form with existing grade data and updates
 * the tblGrade record on valid POST submission.
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */

// Start session
session_start();

// Check login and admin access
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Get logged-in user details
$sessionUser = $_SESSION['user'];

// Load database and Grade class
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Grade.php';

/** @var Grade $gradeClass - Grade object */
$gradeClass = new Grade(db());

// Get grade ID from URL
$id = (int)($_GET['id'] ?? 0);

// Get grade details by ID
$grade = $gradeClass->getById($id);

// Redirect if grade not found
if (!$grade) {
    header('Location: index.php');
    exit;
}

// Default error and old input values
$error = null;
$old   = [];

// Run when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form values
    $gradeName =
        trim($_POST['gradeName'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    // Check if grade name is empty
    if (!$gradeName) {

        $error =
            'Grade name is required.';

    }

    // Check duplicate grade name
    elseif (
        $gradeClass->nameExists(
            $gradeName,
            $id
        )
    ) {

        $error =
            "A grade named \"$gradeName\" already exists.";
    }

    // Keep old values if error exists
    if ($error) {

        $old = compact(
            'gradeName',
            'description'
        );

    } else {

        // Update grade data
        $gradeClass->update(
            $id,
            $gradeName,
            $description ?: null,
            0
        );

        // Success message
        $_SESSION['toast'] =
            "Grade updated successfully.";

        // Return to grade page
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

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<!-- Page title -->
<title>Edit Grade — EduSync</title>

<!-- CSS file -->
<link rel="stylesheet" href="style.css">

</head>

<body>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- User role and name -->
    <div class="page-eyebrow">

      <?= htmlspecialchars($sessionUser['role']) ?>

      ·

      <?= htmlspecialchars($sessionUser['fullName']) ?>

    </div>

    <!-- Page heading -->
    <div class="page-title">
      Edit Grade
    </div>

    <!-- Page description -->
    <div class="page-sub">
      Update the year group name or description.
    </div>

    <!-- Form card -->
    <div class="card" style="max-width:540px;">

      <!-- Error message -->
      <?php if ($error): ?>

        <div
          class="callout callout-danger"
          style="margin-bottom:16px;"
        >

          ⚠️ <?= htmlspecialchars($error) ?>

        </div>

      <?php endif; ?>

      <!-- Edit form -->
      <form
        method="POST"
        action="edit.php?id=<?= $id ?>"
      >

        <!-- Grade name input -->
        <div class="form-group">

          <label class="form-label">

            Grade Name

            <span class="req">*</span>

          </label>

          <input
            class="form-input"
            name="gradeName"
            placeholder="e.g. Year 1"
            value="<?= htmlspecialchars($old['gradeName'] ?? $grade['gradeName']) ?>"
            autofocus
          >

        </div>

        <!-- Description input -->
        <div class="form-group">

          <label class="form-label">

            Description

            <span
              style="
                color:var(--text-muted);
                font-weight:400;
              "
            >
              (optional)
            </span>

          </label>

          <input
            class="form-input"
            name="description"
            placeholder="e.g. First year of secondary school"
            value="<?= htmlspecialchars($old['description'] ?? $grade['description'] ?? '') ?>"
          >

        </div>

        <!-- Buttons -->
        <div
          class="modal-footer"
          style="
            padding:16px 0 0;
            border-top:1px solid var(--border);
            margin-top:8px;
          "
        >

          <!-- Cancel button -->
          <a
            href="index.php"
            class="btn btn-ghost"
          >
            Cancel
          </a>

          <!-- Submit button -->
          <button
            type="submit"
            class="btn btn-primary"
          >
            Update Grade
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