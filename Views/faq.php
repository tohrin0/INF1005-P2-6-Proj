<?php
// FAQ Page for Flight Booking Website

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
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-16 px-4 mb-8">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold mb-4">Frequently Asked Questions</h1>
            <p class="text-xl text-blue-100">Find answers to common questions about our flight booking services</p>
        </div>
    </div>

    <div class="container mx-auto px-4 mb-16">
        <div class="bg-white rounded-xl shadow-sm mb-8 p-8">
            <h2 class="text-2xl font-bold mb-6">Booking & Reservations</h2>

            <?php if (empty($faqs)): ?>
                <p class="text-gray-600">No FAQ items found. Please check back later.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="border border-gray-200 rounded-md overflow-hidden">
                            <div class="flex justify-between items-center bg-gray-50 p-4 cursor-pointer hover:bg-gray-100 transition-colors"
                                 onclick="toggleFaq(<?php echo $index; ?>)">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($faq['question']); ?>
                                </h3>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform duration-300"></i>
                            </div>
                            <div id="faq-<?php echo $index; ?>" class="p-4 bg-white hidden">
                                <p class="text-gray-700 leading-relaxed">
                                    <?php echo htmlspecialchars($faq['answer']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <h2 class="text-2xl font-bold mb-4">Still Have Questions?</h2>
            <p class="text-gray-700 mb-6">If you couldn't find the answer to your question, please don't hesitate to contact our customer support team.</p>

            <div class="text-center">
                <a href="contact.php" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors">
                    <i class="fas fa-envelope mr-2"></i> Contact Us
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleFaq(index) {
            const answer = document.getElementById('faq-' + index);
            const questions = document.querySelectorAll('.faq-question');
            const arrows = document.querySelectorAll('.fa-chevron-down');

            if (answer.classList.contains('hidden')) {
                answer.classList.remove('hidden');
                arrows[index].style.transform = 'rotate(180deg)';
            } else {
                answer.classList.add('hidden');
                arrows[index].style.transform = 'rotate(0deg)';
            }
        }
    </script>

    <?php include 'templates/footer.php'; ?>
</body>

</html>