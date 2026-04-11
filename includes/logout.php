<?php
require_once __DIR__ . '/sessions.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to homepage
header("Location: ../index.php");
exit();
?>