<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/User.php';

// Check if the user is logged in and has admin privileges
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get user ID from URL parameter
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: users.php');
    exit();
}

$error = '';
$success = '';

// Get user details
try {
    $userObj = new User($pdo);
    $user = $userObj->getUserById($userId);

    if (!$user) {
        $_SESSION['admin_error'] = "User not found.";
        header('Location: users.php');
        exit();
    }

    // Handle form submission for updating user
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'] ?? '';

        // Validate form data
        if (empty($username) || empty($email)) {
            $error = "Username and email are required fields.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } else {
            // Check if username or email already exists (excluding current user)
            $existingUser = $userObj->getUserByUsername($username);
            $existingEmail = $userObj->getUserByEmail($email);

            if ($existingUser && $existingUser['id'] != $userId) {
                $error = "Username already exists.";
            } else if ($existingEmail && $existingEmail['id'] != $userId) {
                $error = "Email address already exists.";
            } else {
                // Update user data
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'role' => $role
                ];

                // If password is provided, add it to the update data
                if (!empty($password)) {
                    $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                // Update user in database
                $result = $userObj->updateUser($userId, $userData);

                if ($result) {
                    $success = "User updated successfully.";
                    // Refresh user data
                    $user = $userObj->getUserById($userId);
                } else {
                    $error = "Failed to update user.";
                }
            }
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Get user's booking history
try {
    $stmt = $pdo->prepare("
        SELECT b.*, f.flight_number, f.departure, f.arrival
        FROM bookings b
        LEFT JOIN flights f ON b.flight_id = f.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bookings = [];
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit User</h1>
            <p class="text-gray-600">Update user account information</p>
        </div>
        <div>
            <a href="users.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors flex items-center">
                &larr; Back to Users
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Edit Form -->
        <div class="lg:col-span-2">
            <form method="POST" action="" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">User Information</h2>

                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            New Password <span class="text-gray-500 text-xs">(leave blank to keep unchanged)</span>
                        </label>
                        <input type="password" id="password" name="password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Created</label>
                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <?= date('F j, Y H:i', strtotime($user['created_at'])) ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <?= date('F j, Y H:i', strtotime($user['updated_at'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="users.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Update User
                    </button>
                </div>
            </form>
        </div>

        <!-- User Activity Sidebar -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">User Activity</h2>

                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium text-gray-700 mb-2">Recent Bookings</h3>

                        <?php if (!empty($bookings)): ?>
                            <div class="space-y-3">
                                <?php foreach ($bookings as $booking): ?>
                                    <div class="border border-gray-200 rounded p-3">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <span class="block font-medium">
                                                    <?= htmlspecialchars($booking['flight_number'] ?? 'N/A') ?>:
                                                    <?= htmlspecialchars($booking['departure'] ?? 'N/A') ?> →
                                                    <?= htmlspecialchars($booking['arrival'] ?? 'N/A') ?>
                                                </span>
                                                <span class="text-sm text-gray-500">
                                                    Booking #<?= htmlspecialchars($booking['id']) ?> ·
                                                    <?= date('M j, Y', strtotime($booking['booking_date'])) ?>
                                                </span>
                                            </div>
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                <?php if ($booking['status'] === 'confirmed'): ?>bg-green-100 text-green-800
                                                <?php elseif ($booking['status'] === 'pending'): ?>bg-yellow-100 text-yellow-800
                                                <?php elseif ($booking['status'] === 'canceled'): ?>bg-red-100 text-red-800
                                                <?php else: ?>bg-gray-100 text-gray-800<?php endif; ?>">
                                                <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                            </span>
                                        </div>
                                        <div class="mt-2 flex justify-end">
                                            <a href="view-booking.php?id=<?= $booking['id'] ?>" class="text-blue-600 text-sm hover:underline">
                                                View Details →
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <a href="bookings.php?user_id=<?= $userId ?>" class="text-blue-600 text-sm hover:underline">
                                    View All Bookings →
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 italic">No bookings found for this user.</p>
                        <?php endif; ?>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <h3 class="font-medium text-gray-700 mb-3">Actions</h3>
                        <div class="space-y-2">
                            <button type="button" onclick="resetPassword()" class="w-full text-left px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Send Password Reset Link
                            </button>

                            <form method="POST" action="users.php" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                                <button type="submit" class="w-full text-left px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-md transition-colors flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete User
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function resetPassword() {
        if (confirm('Send password reset email to this user?')) {
            // Add AJAX request to send reset email
            alert('Password reset link sent to user\'s email.');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>