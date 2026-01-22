<?php
/**
 * Secure Login System
 * Save as: login_check_secure.php
 */

// Enable error reporting for debugging login issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include 'includes/connect.php';
require_once __DIR__ . '/includes/security-helpers.php';

// Set security headers
setSecurityHeaders();

if (!$data) {
    die("Connection error");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    requireCSRFToken();
    
    $username = sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize password
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['loginMessage'] = "Username and password are required";
        logSecurityEvent('login_failed', ['reason' => 'empty_fields', 'username' => $username]);
        header("location:login.php");
        exit;
    }
    
    // Rate limiting - prevent brute force
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit('login_' . $clientIP, 5, 300)) {
        $_SESSION['loginMessage'] = "Too many login attempts. Please try again in 5 minutes.";
        logSecurityEvent('login_rate_limited', ['ip' => $clientIP, 'username' => $username]);
        header("location:login.php");
        exit;
    }
    
    // Fetch user with prepared statement
    $user = db_fetch_one(
        $data,
        "SELECT * FROM user WHERE username = ? LIMIT 1",
        [$username]
    );
    
    if (!$user) {
        $_SESSION['loginMessage'] = "Invalid username or password";
        logSecurityEvent('login_failed', ['reason' => 'user_not_found', 'username' => $username]);
        header("location:login.php");
        exit;
    }
    
    // Check if password is hashed (starts with $2y$ for bcrypt)
    $isPasswordHashed = strpos($user['password'], '$2y$') === 0;
    
    $passwordValid = false;
    
    if ($isPasswordHashed) {
        // Verify hashed password
        $passwordValid = verifyPassword($password, $user['password']);
        
        // Check if password needs rehashing (security improvement)
        if ($passwordValid && needsRehash($user['password'])) {
            $newHash = hashPassword($password);
            db_execute($data, "UPDATE user SET password = ? WHERE id = ?", [$newHash, $user['id']]);
        }
    } else {
        // Legacy plain text comparison (for backward compatibility)
        // MIGRATE ALL PASSWORDS TO HASHED VERSIONS
        $passwordValid = ($password === $user['password']);
        
        // Auto-upgrade to hashed password
        if ($passwordValid) {
            $newHash = hashPassword($password);
            db_execute($data, "UPDATE user SET password = ? WHERE id = ?", [$newHash, $user['id']]);
        }
    }
    
    if (!$passwordValid) {
        $_SESSION['loginMessage'] = "Invalid username or password";
        logSecurityEvent('login_failed', ['reason' => 'invalid_password', 'username' => $username]);
        header("location:login.php");
        exit;
    }
    
    // Check user type
    if (empty($user['usertype']) || !in_array($user['usertype'], ['admin', 'lecturer', 'student'], true)) {
        $_SESSION['loginMessage'] = "Invalid user type";
        logSecurityEvent('login_failed', ['reason' => 'invalid_usertype', 'username' => $username]);
        header("location:login.php");
        exit;
    }
    
    // Successful login
    regenerateSession(); // Prevent session fixation
    
    $_SESSION['username'] = $user['username'];
    $_SESSION['usertype'] = $user['usertype'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['login_time'] = time();
    
    logSecurityEvent('login_success', ['username' => $username, 'usertype' => $user['usertype']]);
    
    // Redirect based on user type
    switch ($user['usertype']) {
        case 'student':
            header("location:studenthome.php");
            break;
        case 'admin':
            header("location:adminhome.php");
            break;
        case 'lecturer':
            header("location:lecturerhome.php");
            break;
        default:
            $_SESSION['loginMessage'] = "Unknown user role";
            header("location:login.php");
            break;
    }
    exit;
}

// If not POST, redirect to login
header("location:login.php");
exit;