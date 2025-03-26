<?php

class EmailNotification {
    private $mailer;
    
    public function __construct() {
        // Make sure PHPMailer is properly loaded
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Include autoloader if not already included
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        
        // Initialize PHPMailer
        $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'augmenso.to@gmail.com';
        $this->mailer->Password = 'vjks aktz vheu arse'; // Use environment variables in production
        $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('augmenso.to@gmail.com', 'Flight Booking');
        $this->mailer->isHTML(true);
    }
    
    /**
     * Send an email notification for a new booking (pending payment)
     * 
     * @param array $booking Booking details
     * @param array $flight Flight details
     * @return bool Success status
     */
    public function sendPendingPaymentEmail($booking, $flight) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['customer_email'], $booking['customer_name']);
            $this->mailer->Subject = 'Your Flight Booking Awaits Payment - Booking #' . $booking['id'];
            
            // Get the email template
            $emailBody = $this->getPendingPaymentTemplate($booking, $flight);
            
            $this->mailer->Body = $emailBody;
            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send an email notification for a confirmed booking
     * 
     * @param array $booking Booking details
     * @param array $flight Flight details
     * @param array $payment Payment details
     * @return bool Success status
     */
    public function sendConfirmationEmail($booking, $flight, $payment = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['customer_email'], $booking['customer_name']);
            $this->mailer->Subject = 'Booking Confirmed - Your Flight is Ready! - Booking #' . $booking['id'];
            
            // Get the email template
            $emailBody = $this->getConfirmationTemplate($booking, $flight, $payment);
            
            $this->mailer->Body = $emailBody;
            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send an email notification for a cancelled booking
     * 
     * @param array $booking Booking details
     * @param array $flight Flight details
     * @return bool Success status
     */
    public function sendCancellationEmail($booking, $flight) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['customer_email'], $booking['customer_name']);
            $this->mailer->Subject = 'Your Booking Has Been Cancelled - Booking #' . $booking['id'];
            
            // Get the email template
            $emailBody = $this->getCancellationTemplate($booking, $flight);
            
            $this->mailer->Body = $emailBody;
            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the HTML template for pending payment notification
     */
    private function getPendingPaymentTemplate($booking, $flight) {
        // Format date and time
        $formattedDate = date('l, F j, Y', strtotime($flight['date']));
        $formattedTime = date('g:i A', strtotime($flight['time']));
        
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h1 style='color: #3366cc; margin-bottom: 20px;'>Your Booking Awaits Payment</h1>
                    <p style='font-size: 16px;'>Dear {$booking['customer_name']},</p>
                    <p style='font-size: 16px;'>Thank you for booking with us! Your flight reservation is currently <strong style='color: #f0ad4e;'>pending payment</strong>. Please complete your payment to secure your flight.</p>
                    
                    <div style='background-color: #fff; border-left: 4px solid #3366cc; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                        <h2 style='margin-top: 0; color: #3366cc; font-size: 18px;'>Booking Details</h2>
                        <p style='margin: 5px 0;'><strong>Booking ID:</strong> #{$booking['id']}</p>
                        <p style='margin: 5px 0;'><strong>Flight:</strong> {$flight['flight_number']}</p>
                        <p style='margin: 5px 0;'><strong>Route:</strong> {$flight['departure']} to {$flight['arrival']}</p>
                        <p style='margin: 5px 0;'><strong>Date:</strong> {$formattedDate}</p>
                        <p style='margin: 5px 0;'><strong>Time:</strong> {$formattedTime}</p>
                        <p style='margin: 5px 0;'><strong>Passengers:</strong> {$booking['passengers']}</p>
                        <p style='margin: 5px 0;'><strong>Total Amount:</strong> $" . number_format($booking['total_price'], 2) . "</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "/payments.php?booking_id={$booking['id']}' style='background-color: #3366cc; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>Complete Payment</a>
                    </div>
                    
                    <p>If you have any questions, please don't hesitate to contact our customer service team.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>The Flight Booking Team</p>
                </div>
                
                <div style='text-align: center; font-size: 12px; color: #777; margin-top: 20px;'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Get the HTML template for booking confirmation
     */
    private function getConfirmationTemplate($booking, $flight, $payment) {
        // Format date and time
        $formattedDate = date('l, F j, Y', strtotime($flight['date']));
        $formattedTime = date('g:i A', strtotime($flight['time']));
        $paymentDate = isset($payment['payment_date']) ? date('F j, Y', strtotime($payment['payment_date'])) : date('F j, Y');
        $transactionId = isset($payment['transaction_id']) ? $payment['transaction_id'] : 'N/A';
        
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h1 style='color: #28a745; margin-bottom: 20px;'>Your Booking is Confirmed!</h1>
                    <p style='font-size: 16px;'>Dear {$booking['customer_name']},</p>
                    <p style='font-size: 16px;'>Great news! Your flight booking has been <strong style='color: #28a745;'>confirmed</strong>. Thank you for choosing to fly with us.</p>
                    
                    <div style='background-color: #fff; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                        <h2 style='margin-top: 0; color: #28a745; font-size: 18px;'>Flight Details</h2>
                        <p style='margin: 5px 0;'><strong>Booking ID:</strong> #{$booking['id']}</p>
                        <p style='margin: 5px 0;'><strong>Flight:</strong> {$flight['flight_number']}</p>
                        <p style='margin: 5px 0;'><strong>Airline:</strong> {$flight['airline']}</p>
                        <p style='margin: 5px 0;'><strong>From:</strong> {$flight['departure']}</p>
                        <p style='margin: 5px 0;'><strong>To:</strong> {$flight['arrival']}</p>
                        <p style='margin: 5px 0;'><strong>Date:</strong> {$formattedDate}</p>
                        <p style='margin: 5px 0;'><strong>Departure Time:</strong> {$formattedTime}</p>
                        <p style='margin: 5px 0;'><strong>Passengers:</strong> {$booking['passengers']}</p>
                    </div>
                    
                    <div style='background-color: #fff; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                        <h2 style='margin-top: 0; color: #17a2b8; font-size: 18px;'>Payment Information</h2>
                        <p style='margin: 5px 0;'><strong>Amount Paid:</strong> $" . number_format($booking['total_price'], 2) . "</p>
                        <p style='margin: 5px 0;'><strong>Payment Date:</strong> {$paymentDate}</p>
                        <p style='margin: 5px 0;'><strong>Transaction ID:</strong> {$transactionId}</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "/my-bookings.php' style='background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>View My Booking</a>
                    </div>
                    
                    <div style='background-color: #e9f7ef; padding: 15px; border-radius: 4px; margin-top: 20px;'>
                        <h3 style='margin-top: 0; color: #28a745;'>What's Next?</h3>
                        <ul style='padding-left: 20px;'>
                            <li>Save this email for your records</li>
                            <li>Add all passenger details in your account</li>
                            <li>Check in online 24 hours before your flight</li>
                            <li>Arrive at the airport at least 2 hours before your flight</li>
                        </ul>
                    </div>
                    
                    <p style='margin-top: 30px;'>We look forward to providing you with an exceptional travel experience!</p>
                    
                    <p>Best regards,<br>The Flight Booking Team</p>
                </div>
                
                <div style='text-align: center; font-size: 12px; color: #777; margin-top: 20px;'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Get the HTML template for booking cancellation
     */
    private function getCancellationTemplate($booking, $flight) {
        // Format date and time
        $formattedDate = date('l, F j, Y', strtotime($flight['date']));
        $formattedTime = date('g:i A', strtotime($flight['time']));
        
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h1 style='color: #dc3545; margin-bottom: 20px;'>Your Booking Has Been Cancelled</h1>
                    <p style='font-size: 16px;'>Dear {$booking['customer_name']},</p>
                    <p style='font-size: 16px;'>We're writing to confirm that your flight booking has been <strong style='color: #dc3545;'>cancelled</strong> as requested.</p>
                    
                    <div style='background-color: #fff; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                        <h2 style='margin-top: 0; color: #dc3545; font-size: 18px;'>Cancelled Booking Details</h2>
                        <p style='margin: 5px 0;'><strong>Booking ID:</strong> #{$booking['id']}</p>
                        <p style='margin: 5px 0;'><strong>Flight:</strong> {$flight['flight_number']}</p>
                        <p style='margin: 5px 0;'><strong>Route:</strong> {$flight['departure']} to {$flight['arrival']}</p>
                        <p style='margin: 5px 0;'><strong>Date:</strong> {$formattedDate}</p>
                        <p style='margin: 5px 0;'><strong>Time:</strong> {$formattedTime}</p>
                        <p style='margin: 5px 0;'><strong>Passengers:</strong> {$booking['passengers']}</p>
                    </div>
                    
                    <div style='background-color: #f8d7da; padding: 15px; border-radius: 4px; margin-top: 20px;'>
                        <h3 style='margin-top: 0; color: #dc3545;'>Important Information</h3>
                        <p>If your booking was cancelled after payment was made, please note that any refund will be processed according to our refund policy. Refunds typically take 7-10 business days to appear on your statement.</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "/search2.php' style='background-color: #17a2b8; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>Book a New Flight</a>
                    </div>
                    
                    <p>If you didn't request this cancellation or have any questions, please contact our customer service team immediately.</p>
                    
                    <p style='margin-top: 30px;'>We hope to have the opportunity to serve you in the future.</p>
                    
                    <p>Best regards,<br>The Flight Booking Team</p>
                </div>
                
                <div style='text-align: center; font-size: 12px; color: #777; margin-top: 20px;'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
        ";
    }
}
?>