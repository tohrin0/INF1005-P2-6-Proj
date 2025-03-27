<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/User.php';
require_once '../inc/session.php';

verifyAdminSession();

// Initialize User class with the database connection
$userObj = new User($pdo);

// Get current admin's user ID
$currentAdminId = $_SESSION['user_id'];

// Get users with optional filtering
$role = isset($_GET['role']) ? $_GET['role'] : 'all';
$users = ($role === 'all') ? $userObj->getAllUsers() : $userObj->getUsersByRole($role);

// Handle user actions if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        
        // Prevent admin from deleting their own account
        if ($_POST['action'] === 'delete' && $userId == $currentAdminId) {
            $_SESSION['admin_error'] = 'You cannot delete your own account.';
            header('Location: users.php');
            exit();
        }
        
        if ($_POST['action'] === 'delete') {
            // Delete user logic
            if ($userObj->deleteUser($userId)) {
                $_SESSION['admin_message'] = 'User deleted successfully.';
            } else {
                $_SESSION['admin_error'] = 'Cannot delete the last administrator account.';
            }
            header('Location: users.php');
            exit();
        } elseif ($_POST['action'] === 'change_role') {
            // Change user role logic
            $newRole = $_POST['new_role'];
            if ($userObj->changeUserRole($userId, $newRole)) {
                $_SESSION['admin_message'] = 'User role updated successfully.';
            } else {
                $_SESSION['admin_error'] = 'Cannot change role of the last administrator account.';
            }
            header('Location: users.php');
            exit();
        }
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Manage Users</h1>
        <p class="text-gray-600">View and manage user accounts</p>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($_SESSION['admin_message']); ?></p>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($_SESSION['admin_error']); ?></p>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>
    
    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">User Accounts</h2>
                <div class="flex space-x-2">
                    <a href="?role=all" class="px-3 py-1 rounded-full text-sm <?php echo $role === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                        All Users
                    </a>
                    <a href="?role=admin" class="px-3 py-1 rounded-full text-sm <?php echo $role === 'admin' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                        Admins
                    </a>
                    <a href="?role=user" class="px-3 py-1 rounded-full text-sm <?php echo $role === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                        Regular Users
                    </a>
                    <a href="add-user.php" class="px-3 py-1.5 rounded-md text-sm bg-green-600 text-white hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-1"></i> Add User
                    </a>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 font-bold">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $currentAdminId): ?>
                                            <button onclick="changeRole(<?php echo $user['id']; ?>, '<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>')" class="text-purple-600 hover:text-purple-900" title="<?php echo $user['role'] === 'admin' ? 'Make Standard User' : 'Make Administrator'; ?>">
                                                <i class="fas fa-user-shield"></i>
                                            </button>
                                            <button onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-red-600 hover:text-red-900" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 cursor-not-allowed" title="Cannot modify your own account">
                                                <i class="fas fa-user-shield"></i>
                                            </span>
                                            <span class="text-gray-400 cursor-not-allowed" title="Cannot delete your own account">
                                                <i class="fas fa-trash"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Hidden forms for post actions -->
    <form id="deleteUserForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>
    
    <form id="changeRoleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="change_role">
        <input type="hidden" name="user_id" id="changeRoleUserId">
        <input type="hidden" name="new_role" id="newUserRole">
    </form>
</div>

<script>
function confirmDeleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete the user "${username}"? This action cannot be undone.`)) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserForm').submit();
    }
}

function changeRole(userId, newRole) {
    const roleName = newRole === 'admin' ? 'Administrator' : 'Standard User';
    
    if (confirm(`Are you sure you want to change this user's role to ${roleName}?`)) {
        document.getElementById('changeRoleUserId').value = userId;
        document.getElementById('newUserRole').value = newRole;
        document.getElementById('changeRoleForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>