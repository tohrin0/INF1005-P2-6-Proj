<?php
// Include necessary files
include_once 'templates/header.php';
?>

<main class="flex min-h-screen flex-col">
    <div class="relative h-[600px] w-full overflow-hidden">
        <img
            src="assets/images/Plane1hero.jpg"
            alt="Airplane flying over a beautiful landscape"
            class="object-cover w-full h-full absolute inset-0"
            loading="eager" />
        <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-black/30 flex flex-col justify-center">
            <div class="container mx-auto px-4">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 max-w-2xl">
                    Discover the World with Our Best Flight Deals
                </h1>
                <p class="text-xl text-white/90 mb-8 max-w-xl">
                    Book your flights with confidence. Transparent pricing, no hidden fees, and 24/7 customer support.
                </p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 -mt-24 relative z-10 mb-16">
        <!-- Updated search form to match search2.php functionality -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form action="search2.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="departure" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="text" id="departure" name="departure" placeholder="City or Airport" required
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="arrival" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="text" id="arrival" name="arrival" placeholder="City or Airport" required
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="departDate" class="block text-sm font-medium text-gray-700 mb-1">Departure Date</label>
                    <input type="date" id="departDate" name="departDate" min="<?= date('Y-m-d') ?>" required
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="passengers" class="block text-sm font-medium text-gray-700 mb-1">Passengers</label>
                    <div class="flex gap-2">
                        <select id="adults" name="adults" class="flex-1 p-2 border border-gray-300 rounded-md">
                            <option value="1">1 Adult</option>
                            <option value="2">2 Adults</option>
                            <option value="3">3 Adults</option>
                            <option value="4">4 Adults</option>
                        </select>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-md">
                            Search
                        </button>
                    </div>
                </div>

                <input type="hidden" name="tripType" value="one-way">
                <input type="hidden" name="children" value="0">
                <input type="hidden" name="infants" value="0">
            </form>
        </div>
    </div>

    <!-- Update the destinations section with larger, rectangular cards -->
    <section class="container mx-auto px-4 py-12">
        <h2 class="text-3xl font-bold mb-8 text-center">Featured Destinations</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8"> <!-- Increased gap -->
            <?php
            $destinations = [
                ["name" => "New York", "image" => "/placeholder.svg?height=400&width=700", "price" => 349],
                ["name" => "London", "image" => "/placeholder.svg?height=400&width=700", "price" => 429],
                ["name" => "Paris", "image" => "/placeholder.svg?height=400&width=700", "price" => 399],
                ["name" => "Tokyo", "image" => "/placeholder.svg?height=400&width=700", "price" => 549],
                ["name" => "Sydney", "image" => "/placeholder.svg?height=400&width=700", "price" => 649],
                ["name" => "Dubai", "image" => "/placeholder.svg?height=400&width=700", "price" => 499]
            ];
            
            foreach ($destinations as $destination):
            ?>
                <a href="search2.php?departure=&arrival=<?= htmlspecialchars($destination['name']) ?>" class="group">
                    <div class="relative h-96 overflow-hidden rounded-xl"> <!-- Increased height from h-72 to h-96 -->
                        <img
                            src="<?= htmlspecialchars($destination['image']) ?>"
                            alt="<?= htmlspecialchars($destination['name']) ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-6">
                            <h3 class="text-2xl font-bold text-white"><?= htmlspecialchars($destination['name']) ?></h3> <!-- Increased text size -->
                            <div class="flex justify-between items-center mt-3"> <!-- Increased margin -->
                                <span class="text-white/80 text-lg">From</span> <!-- Increased text size -->
                                <span class="text-white font-bold text-xl">$<?= htmlspecialchars($destination['price']) ?></span> <!-- Increased text size -->
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-center mt-10"> <!-- Increased margin -->
            <a
                href="search2.php"
                class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white py-3 px-8 rounded-full transition-colors text-lg"> <!-- Increased padding and text size -->
                View All Destinations
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-5 w-5"> <!-- Increased icon size -->
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>
    </section>

    <section class="bg-gray-50 py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-12 text-center">Why Choose SkyBooker</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl p-8 shadow-sm text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-blue-600">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Best Price Guarantee</h3>
                    <p class="text-gray-600">
                        We compare prices from hundreds of airlines to ensure you get the best deal every time.
                    </p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-sm text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-blue-600">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Flexible Booking</h3>
                    <p class="text-gray-600">
                        Plans change? No problem. Enjoy free cancellation on select flights and easy rebooking options.
                    </p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-sm text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-blue-600">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">24/7 Support</h3>
                    <p class="text-gray-600">
                        Our customer service team is available around the clock to assist with any questions or concerns.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-6">Download Our Mobile App</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Take SkyBooker with you wherever you go. Book flights, check in, receive flight alerts, and access your
                    boarding pass—all from your mobile device.
                </p>
                <ul class="space-y-4 mb-8">
                    <?php
                    $features = [
                        "Book flights in just a few taps",
                        "Get real-time flight notifications",
                        "Access mobile boarding passes",
                        "Manage your bookings on the go"
                    ];

                    foreach ($features as $feature):
                    ?>
                        <li class="flex items-start">
                            <div class="bg-blue-100 p-1 rounded-full mr-3 mt-1">
                                <?php echo renderCheck("h-4 w-4 text-blue-600"); ?>
                            </div>
                            <span><?= htmlspecialchars($feature) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="flex flex-wrap gap-4">
                    <a href="#" class="inline-block">
                        <img
                            src="/placeholder.svg?height=60&width=180"
                            alt="Download on the App Store"
                            width="180"
                            height="60"
                            class="rounded-lg" />
                    </a>
                    <a href="#" class="inline-block">
                        <img
                            src="/placeholder.svg?height=60&width=180"
                            alt="Get it on Google Play"
                            width="180"
                            height="60"
                            class="rounded-lg" />
                    </a>
                </div>
            </div>
            <div class="relative h-[500px]">
                <img
                    src="/placeholder.svg?height=1000&width=500"
                    alt="SkyBooker mobile app"
                    class="object-contain absolute inset-0 w-full h-full" />
            </div>
        </div>
    </section>

    <section class="bg-blue-600 text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6">Join Our Newsletter</h2>
            <p class="text-xl max-w-2xl mx-auto mb-8">
                Subscribe to our newsletter and be the first to know about exclusive deals, travel tips, and special offers.
            </p>
            <div class="max-w-md mx-auto">
                <form id="newsletter-form" class="flex flex-col gap-3">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input
                            type="text"
                            name="first_name"
                            placeholder="First Name (Optional)"
                            class="flex-1 px-4 py-3 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300" />
                        <input
                            type="email"
                            name="email"
                            placeholder="Enter your email address"
                            class="flex-1 px-4 py-3 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300"
                            required />
                    </div>
                    <button type="submit" class="bg-white text-blue-600 hover:bg-blue-50 px-6 py-3 rounded-lg font-medium transition-colors">
                        Subscribe
                    </button>
                </form>
                <p id="newsletter-message" class="hidden text-sm mt-4 text-blue-100"></p>
                <p class="text-sm mt-4 text-blue-100">
                    By subscribing, you agree to our <a href="privacy-policy.php" class="underline hover:text-white transition-colors">Privacy Policy</a> and consent to receive updates from Sky International Travels.
                </p>
            </div>
        </div>
    </section>
