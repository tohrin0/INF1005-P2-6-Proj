<?php
class Passenger {
    private $db;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Add a new passenger to a booking
     * 
     * @param int $bookingId The booking ID
     * @param array $passengerData The passenger details
     * @return bool True on success, false on failure
     */
    public function addPassenger($bookingId, $passengerData) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO passengers 
                (booking_id, title, first_name, last_name, date_of_birth, 
                nationality, passport_number, passport_expiry, special_requirements) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            return $stmt->execute([
                $bookingId,
                $passengerData['title'],
                $passengerData['first_name'],
                $passengerData['last_name'],
                $passengerData['date_of_birth'],
                $passengerData['nationality'],
                $passengerData['passport_number'],
                $passengerData['passport_expiry'],
                $passengerData['special_requirements'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error adding passenger: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all passengers for a specific booking
     * 
     * @param int $bookingId The booking ID
     * @return array Array of passengers
     */
    public function getPassengersByBooking($bookingId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM passengers WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting passengers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update passenger information
     * 
     * @param int $passengerId The passenger ID
     * @param array $passengerData The updated passenger details
     * @return bool True on success, false on failure
     */
    public function updatePassenger($passengerId, $passengerData) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE passengers SET 
                title = ?, first_name = ?, last_name = ?, date_of_birth = ?,
                nationality = ?, passport_number = ?, passport_expiry = ?, 
                special_requirements = ?
                WHERE id = ?"
            );
            
            return $stmt->execute([
                $passengerData['title'],
                $passengerData['first_name'],
                $passengerData['last_name'],
                $passengerData['date_of_birth'],
                $passengerData['nationality'],
                $passengerData['passport_number'],
                $passengerData['passport_expiry'],
                $passengerData['special_requirements'] ?? null,
                $passengerId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating passenger: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a passenger
     * 
     * @param int $passengerId The passenger ID
     * @return bool True on success, false on failure
     */
    public function deletePassenger($passengerId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM passengers WHERE id = ?");
            return $stmt->execute([$passengerId]);
        } catch (PDOException $e) {
            error_log("Error deleting passenger: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get passenger by ID
     * 
     * @param int $passengerId The passenger ID
     * @return array|null Passenger data or null if not found
     */
    public function getPassengerById($passengerId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM passengers WHERE id = ?");
            $stmt->execute([$passengerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting passenger: " . $e->getMessage());
            return null;
        }
    }
}
?>