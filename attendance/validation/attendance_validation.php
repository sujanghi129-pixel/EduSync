<?php

/**
 * attendance/validation/attendance_validation.php
 *
 * Server-side validation helpers for the Attendance module.
 * All functions return an array of human-readable error strings.
 * An empty array means validation passed.
 *
 * Usage:
 *   require_once __DIR__ . '/validation/attendance_validation.php';
 *   $errors = validateAttendanceSubmission($classId, $date, $statusMap);
 *   if ($errors) { ... }
 *
 * @package EduSync
 * @author  Laxman Giri
 */

/**
 * Validates the full attendance submission (mark / save).
 *
 * @param  int    $classId   Class being marked.
 * @param  string $date      Date string — must be Y-m-d.
 * @param  array  $statusMap studentId => status pairs.
 * @return string[]          Array of error messages (empty = valid).
 */
function validateAttendanceSubmission(int $classId, string $date, array $statusMap): array
{
    $errors = [];

    if (!$classId) {
        $errors[] = 'A class must be selected.';
    }

    if (!$date) {
        $errors[] = 'A date must be provided.';
    } elseif (!validateDateFormat($date)) {
        $errors[] = 'Invalid date format. Expected YYYY-MM-DD.';
    } elseif ($date > date('Y-m-d')) {
        $errors[] = 'Attendance cannot be marked for a future date.';
    }

    if (empty($statusMap)) {
        $errors[] = 'No student statuses were submitted.';
    }

    return $errors;
}

/**
 * Validates a single attendance record update (edit).
 *
 * @param  string $status The new status value.
 * @param  string $notes  Optional remarks.
 * @return string[]       Array of error messages (empty = valid).
 */
function validateAttendanceUpdate(string $status, string $notes): array
{
    $errors = [];

    $validStatuses = ['present', 'absent', 'late'];
    if (!in_array($status, $validStatuses, true)) {
        $errors[] = 'Please select a valid status (present, late or absent).';
    }

    if (strlen($notes) > 500) {
        $errors[] = 'Remarks must not exceed 500 characters.';
    }

    return $errors;
}

/**
 * Validates a date string matches Y-m-d format and is a real calendar date.
 *
 * @param  string $date Date string to validate.
 * @return bool         True if the date is valid.
 */
function validateDateFormat(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    [$y, $m, $d] = explode('-', $date);
    return checkdate((int)$m, (int)$d, (int)$y);
}

/**
 * Validates a date range (from must be <= to).
 *
 * @param  string $from Start date (Y-m-d).
 * @param  string $to   End date (Y-m-d).
 * @return string[]     Array of error messages (empty = valid).
 */
function validateDateRange(string $from, string $to): array
{
    $errors = [];

    if ($from && !validateDateFormat($from)) {
        $errors[] = 'Invalid "from" date format.';
    }
    if ($to && !validateDateFormat($to)) {
        $errors[] = 'Invalid "to" date format.';
    }
    if ($from && $to && $from > $to) {
        $errors[] = '"From" date cannot be after the "to" date.';
    }

    return $errors;
}
