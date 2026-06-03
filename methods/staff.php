<?php

/**
 * methods/staff.php
 *
 * Middle layer class for the Staff Management component.
 * Encapsulates all database operations for tblStaff.
 *
 * USERNAME REMOVED:
 *   - getByUsername()    — method REMOVED (username column gone)
 *   - usernameExists()   — method REMOVED (username column gone)
 *   - create()           — $username parameter REMOVED (now 4 params)
 *   - update()           — $username parameter REMOVED (now 5 params)
 *
 * Staff now identified by email only. All stored procedure calls
 * updated to match the new parameter counts.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

require_once __DIR__ . '/../shared/db.php';

class Staff {

    /** @var PDO  Active database connection injected via constructor. */
    private PDO $pdo;

    /**
     * Constructor — injects the PDO connection.
     * All methods use this connection; no static calls or raw mysqli.
     *
     * @param PDO $pdo  Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  READ METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * getAll()
     *
     * Retrieves every staff record ordered by staffId ascending.
     * Returns: staffId, fullName, email, role, isStaffActive, staffCreatedAt.
     * Used by staff/index.php.
     * Calls: sp_GetAllStaff
     *
     * @return array  All staff rows (may be empty).
     */
    public function getAll(): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAllStaff()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * getById()
     *
     * Retrieves a single staff record by their unique staffId.
     * Returns: staffId, fullName, email, role, isStaffActive, staffCreatedAt.
     * Used by edit.php and delete.php.
     * Calls: sp_GetStaffById
     *
     * @param  int          $staffId  The unique staff identifier.
     * @return array|false  The staff row if found, false if not found.
     */
    public function getById(int $staffId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffById(?)");
        $stmt->execute([$staffId]);
        return $stmt->fetch();
    }

    /**
     * getByEmail()
     *
     * Retrieves a staff record by email address.
     * Used during login authentication.
     * Only returns rows where isStaffActive = TRUE (handled by stored procedure).
     * Calls: sp_GetStaffByEmail
     *
     * @param  string       $email  The email address to look up.
     * @return array|false  The staff row if found and active, false if not.
     */
    public function getByEmail(string $email): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffByEmail(?)");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        // Drain all result sets MySQL stored procedures leave open.
        // Without this, the next query on the same connection would fail.
        try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
        $stmt->closeCursor();

        return $row;
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  WRITE METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * create()
     *
     * Inserts a new staff record into tblStaff.
     * Hashes the password with bcrypt before storing.
     * USERNAME REMOVED: was create($fullName, $username, $email, $password, $role)
     *                   now create($fullName, $email, $password, $role) — 4 params
     * Calls: sp_AddStaff (now takes 4 params, was 5)
     *
     * @param  string $fullName  Staff member's full name.
     * @param  string $email     Unique email address (login credential).
     * @param  string $password  Plaintext password (bcrypt-hashed before storing).
     * @param  string $role      'Administrator', 'Teacher', or 'Headteacher'.
     * @return bool              TRUE on success, FALSE on failure.
     */
    public function create(string $fullName, string $email, string $password, string $role): bool {
        // password_hash() with PASSWORD_BCRYPT generates a salt automatically
        // and produces a 60-character string safe for VARCHAR(255).
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // sp_AddStaff now takes 4 params (fullName, email, hash, role)
        $stmt = $this->pdo->prepare("CALL sp_AddStaff(?, ?, ?, ?)");
        return $stmt->execute([$fullName, $email, $passwordHash, $role]);
    }

    /**
     * update()
     *
     * Updates an existing staff record.
     * If $password is provided, it is hashed and the password is updated too.
     * If $password is empty, the existing hash is preserved.
     * USERNAME REMOVED: was update($staffId, $fullName, $username, $email, $role, $password)
     *                   now update($staffId, $fullName, $email, $role, $password) — 5 params
     * Calls: sp_UpdateStaff (4 params) or sp_UpdateStaffWithPassword (5 params)
     *
     * @param  int    $staffId   ID of the staff record to update.
     * @param  string $fullName  Updated full name.
     * @param  string $email     Updated email address.
     * @param  string $role      Updated role.
     * @param  string $password  New password (empty string = keep existing hash).
     * @return bool              TRUE on success, FALSE on failure.
     */
    public function update(int $staffId, string $fullName, string $email, string $role, string $password = ''): bool {
        if ($password !== '') {
            // Password is being changed — hash it and use the 5-param procedure
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("CALL sp_UpdateStaffWithPassword(?, ?, ?, ?, ?)");
            return $stmt->execute([$staffId, $fullName, $email, $role, $passwordHash]);
        } else {
            // No password change — use the 4-param procedure
            // The existing passwordHash in the DB is left untouched
            $stmt = $this->pdo->prepare("CALL sp_UpdateStaff(?, ?, ?, ?)");
            return $stmt->execute([$staffId, $fullName, $email, $role]);
        }
    }

    /**
     * delete()
     *
     * Permanently removes a staff record from tblStaff.
     * Call isAssignedToClass() first to prevent orphaned classes.
     * Calls: sp_DeleteStaff
     *
     * @param  int  $staffId  ID of the staff record to delete.
     * @return bool           TRUE on success, FALSE on failure.
     */
    public function delete(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_DeleteStaff(?)");
        return $stmt->execute([$staffId]);
    }

    /**
     * toggleStatus()
     *
     * Flips a staff member's isStaffActive flag between TRUE and FALSE.
     * Used by the Activate / Deactivate button on the staff list.
     * Calls: sp_ToggleStaffStatus
     *
     * @param  int  $staffId  ID of the staff record to toggle.
     * @return bool           TRUE on success, FALSE on failure.
     */
    public function toggleStatus(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_ToggleStaffStatus(?)");
        return $stmt->execute([$staffId]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  VALIDATION METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * emailExists()
     *
     * Checks whether an email address is already taken by another staff member.
     * Used by add.php and edit.php before saving.
     * Pass $excludeId on edit forms to exclude the current record from the check
     * (otherwise editing without changing the email would always fail).
     * Calls: sp_CheckEmailExists
     *
     * @param  string $email      The email address to check.
     * @param  int    $excludeId  StaffId to exclude (0 for add forms).
     * @return bool               TRUE if already in use, FALSE if available.
     */
    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("CALL sp_CheckEmailExists(?, ?)");
        $stmt->execute([$email, $excludeId]);
        $taken = (bool)$stmt->fetchColumn();

        // Drain MySQL stored procedure result sets
        try { while ($stmt->nextRowset()) {} } catch (PDOException $e) {}
        $stmt->closeCursor();

        return $taken;
    }

    /**
     * isAssignedToClass()
     *
     * Returns TRUE if the staff member is the class teacher of any active class.
     * Used before deletion or deactivation to prevent orphaned classes.
     * Calls: sp_IsStaffAssignedToClass
     *
     * @param  int  $staffId  ID of the staff member to check.
     * @return bool           TRUE if assigned to at least one active class.
     */
    public function isAssignedToClass(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_IsStaffAssignedToClass(?)");
        $stmt->execute([$staffId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * verifyPassword()
     *
     * Compares a plaintext password against a stored bcrypt hash.
     * Timing-safe by design — always takes the same amount of time.
     *
     * @param  string $password      Plaintext password from the login form.
     * @param  string $passwordHash  Bcrypt hash from tblStaff.
     * @return bool                  TRUE if the password matches.
     */
    public function verifyPassword(string $password, string $passwordHash): bool {
        return password_verify($password, $passwordHash);
    }

    // ── PASSWORD STRENGTH ─────────────────────────────────────────────────────

    /**
     * validatePasswordStrength()
     *
     * Server-side authoritative password policy check.
     * The JS strength meter in the forms is cosmetic only — this is what counts.
     *
     * Rules (NIST SP 800-63B aligned):
     *   ✓ Minimum 8 characters
     *   ✓ At least one uppercase letter
     *   ✓ At least one lowercase letter
     *   ✓ At least one digit
     *   ✓ At least one special character
     *   ✓ Not in the common password list
     *
     * Returns null when the password passes all rules.
     * Returns a string error message on the first rule that fails.
     *
     * @param  string      $password  Plaintext password to validate.
     * @return string|null            null if valid; error message if not.
     */
    public function validatePasswordStrength(string $password): ?string {

        if (strlen($password) < 8)
            return 'Password must be at least 8 characters.';

        if (!preg_match('/[A-Z]/', $password))
            return 'Password must contain at least one uppercase letter.';

        if (!preg_match('/[a-z]/', $password))
            return 'Password must contain at least one lowercase letter.';

        if (!preg_match('/[0-9]/', $password))
            return 'Password must contain at least one number.';

        // [^A-Za-z0-9] matches any character that is NOT a letter or digit
        if (!preg_match('/[^A-Za-z0-9]/', $password))
            return 'Password must contain at least one special character (e.g. !@#$%).';

        // Block common passwords that technically pass all rules above
        $commonPasswords = [
            'Password1!', 'Password1@', 'Welcome1!',  'Admin1234!',
            'Qwerty123!', 'Letmein1!',  'Changeme1!', 'Abc12345!',
            'Iloveyou1!', 'Sunshine1!', 'School123!', 'Summer2024!',
        ];
        if (in_array($password, $commonPasswords, true))
            return 'That password is too common. Please choose a more unique one.';

        return null; // All rules passed
    }

    /**
     * countAdmins()
     *
     * Returns the number of ACTIVE Administrator accounts.
     * Used by delete.php and toggle.php to ensure at least one admin always remains.
     * Calls: sp_CountAdmins
     *
     * @return int  Number of active Administrator accounts.
     */
    public function countAdmins(): int {
        $stmt = $this->pdo->prepare("CALL sp_CountAdmins()");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
