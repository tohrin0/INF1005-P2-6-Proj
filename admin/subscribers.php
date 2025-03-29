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

<div class="container mx-auto px-4 py-6">
    <div class="admin-page-header">
        <h1>Newsletter Subscribers</h1>
        <p>Manage and track newsletter subscribers</p>
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
    
    <!-- Subscriber Stats Cards -->
    <div class="admin-grid-3 mb-6">
        <!-- Total Subscribers -->
        <div class="admin-metric-card admin-metric-primary">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Total Subscribers</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['total'] ?? 0); ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-primary">
                <i class="fas fa-users"></i>
            </div>
        </div>
        
        <!-- Active Subscribers -->
        <div class="admin-metric-card admin-metric-success">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Active Subscribers</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['active'] ?? 0); ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-success">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
        
        <!-- Inactive Subscribers -->
        <div class="admin-metric-card admin-metric-warning">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Inactive Subscribers</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['inactive'] ?? 0); ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-warning">
                <i class="fas fa-user-slash"></i>
            </div>
        </div>
    </div>
    
    <!-- Subscribers Table -->
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h2>Subscribers List</h2>
            <div class="header-actions flex flex-wrap gap-2">
                <a href="?status=all" class="px-3 py-1 rounded-full text-sm <?php echo $status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    All
                </a>
                <a href="?status=subscribed" class="px-3 py-1 rounded-full text-sm <?php echo $status === 'subscribed' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?> transition">
                    Active
                </a>
                <a href="?status=unsubscribed" class="px-3 py-1 rounded-full text-sm <?php echo $status === 'unsubscribed' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> transition">
                    Inactive
                </a>
                
                <form action="subscribers.php" method="GET" class="ml-auto flex items-center gap-2">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Search by email or name" 
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="?status=<?php echo htmlspecialchars($status); ?>" class="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
                
                <a href="send-newsletter.php" class="px-3 py-1.5 rounded-md text-sm bg-green-600 text-white hover:bg-green-700 transition">
                    <i class="fas fa-paper-plane mr-1"></i> Send Newsletter
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Email</th>
                        <th scope="col">Name</th>
                        <th scope="col">Status</th>
                        <th scope="col">Subscribed Date</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="subscriberTableBody">
                    <?php if (empty($subscribers)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">No subscribers found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900"><?php echo !empty($subscriber['first_name']) ? htmlspecialchars($subscriber['first_name']) : 'N/A'; ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $subscriber['status'] === 'subscribed' ? 'status-badge-success' : 'status-badge-warning'; ?>">
                                        <?php echo ucfirst($subscriber['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($subscriber['subscribed_at'])); ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <button type="submit" class="admin-action-btn admin-action-edit" title="<?php echo $subscriber['status'] === 'subscribed' ? 'Unsubscribe' : 'Subscribe'; ?>">
                                                <i class="fas fa-<?php echo $subscriber['status'] === 'subscribed' ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this subscriber?');">
                                            <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="admin-action-btn admin-action-delete" title="Delete">
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
        
        <?php if ($totalPages > 1): ?>
            <div class="admin-card-footer flex justify-center">
                <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo htmlspecialchars($status); ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($status); ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 border border-gray-300 <?php echo $i === $page ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo htmlspecialchars($status); ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Simple search functionality for immediate filtering
    document.getElementById('search').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>