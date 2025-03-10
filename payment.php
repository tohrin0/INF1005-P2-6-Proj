<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Booking.php';
require_once 'classes/ApiClient.php';

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
        "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date, f.time 
         FROM bookings b
         LEFT JOIN flights f ON b.flight_id = f.id
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
            $params = [
                'flight_iata' => $booking['flight_number'],
                'flight_date' => $booking['date']
            ];
            
            $realTimeFlight = $apiClient->getFlightStatus($params);
            
            if (!empty($realTimeFlight)) {
                $flightStatus = $realTimeFlight[0]; // Get first match
            }
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
    // Process payment
    $paymentMethod = $_POST['payment_method'] ?? '';
    $cardNumber = $_POST['card_number'] ?? '';
    $cardName = $_POST['card_name'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    if (empty($paymentMethod) || empty($cardNumber) || empty($cardName) || empty($expiryDate) || empty($cvv)) {
        $error = "Please fill in all payment details.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update booking status to "confirmed"
            $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND user_id = ?");
            if (!$updateStmt->execute([$bookingId, $userId])) {
                throw new Exception("Failed to update booking status");
            }
            
            // Create dummy transaction ID for payment record
            $transactionId = 'TX' . time() . rand(1000, 9999);
            
            // Record payment (if you have a payments table)
            // For now, we'll just simulate a successful payment
            
            // Commit transaction
            $pdo->commit();
            
            // Clear booking data from session
            unset($_SESSION['booking_id']);
            unset($_SESSION['booking_data']);
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $bookingId);
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            $error = "Payment processing failed: " . $e->getMessage();
            error_log("Payment error: " . $e->getMessage());
        }
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
                <p><strong>Flight Number:</strong> <?php echo htmlspecialchars($bookingData['flight_number'] ?? 'N/A'); ?></p>
                <p><strong>From:</strong> <?php echo htmlspecialchars($bookingData['departure'] ?? 'N/A'); ?></p>
                <p><strong>To:</strong> <?php echo htmlspecialchars($bookingData['arrival'] ?? 'N/A'); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($bookingData['date'] ?? 'N/A'); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($bookingData['time'] ?? 'N/A'); ?></p>
                
                <?php if ($flightStatus): ?>
                <div class="real-time-info">
                    <h4>Real-Time Flight Status</h4>
                    <p><span class="status-badge <?php echo strtolower($flightStatus['flight_status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($flightStatus['flight_status'] ?? 'scheduled')); ?>
                    </span></p>
                    
                    <?php if (isset($flightStatus['departure']['terminal'])): ?>
                    <p><strong>Departure Terminal:</strong> <?php echo htmlspecialchars($flightStatus['departure']['terminal']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($flightStatus['departure']['gate'])): ?>
                    <p><strong>Departure Gate:</strong> <?php echo htmlspecialchars($flightStatus['departure']['gate']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($flightStatus['departure']['delay']) && $flightStatus['departure']['delay'] > 0): ?>
                    <p class="delay-warning"><strong>Departure Delay:</strong> <?php echo htmlspecialchars($flightStatus['departure']['delay']); ?> minutes</p>
                    <?php endif; ?>
                    
                    <?php if (isset($flightStatus['arrival']['terminal'])): ?>
                    <p><strong>Arrival Terminal:</strong> <?php echo htmlspecialchars($flightStatus['arrival']['terminal']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($flightStatus['arrival']['gate'])): ?>
                    <p><strong>Arrival Gate:</strong> <?php echo htmlspecialchars($flightStatus['arrival']['gate']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($flightStatus['airline']['name'])): ?>
                    <p><strong>Airline:</strong> <?php echo htmlspecialchars($flightStatus['airline']['name']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="price-info">
                <h3>Payment Details</h3>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($bookingData['customer_name'] ?? 'N/A'); ?></p>
                <div class="total-price">
                    <strong>Total Amount:</strong> $<?php echo htmlspecialchars(number_format($bookingData['total_price'], 2)); ?>
                </div>
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
                <div class="payment-method">
                    <input type="radio" id="debit-card" name="payment_method" value="debit_card">
                    <label for="debit-card">Debit Card</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="card-number">Card Number</label>
                <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" required>
            </div>
            
            <div class="form-group">
                <label for="card-name">Name on Card</label>
                <input type="text" id="card-name" name="card_name" placeholder="John Smith" required>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="expiry-date">Expiry Date</label>
                    <input type="text" id="expiry-date" name="expiry_date" placeholder="MM/YY" required>
                </div>
                <div class="form-group half">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" placeholder="123" required>
                </div>
            </div>
            
            <button type="submit" name="process_payment" class="btn-primary">Pay $<?php echo htmlspecialchars(number_format($bookingData['total_price'], 2)); ?></button>
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
        margin-top: 20px;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumber = document.getElementById('card-number');
        const expiryDate = document.getElementById('expiry-date');
        const cvv = document.getElementById('cvv');
        
        if (cardNumber) {
            cardNumber.addEventListener('input', function(e) {
                // Format card number with spaces every 4 digits
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                e.target.value = formattedValue.substring(0, 19); // Limit to 16 digits plus 3 spaces
            });
        }
        
        if (expiryDate) {
            expiryDate.addEventListener('input', function(e) {
                // Format expiry date as MM/YY
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }
        
        if (cvv) {
            cvv.addEventListener('input', function(e) {
                // Limit CVV to 3 or 4 digits
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
            });
        }
    });
</script>

<?php include 'templates/footer.php'; ?>