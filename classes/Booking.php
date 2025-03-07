<?php
class Booking {
    private $db;
    private $userId;
    private $flightId;
    private $bookingDate;
    private $status;

    public function __construct($db = null) {
        global $pdo;
        $this->db = $db ?? $pdo;
    }

    public function createBooking($userId, $flightId, $passengerDetails, $status = 'confirmed') {
        // Add debug logging
        error_log("Creating booking - userId: $userId, flightId: $flightId, status: $status");
        error_log("Passenger details: " . json_encode($passengerDetails));
        
        $this->userId = $userId;
        $this->flightId = $flightId;
        $this->status = $status;
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Get flight details
            $stmt = $this->db->prepare("SELECT price, available_seats FROM flights WHERE id = ?");
            $stmt->execute([$flightId]);
            $flight = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$flight) {
                error_log("Flight not found with ID: $flightId");
                throw new Exception("Flight not found");
            }
            
            error_log("Flight found: " . json_encode($flight));
            
            // Check if enough seats are available
            $numPassengers = $passengerDetails['passengers'] ?? 1;
            
            if ($flight['available_seats'] < $numPassengers) {
                error_log("Not enough seats: available={$flight['available_seats']}, requested=$numPassengers");
                throw new Exception("Not enough seats available");
            }
            
            // Calculate total price
            $totalPrice = $flight['price'] * $numPassengers;
            
            // Insert booking
            $stmt = $this->db->prepare(
                "INSERT INTO bookings (user_id, flight_id, status, customer_name, customer_email, customer_phone, passengers, total_price) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $userId, 
                $flightId, 
                $this->status, 
                $passengerDetails['name'],
                $passengerDetails['email'],
                $passengerDetails['phone'],
                $numPassengers,
                $totalPrice
            ]);
            
            if (!$result) {
                error_log("Failed to insert booking");
                throw new Exception("Failed to insert booking");
            }
            
            $bookingId = $this->db->lastInsertId();
            error_log("Booking created with ID: $bookingId");
            
            // Update available seats
            $stmt = $this->db->prepare(
                "UPDATE flights 
                 SET available_seats = available_seats - ? 
                 WHERE id = ? AND available_seats >= ?"
            );
            
            $result = $stmt->execute([$numPassengers, $flightId, $numPassengers]);
            
            if (!$result) {
                error_log("Failed to update available seats");
                throw new Exception("Failed to update available seats");
            }
            
            // Commit transaction
            $this->db->commit();
            return $bookingId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Booking error: " . $e->getMessage());
            return false;
        }
    }

    public function getBooking($bookingId) {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cancelBooking($bookingId) {
        $stmt = $this->db->prepare("UPDATE bookings SET status = 'canceled' WHERE id = ?");
        return $stmt->execute([$bookingId]);
    }

    public function getUserBookings($userId) {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllBookings() {
        global $pdo;
        $stmt = $pdo->query("SELECT b.*, u.username, f.flight_number 
                            FROM bookings b
                            JOIN users u ON b.user_id = u.id
                            JOIN flights f ON b.flight_id = f.id
                            ORDER BY b.booking_date DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>