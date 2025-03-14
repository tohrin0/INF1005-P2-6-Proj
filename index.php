<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch flight data from API if user is logged in
$flights = [];
if ($isLoggedIn) {
    try {
        $apiClient = new ApiClient();
        $flights = $apiClient->getFlightSchedules();

        // Debug the API response
        error_log("API getFlightSchedules returned: " . json_encode(array_slice($flights, 0, 2)));
    } catch (Exception $e) {
        error_log("Error fetching flights: " . $e->getMessage());
    }
}

// Add Tailwind CSS and Font Awesome to header
$additionalHeadContent = '
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
';

include 'templates/header.php';
?>

<!-- Hero Section -->
<div class="relative bg-primary isolate overflow-hidden">
    <div class="absolute inset-0">
        <img src="assets/images/hero-bg.jpg" alt="Hero background" class="h-full w-full object-cover opacity-30">
    </div>
    <div class="relative mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8 text-center">
        <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">Discover the World with Flight Booking</h1>
        <p class="mx-auto max-w-xl text-lg text-gray-300 mb-8">Your journey begins with a single search. Find the best flights at competitive prices.</p>
        <a href="search.php" class="inline-flex items-center justify-center rounded-md bg-secondary px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-secondary/90 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition duration-200">
            Start Your Journey
        </a>
    </div>
</div>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <!-- Features Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 my-12">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
            <div class="text-4xl mb-4">✈️</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Global Destinations</h3>
            <p class="text-gray-600">Access flights to over 10,000 destinations worldwide with our comprehensive flight database.</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
            <div class="text-4xl mb-4">💰</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Best Prices</h3>
            <p class="text-gray-600">Compare prices across multiple airlines to ensure you always get the best deal available.</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
            <div class="text-4xl mb-4">🔒</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure Booking</h3>
            <p class="text-gray-600">Book with confidence using our secure payment system and receive instant confirmation.</p>
        </div>
    </div>

    <?php if ($isLoggedIn && !empty($flights)): ?>
        <!-- Personal Section for Logged In Users -->
        <div class="bg-accent/10 rounded-lg p-6 mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p class="text-gray-600 mb-6">Welcome to our flight booking platform. Check out our popular flights below or search for specific routes.</p>
            <div class="flex flex-wrap gap-4">
                <a href="search.php" class="inline-flex items-center justify-center rounded-md bg-accent px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent/90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 transition duration-200">
                    Search Flights
                </a>
                <a href="my-bookings.php" class="inline-flex items-center justify-center rounded-md bg-white border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 transition duration-200">
                    My Bookings
                </a>
            </div>
        </div>

        <?php if (!empty($flights)): ?>
            <!-- Popular Flights Section -->
            <div class="mb-16">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Popular Flights</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php foreach (array_slice($flights, 0, 3) as $flight): ?>
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition duration-200">
                            <div class="flex justify-between items-center border-b p-4">
                                <span class="font-semibold"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                <span class="text-gray-500"><?php echo htmlspecialchars($flight['airline'] ?? ''); ?></span>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-center space-x-3 mb-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($flight['departure']); ?></div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                        <path d="M5 12h14"></path>
                                        <path d="M12 5l7 7-7 7"></path>
                                    </svg>
                                    <div class="font-medium"><?php echo htmlspecialchars($flight['arrival']); ?></div>
                                </div>
                                <div class="text-sm text-gray-500 mb-3 text-center">
                                    <?php echo htmlspecialchars($flight['date']); ?> at <?php echo htmlspecialchars($flight['time']); ?>
                                </div>
                                <div class="text-xl font-bold text-accent text-center mb-3">
                                    $<?php echo htmlspecialchars(number_format($flight['price'], 2)); ?>
                                </div>
                                <div class="text-sm text-gray-500 text-center">
                                    <i class="fas fa-info-circle"></i> View details on search page
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <a href="search.php" class="inline-flex items-center justify-center rounded-full px-6 py-3 bg-gradient-to-r from-accent to-secondary text-white font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-1 transition duration-300">
                        View All Flights
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- CTA Section for Non-Logged In Users -->
        <div class="bg-blue-50 rounded-lg p-8 text-center mb-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Ready to Book Your Next Trip?</h2>
            <p class="text-gray-600 mb-6">Create an account or log in to get started with your flight booking.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="register.php" class="inline-flex items-center justify-center rounded-md bg-secondary px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-secondary/90 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition duration-200">
                    Create Account
                </a>
                <a href="login.php" class="inline-flex items-center justify-center rounded-md bg-white border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition duration-200">
                    Log In
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Testimonials Section -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">What Our Customers Say</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="mb-4 text-gray-700 italic">
                    "The booking process was incredibly easy and the prices were better than any other site I checked. Will definitely book through here again!"
                </div>
                <div class="font-semibold text-gray-900">- Sarah L.</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="mb-4 text-gray-700 italic">
                    "I needed to make a last minute change to my flight, and the customer service was fantastic. They helped me every step of the way."
                </div>
                <div class="font-semibold text-gray-900">- Michael T.</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="mb-4 text-gray-700 italic">
                    "I've been using this site for all my business trips. The interface is clean, and I can always find what I need quickly."
                </div>
                <div class="font-semibold text-gray-900">- Jennifer R.</div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>