<?php
/**
 * Enhanced Security Helper Functions
 * Save as: includes/security-helpers.php
 */

// ============================================
// PASSWORD SECURITY
// ============================================

/**
 * Hash password securely using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing (e.g., cost increased)
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ============================================
// CSRF PROTECTION
// ============================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token input field
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Require valid CSRF token or die
 */
function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh and try again.');
    }
}

// ============================================
// INPUT SANITIZATION
// ============================================

/**
 * Sanitize string input
 */
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize integer
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize phone number (remove non-numeric except +)
 */
function sanitizePhone($phone) {
    return preg_replace('/[^0-9+]/', '', trim($phone));
}

// ============================================
// XSS PROTECTION
// ============================================

/**
 * Escape output for HTML context
 */
function escapeHTML($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for JavaScript context
 */
function escapeJS($string) {
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Escape output for URL context
 */
function escapeURL($string) {
    return urlencode($string);
}

// ============================================
// SESSION SECURITY
// ============================================

/**
 * Regenerate session ID (call after login)
 */
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Destroy session completely
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

/**
 * Set secure session configuration
 *
 * Note: On PHP 7+ you cannot change session ini settings
 * after a session is already active, so we only call ini_set
 * before the first session_start().
 */
function configureSecureSession() {
    // Only configure and start the session if it is not already active
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.cookie_samesite', 'Strict');

        session_start();
    }
}

// ============================================
// FILE UPLOAD SECURITY
// ============================================

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload';
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file was uploaded';
            return ['valid' => false, 'errors' => $errors];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File size exceeds limit';
            return ['valid' => false, 'errors' => $errors];
        default:
            $errors[] = 'Unknown upload error';
            return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds ' . ($maxSize / 1024 / 1024) . 'MB limit';
    }
    
    // Check file type
    if (!empty($allowedTypes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            $errors[] = 'Invalid file type';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Generate safe filename
 */
function generateSafeFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(16));
    return $safeName . '.' . $extension;
}

// ============================================
// RATE LIMITING (Simple)
// ============================================

/**
 * Check rate limit (simple file-based)
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    
    $attempts = [];
    if (file_exists($file)) {
        $data = file_get_contents($file);
        $attempts = json_decode($data, true) ?: [];
    }
    
    // Remove old attempts outside time window
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    // Add current attempt
    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts));
    
    return true;
}

// ============================================
// SQL INJECTION PREVENTION
// ============================================

/**
 * Validate table/column name (prevent SQL injection in dynamic queries)
 */
function isValidIdentifier($identifier) {
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier);
}

/**
 * Safe ORDER BY clause builder
 */
function buildOrderBy($column, $direction = 'ASC', $allowedColumns = []) {
    if (!in_array($column, $allowedColumns, true)) {
        return '';
    }
    
    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
    
    if (!isValidIdentifier($column)) {
        return '';
    }
    
    return " ORDER BY $column $direction";
}

// ============================================
// SECURITY HEADERS
// ============================================

/**
 * Set security headers
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data:;");
}

// ============================================
// AUDIT LOGGING
// ============================================

/**
 * Log security event
 */
function logSecurityEvent($event, $details = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user' => $_SESSION['username'] ?? 'guest',
        'details' => $details
    ];
    
    file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
}

// ============================================
// AUTO-INITIALIZE
// ============================================

// Configure secure session on load
if (!headers_sent()) {
    configureSecureSession();
}