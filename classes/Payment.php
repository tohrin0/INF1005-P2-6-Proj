<?php

class Payment {
    private $amount;
    private $currency;
    private $paymentMethod;
    private $transactionId;

    // Update the constructor to accept default values
    public function __construct($amount = null, $currency = 'USD', $paymentMethod = null) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->paymentMethod = $paymentMethod;
    }

    // Add validation method
    public function validatePayment($amount, $paymentMethod) {
        // Basic validation
        return is_numeric($amount) && $amount > 0 && !empty($paymentMethod);
    }

    // Update process payment method to handle user ID
    public function processPayment($userId, $amount, $paymentMethod) {
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        
        // Process payment logic
        $success = $this->executePaymentProcess();
        
        if ($success) {
            return [
                'success' => true,
                'transaction_id' => $this->transactionId
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Payment processing failed'
        ];
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
    
    // Private method to handle the actual payment processing
    private function executePaymentProcess() {
        // Simulate payment processing
        $this->transactionId = uniqid('txn_');
        return true;
    }
}