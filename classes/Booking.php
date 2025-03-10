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
            
            // Calculate total price based on flight API data and passenger count
            $apiClient = new ApiClient();
            $flightDetails = $apiClient->getFlightById($flightId);
            
            error_log("flightDetails from API: " . print_r($flightDetails, true));
            
            if (!$flightDetails) {
                error_log("ERROR: Flight not found in API");
                throw new Exception("Flight not found");
            }
            
            $price = $flightDetails['price'];
            $passengerCount = $passengerDetails['passengers'];
            $totalPrice = $price * $passengerCount;
            
            error_log("price: " . $price);
            error_log("passengerCount: " . $passengerCount);
            error_log("totalPrice: " . $totalPrice);
            
            // Insert booking into database
            $query = "INSERT INTO bookings 
                    (user_id, flight_id, status, customer_name, customer_email, customer_phone, 
                    passengers, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            error_log("SQL Query: " . $query);
            error_log("Query params: " . print_r([
                $userId,
                $flightId,
                $status,
                $passengerDetails['name'],
                $passengerDetails['email'],
                $passengerDetails['phone'],
                $passengerCount,
                $totalPrice
            ], true));
            
            $stmt = $this->db->prepare($query);
            
            // Enable PDO error info
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $result = $stmt->execute([
                $userId,
                $flightId,
                $status,
                $passengerDetails['name'],
                $passengerDetails['email'],
                $passengerDetails['phone'],
                $passengerCount,
                $totalPrice
            ]);
            
            error_log("Execute result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                $newId = $this->db->lastInsertId();
                error_log("New booking ID: " . $newId);
                return $newId;
            }
            
            error_log("ERROR: Insert failed but no exception thrown");
            error_log("PDO error info: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch (Exception $e) {
            error_log("EXCEPTION in createBooking: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            throw $e;
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
}
?>