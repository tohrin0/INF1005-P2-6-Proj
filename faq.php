<?php
// FAQ Page for Flight Booking Website
session_start();
include 'inc/config.php';
include 'inc/functions.php';
include 'templates/header.php';
?>

<div class="container">
    <h1>Frequently Asked Questions (FAQ)</h1>
    
    <div class="faq-item">
        <h2>What is the flight booking process?</h2>
        <p>The flight booking process involves searching for flights, selecting your preferred flight, entering passenger details, and making a payment.</p>
    </div>

    <div class="faq-item">
        <h2>How can I change or cancel my booking?</h2>
        <p>You can change or cancel your booking by logging into your account and navigating to the 'My Bookings' section. Please note that cancellation policies may apply.</p>
    </div>

    <div class="faq-item">
        <h2>What payment methods are accepted?</h2>
        <p>We accept various payment methods including credit cards, debit cards, and PayPal. Please check the payment page for more details.</p>
    </div>

    <div class="faq-item">
        <h2>How do I contact customer support?</h2>
        <p>You can contact our customer support team via the contact form on our website or by emailing support@flightbooking.com.</p>
    </div>

    <div class="faq-item">
        <h2>Is my personal information safe?</h2>
        <p>Yes, we take your privacy seriously. We use encryption and secure protocols to protect your personal information.</p>
    </div>
</div>

<?php
include 'templates/footer.php';
?>