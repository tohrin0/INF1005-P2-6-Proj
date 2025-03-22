<?php

class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function register($username, $password, $email) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $hashedPassword, $email]);
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
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
}