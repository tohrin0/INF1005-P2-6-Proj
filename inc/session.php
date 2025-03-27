<?php
/**
 * Session security management
 * Centralized file to handle all session-related security
 */

// Add to inc/session.php (at the top)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");

// Define site key for security operations
define('SITE_KEY', 'your-secure-random-string-here');

// Start session with secure settings if not already started
function secureSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.entropy_length', 32);
        ini_set('session.sid_length', 48);
        ini_set('session.sid_bits_per_character', 6);
        
        // Set secure flag if HTTPS is detected
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        // Set session timeout to 30 minutes (1800 seconds)
        ini_set('session.gc_maxlifetime', 1800);
        ini_set('session.cookie_lifetime', 1800);
        
        session_start();
        
        // Check if session has expired
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            // Session expired, destroy it
            destroySession();
            session_start();
            $_SESSION['timeout_message'] = "Your session has expired due to inactivity.";
        }
        
        // Force logout after 24 hours regardless of activity
        if (isset($_SESSION['created_at']) && (time() - $_SESSION['created_at'] > 86400)) {
            destroySession();
            session_start();
            $_SESSION['timeout_message'] = "Your session has expired for security reasons.";
        } else if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Store initial client fingerprint for basic validation
        if (!isset($_SESSION['client_fingerprint'])) {
            $_SESSION['client_fingerprint'] = getClientFingerprint();
        }
    }
}

/**
 * Generate a fingerprint for the client
 * Combines IP and user agent with some flexibility for mobile users
 */
function getClientFingerprint() {
    $ip = $_SERVER['REMOTE_ADDR'];
    // Only use the first part of user agent to allow for minor client updates
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'], 0, 120);
    return hash('sha256', $ip . $userAgent . SITE_KEY);
}

/**
 * Regenerate session ID to prevent session fixation
 * Call this function when authentication state changes (login/logout/privilege change)
 */
function regenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Regenerate session ID and keep existing session data
        session_regenerate_id(true);
        // Reset the last activity time
        $_SESSION['last_activity'] = time();
        // Refresh client fingerprint
        $_SESSION['client_fingerprint'] = getClientFingerprint();
        // Set flag of when session was last regenerated
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Generate CSRF token
 */


/**
 * Properly destroy session
 */
function destroySession() {
    // Clear all session variables
    $_SESSION = array();
    
    // If a session cookie is used, unset it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Verify session integrity for authenticated pages
 * Call this function on pages requiring authentication
 */
function verifyAuthenticatedSession() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, redirect to login page
        header('Location: login.php');
        exit;
    }
    
    // Check for session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired, destroy it and redirect to login
        destroySession();
        session_start();
        $_SESSION['login_message'] = "Your session has expired due to inactivity.";
        $_SESSION['login_message_type'] = "error";
        header('Location: login.php');
        exit;
    }
    
    // Validate client fingerprint (with tolerance for mobile networks)
    if (isset($_SESSION['client_fingerprint'])) {
        $currentFingerprint = getClientFingerprint();
        // Use a more sophisticated comparison for production
        if (hash_equals($_SESSION['client_fingerprint'], $currentFingerprint) === false) {
            // Potential session hijacking attempt
            error_log("Potential session hijacking detected: " . $_SESSION['user_id']);
            destroySession();
            session_start();
            $_SESSION['login_message'] = "Your session has been terminated for security reasons.";
            $_SESSION['login_message_type'] = "error";
            header('Location: login.php');
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Periodically regenerate session ID for long sessions (every hour)
    if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 3600)) {
        regenerateSessionId();
    }
}

/**
 * Function to check admin privileges
 */
function verifyAdminSession() {
    // First check authentication
    verifyAuthenticatedSession();
    
    // Then check admin role
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

// Initialize secure session
secureSessionStart();