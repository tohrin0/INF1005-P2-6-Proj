<?php

class User {
    protected $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Register a new user
     */
    public function register($username, $password, $email) {
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
    public function login($email, $password) {
        try {
            // Search by email
            $stmt = $this->db->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Check if user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Update user details
     */
    public function updateUser($id, $data) {
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
    public function isAdmin($id) {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user && $user['role'] === 'admin';
    }

    public function updateProfile($id, $username, $email) {
        $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        return $stmt->execute([$username, $email, $id]);
    }

    public function deleteUser($id) {
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
    public function getAllUsers() {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get users by specific role
     * 
     * @param string $role The role to filter by ('admin' or 'user')
     * @return array Array of users with the specified role
     */
    public function getUsersByRole($role) {
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
    public function changeUserRole($userId, $newRole) {
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
    private function isLastAdmin($userId) {
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
    public function getTotalUserCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get count of users by role
     * 
     * @param string $role The role to count ('admin' or 'user')
     * @return int Number of users with specified role
     */
    public function getUserCountByRole($role) {
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
    public function changePassword($userId, $oldPassword, $newPassword) {
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
    public function requestDeletion($userId, $deletionTime, $token) {
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
    public function cancelDeletion($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET deletion_requested = NULL, deletion_token = NULL WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error cancelling account deletion: " . $e->getMessage());
            return false;
        }
    }
}