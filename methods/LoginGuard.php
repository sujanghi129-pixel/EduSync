<?php

/**
 * shared/LoginGuard.php
 *
 * Brute-force and credential-stuffing protection for EduSync login endpoints.
 *
 * Behaviour
 * ─────────
 *  - After MAX_FAILS consecutive failures for a username the account is locked
 *    for LOCK_MINUTES minutes. The lock is stored in tblLoginAttempts so it
 *    survives PHP restarts and works across multiple web-server processes.
 *  - A successful login immediately clears the counter via clearFailures().
 *  - The client IP is recorded for audit purposes but is NOT used to gate access
 *    on its own — IP-based blocking is better handled at the web-server / WAF
 *    level where it can be managed without code deploys.
 *  - All database calls go through stored procedures (sp_GetLoginStatus,
 *    sp_RecordLoginFailure, sp_ClearLoginFailures) to stay consistent with
 *    EduSync's three-layer architecture.
 *
 * Usage (both login endpoints follow the same pattern)
 * ────────────────────────────────────────────────────
 *  require_once __DIR__ . '/../shared/LoginGuard.php';
 *  $guard = new LoginGuard(db());
 *
 *  // 1. Before checking credentials:
 *  $status = $guard->check($username);
 *  if ($status['locked']) {
 *      $error = $status['message'];
 *      // ... render the form and exit
 *  }
 *
 *  // 2. On bad credentials:
 *  $status = $guard->recordFailure($username);
 *  $error  = $status['message'];   // includes "X attempts remaining" hint
 *
 *  // 3. On successful login:
 *  $guard->clearFailures($username);
 *
 * @package EduSync
 */

class LoginGuard {

    // ── Policy constants ──────────────────────────────────
    // Change these values to adjust the lockout policy.

    /** Maximum consecutive failures before the account is locked. */
    private const MAX_FAILS = 5;

    /** How many minutes a locked account must wait before retrying. */
    private const LOCK_MINUTES = 15;

    // ── PDO instance ──────────────────────────────────────

    /** @var PDO */
    private PDO $pdo;

    /**
     * @param PDO $pdo  Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ─────────────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────────────

    /**
     * Check whether a username is currently locked out.
     *
     * Call this BEFORE attempting to verify credentials so that
     * locked accounts never trigger a bcrypt comparison (which
     * would let attackers use timing to infer account existence).
     *
     * @param  string $username  The normalised (lowercase, trimmed) username.
     * @return array {
     *     bool   locked   — true if the account is locked right now
     *     string message  — human-readable explanation (empty when not locked)
     * }
     */
    public function check(string $username): array {
        $row = $this->getStatus($username);

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
     * Record a failed login attempt for a username.
     *
     * Call this when credentials are wrong. Returns the updated status
     * so the caller can include a "X attempts remaining" hint in the
     * error message shown to the user.
     *
     * @param  string $username  Normalised username.
     * @return array {
     *     bool   locked   — true if this failure triggered a lockout
     *     string message  — error message to display in the form
     * }
     */
    public function recordFailure(string $username): array {
        $ip = $this->clientIp();

        $stmt = $this->pdo->prepare("CALL sp_RecordLoginFailure(?, ?, ?, ?)");
        $stmt->execute([$username, $ip, self::MAX_FAILS, self::LOCK_MINUTES]);

        // Re-read the updated status so we can give the user an accurate hint
        $row = $this->getStatus($username);

        if ($row && (bool)$row['isLocked']) {
            return [
                'locked'  => true,
                'message' => "Too many failed attempts. This account is locked for " . self::LOCK_MINUTES . " minutes.",
            ];
        }

        $remaining = self::MAX_FAILS - (int)($row['failCount'] ?? 0);
        $remaining = max(0, $remaining);
        $hint      = $remaining > 0
            ? " ({$remaining} attempt(s) remaining before lockout)"
            : '';

        return [
            'locked'  => false,
            'message' => 'Invalid username or password.' . $hint,
        ];
    }

    /**
     * Clear the failure counter for a username after a successful login.
     *
     * @param  string $username  Normalised username.
     * @return void
     */
    public function clearFailures(string $username): void {
        $stmt = $this->pdo->prepare("CALL sp_ClearLoginFailures(?)");
        $stmt->execute([$username]);
    }

    // ─────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────

    /**
     * Fetch the current lockout row for a username, or null if none exists.
     *
     * @param  string     $username
     * @return array|null
     */
    private function getStatus(string $username): ?array {
        $stmt = $this->pdo->prepare("CALL sp_GetLoginStatus(?)");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Calculate how many whole minutes remain until a lockout expires.
     *
     * @param  string $lockedUntil  MySQL DATETIME string.
     * @return int    Minutes remaining (minimum 1 so the message is never "0 minutes").
     */
    private function minutesRemaining(string $lockedUntil): int {
        $diff = strtotime($lockedUntil) - time();
        return max(1, (int)ceil($diff / 60));
    }

    /**
     * Return the client's IP address, respecting common reverse-proxy headers.
     * Falls back to REMOTE_ADDR when no proxy header is present.
     *
     * Note: X-Forwarded-For can be spoofed by clients. Only trust it when your
     * deployment sits behind a known reverse proxy (nginx, Apache, load balancer).
     * For a simple school LAN deployment REMOTE_ADDR is perfectly sufficient.
     *
     * @return string
     */
    private function clientIp(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Header may contain a comma-separated list; the first entry is the client
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}