</main>

<?php
// Helper function for the check icon
function renderCheck($className = "")
{
    return '<svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="' . $className . '">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>';
}

include 'templates/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.getElementById('newsletter-form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[name="email"]');
            const submitButton = this.querySelector('button[type="submit"]');
            const nameInput = this.querySelector('input[name="first_name"]') || null;
            const messageElement = document.getElementById('newsletter-message');
            
            // Basic validation
            if (!emailInput.value.trim() || !emailInput.value.includes('@')) {
                showMessage('Please enter a valid email address.', 'error');
                return;
            }
            
            // Disable button and show loading state
            submitButton.disabled = true;
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Subscribing...';
            
            // Prepare data
            const formData = new FormData();
            formData.append('email', emailInput.value.trim());
            if (nameInput && nameInput.value) {
                formData.append('first_name', nameInput.value.trim());
            }
            
            // Send AJAX request
            fetch('api/subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Log the raw response for debugging
                console.log("Response status:", response.status, response.statusText);
                return response.text().then(text => {
                    // Try to parse as JSON, but handle text if not valid JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Failed to parse JSON:", text);
                        throw new Error("Invalid response format");
                    }
                });
            })
            .then(data => {
                // Reset form
                newsletterForm.reset();
                
                console.log("Response data:", data);
                
                // Show message
                if (data.success) {
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message || 'An error occurred', 'error');
                }
                
                // Restore button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again later.', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
            
            function showMessage(text, type) {
                if (messageElement) {
                    messageElement.textContent = text;
                    messageElement.className = '';
                    
                    if (type === 'success') {
                        messageElement.classList.add('text-green-200', 'bg-green-900/30', 'p-2', 'rounded', 'mt-2');
                    } else if (type === 'error') {
                        messageElement.classList.add('text-red-200', 'bg-red-900/30', 'p-2', 'rounded', 'mt-2');
                    }
                    
                    // Make the message visible
                    messageElement.classList.remove('hidden');
                    
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        messageElement.classList.add('hidden');
                    }, 5000);
                }
            }
        });
    }
});
</script>