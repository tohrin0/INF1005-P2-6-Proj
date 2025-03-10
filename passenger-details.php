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

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect if flight not selected
if (!isset($_SESSION['selected_flight_id'])) {
    header('Location: flight-selection.php');
    exit;
}

$userId = $_SESSION['user_id'];
$flightId = $_SESSION['selected_flight_id'];
$flightPrice = $_SESSION['selected_flight_price'];
$error = '';
$success = '';

// Process form submission
if (isset($_POST['submit_booking'])) {
    error_log("===== DEBUG: Passenger form submitted =====");
    error_log("POST data: " . print_r($_POST, true));
    error_log("User ID from session: " . $userId);
    error_log("Flight ID from session: " . $flightId);
    error_log("Flight Price from session: " . $flightPrice);
    
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        $error = "Please fill in all required fields.";
        error_log("Validation error: Missing required fields");
    } else {
        $passengerDetails = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'passengers' => $_POST['passengers'] ?? 1,
            'price' => $flightPrice
        ];
        
        // Create the booking
        $bookingObj = new Booking();
        $newBookingId = $bookingObj->createBooking($userId, $flightId, $passengerDetails);
        
        if ($newBookingId) {
            $success = "Booking created successfully!";
            error_log("Booking created successfully. ID: " . $newBookingId);
            
            // Clear session variables for flight selection
            unset($_SESSION['selected_flight_id']);
            unset($_SESSION['selected_flight_price']);
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $newBookingId);
            exit;
        } else {
            $error = "There was a problem creating your booking. Please try again.";
            error_log("Booking creation failed");
        }
    }
}

// Get flight details for display
try {
    $apiClient = new ApiClient();
    $flight = $apiClient->getFlightById($flightId);
    if (!$flight) {
        $error = "Selected flight not found. Please try again.";
        header('Location: flight-selection.php');
        exit;
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
                <div><strong>Flight:</strong> <?php echo htmlspecialchars($flight['flight_number']); ?></div>
                <div><strong>From:</strong> <?php echo htmlspecialchars($flight['departure']); ?></div>
                <div><strong>To:</strong> <?php echo htmlspecialchars($flight['arrival']); ?></div>
                <div><strong>Date:</strong> <?php echo htmlspecialchars($flight['date']); ?></div>
                <div><strong>Time:</strong> <?php echo htmlspecialchars($flight['time']); ?></div>
                <div><strong>Price:</strong> $<?php echo htmlspecialchars($flight['price']); ?></div>
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
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            
            <div class="form-group">
                <label for="passengers">Number of Passengers:</label>
                <input type="number" id="passengers" name="passengers" min="1" max="10" value="1" required>
            </div>
            
            <div class="form-group">
                <label for="total-price">Total Price:</label>
                <div id="total-price-display" style="font-size: 1.2em; font-weight: bold; color: #4CAF50;">$<?php echo htmlspecialchars($flightPrice); ?></div>
                <input type="hidden" id="total_price" name="total_price" value="<?php echo htmlspecialchars($flightPrice); ?>">
            </div>
            
            <button type="submit" name="submit_booking" class="btn-primary">Complete Booking</button>
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