<?php

/**
 * grades/delete.php
 *
 * Displays the Delete Grade confirmation page.
 * Blocks deletion if the grade has active classes assigned to it.
 * Requires all classes to be reassigned or deleted first.
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

// Load database and Grade class
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Grade.php';

/** @var Grade $gradeClass - Grade object */
$gradeClass = new Grade(db());

// Get grade ID from URL or form
$id = (int)($_GET['id'] ?? $_POST['gradeId'] ?? 0);

// Get grade details
$grade = $gradeClass->getById($id);

// Redirect if grade not found
if (!$grade) {
    header('Location: index.php');
    exit;
}

/** @var int $classCount - Number of active classes */
$classCount = $gradeClass->countClasses($id);

// Check if grade has classes
$hasClasses = $classCount > 0;

/** @var int $studentCount - Number of active students */
$studentCount = $gradeClass->countStudents($id);

// Check if grade has students
$hasStudents = $studentCount > 0;

// Block deletion if classes or students exist
$blocked = $hasClasses || $hasStudents;

// Run when delete form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Stop delete if blocked
    if ($blocked) {

        // Show reason
        $reason = $hasClasses
            ? "Cannot delete: {$grade['gradeName']} has $classCount active class(es) assigned."
            : "Cannot delete: {$grade['gradeName']} has $studentCount active student(s) assigned.";

        $_SESSION['toast_error'] = $reason;

        // Reload delete page
        header("Location: delete.php?id=$id");
        exit;
    }

    // Delete grade
    $gradeClass->delete($id);

    // Success message
    $_SESSION['toast'] =
        "Grade \"{$grade['gradeName']}\" deleted.";

    // Return to index page
    header('Location: index.php');
    exit;
}

// Get error message
$toastError =
    $_SESSION['toast_error'] ?? null;

// Clear session message
unset($_SESSION['toast_error']);
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
<title>Delete Grade — EduSync</title>

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
    <div class="page-title">
      Delete Grade
    </div>

    <!-- Small description -->
    <div class="page-sub">
      Review before permanently removing this grade.
    </div>

    <!-- Main card -->
    <div class="card" style="max-width:480px;">

      <!-- Show error message -->
      <?php if ($toastError): ?>
        <div
          class="callout callout-danger"
          style="margin-bottom:16px;"
        >
          ⚠️ <?= htmlspecialchars($toastError) ?>
        </div>
      <?php endif; ?>

      <!-- Grade information -->
      <div
        style="
          padding:14px;
          background:var(--surface2);
          border-radius:var(--radius);
          margin-bottom:16px;
        "
      >

        <!-- Grade name -->
        <div
          style="
            font-weight:600;
            font-size:.95rem;
          "
        >
          <?= htmlspecialchars($grade['gradeName']) ?>
        </div>

        <!-- Grade description -->
        <?php if (!empty($grade['description'])): ?>
          <div
            style="
              color:var(--text-muted);
              font-size:.82rem;
              margin-top:2px;
            "
          >
            <?= htmlspecialchars($grade['description']) ?>
          </div>
        <?php endif; ?>

        <!-- Active class count -->
        <div style="margin-top:8px;">
          <span class="badge badge-blue">
            <?= $classCount ?>
            active class<?= $classCount != 1 ? 'es' : '' ?>
          </span>
        </div>

      </div>

      <!-- Delete warning -->
      <p
        style="
          color:var(--text-muted);
          font-size:.875rem;
          margin-bottom:14px;
        "
      >
        Permanently delete
        <strong>
          <?= htmlspecialchars($grade['gradeName']) ?>
        </strong>?
        This cannot be undone.
      </p>

      <!-- Show warning if classes exist -->
      <?php if ($hasClasses): ?>

        <div class="callout callout-danger">

          <span>🚫</span>

          <span>
            Cannot delete:
            this grade has
            <strong>
              <?= $classCount ?>
              active class<?= $classCount != 1 ? 'es' : '' ?>
            </strong>
            assigned to it.
            Reassign or delete the classes first.
          </span>

        </div>

      <?php else: ?>

        <!-- Safe delete message -->
        <div class="callout callout-warn">

          <span>⚠️</span>

          <span>
            This grade has no active classes
            and is safe to delete.
          </span>

        </div>

      <?php endif; ?>

      <!-- Buttons -->
      <div
        class="modal-footer"
        style="
          padding:16px 0 0;
          border-top:1px solid var(--border);
          margin-top:16px;
        "
      >

        <!-- Cancel button -->
        <a
          href="index.php"
          class="btn btn-ghost"
        >
          Cancel
        </a>

        <!-- Delete form -->
        <form
          method="POST"
          action="delete.php?id=<?= $id ?>"
          style="display:inline;"
        >

          <!-- Hidden grade ID -->
          <input
            type="hidden"
            name="gradeId"
            value="<?= $id ?>"
          >

          <!-- Delete button -->
          <button
            type="submit"
            class="btn btn-danger"
            <?= $hasClasses ? 'disabled' : '' ?>
          >
            Delete Permanently
          </button>

        </form>

      </div>

    </div>

  </main>
</div>

<!-- JS file -->
<script src="../shared/auth.js"></script>

</body>
</html>