<?php
// Security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:;");
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sky International Travels</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <style>
        /* Custom styles that can't be handled by Tailwind */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-menu-open {
            animation: fadeIn 0.3s ease forwards;
            max-height: 80vh;
            overflow-y: auto;
        }

        .mobile-menu-closed {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
        }

        /* Footer styles preserved */
        .site-footer {
            background-color: #1a2b49;
            color: #fff;
            padding: 30px 0 15px;
            margin-top: 30px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }

        /* Rest of footer styles... */
        /* ... existing footer styles ... */
    </style>
</head>


<body>
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center w-full px-6 py-3">
            <a href="index.php" class="flex items-center text-blue-500 font-bold hover:text-blue-600 transition-colors">
                <img src="assets/images/logo.svg" alt="Sky International Travels Logo" class="h-8 sm:h-10 w-auto" />
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="hidden sm:block ml-auto mr-4 text-sm font-medium bg-green-50 text-green-600 py-2 px-3 rounded border border-green-100">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!
                </div>
            <?php endif; ?>

            <!-- Mobile menu button -->
            <button id="mobile-menu-button" class="md:hidden flex items-center p-2 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none transition-colors" aria-label="Toggle menu">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Desktop navigation -->
            <nav class="hidden md:block">
                <ul class="flex gap-1">
                    <li><a href="globe.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">World Map</a></li>
                    <li><a href="search2.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Search Flights</a></li>
                    <li><a href="contact.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Contact</a></li>
                    <li><a href="my-bookings.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Bookings</a></li>
                    <?php if (isLoggedIn()) : ?>
                        <li><a href="membership.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Privileges & Miles</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="admin/dashboard.php" class="inline-block py-2 px-3 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-medium transition-colors">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="account.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">My Account</a></li>
                        <li><a href="logout.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Login</a></li>
                        <li><a href="register.php" class="inline-block py-2 px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-md text-sm font-medium transition-colors">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Mobile navigation -->
    <div id="mobile-menu" class="md:hidden mobile-menu-closed absolute top-[56px] sm:top-[64px] left-0 right-0 z-50 bg-white shadow-md border-t border-gray-200">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="py-3 text-center text-sm font-medium bg-green-50 text-green-600 border-l-2 border-green-500 border-b border-green-100">
                Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!
            </div>
        <?php endif; ?>
        <nav class="px-2 pt-1 pb-3">
            <ul class="flex flex-col">
                <li><a href="globe.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">World Map</a></li>
                <li><a href="search2.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Search Flights</a></li>
                <li><a href="contact.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Contact</a></li>
                <li><a href="my-bookings.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Bookings</a></li>
                <?php if (isLoggedIn()) : ?>
                    <li><a href="membership.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Privileges & Miles</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php" class="block py-2 px-4 rounded-md text-red-600 bg-red-50 hover:bg-red-100 border-l-2 border-red-500 transition-colors">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="account.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">My Account</a></li>
                    <li><a href="logout.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Login</a></li>
                    <li><a href="register.php" class="block py-2 px-4 rounded-md text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-l-2 border-transparent hover:border-blue-500 transition-colors">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Script for mobile menu toggle with improved animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const menuIcon = mobileMenuButton.querySelector('svg');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    // Toggle classes for animation
                    if (mobileMenu.classList.contains('mobile-menu-closed')) {
                        mobileMenu.classList.remove('mobile-menu-closed');
                        mobileMenu.classList.add('mobile-menu-open');
                        menuIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        `;
                    } else {
                        mobileMenu.classList.remove('mobile-menu-open');
                        mobileMenu.classList.add('mobile-menu-closed');
                        menuIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        `;
                    }
                });

                // Close mobile menu when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInside = mobileMenuButton.contains(event.target) || mobileMenu.contains(event.target);

                    if (!isClickInside && mobileMenu.classList.contains('mobile-menu-open')) {
                        mobileMenu.classList.remove('mobile-menu-open');
                        mobileMenu.classList.add('mobile-menu-closed');
                        menuIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        `;
                    }
                });
            }
        });
    </script>