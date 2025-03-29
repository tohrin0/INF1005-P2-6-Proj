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
    <div class="admin-page-header">
        <h1>Manage Users</h1>
        <p>View and manage user accounts</p>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="admin-alert admin-alert-success">
            <p><?php echo htmlspecialchars($_SESSION['admin_message']); ?></p>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="admin-alert admin-alert-danger">
            <p><?php echo htmlspecialchars($_SESSION['admin_error']); ?></p>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>
    
    <!-- Users Table -->
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h2>User Accounts</h2>
            <div class="header-actions flex flex-wrap gap-2">
                <a href="?role=all" class="px-3 py-1 rounded-full text-sm <?php echo $role === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    All Users
                </a>
                <a href="?role=admin" class="px-3 py-1 rounded-full text-sm <?php echo $role === 'admin' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    Admins
                </a>
                <a href="?role=user" class="px-3 py-1 rounded-full text-sm <?php echo $role === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    Regular Users
                </a>
                <a href="add-user.php" class="px-3 py-1.5 rounded-md text-sm bg-green-600 text-white hover:bg-green-700 transition ml-auto">
                    <i class="fas fa-plus mr-1"></i> Add User
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Joined</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-badge-success">
                                        Active
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="admin-action-edit" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view-bookings.php?user_id=<?php echo $user['id']; ?>" class="admin-action-view" title="View Bookings">
                                            <i class="fas fa-ticket-alt"></i>
                                        </a>
                                        
                                        <?php if ($user['id'] != $currentAdminId): ?>
                                            <a href="#" onclick="changeRole(<?php echo $user['id']; ?>, '<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>'); return false;" 
                                               class="admin-action-edit" title="<?php echo $user['role'] === 'admin' ? 'Make Standard User' : 'Make Administrator'; ?>">
                                                <i class="fas fa-user-shield"></i>
                                            </a>
                                            
                                            <a href="#" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>'); return false;" 
                                               class="admin-action-delete" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 cursor-not-allowed px-2" title="Cannot modify your own account">
                                                <i class="fas fa-user-shield"></i>
                                            </span>
                                            <span class="text-gray-400 cursor-not-allowed px-2" title="Cannot delete your own account">
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
    const roleText = newRole === 'admin' ? 'administrator' : 'standard user';
    if (confirm(`Are you sure you want to change this user's role to ${roleText}?`)) {
        document.getElementById('changeRoleUserId').value = userId;
        document.getElementById('newUserRole').value = newRole;
        document.getElementById('changeRoleForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>