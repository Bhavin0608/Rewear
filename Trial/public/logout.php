<?php
// Always start the session to access it
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session data on the server
session_destroy();

// To show a "You have been logged out" message, we can 
// start a new session and set a flash variable.
session_start();
$_SESSION['flash'] = "You have been logged out successfully.";
$_SESSION['flash_type'] = "success";

// Redirect to the login page
header("Location: login.php");
exit();
?>