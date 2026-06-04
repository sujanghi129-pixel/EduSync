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

// Cast to int immediately — raw POST data is always a string
$classId    = (int)($_POST['classId'] ?? 0);
$date       = $_POST['date']          ?? '';
// status and notes are keyed by studentId, e.g. ['42' => 'present']
$statusMap  = $_POST['status']        ?? [];
$notesMap   = $_POST['notes']         ?? [];
// Pull the marker's staffId from the session — never trust a POST field for this
$markedById = (int)$_SESSION['user']['staffId'];

// Validate required fields; function returns an array of human-readable errors
$errors = validateAttendanceSubmission($classId, $date, $statusMap);
if ($errors) {
    // Surface the first error as a toast and bounce back to the form
    $_SESSION['toast_error'] = implode(' ', $errors);
    header('Location: index.php');
    exit;
}

// Delete-then-insert acts as an upsert:
// attendance is always submitted for the full class, so replacing the
// entire set is simpler and safer than diffing individual rows.
$attClass->deleteByClassDate($classId, $date);

// Whitelist prevents arbitrary status values being stored in the database
$validStatuses = ['present', 'absent', 'late'];
$count = 0;
foreach ($statusMap as $studentId => $status) {
    // Skip anything not in the whitelist (e.g. tampered form values)
    if (!in_array($status, $validStatuses)) continue;

    $notes = trim($notesMap[$studentId] ?? '');

    // Present students don't have a reason — clear any stale value
    if ($status === 'present') $notes = '';

    $attClass->add((int)$studentId, $classId, $markedById, $date, $status, $notes);
    $count++;
}

// Use date() for human-readable format in the toast message, not raw Y-m-d
$_SESSION['toast'] = "Attendance saved for $count student(s) on " . date('d M Y', strtotime($date)) . ".";

// Return to the same class/date so the teacher can verify the saved values
header("Location: index.php?classId=$classId&date=$date");
exit;
