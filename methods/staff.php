<?php

/**
 * staff/Staff.php
 *
 * Middle layer class for the Staff Management component.
 *
 * This class encapsulates all database operations for the tblStaff table,
 * following the three-layer architecture pattern. It acts as the middle
 * (business logic) layer between the presentation layer (PHP pages) and
 * the data layer (MySQL database via stored procedures).
 *
 * All database operations call stored procedures defined in edusync.sql
 * to keep SQL logic in the data layer.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

require_once __DIR__ . '/../shared/db.php';

class Staff {

    // ── PDO instance ──────────────────────────────────────
    /**
     * Shared PDO connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Initialises the Staff class with a database connection.
     *
     * @param PDO $pdo - Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ══════════════════════════════════════════════════════
    //  READ METHODS
    // ══════════════════════════════════════════════════════

    /**
     * Retrieves all staff records from the database.
     * Calls stored procedure: sp_GetAllStaff
     *
     * @return array Array of all staff rows ordered by staffId ASC.
     */
    public function getAll(): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAllStaff()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single staff record by their unique ID.
     * Calls stored procedure: sp_GetStaffById
     *
     * @param  int          $staffId - The unique staff identifier.
     * @return array|false  The staff row if found, or false if not found.
     */
    public function getById(int $staffId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffById(?)");
        $stmt->execute([$staffId]);
        return $stmt->fetch();
    }

    /**
     * Retrieves a single staff record by their username.
     * Used during login authentication.
     * Calls stored procedure: sp_GetStaffByUsername
     *
     * @param  string       $username - The username to look up.
     * @return array|false  The staff row if found, or false if not found.
     */
    public function getByUsername(string $username): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStaffByUsername(?)");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // ══════════════════════════════════════════════════════
    //  WRITE METHODS
    // ══════════════════════════════════════════════════════

    /**
     * Inserts a new staff record into the database.
     * Hashes the password before storing.
     * Calls stored procedure: sp_AddStaff
     *
     * @param  string $fullName - The staff member's full name.
     * @param  string $username - The unique login username.
     * @param  string $password - The plaintext password (will be hashed).
     * @param  string $role     - The role: Administrator, Teacher or Headteacher.
     * @return bool   True on success, false on failure.
     */
    public function create(string $fullName, string $username, string $password, string $role): bool {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("CALL sp_AddStaff(?, ?, ?, ?)");
        return $stmt->execute([$fullName, $username, $passwordHash, $role]);
    }

    /**
     * Updates an existing staff record in the database.
     * If a new password is provided it is hashed before storing.
     * If password is empty the existing hash is preserved.
     * Calls stored procedure: sp_UpdateStaff / sp_UpdateStaffWithPassword
     *
     * @param  int    $staffId  - The ID of the staff record to update.
     * @param  string $fullName - Updated full name.
     * @param  string $username - Updated username.
     * @param  string $role     - Updated role.
     * @param  string $password - New password (empty string to keep existing).
     * @return bool   True on success, false on failure.
     */
    public function update(int $staffId, string $fullName, string $username, string $role, string $password = ''): bool {
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("CALL sp_UpdateStaffWithPassword(?, ?, ?, ?, ?)");
            return $stmt->execute([$staffId, $fullName, $username, $role, $passwordHash]);
        } else {
            $stmt = $this->pdo->prepare("CALL sp_UpdateStaff(?, ?, ?, ?)");
            return $stmt->execute([$staffId, $fullName, $username, $role]);
        }
    }

    /**
     * Permanently deletes a staff record from the database.
     * Calls stored procedure: sp_DeleteStaff
     *
     * @param  int  $staffId - The ID of the staff record to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_DeleteStaff(?)");
        return $stmt->execute([$staffId]);
    }

    /**
     * Toggles a staff member's active status between TRUE and FALSE.
     * Calls stored procedure: sp_ToggleStaffStatus
     *
     * @param  int  $staffId - The ID of the staff record to toggle.
     * @return bool True on success, false on failure.
     */
    public function toggleStatus(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_ToggleStaffStatus(?)");
        return $stmt->execute([$staffId]);
    }

    // ══════════════════════════════════════════════════════
    //  VALIDATION / BUSINESS LOGIC METHODS
    // ══════════════════════════════════════════════════════

    /**
     * Checks whether a username is already taken by another staff member.
     * Used during add and edit validation.
     * Calls stored procedure: sp_CheckUsernameExists
     *
     * @param  string $username  - The username to check.
     * @param  int    $excludeId - Staff ID to exclude from the check (for edit forms).
     * @return bool   True if the username is already in use, false if available.
     */
    public function usernameExists(string $username, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("CALL sp_CheckUsernameExists(?, ?)");
        $stmt->execute([$username, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Checks whether a staff member is assigned as the teacher of any active class.
     * Used before allowing deletion or deactivation.
     * Calls stored procedure: sp_IsStaffAssignedToClass
     *
     * @param  int  $staffId - The ID of the staff member to check.
     * @return bool True if assigned to at least one active class, false otherwise.
     */
    public function isAssignedToClass(int $staffId): bool {
        $stmt = $this->pdo->prepare("CALL sp_IsStaffAssignedToClass(?)");
        $stmt->execute([$staffId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Verifies a plaintext password against a stored bcrypt hash.
     * Used during login authentication.
     *
     * @param  string $password     - The plaintext password submitted by the user.
     * @param  string $passwordHash - The bcrypt hash stored in the database.
     * @return bool   True if the password matches the hash, false otherwise.
     */
    public function verifyPassword(string $password, string $passwordHash): bool {
        return password_verify($password, $passwordHash);
    }

}
