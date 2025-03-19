<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';
require_once 'classes/Booking.php';
require_once 'classes/Flight.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if there's a direct POST from search.php or index.php
if (isset($_POST['select_flight']) && !empty($_POST['flight_id'])) {
    $_SESSION['selected_flight_id'] = $_POST['flight_id'];
    $_SESSION['selected_flight_price'] = $_POST['price'];
    $flightId = $_POST['flight_id'];
    $flightPrice = $_POST['price'];
} 
// If no direct POST, check if we have stored flight data in session
else if (isset($_SESSION['selected_flight_id'])) {
    $flightId = $_SESSION['selected_flight_id'];
    $flightPrice = $_SESSION['selected_flight_price'];
} 
// No flight selected, redirect to search
else {
    header('Location: search.php');
    exit;
}

// Process form submission for booking
if (isset($_POST['submit_booking'])) {
    error_log("===== DEBUG: Passenger form submitted =====");
    
    // Validate form data
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        $error = "Please fill in all required fields.";
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $passengerDetails = [
            'name' => htmlspecialchars(trim($_POST['name'])),
            'email' => htmlspecialchars(trim($_POST['email'])),
            'phone' => htmlspecialchars(trim($_POST['phone'])),
            'passengers' => (int)$_POST['passengers'],
            'price' => (float)$flightPrice,
            'flight_api' => $flightId  // Store the API flight ID here
        ];
        
        try {
            // First, store flight details in the local DB if not already present
            $apiClient = new ApiClient();
            $flightData = $apiClient->getFlightById($flightId);
            
            if ($flightData) {
                // Create a Flight object with the API data
                $flight = new Flight(
                    $flightData['flight_number'],
                    $flightData['departure'],
                    $flightData['arrival'],
                    $flightData['duration'] ?? 0,
                    $flightData['price']
                );
                
                // Set additional flight properties from API data
                $flight->setFromArray([
                    'date' => $flightData['date'],
                    'time' => $flightData['time'],
                    'available_seats' => $flightData['available_seats'] ?? 100,
                    'airline' => $flightData['airline'] ?? '',
                    'status' => $flightData['status'] ?? 'scheduled',
                    'departure_terminal' => $flightData['departure_terminal'] ?? null,
                    'departure_gate' => $flightData['departure_gate'] ?? null,
                    'arrival_terminal' => $flightData['arrival_terminal'] ?? null,
                    'arrival_gate' => $flightData['arrival_gate'] ?? null,
                    'flight_api' => $flightId // Add the API flight ID here as well
                ]);
                
                // Save flight to database (handles both insert and update)
                $localFlightId = $flight->save();
                
                if ($localFlightId) {
                    // Update available seats
                    $flight->updateSeats($passengerDetails['passengers']);
                    
                    // Create the booking using the local database flight ID
                    $bookingObj = new Booking();
                    $newBookingId = $bookingObj->createBooking($userId, $localFlightId, $passengerDetails);
                    
                    if ($newBookingId) {
                        // Success! Store booking data in session and redirect
                        $_SESSION['booking_id'] = $newBookingId;
                        $_SESSION['booking_data'] = [
                            'flight_id' => $localFlightId,
                            'flight_api' => $flightId, // Include flight_api in session data
                            'total_price' => $passengerDetails['passengers'] * $flightPrice,
                            'customer_name' => $passengerDetails['name'],
                            'flight_number' => $flightData['flight_number'],
                            'departure' => $flightData['departure'],
                            'arrival' => $flightData['arrival'],
                            'date' => $flightData['date'],
                            'time' => $flightData['time']
                        ];
                        
                        // Redirect to confirmation page
                        header("Location: confirmation.php?booking_id=" . $newBookingId);
                        exit;
                    } else {
                        $error = "Failed to create booking. Please try again.";
                    }
                } else {
                    $error = "Failed to save flight data. Please try again.";
                }
            } else {
                $error = "Could not retrieve flight details. Please try again.";
            }
        } catch (Exception $e) {
            $error = "An error occurred while processing your booking: " . $e->getMessage();
            error_log("Booking error: " . $e->getMessage());
        }
    }
}

