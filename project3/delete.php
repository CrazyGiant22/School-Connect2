<?php

session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';

// Only admins may delete users, and only via POST with CSRF protection
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Validate CSRF token for this delete request
    requireCSRFToken();

    $user_id = (int)$_POST['id'];
    if ($user_id > 0) {
        $ok = db_execute($data, "DELETE FROM user WHERE id = ?", array($user_id));
        if ($ok) {
            $_SESSION['message'] = "User deleted successfully";
        } else {
            $_SESSION['message'] = "Failed to delete user";
        }
    }

    // For now, always redirect back to the students list
    header("Location: view_student.php");
    exit();
}

// Fallback: direct access without POST just goes back safely
header("Location: view_student.php");
exit();
?>
