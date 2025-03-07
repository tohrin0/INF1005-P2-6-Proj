<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Booking.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$flight = null;

// Check if form was submitted from booking page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flight_id'])) {
    $flightId = $_POST['flight_id'];
    $flight = getFlightById($flightId);
    
    // Store booking data in session for later use after payment
    $_SESSION['booking_data'] = [
        'flight_id' => $flightId,
        'user_id' => $userId,
        'passenger_details' => $_POST['passenger_details'] ?? [],
        'passengers' => $_POST['passengers'] ?? 1,
        'price' => $_POST['price'] ?? ($flight ? $flight['price'] : 0)
    ];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    // Handle payment submission
    $paymentMethod = $_POST['payment_method'];
    $cardNumber = $_POST['card_number'] ?? '';
    $cardName = $_POST['card_name'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    $bookingData = $_SESSION['booking_data'] ?? null;
    
    if (!$bookingData) {
        $error = 'Booking information is missing. Please start over.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // For demonstration purposes, we'll always consider the payment successful
            // In a real application, you would integrate with a payment gateway here
            
            // Create a successful "payment" record
            $paymentStmt = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) 
                VALUES (?, ?, ?, ?, 'completed')
            ");
            
            // Create the booking first
            $booking = new Booking();
            $flightId = $bookingData['flight_id'];
            $passengerDetails = $bookingData['passenger_details'];
            $passengers = $bookingData['passengers'];
            $totalPrice = $bookingData['price'] * $passengers;
            
            // Add passenger count to passenger details
            $passengerDetails['passengers'] = $passengers;
            
            // Create booking with status "confirmed" since payment is successful
            $bookingId = $booking->createBooking($userId, $flightId, $passengerDetails, 'confirmed');
            
            if (!$bookingId) {
                throw new Exception("Failed to create booking");
            }
            
            // Create dummy transaction ID
            $transactionId = 'TX' . time() . rand(1000, 9999);
            
            // Record payment
            $paymentStmt->execute([
                $bookingId,
                $totalPrice,
                $paymentMethod,
                $transactionId
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Clear booking data from session
            unset($_SESSION['booking_data']);
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $bookingId);
            exit();
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            $error = "Payment error: " . $e->getMessage();
            error_log($error);
        }
    }
} else {
    // No data submitted, redirect back to search
    if (!isset($_SESSION['booking_data'])) {
        header('Location: search.php');
        exit;
    }
    
    // Get flight details for display on payment page
    $flightId = $_SESSION['booking_data']['flight_id'] ?? 0;
    $flight = getFlightById($flightId);
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Payment</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
        <div class="payment-summary">
            <h2>Booking Summary</h2>
            <?php if ($flight): ?>
                <div class="booking-details">
                    <div class="flight-info">
                        <p><strong>Flight:</strong> <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                        <p><strong>From:</strong> <?php echo htmlspecialchars($flight['departure']); ?></p>
                        <p><strong>To:</strong> <?php echo htmlspecialchars($flight['arrival']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($flight['date']))); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($flight['time']))); ?></p>
                    </div>
                    
                    <div class="price-info">
                        <?php 
                            $basePrice = $flight['price'];
                            $passengers = $_SESSION['booking_data']['passengers'] ?? 1;
                            $totalPrice = $basePrice * $passengers;
                        ?>
                        <p><strong>Base Price:</strong> $<?php echo htmlspecialchars(number_format($basePrice, 2)); ?></p>
                        <p><strong>Passengers:</strong> <?php echo htmlspecialchars($passengers); ?></p>
                        <p class="total-price"><strong>Total Price:</strong> $<?php echo htmlspecialchars(number_format($totalPrice, 2)); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="payment-form-container">
            <h2>Payment Details</h2>
            <form action="payment.php" method="POST" id="payment-form">
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" name="payment_method" id="credit-card" value="credit_card" checked>
                        <label for="credit-card">Credit Card</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" name="payment_method" id="debit-card" value="debit_card">
                        <label for="debit-card">Debit Card</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" name="payment_method" id="paypal" value="paypal">
                        <label for="paypal">PayPal</label>
                    </div>
                </div>
                
                <div id="credit-card-form">
                    <div class="form-group">
                        <label for="card-number">Card Number*</label>
                        <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="card-name">Name on Card*</label>
                        <input type="text" id="card-name" name="card_name" placeholder="John Doe" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="expiry-date">Expiry Date*</label>
                            <input type="text" id="expiry-date" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group half">
                            <label for="cvv">CVV*</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Pay Now</button>
            </form>
        </div>
    <?php endif; ?>
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('payment-form');
        const cardNumber = document.getElementById('card-number');
        const expiryDate = document.getElementById('expiry-date');
        const cvv = document.getElementById('cvv');
        
        // Format card number with spaces
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
        
        // Format expiry date with slash
        expiryDate.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 2) {
                e.target.value = value.substring(0, 2) + '/' + value.substring(2);
            } else {
                e.target.value = value;
            }
        });
        
        // Basic form validation
        form.addEventListener('submit', function(e) {
            const cardName = document.getElementById('card-name').value.trim();
            const cardNumberValue = cardNumber.value.replace(/\s/g, '');
            const expiryDateValue = expiryDate.value;
            const cvvValue = cvv.value;
            
            let isValid = true;
            
            if (cardNumberValue.length < 16) {
                alert('Please enter a valid card number');
                isValid = false;
            }
            
            if (!cardName) {
                alert('Please enter the name on card');
                isValid = false;
            }
            
            if (!expiryDateValue.match(/^\d{2}\/\d{2}$/)) {
                alert('Please enter a valid expiry date (MM/YY)');
                isValid = false;
            }
            
            if (cvvValue.length < 3) {
                alert('Please enter a valid CVV');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Toggle between payment methods (for future implementation)
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const creditCardForm = document.getElementById('credit-card-form');
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                if (this.value === 'credit_card' || this.value === 'debit_card') {
                    creditCardForm.style.display = 'block';
                } else {
                    creditCardForm.style.display = 'none';
                }
            });
        });
    });
</script>

<?php include 'templates/footer.php'; ?>