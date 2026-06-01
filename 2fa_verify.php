<?php

/**
 * 2fa_verify.php  (project root — second-factor OTP verification page)
 *
 * WHAT THIS FILE DOES:
 *   This page is the second step of the two-factor login process.
 *   It is reached ONLY after index.php has verified the email + password
 *   and stored staging data in $_SESSION['2fa_*'].
 *
 *   The staff member must enter the 6-digit OTP that was saved to
 *   tblOTPLog by index.php. To find the code during development:
 *       phpMyAdmin → edusync → tblOTPLog → otp column (latest row)
 *
 *   On success:
 *     - The OTP row is marked used=TRUE in the database (prevents replay).
 *     - $_SESSION['user'] is written with the full user data.
 *     - All 2fa_* staging keys are cleared from the session.
 *     - The session ID is regenerated (prevents session fixation).
 *     - The user is redirected to the dashboard (or 2fa_return if set).
 *
 *   On failure:
 *     - Wrong code    → error message, page re-displayed.
 *     - Expired code  → error message, OTP removed from session.
 *     - Resend action → new OTP generated, saved to DB, page re-displayed.
 *
 *   Direct access (no 2fa_pending session) → redirect to index.php.
 *
 * SESSION KEYS CONSUMED (set by index.php or attendance/login/check.php):
 *   $_SESSION['2fa_pending']  — boolean gate; page refuses to load without it
 *   $_SESSION['2fa_staffId']  — staff ID (available for audit logging)
 *   $_SESSION['2fa_email']    — email the OTP belongs to (shown masked)
 *   $_SESSION['2fa_otp']      — the 6-digit code to verify against
 *   $_SESSION['2fa_expires']  — Unix timestamp after which the OTP is invalid
 *   $_SESSION['2fa_user']     — full user array promoted to $_SESSION['user'] on success
 *   $_SESSION['2fa_return']   — optional post-login redirect URL (used by attendance module)
 *
 * THIS IS A NEW FILE — it did not exist in the original codebase.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// auth.php provides startSecureSession()
require_once __DIR__ . '/shared/auth.php';

// db.php provides the db() singleton for PDO connections (needed by storeOtp/markOtpUsed)
require_once __DIR__ . '/shared/db.php';

// Start the session with Secure, HttpOnly, SameSite=Strict cookie flags.
startSecureSession();

// ── ACCESS GUARD ─────────────────────────────────────────────────────────────
// This page must ONLY be reached via a redirect from index.php or check.php
// after a successful first factor. If the required session keys are missing,
// send the visitor back to the login page immediately.
// This prevents someone bookmarking this URL and accessing it directly.
if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_otp'])) {
    header('Location: index.php');
    exit;
}

// If the user somehow already has a full session, skip to the dashboard.
// This handles double-submits or back-button navigation after a successful login.
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

// Initialise page-level variables used in the HTML below.
$error       = null;    // Error message shown in the red callout
$resent      = false;   // TRUE when a new OTP was successfully generated (shows green callout)
$maskedEmail = maskEmail($_SESSION['2fa_email'] ?? '');  // e.g. s***@edusync.school

// ── HANDLE FORM SUBMISSION ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'verify';   // 'verify' or 'resend'

    // ── RESEND ACTION ─────────────────────────────────────────────────────────
    // The user clicked "Resend code". Generate a new OTP and save it to the DB.
    // The old OTP in the session is replaced so the previous code no longer works.
    if ($action === 'resend') {

        $newOtp = generateOtp();

        // storeOtp() deletes the old code and inserts the new one.
        // It also resets the 10-minute expiry window.
        if (storeOtp($_SESSION['2fa_email'], $newOtp)) {
            $_SESSION['2fa_otp']     = $newOtp;     // Replace the stale code in session
            $_SESSION['2fa_expires'] = time() + 600; // Reset the 10-minute clock
            $resent = true;                          // Show the "new code saved" callout
        } else {
            $error = 'Failed to generate a new code. Please try again.';
        }

    // ── VERIFY ACTION ─────────────────────────────────────────────────────────
    // The user submitted the 6 digit boxes. Compare against the session OTP.
    } else {

        // Strip any spaces or dashes the user might have typed between digits
        // (e.g. pasting "483 021" or "483-021"). We only want the raw digits.
        $submitted = preg_replace('/[\s\-]/', '', trim($_POST['otp'] ?? ''));

        if (!$submitted) {
            // Empty submission — user clicked Verify without entering anything.
            $error = 'Please enter the 6-digit code.';

        } elseif (time() > ($_SESSION['2fa_expires'] ?? 0)) {
            // The 10-minute window has passed. Remove the expired OTP from the
            // session so this page becomes unusable until the user logs in again.
            unset($_SESSION['2fa_otp']);
            $error = 'Your verification code has expired. Please sign in again.';

        } elseif (!hash_equals((string)$_SESSION['2fa_otp'], $submitted)) {
            // Wrong code.
            // WHY hash_equals() instead of ===?
            //   The === operator in PHP has a timing leak: it returns false
            //   slightly faster when the first differing character is near the
            //   start of the string. An attacker making thousands of guesses
            //   could use timing measurements to narrow down the correct digits.
            //   hash_equals() always takes the same amount of time regardless
            //   of where the strings differ, closing that attack vector.
            $error = 'Incorrect code. Please check phpMyAdmin → tblOTPLog and try again.';

        } else {
            // ── SUCCESS ───────────────────────────────────────────────────────
            // Both factors passed. Now it is safe to write the real session.

            // Mark the OTP row as used in the database.
            // This prevents the same code from being submitted a second time
            // (replay attack protection).
            markOtpUsed($_SESSION['2fa_email'], $_SESSION['2fa_otp']);

            // Promote the staged user data to a live authenticated session.
            // This is the key write that grants access to the rest of the system.
            $_SESSION['user'] = $_SESSION['2fa_user'];

            // Read the optional return URL before clearing the staging keys.
            // The attendance module sets $_SESSION['2fa_return'] so the user
            // lands back in the attendance area after completing 2FA.
            $returnUrl = $_SESSION['2fa_return'] ?? 'dashboard/index.php';

            // Sanitise: only allow relative paths. Reject absolute URLs that
            // could redirect the user to a malicious external site (open redirect).
            if (strpos($returnUrl, '://') !== false || strpos($returnUrl, '//') === 0) {
                $returnUrl = 'dashboard/index.php';
            }

            // Remove all 2FA staging keys from the session.
            // They must be cleared now — leaving them would mean any page could
            // re-read the OTP from the session.
            unset(
                $_SESSION['2fa_pending'],
                $_SESSION['2fa_staffId'],
                $_SESSION['2fa_email'],
                $_SESSION['2fa_otp'],
                $_SESSION['2fa_expires'],
                $_SESSION['2fa_user'],
                $_SESSION['2fa_return']
            );

            // Regenerate the session ID now that the privilege level has changed.
            // This invalidates the old session ID so an attacker who captured it
            // (e.g. via session fixation) cannot use it after authentication.
            // true = also delete the old session file from the server.
            session_regenerate_id(true);

            header('Location: ' . $returnUrl);
            exit;
        }
    }
}

// ── HELPER FUNCTIONS ─────────────────────────────────────────────────────────

/**
 * generateOtp()
 *
 * Creates a cryptographically secure 6-digit OTP.
 * Uses random_int() which draws from the OS CSPRNG — safe for security tokens.
 * str_pad() ensures the result is always 6 digits (e.g. 007341 not 7341).
 *
 * @return string  6-digit zero-padded numeric string.
 */
