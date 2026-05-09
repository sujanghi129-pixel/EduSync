<?php

/**
 * shared/db.php
 *
 * Provides a singleton PDO database connection for the EduSync system.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

/**
 * Returns a singleton PDO connection to the EduSync database.
 *
 * @return PDO The active PDO database connection instance.
 * @throws PDOException If the connection fails.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $host   = 'localhost';
    $dbname = 'edusync';
    $user   = 'root';
    $pass   = '';

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}