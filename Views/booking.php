<?php
session_start();
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../classes/Flight.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/ApiClient.php'; 
// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'booking.php';
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$flight = null;

// Check if there's a flight selection from search2.php
if (isset($_POST['select_flight']) && !empty($_POST['flight_id'])) {
    $_SESSION['selected_flight_id'] = $_POST['flight_id'];
    $_SESSION['selected_flight_price'] = $_POST['price'];
    $flightId = $_POST['flight_id'];
    $flightPrice = $_POST['price'];
} 
// If no direct POST, check if we have stored flight data in session
else if (isset($_SESSION['selected_flight_id'])) {
    $flightId = $_SESSION['selected_flight_id'];
    $flightPrice = $_SESSION['selected_flight_price'];
} 
// No flight selected, redirect to search
else {
    header('Location: search2.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Validate form data
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || 
        empty($_POST['phone']) || empty($_POST['nationality'])) {
        $error = "Please fill in all required fields.";
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Get flight details
            $apiClient = new ApiClient();
            $flightData = $apiClient->getFlightById($flightId);
            
            if ($flightData) {
                // Create a Flight object with the API data
                $flight = new Flight(
                    $flightData['flight_number'],
                    $flightData['departure'],
                    $flightData['arrival'],
                    $flightData['duration'] ?? 0,
                    $flightData['price']
                );
                
                // Set additional flight properties
                $flight->setFromArray([
                    'date' => $flightData['date'],
                    'time' => $flightData['time'],
                    'available_seats' => $flightData['available_seats'] ?? 100,
                    'airline' => $flightData['airline'] ?? '',
                    'status' => $flightData['status'] ?? 'scheduled',
                    'departure_terminal' => $flightData['departure_terminal'] ?? null,
                    'departure_gate' => $flightData['departure_gate'] ?? null,
                    'arrival_terminal' => $flightData['arrival_terminal'] ?? null,
                    'arrival_gate' => $flightData['arrival_gate'] ?? null,
                    'flight_api' => $flightId
                ]);
                
                // Save flight to database
                $localFlightId = $flight->save();
                
                if ($localFlightId) {
                    // Update available seats
                    $flight->updateSeats($_POST['passengers']);
                    
                    // Prepare booking details
                    $bookingDetails = [
                        'first_name' => $_POST['first_name'],
                        'last_name' => $_POST['last_name'],
                        'email' => $_POST['email'],
                        'phone' => $_POST['phone'],
                        'nationality' => $_POST['nationality'],
                        'passengers' => (int)$_POST['passengers'],
                        'flight_api' => $flightId,
                        'special_requirements' => $_POST['special_requirements'] ?? ''
                    ];
                    
                    // Create the booking
                    $bookingObj = new Booking();
                    
                    // Calculate total price
                    $totalPrice = $flightPrice * $bookingDetails['passengers'];
                    
                    // Create booking record
                    $newBookingId = $bookingObj->createBooking(
                        $userId, 
                        $localFlightId, 
                        [
                            'name' => $bookingDetails['first_name'] . ' ' . $bookingDetails['last_name'],
                            'email' => $bookingDetails['email'],
                            'phone' => $bookingDetails['phone'],
                            'passengers' => $bookingDetails['passengers'],
                            'flight_api' => $bookingDetails['flight_api'],
                            'price' => $flightPrice
                        ],
                        'pending'
                    );
                    
                    if ($newBookingId) {
                        // Store booking data in session
                        $_SESSION['booking_id'] = $newBookingId;
                        $_SESSION['booking_data'] = [
                            'flight_id' => $localFlightId,
                            'flight_api' => $flightId,
                            'total_price' => $totalPrice,
                            'customer_name' => $bookingDetails['first_name'] . ' ' . $bookingDetails['last_name'],
                            'flight_number' => $flightData['flight_number'],
                            'departure' => $flightData['departure'],
                            'arrival' => $flightData['arrival'],
                            'date' => $flightData['date'],
                            'time' => $flightData['time']
                        ];
                        
                        // Redirect to confirm booking page
                        header("Location: confirmbooking.php");
                        exit;
                    } else {
                        $error = "Failed to create booking. Please try again.";
                    }
                } else {
                    $error = "Failed to save flight data. Please try again.";
                }
            } else {
                $error = "Could not retrieve flight details. Please try again.";
            }
        } catch (Exception $e) {
            $error = "An error occurred while processing your booking: " . $e->getMessage();
            error_log("Booking error: " . $e->getMessage());
        }
    }
}

