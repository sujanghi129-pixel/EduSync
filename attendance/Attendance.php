<?php

/**
 * attendance/Attendance.php
 *
 * Middle layer class for the Attendance component.
 * Encapsulates all database operations for tblAttendance.
 * All operations call stored procedures in the data layer.
 *
 * @package EduSync
 * @author  Laxman Giri
 */

require_once __DIR__ . '/../shared/db.php';

class Attendance {

    /** @var PDO $pdo Shared database connection */
    private PDO $pdo;

    /**
     * Initialises the Attendance class with a database connection.
     *
     * @param PDO $pdo Active PDO connection from db().
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves attendance records with optional filters.
     * Calls stored procedure: sp_GetAttendanceReport
     *
     * @param  int    $classId   Filter by class ID (0 for all classes).
     * @param  string $dateFrom  Start date filter (Y-m-d).
     * @param  string $dateTo    End date filter (Y-m-d).
     * @return array  Attendance rows with student, class, grade and staff names.
     */
    public function getReport(int $classId, string $dateFrom, string $dateTo): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAttendanceReport(?, ?, ?)");
        $stmt->execute([$classId ?: null, $dateFrom, $dateTo]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves existing attendance records for a class on a specific date.
     * Used to pre-fill the mark attendance form if already marked.
     * Calls stored procedure: sp_GetAttendanceByClassDate
     *
     * @param  int    $classId The class ID.
     * @param  string $date    The date in Y-m-d format.
     * @return array  Attendance rows keyed by studentId.
     */
    public function getByClassDate(int $classId, string $date): array {
        $stmt = $this->pdo->prepare("CALL sp_GetAttendanceByClassDate(?, ?)");
        $stmt->execute([$classId, $date]);
        $rows   = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['studentId']] = [
                'status' => $row['status'],
                'notes'  => $row['notes']
            ];
        }
        return $result;
    }

    /**
     * Retrieves a single attendance record by ID.
     * Calls stored procedure: sp_GetAttendanceById
     *
     * @param  int         $attendanceId The attendance record ID.
     * @return array|false The record row or false if not found.
     */
    public function getById(int $attendanceId): array|false {
        $stmt = $this->pdo->prepare("CALL sp_GetAttendanceById(?)");
        $stmt->execute([$attendanceId]);
        return $stmt->fetch();
    }

    /**
     * Deletes all attendance records for a class on a given date.
     * Called before saving new records to implement an upsert pattern.
     * Calls stored procedure: sp_DeleteAttendanceByClassDate
     *
     * @param  int    $classId The class ID.
     * @param  string $date    The date in Y-m-d format.
     * @return bool   True on success.
     */
    public function deleteByClassDate(int $classId, string $date): bool {
        $stmt = $this->pdo->prepare("CALL sp_DeleteAttendanceByClassDate(?, ?)");
        return $stmt->execute([$classId, $date]);
    }

    /**
     * Inserts a single attendance record.
     * Calls stored procedure: sp_AddAttendance
     *
     * @param  int    $studentId  The student ID.
     * @param  int    $classId    The class ID.
     * @param  int    $markedById The staff ID of the person marking attendance.
     * @param  string $date       The attendance date in Y-m-d format.
     * @param  string $status     The attendance status: present, absent or late.
     * @param  string $notes      Optional remarks (empty string for none).
     * @return bool   True on success.
     */
    public function add(int $studentId, int $classId, int $markedById, string $date, string $status, string $notes): bool {
        $stmt = $this->pdo->prepare("CALL sp_AddAttendance(?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$studentId, $classId, $markedById, $date, $status, $notes ?: null]);
    }

    /**
     * Updates the status and notes of a single attendance record.
     * Calls stored procedure: sp_UpdateAttendance
     *
     * @param  int    $attendanceId The ID of the record to update.
     * @param  string $status       Updated status: present, absent or late.
     * @param  string $notes        Updated remarks (empty string to clear).
     * @return bool   True on success.
     */
    public function update(int $attendanceId, string $status, string $notes): bool {
        $stmt = $this->pdo->prepare("CALL sp_UpdateAttendance(?, ?, ?)");
        return $stmt->execute([$attendanceId, $status, $notes ?: null]);
    }

    /**
     * Retrieves today's attendance summary counts for a specific class.
     * Calls stored procedure: sp_GetTodayAttendanceSummary
     *
     * @param  int    $classId The class ID to summarise.
     * @param  string $date    The date to summarise (Y-m-d).
     * @return array  Associative array with keys: present, absent, late, total.
     */
    public function getTodaySummary(int $classId, string $date): array {
        $stmt = $this->pdo->prepare("CALL sp_GetTodayAttendanceSummary(?, ?)");
        $stmt->execute([$classId, $date]);
        $rows    = $stmt->fetchAll();
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0];
        foreach ($rows as $row) {
            $summary[$row['status']] = (int)$row['cnt'];
        }
        $summary['total'] = $summary['present'] + $summary['absent'] + $summary['late'];
        return $summary;
    }
}
