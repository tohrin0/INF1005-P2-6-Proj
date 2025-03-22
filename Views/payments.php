<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Booking.php';
require_once 'classes/Payment.php';
require_once 'classes/ApiClient.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$booking = null;

// Check if we have booking ID from form submission
if (isset($_POST['booking_id']) || isset($_GET['booking_id'])) {
    $bookingId = $_POST['booking_id'] ?? $_GET['booking_id'];
    
    // Verify the booking belongs to the user
    try {
        $bookingObj = new Booking();
        $booking = $bookingObj->getBookingById($bookingId, $userId);
        
        if (!$booking) {
            throw new Exception("Invalid booking or you don't have permission to access it.");
        }
        
        // Store booking data in session
        $_SESSION['payment_booking_id'] = $bookingId;
        
        // Get flight details if available
        try {
            $stmt = $pdo->prepare(
                "SELECT f.* FROM flights f
                 JOIN bookings b ON b.flight_id = f.id
                 WHERE b.id = ? AND b.user_id = ?"
            );
            $stmt->execute([$bookingId, $userId]);
            $flight = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($flight) {
                $booking['flight_number'] = $flight['flight_number'];
                $booking['departure'] = $flight['departure'];
                $booking['arrival'] = $flight['arrival'];
                $booking['date'] = $flight['date'];
                $booking['time'] = $flight['time'];
                $booking['airline'] = $flight['airline'];
            }
        } catch (Exception $e) {
            error_log("Error fetching flight details: " . $e->getMessage());
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    header('Location: my-bookings.php');
    exit();
}

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Validate form data
    if (empty($_POST['card_number']) || empty($_POST['card_name']) || 
        empty($_POST['expiry_date']) || empty($_POST['cvv'])) {
        $error = "Please fill in all payment fields.";
    } else if (!preg_match('/^\d{16}$/', str_replace(' ', '', $_POST['card_number']))) {
        $error = "Invalid card number. Please enter a 16-digit number.";
    } else if (!preg_match('/^\d{3,4}$/', $_POST['cvv'])) {
        $error = "Invalid CVV. Please enter a 3 or 4-digit number.";
    } else if (!preg_match('/^\d{2}\/\d{2}$/', $_POST['expiry_date'])) {
        $error = "Invalid expiry date. Please use MM/YY format.";
    } else {
        try {
            // Create payment object
            $payment = new Payment($booking['total_price'], 'USD', 'credit_card');
            
            // Generate a unique transaction ID
            $transactionId = 'TX' . time() . rand(1000, 9999);
            
            // Update booking status
            if ($payment->updateBookingAfterPayment($bookingId, 'confirmed', $transactionId)) {
                // Insert payment record
                $stmt = $pdo->prepare(
                    "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) 
                     VALUES (?, ?, ?, ?, 'completed')"
                );
                
                if ($stmt->execute([
                    $bookingId,
                    $booking['total_price'],
                    'credit_card',
                    $transactionId
                ])) {
                    $success = "Payment processed successfully! Your booking is now confirmed.";
                    
                    // Clear session variables
                    unset($_SESSION['payment_booking_id']);
                } else {
                    $error = "Payment was processed but could not record payment details.";
                }
            } else {
                $error = "Failed to update booking status. Please try again.";
            }
        } catch (Exception $e) {
            $error = "An error occurred during payment processing: " . $e->getMessage();
            error_log("Payment error: " . $e->getMessage());
        }
    }
}

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Payment</h1>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        
        <div class="flex justify-center mb-6">
            <a href="my-bookings.php" class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                View My Bookings
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Booking Summary -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Booking Summary</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Booking ID:</span>
                            <span class="font-medium">#<?= htmlspecialchars($booking['id']) ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Flight:</span>
                            <span class="font-medium"><?= htmlspecialchars($booking['flight_number'] ?? 'N/A') ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Route:</span>
                            <span class="font-medium"><?= htmlspecialchars($booking['departure']) ?> to <?= htmlspecialchars($booking['arrival']) ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Date:</span>
                            <span class="font-medium"><?= htmlspecialchars(date('M j, Y', strtotime($booking['date']))) ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Passengers:</span>
                            <span class="font-medium"><?= htmlspecialchars($booking['passengers']) ?></span>
                        </div>
                        
                        <div class="pt-3 mt-3 border-t border-gray-200">
                            <div class="flex justify-between">
                                <span class="font-medium">Total Amount:</span>
                                <span class="font-bold text-blue-600">$<?= htmlspecialchars(number_format($booking['total_price'], 2)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Payment Information</h2>
                    
                    <form method="POST" action="">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-medium mb-2">Payment Method</label>
                            <div class="grid grid-cols-4 gap-4">
                                <div class="border rounded-md p-4 flex items-center justify-center bg-white shadow-sm cursor-pointer border-blue-500">
                                    <img src="assets/images/visa.svg" alt="Visa" class="h-8" onerror="this.src='https://placehold.co/80x32?text=Visa'">
                                </div>
                                <div class="border rounded-md p-4 flex items-center justify-center bg-white shadow-sm cursor-pointer">
                                    <img src="assets/images/mastercard.svg" alt="Mastercard" class="h-8" onerror="this.src='https://placehold.co/80x32?text=Mastercard'">
                                </div>
                                <div class="border rounded-md p-4 flex items-center justify-center bg-white shadow-sm cursor-pointer">
                                    <img src="assets/images/amex.svg" alt="American Express" class="h-8" onerror="this.src='https://placehold.co/80x32?text=Amex'">
                                </div>
                                <div class="border rounded-md p-4 flex items-center justify-center bg-white shadow-sm cursor-pointer">
                                    <img src="assets/images/paypal.svg" alt="PayPal" class="h-8" onerror="this.src='https://placehold.co/80x32?text=PayPal'">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="card_number" class="block text-gray-700 font-medium mb-2">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" pattern="\d{4}\s?\d{4}\s?\d{4}\s?\d{4}" title="Please enter a valid 16-digit card number" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="card_name" class="block text-gray-700 font-medium mb-2">Cardholder Name</label>
                            <input type="text" id="card_name" name="card_name" placeholder="John Smith" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="expiry_date" class="block text-gray-700 font-medium mb-2">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="cvv" class="block text-gray-700 font-medium mb-2">CVV</label>
                                <input type="password" id="cvv" name="cvv" placeholder="123" pattern="\d{3,4}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-md mb-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="save_card" name="save_card" type="checkbox" 
                                           class="w-4 h-4 border border-gray-300 rounded focus:ring-3 focus:ring-blue-300">
                                </div>
                                <label for="save_card" class="ml-2 text-sm text-gray-600">
                                    Save card for future payments
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <a href="confirmbooking.php" class="text-gray-600 hover:underline">
                                Cancel
                            </a>
                            <button type="submit" name="process_payment" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Pay $<?= htmlspecialchars(number_format($booking['total_price'], 2)) ?> Now
                            </button>
                        </div>
                        
                        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500">This is a demo payment form. No real payments will be processed.</p>
                        <div class="mt-4 flex items-center justify-center space-x-4 text-sm text-gray-600">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Secure Payment
                            </span>
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Encrypted Data
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Format credit card number with spaces
    document.getElementById('card_number').addEventListener('input', function (e) {
        const value = e.target.value.replace(/\s/g, '');
        const formattedValue = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        e.target.value = formattedValue;
    });
    
    // Format expiry date
    document.getElementById('expiry_date').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
</script>

<?php include 'templates/footer.php'; ?>