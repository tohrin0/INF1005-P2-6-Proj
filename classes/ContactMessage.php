<?php

class ContactMessage {
    private $db;
    private $name;
    private $email;
    private $message;
    private $subject;
    private $status;
    
    /**
     * Constructor - initializes the ContactMessage object with database connection
     */
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->status = 'unread'; // Default status for new messages
    }
    
    /**
     * Set message properties from form data
     * 
     * @param array $formData Form data array containing message details
     * @return ContactMessage Returns the current ContactMessage instance for chaining
     */
    public function setFromForm($formData) {
        $this->name = trim($formData['name'] ?? '');
        $this->email = trim($formData['email'] ?? '');
        $this->message = trim($formData['message'] ?? '');
        $this->subject = trim($formData['subject'] ?? 'Contact Message'); // Optional field
        
        return $this;
    }
    
    /**
     * Validate the contact form data
     * 
     * @return array [isValid, errorMessage]
     */
    public function validate() {
        // Check if required fields are empty
        if (empty($this->name) || empty($this->email) || empty($this->message)) {
            return [false, 'Please fill in all the required fields.'];
        }
        
        // Validate email format
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Please enter a valid email address.'];
        }
        
        // Validate message length
        if (strlen($this->message) < 10) {
            return [false, 'Your message is too short. Please provide more details.'];
        }
        
        // All validations passed
        return [true, ''];
    }
    
    /**
     * Save message to the database
     * 
     * @return bool True on success, false on failure
     */
    public function save() {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO contact_messages (name, email, message, subject, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            
            return $stmt->execute([
                $this->name,
                $this->email,
                $this->message,
                $this->subject,
                $this->status
            ]);
        } catch (PDOException $e) {
            error_log("Error saving contact message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all messages with optional filter and pagination
     * 
     * @param string $status Filter by status (unread, read, responded, all)
     * @param int $page Current page number
     * @param int $perPage Messages per page
     * @return array Array of messages
     */
    public function getAllMessages($status = 'all', $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            if ($status !== 'all') {
                $stmt = $this->db->prepare(
                    "SELECT * FROM contact_messages 
                     WHERE status = ? 
                     ORDER BY created_at DESC 
                     LIMIT ? OFFSET ?"
                );
                $stmt->execute([$status, $perPage, $offset]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT * FROM contact_messages 
                     ORDER BY created_at DESC 
                     LIMIT ? OFFSET ?"
                );
                $stmt->execute([$perPage, $offset]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting messages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of messages with optional filter
     * 
     * @param string $status Filter by status (unread, read, responded, all)
     * @return int Number of messages
     */
    public function getTotalCount($status = 'all') {
        try {
            if ($status !== 'all') {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM contact_messages WHERE status = ?");
                $stmt->execute([$status]);
            } else {
                $stmt = $this->db->query("SELECT COUNT(*) FROM contact_messages");
            }
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting messages: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update the status of a message
     * 
     * @param int $messageId Message ID
     * @param string $status New status (read, unread, responded)
     * @return bool Success or failure
     */
    public function updateStatus($messageId, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $messageId]);
        } catch (PDOException $e) {
            error_log("Error updating message status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a message
     * 
     * @param int $messageId Message ID
     * @return bool Success or failure
     */
    public function delete($messageId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM contact_messages WHERE id = ?");
            return $stmt->execute([$messageId]);
        } catch (PDOException $e) {
            error_log("Error deleting message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a message by ID
     * 
     * @param int $messageId Message ID
     * @return array|null Message data or null if not found
     */
    public function getById($messageId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM contact_messages WHERE id = ?");
            $stmt->execute([$messageId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting message: " . $e->getMessage());
            return null;
        }
    }
}