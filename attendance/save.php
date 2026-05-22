<?php
/**
 * attendance/save.php — DEPRECATED
 *
 * This file is kept for backward compatibility only.
 * All POST submissions now go to add.php.
 * Any direct links or bookmarks to save.php are forwarded.
 *
 * @package EduSync
 * @deprecated Use add.php instead.
 */
// Forward all POST data to add.php
header('Location: add.php');
exit;
