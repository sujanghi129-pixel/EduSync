<?php

/**
 * staff/edit.php
 *
 * Presentation layer — Edit Staff form.
 * Uses the Staff middle layer class to retrieve and update records.
 * Requires Administrator role.
 *
 * CHANGES FROM ORIGINAL:
 *   - Added $email = $_POST['email'] to read the new email field
 *   - Added filter_var() email format validation
 *   - Added emailExists($email, $id) duplicate check (excludes current record)
 *   - update() call now passes $email as the 4th argument
 *   - $old array now includes 'email' so the form re-populates after errors
 *   - HTML form: new "Email Address" input row added between username and password
 *   - Email input pre-populated from $staff['email'] on initial page load
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

session_start();

require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Staff.php';

$staffClass = new Staff(db());

// Read the staff ID from the URL query string.
// Cast to int immediately — never use raw $_GET values in DB queries.
$id = (int)($_GET['id'] ?? 0);

// Fetch the staff record. Redirect to the list if it doesn't exist.
$staff = $staffClass->getById($id);
if (!$staff) {
    header('Location: index.php');
    exit;
}

$error = null;
$old   = [];

// ── HANDLE FORM SUBMISSION (POST) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['fullName'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));

    // NEW: read the email field from the submitted form
    $email    = strtolower(trim($_POST['email'] ?? ''));

    $password = $_POST['password'] ?? '';   // Empty = keep existing password
    $role     = $_POST['role'] ?? '';

    // ── VALIDATION CHAIN ─────────────────────────────────────────────────────
    if (!$fullName)
        $error = 'Full name is required.';

    elseif (!$username)
        $error = 'Username is required.';

    // NEW: email is now required
    elseif (!$email)
        $error = 'Email address is required.';

    // NEW: validate email format before hitting the database
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = 'Please enter a valid email address.';

    elseif (!$role)
        $error = 'Please select a role.';

    // Password validation only runs if a new password was provided
    elseif ($password && ($pwError = $staffClass->validatePasswordStrength($password)) !== null)
        $error = $pwError;

    // Check username uniqueness — pass $id to exclude the current record
    // (otherwise editing without changing the username would always fail)
    elseif ($staffClass->usernameExists($username, $id))
        $error = "Username \"$username\" is already taken.";

    // NEW: check email uniqueness — pass $id to exclude the current record
    elseif ($staffClass->emailExists($email, $id))
        $error = "Email \"$email\" is already in use.";

    // ── POST-VALIDATION BRANCHING ─────────────────────────────────────────────
    if ($error) {
        // NEW: 'email' added to $old so the email field re-populates on error
        $old = compact('fullName', 'username', 'email', 'role');

    } else {
        // NEW: $email passed as 4th argument (was not in the original call)
        // update() uses sp_UpdateStaff (no password) or sp_UpdateStaffWithPassword
        $staffClass->update($id, $fullName, $username, $email, $role, $password);

        $_SESSION['toast'] = "Staff account updated successfully.";
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
<title>Edit Staff — EduSync</title>
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

    <div class="page-title">Edit Staff</div>
    <div class="page-sub">Update staff account details and role.</div>

    <div class="card" style="max-width:540px;">

      <!-- Validation error callout -->
      <?php if ($error): ?>
        <div class="callout callout-danger" style="margin-bottom:16px;">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Action targets edit.php?id=N so the $id is always available on POST -->
      <form method="POST" action="edit.php?id=<?= $id ?>">

        <!-- ── ROW 1: FULL NAME + USERNAME ──────────────────────────────────── -->
        <div class="form-row">

          <div class="form-group">
            <label class="form-label">
              Full Name <span class="req">*</span>
            </label>
            <!-- $old['fullName'] used after a failed validation;
                 $staff['fullName'] used on the initial page load -->
            <input class="form-input"
                   name="fullName"
                   placeholder="e.g. Jane Smith"
                   value="<?= htmlspecialchars($old['fullName'] ?? $staff['fullName']) ?>"
                   autofocus>
          </div>

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

        <!-- ── EMAIL ROW — NEW ────────────────────────────────────────────────
             This entire block is new.
             Pre-populated with the existing email from $staff['email']
             on initial page load, or $old['email'] after a failed validation. -->
        <div class="form-row">
          <div class="form-group" style="flex:1 1 100%;">
            <label class="form-label">
              Email Address <span class="req">*</span>
            </label>
            <input class="form-input"
                   type="email"
                   name="email"
                   placeholder="e.g. jane.smith@edusync.school"
                   value="<?= htmlspecialchars($old['email'] ?? $staff['email'] ?? '') ?>"
                   autocomplete="email">
            <div class="form-hint">Used to sign in. Must be unique.</div>
          </div>
        </div>

        <!-- ── ROW 2: PASSWORD + ROLE ──────────────────────────────────────── -->
        <div class="form-row">

          <!-- Optional password update — leave blank to keep existing -->
          <div class="form-group">
            <label class="form-label">Password</label>
            <input class="form-input"
                   id="pw-input"
                   name="password"
                   type="password"
                   placeholder="Leave blank to keep current"
                   autocomplete="new-password"
                   oninput="updateStrength(this.value)">

            <!-- Strength indicator — hidden until the user starts typing -->
            <div id="pw-strength-wrap" style="display:none;margin-top:6px;">
              <div style="height:4px;border-radius:2px;background:var(--color-border-tertiary);overflow:hidden;">
                <div id="pw-bar" style="height:100%;width:0%;border-radius:2px;transition:width .25s,background .25s;"></div>
              </div>
              <ul id="pw-reqs" style="margin:8px 0 0;padding:0;list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;">
                <li id="req-len"     style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; 8+ characters</li>
                <li id="req-upper"   style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Uppercase letter</li>
                <li id="req-lower"   style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Lowercase letter</li>
                <li id="req-digit"   style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Number</li>
                <li id="req-special" style="font-size:12px;color:var(--color-text-secondary);">&#x25CB; Special character</li>
              </ul>
            </div>
          </div>

          <!-- Role dropdown — pre-selects the current or just-submitted role -->
          <div class="form-group">
            <label class="form-label">
              Role <span class="req">*</span>
            </label>
            <select class="form-select" name="role">
              <option value="">Select role…</option>
              <?php foreach (['Administrator', 'Teacher', 'Headteacher'] as $r): ?>
                <?php $sel = ($old['role'] ?? $staff['role']) === $r ? 'selected' : ''; ?>
                <option value="<?= $r ?>" <?= $sel ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>

        <!-- ── FORM ACTIONS ──────────────────────────────────────────────────── -->
        <div class="modal-footer" style="padding:16px 0 0;border-top:1px solid var(--border);margin-top:8px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Staff</button>
        </div>

      </form>
    </div>

  </main>
</div>

<script src="../shared/auth.js"></script>

<script>
/**
 * updateStrength(val)
 *
 * Same logic as add.php. The wrapper div is hidden by default so it doesn't
 * clutter the form when an administrator is only updating a name or role.
 * It appears as soon as the user starts typing a new password.
 *
 * Authoritative server-side check: Staff::validatePasswordStrength()
 */
function updateStrength(val) {
    const wrap = document.getElementById('pw-strength-wrap');
    if (wrap) wrap.style.display = val.length ? 'block' : 'none';

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
            el.innerHTML = el.innerHTML.replace('\u25CB', '\u25CF');
            el.style.color = 'var(--color-text-success)';
            passed++;
        } else {
            el.innerHTML = el.innerHTML.replace('\u25CF', '\u25CB');
            el.style.color = 'var(--color-text-secondary)';
        }
    }

    const bar = document.getElementById('pw-bar');
    if (!bar) return;
    bar.style.width      = (passed / 5 * 100) + '%';
    bar.style.background = passed <= 2 ? 'var(--color-background-danger)'
                         : passed <= 3 ? 'var(--color-background-warning)'
                         :               'var(--color-background-success)';
}
</script>

</body>
</html>
