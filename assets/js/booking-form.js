// filepath: /flight-booking-website/assets/js/booking-form.js

document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('booking-form');
    
    bookingForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        if (validateForm()) {
            const formData = new FormData(bookingForm);
            submitBooking(formData);
        }
    });

    function validateForm() {
        let isValid = true;
        const inputs = bookingForm.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            if (!input.checkValidity()) {
                isValid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        return isValid;
    }

    function submitBooking(formData) {
        fetch('/booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Booking successful!');
                window.location.href = 'confirmation.php';
            } else {
                alert('Booking failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your booking.');
        });
    }
});