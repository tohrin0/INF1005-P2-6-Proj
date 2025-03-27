<?php
/**
 * Mail helper functions
 */

require_once __DIR__ . '/../classes/EmailNotification.php';

/**
 * Send an email using the EmailNotification class
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @return bool Whether the email was sent successfully
 */
function sendEmail($to, $subject, $message) {
    // Create EmailNotification instance
    $emailer = new EmailNotification();
    
    // Use the sendCustomEmail method to send the email
    return $emailer->sendCustomEmail($to, $subject, $message);
}