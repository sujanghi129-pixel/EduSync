<?php
/**
 * attendance/report.php — DEPRECATED
 *
 * This file is kept for backward compatibility only.
 * The report is now served by list.php.
 * Any direct links or bookmarks to report.php are forwarded.
 *
 * @package EduSync
 * @deprecated Use list.php instead.
 */
header('Location: list.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
