<?php
require_once __DIR__ . '/../vendor/autoload.php';
class TwoFactorAuth {
    private $pdo;
    private $google2fa;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->google2fa = new \PragmaRX\Google2FA\Google2FA();
    }
    
    /**
     * Generate a new 2FA secret for a user
     * 
     * @return string The generated secret
     */
    public function generateSecret() {
        return $this->google2fa->generateSecretKey();
    }
    
    /**
     * Get QR code for the 2FA setup
     * 
     * @param string $email User's email
     * @param string $secret The 2FA secret
     * @return string QR code as SVG
     */
    public function getQRCode($email, $secret) {
        $company = 'Sky International Travels';
        $qrCodeUrl = $this->google2fa->getQRCodeUrl($company, $email, $secret);
        
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        
        $writer = new \BaconQrCode\Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }
    
    /**
     * Enable 2FA for a user
     * 
     * @param int $userId User ID
     * @param string $secret The 2FA secret
     * @return bool Success status
     */
    public function enable2FA($userId, $secret) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
            return $stmt->execute([$secret, $userId]);
        } catch (\PDOException $e) {
            error_log("Error enabling 2FA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Disable 2FA for a user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function disable2FA($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            error_log("Error disabling 2FA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify a 2FA code
     * 
     * @param string $secret The user's 2FA secret
     * @param string $code The code to verify
     * @return bool Whether the code is valid
     */
    public function verifyCode($secret, $code) {
        return $this->google2fa->verifyKey($secret, $code);
    }
    
    /**
     * Check if 2FA is enabled for a user
     * 
     * @param int $userId User ID
     * @return bool Whether 2FA is enabled
     */
    public function is2FAEnabled($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result && $result['two_factor_enabled'] == 1;
        } catch (\PDOException $e) {
            error_log("Error checking 2FA status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's 2FA secret
     * 
     * @param int $userId User ID
     * @return string|null The 2FA secret or null if not enabled
     */
    public function getUserSecret($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT two_factor_secret FROM users WHERE id = ? AND two_factor_enabled = 1");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ? $result['two_factor_secret'] : null;
        } catch (\PDOException $e) {
            error_log("Error getting 2FA secret: " . $e->getMessage());
            return null;
        }
    }
}