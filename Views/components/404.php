<div class="container mx-auto px-4 py-16 text-center">
    <h1 class="text-4xl font-bold text-gray-800 mb-4">404 - Page Not Found</h1>
    <p class="text-lg text-gray-600 mb-8">The page you are looking for does not exist or has been moved.</p>
    <p class="text-sm text-gray-500 mb-8"><?php if (isset($request_path)): ?>Request path: <?php echo htmlspecialchars($request_path); ?><?php endif; ?></p>
    <a href="index.php" class="btn-primary">Go Back to Home</a>
</div>