// Get flight details for display
try {
    $apiClient = new ApiClient();
    $flight = $apiClient->getFlightById($flightId);
    
    if (!$flight) {
        $error = "Selected flight not found. Please try again.";
        header('Location: search2.php');
        exit;
    }
} catch (Exception $e) {
    $error = "Error retrieving flight details: " . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Book Your Flight</h1>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md mb-8 p-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Flight Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-gray-600 font-medium">Flight Number:</span>
                    <span class="ml-2 text-gray-800"><?= htmlspecialchars($flight['flight_number'] ?? 'N/A') ?></span>
                </div>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-gray-600 font-medium">Date:</span>
                    <span class="ml-2 text-gray-800"><?= htmlspecialchars(date('F j, Y', strtotime($flight['date']))) ?></span>
                </div>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-gray-600 font-medium">Time:</span>
                    <span class="ml-2 text-gray-800"><?= htmlspecialchars($flight['time'] ?? 'N/A') ?></span>
                </div>
                <?php if (!empty($flight['airline'])): ?>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                    </svg>
                    <span class="text-gray-600 font-medium">Airline:</span>
                    <span class="ml-2 text-gray-800"><?= htmlspecialchars($flight['airline']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-gray-600 font-medium">From:</span>
                    <span class="ml-2 text-gray-800"><?= htmlspecialchars($flight['departure'] ?? 'N/A') ?></span>
                </div>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-gray-600 font-medium">To:</span>
                    <span class="ml-2 text-gray-800"><?= htmlspecialchars($flight['arrival'] ?? 'N/A') ?></span>
                </div>
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-gray-600 font-medium">Price:</span>
                    <span class="ml-2 font-bold text-blue-600">$<?= htmlspecialchars(number_format($flightPrice, 2)) ?></span>
                    <span class="ml-1 text-gray-500 text-sm">per person</span>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <div class="bg-blue-50 p-4 rounded-md mb-6 flex items-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-blue-700">
                    Please enter the passenger details below. All fields marked with * are required.
                </span>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">Passenger Details</h2>
        
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="nationality" class="block text-sm font-medium text-gray-700 mb-1">Nationality *</label>
                    <input type="text" id="nationality" name="nationality" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="passengers" class="block text-sm font-medium text-gray-700 mb-1">Number of Passengers *</label>
                    <select id="passengers" name="passengers" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="updateTotalPrice()">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label for="special_requirements" class="block text-sm font-medium text-gray-700 mb-1">Special Requirements</label>
                    <textarea id="special_requirements" name="special_requirements" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Any dietary restrictions, accessibility needs, etc."></textarea>
                </div>
            </div>
            
            <div class="mt-8 bg-gray-50 p-4 rounded-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-700 font-medium">Total Price:</p>
                        <p class="text-sm text-gray-500">Price per passenger: $<?= number_format($flightPrice, 2) ?></p>
                    </div>
                    <div>
                        <span id="total-price" class="text-2xl font-bold text-blue-600">$<?= number_format($flightPrice, 2) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <a href="search2.php" class="px-6 py-2 mr-4 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" name="submit_booking" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Continue to Booking
                </button>
            </div>
            
            <input type="hidden" name="flight_id" value="<?= htmlspecialchars($flightId) ?>">
            <input type="hidden" name="price" value="<?= htmlspecialchars($flightPrice) ?>">
        </form>
    </div>
</div>

<script>
    function updateTotalPrice() {
        const passengers = document.getElementById('passengers').value;
        const pricePerPassenger = <?= $flightPrice ?>;
        const totalPrice = passengers * pricePerPassenger;
        document.getElementById('total-price').textContent = '$' + totalPrice.toFixed(2);
    }
</script>

<?php include 'templates/footer.php'; ?>