<?php
// Add this at the very beginning to catch any early errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';
require_once 'classes/Booking.php';

// Debug: Log request method and headers for debugging
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("X-Requested-With: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));

if (!isset($_SESSION['user_id'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You must be logged in to book a flight', 'redirect' => 'login.php']);
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$flights = [];

// Debug: log any POST data
error_log("POST data received in booking.php: " . json_encode($_POST));

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Debug: log POST data and AJAX status
    error_log("POST data: " . json_encode($_POST));
    error_log("Is AJAX request: " . ($isAjax ? 'yes' : 'no'));
    
    if (empty($_POST['flight_id']) || empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        $error = "Please fill in all required fields";
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    } else {
        try {
            // Create booking logic
            $booking = new Booking();
            
            // Gather passenger details
            $passengerDetails = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'passengers' => $_POST['passengers'] ?? 1
            ];
            
            // Create booking
            $bookingId = $booking->createBooking(
                $userId,
                $_POST['flight_id'],
                $passengerDetails
            );
            
            if ($bookingId) {
                // Store booking info in session for payment page
                $_SESSION['booking_id'] = $bookingId;
                $_SESSION['booking_data'] = [
                    'flight_id' => $_POST['flight_id'],
                    'total_price' => $_POST['total_price'] ?? 0
                ];
                
                // For AJAX requests, return JSON instead of redirecting
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Booking created successfully',
                        'redirect' => 'payment.php',
                        'booking_id' => $bookingId
                    ]);
                    exit;
                }
                
                // For non-AJAX requests, redirect
                header("Location: payment.php");
                exit;
            } else {
                throw new Exception("Failed to create booking");
            }
            
        } catch (Exception $e) {
            $error = "Booking error: " . $e->getMessage();
            error_log($error);
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }
        }
    }
}

// Retrieve flights data via API
try {
    $apiClient = new ApiClient();
    $flights = $apiClient->getAvailableFlights();
    
    if (!empty($flights)) {
        $_SESSION['flight_data'] = [];
        foreach ($flights as $f) {
            $_SESSION['flight_data'][$f['id']] = $f;
        }
        error_log("Using API data - " . count($flights) . " flights found");
    } else {
        $error = "No flights available at this time.";
        error_log("API returned no flights");
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
        <!-- Using PHP_SELF ensures the form posts to the correct URL -->
        <form id="booking-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="flight_id"><i class="fas fa-plane"></i> Select a Flight:</label>
                <select id="flight_id" name="flight_id" class="form-control" required>
                    <option value="">-- Select Flight --</option>
                    <?php foreach ($flights as $f): ?>
                        <option value="<?php echo htmlspecialchars($f['id']); ?>">
                            <?php echo htmlspecialchars($f['flight_number']); ?> -
                            <?php echo htmlspecialchars($f['departure']); ?> to 
                            <?php echo htmlspecialchars($f['arrival']); ?> - 
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($f['date']))); ?> - 
                            $<?php echo htmlspecialchars(number_format($f['price'], 2)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="passengers"><i class="fas fa-users"></i> Number of Passengers:</label>
                <input type="number" id="passengers" name="passengers" min="1" max="9" value="1" class="form-control" required>
            </div>
            
            <h3>Passenger Information</h3>
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Full Name:</label>
                <input type="text" id="name" name="name" placeholder="Enter your name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Phone:</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" class="form-control" required>
            </div>
            
            <div id="total-price-display" class="total-price">
                <strong>Total Price:</strong> $0.00
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Book Flight
                </button>
            </div>
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
        // Add Font Awesome if not already included
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
            document.head.appendChild(link);
        }
        
        const form = document.getElementById('booking-form');
        const flightSelect = document.getElementById('flight_id');
        const passengers = document.getElementById('passengers');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Basic form validation
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const flightId = flightSelect.value;
                
                if (!flightId || !name || !email || !phone) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }
                
                // Email validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address');
                    return false;
                }
                
                // Basic phone validation
                const phonePattern = /^[0-9\+\-\(\)\s]{7,20}$/;
                if (!phonePattern.test(phone)) {
                    e.preventDefault();
                    alert('Please enter a valid phone number');
                    return false;
                }
            });
            
            // Update price calculation when passengers change
            passengers.addEventListener('change', function() {
                updateTotalPrice();
            });
            
            flightSelect.addEventListener('change', function() {
                updateTotalPrice();
            });
            
            function updateTotalPrice() {
                const selectedOption = flightSelect.options[flightSelect.selectedIndex];
                if (selectedOption.value) {
                    const priceText = selectedOption.text.match(/\$([0-9,.]+)/);
                    if (priceText && priceText[1]) {
                        const basePrice = parseFloat(priceText[1].replace(',', ''));
                        const numPassengers = parseInt(passengers.value) || 1;
                        const totalPrice = basePrice * numPassengers;
                        
                        // Update the price display element
                        const priceDisplay = document.getElementById('total-price-display');
                        priceDisplay.innerHTML = `<strong>Total Price:</strong> $${totalPrice.toFixed(2)}`;
                    }
                }
            }
            
            // Initialize price display on page load
            if (flightSelect.selectedIndex > 0) {
                updateTotalPrice();
            }
        }
        
        // Disable automatic form validation and ajax submission from booking-form.js
        if (typeof window.disableBookingFormAjax === 'undefined') {
            window.disableBookingFormAjax = true;
        }
    });
