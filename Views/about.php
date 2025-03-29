<?php
require_once 'inc/session.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Flight Booking Website</title>
    
    
    <link rel="stylesheet" href="assets/css/tailwind.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-16 px-4 mb-8">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold mb-4">About Us</h1>
            <p class="text-xl text-blue-100">Learn about our story, mission, and the team behind Flight Booking</p>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="bg-white rounded-xl shadow-sm mb-12 p-8">
            <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-gray-200">Our Story</h2>
            <p class="text-gray-700 mb-4">
                Founded in 2020, Flight Booking started with a simple mission: to make air travel accessible, affordable, and hassle-free for everyone. What began as a small startup has grown into a trusted platform used by thousands of travelers worldwide.
            </p>
            <p class="text-gray-700 mb-4">
                Our founders recognized that booking flights online was often complicated and filled with hidden fees. They set out to create a transparent platform that prioritizes customer experience while offering competitive prices.
            </p>
            <p class="text-gray-700">
                Today, we continue to innovate and improve our services, partnering with major airlines around the globe to bring you the best flight options for your journey.
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm mb-12 p-8">
            <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-gray-200">Our Mission</h2>
            <p class="text-gray-700 mb-6">
                Our mission is to revolutionize the way people book flights by providing:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-blue-50 p-6 rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-search text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">Simplicity</h3>
                    <p class="text-gray-600">A user-friendly interface that makes finding and booking flights effortless for everyone.</p>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-dollar-sign text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">Transparency</h3>
                    <p class="text-gray-600">Clear pricing with no hidden fees, so you always know exactly what you're paying for.</p>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">Support</h3>
                    <p class="text-gray-600">Dedicated customer service to assist with any questions or concerns throughout your journey.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm mb-12 p-8">
            <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-gray-200">Our Team</h2>
            <p class="text-gray-700 mb-6 text-center">
                Meet the talented individuals who make Flight Booking possible.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Team Member 1 -->
                <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm">
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user-circle text-gray-400 text-6xl"></i>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-lg text-gray-800">Jane Smith</h3>
                        <p class="text-blue-600 mb-3 text-sm">CEO & Founder</p>
                        <p class="text-gray-600 text-sm">Passionate about making travel accessible to everyone.</p>
                    </div>
                </div>

                <!-- Team Member 2 -->
                <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm">
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user-circle text-gray-400 text-6xl"></i>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-lg text-gray-800">John Davis</h3>
                        <p class="text-blue-600 mb-3 text-sm">CTO</p>
                        <p class="text-gray-600 text-sm">Technology enthusiast with a focus on user experience.</p>
                    </div>
                </div>

                <!-- Team Member 3 -->
                <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm">
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user-circle text-gray-400 text-6xl"></i>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-lg text-gray-800">Sarah Johnson</h3>
                        <p class="text-blue-600 mb-3 text-sm">Head of Customer Relations</p>
                        <p class="text-gray-600 text-sm">Dedicated to providing exceptional customer service.</p>
                    </div>
                </div>

                <!-- Team Member 4 -->
                <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm">
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user-circle text-gray-400 text-6xl"></i>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-lg text-gray-800">Michael Chen</h3>
                        <p class="text-blue-600 mb-3 text-sm">Marketing Director</p>
                        <p class="text-gray-600 text-sm">Expert in creating engaging travel experiences.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>

</html>