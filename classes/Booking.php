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
            $flightApiId = isset($passengerDetails['flight_api']) ? $passengerDetails['flight_api'] : null;
            
            // Prepare the INSERT query using all required fields
            // Note: Using "NONE" instead of empty string for return_flight_id to satisfy NOT NULL constraint
            $query = "INSERT INTO bookings 
                (user_id, flight_id, return_flight_id, flight_api, status, customer_name, customer_email, 
                customer_phone, passengers, total_price)
                VALUES (?, ?, 'NONE', ?, ?, ?, ?, ?, ?, ?)";
            
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
                $totalPrice
            ];
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                return $this->db->lastInsertId();
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
            return $stmt->execute([$status, $bookingId]);
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
            
            // Dynamically build the query based on provided fields
            foreach ($bookingData as $field => $value) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }
            
            // Add updated_at timestamp
            $fields[] = "updated_at = NOW()";
            
            // Add booking ID to values array
            $values[] = $bookingId;
            
            $query = "UPDATE bookings SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error updating booking: " . $e->getMessage());
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