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
    if (isset($_POST['action']) && isset($_POST['subscriber_id'])) {
        $subscriberId = $_POST['subscriber_id'];
        
        if ($_POST['action'] === 'delete') {
            // Delete the subscriber
            $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
            if ($stmt->execute([$subscriberId])) {
                $_SESSION['admin_message'] = "Subscriber deleted successfully.";
            } else {
                $_SESSION['admin_error'] = "Failed to delete subscriber.";
            }
        } elseif ($_POST['action'] === 'toggle_status') {
            // Toggle subscription status
            $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = CASE WHEN status = 'subscribed' THEN 'unsubscribed' ELSE 'subscribed' END, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$subscriberId])) {
                $_SESSION['admin_message'] = "Subscriber status updated successfully.";
            } else {
                $_SESSION['admin_error'] = "Failed to update subscriber status.";
            }
        }
        
        // Redirect to refresh the page
        header('Location: subscribers.php');
        exit();
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare the query
$query = "SELECT * FROM newsletter_subscribers WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (email LIKE ? OR first_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY subscribed_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
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
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total active and inactive subscribers
$countStmt = $pdo->query("SELECT 
    SUM(CASE WHEN status = 'subscribed' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as inactive,
    COUNT(*) as total
    FROM newsletter_subscribers");
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Newsletter Subscribers</h1>
        <a href="send-newsletter.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
            <i class="fas fa-paper-plane mr-2"></i> Send Newsletter
        </a>
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
    
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-gray-500 text-sm mb-1">Total Subscribers</p>
            <p class="text-2xl font-bold"><?php echo number_format($counts['total'] ?? 0); ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-gray-500 text-sm mb-1">Active Subscribers</p>
            <p class="text-2xl font-bold text-green-600"><?php echo number_format($counts['active'] ?? 0); ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-gray-500 text-sm mb-1">Inactive Subscribers</p>
            <p class="text-2xl font-bold text-gray-500"><?php echo number_format($counts['inactive'] ?? 0); ?></p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
        <form action="subscribers.php" method="GET" class="flex flex-col sm:flex-row items-center gap-4">
            <div class="w-full sm:w-auto">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Subscribers</option>
                    <option value="subscribed" <?php echo $status === 'subscribed' ? 'selected' : ''; ?>>Subscribed</option>
                    <option value="unsubscribed" <?php echo $status === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by email or name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-full sm:w-auto self-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Filter
                </button>
            </div>
            <?php if (!empty($search) || $status !== 'all'): ?>
                <div class="w-full sm:w-auto self-end">
                    <a href="subscribers.php" class="inline-block w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-center">
                        Reset
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Subscribers Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribed Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($subscribers) === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No subscribers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo !empty($subscriber['first_name']) ? htmlspecialchars($subscriber['first_name']) : 'N/A'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $subscriber['status'] === 'subscribed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($subscriber['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($subscriber['subscribed_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <form method="POST">
                                            <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <button type="submit" class="text-blue-600 hover:text-blue-900" title="<?php echo $subscriber['status'] === 'subscribed' ? 'Unsubscribe' : 'Subscribe'; ?>">
                                                <i class="fas fa-<?php echo $subscriber['status'] === 'subscribed' ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this subscriber?');">
                                            <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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
            <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $page ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>