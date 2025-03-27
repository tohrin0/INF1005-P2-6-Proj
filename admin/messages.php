<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/ContactMessage.php';
require_once '../inc/session.php';

verifyAdminSession();
// Initialize ContactMessage class
$messageObj = new ContactMessage();

// Handle message status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read']) && isset($_POST['message_id'])) {
        $messageObj->updateStatus($_POST['message_id'], 'read');
    } elseif (isset($_POST['mark_responded']) && isset($_POST['message_id'])) {
        $messageObj->updateStatus($_POST['message_id'], 'responded');
    } elseif (isset($_POST['delete']) && isset($_POST['message_id'])) {
        $messageObj->delete($_POST['message_id']);
    }
    header('Location: messages.php');
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

// Get status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get messages for current page
$messages = $messageObj->getAllMessages($statusFilter, $page, $perPage);

// Get total message count for pagination
$totalMessages = $messageObj->getTotalCount($statusFilter);
$totalPages = ceil($totalMessages / $perPage);

include 'includes/header.php';
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Manage Messages</h1>
    
    <div class="flex space-x-2 mb-6">
        <a href="?status=all" class="filter-btn <?= $statusFilter === 'all' ? 'active bg-blue-600 text-white' : '' ?> px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 transition">All</a>
        <a href="?status=unread" class="filter-btn <?= $statusFilter === 'unread' ? 'active bg-red-600 text-white' : '' ?> px-4 py-2 rounded-md bg-red-100 hover:bg-red-200 transition">Unread</a>
        <a href="?status=read" class="filter-btn <?= $statusFilter === 'read' ? 'active bg-green-600 text-white' : '' ?> px-4 py-2 rounded-md bg-green-100 hover:bg-green-200 transition">Read</a>
        <a href="?status=responded" class="filter-btn <?= $statusFilter === 'responded' ? 'active bg-blue-600 text-white' : '' ?> px-4 py-2 rounded-md bg-blue-100 hover:bg-blue-200 transition">Responded</a>
    </div>
    
    <div class="space-y-4">
        <?php if (empty($messages)): ?>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center">
                <p class="text-gray-500">No messages found.</p>
            </div>
        <?php endif; ?>
        
        <?php foreach ($messages as $msg): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($msg['subject']); ?></h2>
                        <span class="px-3 py-1 text-xs rounded-full <?php 
                            if ($msg['status'] === 'unread') echo 'bg-red-100 text-red-800';
                            elseif ($msg['status'] === 'read') echo 'bg-green-100 text-green-800';
                            else echo 'bg-blue-100 text-blue-800';
                        ?>">
                            <?= ucfirst($msg['status']); ?>
                        </span>
                    </div>
                    
                    <div class="flex flex-wrap gap-4 text-sm text-gray-500 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-user h-5 w-5 mr-1"></i>
                            <span><?= htmlspecialchars($msg['name']); ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope h-5 w-5 mr-1"></i>
                            <span><?= htmlspecialchars($msg['email']); ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock h-5 w-5 mr-1"></i>
                            <span><?= date('M j, Y H:i', strtotime($msg['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="py-4 border-t border-b border-gray-100 text-gray-700 mb-4">
                        <?= nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                    
                    <div class="flex space-x-2">
                        <form method="POST" class="inline-flex">
                            <input type="hidden" name="message_id" value="<?= $msg['id']; ?>">
                            <?php if ($msg['status'] === 'unread'): ?>
                                <button type="submit" name="mark_read" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition">
                                    <i class="fas fa-check h-5 w-5 mr-1.5"></i> Mark Read
                                </button>
                            <?php endif; ?>
                            <?php if ($msg['status'] !== 'responded'): ?>
                                <button type="submit" name="mark_responded" class="inline-flex items-center px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium rounded-md transition">
                                    <i class="fas fa-reply h-5 w-5 mr-1.5"></i> Mark Responded
                                </button>
                            <?php endif; ?>
                            <button type="submit" name="delete" class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium rounded-md transition" 
                                    onclick="return confirm('Are you sure you want to delete this message?')">
                                <i class="fas fa-trash h-5 w-5 mr-1.5"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-8">
            <div class="inline-flex rounded-md shadow">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?status=<?= $statusFilter ?>&page=<?= $i ?>" 
                      class="<?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> px-4 py-2 text-sm font-medium border <?= $i === 1 ? 'rounded-l-md' : '' ?> <?= $i === $totalPages ? 'rounded-r-md' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>