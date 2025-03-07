<?php
// Utility functions used throughout the application

// Add this at the top of functions.php to verify it's loading
// echo "Functions.php is loaded"; // Uncomment this for debugging

/**
 * Sanitize input data to prevent XSS attacks
 *
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Redirect to a specified URL
 *
 * @param string $url
 */
function redirectTo($url) {
    header("Location: $url");
    exit();
}

/**
 * Get the current user's ID
 *
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Flash a message to be displayed on the next page
 *
 * @param string $message
 */
function flashMessage($message) {
    $_SESSION['flash_message'] = $message;
}

/**
 * Get the flashed message
 *
 * @return string|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Validate email format
 *
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a random token for password reset
 *
 * @return string
 */
function generateToken() {
    return bin2hex(random_bytes(16));
}

/**
 * Get user by ID
 * 
 * @param int $id
 * @return array|false
 */
function getUserById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return false;
    }
}

// Remove duplicate isAdmin() function - it's already in auth.php
// /**
//  * Check if current user is an admin
//  */
// function isAdmin() {
//     if (!isLoggedIn()) {
//         return false;
//     }
//     
//     global $pdo;
//     $userId = $_SESSION['user_id'];
//     
//     $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
//     $stmt->execute([$userId]);
//     $user = $stmt->fetch(PDO::FETCH_ASSOC);
//     
//     return $user && $user['role'] === 'admin';
// }

/**
 * Get total users count
 */
function getTotalUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return $stmt->fetchColumn();
}

/**
 * Get total bookings count
 */
function getTotalBookings() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    return $stmt->fetchColumn();
}

/**
 * Get total flights count
 */
function getTotalFlights() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM flights");
    return $stmt->fetchColumn();
}

/**
 * Get all users
 */
function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users ORDER BY username");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all flights
 */
function getAllFlights() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM flights ORDER BY date, time");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Add a new flight
 */
function addFlight($flightData) {
    global $pdo;
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO flights (flight_number, departure, arrival, date, time, price, duration) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $flightData['flight_number'],
            $flightData['departure'],
            $flightData['arrival'],
            $flightData['date'],
            $flightData['time'],
            $flightData['price'],
            $flightData['duration'] ?? 0
        ]);
    } catch (PDOException $e) {
        error_log("Error adding flight: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a flight
 */
function deleteFlight($flightId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM flights WHERE id = ?");
        return $stmt->execute([$flightId]);
    } catch (PDOException $e) {
        error_log("Error deleting flight: " . $e->getMessage());
        return false;
    }
}

/**
 * Get booking details
 */
function getBookingDetails($bookingId, $userId) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date as flight_date
         FROM bookings b
         JOIN flights f ON b.flight_id = f.id
         WHERE b.id = ? AND b.user_id = ?"
    );
    $stmt->execute([$bookingId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetch real-time flight data from AviationStack API
 */
function fetchRealTimeFlights($limit = 10) {
    global $pdo;
    $apiClient = new ApiClient();
    $flights = $apiClient->getFlightSchedules();
    
    // Store the API results in the database for caching purposes
    storeApiFlights($flights);
    
    return $flights;
}

/**
 * Store flights fetched from API into database
 */
function storeApiFlights($flights) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($flights as $flight) {
            // Check if flight already exists
            $stmt = $pdo->prepare("SELECT id FROM flights WHERE flight_number = ? AND date = ?");
            $stmt->execute([$flight['flight_number'], $flight['date']]);
            $existingFlight = $stmt->fetch();
            
            if (!$existingFlight) {
                // Insert new flight
                $stmt = $pdo->prepare(
                    "INSERT INTO flights (flight_number, departure, arrival, date, time, price, duration, available_seats) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $flight['flight_number'],
                    $flight['departure'],
                    $flight['arrival'],
                    $flight['date'],
                    $flight['time'],
                    $flight['price'],
                    isset($flight['duration']) ? $flight['duration'] : 0,
                    isset($flight['available_seats']) ? $flight['available_seats'] : 100
                ]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error storing API flights: " . $e->getMessage());
        return false;
    }
}

/**
 * Search flights with more detailed parameters
 */
function searchFlights($departure, $arrival, $date) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM flights 
             WHERE departure LIKE ? 
             AND arrival LIKE ? 
             AND date = ? 
             AND available_seats > 0
             ORDER BY time"
        );
        $stmt->execute([
            "%$departure%", 
            "%$arrival%", 
            $date
        ]);
        $flights = $stmt->fetchAll();
        
        if (empty($flights)) {
            // If no flights in database, try fetching from API
            $apiClient = new ApiClient();
            $apiFlights = $apiClient->searchFlights($departure, $arrival, $date);
            
            if (!empty($apiFlights)) {
                storeApiFlights($apiFlights);
                return $apiFlights;
            }
        }
        
        return $flights;
    } catch (Exception $e) {
        error_log("Error searching flights: " . $e->getMessage());
        return [];
    }
}

/**
 * Get flight details by ID
 */
function getFlightById($flightId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
        $stmt->execute([$flightId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting flight: " . $e->getMessage());
        return false;
    }
}

/**
 * Update flight information
 */
function updateFlight($flightId, $flightData) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare(
            "UPDATE flights SET 
             flight_number = ?,
             departure = ?,
             arrival = ?,
             date = ?,
             time = ?,
             price = ?,
             duration = ?,
             available_seats = ? 
             WHERE id = ?"
        );
        
        return $stmt->execute([
            $flightData['flight_number'],
            $flightData['departure'],
            $flightData['arrival'],
            $flightData['date'],
            $flightData['time'],
            $flightData['price'],
            $flightData['duration'] ?? 0,
            $flightData['available_seats'] ?? 100,
            $flightId
        ]);
    } catch (Exception $e) {
        error_log("Error updating flight: " . $e->getMessage());
        return false;
    }
}

/**
 * Update available seats after booking
 */
function updateFlightSeats($flightId, $bookedSeats = 1) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare(
            "UPDATE flights 
             SET available_seats = available_seats - ? 
             WHERE id = ? AND available_seats >= ?"
        );
        return $stmt->execute([$bookedSeats, $flightId, $bookedSeats]);
    } catch (Exception $e) {
        error_log("Error updating flight seats: " . $e->getMessage());
        return false;
    }
}

/**
 * Format flight duration from minutes to hours and minutes
 *
 * @param int $durationMinutes Duration in minutes
 * @return string Formatted duration string (e.g. "2h 30m")
 */
function formatDuration($durationMinutes) {
    if (!is_numeric($durationMinutes)) {
        return "N/A";
    }
    
    $hours = floor($durationMinutes / 60);
    $minutes = $durationMinutes % 60;
    
    if ($hours > 0 && $minutes > 0) {
        return $hours . "h " . $minutes . "m";
    } elseif ($hours > 0) {
        return $hours . "h";
    } else {
        return $minutes . "m";
    }
}
?>