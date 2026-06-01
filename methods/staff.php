<?php

/**
 * methods/staff.php
 *
 * WHAT THIS FILE DOES:
 *   Middle layer class for the Staff Management component.
 *   Encapsulates all database operations for tblStaff.
 *   Acts as the business logic layer between the presentation layer
 *   (staff/add.php, staff/edit.php, etc.) and the data layer (MySQL).
 *   All DB operations call stored procedures to keep SQL in the data layer.
 *
 * CHANGES FROM ORIGINAL:
 *   - getByUsername() — now also returns the email field from the DB
 *   - getByEmail()    — NEW method for email-based login lookup
 *   - create()        — added $email parameter; calls sp_AddStaff with 5 args
 *   - update()        — added $email parameter; calls sp_UpdateStaff/WithPassword with extra arg
 *   - emailExists()   — NEW validation method; calls sp_CheckEmailExists
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

require_once __DIR__ . '/../shared/db.php';

class Staff {

    // ── PDO INSTANCE ─────────────────────────────────────────────────────────

    /**
     * Shared PDO connection injected via the constructor.
     * All methods use this connection — no static calls, no raw mysqli.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor — injects the PDO connection.
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
     * Used by the staff list page (staff/index.php).
     * Calls stored procedure: sp_GetAllStaff
     *
     * @return array  Array of all staff rows (may be empty if no staff exist).
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
     * Used by edit.php and delete.php.
     * Calls stored procedure: sp_GetStaffById
     *
     * @param  int          $staffId  The unique staff identifier.
     * @return array|false  The staff row if found, or false if not found.
     */
    public function getById(int $staffId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffById(?)");
        $stmt->execute([$staffId]);
        return $stmt->fetch();
    }

    /**
     * getByUsername()
     *
     * Retrieves a staff record by username.
     * Kept for internal lookups (e.g. session data display).
     * CHANGED: now also returns the email field.
     * Calls stored procedure: sp_GetStaffByUsername
     *
     * @param  string       $username  The username to look up.
     * @return array|false  The staff row if found, or false if not found.
     */
    public function getByUsername(string $username): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffByUsername(?)");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /**
     * getByEmail()   ← NEW METHOD
     *
     * Retrieves a staff record by email address.
     * Used during login authentication (replaces the old username-based lookup).
     * Only returns rows where isStaffActive = TRUE (handled in the stored procedure).
     * Calls stored procedure: sp_GetStaffByEmail
     *
     * WHY a separate method instead of changing getByUsername()?
     *   Separation of concerns — username is still used in session data and
     *   display contexts. Email is specifically the login credential.
     *   Keeping them separate makes the code self-documenting.
     *
     * @param  string       $email  The email address to look up.
     * @return array|false  The staff row if found (and active), or false if not.
     */
    public function getByEmail(string $email): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffByEmail(?)");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        // Drain all result sets MySQL stored procedures leave open
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
     * CHANGED: added $email parameter (3rd argument).
     * Calls stored procedure: sp_AddStaff (now takes 5 arguments, was 4)
     *
     * WHY hash here and not in the stored procedure?
     *   MySQL cannot call PHP's password_hash(). Hashing in the PHP model
     *   layer keeps the logic in one language and means the stored procedure
     *   only ever receives an already-hashed value — the plaintext password
     *   never travels over the DB connection.
     *
     * @param  string $fullName  Staff member's full name.
     * @param  string $username  Unique login username (display/internal use).
     * @param  string $email     Unique email address (used to log in).
     * @param  string $password  Plaintext password (bcrypt-hashed before storing).
     * @param  string $role      Role: 'Administrator', 'Teacher', or 'Headteacher'.
     * @return bool              TRUE on success, FALSE on failure.
     */
    public function create(string $fullName, string $username, string $email, string $password, string $role): bool {
        // password_hash() with PASSWORD_BCRYPT automatically generates a salt
        // and produces a 60-character hash string safe for VARCHAR(255).
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // sp_AddStaff now takes 5 parameters: fullName, username, email, hash, role
        $stmt = $this->pdo->prepare("CALL sp_AddStaff(?, ?, ?, ?, ?)");
        return $stmt->execute([$fullName, $username, $email, $passwordHash, $role]);
    }

    /**
     * update()
     *
     * Updates an existing staff record.
     * CHANGED: added $email parameter (4th argument).
     * If $password is provided, it is hashed and the password is updated too.
     * If $password is an empty string, the existing hash is preserved.
     * Calls stored procedure: sp_UpdateStaff (5 args) or sp_UpdateStaffWithPassword (6 args)
     *
     * @param  int    $staffId   ID of the staff record to update.
     * @param  string $fullName  Updated full name.
     * @param  string $username  Updated username.
     * @param  string $email     Updated email address.
     * @param  string $role      Updated role.
     * @param  string $password  New password (empty string = keep existing).
     * @return bool              TRUE on success, FALSE on failure.
     */
    public function update(int $staffId, string $fullName, string $username, string $email, string $role, string $password = ''): bool {
        if ($password !== '') {
            // Password is being changed — hash it and use the 6-parameter procedure
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("CALL sp_UpdateStaffWithPassword(?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$staffId, $fullName, $username, $email, $role, $passwordHash]);
        } else {
            // No password change — use the 5-parameter procedure
            // The existing passwordHash in the DB is left untouched
            $stmt = $this->pdo->prepare("CALL sp_UpdateStaff(?, ?, ?, ?, ?)");
            return $stmt->execute([$staffId, $fullName, $username, $email, $role]);
        }
    }

    /**
     * delete()
     *
     * Permanently removes a staff record from tblStaff.
     * Should only be called after checking isAssignedToClass().
     * Calls stored procedure: sp_DeleteStaff
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
     * Calls stored procedure: sp_ToggleStaffStatus
     *
     * @param  int  $staffId  ID of the staff record to toggle.
     * @return bool           TRUE on success, FALSE on failure.
     */
    public function toggleStatus(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_ToggleStaffStatus(?)");
        return $stmt->execute([$staffId]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  VALIDATION / BUSINESS LOGIC METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * usernameExists()
     *
     * Checks whether a username is already taken by another staff member.
     * Used by add.php and edit.php before saving.
     * Calls stored procedure: sp_CheckUsernameExists
     *
     * @param  string $username   The username to check for uniqueness.
     * @param  int    $excludeId  Staff ID to exclude (for edit forms — exclude the current record).
     *                            Pass 0 for add forms.
     * @return bool               TRUE if the username is already in use, FALSE if available.
     */
    public function usernameExists(string $username, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("CALL sp_CheckUsernameExists(?, ?)");
        $stmt->execute([$username, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * emailExists()   ← NEW METHOD
     *
     * Checks whether an email address is already taken by another staff member.
     * Used by add.php and edit.php before saving.
     * Calls stored procedure: sp_CheckEmailExists
     *
     * WHY is this a separate check and not combined with usernameExists()?
     *   Username uniqueness and email uniqueness are two separate database
     *   constraints (UNIQUE indexes on different columns). Checking them
     *   separately gives more specific error messages to the user:
     *     "Username already taken"  vs  "Email already in use"
     *   Combining them would produce a generic error that doesn't tell
     *   the administrator which field to change.
     *
     * @param  string $email      The email address to check for uniqueness.
     * @param  int    $excludeId  Staff ID to exclude (for edit forms).
     *                            Pass 0 for add forms.
     * @return bool               TRUE if the email is already in use, FALSE if available.
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
     * Checks whether a staff member is currently the class teacher of any active class.
     * Used before allowing deletion or deactivation to prevent orphaned classes.
     * Calls stored procedure: sp_IsStaffAssignedToClass
     *
     * @param  int  $staffId  ID of the staff member to check.
     * @return bool           TRUE if assigned to at least one active class, FALSE otherwise.
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
     * Wraps PHP's password_verify() for use in authentication contexts.
     * Timing-safe by design — always takes the same amount of time.
     *
     * @param  string $password      Plaintext password submitted by the user.
     * @param  string $passwordHash  Bcrypt hash stored in tblStaff.
     * @return bool                  TRUE if the password matches, FALSE otherwise.
     */
    public function verifyPassword(string $password, string $passwordHash): bool {
        return password_verify($password, $passwordHash);
    }

    // ── PASSWORD STRENGTH ─────────────────────────────────────────────────────

    /**
     * validatePasswordStrength()
     *
     * Checks a plaintext password against EduSync's password policy.
     * This is the AUTHORITATIVE server-side check — the JS strength meter
     * in add.php and edit.php is purely cosmetic and can be bypassed.
     *
     * Policy (aligned with NIST SP 800-63B guidance):
     *   ✓ Minimum 8 characters
     *   ✓ At least one uppercase letter (A–Z)
     *   ✓ At least one lowercase letter (a–z)
     *   ✓ At least one digit (0–9)
     *   ✓ At least one special character (anything not A–Za–z0–9)
     *   ✓ Must not be in the built-in common password list
     *
     * Returns null on success (all rules pass), or a human-readable error string
     * on the FIRST rule that fails (one message at a time for usability).
     *
     * @param  string      $password  Plaintext password to validate.
     * @return string|null            null if valid; error message string if invalid.
     */
    public function validatePasswordStrength(string $password): ?string {

        // Rule 1: minimum length
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }

        // Rule 2: at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }

        // Rule 3: at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }

        // Rule 4: at least one digit
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }

        // Rule 5: at least one special character
        // [^A–Za–z0–9] matches any character that is NOT a letter or digit
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Password must contain at least one special character (e.g. !@#$%).';
        }

        // Rule 6: block common passwords that technically pass the rules above
        // (e.g. "Password1!" is 10 chars, has upper/lower/digit/special — but it's trivially guessable)
        $commonPasswords = [
            'Password1!', 'Password1@', 'Welcome1!',  'Admin1234!',
            'Qwerty123!', 'Letmein1!',  'Changeme1!', 'Abc12345!',
            'Iloveyou1!', 'Sunshine1!', 'School123!', 'Summer2024!',
        ];
        if (in_array($password, $commonPasswords, true)) {
            return 'That password is too common. Please choose a more unique one.';
        }

        return null;  // All rules passed — password is acceptable
    }

    /**
     * countAdmins()
     *
     * Returns the number of ACTIVE Administrator accounts.
     * Only active admins are counted — inactive admins cannot log in and
     * must not count toward the "at least one admin must remain" rule.
     * Used by delete.php and toggle.php before removing/deactivating an admin.
     * Calls stored procedure: sp_CountAdmins
     *
     * @return int  Number of active Administrator accounts.
     */
    public function countAdmins(): int {
        $stmt = $this->pdo->prepare("CALL sp_CountAdmins()");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
