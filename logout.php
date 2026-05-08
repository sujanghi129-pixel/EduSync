<?php

/**
 * logout.php
 *
 * Handles user logout for the EduSync system.
 *
 * Destroys the current session and redirects the user back to the
 * login page. This file should be linked from the nav Sign Out button.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

// Start the session so it can be destroyed
session_start();

// Remove all session data
session_destroy();

// Redirect to login page
header('Location: index.php');
exit;
