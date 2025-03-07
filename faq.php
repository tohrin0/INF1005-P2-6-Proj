<?php
// FAQ Page for Flight Booking Website
session_start();
include 'inc/config.php';
include 'inc/db.php';
include 'inc/functions.php';

// Fetch FAQ items from database
try {
    $stmt = $pdo->prepare("SELECT id, question, answer FROM faq ORDER BY display_order ASC");
    $stmt->execute();
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error but don't display to users
    error_log("Database error in FAQ page: " . $e->getMessage());
    $faqs = []; // Empty array if database query fails
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Frequently Asked Questions (FAQ)</h1>
    
    <?php if (empty($faqs)): ?>
        <p>No FAQ items found. Please check back later.</p>
    <?php else: ?>
        <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <h2><?php echo htmlspecialchars($faq['question']); ?></h2>
                <p><?php echo htmlspecialchars($faq['answer']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .faq-item {
        background-color: #f8f9fa;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .faq-item h2 {
        color: #007bff;
        font-size: 1.2rem;
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .faq-item p {
        line-height: 1.6;
        color: #333;
    }
</style>

<?php
include 'templates/footer.php';
?>