function generateOtp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * storeOtp()
 *
 * Saves a new OTP to tblOTPLog, first deleting any existing unused codes
 * for this email. The expiry is set to 10 minutes from now by MySQL.
 *
 * During development: read the otp column from phpMyAdmin → tblOTPLog.
 * In production: replace the DB insert with an SMTP/email send.
 *
 * @param string $email  Staff email address used as the lookup key.
 * @param string $otp    The 6-digit code to store.
 * @return bool          TRUE on success, FALSE on database error.
 */
function storeOtp(string $email, string $otp): bool {
    try {
        $pdo = db();

        // Delete old unused codes for this email first (housekeeping).
        $pdo->prepare("DELETE FROM tblOTPLog WHERE email = ? AND used = FALSE")
            ->execute([$email]);

        // Insert the new OTP. expires_at uses MySQL's NOW() to avoid PHP/DB clock drift.
        return $pdo->prepare("
            INSERT INTO tblOTPLog (email, otp, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ")->execute([$email, $otp]);

    } catch (PDOException $e) {
        return false;  // DB error — caller will show an error message
    }
}

/**
 * markOtpUsed()
 *
 * Flags the verified OTP row as used=TRUE in tblOTPLog.
 *
 * WHY mark as used instead of deleting?
 *   Keeping the row (with used=TRUE) provides an audit trail showing when
 *   each staff member successfully completed 2FA. Deleting it would erase
 *   that history. The tblOTPLog table can be pruned periodically in production.
 *
 * This is non-fatal if it fails — the session clears the OTP independently.
 *
 * @param string $email  Staff email address.
 * @param string $otp    The code that was just successfully verified.
 */
function markOtpUsed(string $email, string $otp): void {
    try {
        db()->prepare("UPDATE tblOTPLog SET used = TRUE WHERE email = ? AND otp = ?")
           ->execute([$email, $otp]);
    } catch (PDOException $e) {
        // Non-fatal — the session-level OTP is already consumed.
        // Log this in production; silently ignore in development.
    }
}

/**
 * maskEmail()
 *
 * Partially hides an email address for display on the verify page.
 * Shows enough to confirm which inbox to check without revealing the full address.
 *
 * Examples:
 *   sujan.ghimire@edusync.school  →  s***@edusync.school
 *   susma.thapa@edusync.school    →  s***@edusync.school
 *   ab@edusync.school             →  a***@edusync.school
 *
 * @param string $email  Full email address to mask.
 * @return string        Masked version for safe display.
 */
function maskEmail(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;   // Not a valid email — return as-is

    $local  = $parts[0];
    $domain = $parts[1];

    // Keep only the first character; replace the rest with asterisks.
    // max(3, strlen-1) ensures at least 3 asterisks even on short local parts.
    $masked = substr($local, 0, 1) . str_repeat('*', max(3, strlen($local) - 1));

    return $masked . '@' . $domain;
}

// Seconds remaining until the OTP expires — drives the countdown timer in JS.
// max(0, ...) prevents a negative value if the OTP has already expired.
$secondsLeft = max(0, ($_SESSION['2fa_expires'] ?? 0) - time());
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSync — Verify Your Identity</title>
<!-- Reuses login.css for consistent card, callout, button, and form styles -->
<link rel="stylesheet" href="login.css">
<style>
  /* ── OTP digit input boxes ─────────────────────────────────────────────── */
  .otp-wrap {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin: 24px 0 8px;
  }

  /* Each of the 6 individual digit boxes */
  .otp-digit {
    width: 48px;
    height: 56px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    border: 1.5px solid #2e2c27;
    border-radius: 10px;
    background: #242320;
    color: #f0ede5;
    font-family: 'Inter', system-ui, monospace;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    caret-color: #60a5fa;
  }

  /* Focus ring: blue border + soft glow (matches login.css form-input:focus) */
  .otp-digit:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(96,165,250,.2);
  }

  /* Green border once a digit has been entered in this box */
  .otp-digit.filled {
    border-color: #4ade80;
    background: #0f2016;
  }

  /* ── Countdown timer ───────────────────────────────────────────────────── */
  .otp-timer {
    text-align: center;
    font-size: .8rem;
    color: #8a8880;
    margin-bottom: 20px;
  }
  .otp-timer.warning { color: #f97316; }   /* Orange when < 60 seconds remain */
  .otp-timer.expired { color: #f87171; }   /* Red when time is up */

  /* ── Success callout (code resent) ────────────────────────────────────── */
  .callout-success {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: .855rem;
    margin-bottom: 20px;
    background: #0a2218;
    border-left: 3px solid #4ade80;
    color: #86efac;
  }

  /* ── Resend link row ───────────────────────────────────────────────────── */
  .resend-row {
    text-align: center;
    font-size: .8rem;
    color: #8a8880;
    margin-top: 18px;
  }
  .resend-row button {
    background: none;
    border: none;
    color: #60a5fa;
    font-size: .8rem;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    font-family: inherit;
  }
  .resend-row button:hover { color: #93c5fd; }

  /* ── Back to login link ────────────────────────────────────────────────── */
  .back-link {
    display: block;
    text-align: center;
    font-size: .8rem;
    color: #8a8880;
    margin-top: 14px;
    text-decoration: none;
  }
  .back-link:hover { color: #f0ede5; }

  /* ── Shield icon container ─────────────────────────────────────────────── */
  .shield-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #0c1f38;
    border: 1.5px solid #1e3a5f;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.5rem;
  }

  /* ── Development hint box ──────────────────────────────────────────────── */
  /* Amber-tinted box reminding the developer where to find the OTP code */
  .dev-hint {
    margin-top: 16px;
    padding: 12px 14px;
    background: #1a1500;
    border: 1px solid #3d3200;
    border-radius: 8px;
    font-size: .78rem;
    color: #d4a800;
    text-align: center;
    line-height: 1.6;
  }
  .dev-hint strong { color: #fbbf24; }
</style>
</head>
<body>

<div class="login-wrap">
  <div class="login-card">

    <!-- ── TOP ROW: LOGO + BACK BUTTON ──────────────────────────────────────── -->
    <div class="login-top-row">
      <a href="landing.php" class="login-logo">
        <img src="shared/logo.png" alt="EduSync Logo" class="login-logo-img">
        <span class="login-logo-name">EduSync</span>
      </a>
      <!-- Back to Login — clears the 2FA session state and returns to index.php -->
      <a href="index.php" class="login-back-home">&#8592; Back to Login</a>
    </div>

    <!-- Lock/shield icon — visual indicator this is a security step -->
    <div class="shield-icon">🔐</div>

    <div class="login-title">Verify your identity</div>
    <div class="login-sub" style="margin-bottom:20px;">
      Enter the 6-digit code for
      <!-- Masked email: shows enough to confirm the inbox without revealing it fully -->
      <strong style="color:#f0ede5;"><?= htmlspecialchars($maskedEmail) ?></strong>
    </div>

    <!-- ── ERROR CALLOUT ─────────────────────────────────────────────────────
         Shown when the OTP is wrong, expired, or a resend failed. -->
    <?php if ($error): ?>
      <div class="callout callout-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ── SUCCESS CALLOUT ───────────────────────────────────────────────────
         Shown after a successful resend — tells the developer to check the DB. -->
    <?php if ($resent): ?>
      <div class="callout-success">✅ A new code has been saved. Check phpMyAdmin → tblOTPLog.</div>
    <?php endif; ?>

    <!-- ── OTP VERIFICATION FORM ─────────────────────────────────────────────
         Six individual digit boxes for UX.
         JavaScript joins their values into the hidden #otpHidden field
         before the form is submitted to the server. -->
    <form method="POST" action="2fa_verify.php" id="otpForm">

      <!-- action=verify tells the PHP handler this is a code submission,
           not a resend request. -->
      <input type="hidden" name="action" value="verify">

      <!-- The actual OTP value sent to the server — populated by syncHidden() in JS -->
      <input type="hidden" name="otp" id="otpHidden">

      <!-- Six individual digit input boxes.
           PHP generates them in a loop so the count is easy to change.
           autocomplete="one-time-code" on the first box enables the browser's
           SMS/email OTP autofill on supported devices. -->
      <div class="otp-wrap" id="otpBoxes">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input
            class="otp-digit"
            type="text"
            inputmode="numeric"
            maxlength="1"
            pattern="[0-9]"
            autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
            data-index="<?= $i ?>"
            <?= $i === 0 ? 'autofocus' : '' ?>
          >
        <?php endfor; ?>
      </div>

      <!-- Countdown timer — updated every second by the JS setInterval below -->
      <div class="otp-timer" id="timerDisplay">
        Code expires in <span id="timerValue"><?= $secondsLeft ?></span>s
      </div>

      <!-- Submit button — disabled by JS until all 6 digits are entered -->
      <button type="submit" class="btn-primary" id="verifyBtn">Verify Code</button>
    </form>

    <!-- ── RESEND FORM ────────────────────────────────────────────────────────
         Separate POST form so it doesn't interfere with the OTP form above.
         action=resend routes to the resend branch in the PHP handler. -->
    <div class="resend-row">
      Need a new code?
      <form method="POST" action="2fa_verify.php" style="display:inline;">
        <input type="hidden" name="action" value="resend">
        <button type="submit">Resend code</button>
      </form>
    </div>

    <!-- Link to go back and log in with a different account -->
    <a href="index.php" class="back-link">← Sign in with a different account</a>

    <!-- ── DEV HINT BOX ──────────────────────────────────────────────────────
         Amber box reminding the developer where to find the OTP.
         REMOVE this in production. -->
    <div class="dev-hint">
      🛠️ <strong>Development mode</strong><br>
      Open <strong>phpMyAdmin → edusync → tblOTPLog</strong><br>
      and read the <strong>otp</strong> column for the latest code.
    </div>

  </div><!-- /.login-card -->
</div><!-- /.login-wrap -->

<script>
/**
 * OTP DIGIT BOX BEHAVIOUR
 * ──────────────────────────────────────────────────────────────────────────
 * The six individual <input> boxes provide a better UX than a single text
 * field: each box accepts exactly one digit, focus advances automatically,
 * backspace navigates backwards, and paste handles a full 6-digit code.
 *
 * The hidden #otpHidden input is kept in sync via syncHidden() and is the
 * value actually submitted to the PHP handler.
 */

const boxes     = Array.from(document.querySelectorAll('.otp-digit'));
const hidden    = document.getElementById('otpHidden');
const form      = document.getElementById('otpForm');
const verifyBtn = document.getElementById('verifyBtn');

/**
 * syncHidden()
 * Joins all 6 box values into the hidden input.
 * Toggles the .filled class (green border) on each box.
 * Enables/disables the Verify button based on whether all 6 are filled.
 */
function syncHidden() {
    const val = boxes.map(b => b.value).join('');
    hidden.value = val;
    boxes.forEach(b => b.classList.toggle('filled', b.value !== ''));
    // Button stays disabled until all 6 digits are present
    verifyBtn.disabled = (val.length < 6);
}

// Start with the button disabled — user must enter all 6 digits first
verifyBtn.disabled = true;

boxes.forEach((box, idx) => {

    box.addEventListener('input', () => {
        // Strip any non-digit characters (e.g. letters from the mobile keyboard)
        // and keep only the last character typed (handles rapid input).
        box.value = box.value.replace(/\D/g, '').slice(-1);
        syncHidden();
        // Auto-advance to the next box after a digit is entered
        if (box.value && idx < 5) boxes[idx + 1].focus();
    });

    box.addEventListener('keydown', e => {
        // On Backspace in an empty box, move focus back to the previous box
        // so the user can correct a digit without clicking
        if (e.key === 'Backspace' && !box.value && idx > 0) {
            boxes[idx - 1].focus();
        }
    });

    box.addEventListener('paste', e => {
        e.preventDefault();
        // Allow pasting a full 6-digit code (e.g. copied from the DB or an email)
        // into any box — extract digits, fill all boxes, update the hidden input
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((d, i) => { if (boxes[i]) boxes[i].value = d; });
        syncHidden();
        // Focus the box after the last pasted digit (or the last box)
        boxes[Math.min(digits.length, 5)].focus();
    });
});

// Auto-submit the form as soon as all 6 digits are entered.
// The 'input' event fires on the form container after any of its children change.
form.addEventListener('input', () => {
    if (hidden.value.length === 6) form.submit();
});

/**
 * COUNTDOWN TIMER
 * ──────────────────────────────────────────────────────────────────────────
 * Counts down from the PHP-calculated $secondsLeft value.
 * At 60 seconds remaining: turns orange (warning class).
 * At 0 seconds: turns red (expired class), disables the Verify button,
 *               and clears the interval so it stops updating.
 *
 * The timer is cosmetic — the server-side expiry check in PHP is authoritative.
 */
let secondsLeft = <?= (int)$secondsLeft ?>;   // PHP-injected initial value

const timerEl    = document.getElementById('timerDisplay');
const timerValue = document.getElementById('timerValue');

/**
 * formatTime(s)
 * Converts a raw second count into a human-readable string.
 * e.g. 135 → "2m 15s",  45 → "45s"
 */
function formatTime(s) {
    const m   = Math.floor(s / 60);
    const sec = s % 60;
    return m > 0 ? `${m}m ${String(sec).padStart(2, '0')}s` : `${sec}s`;
}

function tick() {
    if (secondsLeft <= 0) {
        // Time's up — show expired state and disable the verify button
        timerEl.className    = 'otp-timer expired';
        timerEl.textContent  = 'Code expired — click Resend code to get a new one.';
        verifyBtn.disabled   = true;
        clearInterval(timer);
        return;
    }

    // Update the display and apply the warning colour when nearly expired
    timerValue.textContent = formatTime(secondsLeft);
    timerEl.className      = 'otp-timer' + (secondsLeft <= 60 ? ' warning' : '');
    secondsLeft--;
}

tick();                                    // Run immediately on page load
const timer = setInterval(tick, 1000);    // Then update every second
</script>

</body>
</html>
