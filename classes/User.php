<?php

class User
{
    protected $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Register a new user
     */
    public function register($username, $password, $email)
    {
        // Check if email already exists
        $checkStmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->rowCount() > 0) {
            return false;
        }

        // Check if username already exists
        $checkStmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->rowCount() > 0) {
            return false;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->beginTransaction();

            // Insert user
            $stmt = $this->db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $userInserted = $stmt->execute([$username, $hashedPassword, $email]);

            if (!$userInserted) {
                $this->db->rollBack();
                return false;
            }

            // Get the user ID
            $userId = $this->db->lastInsertId();

            // Add password to history
            $stmt = $this->db->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
            $historyAdded = $stmt->execute([$userId, $hashedPassword]);

            if (!$historyAdded) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log in a user
     */
    public function login($email, $password)
    {
        try {
            // Get IP address and user agent for logging
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            // Check for IP-based rate limiting first
            if ($this->isIpRateLimited($ip_address)) {
                // Log the blocked attempt
                $this->logLoginAttempt($email, $ip_address, $user_agent, 'blocked');
                return [
                    'success' => false,
                    'message' => 'Too many login attempts from this IP address. Please try again later.',
                    'rate_limited' => true
                ];
            }

            // Search by email
            $stmt = $this->db->prepare("SELECT id, username, password, role, lockout_until, failed_login_attempts, two_factor_enabled FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Check if user exists
            if (!$user) {
                // Log failed login attempt
                $this->logLoginAttempt($email, $ip_address, $user_agent, 'failure');
                $this->incrementIpAttempts($ip_address);
                return [
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ];
            }

            // Check if account is locked
            if ($user['lockout_until'] !== null && new \DateTime($user['lockout_until']) > new \DateTime()) {
                // Account is locked, log attempt as blocked
                $this->logLoginAttempt($email, $ip_address, $user_agent, 'blocked');
                $lockoutTime = new \DateTime($user['lockout_until']);
                $now = new \DateTime();
                $minutesLeft = ceil(($lockoutTime->getTimestamp() - $now->getTimestamp()) / 60);

                return [
                    'success' => false,
                    'message' => "Your account is temporarily locked due to multiple failed login attempts. Please try again in {$minutesLeft} minute(s) or reset your password.",
                    'locked' => true
                ];
            }

            // Check if password is correct
            if (password_verify($password, $user['password'])) {
                // Check if 2FA is enabled for the user
                if ($user['two_factor_enabled'] == 1) {
                    // Log this attempt as "pending 2FA verification"
                    $this->logLoginAttempt($email, $ip_address, $user_agent, 'pending');
                    
                    return [
                        'success' => false,
                        'requires_2fa' => true,
                        'user_id' => $user['id'],
                        'message' => 'Two-factor authentication required'
                    ];
                }
                
                // Regenerate session ID to prevent session fixation
                regenerateSessionId();

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();

                // Reset failed login attempts and clear lockout
                $resetStmt = $this->db->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL, last_login_at = NOW() WHERE id = ?");
                $resetStmt->execute([$user['id']]);

                // Log successful login to database
                $this->logLoginAttempt($email, $ip_address, $user_agent, 'success');

                // Reset IP-based attempts for this IP on successful login
                $this->resetIpAttempts($ip_address);

                // Log successful login to error log as well (can be removed in production)
                error_log("User {$user['id']} ({$email}) logged in successfully");

                return [
                    'success' => true,
                    'message' => 'Login successful'
                ];
            }

            // Password is incorrect, increment failed attempts
            $this->incrementFailedAttempts($user['id'], $user['failed_login_attempts']);

            // Also increment IP-based attempts
            $this->incrementIpAttempts($ip_address);

            // Log failed login attempt to database
            $this->logLoginAttempt($email, $ip_address, $user_agent, 'failure');

            // Log to error log as well (can be removed in production)
            error_log("Failed login attempt for email: {$email}");

            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        } catch (\PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'A system error occurred. Please try again later.'
            ];
        }
    }

    /**
     * Log login attempt to database
     * 
     * @param string $email Email address used for login attempt
     * @param string $ip_address IP address of the user
     * @param string $user_agent User agent of the browser
     * @param string $status 'success' or 'failure'
     * @return bool Whether logging was successful
     */
    private function logLoginAttempt($email, $ip_address, $user_agent, $status)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts 
                (email, ip_address, user_agent, status) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$email, $ip_address, $user_agent, $status]);
        } catch (\PDOException $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
            return false; // Logging failure shouldn't break the authentication flow
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT id, username, email, role, two_factor_enabled, created_at, deletion_requested FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user by username
     */
    public function getUserByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Update user details
     */
    public function updateUser($id, $data)
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Check if a user is admin
     */
    public function isAdmin($id)
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user && $user['role'] === 'admin';
    }

    public function updateProfile($id, $username, $email)
    {
        $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        return $stmt->execute([$username, $email, $id]);
    }

    public function deleteUser($id)
    {
        // First check if this is the last admin
        if ($this->isLastAdmin($id)) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // New methods for admin functionality

    /**
     * Get all users from the database
     * 
     * @return array Array of users
     */
    public function getAllUsers()
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get users by specific role
     * 
     * @param string $role The role to filter by ('admin' or 'user')
     * @return array Array of users with the specified role
     */
    public function getUsersByRole($role)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Change a user's role
     * 
     * @param int $userId The user ID to modify
     * @param string $newRole The new role ('admin' or 'user')
     * @return bool Success or failure
     */
    public function changeUserRole($userId, $newRole)
    {
        // Don't change role of the last admin
        if ($newRole === 'user' && $this->isLastAdmin($userId)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$newRole, $userId]);
    }

    /**
     * Check if this is the last admin user
     * 
     * @param int $userId The user ID to check
     * @return bool True if this is the last admin user
     */
    private function isLastAdmin($userId)
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['role'] === 'admin') {
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $adminCount = (int)$stmt->fetchColumn();
            return $adminCount <= 1;
        }

        return false;
    }

    /**
     * Get total count of users
     * 
     * @return int Number of users
     */
    public function getTotalUserCount()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get count of users by role
     * 
     * @param string $role The role to count ('admin' or 'user')
     * @return int Number of users with specified role
     */
    public function getUserCountByRole($role)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Change user password with history check
     * @param int $userId User ID
     * @param string $oldPassword Current password
     * @param string $newPassword New password
     * @return array [success: bool, message: string] Result with message
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        try {
            // Validate password strength
            list($isValid, $message) = validatePasswordStrength($newPassword);
            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => $message
                ];
            }

            // Get user's current password hash
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => "User not found."
                ];
            }

            // Verify current password
            if (!password_verify($oldPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => "Current password is incorrect."
                ];
            }

            // Check if new password matches current password
            if (password_verify($newPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => "New password cannot be the same as your current password."
                ];
            }

            // Check password history (last 5 passwords)
            $stmt = $this->db->prepare("
                SELECT password_hash 
                FROM password_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $passwordHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($passwordHistory as $historyItem) {
                if (password_verify($newPassword, $historyItem['password_hash'])) {
                    return [
                        'success' => false,
                        'message' => "New password cannot be the same as any of your previous 5 passwords."
                    ];
                }
            }

            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Start transaction
            $this->db->beginTransaction();

            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $passwordUpdated = $stmt->execute([$hashedPassword, $userId]);

            if (!$passwordUpdated) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => "Failed to update password."
                ];
            }

            // Add to password history
            $stmt = $this->db->prepare("
                INSERT INTO password_history (user_id, password_hash) 
                VALUES (?, ?)
            ");
            $historyAdded = $stmt->execute([$userId, $hashedPassword]);

            if (!$historyAdded) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => "Failed to update password history."
                ];
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => "Password updated successfully."
            ];
        } catch (\PDOException $e) {
            // Rollback transaction in case of error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log("Database error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "An error occurred. Please try again later."
            ];
        }
    }

    /**
     * Request account deletion with 24 hour window
     * 
     * @param int $userId User ID
     * @param string $deletionTime Timestamp when deletion was requested
     * @param string $token Unique token for deletion verification
     * @return bool Success or failure
     */
    public function requestDeletion($userId, $deletionTime, $token)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET deletion_requested = ?, deletion_token = ? WHERE id = ?");
            return $stmt->execute([$deletionTime, $token, $userId]);
        } catch (PDOException $e) {
            error_log("Error requesting account deletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel account deletion request
     * 
     * @param int $userId User ID
     * @return bool Success or failure
     */
    public function cancelDeletion($userId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET deletion_requested = NULL, deletion_token = NULL WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error cancelling account deletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment failed login attempts for a user
     * Lock the account if threshold is reached
     */
    private function incrementFailedAttempts($userId, $currentAttempts)
    {
        // Threshold for account lockout
        $maxAttempts = 5;

        // Lockout duration in minutes
        $lockoutDuration = 15;

        $newAttempts = $currentAttempts + 1;

        if ($newAttempts >= $maxAttempts) {
            // Lock the account
            $lockoutUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutDuration} minutes"));
            $stmt = $this->db->prepare("UPDATE users SET failed_login_attempts = ?, lockout_until = ? WHERE id = ?");
            $stmt->execute([$newAttempts, $lockoutUntil, $userId]);

            error_log("Account ID {$userId} locked until {$lockoutUntil} after {$newAttempts} failed attempts");
        } else {
            // Just increment the counter
            $stmt = $this->db->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
            $stmt->execute([$newAttempts, $userId]);
        }

        return $newAttempts;
    }

    /**
     * Check if an IP address is rate limited
     */
    private function isIpRateLimited($ipAddress)
    {
        try {
            // Get current rate limit status
            $stmt = $this->db->prepare("SELECT attempts, blocked_until, first_attempt_at FROM ip_rate_limits WHERE ip_address = ?");
            $stmt->execute([$ipAddress]);
            $rateLimit = $stmt->fetch(\PDO::FETCH_ASSOC);

            // If no record exists, IP is not rate limited
            if (!$rateLimit) {
                return false;
            }

            // Check if IP is blocked
            if ($rateLimit['blocked_until'] !== null && new \DateTime($rateLimit['blocked_until']) > new \DateTime()) {
                return true;
            }

            // Check if the time window has reset (2 hours)
            $firstAttempt = new \DateTime($rateLimit['first_attempt_at']);
            $now = new \DateTime();
            $hoursSinceFirstAttempt = ($now->getTimestamp() - $firstAttempt->getTimestamp()) / 3600;

            if ($hoursSinceFirstAttempt > 2) {
                // Reset the counter if time window has passed
                $this->resetIpAttempts($ipAddress);
                return false;
            }

            // IP is not currently blocked, but we'll check attempt count in incrementIpAttempts
            return false;
        } catch (\PDOException $e) {
            error_log("Error checking IP rate limit: " . $e->getMessage());
            return false; // Don't block on errors
        }
    }

    /**
     * Increment IP-based attempt counter
     */
    private function incrementIpAttempts($ipAddress)
    {
        try {
            // Maximum attempts allowed in the time window
            $maxAttempts = 15;

            // Block duration in minutes
            $blockDuration = 30;

            // First, check if entry exists
            $stmt = $this->db->prepare("SELECT id, attempts FROM ip_rate_limits WHERE ip_address = ?");
            $stmt->execute([$ipAddress]);
            $rateLimit = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($rateLimit) {
                // Increment existing record
                $newAttempts = $rateLimit['attempts'] + 1;

                if ($newAttempts >= $maxAttempts) {
                    // Block the IP
                    $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$blockDuration} minutes"));
                    $stmt = $this->db->prepare("UPDATE ip_rate_limits SET attempts = ?, blocked_until = ?, updated_at = NOW() WHERE ip_address = ?");
                    $stmt->execute([$newAttempts, $blockedUntil, $ipAddress]);

                    error_log("IP address {$ipAddress} blocked until {$blockedUntil} after {$newAttempts} attempts");
                } else {
                    // Just increment the counter
                    $stmt = $this->db->prepare("UPDATE ip_rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE ip_address = ?");
                    $stmt->execute([$ipAddress]);
                }
            } else {
                // Create new record
                $stmt = $this->db->prepare("INSERT INTO ip_rate_limits (ip_address, attempts, first_attempt_at) VALUES (?, 1, NOW())");
                $stmt->execute([$ipAddress]);
            }
        } catch (\PDOException $e) {
            error_log("Error incrementing IP attempts: " . $e->getMessage());
        }
    }

    /**
     * Reset IP-based attempts counter
     */
    private function resetIpAttempts($ipAddress)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM ip_rate_limits WHERE ip_address = ?");
            $stmt->execute([$ipAddress]);
        } catch (\PDOException $e) {
            error_log("Error resetting IP attempts: " . $e->getMessage());
        }
    }
    /**
     * Reset failed login attempts for a user
     * 
     * @param int $userId The user ID
     * @return bool Whether the operation was successful
     */
    public function resetFailedAttempts($userId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            error_log("Error resetting failed attempts: " . $e->getMessage());
            return false;
        }
    }
}
