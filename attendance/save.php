<?php

/**
 * attendance/save.php
 *
 * Handles POST submission from the Mark Attendance form.
 * Deletes any existing attendance records for the selected class and date,
 * then inserts the new records — effectively an upsert.
 * Validates the date format and status values before saving.
 *
 * @package EduSync
 * @author  Laxman Giri
 *
 * @param  int    $_POST['classId']         The ID of the class being marked.
 * @param  string $_POST['date']            The attendance date (Y-m-d format).
 * @param  array  $_POST['status']          Map of studentId => status.
 * @param  array  $_POST['notes']           Map of studentId => remarks text.
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator', 'Teacher', 'Headteacher']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../methods/Attendance.php';

/** @var Attendance $attClass - Middle layer instance */
$attClass = new Attendance(db());
$pdo = db(); // Still needed for class/student lookups

$classId    = (int)($_POST['classId'] ?? 0);
$date       = $_POST['date'] ?? '';
$statusMap  = $_POST['status'] ?? [];
$notesMap   = $_POST['notes']  ?? [];
$markedById = (int)$_SESSION['user']['staffId'];

if (!$classId || !$date || empty($statusMap)) {
    $_SESSION['toast_error'] = 'Invalid submission. Please try again.';
    header('Location: index.php');
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $_SESSION['toast_error'] = 'Invalid date format.';
    header('Location: index.php');
    exit;
}

// Delete existing records via middle layer (overwrite approach)
$attClass->deleteByClassDate($classId, $date);

// Insert new records
$validStatuses = ['present', 'absent', 'late'];
$count = 0;
foreach ($statusMap as $studentId => $status) {
    if (!in_array($status, $validStatuses)) continue;
    $notes = trim($notesMap[$studentId] ?? '');
    if ($status === 'present') $notes = '';
    // Insert via middle layer
    $attClass->add((int)$studentId, $classId, $markedById, $date, $status, $notes);
    $count++;
}

$_SESSION['toast'] = "Attendance saved for $count student(s) on " . date('d M Y', strtotime($date)) . ".";
header("Location: index.php?classId=$classId&date=$date");
exit;
