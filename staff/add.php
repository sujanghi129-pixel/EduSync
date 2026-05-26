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
// Only runs on form submission; GET requests fall through to the HTML below.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise inputs before any validation or DB interaction.
    // trim() removes accidental leading/trailing whitespace.
    // strtolower() normalises the username so "Jane.Smith" and "jane.smith"
    // are treated as the same account.
    $fullName = trim($_POST['fullName'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // ── VALIDATION CHAIN ─────────────────────────────────────────────────────
    // Checks run in priority order; the first failure sets $error and stops
    // the chain. This gives the user one focused message at a time.

    if (!$fullName)
        $error = 'Full name is required.';

    elseif (!$username)
        $error = 'Username is required.';

    elseif (!$role)
        $error = 'Please select a role.';

    elseif (!$password)
        $error = 'Password is required for new accounts.';

    // Delegate password-strength rules to the Staff model.
    // validatePasswordStrength() returns a string on failure, null on pass.
    elseif (($pwError = $staffClass->validatePasswordStrength($password)) !== null)
        $error = $pwError;

    // Check for a duplicate username — must run after format validation
    // so we don't do a DB round-trip on obviously bad input.
    elseif ($staffClass->usernameExists($username))
        $error = "Username \"$username\" is already taken.";

    // ── POST-VALIDATION BRANCHING ─────────────────────────────────────────────
    if ($error) {
        // Validation failed: save submitted values so the form can re-populate.
        // compact() builds ['fullName'=>..., 'username'=>..., 'role'=>...].
        // Password is intentionally excluded — the user must retype it.
        $old = compact('fullName', 'username', 'role');

    } else {
        // Validation passed: create the staff record.
        // Staff::create() handles bcrypt hashing internally — the plain-text
        // password is never stored or logged.
        $staffClass->create($fullName, $username, $password, $role);

        // Store a one-shot success toast for the staff list page to display.
        // $_SESSION is used so the message survives the redirect.
        $_SESSION['toast'] = "Staff account for \"$fullName\" created successfully.";

        // Redirect to the staff list; exit prevents further output after the header.
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">

<!-- Shared metadata: favicon, CSP headers, and other head tags -->
<?php require_once __DIR__ . '/../shared/meta.php'; ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Staff — EduSync</title>

<!-- Staff module stylesheet (tokens, layout, form components) -->
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Full-screen overlay used by the sidebar on mobile -->
<div class="overlay" id="overlay"></div>

<!-- Top navigation bar — populated by shared/auth.js -->
<nav class="topnav" id="topnav"></nav>

<div class="app-layout">

  <!-- Sidebar navigation — populated by shared/auth.js -->
  <aside class="sidebar" id="sidebar"></aside>

  <main class="content">

    <!-- Breadcrumb-style eyebrow: "Role · Full Name" -->
    <div class="page-eyebrow">
      <?= htmlspecialchars($_SESSION['user']['role']) ?> ·
      <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
    </div>

    <div class="page-title">Add New Staff</div>
    <div class="page-sub">Create a new staff account and assign a role.</div>

    <!-- Form card: max-width keeps the two-column layout readable -->
    <div class="card" style="max-width:540px;">

      <!-- ── VALIDATION ERROR CALLOUT ────────────────────────────────────────
           Rendered only when $error is non-null (failed POST).
           htmlspecialchars prevents XSS in case the error message ever
           reflects user-supplied input (e.g. the duplicate username message). -->
      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- POST to the same file; the PHP block above handles the submission -->
      <form method="POST" action="add.php">

        <!-- ── ROW 1: FULL NAME + USERNAME ────────────────────────────────────
             Two equal columns side by side via .form-row grid. -->
        <div class="form-row">

          <!-- Full Name field -->
          <div class="form-group">
            <label class="form-label">
              Full Name <span class="req">*</span>
              <!-- .req applies red colour to the asterisk via CSS -->
            </label>
            <input
              class="form-input"
              name="fullName"
              placeholder="e.g. Jane Smith"
              value="<?= htmlspecialchars($old['fullName'] ?? '') ?>"
              autofocus
              <!-- value re-populates after a failed submission;
                   autofocus places the cursor here on page load -->
            >
          </div>

          <!-- Username field -->
          <div class="form-group">
            <label class="form-label">
              Username <span class="req">*</span>
            </label>
            <input
              class="form-input"
              name="username"
              placeholder="e.g. jane.smith"
              value="<?= htmlspecialchars($old['username'] ?? '') ?>"
              <!-- Re-populated from $old after a failed attempt -->
            >
          </div>

        </div><!-- /.form-row (row 1) -->

        <!-- ── ROW 2: PASSWORD + ROLE ──────────────────────────────────────────
             Password has a live strength meter below it (JS-driven).
             Role is a select dropdown. -->
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
              <!-- autocomplete="new-password" tells the browser this is a
                   creation field, not a login field, preventing autofill
                   of the user's own saved credentials -->
              oninput="updateStrength(this.value)"
              <!-- oninput fires updateStrength() on every keystroke so the
                   strength bar and checklist update in real time -->
            >

            <!-- ── STRENGTH BAR ───────────────────────────────────────────────
                 Thin progress bar below the input.
                 Width (0–100%) and background colour are set by updateStrength()
                 in JS: red (≤2 rules) → amber (3) → green (4–5). -->
            <div style="margin-top:6px;height:4px;border-radius:2px;background:var(--color-border-tertiary);overflow:hidden;">
              <div id="pw-bar" style="height:100%;width:0%;border-radius:2px;transition:width .25s,background .25s;"></div>
            </div>

            <!-- ── REQUIREMENTS CHECKLIST ─────────────────────────────────────
                 Five items in a 2-column grid.
                 Each <li> has a unique id so updateStrength() can target it.
                 &#x25CB; = hollow circle (not yet met); swapped to &#x25CF; = filled
                 circle when the corresponding regex passes. -->
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

              <!-- Default placeholder option — forces the user to make an active choice -->
              <option value="">Select role…</option>

              <!-- Loop over the three valid roles.
                   'selected' is re-applied from $old['role'] after a failed
                   submission so the user doesn't have to re-pick the role. -->
              <?php foreach (['Administrator', 'Teacher', 'Headteacher'] as $r): ?>
                <option value="<?= $r ?>"
                  <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>>
                  <?= $r ?>
                </option>
              <?php endforeach; ?>

            </select>
          </div><!-- /.form-group (role) -->

        </div><!-- /.form-row (row 2) -->

        <!-- ── FORM ACTIONS ────────────────────────────────────────────────────
             Cancel returns to the staff list without saving.
             Submit triggers the POST handler at the top of this file. -->
        <div class="modal-footer"
             style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">

          <!-- Cancel: plain anchor back to the list — no JS confirm needed
               since nothing has been saved yet -->
          <a href="index.php" class="btn btn-ghost">Cancel</a>

          <!-- Submit: triggers POST validation then Staff::create() on success -->
          <button type="submit" class="btn btn-primary">Save Staff</button>
        </div>

      </form>
    </div><!-- /.card -->

  </main>
</div><!-- /.app-layout -->

<!-- Shared auth script: renders topnav + sidebar based on session role -->
<script src="../shared/auth.js"></script>

<script>
/**
 * updateStrength(val)
 *
 * Live client-side password strength feedback.
 * Called on every keystroke via oninput on #pw-input.
 *
 * Updates two UI elements:
 *   1. #pw-reqs checklist — hollow circle (&#x25CB;) → filled circle (&#x25CF;)
 *      with green colour when the rule passes; reverts when it fails again.
 *   2. #pw-bar width (0–100%) and colour:
 *        ≤2 rules passed → red   (weak)
 *         3 rules passed → amber (fair)
 *        ≥4 rules passed → green (strong)
 *
 * NOTE: This is purely cosmetic client-side feedback.
 * The authoritative password policy check is Staff::validatePasswordStrength()
 * on the server — this JS can be bypassed by disabling JavaScript.
 *
 * @param {string} val — Current value of the password input.
 */
function updateStrength(val) {

    // Map each checklist item ID to its corresponding regex/condition.
    // Object.entries() iterates them in insertion order (top → bottom of the list).
    const checks = {
        'req-len':     val.length >= 8,          // Minimum length
        'req-upper':   /[A-Z]/.test(val),        // At least one uppercase letter
        'req-lower':   /[a-z]/.test(val),        // At least one lowercase letter
        'req-digit':   /[0-9]/.test(val),        // At least one digit
        'req-special': /[^A-Za-z0-9]/.test(val), // At least one non-alphanumeric character
    };

    let passed = 0;   // Running count of rules satisfied

    for (const [id, ok] of Object.entries(checks)) {
        const el = document.getElementById(id);
        if (!el) continue;   // Guard: skip if element not found in DOM

        if (ok) {
            // Rule met: swap hollow circle for filled circle and apply green colour
            el.innerHTML = el.innerHTML.replace('\u25CB', '\u25CF');
            el.style.color = 'var(--color-text-success)';
            passed++;
        } else {
            // Rule not met: revert to hollow circle and muted colour
            el.innerHTML = el.innerHTML.replace('\u25CF', '\u25CB');
            el.style.color = 'var(--color-text-secondary)';
        }
    }

    // ── UPDATE STRENGTH BAR ──────────────────────────────────────────────────
    const bar = document.getElementById('pw-bar');
    if (!bar) return;   // Guard: bail if bar element is missing

    // Width scales linearly: each rule = 20% of the bar
    const pct = (passed / 5) * 100;

    // Colour communicates overall strength at a glance
    const color = passed <= 2 ? 'var(--color-background-danger)'    // Red   — weak
                : passed <= 3 ? 'var(--color-background-warning)'   // Amber — fair
                :               'var(--color-background-success)';  // Green — strong

    bar.style.width      = pct + '%';
    bar.style.background = color;
}
</script>

</body>
</html>