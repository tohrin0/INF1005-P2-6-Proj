<?php

class Payment {
    private $db;
    private $amount;
    private $currency;
    private $paymentMethod;
    private $transactionId;

    // Update constructor to initialize database connection
    public function __construct($amount = 0, $currency = 'USD', $paymentMethod = 'credit_card') {
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
        
        // Debug line
        error_log("Processing payment with amount: $amount, method: $paymentMethod");
        
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
    public function updateBookingAfterPayment($bookingId, $status, $transactionId) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Update booking status
            $bookingStmt = $this->db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $bookingResult = $bookingStmt->execute([$status, $bookingId]);
            
            if ($bookingResult) {
                // Get booking amount
                $bookingQuery = $this->db->prepare("SELECT total_price FROM bookings WHERE id = ?");
                $bookingQuery->execute([$bookingId]);
                $booking = $bookingQuery->fetch(PDO::FETCH_ASSOC);
                $amount = $booking ? $booking['total_price'] : $this->amount;
                
                // Insert payment record
                $paymentStmt = $this->db->prepare(
                    "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) 
                     VALUES (?, ?, ?, ?, 'completed')"
                );
                
                $paymentResult = $paymentStmt->execute([
                    $bookingId,
                    $amount,
                    $this->paymentMethod,
                    $transactionId
                ]);
                
                if ($paymentResult) {
                    $this->db->commit();
                    return true;
                }
            }
            
            // If we got here, something went wrong
            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error processing payment: " . $e->getMessage());
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
            // Match the schema exactly
            $stmt = $this->db->prepare(
                "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) 
                 VALUES (?, ?, ?, ?, 'completed')"
            );
            
            // Debug line
            error_log("Recording payment for booking: $bookingId");
            
            return $stmt->execute([
                $bookingId,
                $this->amount,
                $this->paymentMethod,
                $transactionId
            ]);
        } catch (PDOException $e) {
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

    public function getPaymentsByBookingId($bookingId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}