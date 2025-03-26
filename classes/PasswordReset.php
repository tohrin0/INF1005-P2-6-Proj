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
        $this->mailer->Username = 'augmenso.to@gmail.com';
        $this->mailer->Password = 'vjks aktz vheu arse'; // In production, use environment variables
        $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('augmenso.to@gmail.com', 'Flight Booking');
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
                    <p>You requested a password reset for your Flight Booking account.</p>
                    <p>Your One-Time Password (OTP) is: <strong style='font-size: 18px; letter-spacing: 2px;'>{$otp}</strong></p>
                    <p>This OTP will expire in 30 minutes.</p>
                    <p>If you did not request this reset, please ignore this email.</p>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Reset password for a user
     */
    public function resetPassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$hashedPassword, $email]);
            
            // Clear session variables after successful reset
            if ($result) {
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_otp_time']);
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
}