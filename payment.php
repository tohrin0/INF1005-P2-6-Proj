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

// Check if we have booking data in session
if (!isset($_SESSION['booking_id']) || !isset($_SESSION['booking_data'])) {
    header('Location: booking.php');
    exit;
}

$bookingId = $_SESSION['booking_id'];
$bookingData = $_SESSION['booking_data'];

// Get flight details for display on payment page
$flightId = $bookingData['flight_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
$stmt->execute([$flightId]);
$flight = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                throw new Exception("Failed to update booking status.");
            }
            
            // Create dummy transaction ID for payment record
            $transactionId = 'TX' . time() . rand(1000, 9999);
            
            // Record payment
            $paymentStmt = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status)
                VALUES (?, ?, ?, ?, 'completed')
            ");
            
            if (!$paymentStmt->execute([
                $bookingId,
                $bookingData['total_price'],
                $paymentMethod,
                $transactionId
            ])) {
                throw new Exception("Failed to record payment.");
            }
            
            // Update available seats
            $updateSeatsStmt = $pdo->prepare("
                UPDATE flights 
                SET available_seats = available_seats - ? 
                WHERE id = ?
            ");
            
            if (!$updateSeatsStmt->execute([
                $bookingData['passenger_details']['passengers'],
                $flightId
            ])) {
                throw new Exception("Failed to update available seats.");
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Clear booking data from session
            unset($_SESSION['booking_id']);
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
                        <h3>Flight Details</h3>
                        <p><strong>Flight Number:</strong> <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                        <p><strong>From:</strong> <?php echo htmlspecialchars($flight['departure']); ?></p>
                        <p><strong>To:</strong> <?php echo htmlspecialchars($flight['arrival']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($flight['date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($flight['time']); ?></p>
                    </div>
                    
                    <div class="price-info">
                        <h3>Price Details</h3>
                        <p><strong>Price per passenger:</strong> $<?php echo number_format($flight['price'], 2); ?></p>
                        <p><strong>Passengers:</strong> <?php echo $bookingData['passenger_details']['passengers']; ?></p>
                        <div class="total-price">
                            <span class="label">Total:</span>
                            <span class="price">$<?php echo number_format($bookingData['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="payment-form-container">
            <h2>Payment Details</h2>
            <form action="payment.php" method="POST" id="payment-form">
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" id="credit-card" name="payment_method" value="credit_card" checked>
                        <label for="credit-card">Credit Card</label>
                    </div>
                </div>
                
                <div id="credit-card-form">
                    <div class="form-group">
                        <label for="card-name">Name on Card</label>
                        <input type="text" id="card-name" name="card_name" placeholder="John Smith" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="card-number">Card Number</label>
                        <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="expiry-date">Expiry Date</label>
                            <input type="text" id="expiry-date" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group half">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3" required>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Complete Payment</button>
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
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            e.target.value = value;
        });
        
        // Basic form validation
        form.addEventListener('submit', function(e) {
            const cardValue = cardNumber.value.replace(/\s/g, '');
            const expiryValue = expiryDate.value;
            const cvvValue = cvv.value;
            
            let isValid = true;
            
            // Validate card number (16 digits)
            if (!/^\d{16}$/.test(cardValue)) {
                alert('Please enter a valid 16-digit card number.');
                isValid = false;
            }
            
            // Validate expiry date (MM/YY format)
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiryValue)) {
                alert('Please enter a valid expiry date in MM/YY format.');
                isValid = false;
            }
            
            // Validate CVV (3 digits)
            if (!/^\d{3}$/.test(cvvValue)) {
                alert('Please enter a valid 3-digit CVV.');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>

<?php include 'templates/footer.php'; ?>