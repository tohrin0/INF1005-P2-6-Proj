<?php

class Passenger {
    private $db;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

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
}