<?php
// Add this at the very beginning to catch any early errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';
require_once 'classes/Booking.php';

// Debug database connection
try {
    if (!isset($pdo)) {
        error_log("ERROR: Database connection not established");
    } else {
        $testStmt = $pdo->query("SELECT 1");
        $testStmt->execute();
        error_log("Database connection test: Success");
        
        // Test database write permission
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        error_log("Current bookings count: " . $count);
    }
} catch (Exception $e) {
    error_log("Database connection/query error: " . $e->getMessage());
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$flights = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("===== DEBUG: Form submitted =====");
    error_log("POST data: " . print_r($_POST, true));
    error_log("User ID from session: " . $userId);
    
    // Server-side validation
    if (empty($_POST['flight_id']) || empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        $error = "Please fill in all required fields";
        error_log("Validation error: " . $error);
    } else {
        try {
            // Create booking object
            $booking = new Booking();
            
            // Gather passenger details
            $passengerDetails = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'passengers' => intval($_POST['passengers'] ?? 1)
            ];
            
            error_log("Passenger details: " . print_r($passengerDetails, true));
            
            $flightId = $_POST['flight_id'];
            $status = $_POST['status'] ?? 'pending';
            
            error_log("Flight ID: " . $flightId);
            error_log("Status: " . $status);
            error_log("Total price: " . $_POST['total_price']);
            
            // Create booking
            $bookingId = $booking->createBooking(
                $userId,
                $flightId,
                $passengerDetails,
                $status
            );
            
            error_log("Booking creation result: " . ($bookingId ? "Success (ID: $bookingId)" : "Failed"));
            
            if ($bookingId) {
                // Store booking info in session for payment page
                $_SESSION['booking_id'] = $bookingId;
                $_SESSION['booking_data'] = [
                    'flight_id' => $flightId,
                    'total_price' => $_POST['total_price'] ?? 0
                ];
                
                // Redirect to payment page
                header("Location: payment.php");
                exit;
            } else {
                $error = "Failed to create booking. Please try again.";
                error_log("Booking failed with error: " . $error);
            }
        } catch (Exception $e) {
            $error = "Booking error: " . $e->getMessage();
            error_log("Exception in booking process: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
        }
    }
}

// Retrieve flights data directly from API
try {
    $apiClient = new ApiClient();
    $flights = $apiClient->getAvailableFlights();
    
    if (empty($flights)) {
        $error = "No flights available at this time.";
    }
} catch (Exception $e) {
    $error = "Error loading flights from API: " . $e->getMessage();
    error_log("Error loading flights from API: " . $e->getMessage());
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Book Your Flight</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($flights)): ?>
    <div class="booking-form-container">
        <h2>Flight Booking</h2>
        <form id="booking-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="flight_id">Select Flight:</label>
                <select id="flight_id" name="flight_id" required>
                    <option value="">-- Select a flight --</option>
                    <?php foreach ($flights as $flight): ?>
                        <option value="<?php echo htmlspecialchars($flight['id']); ?>" 
                                data-price="<?php echo htmlspecialchars($flight['price']); ?>">
                            <?php echo htmlspecialchars($flight['flight_number']); ?> - 
                            <?php echo htmlspecialchars($flight['departure']); ?> to 
                            <?php echo htmlspecialchars($flight['arrival']); ?> - 
                            $<?php echo htmlspecialchars($flight['price']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="passengers">Number of Passengers:</label>
                <input type="number" id="passengers" name="passengers" min="1" max="10" value="1" required>
            </div>
            
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
                <label for="total-price">Total Price:</label>
                <div id="total-price-display" style="font-size: 1.2em; font-weight: bold; color: #4CAF50;">$0.00</div>
                <input type="hidden" id="total_price" name="total_price" value="0">
            </div>
            
            <!-- Hidden field for status -->
            <input type="hidden" id="status" name="status" value="pending">
            
            <button type="submit" class="btn-primary">Proceed to Payment</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
    .container {
        max-width: 1200px;
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
    
    .booking-form-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 30px;
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
    .form-group select,
    .form-group textarea {
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
    
    h2, h3 {
        margin-bottom: 20px;
        color: #333;
    }
    
    h3 {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    
    @media (max-width: 768px) {
        .container {
            padding: 10px;
        }
        
        .booking-form-container {
            padding: 15px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Document loaded");
        
        // Debug session user information
        console.log("Session user ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?>");
        console.log("Session username: <?php echo $_SESSION['username'] ?? 'Not set'; ?>");
        
        // Price calculation functionality
        const flightSelect = document.getElementById('flight_id');
        const passengers = document.getElementById('passengers');
        const totalPriceDisplay = document.getElementById('total-price-display');
        const totalPriceInput = document.getElementById('total_price');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const statusInput = document.getElementById('status');
        
        // Calculate total price when flight or passengers change
        function calculateTotal() {
            console.log("Calculating total");
            if (flightSelect && flightSelect.value) {
                const selectedOption = flightSelect.options[flightSelect.selectedIndex];
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const numPassengers = parseInt(passengers.value) || 1;
                
                const totalPrice = price * numPassengers;
                totalPriceDisplay.textContent = '$' + totalPrice.toFixed(2);
                totalPriceInput.value = totalPrice.toFixed(2);
                console.log("Updated price: " + totalPrice);
            } else {
                totalPriceDisplay.textContent = '$0.00';
                totalPriceInput.value = '0';
            }
        }
        
        // Log elements to make sure they're found
        console.log("Flight select:", flightSelect);
        console.log("Passengers:", passengers);
        console.log("Name field:", nameInput);
        console.log("Email field:", emailInput);
        console.log("Phone field:", phoneInput);
        console.log("Status field:", statusInput);
        
        if (flightSelect && passengers) {
            flightSelect.addEventListener('change', calculateTotal);
            passengers.addEventListener('input', calculateTotal);
            passengers.addEventListener('change', calculateTotal);
            
            // Initial calculation
            calculateTotal();
            console.log("Event listeners added");
        }
        
        // Debug form submission
        const form = document.getElementById('booking-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log("Form submitted");
                console.log("User ID:", <?php echo $userId; ?>);
                console.log("Flight ID:", flightSelect.value);
                console.log("Passengers:", passengers.value);
                console.log("Name:", nameInput.value);
                console.log("Email:", emailInput.value);
                console.log("Phone:", phoneInput.value);
                console.log("Total Price:", totalPriceInput.value);
                console.log("Status:", statusInput.value);
                
                // Additional validation
                if (!flightSelect.value) {
                    console.error("Missing flight selection");
                }
                
                if (<?php echo $userId; ?> <= 0) {
                    console.error("Invalid user ID");
                }
            });
        }
    });
</script>

<?php include 'templates/footer.php'; ?>