<?php

/**
 * shared/meta.php
 *
 * Outputs a <meta> tag containing the logged-in user's session data as JSON.
 * This allows auth.js to read the user's name and role for the nav and sidebar
 * without making an extra HTTP request.
 *
 * Usage: Include in the <head> of every authenticated page after session_start().
 *
 * Example:
 *   <?php require_once __DIR__ . '/../shared/meta.php'; ?>
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

/**
 * Encode the session user as a safely escaped JSON string.
 * Falls back to an empty object if no user is in session.
 *
 * @var string $_metaUser HTML-escaped JSON string of the current user.
 */
$_metaUser = isset($_SESSION['user'])
    ? htmlspecialchars(json_encode($_SESSION['user']), ENT_QUOTES)
    : '{}';

echo "<meta name=\"edu-user\" content=\"$_metaUser\">\n";
