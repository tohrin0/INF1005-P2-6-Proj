<?php

class PasswordReset {
    private $pdo;
    private $mailer;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Initialize PHPMailer
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer with Gmail SMTP settings
     */
    private function initializeMailer() {
        $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = getenv('SMTP_USERNAME') ?: 'augmenso.to@gmail.com';
        $this->mailer->Password = getenv('SMTP_PASSWORD') ?: 'vjks aktz vheu arse';
        $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('augmenso.to@gmail.com', 'Sky International Travels');
        $this->mailer->isHTML(true);
    }
    
    /**
     * Check if email exists in the database
     */
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Generate 6-digit OTP
     */
    public function generateOTP() {
        return rand(100000, 999999);
    }
    
    /**
     * Store OTP in session with expiry time
     */
    public function storeOTP($email, $otp) {
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_time'] = time();
    }
    
    /**
     * Verify OTP is valid and not expired
     */
    public function verifyOTP($enteredOTP) {
        if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp_time'])) {
            return false;
        }
        
        $storedOTP = $_SESSION['reset_otp'];
        $otpTime = $_SESSION['reset_otp_time'];
        
        // OTP expires after 30 minutes
        if (time() - $otpTime > 1800) {
            return false;
        }
        
        return $storedOTP == $enteredOTP;
    }
    
    /**
     * Send OTP via email
     */
    public function sendOTPEmail($email, $otp) {
        try {
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Password Reset OTP';
            $this->mailer->Body = $this->getOTPEmailTemplate($otp);
            
            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Get HTML template for OTP email
     */
    private function getOTPEmailTemplate($otp) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                    <h2 style='color: #3366cc;'>Password Reset</h2>
                    <p>You requested a password reset for your Sky International Travels account.</p>
                    <p>Your One-Time Password (OTP) is: <strong style='font-size: 18px; letter-spacing: 2px;'>{$otp}</strong></p>
                    <p>This OTP will expire in 30 minutes.</p>
                    <p>If you did not request this reset, please ignore this email.</p>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Check if password matches any recent passwords
     * @param string $email User's email
     * @param string $newPassword Plain text new password
     * @return bool True if password is already used, false otherwise
     */
    public function isPasswordReused($email, $newPassword) {
        try {
            // Get user ID
            $stmt = $this->pdo->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false; // User not found
            }
            
            $userId = $user['id'];
            
            // Check current password
            if (password_verify($newPassword, $user['password'])) {
                return true; // Matches current password
            }
            
            // Check password history (last 5 passwords)
            $stmt = $this->pdo->prepare("
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
                    return true; // Matches a previous password
                }
            }
            
            return false; // Password not found in history
        } catch (\PDOException $e) {
            error_log("Database error checking password history: " . $e->getMessage());
            return false; // In case of error, allow the reset
        }
    }
    
    /**
     * Add password to history
     * @param int $userId User ID
     * @param string $passwordHash Hashed password to store
     * @return bool Success status
     */
    private function addPasswordToHistory($userId, $passwordHash) {
        try {
            // Insert new password into history
            $stmt = $this->pdo->prepare("
                INSERT INTO password_history (user_id, password_hash) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$userId, $passwordHash]);
        } catch (\PDOException $e) {
            error_log("Database error adding password to history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset password for a user with history check
     * @param string $email User's email
     * @param string $newPassword New password in plain text
     * @return array [success: bool, message: string] Result with message
     */
    public function resetPassword($email, $newPassword) {
        try {
            // Validate password strength
            list($isValid, $message) = validatePasswordStrength($newPassword);
            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => $message
                ];
            }
            
            // Check if password is reused
            if ($this->isPasswordReused($email, $newPassword)) {
                return [
                    'success' => false,
                    'message' => "Cannot reuse your current password or any of your last 5 passwords."
                ];
            }
            
            // Get user ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => "User not found."
                ];
            }
            
            $userId = $user['id'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Update user's password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $passwordUpdated = $stmt->execute([$hashedPassword, $email]);
            
            if (!$passwordUpdated) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => "Failed to update password."
                ];
            }
            
            // Add password to history
            $historyAdded = $this->addPasswordToHistory($userId, $hashedPassword);
            
            if (!$historyAdded) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => "Failed to update password history."
                ];
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            // Clear session variables after successful reset
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_otp_time']);
            
            return [
                'success' => true,
                'message' => "Your password has been reset successfully."
            ];
        } catch (\PDOException $e) {
            // Rollback transaction in case of error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            error_log("Database error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "An error occurred. Please try again later."
            ];
        }
    }

    /**
     * Verify admin reset token
     * @param string $token Reset token from URL
     * @return array User data if token is valid, false otherwise
     */
    public function verifyAdminResetToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, username 
                FROM users 
                WHERE reset_token = ? 
                  AND token_expiry > NOW() 
                  AND admin_reset = 1
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user) {
                // Store user email in session for the reset process
                $_SESSION['reset_email'] = $user['email'];
                return $user;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Token verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear admin reset token after use
     * @param string $email User's email
     */
    public function clearAdminResetToken($email) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET reset_token = NULL, token_expiry = NULL, admin_reset = 0 
                WHERE email = ?
            ");
            return $stmt->execute([$email]);
        } catch (\PDOException $e) {
            error_log("Clear token error: " . $e->getMessage());
            return false;
        }
    }
}