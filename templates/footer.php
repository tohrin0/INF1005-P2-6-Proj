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
<footer class="bg-gray-50 border-t border-gray-200/80">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        <div class="py-12 grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Column 1 - About -->
            <div class="space-y-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <img src="assets/images/horizontal.svg" alt="Sky International Travels Logo" class="h-auto w-auto" />
                    <!-- <span class="ml-2 text-xl font-bold text-gray-900">Sky International Travels</span> -->
                </div>
                <!-- <p class="text-gray-600 text-sm">
                    Your trusted partner for booking flights worldwide. We provide
                    seamless booking experiences with the best prices.
                </p> -->
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-500 hover:text-sky-600 transition-colors duration-300">
                        <i class="fa fa-twitter fa-lg"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-sky-600 transition-colors duration-300">
                        <i class="fa fa-facebook fa-lg"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-sky-600 transition-colors duration-300">
                        <i class="fa fa-instagram fa-lg"></i>
                    </a>
                </div>
            </div>

            <!-- Column 2 - Quick Links -->
            <div>
                <h3 class="font-medium text-gray-900 mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors duration-300">Home</a>
                    </li>
                    <li>
                        <a href="search2.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors duration-300">Search Flights</a>
                    </li>
                    <li>
                        <a href="globe.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors duration-300">Destinations</a>
                    </li>
                    <li>
                        <a href="my-bookings.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors duration-300">My Bookings</a>
                    </li>
                    <li>
                        <a href="contact.php" class="text-gray-600 hover:text-sky-600 text-sm transition-colors duration-300">Contact Us</a>
                    </li>
                </ul>
            </div>

            <!-- Column 3 - Contact -->
            <div>
                <h3 class="font-medium text-gray-900 mb-4">Contact Information</h3>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <i class="fa fa-map-marker h-5 w-5 text-sky-600 mr-2 mt-0.5"></i>
                        <span class="text-gray-600 text-sm">123 Airport Road, City, Country</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa fa-phone h-5 w-5 text-sky-600 mr-2 mt-0.5"></i>
                        <span class="text-gray-600 text-sm">+1 (234) 567-8900</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa fa-envelope h-5 w-5 text-sky-600 mr-2 mt-0.5"></i>
                        <span class="text-gray-600 text-sm">info@skyinternational.com</span>
                    </li>
                </ul>
            </div>

            <!-- Column 4 - Newsletter -->
            

        <div class="py-6 border-t border-gray-200 text-center">
            <!-- UTC Clock with light box -->
            <div class="mb-3">
                <div class="inline-block bg-gray-100 px-4 py-2 rounded-md shadow-sm border border-gray-200">
                    <p class="text-gray-800 text-sm font-medium">
                        <i class="fa fa-clock-o text-sky-600 mr-1"></i>
                        <span id="utc-clock" class="font-mono text-gray-900 font-bold"></span> UTC
                    </p>
                </div>
                <p class="text-gray-500 text-xs mt-1 italic">
                    All flight times displayed across the website are in UTC timezone
                </p>
            </div>
            <p class="text-gray-600 text-sm">
                &copy; <?php echo getCurrentYear(); ?> Sky International Travels. All rights reserved.
            </p>
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