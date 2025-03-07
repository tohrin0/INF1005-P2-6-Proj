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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequently Asked Questions - Flight Booking Website</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about our flight booking services</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <h2>Booking & Reservations</h2>
            
            <?php if (empty($faqs)): ?>
                <p>No FAQ items found. Please check back later.</p>
            <?php else: ?>
                <div class="faq-container">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(<?php echo $index; ?>)">
                                <?php echo htmlspecialchars($faq['question']); ?> 
                                <i class="fas fa-chevron-down" style="float: right;"></i>
                            </div>
                            <div class="faq-answer" id="faq-<?php echo $index; ?>" style="display: none;">
                                <?php echo htmlspecialchars($faq['answer']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="content-section">
            <h2>Still Have Questions?</h2>
            <p>If you couldn't find the answer to your question, please don't hesitate to contact our customer support team.</p>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="contact.php" class="submit-btn">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleFaq(index) {
            const answer = document.getElementById('faq-' + index);
            const questions = document.querySelectorAll('.faq-question');
            const arrows = document.querySelectorAll('.fa-chevron-down');
            
            if (answer.style.display === 'none') {
                answer.style.display = 'block';
                questions[index].style.backgroundColor = '#e9ecef';
                arrows[index].style.transform = 'rotate(180deg)';
            } else {
                answer.style.display = 'none';
                questions[index].style.backgroundColor = '#f8f9fa';
                arrows[index].style.transform = 'rotate(0deg)';
            }
        }
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>