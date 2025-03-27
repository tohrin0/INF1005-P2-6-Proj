<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/User.php';
require_once '../inc/session.php';

verifyAdminSession();

// Get user ID from URL parameter
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: users.php');
    exit();
}

$error = '';
$success = '';

try {
    // Initialize User class
    $userObj = new User($pdo);
    $user = $userObj->getUserById($userId);
    
    if (!$user) {
        $_SESSION['admin_error'] = "User not found.";
        header('Location: users.php');
        exit();
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_user'])) {
            // Handle user deletion
            if ($userObj->deleteUser($userId)) {
                $_SESSION['admin_message'] = "User deleted successfully.";
                header('Location: users.php');
                exit();
            } else {
                $error = "Failed to delete user.";
            }
        } else {
            // Handle user update
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            // Validate input
            if (empty($username) || empty($email)) {
                $error = "Username and email are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } else {
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'role' => $role
                ];
                
                // Update user in database
                $result = $userObj->updateUser($userId, $userData);

                if ($result) {
                    $success = "User updated successfully.";
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
            <p class="text-gray-600">Update user information and manage account</p>
        </div>
        <div>
            <a href="users.php" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md transition-colors">
                Back to Users
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="max-w-4xl mx-auto mb-6 p-4 rounded-md border <?php echo ($_SESSION['flash_type'] ?? '') === 'success' ? 'bg-green-100 border-green-400' : 'bg-red-100 border-red-400'; ?>">
        <div class="flex items-center">
            <?php if (($_SESSION['flash_type'] ?? '') === 'success'): ?>
                <span class="mr-2 text-green-800">✔️</span>
            <?php else: ?>
                <span class="mr-2 text-red-800">❌</span>
            <?php endif; ?>
            <span class="<?php echo ($_SESSION['flash_type'] ?? '') === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            </span>
        </div>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Information Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">User Information</h2>
                    <form action="" method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select id="role" name="role" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                                <p class="px-3 py-2 border border-gray-200 bg-gray-50 rounded-md"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow transition-colors">
                                Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($bookings)): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Recent Bookings</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flight</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                            #<?php echo htmlspecialchars($booking['id']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($booking['flight_number'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php 
                                        $departure = htmlspecialchars($booking['departure'] ?? 'N/A');
                                        $arrival = htmlspecialchars($booking['arrival'] ?? 'N/A');
                                        echo "{$departure} → {$arrival}";
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            if ($booking['status'] === 'confirmed') {
                                                $statusClass = 'bg-green-100 text-green-800';
                                            } elseif ($booking['status'] === 'pending') {
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                            } elseif ($booking['status'] === 'cancelled' || $booking['status'] === 'canceled') {
                                                $statusClass = 'bg-red-100 text-red-800';
                                            }
                                            echo $statusClass;
                                            ?>">
                                            <?php echo ucfirst(htmlspecialchars($booking['status'] ?? 'Unknown')); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- User Actions Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">User Actions</h2>
                    <div class="space-y-3">
                        <!-- Password Reset Form -->
                        <form action="admin-reset-password.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                            <button type="submit" 
                                class="w-full text-left px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors flex items-center">
                                <span class="mr-2 text-gray-600 inline-block w-5 h-5">🔑</span>
                                Send Password Reset Link
                            </button>
                        </form>
                        <br>
                        <!-- 2FA Setup Form -->
                        <form action="admin-reset-2fa.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                            <button type="submit" 
                                class="w-full text-left px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors flex items-center">
                                <span class="mr-2 text-gray-600 inline-block w-5 h-5">🔒</span>
                                Send 2FA Reset Link
                            </button>
                        </form>
                        
                        <a href="view-bookings.php?user_id=<?php echo $userId; ?>" 
                            class="w-full text-left px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors flex items-center block">
                            <span class="mr-2 text-gray-600 inline-block w-5 h-5">📅</span>
                            View All Bookings
                        </a>
                        
                        <form action="" method="POST" 
                            onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                            <button type="submit" name="delete_user" 
                                class="w-full text-left px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-md transition-colors flex items-center">
                                <span class="mr-2 text-red-600 inline-block w-5 h-5">🗑️</span>
                                Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>