// Get flight details for display
try {
    $apiClient = new ApiClient();
    $flight = $apiClient->getFlightById($flightId);
    
    if (!$flight) {
        $error = "Selected flight not found. Please try again.";
        header('Location: search.php');
        exit;
    }
    
    // Check if we have real-time flight data from aviation stack API
    if (isset($flight['flight_number']) && !empty($flight['flight_number'])) {
        try {
            $params = [
                'flight_iata' => $flight['flight_number'],
                'flight_date' => $flight['date']
            ];
            
            $realTimeFlight = $apiClient->getFlightStatus($params);
            
            if (!empty($realTimeFlight)) {
                $rtf = $realTimeFlight[0]; // Get first match
                
                // Enhance flight data with real-time information
                $flight['status'] = $rtf['flight_status'] ?? 'scheduled';
                $flight['departure_terminal'] = $rtf['departure']['terminal'] ?? null;
                $flight['departure_gate'] = $rtf['departure']['gate'] ?? null;
                $flight['arrival_terminal'] = $rtf['arrival']['terminal'] ?? null;
                $flight['arrival_gate'] = $rtf['arrival']['gate'] ?? null;
                $flight['airline'] = $rtf['airline']['name'] ?? $flight['airline'] ?? null;
            }
        } catch (Exception $e) {
            // Just log the error but continue
            error_log("Error fetching real-time flight data: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    $error = "Error retrieving flight information: " . $e->getMessage();
    error_log("Error retrieving flight: " . $e->getMessage());
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Passenger Details</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="booking-summary">
        <h2>Flight Summary</h2>
        <div class="flight-info">
            <div class="flight-details">
                <div><strong>Flight Number:</strong> <?php echo htmlspecialchars($flight['flight_number']); ?></div>
                <div><strong>Departure:</strong> <?php echo htmlspecialchars($flight['departure']); ?></div>
                <div><strong>Arrival:</strong> <?php echo htmlspecialchars($flight['arrival']); ?></div>
                <div><strong>Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($flight['date']))); ?></div>
                <div><strong>Time:</strong> <?php echo htmlspecialchars($flight['time']); ?></div>
                <?php if (!empty($flight['airline'])): ?>
                <div><strong>Airline:</strong> <?php echo htmlspecialchars($flight['airline']); ?></div>
                <?php endif; ?>
                <?php if (!empty($flight['status']) && $flight['status'] != 'scheduled'): ?>
                <div><strong>Status:</strong> <span class="flight-status <?php echo strtolower($flight['status']); ?>"><?php echo ucfirst($flight['status']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($flight['departure_terminal']) || !empty($flight['departure_gate'])): ?>
                <div><strong>Departure Details:</strong> 
                    <?php 
                    $details = [];
                    if (!empty($flight['departure_terminal'])) $details[] = "Terminal " . htmlspecialchars($flight['departure_terminal']);
                    if (!empty($flight['departure_gate'])) $details[] = "Gate " . htmlspecialchars($flight['departure_gate']);
                    echo implode(', ', $details);
                    ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($flight['arrival_terminal']) || !empty($flight['arrival_gate'])): ?>
                <div><strong>Arrival Details:</strong> 
                    <?php 
                    $details = [];
                    if (!empty($flight['arrival_terminal'])) $details[] = "Terminal " . htmlspecialchars($flight['arrival_terminal']);
                    if (!empty($flight['arrival_gate'])) $details[] = "Gate " . htmlspecialchars($flight['arrival_gate']);
                    echo implode(', ', $details);
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="price-info">
                <div class="unit-price">
                    <strong>Price per passenger:</strong> $<?php echo htmlspecialchars(number_format($flightPrice, 2)); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="passenger-form-container">
        <h2>Enter Passenger Information</h2>
        <form id="passenger-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            
            <div class="form-group">
                <label for="passengers">Number of Passengers:</label>
                <select id="passengers" name="passengers" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="total-price">Total Price:</label>
                <div id="total-price-display" class="total-price">$<?php echo htmlspecialchars(number_format($flightPrice, 2)); ?></div>
                <input type="hidden" id="total_price" name="total_price" value="<?php echo htmlspecialchars($flightPrice); ?>">
            </div>
            
            <button type="submit" name="submit_booking" class="btn-primary">Continue</button>
        </form>
    </div>
</div>

<style>
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    h1 {
        color: #333;
        margin-bottom: 30px;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .booking-summary {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .flight-info {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .flight-details {
        flex: 1;
        min-width: 250px;
    }
    
    .flight-details div {
        margin-bottom: 10px;
    }
    
    .flight-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.9em;
    }
    
    .flight-status.active {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .flight-status.scheduled {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }
    
    .flight-status.landed {
        background-color: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }
    
    .flight-status.cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .price-info {
        text-align: right;
        padding-top: 20px;
    }
    
    .unit-price {
        font-size: 1.2em;
        margin-bottom: 10px;
    }
    
    .total-price {
        font-size: 1.5em;
        color: #28a745;
        font-weight: bold;
    }
    
    .passenger-form-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
    }
    
    @media (max-width: 768px) {
        .flight-info {
            flex-direction: column;
        }
        
        .price-info {
            text-align: left;
            margin-top: 20px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passengersInput = document.getElementById('passengers');
    const priceElement = document.getElementById('total-price-display');
    const priceInput = document.getElementById('total_price');
    const basePrice = <?php echo json_encode($flightPrice); ?>;
    
    // Update total price when passenger count changes
    if (passengersInput) {
        passengersInput.addEventListener('change', function() {
            const passengers = parseInt(this.value) || 1;
            const totalPrice = (parseFloat(basePrice) * passengers).toFixed(2);
            
            if (priceElement) {
                priceElement.textContent = '$' + totalPrice;
            }
            
            if (priceInput) {
                priceInput.value = totalPrice;
            }
        });
    }
});
</script>

<?php include 'templates/footer.php'; ?>