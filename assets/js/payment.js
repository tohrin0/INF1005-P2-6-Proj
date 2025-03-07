// filepath: /flight-booking-website/assets/js/payment.js
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-payment');

    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault();
        validatePaymentForm();
    });

    function validatePaymentForm() {
        const cardNumber = document.getElementById('card-number').value;
        const expiryDate = document.getElementById('expiry-date').value;
        const cvv = document.getElementById('cvv').value;

        let isValid = true;

        if (!isCardNumberValid(cardNumber)) {
            isValid = false;
            alert('Invalid card number.');
        }

        if (!isExpiryDateValid(expiryDate)) {
            isValid = false;
            alert('Invalid expiry date.');
        }

        if (!isCvvValid(cvv)) {
            isValid = false;
            alert('Invalid CVV.');
        }

        if (isValid) {
            processPayment(cardNumber, expiryDate, cvv);
        }
    }

    function isCardNumberValid(cardNumber) {
        // Basic validation for card number (length and digits)
        return /^\d{16}$/.test(cardNumber);
    }

    function isExpiryDateValid(expiryDate) {
        // Basic validation for expiry date (MM/YY format)
        return /^(0[1-9]|1[0-2])\/\d{2}$/.test(expiryDate);
    }

    function isCvvValid(cvv) {
        // Basic validation for CVV (3 digits)
        return /^\d{3}$/.test(cvv);
    }

    function processPayment(cardNumber, expiryDate, cvv) {
        // Simulate payment processing
        alert('Payment processed successfully!');
        // Here you would typically send the payment data to the server
    }
});