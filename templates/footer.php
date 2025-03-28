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
            <div>
                <h3 class="font-medium text-gray-900 mb-4">Newsletter</h3>
                <p class="text-gray-600 text-sm mb-4">Subscribe to get special offers and travel updates.</p>
                <form class="flex space-x-2">
                    <input
                        type="email"
                        placeholder="Your email"
                        class="flex h-10 max-w-[200px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500" />
                    <button type="submit" class="inline-flex items-center justify-center gap-2 h-10 rounded-md px-3 bg-sky-600 text-white hover:bg-sky-700 text-sm font-medium transition-colors duration-300">
                        <i class="fa fa-paper-plane h-4 w-4"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="py-6 border-t border-gray-200 text-center">
            <p class="text-gray-600 text-sm">
                &copy; <?php echo getCurrentYear(); ?> Sky International Travels. All rights reserved.
            </p>
        </div>
    </div>
</footer>