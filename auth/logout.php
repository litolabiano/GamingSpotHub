<?php
/**
 * Good Spot Gaming Hub - Logout
 */
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
?>
