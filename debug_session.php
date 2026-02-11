<?php
// debug_session.php
session_start();
echo "<pre>";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "Cookies:\n";
print_r($_COOKIE);
echo "</pre>";
?>
