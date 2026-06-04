<?php

/**
 * methods/LoginGuard.php
 *
 * WHAT THIS FILE DOES:
 *   Brute-force and credential-stuffing protection for EduSync login endpoints.
 *
 *   It tracks failed login attempts in tblLoginAttempts. After MAX_FAILS
 *   consecutive failures for an email address, the account is locked for
 *   LOCK_MINUTES minutes. All lock state is stored in the database so it
 *   survives PHP restarts and works across multiple Apache processes.
 *
 *   All three public methods follow the same pattern in index.php and check.php:
 *     1. check()         — call BEFORE password_verify() (rejects locked accounts)
 *     2. recordFailure() — call on wrong credentials (increments counter)
 *     3. clearFailures() — call on successful login (resets counter)
 *
 * CHANGE FROM ORIGINAL:
 *   - All three public methods changed parameter name from $username → $email.
 *   - tblLoginAttempts.username column renamed to .email in the database.
 *   - Stored procedures updated:
 *       sp_GetLoginStatus($username)   →  sp_GetLoginStatus($email)
 *       sp_RecordLoginFailure($username, ...) →  sp_RecordLoginFailure($email, ...)
 *       sp_ClearLoginFailures($username) →  sp_ClearLoginFailures($email)
 *   - Error message "Invalid username or password" → "Invalid email or password"
 *
 * WHY THE LOCKOUT KEY IS THE EMAIL (NOT THE IP ADDRESS)?
 *   Locking by IP address would block a legitimate user sharing an IP with
 *   an attacker (e.g. on a shared school network). Locking by email means
 *   only that specific account is locked, not the whole subnet.
 *   The client IP is still recorded in tblLoginAttempts for audit purposes,
 *   but it is not used to gate access.
 *
 * @package EduSync
 */

class LoginGuard {

    // ── POLICY CONSTANTS ─────────────────────────────────────────────────────
    // Centralised here so the lockout policy can be changed in one place.

    /**
     * Maximum consecutive failures before the account is locked.
     * After this many wrong attempts the user must wait LOCK_MINUTES minutes.
     */
    private const MAX_FAILS = 5;

    /**
     * How many minutes a locked account must wait before retrying.
     */
    private const LOCK_MINUTES = 15;

    // ── PDO INSTANCE ─────────────────────────────────────────────────────────

    /** @var PDO  Active database connection from db(). */
    private PDO $pdo;

    /**
     * Constructor — injects the PDO connection.
     * All database calls use this connection to stay within EduSync's
     * three-layer architecture (no raw queries in presentation layer files).
     *
     * @param PDO $pdo  Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ── PUBLIC API ────────────────────────────────────────────────────────────

    /**
     * check()
     *
     * Determines whether an email address is currently locked out.
     *
     * CALL THIS BEFORE password_verify().
     * Why? If we checked the password first, locked accounts would still
     * trigger a bcrypt comparison. bcrypt is intentionally slow (to resist
     * brute force), but that slowness creates a timing oracle: an attacker
     * could tell "this account exists" because locked accounts with a real
     * bcrypt hash take longer to reject than accounts that don't exist.
     * Checking the lock first means locked accounts always return instantly.
     *
     * @param  string $email  The normalised (lowercase, trimmed) email address.
     * @return array {
     *     bool   locked   — TRUE if the account is currently locked
     *     string message  — Human-readable explanation (empty string if not locked)
     * }
     */
    public function check(string $email): array {
        $row = $this->getStatus($email);

        // isLocked is a computed MySQL column: (lockedUntil IS NOT NULL AND lockedUntil > NOW())
        if ($row && (bool)$row['isLocked']) {
            $wait = $this->minutesRemaining($row['lockedUntil']);
            return [
                'locked'  => true,
                'message' => "Too many failed attempts. This account is locked for {$wait} more minute(s). Please try again later.",
            ];
        }

        return ['locked' => false, 'message' => ''];
    }

