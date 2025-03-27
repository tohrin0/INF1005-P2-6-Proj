<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../inc/session.php';

verifyAdminSession();

// Handle actions (if any)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_all') {
        // Clear all login attempts
        $stmt = $pdo->prepare("TRUNCATE TABLE login_attempts");
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "All login attempts cleared successfully.";
        } else {
            $_SESSION['admin_error'] = "Failed to clear login attempts.";
        }
        
        // Redirect to refresh the page
        header('Location: login-attempts.php');
        exit();
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

// Prepare the query
$query = "SELECT * FROM login_attempts WHERE 1=1";
$params = [];

// Limit by time
if ($days > 0) {
    $query .= " AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = $days;
}

if ($status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (email LIKE ? OR ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY attempt_time DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = str_replace("SELECT *", "SELECT COUNT(*)", $query);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Add limit to the query using integer interpolation for LIMIT clause
$offset = (int)$offset; // Ensure it's an integer
$perPage = (int)$perPage; // Ensure it's an integer
$query .= " LIMIT $offset, $perPage"; // Directly include integers in query

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params); // params doesn't include limit values anymore
$loginAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$statsStmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'failure' THEN 1 ELSE 0 END) as failed
    FROM login_attempts 
    WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get IP with most failures
$ipStmt = $pdo->query("SELECT 
    ip_address, 
    COUNT(*) as attempts
    FROM login_attempts 
    WHERE status = 'failure' AND attempt_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY ip_address
    ORDER BY attempts DESC
    LIMIT 1");
$mostFailedIP = $ipStmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Login Attempts</h1>
            <p class="text-gray-600 mt-1">Monitor and manage user login activity</p>
        </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all login attempts? This action cannot be undone.');">
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="flex items-center px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 shadow-sm">
                Clear All Records
            </button>
        </form>
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
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all hover:shadow-md">
            <div class="flex items-center">
                <div class="flex-1">
                    <h2 class="text-gray-500 text-sm font-medium mb-2">Total Attempts (7 days)</h2>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['total'] ?? 0); ?></p>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
                    <span class="text-blue-600 text-xl">🔍</span>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all hover:shadow-md">
            <div class="flex items-center">
                <div class="flex-1">
                    <h2 class="text-gray-500 text-sm font-medium mb-2">Successful Logins</h2>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['successful'] ?? 0); ?></p>
                </div>
                <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
                    <span class="text-green-600 text-xl">✓</span>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all hover:shadow-md">
            <div class="flex items-center">
                <div class="flex-1">
                    <h2 class="text-gray-500 text-sm font-medium mb-2">Failed Attempts</h2>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['failed'] ?? 0); ?></p>
                </div>
                <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                    <span class="text-red-600 text-xl">✗</span>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all hover:shadow-md">
            <div class="flex items-center">
                <div class="flex-1">
                    <h2 class="text-gray-500 text-sm font-medium mb-2">Most Failed IP</h2>
                    <p class="text-xl font-bold text-orange-600 truncate" title="<?php echo $mostFailedIP ? $mostFailedIP['ip_address'] : 'N/A'; ?>">
                        <?php echo $mostFailedIP ? htmlspecialchars($mostFailedIP['ip_address']) : 'N/A'; ?>
                    </p>
                    <?php if($mostFailedIP): ?>
                        <p class="text-sm text-gray-600"><?php echo $mostFailedIP['attempts']; ?> attempts</p>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
                    <span class="text-orange-600 text-xl">⚠️</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <form action="login-attempts.php" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Attempts</option>
                        <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>Successful</option>
                        <option value="failure" <?php echo $status === 'failure' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                
                <div>
                    <label for="days" class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                    <select id="days" name="days" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1" <?php echo $days === 1 ? 'selected' : ''; ?>>Last 24 Hours</option>
                        <option value="7" <?php echo $days === 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30" <?php echo $days === 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="90" <?php echo $days === 90 ? 'selected' : ''; ?>>Last 90 Days</option>
                        <option value="0" <?php echo $days === 0 ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </div>
                
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Email or IP address" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="flex flex-wrap gap-4 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 shadow-sm">
                    Apply Filters
                </button>
                
                <?php if (!empty($search) || $status !== 'all' || $days !== 7): ?>
                    <a href="login-attempts.php" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200 shadow-sm">
                        Reset Filters
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Login Attempts Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($loginAttempts) === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-5 text-center text-gray-500">No login attempts found matching your criteria</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($loginAttempts as $attempt): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($attempt['email']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($attempt['ip_address']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $attempt['status'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($attempt['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($attempt['user_agent']); ?>">
                                        <?php echo htmlspecialchars(substr($attempt['user_agent'], 0, 50)) . (strlen($attempt['user_agent']) > 50 ? '...' : ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i a', strtotime($attempt['attempt_time'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex justify-center">
            <nav class="inline-flex rounded-lg shadow-sm" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&days=<?php echo $days; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-3 py-2 rounded-l-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&days=<?php echo $days; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $page ? 'bg-blue-50 text-blue-600 border-blue-500 z-10' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&days=<?php echo $days; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-3 py-2 rounded-r-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>