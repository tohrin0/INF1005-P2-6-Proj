<?php
/**
 * OpenRouter Chat API Endpoint
 * This file securely handles calls to the OpenRouter API without exposing the API key in frontend code
 */

// Ensure this script is only called via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Set error handling to prevent HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Include the main configuration file - adjust path if needed
    require_once __DIR__ . '/../inc/config.php';
    
    // Get the API key - fix the key access method
    $api_keys = json_decode(OPENROUTER_API_KEYS, true);
    $api_key = $api_keys[0]; // Use the first key
    
    if (empty($api_key)) {
        throw new Exception("API key not found");
    }
    
    // Get the posted JSON data
    $json_data = file_get_contents('php://input');
    $request_data = json_decode($json_data, true);
    
    // Basic validation
    if (!$request_data || !isset($request_data['messages'])) {
        throw new Exception("Invalid request format");
    }
    
    // Set up the OpenRouter API request
    $api_url = 'https://openrouter.ai/api/v1/chat/completions';
    $request_body = json_encode([
        'model' => 'openai/gpt-3.5-turbo', // Changed to a model that's definitely available
        'messages' => $request_data['messages'],
        'max_tokens' => 250, // Limiting response length for chat
        'temperature' => 0.7 // Balanced between creative and consistent
    ]);
    
    // Initialize cURL
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'HTTP-Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : SITE_URL),
        'X-Title: SkyBooker Chat Assistant'
    ]);
    
    // Add additional debug logging
    error_log('Sending request to OpenRouter: ' . $request_body);
    
    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the response for debugging
    error_log('OpenRouter response code: ' . $http_code . ', Response: ' . substr($response, 0, 200) . '...');
    
    // Handle errors
    if ($http_code !== 200 || !$response) {
        throw new Exception('OpenRouter API Error: ' . $curl_error . ' HTTP Code: ' . $http_code . ' Response: ' . $response);
    }
    
    // Return the API response
    header('Content-Type: application/json');
    echo $response;

} catch (Exception $e) {
    // Log the error with more details
    error_log('Chat API Error: ' . $e->getMessage());
    
    // Return a properly formatted JSON error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Failed to get response from AI service',
        'debug_message' => $e->getMessage()
    ]);
    exit;
}