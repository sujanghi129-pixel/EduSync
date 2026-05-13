<?php

/**
 * classes/ClassModel.php
 *
 * Middle layer class for the Class Management component.
 * Encapsulates all database operations for tblClass.
 * note: Named ClassModel to avoid conflict with PHP reserved word 'class'.
 * All operations call stored procedures in the data layer.
 *
 * @package EduSync
 * @author  Saimon
 */

require_once __DIR__ . '/../shared/db.php';

class ClassModel {

    /** @var PDO $pdo Shared database connection */
    private PDO $pdo;

    /**
     * Initialises the ClassModel with a database connection.
     *
     * @param PDO $pdo Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all class records with grade and teacher names.
     * Calls stored procedure: sp_GetAllClasses
     *
     * @return array All class rows with joined grade and staff data.
     */
    public function getAll(): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAllClasses()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single class record by ID.
     * Calls stored procedure: sp_GetClassById
     *
     * @param  int         $classId The class ID to retrieve.
     * @return array|false The class row or false if not found.
     */
    public function getById(int $classId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetClassById(?)");
        $stmt->execute([$classId]);
        return $stmt->fetch();
    }

    /**
     * Retrieves all active classes for a given grade.
     * Used to populate the class dropdown on student forms.
     * Calls stored procedure: sp_GetClassesByGrade
     *
     * @param  int   $gradeId The grade ID to filter by.
     * @return array Active class rows for that grade.
     */
    public function getByGrade(int $gradeId): array {
        $stmt = $this->pdo->prepare("CALL sp_GetClassesByGrade(?)");
        $stmt->execute([$gradeId]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves the class assigned to a specific staff member as teacher.
     * Calls stored procedure: sp_GetClassByTeacher
     *
     * @param  int         $staffId The staff ID of the class teacher.
     * @return array|false The class row or false if not assigned.
     */
    public function getByTeacher(int $staffId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetClassByTeacher(?)");
        $stmt->execute([$staffId]);
        return $stmt->fetch();
    }

    /**
     * Inserts a new class record.
     * Calls stored procedure: sp_AddClass
     *
     * @param  string $className      The class name (e.g. 1A).
     * @param  int    $gradeId        The grade the class belongs to.
     * @param  int    $classTeacherID The staff ID of the class teacher.
     * @return bool   True on success.
     */
    public function create(string $className, int $gradeId, int $classTeacherID): bool {
        $stmt = $this->pdo->prepare("CALL sp_AddClass(?, ?, ?)");
        return $stmt->execute([$className, $gradeId, $classTeacherID]);
    }

    /**
     * Updates an existing class record.
     * Calls stored procedure: sp_UpdateClass
     *
     * @param  int    $classId        The ID of the class to update.
     * @param  string $className      Updated class name.
     * @param  int    $gradeId        Updated grade ID.
     * @param  int    $classTeacherID Updated teacher staff ID.
     * @return bool   True on success.
     */
    public function update(int $classId, string $className, int $gradeId, int $classTeacherID): bool {
        $stmt = $this->pdo->prepare("CALL sp_UpdateClass(?, ?, ?, ?)");
        return $stmt->execute([$classId, $className, $gradeId, $classTeacherID]);
    }

    /**
     * Permanently deletes a class record.
     * Calls stored procedure: sp_DeleteClass
     *
     * @param  int  $classId The ID of the class to delete.
     * @return bool True on success.
     */
    public function delete(int $classId): bool {
        $stmt = $this->pdo->prepare("CALL sp_DeleteClass(?)");
        return $stmt->execute([$classId]);
    }

    /**
     * Toggles a class's active status between TRUE and FALSE.
     * Calls stored procedure: sp_ToggleClassStatus
     *
     * @param  int  $classId The ID of the class to toggle.
     * @return bool True on success.
     */
    public function toggleStatus(int $classId): bool {
        $stmt = $this->pdo->prepare("CALL sp_ToggleClassStatus(?)");
        return $stmt->execute([$classId]);
    }

    /**
     * Checks whether a class name already exists within a grade.
     * Calls stored procedure: sp_CheckClassNameExists
     *
     * @param  string $className The name to check.
     * @param  int    $gradeId   The grade to check within.
     * @param  int    $excludeId Class ID to exclude (0 for add forms).
     * @return bool   True if taken.
     */
    public function nameExists(string $className, int $gradeId, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("CALL sp_CheckClassNameExists(?, ?, ?)");
        $stmt->execute([$className, $gradeId, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Counts active students enrolled in a class.
     * Used before allowing deletion or deactivation.
     * Calls stored procedure: sp_CountStudentsInClass
     *
     * @param  int $classId The class ID to check.
     * @return int Number of active students.
     */
    public function countStudents(int $classId): int {
        $stmt = $this->pdo->prepare("CALL sp_CountStudentsInClass(?)");
        $stmt->execute([$classId]);
        return (int)$stmt->fetchColumn();
    }
}
