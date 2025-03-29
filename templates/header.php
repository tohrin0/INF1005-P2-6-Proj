<?php
// Security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sky International Travels</title>
    <!-- Only include Tailwind CSS -->
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
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

        /* SkyBooker Chat Styles */
        .sk-chat-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        /* Chat Button */
        .sk-chat-button {
            background-color: #2563EB;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .sk-chat-button:hover {
            background-color: #1D4ED8;
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        /* Chat Window */
        .sk-chat-window {
            display: none;
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            flex-direction: column;
            animation: sk-slide-up 0.3s ease;
        }

        @keyframes sk-slide-up {
            from {
            opacity: 0;
            transform: translateY(20px);
            }
            to {
            opacity: 1;
            transform: translateY(0);
            }
        }

        /* Chat Header */
        .sk-chat-header {
            background: linear-gradient(to right, #2563EB, #3B82F6);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sk-chat-header-left {
            display: flex;
            align-items: center;
        }

        .sk-chat-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .sk-chat-header-info {
            display: flex;
            flex-direction: column;
        }

        .sk-chat-header-title {
            font-weight: 600;
            font-size: 16px;
        }

        .sk-chat-header-status {
            font-size: 12px;
            opacity: 0.8;
        }

        .sk-chat-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .sk-chat-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Messages Area */
        .sk-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: calc(100% - 160px);
            background-color: #F9FAFB;
        }

        .sk-message {
            display: flex;
            flex-direction: column;
            max-width: 80%;
        }

        .sk-message-user {
            align-self: flex-end;
        }

        .sk-message-assistant {
            align-self: flex-start;
        }

        .sk-message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            animation: sk-fade-in 0.3s ease;
        }

        @keyframes sk-fade-in {
            from {
            opacity: 0;
            }
            to {
            opacity: 1;
            }
        }

        .sk-message-user .sk-message-bubble {
            background-color: #2563EB;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .sk-message-assistant .sk-message-bubble {
            background-color: white;
            color: #1F2937;
            border-bottom-left-radius: 4px;
        }

        .sk-message-bubble p {
            margin: 0;
            line-height: 1.5;
        }

        .sk-message-bubble a {
            color: #2563EB;
            text-decoration: underline;
            font-weight: 500;
        }

        .sk-message-user .sk-message-bubble a {
            color: white;
        }

        .sk-message-time {
            font-size: 11px;
            margin-top: 4px;
            opacity: 0.6;
            margin-left: 4px;
            margin-right: 4px;
        }

        /* Typing Indicator */
        .sk-typing-indicator {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 12px 16px;
            background-color: white;
            border-radius: 18px;
            max-width: fit-content;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            animation: sk-fade-in 0.3s ease;
        }

        .sk-typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin: 0 2px;
            background-color: #CBD5E1;
            display: inline-block;
            animation: sk-typing 1.4s infinite both;
        }

        .sk-typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .sk-typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes sk-typing {
            0%, 100% {
            transform: scale(0.7);
            opacity: 0.5;
            }
            50% {
            transform: scale(1);
            opacity: 1;
            }
        }

        /* Input Area */
        .sk-chat-input-container {
            border-top: 1px solid #E5E7EB;
            padding: 12px 16px;
            background-color: white;
        }

        .sk-chat-form {
            display: flex;
            gap: 8px;
        }

        .sk-chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .sk-chat-input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        .sk-chat-send {
            background-color: #2563EB;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sk-chat-send:hover {
            background-color: #1D4ED8;
        }

        .sk-chat-footer {
            text-align: center;
            font-size: 11px;
            color: #9CA3AF;
            margin-top: 8px;
        }

        /* Scrollbar Styling */
        .sk-chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .sk-chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .sk-chat-messages::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sk-chat-window {
            width: calc(100vw - 40px);
            height: 60vh;
            right: 20px;
            bottom: 80px;
            }
        }
    </style>
</head>


<body>
    <!-- Mobile menu overlay -->
    <div id="menu-overlay" class="menu-overlay"></div>

    <header class="sticky top-0 z-50 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center w-full px-6 py-3">
            <a href="index.php" class="flex items-center text-blue-500 font-bold hover:text-blue-600 transition-colors">
                <img src="assets/images/logo.svg" alt="Sky International Travels Logo" class="h-8 sm:h-10 w-auto" />
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="hidden sm:block ml-auto mr-4 text-sm font-medium bg-green-50 text-green-800 py-2 px-3 rounded border border-green-100">
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
                            <li><a href="admin/dashboard.php" class="inline-block py-2 px-3 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium transition-colors">Admin Dashboard</a></li>
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
    <div id="mobile-menu" class="md:hidden mobile-menu-closed fixed top-[56px] sm:top-[64px] left-0 right-0 z-50 bg-white shadow-md border-t border-gray-200">
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
            const menuOverlay = document.getElementById('menu-overlay');
            const menuIcon = mobileMenuButton.querySelector('svg');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    // Toggle classes for animation
                    if (mobileMenu.classList.contains('mobile-menu-closed')) {
                        // Open menu
                        mobileMenu.classList.remove('mobile-menu-closed');
                        mobileMenu.classList.add('mobile-menu-open');
                        menuOverlay.classList.add('active');
                        menuIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        `;
                        document.body.style.overflow = 'hidden'; // Prevent body scrolling
                    } else {
                        // Close menu
                        closeMenu();
                    }
                });

                // Close menu when clicking on overlay
                menuOverlay.addEventListener('click', function() {
                    closeMenu();
                });

                // Close mobile menu when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInside = mobileMenuButton.contains(event.target) ||
                        mobileMenu.contains(event.target) ||
                        menuOverlay.contains(event.target);

                    if (!isClickInside && mobileMenu.classList.contains('mobile-menu-open')) {
                        closeMenu();
                    }
                });

                // Function to close the menu
                function closeMenu() {
                    mobileMenu.classList.remove('mobile-menu-open');
                    mobileMenu.classList.add('mobile-menu-closed');
                    menuOverlay.classList.remove('active');
                    menuIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    `;
                    document.body.style.overflow = ''; // Restore body scrolling
                }
            }
        });
    </script>