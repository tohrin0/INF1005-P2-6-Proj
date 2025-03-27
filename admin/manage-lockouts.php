<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../inc/session.php';

verifyAdminSession();

// Handle actions (if any)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unlock_user']) && !empty($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        
        // Reset failed attempts and clear lockout
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?");
        if ($stmt->execute([$userId])) {
            $_SESSION['admin_message'] = "User account has been unlocked successfully.";
        } else {
            $_SESSION['admin_error'] = "Failed to unlock user account.";
        }
    } else if (isset($_POST['unblock_ip']) && !empty($_POST['ip_address'])) {
        $ipAddress = $_POST['ip_address'];
        
        // Reset IP rate limit
        $stmt = $pdo->prepare("DELETE FROM ip_rate_limits WHERE ip_address = ?");
        if ($stmt->execute([$ipAddress])) {
            $_SESSION['admin_message'] = "IP address has been unblocked successfully.";
        } else {
            $_SESSION['admin_error'] = "Failed to unblock IP address.";
        }
    }
    
    // Redirect to refresh the page
    header('Location: manage-lockouts.php');
    exit();
}

// Get currently locked accounts
$lockedUsersStmt = $pdo->query("
    SELECT id, username, email, failed_login_attempts, lockout_until 
    FROM users 
    WHERE lockout_until IS NOT NULL AND lockout_until > NOW()
    ORDER BY lockout_until DESC
");
$lockedUsers = $lockedUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recently locked accounts (expired within last 24 hours)
$recentlyLockedUsersStmt = $pdo->query("
    SELECT id, username, email, failed_login_attempts, lockout_until 
    FROM users 
    WHERE lockout_until IS NOT NULL 
    AND lockout_until <= NOW() 
    AND lockout_until > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND failed_login_attempts >= 5
    ORDER BY lockout_until DESC
");
$recentlyLockedUsers = $recentlyLockedUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Add recently locked users to display with a different status
foreach ($recentlyLockedUsers as &$user) {
    $user['status'] = 'Expired';
}
$allLockedUsers = array_merge($lockedUsers, $recentlyLockedUsers);

// Do similar for IP addresses
$blockedIpsStmt = $pdo->query("
    SELECT ip_address, attempts, first_attempt_at, blocked_until 
    FROM ip_rate_limits 
    WHERE blocked_until IS NOT NULL AND blocked_until > NOW()
    ORDER BY blocked_until DESC
");
$blockedIps = $blockedIpsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recently blocked IPs (expired within last 24 hours)
$recentlyBlockedIpsStmt = $pdo->query("
    SELECT ip_address, attempts, first_attempt_at, blocked_until 
    FROM ip_rate_limits 
    WHERE blocked_until IS NOT NULL 
    AND blocked_until <= NOW() 
    AND blocked_until > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY blocked_until DESC
");
$recentlyBlockedIps = $recentlyBlockedIpsStmt->fetchAll(PDO::FETCH_ASSOC);

// Add recently blocked IPs to display with a different status
foreach ($recentlyBlockedIps as &$ip) {
    $ip['status'] = 'Expired';
}
$allBlockedIps = array_merge($blockedIps, $recentlyBlockedIps);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Security Lockouts</h1>
        <p class="text-gray-600 mt-1">Manage account lockouts and IP address blocks</p>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800 border-l-4 border-green-500 flex items-center" role="alert">
            <span class="font-medium mr-2">Success!</span> <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-800 border-l-4 border-red-500 flex items-center" role="alert">
            <span class="font-medium mr-2">Error!</span> <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>
    
    <!-- Locked User Accounts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Locked User Accounts</h2>
            <p class="text-sm text-gray-600">Users who are temporarily locked out due to failed login attempts</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed Attempts</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locked Until</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($allLockedUsers) === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-5 text-center text-gray-500">No locked user accounts</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allLockedUsers as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $user['failed_login_attempts']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y g:i a', strtotime($user['lockout_until'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to unlock this account?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="unlock_user" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            Unlock Account
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Blocked IP Addresses -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Blocked IP Addresses</h2>
            <p class="text-sm text-gray-600">IP addresses that are temporarily blocked due to excessive login attempts</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Attempt</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blocked Until</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($allBlockedIps) === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-5 text-center text-gray-500">No blocked IP addresses</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allBlockedIps as $ip): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($ip['ip_address']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $ip['attempts']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y g:i a', strtotime($ip['first_attempt_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y g:i a', strtotime($ip['blocked_until'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to unblock this IP address?');">
                                        <input type="hidden" name="ip_address" value="<?php echo htmlspecialchars($ip['ip_address']); ?>">
                                        <button type="submit" name="unblock_ip" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            Unblock IP
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>