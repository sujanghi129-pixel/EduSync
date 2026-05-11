<?php

/**
 * students/get_classes.php
 *
 * AJAX endpoint that returns a JSON array of active classes
 * belonging to a given grade. Used by the dynamic class dropdown
 * on the add and edit student forms.
 *
 * @package EduSync
 * @author  Susma Thapa
 *
 * @param  int   $_GET['gradeId']  The ID of the grade to filter classes by.
 * @return string JSON array of objects: [{ classId, className }, ...]
 */
session_start();
if (empty($_SESSION['user'])) { http_response_code(401); exit; }

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/Student.php';

/** @var Student $studentClass - Middle layer instance */
$studentClass = new Student(db());
$pdo = db(); // Still needed for grade/class dropdowns

$gradeId = (int)($_GET['gradeId'] ?? 0);
$classes = [];

if ($gradeId) {
    $stmt = $pdo->prepare("
        SELECT classId, className
        FROM tblClass
        WHERE gradeId = ? AND isStudentActive = 1
        ORDER BY className ASC
    ");
    $stmt->execute([$gradeId]);
    $classes = $stmt->fetchAll();
}

header('Content-Type: application/json');
echo json_encode($classes);