</script>

<script>
    // Add this at the end of booking.php right before the footer include
    document.addEventListener('DOMContentLoaded', function() {
        // Get element references
        const form = document.getElementById('booking-form');
        const flightSelect = document.getElementById('flight_id');
        const passengers = document.getElementById('passengers');
        
        if (form && flightSelect && passengers) {
            // Override any existing event listeners
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            
            // Reattach our own listeners to the new form
            newForm.addEventListener('submit', function(e) {
                // Basic form validation
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const flightId = document.getElementById('flight_id').value;
                
                if (!flightId || !name || !email || !phone) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }
                
                // Allow normal form submission
                return true;
            });
            
            // Get new references after replacing the form
            const newFlightSelect = document.getElementById('flight_id');
            const newPassengers = document.getElementById('passengers');
            
            // Re-attach price update functionality
            if (newFlightSelect && newPassengers) {
                newFlightSelect.addEventListener('change', updateTotalPrice);
                newPassengers.addEventListener('change', updateTotalPrice);
                
                // Initialize price display
                if (newFlightSelect.selectedIndex > 0) {
                    updateTotalPrice();
                }
            }
        }
        
        function updateTotalPrice() {
            const flightSelect = document.getElementById('flight_id');
            const passengers = document.getElementById('passengers');
            const priceDisplay = document.getElementById('total-price-display');
            
            if (!flightSelect || !passengers || !priceDisplay) return;
            
            const selectedOption = flightSelect.options[flightSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const priceText = selectedOption.text.match(/\$([0-9,.]+)/);
                if (priceText && priceText[1]) {
                    const basePrice = parseFloat(priceText[1].replace(',', ''));
                    const numPassengers = parseInt(passengers.value) || 1;
                    const totalPrice = basePrice * numPassengers;
                    
                    priceDisplay.innerHTML = `<strong>Total Price:</strong> $${totalPrice.toFixed(2)}`;
                }
            }
        }
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get element references
    const form = document.getElementById('booking-form');
    const flightSelect = document.getElementById('flight_id');
    const passengers = document.getElementById('passengers');
    const totalPriceDisplay = document.getElementById('total-price-display');
    
    if (form && flightSelect && passengers) {
        // Update price when flight or passenger count changes
        flightSelect.addEventListener('change', updateTotalPrice);
        passengers.addEventListener('change', updateTotalPrice);
        
        function updateTotalPrice() {
            const selectedOption = flightSelect.selectedOptions[0];
            
            if (selectedOption && selectedOption.value) {
                // Extract price from the option text (assuming format ends with "$XX.XX")
                const priceText = selectedOption.text.split('$').pop();
                const price = parseFloat(priceText.replace(/,/g, ''));
                
                if (!isNaN(price)) {
                    const passengerCount = parseInt(passengers.value) || 1;
                    const totalPrice = (price * passengerCount).toFixed(2);
                    totalPriceDisplay.innerHTML = '<strong>Total Price:</strong> $' + totalPrice;
                }
            }
        }
    }
});
</script>

<?php include 'templates/footer.php'; ?>