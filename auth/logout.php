<?php
require_once '../includes/auth.php'; // This will start the session

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>