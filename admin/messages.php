<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/ContactMessage.php';
require_once '../inc/session.php';

verifyAdminSession();

// Handle actions (if any)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['message_id'])) {
        $messageId = $_POST['message_id'];
        
        if ($_POST['action'] === 'delete') {
            // Delete message
            $result = ContactMessage::delete($messageId);
            if ($result) {
                $_SESSION['admin_message'] = "Message deleted successfully.";
            } else {
                $_SESSION['admin_error'] = "Failed to delete message.";
            }
        } elseif ($_POST['action'] === 'mark_read') {
            // Mark message as read
            $result = ContactMessage::markAsRead($messageId);
            if ($result) {
                $_SESSION['admin_message'] = "Message marked as read.";
            } else {
                $_SESSION['admin_error'] = "Failed to update message status.";
            }
        } elseif ($_POST['action'] === 'mark_unread') {
            // Mark message as unread
            $result = ContactMessage::markAsUnread($messageId);
            if ($result) {
                $_SESSION['admin_message'] = "Message marked as unread.";
            } else {
                $_SESSION['admin_error'] = "Failed to update message status.";
            }
        }
        
        // Redirect to refresh the page
        header('Location: messages.php');
        exit();
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get messages based on filters
if ($status === 'read') {
    $messages = ContactMessage::getReadMessages();
} elseif ($status === 'unread') {
    $messages = ContactMessage::getUnreadMessages();
} else {
    $messages = ContactMessage::getAllMessages();
}

// Apply search filter if present
if (!empty($search)) {
    $filteredMessages = [];
    foreach ($messages as $message) {
        if (stripos($message['name'], $search) !== false || 
            stripos($message['email'], $search) !== false || 
            stripos($message['subject'], $search) !== false || 
            stripos($message['message'], $search) !== false) {
            $filteredMessages[] = $message;
        }
    }
    $messages = $filteredMessages;
}

// Get message counts
$totalMessages = count(ContactMessage::getAllMessages());
$unreadMessages = count(ContactMessage::getUnreadMessages());
$readMessages = count(ContactMessage::getReadMessages());

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="admin-page-header">
        <h1>Contact Messages</h1>
        <p>View and manage customer inquiries and messages</p>
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
    
    <!-- Messages Stats Cards -->
    <div class="admin-grid-3 mb-6">
        <!-- Total Messages -->
        <div class="admin-metric-card admin-metric-primary">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Total Messages</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($totalMessages); ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-primary">
                <i class="fas fa-envelope"></i>
            </div>
        </div>
        
        <!-- Unread Messages -->
        <div class="admin-metric-card admin-metric-danger">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Unread Messages</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($unreadMessages); ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-danger">
                <i class="fas fa-envelope-open"></i>
            </div>
        </div>
        
        <!-- Read Messages -->
        <div class="admin-metric-card admin-metric-success">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Read Messages</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($readMessages); ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    
    <!-- Messages Table -->
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h2>Messages List</h2>
            <div class="header-actions flex flex-wrap gap-2">
                <a href="?status=all" class="px-3 py-1 rounded-full text-sm <?php echo $status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    All
                </a>
                <a href="?status=unread" class="px-3 py-1 rounded-full text-sm <?php echo $status === 'unread' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> transition">
                    Unread
                </a>
                <a href="?status=read" class="px-3 py-1 rounded-full text-sm <?php echo $status === 'read' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?> transition">
                    Read
                </a>
                
                <form action="messages.php" method="GET" class="ml-auto flex items-center gap-2">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Search messages" 
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
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Status</th>
                        <th scope="col">From</th>
                        <th scope="col">Subject</th>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="messageTableBody">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">No messages found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <tr class="<?php echo $message['is_read'] ? '' : 'font-semibold bg-gray-50'; ?>">
                                <td>
                                    <span class="status-badge <?php echo $message['is_read'] ? 'status-badge-success' : 'status-badge-danger'; ?>">
                                        <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($message['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($message['email']); ?></div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo mb_substr(htmlspecialchars($message['message']), 0, 50) . (mb_strlen($message['message']) > 50 ? '...' : ''); ?></div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="view-message.php?id=<?php echo $message['id']; ?>" class="admin-action-btn admin-action-view" title="View Message">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($message['status'] === 'read'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <input type="hidden" name="action" value="mark_unread">
                                                <button type="submit" class="admin-action-edit" title="Mark as Unread">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <input type="hidden" name="action" value="mark_read">
                                                <button type="submit" class="admin-action-edit" title="Mark as Read">
                                                    <i class="fas fa-envelope-open"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="admin-action-delete" title="Delete">
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
</div>

<script>
    // Simple search functionality
    document.getElementById('search').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>