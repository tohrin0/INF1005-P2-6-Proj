<?php
header('Content-Type: application/json');

// Simulate successful response
echo json_encode([
    'success' => true,
    'message' => 'AJAX is working correctly',
    'timestamp' => time()
]);