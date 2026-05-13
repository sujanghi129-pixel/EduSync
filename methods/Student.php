<?php

/**
 * students/Student.php
 *
 * Middle layer class for the Student Management component.
 * Encapsulates all database operations for tblStudent.
 * All operations call stored procedures in the data layer.
 *
 * @package EduSync
 * @author  Susma pandey
 */

require_once __DIR__ . '/../shared/db.php';

class Student {

    /** @var PDO $pdo Shared database connection */
    private PDO $pdo;

    /**
     * Initialises the Student class with a database connection.
     *
     * @param PDO $pdo Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all student records with grade and class names.
     * Calls stored procedure: sp_GetAllStudents
     *
     * @return array All student rows with joined data.
     */
    public function getAll(): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAllStudents()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single student record by ID.
     * Calls stored procedure: sp_GetStudentById
     *
     * @param  int         $studentId The student ID to retrieve.
     * @return array|false The student row or false if not found.
     */
    public function getById(int $studentId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetStudentById(?)");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }

    /**
     * Retrieves all active students in a specific class.
     * Used by the attendance and my_class pages.
     * Calls stored procedure: sp_GetStudentsByClass
     *
     * @param  int   $classId The class ID to filter by.
     * @return array Active student rows for that class.
     */
    public function getByClass(int $classId): array {
        $stmt = $this->pdo->prepare("CALL sp_GetStudentsByClass(?)");
        $stmt->execute([$classId]);
        return $stmt->fetchAll();
    }

    /**
     * Inserts a new student record.
     * Calls stored procedure: sp_AddStudent
     *
     * @param  string $fullName    The student's full name.
     * @param  string $dateOfBirth Date of birth in Y-m-d format.
     * @param  int    $gradeId     The grade the student belongs to.
     * @param  int    $classId     The class the student is enrolled in.
     * @return bool   True on success.
     */
    public function create(string $fullName, string $dateOfBirth, int $gradeId, int $classId): bool {
        $stmt = $this->pdo->prepare("CALL sp_AddStudent(?, ?, ?, ?)");
        return $stmt->execute([$fullName, $dateOfBirth, $gradeId, $classId]);
    }

    /**
     * Updates an existing student record.
     * Calls stored procedure: sp_UpdateStudent
     *
     * @param  int    $studentId   The ID of the student to update.
     * @param  string $fullName    Updated full name.
     * @param  string $dateOfBirth Updated date of birth.
     * @param  int    $gradeId     Updated grade ID.
     * @param  int    $classId     Updated class ID.
     * @return bool   True on success.
     */
    public function update(int $studentId, string $fullName, string $dateOfBirth, int $gradeId, int $classId): bool {
        $stmt = $this->pdo->prepare("CALL sp_UpdateStudent(?, ?, ?, ?, ?)");
        return $stmt->execute([$studentId, $fullName, $dateOfBirth, $gradeId, $classId]);
    }

    /**
     * Permanently deletes a student record and their attendance records.
     * Calls stored procedure: sp_DeleteStudent
     *
     * @param  int  $studentId The ID of the student to delete.
     * @return bool True on success.
     */
    public function delete(int $studentId): bool {
        $stmt = $this->pdo->prepare("CALL sp_DeleteStudent(?)");
        return $stmt->execute([$studentId]);
    }

    /**
     * Toggles a student's active status between TRUE and FALSE.
     * Calls stored procedure: sp_ToggleStudentStatus
     *
     * @param  int  $studentId The ID of the student to toggle.
     * @return bool True on success.
     */
    public function toggleStatus(int $studentId): bool {
        $stmt = $this->pdo->prepare("CALL sp_ToggleStudentStatus(?)");
        return $stmt->execute([$studentId]);
    }

    /**
     * Counts attendance records belonging to a student.
     * Used before deletion to warn the user.
     * Calls stored procedure: sp_CountStudentAttendance
     *
     * @param  int $studentId The student ID to check.
     * @return int Number of attendance records.
     */
    public function countAttendance(int $studentId): int {
        $stmt = $this->pdo->prepare("CALL sp_CountStudentAttendance(?)");
        $stmt->execute([$studentId]);
        return (int)$stmt->fetchColumn();
    }
}