    /**
     * recordFailure()
     *
     * Records a failed login attempt for an email address and returns
     * an updated status message to display to the user.
     *
     * CALL THIS when credentials are wrong.
     * The message includes a "X attempts remaining" hint so the user knows
     * how many more tries they have before being locked out.
     *
     * @param  string $email  Normalised email address of the failed attempt.
     * @return array {
     *     bool   locked   — TRUE if this failure triggered a lockout
     *     string message  — Error message to show in the login form
     * }
     */
    public function recordFailure(string $email): array {
        $ip = $this->clientIp();

        // sp_RecordLoginFailure uses ON DUPLICATE KEY UPDATE to either insert
        // a new row or increment the existing one. It automatically sets
        // lockedUntil when failCount reaches MAX_FAILS.
        $stmt = $this->pdo->prepare("CALL sp_RecordLoginFailure(?, ?, ?, ?)");
        $stmt->execute([$email, $ip, self::MAX_FAILS, self::LOCK_MINUTES]);
        try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
        $stmt->closeCursor();

        // Re-read the row to get the updated fail count for the hint message
        $row = $this->getStatus($email);

        if ($row && (bool)$row['isLocked']) {
            return [
                'locked'  => true,
                'message' => "Too many failed attempts. This account is locked for " . self::LOCK_MINUTES . " minutes.",
            ];
        }

        // Calculate how many attempts remain before lockout
        $remaining = self::MAX_FAILS - (int)($row['failCount'] ?? 0);
        $remaining = max(0, $remaining);
        $hint      = $remaining > 0 ? " ({$remaining} attempt(s) remaining before lockout)" : '';

        return [
            'locked'  => false,
            'message' => 'Invalid email or password.' . $hint,
            // CHANGED: was "Invalid username or password." in the original
        ];
    }

    /**
     * clearFailures()
     *
     * Resets the failure counter and removes the lockout for an email address.
     *
     * CALL THIS immediately after a successful first-factor login.
     * This ensures a user who was temporarily locked out is fully unblocked
     * once they prove their identity.
     *
     * @param  string $email  Normalised email address to clear.
     * @return void
     */
    public function clearFailures(string $email): void {
        $stmt = $this->pdo->prepare("CALL sp_ClearLoginFailures(?)");
        $stmt->execute([$email]);
        try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
        $stmt->closeCursor();
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    /**
     * getStatus()
     *
     * Fetches the current lockout row for an email address from tblLoginAttempts.
     * Returns null if no row exists yet (i.e. no failed attempts recorded).
     *
     * Uses sp_GetLoginStatus which returns:
     *   failCount   — number of consecutive failures
     *   lockedUntil — DATETIME the lock expires (NULL if not locked)
     *   isLocked    — computed boolean: 1 if lockedUntil > NOW(), else 0
     *
     * @param  string     $email
     * @return array|null  The row if found, null if no record exists.
     */
    private function getStatus(string $email): ?array {
        $stmt = $this->pdo->prepare("CALL sp_GetLoginStatus(?)");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        // Drain all result sets MySQL stored procedures leave open.
        // Without this, subsequent queries on the same connection fail.
        try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
        $stmt->closeCursor();

        return $row ?: null;
    }

    /**
     * minutesRemaining()
     *
     * Calculates how many whole minutes remain until a lockout expires.
     * Used to produce the "locked for X more minute(s)" message.
     *
     * Returns a minimum of 1 so the message is never "locked for 0 minutes"
     * (which would be confusing — the user would think they can try again immediately).
     *
     * @param  string $lockedUntil  MySQL DATETIME string (e.g. "2026-06-01 15:30:00").
     * @return int    Minutes remaining, always at least 1.
     */
    private function minutesRemaining(string $lockedUntil): int {
        $diff = strtotime($lockedUntil) - time();
        return max(1, (int)ceil($diff / 60));
    }

    /**
     * clientIp()
     *
     * Returns the client's IP address for audit logging in tblLoginAttempts.
     *
     * Respects the X-Forwarded-For header when a reverse proxy (nginx, Apache,
     * a load balancer) is in front of PHP. The header can contain a comma-
     * separated list of IPs (added by each proxy hop); the first entry is the
     * original client.
     *
     * SECURITY NOTE:
     *   X-Forwarded-For can be spoofed by clients. Only trust it when your
     *   deployment sits behind a known reverse proxy. For a simple school LAN
     *   deployment, REMOTE_ADDR is perfectly sufficient and cannot be spoofed
     *   at the TCP layer.
     *
     * The IP is stored for audit purposes only — it is NOT used to gate access.
     *
     * @return string  Client IP address, or "0.0.0.0" as a safe fallback.
     */
    private function clientIp(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take the first IP in the comma-separated list (the original client)
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
