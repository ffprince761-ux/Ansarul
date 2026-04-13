<?php
/**
 * Owner Panel - Logout
 */
require_once 'config/db.php';
require_once 'config/functions.php';

// Log activity before logout
if (isOwnerLoggedIn()) {
    logOwnerActivity($pdo, $_SESSION['owner_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: index.php');
exit();
?>
