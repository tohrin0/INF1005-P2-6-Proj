<?php

class Payment {
    private $db;
    private $amount;
    private $currency;
    private $paymentMethod;
    private $transactionId;

    // Update constructor to initialize database connection
    public function __construct($amount = null, $currency = 'USD', $paymentMethod = null) {
        global $pdo;
        $this->db = $pdo;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Validate credit card payment details
     * 
     * @param string $cardNumber The credit card number
     * @param string $expiryDate The card's expiry date in MM/YY format
     * @param string $cvv The card verification value
     * @param string $cardholderName The cardholder's name
     * @return array [isValid, errorMessage]
     */
    public function validateCardDetails($cardNumber, $expiryDate, $cvv, $cardholderName) {
        // Validate card number (must be Visa or MasterCard)
        if (empty($cardNumber) || !preg_match('/^(4|5)\d{15}$/', $cardNumber)) {
            return [false, "Please enter a valid Visa or MasterCard card number"];
        }
        
        // Validate expiry date (format and not expired)
        if (empty($expiryDate) || !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiryDate, $matches)) {
            return [false, "Please enter a valid expiry date (MM/YY)"];
        }
        
        // Extract month and year
        $expMonth = intval($matches[1]);
        $expYear = intval('20' . $matches[2]);
        
        // Get current date for comparison
        $currentMonth = intval(date('m'));
        $currentYear = intval(date('Y'));
        
        // Check if card is expired
        if (($expYear < $currentYear) || ($expYear == $currentYear && $expMonth < $currentMonth)) {
            return [false, "The card has expired"];
        }
        
        // Check for unreasonably far future dates
        if ($expYear > 2035) {
            return [false, "Please enter a valid expiry date"];
        }
        
        // Validate CVV (must be 3 digits)
        if (empty($cvv) || !preg_match('/^[0-9]{3}$/', $cvv)) {
            return [false, "Please enter a valid 3-digit CVV code"];
        }
        
        // Validate cardholder name
        if (empty($cardholderName)) {
            return [false, "Please enter the cardholder name"];
        }
        
        // All validations passed
        return [true, ""];
    }

    // Process payment method
    public function processPayment($userId, $amount, $paymentMethod) {
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        
        // Generate a transaction ID
        $this->transactionId = uniqid('txn_');
        
        // Simulate payment processing (always successful in this demo)
        return [
            'success' => true,
            'transaction_id' => $this->transactionId
        ];
    }
    
    /**
     * Update booking status after successful payment
     * 
     * @param int $bookingId The booking ID
     * @param string $status The new status (typically 'confirmed')
     * @param string $transactionId Optional transaction ID
     * @return bool Success or failure
     */
    public function updateBookingAfterPayment($bookingId, $status = 'confirmed', $transactionId = null) {
        try {
            // Update the booking status
            $stmt = $this->db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $bookingId]);
            
            if ($result && $transactionId) {
                // Record the payment in payments table if you have one
                $this->recordPayment($bookingId, $transactionId);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating booking status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record payment details in payments table
     * 
     * @param int $bookingId The booking ID
     * @param string $transactionId The payment transaction ID
     * @return bool Success or failure
     */
    private function recordPayment($bookingId, $transactionId) {
        try {
            // Check if payments table is properly set up in your database
            $stmt = $this->db->prepare(
                "INSERT INTO payments (booking_id, transaction_id, amount, payment_method, status, created_at) 
                 VALUES (?, ?, ?, ?, 'completed', NOW())"
            );
            return $stmt->execute([$bookingId, $transactionId, $this->amount, $this->paymentMethod]);
        } catch (PDOException $e) {
            // Just log the error but don't fail the main booking status update
            error_log("Error recording payment details: " . $e->getMessage());
            return false;
        }
    }

    public function getTransactionId() {
        return $this->transactionId;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getPaymentMethod() {
        return $this->paymentMethod;
    }
}