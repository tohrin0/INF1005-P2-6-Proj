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

    public function createBooking($userId, $flightId, $passengerDetails) {
        $this->userId = $userId;
        $this->flightId = $flightId;
        $this->status = 'confirmed';
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Get flight details
            $stmt = $this->db->prepare("SELECT price FROM flights WHERE id = ?");
            $stmt->execute([$flightId]);
            $flight = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$flight) {
                return false;
            }
            
            // Insert booking
            $stmt = $this->db->prepare(
                "INSERT INTO bookings (user_id, flight_id, status, customer_name, customer_email, customer_phone, total_price) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $userId, 
                $flightId, 
                $this->status, 
                $passengerDetails['name'],
                $passengerDetails['email'],
                $passengerDetails['phone'],
                $flight['price']
            ]);
            
            // Commit transaction
            if ($result) {
                $this->db->commit();
                return $this->db->lastInsertId();
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
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