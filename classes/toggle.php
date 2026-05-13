<?php

/**
 * classes/toggle.php
 *
 * Handles POST requests to activate or deactivate a class.
 * Blocks deactivation if the class still has active students enrolled.
 * Redirects back to the classes list with a toast message.
 *
 * @package EduSync
 * @author  Saimon Shrestha
 */
session_start();
require_once __DIR__ . '/../shared/auth.php';
requireRole(['Administrator']);

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/ClassModel.php';

/** @var ClassModel $classModel - Middle layer instance */
$classModel = new ClassModel(db());
$pdo = db(); // Still needed for grades/staff dropdowns

$id   = (int)($_POST['classId'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tblClass WHERE classId = ?");
$stmt->execute([$id]);
$class = $stmt->fetch();

if (!$class) { header('Location: index.php'); exit; }

// Prevent deactivating if it has active students
if ($class['isClassActive']) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblStudent WHERE classId = ? AND isStudentActive = TRUE");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['toast_error'] = "Cannot deactivate \"{$class['className']}\": it still has active students enrolled.";
        header('Location: index.php');
        exit;
    }
}

$classModel->toggleStatus($id);
$newState = $class['isClassActive'] ? 'deactivated' : 'activated';
$_SESSION['toast'] = "Class \"{$class['className']}\" {$newState}.";
header('Location: index.php');
exit;
