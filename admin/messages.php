<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Handle message status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read']) && isset($_POST['message_id'])) {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$_POST['message_id']]);
    } elseif (isset($_POST['mark_responded']) && isset($_POST['message_id'])) {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'responded' WHERE id = ?");
        $stmt->execute([$_POST['message_id']]);
    } elseif (isset($_POST['delete']) && isset($_POST['message_id'])) {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$_POST['message_id']]);
    }
    header('Location: messages.php');
    exit();
}

// Fetch messages without using LIMIT/OFFSET parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// For MariaDB/MySQL compatibility, hardcode the limit and offset values in the SQL query
$query = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT " . $perPage . " OFFSET " . $offset;
$stmt = $pdo->query($query);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total message count for pagination
$stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
$totalMessages = $stmt->fetchColumn();
$totalPages = ceil($totalMessages / $perPage);

include 'includes/header.php';
?>

<div class="container">
    <h1>Manage Messages</h1>
    
    <div class="messages-filters">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="unread">Unread</button>
        <button class="filter-btn" data-filter="read">Read</button>
        <button class="filter-btn" data-filter="responded">Responded</button>
    </div>

    <div class="messages-container">
        <?php foreach ($messages as $msg): ?>
            <div class="message-card <?php echo $msg['status']; ?>">
                <div class="message-header">
                    <h3><?php echo htmlspecialchars($msg['subject']); ?></h3>
                    <span class="status-badge <?php echo $msg['status']; ?>">
                        <?php echo ucfirst($msg['status']); ?>
                    </span>
                </div>
                <div class="message-meta">
                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($msg['name']); ?></span>
                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo date('M j, Y H:i', strtotime($msg['created_at'])); ?></span>
                </div>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                </div>
                <div class="message-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                        <?php if ($msg['status'] === 'unread'): ?>
                            <button type="submit" name="mark_read" class="btn-action">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        <?php endif; ?>
                        <?php if ($msg['status'] !== 'responded'): ?>
                            <button type="submit" name="mark_responded" class="btn-action">
                                <i class="fas fa-reply"></i> Mark as Responded
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="delete" class="btn-action delete" 
                                onclick="return confirm('Are you sure you want to delete this message?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.messages-filters {
    margin-bottom: 20px;
}

.filter-btn {
    padding: 8px 15px;
    margin-right: 10px;
    border: none;
    border-radius: 4px;
    background: #f8f9fa;
    cursor: pointer;
}

.filter-btn.active {
    background: #007bff;
    color: white;
}

.message-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
}

.status-badge.unread { background: #ffeeba; color: #856404; }
.status-badge.read { background: #d4edda; color: #155724; }
.status-badge.responded { background: #cce5ff; color: #004085; }

.message-meta {
    display: flex;
    gap: 20px;
    color: #6c757d;
    margin-bottom: 15px;
}

.message-content {
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    margin-bottom: 15px;
}

.message-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background: #e9ecef;
    color: #495057;
}

.btn-action:hover {
    background: #dee2e6;
}

.btn-action.delete {
    background: #dc3545;
    color: white;
}

.btn-action.delete:hover {
    background: #c82333;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.pagination a {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-decoration: none;
    color: #007bff;
}

.pagination a.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const messageCards = document.querySelectorAll('.message-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter messages
            messageCards.forEach(card => {
                if (filter === 'all' || card.classList.contains(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>