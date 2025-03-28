<?php
class BookingManager
{
    private $db;

    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Get all bookings for a user with flight details, categorized by status
     * 
     * @param int $userId The user ID
     * @return array Array of bookings categorized by status
     */
    public function getUserBookings($userId)
    {
        try {
            $sql = "SELECT DISTINCT b.*, f.flight_number, f.departure, f.arrival, f.date, f.time, f.airline,
                    (SELECT COUNT(*) FROM passengers p WHERE p.booking_id = b.id) AS registered_passengers
                    FROM bookings b 
                    JOIN flights f ON b.flight_id = f.id 
                    WHERE b.user_id = ?
                    ORDER BY b.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // The DISTINCT in your SQL query already handles duplicates,
            // so we can remove the PHP-based duplicate removal code

            $currentDate = date('Y-m-d');
            $categorizedBookings = [
                'pending'   => [],
                'confirmed' => [],
                'past'      => []
            ];

            foreach ($allBookings as $booking) {
                // Simple categorization based on date and status
                if (strtotime($booking['date']) < strtotime($currentDate)) {
                    $categorizedBookings['past'][] = $booking;
                } else if ($booking['status'] === 'pending') {
                    $categorizedBookings['pending'][] = $booking;
                } else if ($booking['status'] === 'confirmed') {
                    $categorizedBookings['confirmed'][] = $booking;
                } else {
                    $categorizedBookings['past'][] = $booking;
                }
            }

            return $categorizedBookings;
        } catch (Exception $e) {
            error_log("Error getting user bookings: " . $e->getMessage());
            return [
                'pending'   => [],
                'confirmed' => [],
                'past'      => []
            ];
        }
    }

    /**
     * Check if a booking has payments
     * 
     * @param int $bookingId The booking ID
     * @return bool True if payment exists, false otherwise
     */
    public function hasPayment($bookingId)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'completed'");
            $stmt->execute([$bookingId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment information for a booking
     * 
     * @param int $bookingId The booking ID
     * @return array|null Payment details or null if not found
     */
    public function getBookingPayment($bookingId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC LIMIT 1");
            $stmt->execute([$bookingId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check how many passengers are remaining to be added for a booking
     * 
     * @param int $bookingId The booking ID
     * @param int $totalPassengers The total number of passengers in the booking
     * @return int Number of remaining passengers to be added
     */
    public function getRemainingPassengersCount($bookingId, $totalPassengers)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM passengers WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            $registeredPassengers = (int)$stmt->fetchColumn();

            return max(0, $totalPassengers - $registeredPassengers);
        } catch (Exception $e) {
            error_log("Error counting remaining passengers: " . $e->getMessage());
            return 0;
        }
    }
}
