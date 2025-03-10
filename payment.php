<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Booking.php';
require_once 'classes/Payment.php';
require_once 'classes/ApiClient.php';

// At the top of the file, add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$flight = null;
$flightStatus = null;

// Check if we have booking data from My Bookings page
if (isset($_POST['pay_booking']) && isset($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];
    $bookingPrice = $_POST['price'] ?? 0;
    
    // Verify the booking belongs to the user
    $stmt = $pdo->prepare(
        "SELECT b.*, f.flight_number, f.flight_api, f.departure, f.arrival, f.date, f.time 
         FROM bookings b
         JOIN flights f ON b.flight_id = f.id
         WHERE b.id = ? AND b.user_id = ?"
    );
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $error = "Invalid booking or you don't have permission to pay for this booking.";
        header('Location: my-bookings.php');
        exit();
    }
    
    // Store booking data in session for the payment page
    $_SESSION['booking_id'] = $bookingId;
    $_SESSION['booking_data'] = [
        'flight_id' => $booking['flight_id'],
        'flight_api' => $booking['flight_api'], // Include the flight_api ID
        'total_price' => $booking['total_price'],
        'customer_name' => $booking['customer_name'],
        'flight_number' => $booking['flight_number'],
        'departure' => $booking['departure'],
        'arrival' => $booking['arrival'],
        'date' => $booking['date'],
        'time' => $booking['time']
    ];
    
    // Get real-time flight data
    try {
        $apiClient = new ApiClient();
        if (isset($booking['flight_number']) && !empty($booking['flight_number'])) {
            $flightStatus = $apiClient->getFlightStatus([
                'flight_number' => $booking['flight_number'],
                'date' => $booking['date']
            ]);
        }
    } catch (Exception $e) {
        // Just log the error but continue
        error_log("Error fetching real-time flight data for payment page: " . $e->getMessage());
    }
}
// Check if we have booking data in session
else if (!isset($_SESSION['booking_id']) || !isset($_SESSION['booking_data'])) {
    header('Location: my-bookings.php');
    exit;
}

$bookingId = $_SESSION['booking_id'];
$bookingData = $_SESSION['booking_data'];

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Debug line
    error_log("Payment form submitted");
    
    // Create Payment object with booking amount
    $payment = new Payment($bookingData['total_price'], 'USD', 'credit_card');
    
    try {
        // Process payment (simplified version)
        $result = $payment->processPayment($userId, $bookingData['total_price'], 'credit_card');
        
        if ($result['success']) {
            // Debug line
            error_log("Payment processed successfully");
            
            // Update booking status to confirmed
            if ($payment->updateBookingAfterPayment($bookingId, 'confirmed', $result['transaction_id'])) {
                // Debug line
                error_log("Booking status updated successfully");
                
                $_SESSION['payment_success'] = true;
                header('Location: my-bookings.php?success=payment');
                exit();
            } else {
                $error = "Payment was processed but booking status could not be updated";
                error_log("Failed to update booking status");
            }
        } else {
            $error = "Payment processing failed";
            error_log("Payment processing failed");
        }
    } catch (Exception $e) {
        error_log("Payment error: " . $e->getMessage());
        $error = "An error occurred during payment processing";
    }
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Payment</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="payment-summary">
        <h2>Booking Summary</h2>
        <div class="booking-details">
            <div class="flight-info">
                <h3>Flight Details</h3>
                <p><strong>Flight Number:</strong> <?php echo htmlspecialchars($bookingData['flight_number']); ?></p>
                <p><strong>From:</strong> <?php echo htmlspecialchars($bookingData['departure']); ?></p>
                <p><strong>To:</strong> <?php echo htmlspecialchars($bookingData['arrival']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($bookingData['date']))); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($bookingData['time']))); ?></p>
                
                <?php if ($flightStatus): ?>
                <div class="real-time-info">
                    <h4>Real-Time Flight Status</h4>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($flightStatus['status'] ?? 'Scheduled')); ?></p>
                    <?php if (isset($flightStatus['delay']) && $flightStatus['delay'] > 0): ?>
                    <p class="delay"><strong>Delay:</strong> <?php echo htmlspecialchars($flightStatus['delay']); ?> minutes</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="price-info">
                <h3>Price Details</h3>
                <p><strong>Passenger:</strong> <?php echo htmlspecialchars($bookingData['customer_name']); ?></p>
                <p class="total-price"><strong>Total Amount:</strong> $<?php echo htmlspecialchars(number_format($bookingData['total_price'], 2)); ?></p>
            </div>
        </div>
    </div>
    
    <div class="payment-form-container">
        <h2>Payment Details</h2>
        <form id="payment-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="payment-methods">
                <div class="payment-method">
                    <input type="radio" id="credit-card" name="payment_method" value="credit_card" checked>
                    <label for="credit-card">Credit Card</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" placeholder="e.g., 4111222233334444" maxlength="16" required>
                <small>Visa (starts with 4) or MasterCard (starts with 5)</small>
            </div>
            
            <div class="form-group">
                <label for="card_name">Cardholder Name</label>
                <input type="text" id="card_name" name="card_name" placeholder="Name on card" required>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
                </div>
                <div class="form-group half">
                    <label for="cvv">CVV</label>
                    <input type="password" id="cvv" name="cvv" placeholder="3 digits" maxlength="3" required>
                </div>
            </div>
            
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($bookingId); ?>">
            <button type="submit" name="process_payment" class="btn-primary">Complete Payment</button>
        </form>
    </div>
</div>

<style>
    .payment-summary {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .booking-details {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    
    .flight-info, .price-info {
        flex: 1;
        min-width: 250px;
    }
    
    .real-time-info {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px dashed #ccc;
    }
    
    .total-price {
        font-size: 1.2em;
        color: #4CAF50;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
    }
    
    .payment-form-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .payment-methods {
        display: flex;
        margin-bottom: 20px;
    }
    
    .payment-method {
        margin-right: 20px;
        display: flex;
        align-items: center;
    }
    
    .payment-method input {
        margin-right: 5px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
    }
    
    .form-group.half {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }
    
    .form-group small {
        color: #6c757d;
        font-size: 0.85em;
        margin-top: 5px;
        display: block;
    }
    
    .btn-primary {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format card number with spaces for readability
        const cardInput = document.getElementById('card_number');
        if (cardInput) {
            cardInput.addEventListener('input', function(e) {
                // Remove all non-digits
                let value = e.target.value.replace(/\D/g, '');
                
                // Limit to 16 digits
                value = value.substring(0, 16);
                
                // Format with spaces (optional)
                e.target.value = value;
            });
        }
        
        // Format expiry date input
        const expiryInput = document.getElementById('expiry_date');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                // Add slash after month
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });
        }
        
        // Ensure CVV is only digits
        const cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
            });
        }
    });
</script>

<?php include 'templates/footer.php'; ?>