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
            "INSERT INTO flights (flight_number, flight_api, departure, arrival, date, time, price, duration) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $flightData['flight_number'],
            $flightData['flight_api'] ?? null, // Add API flight ID
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
                    "INSERT INTO flights (flight_number, flight_api, departure, arrival, date, time, price, duration, available_seats) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $flight['flight_number'],
                    $flight['id'] ?? null, // Add API flight ID
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
             flight_api = ?,
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
            $flightData['flight_api'] ?? null, // Add API flight ID
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
function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . str_pad($mins, 2, '0', STR_PAD_LEFT) . 'm';
}

/**
 * Check API key status and update database with usage information
 * 
 * @param int $keyIndex The index of the key in the FLIGHT_API_KEYS array
 * @param bool $isWorking Whether the key is currently working
 * @param string $errorMessage Error message if the key failed
 * @return bool Success or failure
 */
function updateApiKeyStatus($keyIndex, $isWorking, $errorMessage = '') {
    global $pdo;
    
    try {
        // Check if we have a table to track API key status
        $tableExists = $pdo->query("SHOW TABLES LIKE 'api_keys'")->rowCount() > 0;
        
        // Create table if it doesn't exist
        if (!$tableExists) {
            $pdo->exec("CREATE TABLE api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_index INT NOT NULL,
                api_key VARCHAR(255) NOT NULL,
                is_working BOOLEAN DEFAULT TRUE,
                last_error TEXT,
                last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                request_count INT DEFAULT 0
            )");
        }
        
        // Get the API key for this index
        $apiKeys = json_decode(FLIGHT_API_KEYS, true);
        $apiKey = $apiKeys[$keyIndex] ?? null;
        
        if (!$apiKey) {
            return false;
        }
        
        // Check if this key is already in the database
        $stmt = $pdo->prepare("SELECT id, request_count FROM api_keys WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $keyData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($keyData) {
            // Update existing key
            $stmt = $pdo->prepare("UPDATE api_keys SET 
                is_working = ?, 
                last_error = ?, 
                last_used = NOW(),
                request_count = request_count + 1
                WHERE id = ?");
            $stmt->execute([$isWorking ? 1 : 0, $errorMessage, $keyData['id']]);
        } else {
            // Insert new key
            $stmt = $pdo->prepare("INSERT INTO api_keys 
                (key_index, api_key, is_working, last_error, request_count) 
                VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$keyIndex, $apiKey, $isWorking ? 1 : 0, $errorMessage]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating API key status: " . $e->getMessage());
        return false;
    }
}

/* 
* Generate CSRF token for form protection
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/* 
 * Validate CSRF token from form submission
 * @param string $token Submitted token
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Validate password strength against policy requirements
 * 
 * @param string $password Password to validate
 * @return array [isValid: bool, message: string] Validation result with message
 */
function validatePasswordStrength($password) {
    // Check minimum length
    if (strlen($password) < 12) {
        return [false, "Password must be at least 12 characters long."];
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return [false, "Password must contain at least one uppercase letter."];
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return [false, "Password must contain at least one lowercase letter."];
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return [false, "Password must contain at least one number."];
    }
    
    // Check for at least one symbol/special character
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return [false, "Password must contain at least one special character (!@#$%^&*(),.?\":{}|<>)."];
    }
    
    // All checks passed
    return [true, "Password meets strength requirements."];
}

/**
 * Get the real client IP address
 * This handles proxies, load balancers, and local development
 * 
 * @return string The client's IP address
 */
function getClientIp() {
    // Check for CloudFlare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    
    // Check for proxy headers
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR can contain multiple IPs separated by commas
        // The first one is the original client IP
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    
    // For local testing, you could uncommment this line and set a test IP
    // return '123.45.67.89'; 
    
    // Fall back to REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>