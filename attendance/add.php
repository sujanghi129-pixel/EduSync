<?php

/**
 * attendance/add.php
 *
 * Handles POST submission from the Mark Attendance form (index.php).
 * Validates input, deletes any existing records for the class/date
 * (upsert pattern), then inserts new rows via the middle layer.
 *
 * Redirects back to index.php with a toast on success or error.
 *
 * @package EduSync
 * @author  Laxman Giri
 *
 * @param  int    $_POST['classId']   The ID of the class being marked.
 * @param  string $_POST['date']      The attendance date (Y-m-d format).
 * @param  array  $_POST['status']    Map of studentId => status.
 * @param  array  $_POST['notes']     Map of studentId => remarks text.
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator', 'Teacher', 'Headteacher']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Attendance.php';
require_once __DIR__ . '/validation/attendance_validation.php';

/** @var Attendance $attClass - Middle layer instance */
$attClass = new Attendance(db());

$classId    = (int)($_POST['classId'] ?? 0);
$date       = $_POST['date']          ?? '';
$statusMap  = $_POST['status']        ?? [];
$notesMap   = $_POST['notes']         ?? [];
$markedById = (int)$_SESSION['user']['staffId'];

// Validate required fields
$errors = validateAttendanceSubmission($classId, $date, $statusMap);
if ($errors) {
    $_SESSION['toast_error'] = implode(' ', $errors);
    header('Location: index.php');
    exit;
}

// Delete existing records for this class/date (overwrite/upsert)
$attClass->deleteByClassDate($classId, $date);

// Insert new records
$validStatuses = ['present', 'absent', 'late'];
$count = 0;
foreach ($statusMap as $studentId => $status) {
    if (!in_array($status, $validStatuses)) continue;
    $notes = trim($notesMap[$studentId] ?? '');
    if ($status === 'present') $notes = '';
    $attClass->add((int)$studentId, $classId, $markedById, $date, $status, $notes);
    $count++;
}

$_SESSION['toast'] = "Attendance saved for $count student(s) on " . date('d M Y', strtotime($date)) . ".";
header("Location: index.php?classId=$classId&date=$date");
exit;
