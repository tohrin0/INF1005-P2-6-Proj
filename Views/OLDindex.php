<?php
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

include 'templates/header.php';
?>

<!-- Hero Section -->
<div class="relative bg-gradient-to-br from-indigo-900 to-blue-800 overflow-hidden">
    <!-- Background pattern -->
    <div class="absolute inset-0 opacity-20">
        <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path d="M0 0 L50 100 L100 0 Z" fill="white" />
        </svg>
    </div>
    <!-- Hero content -->
    <div class="container mx-auto px-4 py-24 relative z-10">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 tracking-tight animate-fade-in">
                Discover the World with Flight Booking
            </h1>
            <p class="text-xl text-blue-100 mb-8 animate-fade-in-delayed">
                Your journey begins with a single search. Find the best flights at competitive prices.
            </p>
            <a href="search.php" class="btn-primary animate-fade-in-delayed">
                <span>Start Your Journey</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>
</div>

<div class="bg-gray-50">
    <div class="container mx-auto px-4 py-12 md:py-24">
        <!-- Features Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">✈️</div>
                <h3 class="text-xl font-semibold mb-3">Global Destinations</h3>
                <p class="text-gray-600">Access flights to over 10,000 destinations worldwide with our comprehensive flight database.</p>
            </div>
            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">💰</div>
                <h3 class="text-xl font-semibold mb-3">Best Prices</h3>
                <p class="text-gray-600">Compare prices across multiple airlines to ensure you always get the best deal available.</p>
            </div>
            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">🔒</div>
                <h3 class="text-xl font-semibold mb-3">Secure Booking</h3>
                <p class="text-gray-600">Book with confidence using our secure payment system and receive instant confirmation.</p>
            </div>
        </div>

        <?php if ($isLoggedIn && !empty($flights)): ?>
            <!-- Personal Section for Logged-in Users -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-8 mb-16 border border-blue-100">
                <div class="max-w-3xl mx-auto">
                    <h2 class="text-2xl font-bold text-blue-900 mb-3">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p class="text-gray-700 mb-6">Welcome to our flight booking platform. Check out our popular flights below or search for specific routes.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="search.php" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Search Flights
                        </a>
                        <a href="my-bookings.php" class="btn-secondary">
                            My Bookings
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($flights)): ?>
                <!-- Popular Flights Section -->
                <div class="mb-20">
                    <h2 class="text-3xl font-bold text-center mb-10 text-gray-900">Popular Flights</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach (array_slice($flights, 0, 3) as $flight): ?>
                            <div class="flight-card">
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-sm font-semibold bg-blue-100 text-blue-800 py-1 px-3 rounded-full"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                        <span class="text-gray-600 text-sm"><?php echo htmlspecialchars($flight['airline'] ?? ''); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 mb-1">From</p>
                                            <p class="text-lg font-bold"><?php echo htmlspecialchars($flight['departure']); ?></p>
                                        </div>
                                        <div class="flex-1 px-4">
                                            <div class="relative h-px bg-gray-300 w-full">
                                                <div class="absolute -top-2 right-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 mb-1">To</p>
                                            <p class="text-lg font-bold"><?php echo htmlspecialchars($flight['arrival']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-4">
                                        <?php echo htmlspecialchars($flight['date']); ?> at <?php echo htmlspecialchars($flight['time']); ?>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="text-2xl font-bold text-blue-600">
                                            $<?php echo htmlspecialchars(number_format($flight['price'], 2)); ?>
                                        </div>
                                        <a href="search.php" class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                                            View details
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-10 text-center">
                        <a href="search.php" class="inline-flex items-center justify-center rounded-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-medium shadow-lg hover:shadow-xl transform transition-all duration-200 hover:-translate-y-1">
                            View All Flights
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- CTA Section for Non-logged-in Users -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl overflow-hidden shadow-lg mb-20">
                <div class="px-6 py-12 sm:px-12 sm:py-16 text-center text-white">
                    <h2 class="text-3xl font-bold mb-4">Ready to Book Your Next Trip?</h2>
                    <p class="text-lg mb-8 text-blue-100">Create an account or log in to get started with your flight booking.</p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="register.php" class="inline-flex items-center justify-center rounded-lg px-6 py-3 bg-white text-blue-600 font-medium hover:bg-blue-50 transition-colors shadow-md">
                            Create Account
                        </a>
                        <a href="login.php" class="inline-flex items-center justify-center rounded-lg px-6 py-3 bg-transparent border border-white text-white font-medium hover:bg-white/10 transition-colors">
                            Log In
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Testimonials Section -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-900">What Our Customers Say</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="text-amber-400 flex">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <blockquote class="text-gray-700 mb-4 italic">
                        "The booking process was incredibly easy and the prices were better than any other site I checked. Will definitely book through here again!"
                    </blockquote>
                    <div class="font-semibold text-gray-900">Sarah L.</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="text-amber-400 flex">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <blockquote class="text-gray-700 mb-4 italic">
                        "I needed to make a last minute change to my flight, and the customer service was fantastic. They helped me every step of the way."
                    </blockquote>
                    <div class="font-semibold text-gray-900">Michael T.</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="text-amber-400 flex">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <blockquote class="text-gray-700 mb-4 italic">
                        "I've been using this site for all my business trips. The interface is clean, and I can always find what I need quickly."
                    </blockquote>
                    <div class="font-semibold text-gray-900">Jennifer R.</div>
                </div>
            </div>
        </div>

        <!-- Partners Section (new) -->
        <div class="mb-20">
            <h2 class="text-2xl font-semibold text-center mb-8 text-gray-700">Trusted by Leading Airlines</h2>
            <div class="flex flex-wrap justify-center items-center gap-8 opacity-70">
                <div class="w-24 h-12 flex items-center justify-center">
                    <span class="text-xl font-bold text-gray-500">AirLineCo</span>
                </div>
                <div class="w-24 h-12 flex items-center justify-center">
                    <span class="text-xl font-bold text-gray-500">SkyHigh</span>
                </div>
                <div class="w-24 h-12 flex items-center justify-center">
                    <span class="text-xl font-bold text-gray-500">JetFast</span>
                </div>
                <div class="w-24 h-12 flex items-center justify-center">
                    <span class="text-xl font-bold text-gray-500">EagleAir</span>
                </div>
                <div class="w-24 h-12 flex items-center justify-center">
                    <span class="text-xl font-bold text-gray-500">CloudWay</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>