<?php

/**
 * Footer Component for PHP
 * Converted from React/TSX component
 */

// Function to get the current year
function getCurrentYear()
{
    return date('Y');
}
?>

<!-- Footer Component -->
<footer class="bg-gray-50 border-t border-gray-200">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        <div class="py-8 sm:py-12 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 md:gap-8">
            <!-- Column 1 - About -->
            <div class="space-y-4 flex flex-col items-center sm:items-start">
                <!-- Logo -->
                <div class="flex justify-center sm:justify-start">
                    <img src="assets/images/horizontal.svg" alt="Sky International Travels Logo" class="h-auto sm:h-auto w-auto max-w-[200px] sm:max-w-[240px]" />
                </div>
                <div class="flex gap-4 pt-2">
                    <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-sky-100 hover:text-sky-600 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-sky-100 hover:text-sky-600 transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-sky-100 hover:text-sky-600 transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>

            <!-- Column 2 - Quick Links -->
            <div class="mt-6 sm:mt-0 text-center sm:text-left">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Links</h3>
                <ul class="space-y-2.5">
                    <li>
                        <a href="index.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors inline-flex items-center justify-center sm:justify-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="search2.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors inline-flex items-center justify-center sm:justify-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Search Flights
                        </a>
                    </li>
                    <li>
                        <a href="globe.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors inline-flex items-center justify-center sm:justify-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Destinations
                        </a>
                    </li>
                    <li>
                        <a href="my-bookings.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors inline-flex items-center justify-center sm:justify-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            My Bookings
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors inline-flex items-center justify-center sm:justify-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Contact Us
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Column 3 - Contact -->
            <div class="mt-6 sm:mt-0 sm:col-span-2 md:col-span-1">
                <h3 class="text-lg font-medium text-gray-900 mb-4 text-center sm:text-left">Contact Information</h3>
                <ul class="space-y-3">
                    <li class="flex items-start justify-center sm:justify-start">
                        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-sky-100 text-sky-600 mr-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <span class="text-gray-600 text-sm pt-1.5">123 Airport Road, City, Country</span>
                    </li>
                    <li class="flex items-start justify-center sm:justify-start">
                        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-sky-100 text-sky-600 mr-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <span class="text-gray-600 text-sm pt-1.5">+1 (234) 567-8900</span>
                    </li>
                    <li class="flex items-start justify-center sm:justify-start">
                        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-sky-100 text-sky-600 mr-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="text-gray-600 text-sm pt-1.5">info@skyinternational.com</span>
                    </li>
                </ul>
            </div>

            <!-- Column 4 - UTC Clock -->
            <div class="mt-8 sm:mt-0 sm:col-span-2 md:col-span-1 flex flex-col items-center md:items-start space-y-4">
                <h3 class="text-lg font-medium text-gray-900 text-center md:text-left">Current UTC Time</h3>
                <div class="flex items-center justify-center bg-white rounded-lg shadow-sm p-3 border border-gray-200 w-full max-w-[240px]">
                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-sky-100 text-sky-600 mr-3">
                        <i class="far fa-clock"></i>
                    </div>
                    <div>
                        <div id="utc-clock" class="text-xl font-mono font-bold text-gray-900"></div>
                        <div class="text-xs text-gray-500">Coordinated Universal Time</div>
                    </div>
                </div>
                <p class="text-gray-500 text-xs italic text-center md:text-left max-w-[240px]">
                    All flight times displayed across the website are in UTC timezone
                </p>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="py-5 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <p class="text-gray-600 text-sm text-center sm:text-left">
                &copy; <?php echo getCurrentYear(); ?> Sky International Travels. All rights reserved.
            </p>
            <div class="flex flex-wrap justify-center gap-4 sm:gap-x-4 text-xs text-gray-500 px-4 sm:px-0">
                <a href="#" class="hover:text-sky-600 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-sky-600 transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-sky-600 transition-colors">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript for UTC Clock -->
<script>
    function updateUTCClock() {
        const now = new Date();
        const utcTimeString = now.toUTCString();
        // Format: extract just the time portion (removes day and date)
        const timeOnly = utcTimeString.split(' ')[4];
        document.getElementById('utc-clock').textContent = timeOnly;
    }

    // Update immediately on page load
    updateUTCClock();

    // Then update every second
    setInterval(updateUTCClock, 1000);
</script>