<?php
// Don't start session here since it's included in files that already start sessions
// session_start();

function registerUser($username, $password, $email) {
    // Include database connection
    global $pdo;
    
    try {
        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->rowCount() > 0) {
            error_log("Registration failed: Email already exists: $email");
            return false;
        }
        
        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->rowCount() > 0) {
            error_log("Registration failed: Username already exists: $username");
            return false;
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $success = $stmt->execute([$username, $hashedPassword, $email]);
        
        if ($success) {
            error_log("User registered successfully: $username ($email)");
        } else {
            error_log("Registration failed: Database error for $username ($email)");
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

function loginUser($email, $password) {
    // Include database connection
    global $pdo;
    
    try {
        // Add debugging
        error_log("loginUser function called with email: $email");
        
        // Get IP address and user agent for logging
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Changed to search by email instead of username
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("User query result: " . ($user ? "Found user with ID: {$user['id']}" : "No user found"));
        
        // Check if user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Inside the login function, after password verification:

            // Check if user has 2FA enabled
            $twoFactorAuth = new TwoFactorAuth($pdo);
            if ($twoFactorAuth->is2FAEnabled($user['id'])) {
                // If 2FA is enabled, don't complete login yet
                // Store user info temporarily in session
                $_SESSION['2fa_user_id'] = $user['id'];
                $_SESSION['2fa_username'] = $user['username'];
                $_SESSION['2fa_role'] = $user['role'];
                
                // Return with a 2FA required flag
                return [
                    'success' => false,
                    'requires_2fa' => true,
                    'message' => 'Two-factor authentication required'
                ];
            }

            // If no 2FA or 2FA is verified, complete login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Log successful login attempt
            logLoginAttempt($email, $ip_address, $user_agent, 'success');
            
            error_log("Login successful, set session variables: user_id=" . $_SESSION['user_id'] . ", username=" . $_SESSION['username']);
            return true;
        }
        
        // Log failed login attempt
        logLoginAttempt($email, $ip_address, $user_agent, 'failure');
        
        error_log("Login failed: user not found or password incorrect");
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log login attempt to database
 */
function logLoginAttempt($email, $ip_address, $user_agent, $status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts 
            (email, ip_address, user_agent, status) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$email, $ip_address, $user_agent, $status]);
    } catch (PDOException $e) {
        error_log("Error logging login attempt: " . $e->getMessage());
        return false;
    }
}

function logoutUser() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Add the password reset functions
function emailExists($email) {
    // Check if the email exists in the database
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

function storeToken($email, $token) {
    // Store the token in the database with an expiration time
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
    $stmt->execute([$token, $email]);
}

function sendPasswordResetEmail($email, $token) {
    $subject = "Password Reset Request";
    $resetLink = SITE_URL . "/reset-password.php?token=" . $token;
    $message = "Click the following link to reset your password: " . $resetLink;
    mail($email, $subject, $message);
}
?>