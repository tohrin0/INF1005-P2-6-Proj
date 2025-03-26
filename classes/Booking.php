<?php
class Booking {
    private $db;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }
    
    // Add this static method to handle bookings by status
    public static function getBookingsByStatus($status) {
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date, u.username 
             FROM bookings b 
             LEFT JOIN flights f ON b.flight_id = f.id 
             LEFT JOIN users u ON b.user_id = u.id
             WHERE b.status = ? 
             ORDER BY b.booking_date DESC"
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createBooking($userId, $flightId, $passengerDetails, $status = 'pending') {
        try {
            error_log("===== DEBUG: Starting createBooking =====");
            
            // Get values needed for the booking
            $passengerCount = isset($passengerDetails['passengers']) ? (int)$passengerDetails['passengers'] : 1;
            $price = isset($passengerDetails['price']) ? floatval($passengerDetails['price']) : 0;
            $totalPrice = $price * $passengerCount;
            $miles = $totalPrice / 10;
            $flightApiId = isset($passengerDetails['flight_api']) ? $passengerDetails['flight_api'] : null;
            
            // Prepare the INSERT query using all required fields
            // Note: Using "NONE" instead of empty string for return_flight_id to satisfy NOT NULL constraint
            $query = "INSERT INTO bookings 
                (user_id, flight_id, return_flight_id, flight_api, status, customer_name, customer_email, 
                customer_phone, passengers, total_price, miles)
                VALUES (?, ?, 'NONE', ?, ?, ?, ?, ?, ?, ?, ?)";
            
            error_log("SQL Query: " . $query);
            
            $params = [
                $userId,
                $flightId,  // This should already be a string from Flight::save()
                $flightApiId,
                $status,
                isset($passengerDetails['name']) ? $passengerDetails['name'] : '',
                isset($passengerDetails['email']) ? $passengerDetails['email'] : '',
                isset($passengerDetails['phone']) ? $passengerDetails['phone'] : '',
                $passengerCount,
                $totalPrice,
                $miles
            ];
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $bookingId = $this->db->lastInsertId();
                
                // Send booking pending payment email
                $this->sendBookingStatusEmail($bookingId, 'pending');
                
                return $bookingId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating booking: " . $e->getMessage());
            return false;
        }
    }
    
    public function getBookingById($bookingId, $userId = null) {
        $query = "SELECT * FROM bookings WHERE id = ?";
        $params = [$bookingId];
        
        if ($userId) {
            $query .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateBookingStatus($bookingId, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $bookingId]);
            
            if ($result) {
                // Send email notification about status change
                $this->sendBookingStatusEmail($bookingId, $status);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error updating booking status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update booking information
     * 
     * @param int $bookingId The booking ID
     * @param array $bookingData The booking data to update
     * @return bool Success or failure
     */
    public function updateBooking($bookingId, $bookingData) {
        try {
            $fields = [];
            $values = [];
            $statusChanged = false;
            $newStatus = null;
            
            // Dynamically build the query based on provided fields
            foreach ($bookingData as $field => $value) {
                $fields[] = "$field = ?";
                $values[] = $value;
                
                // Check if status is being updated
                if ($field === 'status') {
                    $statusChanged = true;
                    $newStatus = $value;
                }
            }
            
            // Add updated_at timestamp
            $fields[] = "updated_at = NOW()";
            
            // Add booking ID to values array
            $values[] = $bookingId;
            
            $query = "UPDATE bookings SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            $result = $stmt->execute($values);
            
            // If status changed and update was successful, send email notification
            if ($statusChanged && $result && $newStatus) {
                $this->sendBookingStatusEmail($bookingId, $newStatus);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error updating booking: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email notification based on booking status
     * 
     * @param int $bookingId The booking ID
     * @param string $status The current status
     * @return bool Success or failure
     */
    private function sendBookingStatusEmail($bookingId, $status) {
        try {
            // Get booking details with flight information using aliased columns to prevent conflicts
            $stmt = $this->db->prepare(
                "SELECT b.id AS booking_id, b.user_id, b.flight_id, b.return_flight_id, b.flight_api AS booking_flight_api,
                        b.status, b.customer_name, b.customer_email, b.customer_phone, b.passengers, b.total_price, b.miles,
                        b.booking_date, b.created_at, b.updated_at,
                        f.id AS flight_id, f.flight_number, f.flight_api, f.departure, f.arrival, f.date, f.time,
                        f.duration, f.price, f.available_seats, f.airline, f.departure_gate, f.arrival_gate,
                        f.departure_terminal, f.arrival_terminal, f.status AS flight_status
                 FROM bookings b 
                 JOIN flights f ON b.flight_id = f.id 
                 WHERE b.id = ?"
            );
            $stmt->execute([$bookingId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("Unable to send status email: Booking not found");
                return false;
            }
            
            // Verify we have a valid email
            if (empty($result['customer_email']) || !filter_var($result['customer_email'], FILTER_VALIDATE_EMAIL)) {
                error_log("Unable to send status email: Invalid email address");
                return false;
            }
            
            // Create separate arrays for booking and flight data
            $booking = [
                'id' => $result['booking_id'],
                'status' => $result['status'],
                'customer_name' => $result['customer_name'],
                'customer_email' => $result['customer_email'],
                'customer_phone' => $result['customer_phone'],
                'passengers' => $result['passengers'],
                'total_price' => $result['total_price'],
                'miles' => $result['miles'],
                'booking_date' => $result['booking_date']
            ];
            
            $flight = [
                'id' => $result['flight_id'],
                'flight_number' => $result['flight_number'],
                'flight_api' => $result['flight_api'],
                'departure' => $result['departure'],
                'arrival' => $result['arrival'],
                'date' => $result['date'],
                'time' => $result['time'],
                'duration' => $result['duration'],
                'price' => $result['price'],
                'airline' => $result['airline'],
                'status' => $result['flight_status']
            ];
            
            // Create EmailNotification instance
            require_once __DIR__ . '/../vendor/autoload.php';
            require_once __DIR__ . '/EmailNotification.php';
            $emailNotification = new EmailNotification();
            
            // Send appropriate email based on status
            switch ($status) {
                case 'pending':
                    return $emailNotification->sendPendingPaymentEmail($booking, $flight);
                    
                case 'confirmed':
                    // Get payment information if available
                    $paymentStmt = $this->db->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC LIMIT 1");
                    $paymentStmt->execute([$bookingId]);
                    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
                    
                    return $emailNotification->sendConfirmationEmail($booking, $flight, $payment);
                    
                case 'canceled':
                    return $emailNotification->sendCancellationEmail($booking, $flight);
                    
                default:
                    // No email for other statuses
                    return true;
            }
        } catch (Exception $e) {
            error_log("Error sending status email: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserBookings($userId) {
        $query = "SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getAllBookings() {
        global $pdo;
        $stmt = $pdo->query(
            "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date, u.username 
             FROM bookings b 
             LEFT JOIN flights f ON b.flight_id = f.id 
             LEFT JOIN users u ON b.user_id = u.id
             ORDER BY b.booking_date DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>