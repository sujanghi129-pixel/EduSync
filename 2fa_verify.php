<?php

/**
 * 2fa_verify.php  (project root — OTP verification page)
 *
 * Second step of the two-factor login process.
 * Reads the OTP from phpMyAdmin → edusync → tblOTPLog during development.
 *
 * USERNAME REMOVED:
 *   $_SESSION['user'] and $_SESSION['2fa_user'] no longer contain 'username'.
 *   Session keys: staffId, fullName, email, role.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

require_once __DIR__ . '/shared/auth.php';
require_once __DIR__ . '/shared/db.php';
startSecureSession();

// Guard: must arrive via index.php after a successful first factor
if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_otp'])) {
    header('Location: index.php');
    exit;
}

// Already fully authenticated — skip to dashboard
if (!empty($_SESSION['user'])) {
    header('Location: dashboard/index.php');
    exit;
}

$error       = null;
$resent      = false;
$maskedEmail = maskEmail($_SESSION['2fa_email'] ?? '');

// ── HANDLE FORM SUBMISSION ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'verify';

    // RESEND: generate a new OTP and save to DB
    if ($action === 'resend') {
        $newOtp = generateOtp();
        if (storeOtp($_SESSION['2fa_email'], $newOtp)) {
            $_SESSION['2fa_otp']     = $newOtp;
            $_SESSION['2fa_expires'] = time() + 600;
            $resent = true;
        } else {
            $error = 'Failed to generate a new code. Please try again.';
        }

    // VERIFY: check the submitted code against the session OTP
    } else {
        // Strip spaces/dashes the user might type between digits
        $submitted = preg_replace('/[\s\-]/', '', trim($_POST['otp'] ?? ''));

        if (!$submitted) {
            $error = 'Please enter the 6-digit code.';

        } elseif (time() > ($_SESSION['2fa_expires'] ?? 0)) {
            unset($_SESSION['2fa_otp']);
            $error = 'Your verification code has expired. Please sign in again.';

        } elseif (!hash_equals((string)$_SESSION['2fa_otp'], $submitted)) {
            // hash_equals() is timing-safe — prevents timing-based brute force
            $error = 'Incorrect code. Please check phpMyAdmin → tblOTPLog and try again.';

        } else {
            // ── SUCCESS ───────────────────────────────────────────────────────
            // Mark the OTP as used in the DB so it cannot be replayed
            markOtpUsed($_SESSION['2fa_email'], $_SESSION['2fa_otp']);

            // Promote staged user data to a live session
            // USERNAME REMOVED: '2fa_user' no longer has a 'username' key
            $_SESSION['user'] = $_SESSION['2fa_user'];

            // Capture optional return URL (set by attendance module)
            $returnUrl = $_SESSION['2fa_return'] ?? 'dashboard/index.php';
            if (strpos($returnUrl, '://') !== false || strpos($returnUrl, '//') === 0) {
                $returnUrl = 'dashboard/index.php';
            }

            // Clear all 2FA staging keys
            unset(
                $_SESSION['2fa_pending'],
                $_SESSION['2fa_staffId'],
                $_SESSION['2fa_email'],
                $_SESSION['2fa_otp'],
                $_SESSION['2fa_expires'],
                $_SESSION['2fa_user'],
                $_SESSION['2fa_return']
            );

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            header('Location: ' . $returnUrl);
            exit;
        }
    }
}

// ── HELPERS ───────────────────────────────────────────────────────────────────

function generateOtp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function storeOtp(string $email, string $otp): bool {
    try {
        $pdo = db();
        $pdo->prepare("DELETE FROM tblOTPLog WHERE email = ? AND used = FALSE")->execute([$email]);
        return $pdo->prepare("
            INSERT INTO tblOTPLog (email, otp, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ")->execute([$email, $otp]);
    } catch (PDOException $e) {
        return false;
    }
}

function markOtpUsed(string $email, string $otp): void {
    try {
        db()->prepare("UPDATE tblOTPLog SET used = TRUE WHERE email = ? AND otp = ?")
           ->execute([$email, $otp]);
    } catch (PDOException $e) {}
}

function maskEmail(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    $masked = substr($parts[0], 0, 1) . str_repeat('*', max(3, strlen($parts[0]) - 1));
    return $masked . '@' . $parts[1];
}

$secondsLeft = max(0, ($_SESSION['2fa_expires'] ?? 0) - time());
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSync — Verify Your Identity</title>
<link rel="stylesheet" href="login.css">
<style>
  .otp-wrap { display:flex; gap:10px; justify-content:center; margin:24px 0 8px; }
  .otp-digit {
    width:48px; height:56px; text-align:center; font-size:1.5rem; font-weight:700;
    border:1.5px solid #2e2c27; border-radius:10px; background:#242320; color:#f0ede5;
    font-family:'Inter',system-ui,monospace; outline:none;
    transition:border-color .15s, box-shadow .15s; caret-color:#60a5fa;
  }
  .otp-digit:focus { border-color:#60a5fa; box-shadow:0 0 0 3px rgba(96,165,250,.2); }
  .otp-digit.filled { border-color:#4ade80; background:#0f2016; }
  .otp-timer { text-align:center; font-size:.8rem; color:#8a8880; margin-bottom:20px; }
  .otp-timer.warning { color:#f97316; }
  .otp-timer.expired { color:#f87171; }
  .callout-success {
    padding:12px 14px; border-radius:8px; font-size:.855rem; margin-bottom:20px;
    background:#0a2218; border-left:3px solid #4ade80; color:#86efac;
  }
  .resend-row { text-align:center; font-size:.8rem; color:#8a8880; margin-top:18px; }
  .resend-row button {
    background:none; border:none; color:#60a5fa; font-size:.8rem;
    cursor:pointer; padding:0; text-decoration:underline; font-family:inherit;
  }
  .back-link { display:block; text-align:center; font-size:.8rem; color:#8a8880; margin-top:14px; text-decoration:none; }
  .back-link:hover { color:#f0ede5; }
  .shield-icon {
    width:52px; height:52px; border-radius:14px; background:#0c1f38;
    border:1.5px solid #1e3a5f; display:flex; align-items:center;
    justify-content:center; margin:0 auto 20px; font-size:1.5rem;
  }
  .dev-hint {
    margin-top:16px; padding:12px 14px; background:#1a1500;
    border:1px solid #3d3200; border-radius:8px; font-size:.78rem;
    color:#d4a800; text-align:center; line-height:1.6;
  }
  .dev-hint strong { color:#fbbf24; }
</style>
</head>
<body>

<div class="login-wrap">
  <div class="login-card">

    <div class="login-top-row">
      <a href="landing.php" class="login-logo">
        <img src="shared/logo.png" alt="EduSync Logo" class="login-logo-img">
        <span class="login-logo-name">EduSync</span>
      </a>
      <a href="index.php" class="login-back-home">&#8592; Back to Login</a>
    </div>

    <div class="shield-icon">🔐</div>
    <div class="login-title">Verify your identity</div>
    <div class="login-sub" style="margin-bottom:20px;">
      Enter the 6-digit code for <strong style="color:#f0ede5;"><?= htmlspecialchars($maskedEmail) ?></strong>
    </div>

    <?php if ($error): ?>
      <div class="callout callout-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($resent): ?>
      <div class="callout-success">✅ A new code has been saved. Check phpMyAdmin → tblOTPLog.</div>
    <?php endif; ?>

    <form method="POST" action="2fa_verify.php" id="otpForm">
      <input type="hidden" name="action" value="verify">
      <input type="hidden" name="otp" id="otpHidden">

      <div class="otp-wrap" id="otpBoxes">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input
            class="otp-digit" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"
            autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
            data-index="<?= $i ?>" <?= $i === 0 ? 'autofocus' : '' ?>
          >
        <?php endfor; ?>
      </div>

      <div class="otp-timer" id="timerDisplay">
        Code expires in <span id="timerValue"><?= $secondsLeft ?></span>s
      </div>

      <button type="submit" class="btn-primary" id="verifyBtn">Verify Code</button>
    </form>

    <div class="resend-row">
      Need a new code?
      <form method="POST" action="2fa_verify.php" style="display:inline;">
        <input type="hidden" name="action" value="resend">
        <button type="submit">Resend code</button>
      </form>
    </div>

    <a href="index.php" class="back-link">← Sign in with a different account</a>

    <div class="dev-hint">
      🛠️ <strong>Development mode</strong><br>
      Open <strong>phpMyAdmin → edusync → tblOTPLog</strong><br>
      and read the <strong>otp</strong> column for the latest code.
    </div>

  </div>
</div>

<script>
const boxes = Array.from(document.querySelectorAll('.otp-digit'));
const hidden = document.getElementById('otpHidden');
const form = document.getElementById('otpForm');
const verifyBtn = document.getElementById('verifyBtn');

function syncHidden() {
    const val = boxes.map(b => b.value).join('');
    hidden.value = val;
    boxes.forEach(b => b.classList.toggle('filled', b.value !== ''));
    verifyBtn.disabled = (val.length < 6);
}

verifyBtn.disabled = true;

boxes.forEach((box, idx) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        syncHidden();
        if (box.value && idx < 5) boxes[idx + 1].focus();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && idx > 0) boxes[idx - 1].focus();
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((d, i) => { if (boxes[i]) boxes[i].value = d; });
        syncHidden();
        boxes[Math.min(digits.length, 5)].focus();
    });
});

form.addEventListener('input', () => { if (hidden.value.length === 6) form.submit(); });

let secondsLeft = <?= (int)$secondsLeft ?>;
const timerEl = document.getElementById('timerDisplay');
const timerValue = document.getElementById('timerValue');

function formatTime(s) {
    const m = Math.floor(s / 60), sec = s % 60;
    return m > 0 ? `${m}m ${String(sec).padStart(2,'0')}s` : `${sec}s`;
}

function tick() {
    if (secondsLeft <= 0) {
        timerEl.className = 'otp-timer expired';
        timerEl.textContent = 'Code expired — click Resend code to get a new one.';
        verifyBtn.disabled = true;
        clearInterval(timer);
        return;
    }
    timerValue.textContent = formatTime(secondsLeft);
    timerEl.className = 'otp-timer' + (secondsLeft <= 60 ? ' warning' : '');
    secondsLeft--;
}

tick();
const timer = setInterval(tick, 1000);
</script>

</body>
</html>
