<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/User.php';

// If user is already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// Create user object
$user = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Validate password strength
        list($isValidPassword, $passwordMessage) = validatePasswordStrength($password);
        if (!$isValidPassword) {
            $error = $passwordMessage;
        } else {
            // Register user
            if ($user->register($username, $password, $email)) {
                header("Location: login.php?success=Registration successful. Please log in.");
                exit;
            } else {
                $error = "Registration failed. Email or username may already exist.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Flight Booking</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
            <div class="md:flex">
                <div class="p-8 w-full">
                    <div class="uppercase tracking-wide text-sm text-indigo-600 font-semibold mb-1">Account</div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Create Account</h2>
                    <p class="text-gray-600 mb-6">Join us to start booking flights</p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle w-5 h-5"></i>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="space-y-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" id="username" name="username" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Choose a username" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Create a password" required>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters with 1 uppercase letter and 1 special character</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Confirm your password" required>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create Account
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-sm">
                                Already have an account?
                                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    Sign In
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Benefits Section -->
        <div class="max-w-md mx-auto mt-8 bg-white rounded-xl shadow-sm overflow-hidden md:max-w-2xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Account Benefits</h3>
                <div class="space-y-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-plane-departure text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Save your favorite flights and travel preferences
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Quick booking process with saved information
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tag text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Access to exclusive deals and promotions
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-history text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            View and manage your booking history
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>