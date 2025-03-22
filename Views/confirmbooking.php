<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Booking.php';
require_once 'classes/ApiClient.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$booking = null;
$flightStatus = null;

// Check if we have booking ID from session
if (isset($_SESSION['booking_id']) && isset($_SESSION['booking_data'])) {
    $bookingId = $_SESSION['booking_id'];
    $bookingData = $_SESSION['booking_data'];
    
    // Get booking details from database
    try {
        $bookingObj = new Booking();
        $booking = $bookingObj->getBookingById($bookingId, $userId);
        
        if (!$booking) {
            throw new Exception("Booking not found or you don't have permission to view it.");
        }
        
        // Get real-time flight data if available
        try {
            $apiClient = new ApiClient();
            if (isset($bookingData['flight_api']) && !empty($bookingData['flight_api'])) {
                $flightData = $apiClient->getFlightById($bookingData['flight_api']);
                if ($flightData) {
                    // Enhance booking data with flight details
                    $booking = array_merge($booking, [
                        'flight_number' => $flightData['flight_number'] ?? $booking['flight_number'] ?? '',
                        'departure' => $flightData['departure'] ?? $booking['departure'] ?? '',
                        'arrival' => $flightData['arrival'] ?? $booking['arrival'] ?? '',
                        'date' => $flightData['date'] ?? $booking['date'] ?? '',
                        'time' => $flightData['time'] ?? $booking['time'] ?? '',
                        'airline' => $flightData['airline'] ?? '',
                    ]);
                }
            }
        } catch (Exception $e) {
            // Just log the error but continue
            error_log("Error fetching flight data: " . $e->getMessage());
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error fetching booking: " . $e->getMessage());
    }
} else {
    header('Location: my-bookings.php');
    exit();
}

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Booking Confirmation</h1>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php else: ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Your booking has been created successfully! Booking reference: <strong><?= htmlspecialchars($booking['id']) ?></strong></span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="bg-blue-600 p-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-white">Booking Details</h2>
                    <span class="px-3 py-1 bg-white text-blue-700 rounded-full text-sm font-medium">
                        <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                    <!-- Flight Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-200">Flight Information</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Flight Number:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['flight_number'] ?? 'N/A') ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-medium"><?= htmlspecialchars(date('F j, Y', strtotime($booking['date']))) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Time:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['time']) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">From:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['departure']) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">To:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['arrival']) ?></span>
                            </div>
                            
                            <?php if (!empty($booking['airline'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Airline:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['airline']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-200">Customer Information</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Name:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['customer_name']) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['customer_email']) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Phone:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['customer_phone']) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Passengers:</span>
                                <span class="font-medium"><?= htmlspecialchars($booking['passengers']) ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Booking Date:</span>
                                <span class="font-medium"><?= htmlspecialchars(date('F j, Y', strtotime($booking['booking_date']))) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Price Summary -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Price Summary</h3>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Base Price:</span>
                            <span>$<?= htmlspecialchars(number_format($booking['total_price'] / $booking['passengers'], 2)) ?> x <?= htmlspecialchars($booking['passengers']) ?></span>
                        </div>
                        
                        <div class="flex justify-between font-bold text-lg text-blue-600">
                            <span>Total:</span>
                            <span>$<?= htmlspecialchars(number_format($booking['total_price'], 2)) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">What's Next?</h2>
            
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="bg-blue-100 rounded-full p-2 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800">Complete Your Payment</h3>
                        <p class="text-gray-600 mb-2">Your booking is currently in pending status. Complete the payment to confirm your reservation.</p>
                        <form action="payments.php" method="POST">
                            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                            <button type="submit" name="proceed_to_payment" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Proceed to Payment
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-blue-100 rounded-full p-2 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800">Manage Passengers</h3>
                        <p class="text-gray-600 mb-2">Add details for all passengers traveling on this booking.</p>
                        <a href="manage-passengers.php?booking_id=<?= htmlspecialchars($booking['id']) ?>" class="mt-2 inline-block px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200 transition-colors">
                            Manage Passengers
                        </a>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-blue-100 rounded-full p-2 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800">View All Bookings</h3>
                        <p class="text-gray-600 mb-2">View and manage all your bookings in one place.</p>
                        <a href="my-bookings.php" class="mt-2 inline-block px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200 transition-colors">
                            My Bookings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="flex justify-between">
        <a href="search2.php" class="text-blue-600 hover:underline flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Search
        </a>
        
        <a href="my-bookings.php" class="text-blue-600 hover:underline flex items-center">
            View All Bookings
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>
    </div>
</div>

<?php include 'templates/footer.php'; ?>