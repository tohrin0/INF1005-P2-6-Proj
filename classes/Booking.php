<?php
class Booking {
    private $db;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }
    
    public function createBooking($userId, $flightId, $passengerDetails, $status = 'pending') {
        try {
            error_log("===== DEBUG: Starting createBooking =====");
            error_log("userId: " . $userId);
            error_log("flightId: " . $flightId);
            error_log("status: " . $status);
            error_log("passengerDetails: " . print_r($passengerDetails, true));
            
            // Use passenger count from the form; default to 1 if not set
            $passengerCount = isset($passengerDetails['passengers']) ? (int)$passengerDetails['passengers'] : 1;
            
            // Instead of fetching any price via the API, get the flight price _directly_ from the form
            $price = isset($passengerDetails['price']) ? floatval($passengerDetails['price']) : 0;
            $totalPrice = $price * $passengerCount;
            
            // Prepare the INSERT query using all required fields
            $query = "INSERT INTO bookings 
                (user_id, flight_id, status, customer_name, customer_email, customer_phone, passengers, total_price)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            error_log("SQL Query: " . $query);
            
            $params = [
                $userId,
                $flightId,
                $status,
                isset($passengerDetails['name']) ? $passengerDetails['name'] : '',
                isset($passengerDetails['email']) ? $passengerDetails['email'] : '',
                isset($passengerDetails['phone']) ? $passengerDetails['phone'] : '',
                $passengerCount,
                $totalPrice
            ];
            error_log("Query parameters: " . print_r($params, true));
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $newId = $this->db->lastInsertId();
                error_log("New booking ID: " . $newId);
                return $newId;
            }
            
            error_log("ERROR: Insert failed");
            error_log("PDO error info: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch (Exception $e) {
            error_log("EXCEPTION in createBooking: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
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
        $query = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $bookingId]);
    }
    
    public function getUserBookings($userId) {
        $query = "SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getAllBookings() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM bookings");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllBookings: " . $e->getMessage());
            return [];
        }
    }
}
?>