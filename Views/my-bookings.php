<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/ApiClient.php';
require_once 'classes/BookingManager.php';
require_once 'classes/Passenger.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-bookings.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';

try {
    // Create BookingManager instance
    $bookingManager = new BookingManager();
    
    // Get all user bookings, categorized by status
    $categorizedBookings = $bookingManager->getUserBookings($userId);
    
    // Enhance with real-time flight data from API
    $apiClient = new ApiClient();

    // Process all booking categories
    foreach ($categorizedBookings as $category => &$bookings) {
        foreach ($bookings as &$booking) {
            if (isset($booking['flight_number']) && !empty($booking['flight_number'])) {
                try {
                    // Get real-time flight data
                    $params = [
                        'flight_iata' => $booking['flight_number'],
                        'flight_date' => $booking['date']
                    ];

                    $realTimeFlight = $apiClient->getFlightStatus($params);

                    if (!empty($realTimeFlight)) {
                        $rtf = $realTimeFlight[0]; // Get first match

                        // Add real-time data to the booking
                        $booking['real_time_status'] = $rtf['flight_status'] ?? 'scheduled';
                        $booking['departure_terminal'] = $rtf['departure']['terminal'] ?? null;
                        $booking['departure_gate'] = $rtf['departure']['gate'] ?? null;
                        $booking['departure_delay'] = $rtf['departure']['delay'] ?? 0;
                        $booking['arrival_terminal'] = $rtf['arrival']['terminal'] ?? null;
                        $booking['arrival_gate'] = $rtf['arrival']['gate'] ?? null;
                        $booking['arrival_delay'] = $rtf['arrival']['delay'] ?? 0;
                        $booking['aircraft_registration'] = $rtf['aircraft']['registration'] ?? null;
                        $booking['aircraft_type'] = $rtf['aircraft']['iata'] ?? null;
                        $booking['airline_name'] = $rtf['airline']['name'] ?? null;
                    }
                } catch (Exception $e) {
                    // Just log the error but continue
                    error_log("Error fetching real-time flight data: " . $e->getMessage());
                }
            }
        }
        unset($booking); // Unset inner loop reference
    }
    unset($bookings); // Unset outer loop reference
} catch (Exception $e) {
    $error = "Error retrieving bookings: " . $e->getMessage();
    error_log($error);
}

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">My Bookings</h1>

    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php 
    // Check if there are any bookings at all
    $noBookings = empty($categorizedBookings['pending']) && 
                 empty($categorizedBookings['confirmed']) && 
                 empty($categorizedBookings['past']);
    ?>

    <?php if ($noBookings): ?>
        <div class="bg-white rounded-lg shadow-md p-10 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">You don't have any bookings yet</h2>
            <p class="text-gray-600 mb-6">Start by searching for flights to book your next adventure!</p>
            <a href="search2.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                Search Flights
            </a>
        </div>
    <?php else: ?>
        <!-- Pending Bookings Section -->
        <?php if (!empty($categorizedBookings['pending'])): ?>
            <div class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pending Payment
                </h2>

                <?php foreach ($categorizedBookings['pending'] as $booking): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-l-4 border-yellow-500">
                        <div class="bg-yellow-50 p-4 flex justify-between items-center">
                            <div>
                                <span class="font-semibold text-gray-800">Booking #<?= htmlspecialchars($booking['id']) ?></span>
                                <span class="ml-2 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                    <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Booked on <?= date('F j, Y', strtotime($booking['booking_date'])) ?>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
                            <!-- Flight Details -->
                            <div class="col-span-2">
                                <h3 class="font-semibold text-gray-800 mb-2 pb-2 border-b border-gray-200">
                                    Flight Details
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <p class="text-sm text-gray-600">Flight Number</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['flight_number'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Airline</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['airline'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">From</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['departure'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">To</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['arrival'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Date</p>
                                        <p class="font-medium"><?= date('F j, Y', strtotime($booking['date'])) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Time</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['time'] ?? 'N/A') ?></p>
                                    </div>
                                </div>

                                <h3 class="font-semibold text-gray-800 mb-2 pb-2 border-b border-gray-200 mt-4">
                                    Passenger Information
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <p class="text-sm text-gray-600">Customer Name</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['customer_name']) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Email</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['customer_email']) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Phone</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['customer_phone']) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Passengers</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['passengers']) ?></p>
                                    </div>
                                    
                                    <?php
                                    // Get remaining passengers to be registered
                                    $remainingPassengers = $bookingManager->getRemainingPassengersCount($booking['id'], $booking['passengers']);
                                    ?>
                                    
                                    <?php if ($remainingPassengers > 0): ?>
                                        <div class="col-span-2">
                                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-3 py-2 rounded-md text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <?= $remainingPassengers ?> passenger(s) information still needs to be added.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Payment & Actions -->
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h3 class="font-semibold text-gray-800 mb-3">Payment Summary</h3>
                                <div class="flex justify-between mb-4">
                                    <span>Total Price:</span>
                                    <span class="font-bold text-blue-600">$<?= number_format($booking['total_price'], 2) ?></span>
                                </div>
                                
                                <div class="space-y-3">
                                    <form action="payments.php" method="POST" class="w-full">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors">
                                            Pay Now
                                        </button>
                                    </form>
                                    
                                    <a href="manage-passengers.php?booking_id=<?= $booking['id'] ?>" class="block text-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-medium py-2 px-4 rounded transition-colors">
                                        <?php if ($remainingPassengers > 0): ?>
                                            Add Passenger Details
                                        <?php else: ?>
                                            Edit Passenger Details
                                        <?php endif; ?>
                                    </a>
                                    
                                    <!-- Add Cancel Booking Button -->
                                    <button onclick="confirmCancelBooking(<?= $booking['id'] ?>)" class="w-full bg-red-100 hover:bg-red-200 text-red-800 font-medium py-2 px-4 rounded transition-colors">
                                        Cancel Booking
                                    </button>
                                </div>
                                
                                <?php if ($booking['status'] === 'pending' && strtotime($booking['date']) < strtotime('+2 days')): ?>
                                    <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-md text-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Your flight is in less than 48 hours. Please complete payment soon.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Confirmed Bookings Section -->
        <?php if (!empty($categorizedBookings['confirmed'])): ?>
            <div class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Confirmed Bookings
                </h2>
                
                <?php foreach ($categorizedBookings['confirmed'] as $booking): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-l-4 border-green-500">
                        <div class="bg-green-50 p-4 flex justify-between items-center">
                            <div>
                                <span class="font-semibold text-gray-800">Booking #<?= htmlspecialchars($booking['id']) ?></span>
                                <span class="ml-2 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                    <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                </span>
                                
                                <?php if (isset($booking['real_time_status'])): ?>
                                    <span class="ml-2 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                        Flight: <?= ucfirst(htmlspecialchars($booking['real_time_status'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                Booked on <?= date('F j, Y', strtotime($booking['booking_date'])) ?>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
                            <!-- Flight Details -->
                            <div class="col-span-2">
                                <h3 class="font-semibold text-gray-800 mb-2 pb-2 border-b border-gray-200">
                                    Flight Details
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <p class="text-sm text-gray-600">Flight Number</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['flight_number'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Airline</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['airline'] ?? $booking['airline_name'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">From</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['departure'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">To</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['arrival'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Date</p>
                                        <p class="font-medium"><?= date('F j, Y', strtotime($booking['date'])) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Time</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['time'] ?? 'N/A') ?></p>
                                    </div>
                                    
                                    <?php if (!empty($booking['departure_terminal']) || !empty($booking['departure_gate'])): ?>
                                    <div>
                                        <p class="text-sm text-gray-600">Departure Details</p>
                                        <p class="font-medium">
                                            <?php
                                            $details = [];
                                            if (!empty($booking['departure_terminal'])) $details[] = "Terminal: " . htmlspecialchars($booking['departure_terminal']);
                                            if (!empty($booking['departure_gate'])) $details[] = "Gate: " . htmlspecialchars($booking['departure_gate']);
                                            echo implode(' | ', $details);
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($booking['arrival_terminal']) || !empty($booking['arrival_gate'])): ?>
                                    <div>
                                        <p class="text-sm text-gray-600">Arrival Details</p>
                                        <p class="font-medium">
                                            <?php
                                            $details = [];
                                            if (!empty($booking['arrival_terminal'])) $details[] = "Terminal: " . htmlspecialchars($booking['arrival_terminal']);
                                            if (!empty($booking['arrival_gate'])) $details[] = "Gate: " . htmlspecialchars($booking['arrival_gate']);
                                            echo implode(' | ', $details);
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <h3 class="font-semibold text-gray-800 mb-2 pb-2 border-b border-gray-200 mt-4">
                                    Passenger Information
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <p class="text-sm text-gray-600">Customer Name</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['customer_name']) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Email</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['customer_email']) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Phone</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['customer_phone']) ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Passengers</p>
                                        <p class="font-medium"><?= htmlspecialchars($booking['passengers']) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment & Actions -->
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h3 class="font-semibold text-gray-800 mb-3">Payment Information</h3>
                                
                                <?php 
                                $payment = $bookingManager->getBookingPayment($booking['id']);
                                ?>
                                
                                <?php if ($payment): ?>
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Amount Paid:</span>
                                        <span class="font-medium">$<?= number_format($payment['amount'], 2) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Payment Date:</span>
                                        <span class="font-medium"><?= date('F j, Y', strtotime($payment['payment_date'])) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Payment Method:</span>
                                        <span class="font-medium"><?= htmlspecialchars(ucfirst($payment['payment_method'])) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Transaction ID:</span>
                                        <span class="font-medium text-xs"><?= htmlspecialchars($payment['transaction_id']) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <?php
                                    // Check remaining passengers to be registered
                                    $remainingPassengers = $bookingManager->getRemainingPassengersCount($booking['id'], $booking['passengers']);
                                    ?>
                                    
                                    <a href="manage-passengers.php?booking_id=<?= $booking['id'] ?>" class="block text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors mb-3">
                                        <?php if ($remainingPassengers > 0): ?>
                                            Add Passenger Details (<?= $remainingPassengers ?> remaining)
                                        <?php else: ?>
                                            View/Edit Passenger Details
                                        <?php endif; ?>
                                    </a>
                                    
                                    <a href="confirmation.php?booking_id=<?= $booking['id'] ?>" class="block text-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-medium py-2 px-4 rounded transition-colors">
                                        View Booking Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Past Flights Section -->
        <?php if (!empty($categorizedBookings['past'])): ?>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Past Flights
                </h2>
                
                <?php foreach ($categorizedBookings['past'] as $booking): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-l-4 border-gray-400">
                        <div class="bg-gray-50 p-4 flex justify-between items-center">
                            <div>
                                <span class="font-semibold text-gray-800">Booking #<?= htmlspecialchars($booking['id']) ?></span>
                                <span class="ml-2 px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm">
                                    Past Flight
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Traveled on <?= date('F j, Y', strtotime($booking['date'])) ?>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="mb-4 md:mb-0">
                                    <h3 class="font-medium text-lg text-gray-800">
                                        <?= htmlspecialchars($booking['departure']) ?> → <?= htmlspecialchars($booking['arrival']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        <?= htmlspecialchars($booking['airline'] ?? $booking['airline_name'] ?? 'Unknown Airline') ?> • 
                                        Flight <?= htmlspecialchars($booking['flight_number']) ?>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?= date('F j, Y', strtotime($booking['date'])) ?> • 
                                        <?= htmlspecialchars($booking['time']) ?>
                                    </p>
                                </div>
                                
                                <div class="flex flex-col items-end">
                                    <div class="text-sm text-gray-600 mb-2">
                                        <?= htmlspecialchars($booking['passengers']) ?> passenger(s)
                                    </div>
                                    
                                    <a href="confirmation.php?booking_id=<?= $booking['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                        <span>View Details</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function confirmCancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        // User confirmed, proceed with cancellation
        fetch('cancel-booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Reload the page to show updated status
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the booking. Please try again.');
        });
    }
}
</script>

<?php include 'templates/footer.php'; ?>