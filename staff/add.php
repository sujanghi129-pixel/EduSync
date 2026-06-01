<?php

/**
 * staff/add.php
 *
 * Presentation layer — Add New Staff form.
 * Uses the Staff middle layer class to validate and create records.
 * Requires Administrator role.
 *
 * CHANGES FROM ORIGINAL:
 *   - Added $email = $_POST['email'] to read the new email field
 *   - Added filter_var() email format validation
 *   - Added emailExists() duplicate check
 *   - create() call now passes $email as the 3rd argument
 *   - $old array now includes 'email' so the form re-populates after errors
 *   - HTML form: new "Email Address" input row added between username and password
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start (or resume) the session so $_SESSION is available throughout
session_start();

// Load auth helpers and enforce Administrator-only access —
// any other role is redirected away before the page renders
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

// Load the database connection factory and the Staff model
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

// Instantiate the Staff model with a live PDO connection.
// All validation and DB writes go through this object, not raw SQL here.
$staffClass = new Staff(db());

// $error holds the first validation failure message shown in the callout.
// $old holds the previously submitted values so the form re-populates
// after a failed attempt, saving the user from retyping everything.
$error = null;
$old   = [];

// ── HANDLE FORM SUBMISSION (POST) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise all text inputs: trim() removes accidental whitespace.
    // strtolower() normalises username and email so case differences
    // don't create duplicate-looking records.
    $fullName = trim($_POST['fullName'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));

    // NEW: read the email field submitted from the form
    $email    = strtolower(trim($_POST['email'] ?? ''));

    // Password taken as-is (bcrypt comparison is case-sensitive)
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // ── VALIDATION CHAIN ─────────────────────────────────────────────────────
    // Checks run in order; the first failure sets $error and stops the chain.
    // This gives the user one focused error message at a time.

    if (!$fullName)
        $error = 'Full name is required.';

    elseif (!$username)
        $error = 'Username is required.';

    // NEW: check the email field is not empty
    elseif (!$email)
        $error = 'Email address is required.';

    // NEW: validate that the email looks like a real email address.
    // FILTER_VALIDATE_EMAIL checks for the presence of @, a domain, etc.
    // This catches typos like "sujan@" or "sujan.ghimire" before any DB query.
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = 'Please enter a valid email address.';

    elseif (!$role)
        $error = 'Please select a role.';

    elseif (!$password)
        $error = 'Password is required for new accounts.';

    // Delegate password-strength rules to the Staff model.
    // validatePasswordStrength() returns a string on failure, null on pass.
    elseif (($pwError = $staffClass->validatePasswordStrength($password)) !== null)
        $error = $pwError;

    // Check for duplicate username — runs after format checks
    // so we don't make a DB round-trip on obviously invalid input
    elseif ($staffClass->usernameExists($username))
        $error = "Username \"$username\" is already taken.";

    // NEW: check for duplicate email address (must be unique in tblStaff)
    elseif ($staffClass->emailExists($email))
        $error = "Email \"$email\" is already in use.";

    // ── POST-VALIDATION BRANCHING ─────────────────────────────────────────────
    if ($error) {
        // Validation failed — save submitted values so the form can re-populate.
        // Password is intentionally excluded: the user must retype it.
        // NEW: 'email' added to the compact() call
        $old = compact('fullName', 'username', 'email', 'role');

    } else {
        // Validation passed — create the staff record.
        // Staff::create() handles bcrypt hashing internally.
        // NEW: $email passed as 3rd argument (was not in the original call)
        $staffClass->create($fullName, $username, $email, $password, $role);

        // Store a one-shot success toast for the staff list page to display.
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

    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> ·
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <div class="page-title">Add New Staff</div>
    <div class="page-sub">Create a new staff account and assign a role.</div>

    <div class="card" style="max-width:540px;">

      <!-- Validation error callout — only shown on a failed POST -->
      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="add.php">

        <!-- ── ROW 1: FULL NAME + USERNAME ──────────────────────────────────── -->
        <div class="form-row">

          <!-- Full Name field -->
          <div class="form-group">
            <label class="form-label">
              Full Name <span class="req">*</span>
            </label>
            <input
              class="form-input"
              name="fullName"
              placeholder="e.g. Jane Smith"
              value="<?= htmlspecialchars($old['fullName'] ?? '') ?>"
              autofocus
            >
          </div>

          <!-- Username field (still used for display/internal purposes) -->
          <div class="form-group">
            <label class="form-label">
              Username <span class="req">*</span>
            </label>
            <input
              class="form-input"
              name="username"
              placeholder="e.g. jane.smith"
              value="<?= htmlspecialchars($old['username'] ?? '') ?>"
            >
          </div>

        </div><!-- /.form-row (row 1) -->

        <!-- ── EMAIL ROW — NEW ────────────────────────────────────────────────
             This entire block is new. The email address is what the staff
             member uses to log in. It must be unique across tblStaff.
             type="email" enables the browser's email keyboard on mobile. -->
        <div class="form-row">
          <div class="form-group" style="flex:1 1 100%;">
            <label class="form-label">
              Email Address <span class="req">*</span>
            </label>
            <input
              class="form-input"
              type="email"
              name="email"
              placeholder="e.g. jane.smith@edusync.school"
              value="<?= htmlspecialchars($old['email'] ?? '') ?>"
              autocomplete="email"
            >
            <!-- Hint text below the field explains its purpose -->
            <div class="form-hint">Used to sign in. Must be unique.</div>
          </div>
        </div>

        <!-- ── ROW 2: PASSWORD + ROLE ──────────────────────────────────────── -->
        <div class="form-row">

          <!-- Password field with live strength indicator -->
          <div class="form-group">
            <label class="form-label">
              Password <span class="req">*</span>
            </label>
            <input
              class="form-input"
              id="pw-input"
              name="password"
              type="password"
              placeholder="Min 8 characters"
              autocomplete="new-password"
              oninput="updateStrength(this.value)"
            >

            <!-- Strength bar: width and colour updated by updateStrength() JS -->
            <div style="margin-top:6px;height:4px;border-radius:2px;background:var(--color-border-tertiary);overflow:hidden;">
              <div id="pw-bar" style="height:100%;width:0%;border-radius:2px;transition:width .25s,background .25s;"></div>
            </div>

            <!-- Requirements checklist: each <li> targeted by updateStrength() -->
            <ul id="pw-reqs" style="margin:8px 0 0;padding:0;list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;">
              <li id="req-len"     style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; 8+ characters</li>
              <li id="req-upper"   style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Uppercase letter</li>
              <li id="req-lower"   style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Lowercase letter</li>
              <li id="req-digit"   style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Number</li>
              <li id="req-special" style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Special character</li>
            </ul>
          </div><!-- /.form-group (password) -->

          <!-- Role dropdown -->
          <div class="form-group">
            <label class="form-label">
              Role <span class="req">*</span>
            </label>
            <select class="form-select" name="role">
              <option value="">Select role…</option>
              <?php foreach (['Administrator', 'Teacher', 'Headteacher'] as $r): ?>
                <option value="<?= $r ?>" <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>>
                  <?= $r ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

        </div><!-- /.form-row (row 2) -->

        <!-- ── FORM ACTIONS ──────────────────────────────────────────────────── -->
        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Staff</button>
        </div>

      </form>
    </div><!-- /.card -->

  </main>
</div><!-- /.app-layout -->

<script src="../shared/auth.js"></script>

<script>
/**
 * updateStrength(val)
 *
 * Live client-side password strength feedback — called on every keystroke.
 * NOTE: this is cosmetic only. The authoritative check is
 * Staff::validatePasswordStrength() on the server.
 *
 * Updates:
 *   - #pw-reqs checklist (hollow → filled circle per rule)
 *   - #pw-bar width (0–100%) and colour (red/amber/green)
 *
 * @param {string} val  Current value of the password input.
 */
function updateStrength(val) {
    const checks = {
        'req-len':     val.length >= 8,
        'req-upper':   /[A-Z]/.test(val),
        'req-lower':   /[a-z]/.test(val),
        'req-digit':   /[0-9]/.test(val),
        'req-special': /[^A-Za-z0-9]/.test(val),
    };

    let passed = 0;

    for (const [id, ok] of Object.entries(checks)) {
        const el = document.getElementById(id);
        if (!el) continue;
        if (ok) {
            el.innerHTML = el.innerHTML.replace('\u25CB', '\u25CF');   // Hollow → filled
            el.style.color = 'var(--color-text-success)';
            passed++;
        } else {
            el.innerHTML = el.innerHTML.replace('\u25CF', '\u25CB');   // Filled → hollow
            el.style.color = 'var(--color-text-secondary)';
        }
    }

    const bar   = document.getElementById('pw-bar');
    if (!bar) return;

    bar.style.width      = (passed / 5 * 100) + '%';
    bar.style.background = passed <= 2 ? 'var(--color-background-danger)'
                         : passed <= 3 ? 'var(--color-background-warning)'
                         :               'var(--color-background-success)';
}
</script>

</body>
</html>
