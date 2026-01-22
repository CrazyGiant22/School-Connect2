<?php
/**
 * Session Helper Functions
 * Provides safe ways to retrieve session data
 */

/**
 * Get current user's ID from session
 * Works with admin, lecturer, and student users
 */
function getCurrentUserId($data = null) {
    // First check if user_id is already in session
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    // If not, try to get it from username
    $username = $_SESSION['username'] ?? null;
    if ($username && $data instanceof mysqli) {
        $row = db_fetch_one($data, "SELECT id FROM user WHERE username = ?", [$username]);
        if ($row && isset($row['id'])) {
            $user_id = $row['id'];
            $_SESSION['user_id'] = $user_id;
            return $user_id;
        }
    }
    return null;
}

/**
 * Get student ID from session
 * Handles both stored student_id and retrieval from username
 */
function getStudentId($data = null) {
    // First check if student_id is directly in session
    if (isset($_SESSION['student_id'])) {
        return $_SESSION['student_id'];
    }
    
    // Try to get it using current user ID
    $user_id = getCurrentUserId($data);
    if ($user_id) {
        return $user_id;
    }
    
    return null;
}

/**
 * Get lecturer ID from session
 * Handles both stored lecturer_id and retrieval from username
 */
function getLecturerId($data = null) {
    // First check if lecturer_id is directly in session
    if (isset($_SESSION['lecturer_id'])) {
        return $_SESSION['lecturer_id'];
    }
    
    // Try to get it using current user ID
    $user_id = getCurrentUserId($data);
    if ($user_id) {
        return $user_id;
    }
    
    return null;
}

/**
 * Get current user's full information
 */
function getCurrentUser($data = null) {
    $username = $_SESSION['username'] ?? null;
    $usertype = $_SESSION['usertype'] ?? null;
    
    if (!$username || !$usertype || !$data instanceof mysqli) {
        return null;
    }
    
    return db_fetch_one($data, "SELECT * FROM user WHERE username = ? AND usertype = ?", [$username, $usertype]);
}

/**
 * Display user avatar initials
 */
function getUserInitials() {
    $username = $_SESSION['username'] ?? 'U';
    return strtoupper(substr($username, 0, 1));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['username']) && isset($_SESSION['usertype']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return $_SESSION['usertype'] ?? null === 'admin';
}

/**
 * Check if user is lecturer
 */
function isLecturer() {
    return $_SESSION['usertype'] ?? null === 'lecturer';
}

/**
 * Check if user is student
 */
function isStudent() {
    return $_SESSION['usertype'] ?? null === 'student';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("location:login.php");
        exit;
    }
}

/**
 * Require specific role - redirect if not authorized
 */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['usertype'] !== $role) {
        header("location:login.php");
        exit;
    }
}

?>
