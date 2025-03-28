<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/User.php';
require_once '../inc/session.php';

verifyAdminSession();

// Initialize User class
$userObj = new User($pdo);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    // Validate inputs
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } else {
        // Validate password strength
        list($isValid, $passwordError) = validatePasswordStrength($password);
        if (!$isValid) {
            $errors['password'] = $passwordError;
        }
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    if ($role !== 'user' && $role !== 'admin') {
        $errors['role'] = "Invalid role selected";
    }
    
    // Check if username already exists
    $existingUser = $userObj->getUserByUsername($username);
    if ($existingUser) {
        $errors['username'] = "Username already exists";
    }
    
    // Check if email already exists
    $existingEmail = $userObj->getUserByEmail($email);
    if ($existingEmail) {
        $errors['email'] = "Email address already in use";
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        if ($userObj->register($username, $password, $email)) {
            // Registration successful
            
            // If admin role was selected, update the role
            if ($role === 'admin') {
                $newUser = $userObj->getUserByUsername($username);
                if ($newUser) {
                    $userObj->changeUserRole($newUser['id'], 'admin');
                }
            }
            
            $success = true;
            
            // Clear form data after successful submission
            $username = $email = '';
        } else {
            $errors['general'] = "Failed to create user. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Add New User</h1>
            <p class="text-gray-600">Create a new user account with specified role and permissions</p>
        </div>
        <div>
            <a href="users.php" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Users
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3">
                    <p class="font-medium">User created successfully!</p>
                    <p class="text-sm mt-1">The new user account has been created and can now log in using the provided credentials.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errors['general'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                </div>
                <div class="ml-3">
                    <p class="font-medium"><?php echo htmlspecialchars($errors['general']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">User Information</h2>
        </div>
        
        <div class="p-6">
            <form method="POST" action="" class="space-y-6">
                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           class="w-full px-4 py-2.5 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter username">
                    <?php if (isset($errors['username'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['username']); ?></p>
                    <?php else: ?>
                        <p class="mt-1 text-sm text-gray-500">Username must be 3-20 characters and can only contain letters, numbers, and underscores.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           class="w-full px-4 py-2.5 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="user@example.com">
                    <?php if (isset($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['email']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Password Fields -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password"
                               class="w-full px-4 py-2.5 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Create a strong password">
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['password']); ?></p>
                        <?php else: ?>
                            <p class="mt-1 text-sm text-gray-500">Password must be at least 12 characters long and include uppercase, lowercase, numbers, and special characters.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="w-full px-4 py-2.5 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Confirm password">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['confirm_password']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">User Role <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <input type="radio" id="role_user" name="role" value="user" <?php echo (!isset($role) || $role === 'user') ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <label for="role_user" class="ml-2 block text-sm text-gray-700">
                                Regular User
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="role_admin" name="role" value="admin" <?php echo (isset($role) && $role === 'admin') ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <label for="role_admin" class="ml-2 block text-sm text-gray-700">
                                Administrator
                            </label>
                        </div>
                    </div>
                    <?php if (isset($errors['role'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['role']); ?></p>
                    <?php else: ?>
                        <p class="mt-1 text-sm text-gray-500">Administrators have full access to all admin features. Regular users can only manage their own bookings.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Additional Settings -->
                <div class="pt-4 border-t border-gray-200">
                    <h3 class="text-md font-medium text-gray-800 mb-3">Additional Options</h3>
                    <div class="flex flex-col space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" id="send_welcome_email" name="send_welcome_email" checked
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="send_welcome_email" class="ml-2 block text-sm text-gray-700">
                                Send welcome email with login credentials
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="require_password_change" name="require_password_change" checked
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="require_password_change" class="ml-2 block text-sm text-gray-700">
                                Require password change on first login
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="flex justify-end pt-4 border-t border-gray-200 space-x-3">
                    <a href="users.php" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Quick Help Card -->
    <div class="mt-8 bg-blue-50 rounded-xl shadow-sm border border-blue-200 p-6">
        <h3 class="text-lg font-medium text-blue-800 mb-3 flex items-center">
            <i class="fas fa-info-circle mr-2"></i> Quick Help
        </h3>
        <div class="text-blue-700 space-y-2">
            <p><strong>User Types:</strong> Regular users can only manage their own bookings, while administrators have access to the admin panel and all its features.</p>
            <p><strong>Password Security:</strong> Make sure to create strong, unique passwords for each user. Passwords should be at least 12 characters long and include a mix of uppercase and lowercase letters, numbers, and special characters.</p>
            <p><strong>Email Notifications:</strong> If you enable welcome emails, users will receive their login credentials via email. Ensure the email address is correct before creating the account.</p>
        </div>
    </div>
</div>

<script>
// JavaScript for password strength indicator (optional enhancement)
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const passwordConfirmField = document.getElementById('confirm_password');
    
    // Optional: Add password visibility toggle
    const togglePasswordVisibility = (inputField) => {
        const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
        inputField.setAttribute('type', type);
    };
    
    // Optional: Check password match in real-time
    if (passwordField && passwordConfirmField) {
        passwordConfirmField.addEventListener('input', function() {
            if (this.value !== passwordField.value) {
                this.classList.add('border-yellow-500');
                this.classList.remove('border-green-500');
            } else {
                this.classList.remove('border-yellow-500');
                this.classList.add('border-green-500');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>