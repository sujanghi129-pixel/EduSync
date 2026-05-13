<?php

/**
 * grades/Grade.php
 *
 * Middle layer class for the Grade Management component.
 * Encapsulates all database operations for tblGrade.
 * All operations call stored procedures in the data layer.
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */

require_once __DIR__ . '/../shared/db.php';

class Grade {

    /** @var PDO $pdo Shared database connection */
    private PDO $pdo;

    /**
     * Initialises the Grade class with a database connection.
     *
     * @param PDO $pdo Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all grade records ordered by displayOrder.
     * Calls stored procedure: sp_GetAllGrades
     *
     * @return array All grade rows.
     */
    public function getAll(): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAllGrades()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single grade record by ID.
     * Calls stored procedure: sp_GetGradeById
     *
     * @param  int         $gradeId The grade ID to retrieve.
     * @return array|false The grade row or false if not found.
     */
    public function getById(int $gradeId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetGradeById(?)");
        $stmt->execute([$gradeId]);
        return $stmt->fetch();
    }

    /**
     * Retrieves all active grades for use in dropdown menus.
     * Calls stored procedure: sp_GetActiveGrades
     *
     * @return array Active grade rows.
     */
    public function getActive(): array {
        $stmt = $this->pdo->prepare("CALL sp_GetActiveGrades()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Inserts a new grade record.
     * Calls stored procedure: sp_AddGrade
     *
     * @param  string      $gradeName    The name of the grade (e.g. Year 1).
     * @param  string|null $description  Optional description.
     * @param  int         $displayOrder Sort order for display.
     * @return bool        True on success.
     */
    public function create(string $gradeName, ?string $description, int $displayOrder): bool {
        $stmt = $this->pdo->prepare("CALL sp_AddGrade(?, ?, ?)");
        return $stmt->execute([$gradeName, $description, $displayOrder]);
    }

    /**
     * Updates an existing grade record.
     * Calls stored procedure: sp_UpdateGrade
     *
     * @param  int         $gradeId      The ID of the grade to update.
     * @param  string      $gradeName    Updated grade name.
     * @param  string|null $description  Updated description.
     * @param  int         $displayOrder Updated sort order.
     * @return bool        True on success.
     */
    public function update(int $gradeId, string $gradeName, ?string $description, int $displayOrder): bool {
        $stmt = $this->pdo->prepare("CALL sp_UpdateGrade(?, ?, ?, ?)");
        return $stmt->execute([$gradeId, $gradeName, $description, $displayOrder]);
    }

    /**
     * Permanently deletes a grade record.
     * Calls stored procedure: sp_DeleteGrade
     *
     * @param  int  $gradeId The ID of the grade to delete.
     * @return bool True on success.
     */
    public function delete(int $gradeId): bool {
        $stmt = $this->pdo->prepare("CALL sp_DeleteGrade(?)");
        return $stmt->execute([$gradeId]);
    }

    /**
     * Checks whether a grade name is already taken.
     * Calls stored procedure: sp_CheckGradeNameExists
     *
     * @param  string $gradeName  The name to check.
     * @param  int    $excludeId  Grade ID to exclude (0 for add forms).
     * @return bool   True if taken.
     */
    public function nameExists(string $gradeName, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("CALL sp_CheckGradeNameExists(?, ?)");
        $stmt->execute([$gradeName, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Counts active classes assigned to a grade.
     * Calls stored procedure: sp_CountClassesInGrade
     *
     * @param  int $gradeId The grade ID to check.
     * @return int Number of active classes.
     */
    public function countClasses(int $gradeId): int {
        $stmt = $this->pdo->prepare("CALL sp_CountClassesInGrade(?)");
        $stmt->execute([$gradeId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Counts active students assigned to a grade.
     * Calls stored procedure: sp_CountStudentsInGrade
     *
     * @param  int $gradeId The grade ID to check.
     * @return int Number of active students.
     */
    public function countStudents(int $gradeId): int {
        $stmt = $this->pdo->prepare("CALL sp_CountStudentsInGrade(?)");
        $stmt->execute([$gradeId]);
        return (int)$stmt->fetchColumn();
    }
}
